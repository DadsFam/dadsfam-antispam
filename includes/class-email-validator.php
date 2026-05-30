<?php
/**
 * Email Validator — checks MX records and disposable email domains.
 * Free: ~50 common disposable domains built-in.
 * PRO: 1 500+ domain database + automatic updates.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class DFSAS_EmailValidator {

    private array $opts;

    // Built-in free-tier list (most common disposable services)
    private const DISPOSABLE_DOMAINS_FREE = [
        'mailinator.com','guerrillamail.com','guerrillamail.net','guerrillamail.org',
        'trashmail.com','trashmail.net','trashmail.org','trashmail.me',
        'yopmail.com','yopmail.fr','cool.fr.nf','jetable.fr.nf',
        'spam4.me','getairmail.com','fakeinbox.com','throwawaymailnow.com',
        'throwaway.email','tempmail.com','temp-mail.org','tempmail.net',
        'dispostable.com','mailnull.com','spamgourmet.com','spamgourmet.net',
        'sharklasers.com','guerrillamailblock.com','grr.la','guerrillamail.info',
        'spam.la','spam.su','binkmail.com','bob.email','mt2009.com',
        'maildrop.cc','mytrashmail.com','throwam.com','throwit.net',
        'spamherelots.com','spam.org.es','spamoff.de','spamthisplease.com',
        '10minutemail.com','10minutemail.net','10minutemail.org','10minutemail.de',
        'tempr.email','discard.email','spamwc.de','spambin.com',
        'anonbox.net','filzmail.com','no-spam.ws','nospamfor.us',
    ];

    public static function get_free_list(): array {
        return self::DISPOSABLE_DOMAINS_FREE;
    }

    public function __construct( array $opts ) {
        $this->opts = $opts;
        $this->register_hooks();
    }

    private function register_hooks(): void {
        add_filter( 'wpcf7_spam',             [ $this, 'check_cf7'          ], 5, 2 );
        add_action( 'wpforms_process_before', [ $this, 'check_wpforms'      ], 5, 2 );
        add_filter( 'registration_errors',    [ $this, 'check_registration' ], 7, 3 );
    }

    // ─── Core Checks ──────────────────────────────────────────────────────────

    public function is_disposable( string $email ): bool {
        if ( empty( $this->opts['block_disposable_emails'] ) ) return false;

        $domain = DFSAS_Helpers::email_domain( $email );
        if ( ! $domain ) return false;

        $list = DFSAS_Helpers::is_pro()
            ? $this->get_pro_disposable_list()
            : self::DISPOSABLE_DOMAINS_FREE;

        return in_array( strtolower( $domain ), $list, true );
    }

    public function has_mx_record( string $email ): bool {
        if ( empty( $this->opts['check_mx_records'] ) ) return true;

        $domain = DFSAS_Helpers::email_domain( $email );
        if ( ! $domain ) return false;

        // Cache result per domain for this request
        static $cache = [];
        if ( isset( $cache[ $domain ] ) ) return $cache[ $domain ];

        $cache[ $domain ] = checkdnsrr( $domain, 'MX' ) || checkdnsrr( $domain, 'A' );
        return $cache[ $domain ];
    }

    public function validate( string $email ): array {
        if ( ! $email || ! is_email( $email ) ) {
            return [ 'valid' => false, 'reason' => 'invalid_email_format' ];
        }

        if ( $this->is_disposable( $email ) ) {
            return [ 'valid' => false, 'reason' => 'disposable_email' ];
        }

        if ( ! $this->has_mx_record( $email ) ) {
            return [ 'valid' => false, 'reason' => 'no_mx_record' ];
        }

        return [ 'valid' => true, 'reason' => '' ];
    }

    // ─── Integration Hooks ────────────────────────────────────────────────────

    public function check_cf7( bool $spam, $submission ): bool {
        if ( $spam ) return true;
        $email  = sanitize_email( $_POST['your-email'] ?? '' );
        $result = $this->validate( $email );
        if ( ! $result['valid'] ) {
            DFSAS_Logger::log( [
                'form_type' => 'contact-form-7',
                'ip'        => DFSAS_Helpers::get_client_ip(),
                'email'     => $email,
                'reason'    => $result['reason'],
                'score'     => 10,
            ] );
            return true;
        }
        return false;
    }

    public function check_wpforms( array $fields, array $form_data ): void {
        $email = '';
        foreach ( $fields as $field ) {
            if ( $field['type'] === 'email' ) { $email = $field['value'] ?? ''; break; }
        }
        if ( ! $email ) return;

        $result = $this->validate( $email );
        if ( ! $result['valid'] ) {
            DFSAS_Logger::log( [
                'form_type' => 'wpforms',
                'ip'        => DFSAS_Helpers::get_client_ip(),
                'email'     => $email,
                'reason'    => $result['reason'],
                'score'     => 10,
            ] );
            wpforms()->process->errors['header'] = __( 'Invalid email address.', 'dadsfam-antispam' );
        }
    }

    public function check_registration( \WP_Error $errors, string $login, string $email ): \WP_Error {
        $result = $this->validate( $email );
        if ( ! $result['valid'] ) {
            DFSAS_Logger::log( [
                'form_type' => 'wp-registration',
                'ip'        => DFSAS_Helpers::get_client_ip(),
                'email'     => $email,
                'reason'    => $result['reason'],
                'score'     => 10,
            ] );
            $errors->add( 'dfsas_email', __( '<strong>Error</strong>: Invalid email address.', 'dadsfam-antispam' ) );
        }
        return $errors;
    }

    // ─── PRO: Extended List ────────────────────────────────────────────────────

    private function get_pro_disposable_list(): array {
        // First choice: list fetched by DFSAS_ListUpdater (stored as WP option)
        $fetched = DFSAS_ListUpdater::get_domains();
        if ( ! empty( $fetched ) ) {
            return $fetched;
        }

        // Fallback: bundled extended list file (shipped with PRO package)
        $file = DFSAS_PATH . 'data/disposable-domains.txt';
        if ( file_exists( $file ) ) {
            $list = file( $file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
            return array_map( 'strtolower', $list );
        }

        // Last resort: fall back to free list
        return self::DISPOSABLE_DOMAINS_FREE;
    }
}
