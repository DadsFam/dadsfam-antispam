<?php
/**
 * Pagelayer / Softaculous contact form integration.
 *
 * Hooks into Pagelayer's AJAX submission BEFORE it processes.
 * Does NOT instantiate other module classes (they're already booted by Core
 * and their hooks fire automatically). We just do lightweight inline checks
 * here using static helpers — no risk of double hook registration.
 *
 * If Pagelayer is not installed these hooks simply never fire.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class DFSAS_Pagelayer {

    private array $opts;

    public function __construct( array $opts ) {
        $this->opts = $opts;
        $this->register_hooks();
    }

    private function register_hooks(): void {
        // Priority 1 — run before Pagelayer processes the submission
        add_action( 'wp_ajax_nopriv_pagelayer_contact_form', [ $this, 'check' ], 1 );
        add_action( 'wp_ajax_pagelayer_contact_form',        [ $this, 'check' ], 1 );
        add_action( 'wp_ajax_nopriv_pagelayer_send_email',   [ $this, 'check' ], 1 );
        add_action( 'wp_ajax_pagelayer_send_email',          [ $this, 'check' ], 1 );
    }

    public function check(): void {
        $ip    = DFSAS_Helpers::get_client_ip();
        $email = sanitize_email( $_POST['email'] ?? $_POST['your-email'] ?? '' );
        $msg   = sanitize_textarea_field( $_POST['message'] ?? $_POST['your-message'] ?? '' );

        // ── 1. Honeypot ───────────────────────────────────────────────────────
        if ( ! empty( $this->opts['enable_honeypot'] ) ) {
            $hp = DFSAS_Helpers::honeypot_field_name();
            if ( isset( $_POST[ $hp ] ) && '' !== $_POST[ $hp ] ) {
                $this->block( 'honeypot_filled', $ip, $email );
            }
        }

        // ── 2. Time Check ─────────────────────────────────────────────────────
        if ( ! empty( $this->opts['enable_time_check'] ) ) {
            $raw = sanitize_text_field( $_POST[ DFSAS_Helpers::timestamp_field_name() ] ?? '' );
            if ( $raw ) {
                $submitted = DFSAS_Helpers::decrypt_timestamp( $raw );
                $min       = max( 1, (int) ( $this->opts['time_check_min_seconds'] ?? 3 ) );
                if ( ! $submitted || ( time() - $submitted ) < $min ) {
                    $this->block( 'submitted_too_fast', $ip, $email );
                }
            }
        }

        // ── 3. Rate Limiter (transient-based, no class instantiation needed) ──
        if ( ! empty( $this->opts['enable_rate_limiter'] ) ) {
            $max     = max( 1, (int) ( $this->opts['rate_limit_max']     ?? 5 ) );
            $window  = max( 60, (int) ( $this->opts['rate_limit_window'] ?? 3600 ) );
            $lockout = max( 60, (int) ( $this->opts['rate_limit_lockout']?? 86400 ) );
            $lk      = 'dfsas_lo_' . md5( $ip );
            $rk      = 'dfsas_rl_' . md5( $ip );

            if ( get_transient( $lk ) !== false ) {
                $this->block( 'rate_limited', $ip, $email );
            }
            $count = (int) get_transient( $rk );
            $count === 0
                ? set_transient( $rk, 1, $window )
                : set_transient( $rk, $count + 1, $window );
            if ( ( $count + 1 ) > $max ) {
                set_transient( $lk, 1, $lockout );
                $this->block( 'rate_limited', $ip, $email );
            }
        }

        // ── 4. Blocklist (direct array checks, no class hooks) ────────────────
        if ( ! empty( $this->opts['enable_blocklist'] ) ) {
            $blocked_ips = DFSAS_Helpers::textarea_to_array( $this->opts['blocked_ips'] ?? '' );
            foreach ( $blocked_ips as $pattern ) {
                if ( DFSAS_Helpers::ip_matches( $ip, $pattern ) ) {
                    $this->block( 'blocked_ip', $ip, $email );
                }
            }
            if ( $email ) {
                $domain          = DFSAS_Helpers::email_domain( $email );
                $blocked_emails  = array_map( 'strtolower', DFSAS_Helpers::textarea_to_array( $this->opts['blocked_emails']  ?? '' ) );
                $blocked_domains = array_map( 'strtolower', DFSAS_Helpers::textarea_to_array( $this->opts['blocked_domains'] ?? '' ) );
                if ( in_array( strtolower( $email ), $blocked_emails, true ) ) {
                    $this->block( 'blocked_email', $ip, $email );
                }
                if ( $domain && in_array( strtolower( $domain ), $blocked_domains, true ) ) {
                    $this->block( 'blocked_email', $ip, $email );
                }
            }
            if ( $msg ) {
                foreach ( DFSAS_Helpers::textarea_to_array( $this->opts['blocked_keywords'] ?? '' ) as $kw ) {
                    if ( stripos( $msg, trim( $kw ) ) !== false ) {
                        $this->block( 'blocked_keyword', $ip, $email );
                    }
                }
            }
        }

        // ── 5. Disposable email (static list check only) ─────────────────────
        if ( ! empty( $this->opts['enable_email_validator'] ) && ! empty( $this->opts['block_disposable_emails'] ) && $email ) {
            $domain = DFSAS_Helpers::email_domain( $email );
            $list   = DFSAS_Helpers::is_pro()
                ? (array) get_option( 'dfsas_disposable_domains', [] )
                : DFSAS_EmailValidator::get_free_list();
            if ( $domain && in_array( strtolower( $domain ), $list, true ) ) {
                $this->block( 'disposable_email', $ip, $email );
            }
        }
    }

    private function block( string $reason, string $ip, string $email ): void {
        DFSAS_Logger::log( [
            'form_type' => 'pagelayer',
            'ip'        => $ip,
            'email'     => $email,
            'reason'    => $reason,
            'score'     => 10,
        ] );
        // Return JSON error that Pagelayer's JS understands
        wp_send_json( [
            'error'   => 1,
            'message' => __( 'Submission blocked. Please contact us directly if this is a mistake.', 'dadsfam-antispam' ),
        ] );
        exit;
    }
}
