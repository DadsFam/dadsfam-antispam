<?php
/**
 * Rate Limiter — blocks IPs that exceed N submissions per time window.
 * Uses WordPress transients (no extra DB table needed).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class DFSAS_RateLimiter {

    private array $opts;
    private int   $max;
    private int   $window;
    private int   $lockout;

    public function __construct( array $opts ) {
        $this->opts    = $opts;
        $this->max     = max( 1, (int) ( $opts['rate_limit_max']     ?? 5 ) );
        $this->window  = max( 60, (int) ( $opts['rate_limit_window']  ?? 3600 ) );
        $this->lockout = max( 60, (int) ( $opts['rate_limit_lockout'] ?? 86400 ) );

        $this->register_hooks();
    }

    private function register_hooks(): void {
        add_filter( 'wpcf7_spam',             [ $this, 'check_cf7'     ], 7, 2 );
        add_action( 'wpforms_process_before', [ $this, 'check_wpforms' ], 7, 2 );
        add_filter( 'registration_errors',    [ $this, 'check_registration' ], 8, 3 );
    }

    // ─── Core Logic ───────────────────────────────────────────────────────────

    private function transient_key( string $ip ): string {
        return 'dfsas_rl_' . md5( $ip );
    }

    private function lockout_key( string $ip ): string {
        return 'dfsas_lo_' . md5( $ip );
    }

    public function is_rate_limited( string $ip ): bool {
        // Always allow whitelisted IPs regardless of rate-limit state.
        $opts     = (array) get_option( 'dfsas_options', [] );
        $wl_raw   = trim( $opts['whitelisted_ips'] ?? '' );
        if ( $wl_raw ) {
            $whitelisted = array_filter( array_map( 'trim', explode( "\n", $wl_raw ) ) );
            if ( in_array( $ip, $whitelisted, true ) ) return false;
        }
        // Under lockout?
        if ( get_transient( $this->lockout_key( $ip ) ) !== false ) {
            return true;
        }
        return false;
    }

    public function increment_and_check( string $ip ): bool {
        if ( $this->is_rate_limited( $ip ) ) return true;

        $key   = $this->transient_key( $ip );
        $count = (int) get_transient( $key );

        if ( $count === 0 ) {
            set_transient( $key, 1, $this->window );
        } else {
            set_transient( $key, $count + 1, $this->window );
        }

        if ( ( $count + 1 ) > $this->max ) {
            // Impose lockout
            set_transient( $this->lockout_key( $ip ), 1, $this->lockout );
            return true;
        }

        return false;
    }

    // ─── Integration Hooks ────────────────────────────────────────────────────

    public function check_cf7( bool $spam, $submission ): bool {
        if ( $spam ) return true;
        $ip = DFSAS_Helpers::get_client_ip();
        if ( $this->increment_and_check( $ip ) ) {
            DFSAS_Logger::log( [ 'form_type' => 'contact-form-7', 'ip' => $ip, 'reason' => 'rate_limited', 'score' => 10 ] );
            return true;
        }
        return false;
    }

    public function check_wpforms( array $fields, array $form_data ): void {
        $ip = DFSAS_Helpers::get_client_ip();
        if ( $this->increment_and_check( $ip ) ) {
            DFSAS_Logger::log( [ 'form_type' => 'wpforms', 'ip' => $ip, 'reason' => 'rate_limited', 'score' => 10 ] );
            wpforms()->process->errors['header'] = __( 'Too many submissions. Please try again later.', 'dadsfam-antispam' );
        }
    }

    public function check_registration( \WP_Error $errors, string $login, string $email ): \WP_Error {
        $ip = DFSAS_Helpers::get_client_ip();
        if ( $this->increment_and_check( $ip ) ) {
            DFSAS_Logger::log( [ 'form_type' => 'wp-registration', 'ip' => $ip, 'email' => $email, 'reason' => 'rate_limited', 'score' => 10 ] );
            $errors->add( 'dfsas_rate', __( '<strong>Error</strong>: Too many attempts. Try again later.', 'dadsfam-antispam' ) );
        }
        return $errors;
    }

    // ─── Admin helpers ────────────────────────────────────────────────────────

    public static function unblock_ip( string $ip ): void {
        $key_rl = 'dfsas_rl_' . md5( $ip );
        $key_lo = 'dfsas_lo_' . md5( $ip );
        delete_transient( $key_rl );
        delete_transient( $key_lo );
    }
}
