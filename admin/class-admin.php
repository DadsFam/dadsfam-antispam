<?php
/**
 * Admin panel — menus, settings registration, AJAX handlers, asset loading.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class DFSAS_Admin {

    private array $opts;

    public function __construct( array $opts ) {
        $this->opts = $opts;
        add_action( 'admin_menu',             [ $this, 'register_menus' ] );
        add_action( 'admin_init',             [ $this, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts',  [ $this, 'enqueue_assets' ] );
        add_action( 'wp_ajax_dfsas_delete_log',          [ $this, 'ajax_delete_log'         ] );
        add_action( 'wp_ajax_dfsas_clear_logs',          [ $this, 'ajax_clear_logs'         ] );
        add_action( 'wp_ajax_dfsas_export_csv',          [ $this, 'ajax_export_csv'         ] );
        add_action( 'wp_ajax_dfsas_unblock_ip',          [ $this, 'ajax_unblock_ip'         ] );
        add_action( 'wp_ajax_dfsas_test_email',          [ $this, 'ajax_test_email'         ] );
        add_action( 'wp_ajax_dfsas_upload_domain_list',  [ $this, 'ajax_upload_domain_list' ] );
        add_action( 'wp_ajax_dfsas_quick_block',         [ $this, 'ajax_quick_block'        ] );
        add_action( 'wp_ajax_dfsas_export_settings',     [ $this, 'ajax_export_settings'   ] );
        add_action( 'wp_ajax_dfsas_import_settings',     [ $this, 'ajax_import_settings'   ] );
    }

    // ─── Menu Registration ────────────────────────────────────────────────────

    public function register_menus(): void {
        add_menu_page(
            __( 'DadsFam Anti-Spam', 'dadsfam-antispam' ),
            __( 'Anti-Spam', 'dadsfam-antispam' ),
            'manage_options',
            'dadsfam-antispam',
            [ $this, 'page_dashboard' ],
            'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#a7aaad" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>' ),
            '58.5'
        );

        add_submenu_page( 'dadsfam-antispam', __( 'Dashboard', 'dadsfam-antispam' ),  __( 'Dashboard', 'dadsfam-antispam' ),  'manage_options', 'dadsfam-antispam',    [ $this, 'page_dashboard' ] );
        add_submenu_page( 'dadsfam-antispam', __( 'Settings',  'dadsfam-antispam' ),  __( 'Settings',  'dadsfam-antispam' ),  'manage_options', 'dfsas-settings',      [ $this, 'page_settings'  ] );
        add_submenu_page( 'dadsfam-antispam', __( 'Blocklist', 'dadsfam-antispam' ),  __( 'Blocklist', 'dadsfam-antispam' ),  'manage_options', 'dfsas-blocklist',     [ $this, 'page_blocklist' ] );
        add_submenu_page( 'dadsfam-antispam', __( 'Spam Log',  'dadsfam-antispam' ),  __( 'Spam Log',  'dadsfam-antispam' ),  'manage_options', 'dfsas-logs',          [ $this, 'page_logs'      ] );
        add_submenu_page( 'dadsfam-antispam', __( 'Changelog', 'dadsfam-antispam' ),  __( 'Changelog', 'dadsfam-antispam' ),  'manage_options', 'dfsas-changelog',     [ $this, 'page_changelog' ] );
        add_submenu_page( 'dadsfam-antispam',
            __( 'PRO License', 'dadsfam-antispam' ),
            DFSAS_Helpers::is_pro()
                ? __( '⭐ PRO — Active', 'dadsfam-antispam' )
                : '<span style="color:#f05a28;font-weight:700;">⭐ Go PRO</span>',
            'manage_options',
            'dfsas-pro',
            [ $this, 'page_pro' ]
        );
    }

    // ─── Page Renderers ───────────────────────────────────────────────────────

    public function page_dashboard(): void {
        require_once DFSAS_PATH . 'admin/views/dashboard.php';
    }

    public function page_settings(): void {
        require_once DFSAS_PATH . 'admin/views/settings.php';
    }

    public function page_blocklist(): void {
        require_once DFSAS_PATH . 'admin/views/blocklist.php';
    }

    public function page_logs(): void {
        require_once DFSAS_PATH . 'admin/views/logs.php';
    }

    public function page_changelog(): void {
        require_once DFSAS_PATH . 'admin/views/changelog.php';
    }

    public function page_pro(): void {
        require_once DFSAS_PATH . 'admin/views/pro.php';
    }

    // ─── Settings API ─────────────────────────────────────────────────────────

    public function register_settings(): void {
        register_setting(
            'dfsas_options_group',
            'dfsas_options',
            [ 'sanitize_callback' => [ $this, 'sanitize_options' ] ]
        );
    }

    public function sanitize_options( array $raw ): array {
        // ── Always start from the FULL existing options ───────────────────────
        // This is critical: each form (settings / blocklist) only submits its
        // own fields. Without merging, saving the blocklist would wipe all the
        // module toggles and vice versa.
        $existing = wp_parse_args(
            (array) get_option( 'dfsas_options', [] ),
            DFSAS_Core::default_options()
        );
        $clean = $existing; // carry everything forward by default

        $context = sanitize_text_field( $raw['_context'] ?? '' );

        // ── Programmatic save (Quick Block, Import, etc.) ─────────────────────
        // These call update_option() directly with a complete, already-sanitised
        // array — they do NOT submit a form so there is no _context. Return the
        // array as-is; rebuilding it from form fields here would discard the
        // freshly-added value (e.g. a Quick Block IP) and restore the old copy.
        if ( '' === $context ) {
            unset( $raw['_context'] );
            return $raw;
        }

        // ── Blocklist form: only update the textarea fields ───────────────────
        if ( $context === 'blocklist' ) {
            $clean['blocked_ips']       = sanitize_textarea_field( $raw['blocked_ips']       ?? '' );
            $clean['blocked_emails']    = sanitize_textarea_field( $raw['blocked_emails']    ?? '' );
            $clean['blocked_domains']   = sanitize_textarea_field( $raw['blocked_domains']   ?? '' );
            $clean['blocked_keywords']  = sanitize_textarea_field( $raw['blocked_keywords']  ?? '' );
            $clean['blocked_usernames'] = sanitize_textarea_field( $raw['blocked_usernames'] ?? '' );
            $clean['block_login_ip']    = ! empty( $raw['block_login_ip'] ) ? 1 : 0;
            return $clean;
        }

        // ── Settings form: update everything EXCEPT the blocklist textareas ───
        // (those are preserved from $existing above)

        // Checkboxes
        $checkboxes = [
            'enable_honeypot','enable_time_check','enable_rate_limiter','enable_blocklist',
            'enable_content_filter','enable_email_validator','enable_dnsbl','enable_geo_block',
            'enable_log_cleanup','enable_email_digest', 'enable_auto_update', 'enable_recaptcha',
            'enable_comments',
            'comment_block_non_latin',
            'honeypot_cf7','honeypot_wpforms','honeypot_ninjaforms','honeypot_gravityforms',
            'honeypot_fluentforms','honeypot_generic',
            'block_disposable_emails','check_mx_records','block_html_in_message','log_blocked',
            'recaptcha_cf7','recaptcha_wpforms','recaptcha_ninjaforms','recaptcha_gravityforms',
            'recaptcha_fluentforms','recaptcha_wp_login','recaptcha_wp_registration',
            'recaptcha_wp_lostpassword','recaptcha_woo_checkout','recaptcha_generic',
        ];
        foreach ( $checkboxes as $key ) {
            $clean[ $key ] = ! empty( $raw[ $key ] ) ? 1 : 0;
        }

        // Integers
        $ints = [
            'time_check_min_seconds','rate_limit_max','rate_limit_window','rate_limit_lockout',
            'max_links_allowed','spam_score_threshold','log_retention_days',
        ];
        foreach ( $ints as $key ) {
            if ( isset( $raw[ $key ] ) ) {
                $clean[ $key ] = absint( $raw[ $key ] );
            }
        }

        // Textareas (non-blocklist)
        $textareas = [
            'dnsbl_servers','geo_blocked_countries','whitelisted_ips','whitelisted_emails',
        ];
        foreach ( $textareas as $key ) {
            if ( isset( $raw[ $key ] ) ) {
                $clean[ $key ] = sanitize_textarea_field( $raw[ $key ] );
            }
        }

        // Strings
        if ( isset( $raw['digest_email'] ) )
            $clean['digest_email'] = sanitize_email( $raw['digest_email'] );

        if ( isset( $raw['digest_frequency'] ) )
            $clean['digest_frequency'] = in_array( $raw['digest_frequency'], [ 'daily', 'weekly' ], true )
                ? $raw['digest_frequency'] : 'daily';

        if ( isset( $raw['domain_list_url'] ) )
            $clean['domain_list_url'] = esc_url_raw( $raw['domain_list_url'] );

        if ( isset( $raw['domain_list_frequency'] ) ) {
            $valid_frequencies = [ 'hourly', 'every_6_hours', 'every_12_hours', 'twicedaily', 'daily', 'every_3_days', 'weekly' ];
            $clean['domain_list_frequency'] = in_array( $raw['domain_list_frequency'], $valid_frequencies, true )
                ? $raw['domain_list_frequency'] : 'weekly';
        }

        // reCAPTCHA
        if ( isset( $raw['recaptcha_version'] ) )
            $clean['recaptcha_version'] = in_array( $raw['recaptcha_version'], [ 'v3', 'v2_invisible', 'v2_checkbox' ], true ) ? $raw['recaptcha_version'] : 'v3';
        if ( isset( $raw['recaptcha_site_key'] ) )
            $clean['recaptcha_site_key'] = sanitize_text_field( $raw['recaptcha_site_key'] );
        if ( isset( $raw['recaptcha_secret_key'] ) )
            $clean['recaptcha_secret_key'] = sanitize_text_field( $raw['recaptcha_secret_key'] );
        if ( isset( $raw['recaptcha_v3_threshold'] ) )
            $clean['recaptcha_v3_threshold'] = min( 0.9, max( 0.1, (float) $raw['recaptcha_v3_threshold'] ) );

        return $clean;
    }

    // ─── Asset Loading ────────────────────────────────────────────────────────

    public function enqueue_assets( string $hook ): void {
        if ( strpos( $hook, 'dadsfam-antispam' ) === false && strpos( $hook, 'dfsas-' ) === false ) return;

        wp_enqueue_style(
            'dfsas-admin',
            DFSAS_URL . 'admin/assets/admin.css',
            [],
            DFSAS_VERSION
        );

        wp_enqueue_script(
            'dfsas-admin',
            DFSAS_URL . 'admin/assets/admin.js',
            [ 'jquery' ],
            DFSAS_VERSION,
            true
        );

        wp_localize_script( 'dfsas-admin', 'dfsasAdmin', [
            'nonce'    => wp_create_nonce( 'dfsas_admin_nonce' ),
            'ajaxurl'  => admin_url( 'admin-ajax.php' ),
            'strings'  => [
                'confirm_clear'  => __( 'Clear ALL log entries? This cannot be undone.', 'dadsfam-antispam' ),
                'confirm_delete' => __( 'Delete this log entry?', 'dadsfam-antispam' ),
                'confirm_unblock'=> __( 'Unblock this IP address?', 'dadsfam-antispam' ),
                'blocked'        => __( 'Blocked', 'dadsfam-antispam' ),
                'unblocked'      => __( 'Unblocked', 'dadsfam-antispam' ),
            ],
        ] );
    }

    // ─── AJAX Handlers ────────────────────────────────────────────────────────

    private function verify_ajax(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( -1 );
        check_ajax_referer( 'dfsas_admin_nonce', 'nonce' );
    }

    public function ajax_delete_log(): void {
        $this->verify_ajax();
        $id = absint( $_POST['id'] ?? 0 );
        if ( $id ) DFSAS_Logger::delete_entry( $id );
        wp_send_json_success();
    }

    public function ajax_clear_logs(): void {
        $this->verify_ajax();
        DFSAS_Logger::clear_all();
        wp_send_json_success( [ 'message' => __( 'All log entries cleared.', 'dadsfam-antispam' ) ] );
    }

    public function ajax_export_csv(): void {
        $this->verify_ajax();
        if ( ! DFSAS_Helpers::is_pro() ) {
            wp_send_json_error( __( 'CSV export is a PRO feature.', 'dadsfam-antispam' ) );
        }
        $csv = DFSAS_Logger::export_csv();
        header( 'Content-Type: text/csv' );
        header( 'Content-Disposition: attachment; filename="dfsas-spam-log-' . date( 'Y-m-d' ) . '.csv"' );
        echo $csv; // phpcs:ignore
        exit;
    }

    public function ajax_export_settings(): void {
        $this->verify_ajax();
        $opts = get_option( 'dfsas_options', [] );
        // Never export secret keys — they're site-specific and sensitive
        unset( $opts['recaptcha_secret_key'] );
        $payload = [
            'plugin'     => 'dadsfam-antispam',
            'version'    => DFSAS_VERSION,
            'exported'   => gmdate( 'c' ),
            'options'    => $opts,
        ];
        wp_send_json_success( [ 'json' => wp_json_encode( $payload, JSON_PRETTY_PRINT ) ] );
    }

    public function ajax_import_settings(): void {
        $this->verify_ajax();

        $raw = isset( $_POST['json'] ) ? wp_unslash( $_POST['json'] ) : '';
        if ( empty( $raw ) ) {
            wp_send_json_error( __( 'No settings data received.', 'dadsfam-antispam' ) );
        }

        $data = json_decode( $raw, true );
        if ( ! is_array( $data ) || empty( $data['options'] ) || ! is_array( $data['options'] ) ) {
            wp_send_json_error( __( 'That does not look like a valid DadsFam Anti-Spam settings file.', 'dadsfam-antispam' ) );
        }
        if ( ( $data['plugin'] ?? '' ) !== 'dadsfam-antispam' ) {
            wp_send_json_error( __( 'This settings file is for a different plugin.', 'dadsfam-antispam' ) );
        }

        // Merge imported options over the defaults so any missing keys are filled,
        // and run them through the same sanitiser the settings form uses.
        $defaults = DFSAS_Core::default_options();
        $merged   = array_merge( $defaults, $data['options'] );

        // Preserve the existing secret key (it's never exported)
        $current = get_option( 'dfsas_options', [] );
        if ( ! empty( $current['recaptcha_secret_key'] ) ) {
            $merged['recaptcha_secret_key'] = $current['recaptcha_secret_key'];
        }

        update_option( 'dfsas_options', $merged );

        wp_send_json_success( [ 'message' => __( '✅ Settings imported successfully. Reloading…', 'dadsfam-antispam' ) ] );
    }

    public function ajax_unblock_ip(): void {
        $this->verify_ajax();
        $ip = sanitize_text_field( $_POST['ip'] ?? '' );
        if ( $ip ) DFSAS_RateLimiter::unblock_ip( $ip );
        wp_send_json_success();
    }

    public function ajax_quick_block(): void {
        $this->verify_ajax();

        $type  = sanitize_text_field( $_POST['block_type']  ?? '' );
        $value = sanitize_text_field( $_POST['block_value'] ?? '' );

        if ( ! $type || ! $value ) {
            wp_send_json_error( __( 'Missing type or value.', 'dadsfam-antispam' ) );
        }

        $map = [
            'ip'     => 'blocked_ips',
            'email'  => 'blocked_emails',
            'domain' => 'blocked_domains',
            'keyword'=> 'blocked_keywords',
        ];

        if ( ! isset( $map[ $type ] ) ) {
            wp_send_json_error( __( 'Invalid block type.', 'dadsfam-antispam' ) );
        }

        $opts    = get_option( 'dfsas_options', [] );
        $key     = $map[ $type ];
        $current = DFSAS_Helpers::textarea_to_array( $opts[ $key ] ?? '' );

        // Check for duplicate
        if ( in_array( strtolower( $value ), array_map( 'strtolower', $current ), true ) ) {
            wp_send_json_success( [
                'message'       => sprintf( __( '%s is already in the %s list.', 'dadsfam-antispam' ), $value, $type ),
                'already_exists'=> true,
            ] );
        }

        $current[]   = $value;
        $opts[ $key ] = DFSAS_Helpers::array_to_textarea( $current );
        update_option( 'dfsas_options', $opts );

        wp_send_json_success( [
            'message' => sprintf(
                __( '✅ %s added to %s blocklist (%d total).', 'dadsfam-antispam' ),
                esc_html( $value ),
                $type,
                count( $current )
            ),
            'count' => count( $current ),
        ] );
    }

    public function ajax_upload_domain_list(): void {
        $this->verify_ajax();
        DFSAS_ListUpdater::ajax_upload_list();
    }

    public function ajax_test_email(): void {
        $this->verify_ajax();
        $to   = get_option( 'admin_email' );
        $sent = wp_mail( $to, '[DadsFam Anti-Spam] Test Email', 'Your email is working correctly.' );
        wp_send_json_success( [ 'sent' => $sent ] );
    }
}
