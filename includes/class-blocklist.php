<?php
/**
 * Blocklist module — IP, email, domain, and keyword blocking.
 * Free: up to 100 entries per list.
 * PRO: unlimited + wildcard/CIDR IPs + whitelist bypass.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class DFSAS_Blocklist {

    private array $opts;
    private array $blocked_ips;
    private array $blocked_emails;
    private array $blocked_domains;
    private array $blocked_keywords;
    private array $whitelisted_ips;
    private array $whitelisted_emails;

    public function __construct( array $opts ) {
        $this->opts               = $opts;
        $this->blocked_ips        = DFSAS_Helpers::textarea_to_array( $opts['blocked_ips']       ?? '' );
        $this->blocked_emails     = DFSAS_Helpers::textarea_to_array( $opts['blocked_emails']    ?? '' );
        $this->blocked_domains    = DFSAS_Helpers::textarea_to_array( $opts['blocked_domains']   ?? '' );
        $this->blocked_keywords   = DFSAS_Helpers::textarea_to_array( $opts['blocked_keywords']  ?? '' );
        // Whitelisted IPs/emails are always loaded — admin must be able to unblock
        // their own IP even on free tier. PRO features (geo, DNSBL) remain gated.
        $this->whitelisted_ips    = DFSAS_Helpers::textarea_to_array( $opts['whitelisted_ips']    ?? '' );
        $this->whitelisted_emails = DFSAS_Helpers::textarea_to_array( $opts['whitelisted_emails'] ?? '' );

        // Enforce free cap
        if ( ! DFSAS_Helpers::is_pro() ) {
            $this->blocked_ips      = array_slice( $this->blocked_ips,      0, 100 );
            $this->blocked_emails   = array_slice( $this->blocked_emails,   0, 100 );
            $this->blocked_domains  = array_slice( $this->blocked_domains,  0, 100 );
            $this->blocked_keywords = array_slice( $this->blocked_keywords, 0, 50  );
        }

        $this->register_hooks();
    }

    private function register_hooks(): void {
        add_filter( 'wpcf7_spam',             [ $this, 'check_cf7'     ], 8, 2 );
        add_action( 'wpforms_process_before', [ $this, 'check_wpforms' ], 8, 2 );
        add_filter( 'registration_errors',    [ $this, 'check_registration' ], 9, 3 );
        add_filter( 'wp_mail',                [ $this, 'filter_wp_mail' ] );
    }

    // ─── Core Checkers ────────────────────────────────────────────────────────

    public function check_ip( string $ip ): bool {
        if ( $this->is_whitelisted_ip( $ip ) ) return false;
        foreach ( $this->blocked_ips as $pattern ) {
            if ( DFSAS_Helpers::ip_matches( $ip, $pattern ) ) return true;
        }
        return false;
    }

    public function check_email( string $email ): bool {
        $email  = strtolower( trim( $email ) );
        $domain = DFSAS_Helpers::email_domain( $email );

        if ( $this->is_whitelisted_email( $email ) ) return false;

        // Exact email match
        if ( in_array( $email, array_map( 'strtolower', $this->blocked_emails ), true ) ) return true;

        // Domain match
        if ( $domain && in_array( $domain, array_map( 'strtolower', $this->blocked_domains ), true ) ) return true;

        return false;
    }

    public function check_content( string $text ): array {
        $text_lower = strtolower( $text );
        foreach ( $this->blocked_keywords as $keyword ) {
            $kw = strtolower( trim( $keyword ) );
            if ( $kw && stripos( $text_lower, $kw ) !== false ) {
                return [ 'blocked' => true, 'keyword' => $keyword ];
            }
        }
        return [ 'blocked' => false, 'keyword' => '' ];
    }

    private function is_whitelisted_ip( string $ip ): bool {
        foreach ( $this->whitelisted_ips as $pattern ) {
            if ( DFSAS_Helpers::ip_matches( $ip, $pattern ) ) return true;
        }
        return false;
    }

    private function is_whitelisted_email( string $email ): bool {
        return in_array( strtolower( $email ), array_map( 'strtolower', $this->whitelisted_emails ), true );
    }

    // ─── Integration Hooks ────────────────────────────────────────────────────

    public function check_cf7( bool $spam, $submission ): bool {
        if ( $spam ) return true;
        return $this->run_checks( 'contact-form-7' );
    }

    public function check_wpforms( array $fields, array $form_data ): void {
        if ( $this->run_checks( 'wpforms' ) ) {
            wpforms()->process->errors['header'] = __( 'Submission blocked.', 'dadsfam-antispam' );
        }
    }

    public function check_registration( \WP_Error $errors, string $login, string $email ): \WP_Error {
        $ip = DFSAS_Helpers::get_client_ip();

        if ( $this->check_ip( $ip ) ) {
            DFSAS_Logger::log( [ 'form_type' => 'wp-registration', 'ip' => $ip, 'email' => $email, 'reason' => 'blocked_ip', 'score' => 10 ] );
            $errors->add( 'dfsas_blocked', __( '<strong>Error</strong>: Registration blocked.', 'dadsfam-antispam' ) );
        } elseif ( $this->check_email( $email ) ) {
            DFSAS_Logger::log( [ 'form_type' => 'wp-registration', 'ip' => $ip, 'email' => $email, 'reason' => 'blocked_email', 'score' => 10 ] );
            $errors->add( 'dfsas_blocked', __( '<strong>Error</strong>: Registration blocked.', 'dadsfam-antispam' ) );
        }

        return $errors;
    }

    public function filter_wp_mail( array $atts ): array {
        // Never filter emails sent by admin/shop-manager users.
        // This covers invoice plugin resends, WooCommerce order emails,
        // and any other internal plugin communication. The keyword filter
        // is only intended to block spam from public form submissions.
        if ( current_user_can( 'manage_options' ) || current_user_can( 'manage_woocommerce' ) ) {
            return $atts;
        }

        // Also skip during WordPress cron jobs (scheduled WC emails etc.)
        if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
            return $atts;
        }

        $body    = $atts['message'] ?? '';
        $subject = $atts['subject'] ?? '';
        $check   = $this->check_content( $body . ' ' . $subject );

        if ( $check['blocked'] ) {
            DFSAS_Logger::log( [
                'form_type' => 'wp-mail',
                'ip'        => DFSAS_Helpers::get_client_ip(),
                'subject'   => $subject,
                'reason'    => 'blocked_keyword',
                'details'   => [ 'keyword' => $check['keyword'] ],
                'score'     => 8,
            ] );
            // Cancel the email cleanly via pre_wp_mail (WP 5.7+) instead of
            // setting an invalid TO address which causes a PHPMailer exception.
            // We use a self-removing closure so only this one email is cancelled.
            $cancel = null;
            $cancel = function() use ( &$cancel ) {
                remove_filter( 'pre_wp_mail', $cancel, 99 );
                return false; // false = cancel this send
            };
            add_filter( 'pre_wp_mail', $cancel, 99 );
        }
        return $atts;
    }

    // ─── Shared run_checks ────────────────────────────────────────────────────

    private function run_checks( string $form_type ): bool {
        $ip    = DFSAS_Helpers::get_client_ip();
        $email = sanitize_email( $_POST['your-email'] ?? $_POST['email'] ?? $_POST['wpforms']['fields']['email'] ?? '' );

        if ( is_array( $email ) ) $email = reset( $email );

        if ( $this->check_ip( $ip ) ) {
            DFSAS_Logger::log( [ 'form_type' => $form_type, 'ip' => $ip, 'email' => $email, 'reason' => 'blocked_ip',    'score' => 10 ] );
            return true;
        }

        if ( $email && $this->check_email( $email ) ) {
            DFSAS_Logger::log( [ 'form_type' => $form_type, 'ip' => $ip, 'email' => $email, 'reason' => 'blocked_email', 'score' => 10 ] );
            return true;
        }

        // Keyword check on POST message fields
        $message = sanitize_textarea_field( $_POST['your-message'] ?? $_POST['message'] ?? '' );
        if ( $message ) {
            $check = $this->check_content( $message );
            if ( $check['blocked'] ) {
                DFSAS_Logger::log( [
                    'form_type' => $form_type,
                    'ip'        => $ip,
                    'email'     => $email,
                    'reason'    => 'blocked_keyword',
                    'details'   => [ 'keyword' => $check['keyword'] ],
                    'score'     => 8,
                ] );
                return true;
            }
        }

        return false;
    }
}
