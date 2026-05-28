<?php
/**
 * DFSAS_License — remote license verification, force-lock REST endpoint,
 * hourly background re-check, and AJAX activate/deactivate.
 *
 * Matches the Invoice Manager two-factor pattern exactly:
 *   1. Stored status option must equal 'active'.
 *   2. Stored HMAC fingerprint must match a fresh hash of key + site URL + wp_salt.
 *      Simply flipping the status option in the DB is NOT enough to unlock PRO.
 *
 * How is_pro() is unlocked:
 *   add_filter( 'dfsas_is_pro', [ DFSAS_License::class, 'filter_is_pro' ] );
 *   (registered in init() below — no extra code needed in DFSAS_Helpers)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class DFSAS_License {

    // ── Option keys ───────────────────────────────────────────────────────────
    const OPT_KEY       = 'dfsas_license_key';
    const OPT_STATUS    = 'dfsas_license_status';
    const OPT_EXPIRY    = 'dfsas_license_expiry';
    const OPT_HASH      = 'dfsas_license_hash';
    const TRANSIENT     = 'dfsas_lic_cache';
    const CRON_HOOK     = 'dfsas_license_recheck';
    const VERIFY_URL    = 'https://dadsfam.co.za/wp-json/dfem-licenses/v1/verify';
    const PRODUCT_SLUG  = 'dadsfam-antispam';

    public function init(): void {
        // Hook is_pro filter
        add_filter( 'dfsas_is_pro', [ $this, 'filter_is_pro' ] );

        // Force-lock REST endpoint (called by dadsfam.co.za license server)
        add_action( 'rest_api_init', [ $this, 'register_rest_endpoint' ] );

        // Hourly background re-verify
        add_filter( 'cron_schedules',     [ $this, 'add_cron_interval' ] );
        add_action( self::CRON_HOOK,      [ $this, 'background_verify' ] );
        add_action( 'admin_init',         [ $this, 'maybe_schedule_cron' ], 99 );

        // AJAX
        add_action( 'wp_ajax_dfsas_license_activate',   [ $this, 'ajax_activate'   ] );
        add_action( 'wp_ajax_dfsas_license_deactivate', [ $this, 'ajax_deactivate' ] );
    }

    // ── Filter hook — powers is_pro() ─────────────────────────────────────────

    public function filter_is_pro( bool $current ): bool {
        if ( $current ) return true; // already true from somewhere else
        return $this->is_licensed();
    }

    // ── Two-factor license check ───────────────────────────────────────────────

    public function is_licensed(): bool {
        if ( get_option( self::OPT_STATUS, '' ) !== 'active' ) return false;

        $key  = get_option( self::OPT_KEY,  '' );
        $hash = get_option( self::OPT_HASH, '' );
        if ( empty( $key ) || empty( $hash ) ) return false;

        $expected = hash_hmac(
            'sha256',
            'dfsas|active|' . $key . '|' . home_url(),
            wp_salt( 'secure_auth' )
        );
        return hash_equals( $expected, $hash );
    }

    private function write_hash( string $key ): void {
        $hash = hash_hmac(
            'sha256',
            'dfsas|active|' . $key . '|' . home_url(),
            wp_salt( 'secure_auth' )
        );
        update_option( self::OPT_HASH, $hash, false );
    }

    private function clear_hash(): void {
        delete_option( self::OPT_HASH );
    }

    // ── Remote verification ────────────────────────────────────────────────────

    public function verify_remote( string $key ): array {
        if ( empty( trim( $key ) ) ) {
            return [ 'valid' => false, 'status' => 'invalid', 'expires' => '', 'message' => 'No license key entered.' ];
        }

        $response = wp_remote_post( self::VERIFY_URL, [
            'timeout' => 10,
            'body'    => [
                'license_key' => $key,
                'site_url'    => home_url(),
                'plugin_ver'  => DFSAS_VERSION,
                'plugin_slug' => self::PRODUCT_SLUG,
            ],
        ] );

        if ( is_wp_error( $response ) ) {
            // Network error — keep existing status rather than locking out
            return [ 'valid' => false, 'status' => 'error', 'expires' => '', 'message' => 'Could not reach license server. Will retry in 1 hour.' ];
        }

        $body    = json_decode( wp_remote_retrieve_body( $response ), true );
        $valid   = ! empty( $body['valid'] );
        $expires = $body['expires'] ?? 'never';
        $message = $body['message'] ?? '';
        $status  = $valid ? 'active'
            : ( stripos( $message, 'suspend' ) !== false ? 'suspended' : 'invalid' );

        return compact( 'valid', 'status', 'expires', 'message' );
    }

    public function get_cached_status(): array {
        $cached = get_transient( self::TRANSIENT );
        if ( $cached !== false ) return (array) $cached;

        $key = get_option( self::OPT_KEY, '' );
        if ( empty( trim( $key ) ) ) {
            return [ 'valid' => false, 'status' => '', 'expires' => '', 'message' => 'No license key saved.' ];
        }

        $result = $this->verify_remote( $key );

        // Don't wipe a working license on a network error
        if ( $result['status'] === 'error' ) {
            set_transient( self::TRANSIENT, [ 'valid' => $this->is_licensed(), 'status' => 'error', 'expires' => '', 'message' => $result['message'] ], HOUR_IN_SECONDS );
            return $result;
        }

        $this->persist( $key, $result );
        return $result;
    }

    private function persist( string $key, array $result ): void {
        update_option( self::OPT_STATUS, $result['status'], false );
        update_option( self::OPT_EXPIRY, $result['expires'], false );
        delete_transient( self::TRANSIENT );

        if ( $result['valid'] ) {
            update_option( self::OPT_KEY, $key, false );
            $this->write_hash( $key );
            set_transient( self::TRANSIENT, $result, HOUR_IN_SECONDS );
        } else {
            $this->clear_hash();
        }
    }

    // ── Force-Lock REST Endpoint ───────────────────────────────────────────────
    // Called by dadsfam.co.za license server when a key is suspended.
    // Route: POST /wp-json/dflm/v1/force-lock
    // Body:  key = license_key

    public function register_rest_endpoint(): void {
        register_rest_route( 'dflm/v1', '/force-lock', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'rest_force_lock' ],
            'permission_callback' => '__return_true',
        ] );
    }

    public function rest_force_lock( WP_REST_Request $req ): WP_REST_Response {
        $provided = sanitize_text_field( $req->get_param( 'key' ) ?? '' );
        $stored   = get_option( self::OPT_KEY, '' );

        if ( empty( $provided ) || empty( $stored ) || ! hash_equals( $stored, $provided ) ) {
            return new WP_REST_Response( [ 'success' => false, 'message' => 'Invalid key' ], 403 );
        }

        update_option( self::OPT_STATUS, 'suspended', false );
        update_option( self::OPT_EXPIRY, '',          false );
        $this->clear_hash();
        delete_transient( self::TRANSIENT );

        return new WP_REST_Response( [ 'success' => true, 'message' => 'PRO features locked' ], 200 );
    }

    // ── Cron: hourly background re-verify ─────────────────────────────────────

    public function add_cron_interval( array $schedules ): array {
        if ( ! isset( $schedules['every_hour'] ) ) {
            $schedules['every_hour'] = [
                'interval' => HOUR_IN_SECONDS,
                'display'  => __( 'Every Hour', 'dadsfam-antispam' ),
            ];
        }
        return $schedules;
    }

    public function maybe_schedule_cron(): void {
        if ( empty( get_option( self::OPT_KEY ) ) ) return;
        if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
            wp_schedule_event( time(), 'every_hour', self::CRON_HOOK );
        }
    }

    public function background_verify(): void {
        $key = get_option( self::OPT_KEY, '' );
        if ( empty( $key ) ) return;
        $result = $this->verify_remote( $key );
        if ( $result['status'] !== 'error' ) {
            $this->persist( $key, $result );
        }
    }

    public static function unschedule(): void {
        wp_clear_scheduled_hook( self::CRON_HOOK );
    }

    // ── AJAX: Activate ────────────────────────────────────────────────────────

    public function ajax_activate(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( -1 );
        check_ajax_referer( 'dfsas_admin_nonce', 'nonce' );

        $key = sanitize_text_field( $_POST['key'] ?? '' );
        if ( empty( trim( $key ) ) ) {
            wp_send_json_error( __( 'Please enter a license key.', 'dadsfam-antispam' ) );
        }

        $result = $this->verify_remote( $key );
        $this->persist( $key, $result );

        if ( $result['valid'] ) {
            // Schedule cron now that we have a key
            if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
                wp_schedule_event( time(), 'every_hour', self::CRON_HOOK );
            }
            wp_send_json_success( [
                'message' => __( '✅ License activated! PRO features are now unlocked.', 'dadsfam-antispam' ),
                'expires' => $result['expires'],
                'reload'  => true,
            ] );
        } else {
            wp_send_json_error( $result['message'] ?: __( 'License key is invalid or suspended.', 'dadsfam-antispam' ) );
        }
    }

    // ── AJAX: Deactivate ──────────────────────────────────────────────────────

    public function ajax_deactivate(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( -1 );
        check_ajax_referer( 'dfsas_admin_nonce', 'nonce' );

        delete_option( self::OPT_KEY );
        delete_option( self::OPT_STATUS );
        delete_option( self::OPT_EXPIRY );
        $this->clear_hash();
        delete_transient( self::TRANSIENT );
        self::unschedule();

        wp_send_json_success( [
            'message' => __( 'License key removed. PRO features deactivated.', 'dadsfam-antispam' ),
            'reload'  => true,
        ] );
    }

    // ── Helper for admin views ────────────────────────────────────────────────

    public static function get_display_status(): array {
        $status  = get_option( self::OPT_STATUS, '' );
        $key     = get_option( self::OPT_KEY, '' );
        $expires = get_option( self::OPT_EXPIRY, '' );

        return [
            'key'       => $key ? '•••••••••••' . substr( $key, -8 ) : '',
            'key_full'  => $key,
            'status'    => $status,
            'expires'   => $expires ?: 'never',
            'active'    => $status === 'active',
        ];
    }
}
