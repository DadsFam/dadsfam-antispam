<?php
/**
 * Core orchestrator — boots all modules, manages options.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class DFSAS_Core {

    private static ?DFSAS_Core $_instance = null;
    private array $options = [];

    public static function instance(): self {
        if ( null === self::$_instance ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    private function __construct() {}

    public function init(): void {
        $this->options = $this->get_options();

        // ── Always register: strip our injected field names from ANY outgoing email ──
        // Runs unconditionally — CF7, Pagelayer, WPForms, any form builder.
        // Our rotating field names always match wp_[8 hex chars] so this is safe.
        add_filter( 'wp_mail', [ $this, 'clean_outgoing_mail' ], 1 );

        // Admin panel (always)
        if ( is_admin() ) {
            new DFSAS_Admin( $this->options );
        }

        // Front-end & AJAX modules
        if ( ! is_admin() || wp_doing_ajax() ) {
            $this->load_modules();
        }

        // Scheduled tasks
        $this->schedule_tasks();

        // Plugin action links
        add_filter( 'plugin_action_links_' . DFSAS_BASENAME, [ $this, 'action_links' ] );
    }

    // ─── Module Loading ───────────────────────────────────────────────────────

    private function load_modules(): void {
        $opts = $this->options;

        if ( $opts['enable_honeypot'] )        new DFSAS_Honeypot( $opts );
        if ( $opts['enable_time_check'] )      new DFSAS_TimeCheck( $opts );
        if ( $opts['enable_rate_limiter'] )    new DFSAS_RateLimiter( $opts );
        if ( $opts['enable_blocklist'] )       new DFSAS_Blocklist( $opts );
        if ( $opts['enable_content_filter'] )  new DFSAS_ContentFilter( $opts );
        if ( $opts['enable_email_validator'] ) new DFSAS_EmailValidator( $opts );
        // reCAPTCHA — FREE, boots if keys are configured
        if ( $opts['enable_recaptcha'] )       new DFSAS_ReCaptcha( $opts );
        // Comment spam protection — FREE
        if ( $opts['enable_comments'] )        new DFSAS_Comments( $opts );
        // Pagelayer / Softaculous form builder
        new DFSAS_Pagelayer( $opts );

        // PRO-only modules
        if ( DFSAS_Helpers::is_pro() ) {
            if ( $opts['enable_dnsbl'] )     new DFSAS_DNSBL( $opts );
            if ( $opts['enable_geo_block'] ) new DFSAS_GeoBlock( $opts );
            // Always boot the list updater when PRO — it self-schedules
            new DFSAS_ListUpdater( $opts );
        }
    }

    // ─── Scheduled Tasks ──────────────────────────────────────────────────────

    private function schedule_tasks(): void {
        if ( ! DFSAS_Helpers::is_pro() ) return;

        $opts = $this->options;

        // Log cleanup
        if ( $opts['enable_log_cleanup'] && ! wp_next_scheduled( 'dfsas_cleanup_logs' ) ) {
            wp_schedule_event( time(), 'daily', 'dfsas_cleanup_logs' );
        }
        add_action( 'dfsas_cleanup_logs', [ 'DFSAS_Logger', 'cleanup_old_logs' ] );

        // Email digest
        if ( $opts['enable_email_digest'] && ! wp_next_scheduled( 'dfsas_email_digest' ) ) {
            wp_schedule_event( time(), $opts['digest_frequency'] ?: 'daily', 'dfsas_email_digest' );
        }
        add_action( 'dfsas_email_digest', [ $this, 'send_digest_email' ] );
    }

    // ─── Email Digest (PRO) ───────────────────────────────────────────────────

    public function send_digest_email(): void {
        $stats = DFSAS_Logger::get_stats();
        $to    = $this->options['digest_email'] ?: get_option( 'admin_email' );

        if ( ! $stats['today'] ) return; // nothing to report

        $subject = sprintf( '[DadsFam Anti-Spam] %d spam submissions blocked today', $stats['today'] );
        $body    = "Your daily spam protection summary:\n\n";
        $body   .= "• Blocked today:     {$stats['today']}\n";
        $body   .= "• Blocked this week: {$stats['this_week']}\n";
        $body   .= "• All-time total:    {$stats['total']}\n\n";
        $body   .= 'View full log: ' . admin_url( 'admin.php?page=dfsas-logs' ) . "\n";

        wp_mail( $to, $subject, $body );
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function clean_outgoing_mail( array $atts ): array {
        if ( empty( $atts['message'] ) ) return $atts;
        $msg = $atts['message'];
        // Remove lines matching our rotating field name pattern: wp_ + 8 hex chars
        $msg = preg_replace( '/^wp_[0-9a-f]{8}\s*:.*\r?\n?/mi', '', $msg );
        // Remove reCAPTCHA token lines
        $msg = preg_replace( '/^(dfsas_rc_token|g-recaptcha-response)\s*:.*\r?\n?/mi', '', $msg );
        // Clean up extra blank lines left behind
        $msg = preg_replace( '/(\r?\n){3,}/', "\n\n", $msg );
        $atts['message'] = $msg;
        return $atts;
    }

    public function get_options(): array {
        return wp_parse_args(
            (array) get_option( 'dfsas_options', [] ),
            self::default_options()
        );
    }

    public function option( string $key, $default = false ) {
        return $this->options[ $key ] ?? $default;
    }

    public function action_links( array $links ): array {
        $plugin_links = [
            '<a href="' . admin_url( 'admin.php?page=dadsfam-antispam' ) . '">' . __( 'Settings', 'dadsfam-antispam' ) . '</a>',
        ];
        if ( ! DFSAS_Helpers::is_pro() ) {
            $plugin_links[] = '<a href="' . admin_url( 'admin.php?page=dfsas-pro' ) . '" style="color:#f05a28;font-weight:700;">' . __( 'Upgrade to PRO', 'dadsfam-antispam' ) . '</a>';
        }
        return array_merge( $plugin_links, $links );
    }

    // ─── Default Options ──────────────────────────────────────────────────────

    public static function default_options(): array {
        return [
            // ── reCAPTCHA (FREE) ─────────────────────────────────────────────
            'enable_recaptcha'          => 0,
            'recaptcha_version'         => 'v3',
            'recaptcha_site_key'        => '',
            'recaptcha_secret_key'      => '',
            'recaptcha_v3_threshold'    => 0.5,
            'recaptcha_cf7'             => 1,
            'recaptcha_wpforms'         => 1,
            'recaptcha_ninjaforms'      => 1,
            'recaptcha_gravityforms'    => 1,
            'recaptcha_fluentforms'     => 1,
            'recaptcha_wp_login'        => 1,
            'recaptcha_wp_registration' => 1,
            'recaptcha_wp_lostpassword' => 1,
            'recaptcha_woo_checkout'    => 0,
            'recaptcha_generic'         => 1,
            // ── Modules ──────────────────────────────────────────────────────
            'enable_honeypot'         => 1,
            'enable_time_check'       => 1,
            'enable_rate_limiter'     => 1,
            'enable_blocklist'        => 1,
            'enable_content_filter'   => 1,
            'enable_email_validator'  => 1,
            'enable_comments'         => 1,  // comment spam protection
            'comment_block_non_latin' => 0,  // opt-in: flag Cyrillic/CJK/Arabic comments
            'enable_dnsbl'            => 0,  // PRO
            'enable_geo_block'        => 0,  // PRO
            'enable_log_cleanup'      => 0,  // PRO
            'enable_email_digest'     => 0,  // PRO
            // ── Honeypot ─────────────────────────────────────────────────────
            'honeypot_cf7'            => 1,
            'honeypot_wpforms'        => 1,
            'honeypot_ninjaforms'     => 1,
            'honeypot_gravityforms'   => 1,
            'honeypot_fluentforms'    => 1,
            'honeypot_generic'        => 1,  // JS injection on all forms
            // ── Time Check ───────────────────────────────────────────────────
            'time_check_min_seconds'  => 3,
            // ── Rate Limiter ─────────────────────────────────────────────────
            'rate_limit_max'          => 5,
            'rate_limit_window'       => 3600,   // 1 hour
            'rate_limit_lockout'      => 86400,  // 24 hours after exceeding
            // ── Blocklists ───────────────────────────────────────────────────
            'blocked_ips'             => '',
            'block_login_ip'          => 1,  // also block blocklisted IPs at login
            'blocked_emails'          => '',
            'blocked_domains'         => '',
            'blocked_usernames'       => "admin\nadministrator\ntest\nseo\nsupport\nwebmaster",
            'blocked_keywords'        => "casino\nviagra\ncialis\npayday loan\nbuy followers\nseo backlinks\ncheap seo\ncrypto investment\nbinary options",
            // ── Content Filter ───────────────────────────────────────────────
            'max_links_allowed'       => 2,
            'block_html_in_message'   => 1,
            'spam_score_threshold'    => 5,
            // ── Email Validator ───────────────────────────────────────────────
            'block_disposable_emails' => 1,
            'check_mx_records'        => 1,
            // ── PRO: DNSBL ────────────────────────────────────────────────────
            'dnsbl_servers'           => "zen.spamhaus.org\nbl.spamcop.net\ndnsbl.sorbs.net",
            // ── PRO: Geo Block ────────────────────────────────────────────────
            'geo_blocked_countries'   => '',
            // ── PRO: Whitelist ────────────────────────────────────────────────
            'whitelisted_ips'         => '',
            'whitelisted_emails'      => '',
            // ── PRO: Log Cleanup ──────────────────────────────────────────────
            'log_retention_days'      => 30,
            // ── PRO: Email Digest ─────────────────────────────────────────────
            'digest_email'            => '',
            'digest_frequency'        => 'daily',
            // ── PRO: Auto-Update Domain List ──────────────────────────────────
            'enable_auto_update'      => 1,
            'domain_list_url'         => 'https://dadsfam.co.za/anti-spam/disposable-domains.txt',
            'domain_list_frequency'   => 'weekly',
            // ── Logging ───────────────────────────────────────────────────────
            'log_blocked'             => 1,
        ];
    }
}
