<?php
/**
 * List Updater — PRO feature.
 *
 * Fetches a fresh disposable-email domain list from a remote URL
 * (hosted on dadsfam.co.za) on a schedule and stores it locally.
 * Free plan always uses the hardcoded 50-domain list in class-email-validator.php.
 *
 * How it works:
 *  1. A WP-Cron event fires once a week (configurable).
 *  2. It fetches a plain .txt file — one domain per line.
 *  3. The list is stored in a WordPress option so it survives cache clears.
 *  4. class-email-validator.php reads from this option automatically (PRO).
 *  5. Admins can also trigger a manual update from the Settings page.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class DFSAS_ListUpdater {

    const OPTION_DOMAINS    = 'dfsas_disposable_domains';   // stored domain list
    const OPTION_LAST_CHECK = 'dfsas_domains_last_updated'; // timestamp
    const OPTION_LAST_COUNT = 'dfsas_domains_last_count';   // how many were fetched
    const CRON_HOOK         = 'dfsas_update_domain_list';

    // Default remote URL — host this file on dadsfam.co.za
    // Format: plain text, one domain per line, UTF-8
    const DEFAULT_URL = 'https://dadsfam.co.za/anti-spam/disposable-domains.txt';

    private string $remote_url;

    public function __construct( array $opts ) {
        if ( ! DFSAS_Helpers::is_pro() ) return;

        $this->remote_url = trim( $opts['domain_list_url'] ?? self::DEFAULT_URL );
        if ( ! filter_var( $this->remote_url, FILTER_VALIDATE_URL ) ) {
            $this->remote_url = self::DEFAULT_URL;
        }

        $this->schedule();

        // AJAX handler for manual "Update Now" button
        add_action( 'wp_ajax_dfsas_update_domain_list', [ $this, 'ajax_manual_update' ] );
    }

    // ─── Scheduling ───────────────────────────────────────────────────────────

    private function schedule(): void {
        // Register custom intervals on EVERY request so WP-Cron always knows about them
        add_filter( 'cron_schedules', [ self::class, 'add_cron_intervals' ] );

        // Register the cron callback
        add_action( self::CRON_HOOK, [ $this, 'run_update' ] );

        // Reschedule IMMEDIATELY when settings are saved — no page reload needed
        add_action( 'update_option_dfsas_options', [ self::class, 'reschedule_on_save' ], 10, 2 );

        // Initial schedule if nothing is set yet
        $opts      = get_option( 'dfsas_options', [] );
        $frequency = $opts['domain_list_frequency'] ?? 'weekly';
        $enabled   = ! empty( $opts['enable_auto_update'] );

        if ( $enabled && ! wp_next_scheduled( self::CRON_HOOK ) ) {
            wp_schedule_event( time(), $frequency, self::CRON_HOOK );
        } elseif ( ! $enabled ) {
            wp_clear_scheduled_hook( self::CRON_HOOK );
        }
    }

    /**
     * Fires the moment dfsas_options is saved.
     * Clears and reschedules with the new frequency immediately.
     */
    public static function reschedule_on_save( $old_value, $new_value ): void {
        $new_freq    = $new_value['domain_list_frequency'] ?? 'weekly';
        $old_freq    = $old_value['domain_list_frequency'] ?? 'weekly';
        $new_enabled = ! empty( $new_value['enable_auto_update'] );
        $old_enabled = ! empty( $old_value['enable_auto_update'] );

        // Only act if something changed
        if ( $new_freq === $old_freq && $new_enabled === $old_enabled ) return;

        // Clear all existing instances cleanly
        wp_clear_scheduled_hook( self::CRON_HOOK );

        // Reschedule with new frequency if still enabled
        if ( $new_enabled ) {
            wp_schedule_event( time(), $new_freq, self::CRON_HOOK );
        }
    }

    public static function add_cron_intervals( array $schedules ): array {
        $custom = [
            'every_6_hours'  => [ 'interval' => 6  * HOUR_IN_SECONDS, 'display' => 'Every 6 Hours'  ],
            'every_12_hours' => [ 'interval' => 12 * HOUR_IN_SECONDS, 'display' => 'Every 12 Hours' ],
            'every_3_days'   => [ 'interval' => 3  * DAY_IN_SECONDS,  'display' => 'Every 3 Days'   ],
        ];
        foreach ( $custom as $key => $val ) {
            if ( ! isset( $schedules[ $key ] ) ) {
                $schedules[ $key ] = $val;
            }
        }
        return $schedules;
    }

    public static function unschedule(): void {
        $ts = wp_next_scheduled( self::CRON_HOOK );
        if ( $ts ) wp_unschedule_event( $ts, self::CRON_HOOK );
    }

    // ─── Core Fetch ───────────────────────────────────────────────────────────

    /**
     * Fetch the remote list and store it.
     * Returns array with status info.
     */
    public function run_update(): array {
        $response = wp_remote_get( $this->remote_url, [
            'timeout'    => 15,
            'user-agent' => 'DadsFam-AntiSpam/' . DFSAS_VERSION . '; ' . get_bloginfo('url'),
            'sslverify'  => true,
        ] );

        if ( is_wp_error( $response ) ) {
            return [
                'success' => false,
                'message' => 'Fetch failed: ' . $response->get_error_message(),
                'count'   => 0,
            ];
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code !== 200 ) {
            return [
                'success' => false,
                'message' => "Remote server returned HTTP {$code}.",
                'count'   => 0,
            ];
        }

        $body = wp_remote_retrieve_body( $response );
        if ( empty( $body ) ) {
            return [
                'success' => false,
                'message' => 'Remote file is empty.',
                'count'   => 0,
            ];
        }

        // Parse: one domain per line, strip comments (#) and blank lines
        $lines = explode( "\n", $body );
        $domains = [];
        foreach ( $lines as $line ) {
            $line = strtolower( trim( $line ) );
            if ( ! $line || $line[0] === '#' ) continue;
            // Basic sanity check — must look like a domain
            if ( preg_match( '/^[a-z0-9.\-]+\.[a-z]{2,}$/', $line ) ) {
                $domains[] = $line;
            }
        }

        $domains = array_unique( $domains );

        if ( empty( $domains ) ) {
            return [
                'success' => false,
                'message' => 'No valid domains found in remote file.',
                'count'   => 0,
            ];
        }

        // Store
        update_option( self::OPTION_DOMAINS,    $domains,           false );
        update_option( self::OPTION_LAST_CHECK, time(),             false );
        update_option( self::OPTION_LAST_COUNT, count( $domains ),  false );

        return [
            'success' => true,
            'message' => 'Updated successfully.',
            'count'   => count( $domains ),
        ];
    }

    // ─── AJAX: Manual Update ──────────────────────────────────────────────────

    public function ajax_manual_update(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( -1 );
        check_ajax_referer( 'dfsas_admin_nonce', 'nonce' );

        if ( ! DFSAS_Helpers::is_pro() ) {
            wp_send_json_error( __( 'PRO feature only.', 'dadsfam-antispam' ) );
        }

        $result = $this->run_update();
        if ( $result['success'] ) {
            wp_send_json_success( [
                'message' => sprintf(
                    __( '✅ List updated — %s domains loaded.', 'dadsfam-antispam' ),
                    number_format( $result['count'] )
                ),
                'count'        => $result['count'],
                'last_updated' => human_time_diff( time() ) . ' ago',
            ] );
        } else {
            wp_send_json_error( $result['message'] );
        }
    }

    // ─── Static Helpers for Admin UI ─────────────────────────────────────────

    public static function get_status(): array {
        $last  = (int) get_option( self::OPTION_LAST_CHECK, 0 );
        $count = (int) get_option( self::OPTION_LAST_COUNT, 0 );
        $next  = (int) wp_next_scheduled( self::CRON_HOOK );

        return [
            'last_updated'   => $last  ? wp_date( 'd M Y H:i', $last )        : __( 'Never', 'dadsfam-antispam' ),
            'last_updated_h' => $last  ? human_time_diff( $last ) . ' ago'     : __( 'Never', 'dadsfam-antispam' ),
            'next_check'     => $next  ? wp_date( 'd M Y H:i', $next )        : __( 'Not scheduled', 'dadsfam-antispam' ),
            'next_check_h'   => $next  ? 'in ' . human_time_diff( $next )      : __( 'Not scheduled', 'dadsfam-antispam' ),
            'count'          => $count ?: 0,
            'has_list'       => $count > 0,
        ];
    }

    public static function get_domains(): array {
        return (array) get_option( self::OPTION_DOMAINS, [] );
    }

    // ─── AJAX: Upload .txt file directly ─────────────────────────────────────

    /**
     * Handles a .txt file upload from the browser.
     * Parses domains and stores them — same option as the remote fetch.
     * User just picks a file from their computer. No FTP, no cPanel needed.
     */
    public static function ajax_upload_list(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( -1 );

        if ( ! DFSAS_Helpers::is_pro() ) {
            wp_send_json_error( __( 'PRO feature only.', 'dadsfam-antispam' ) );
        }

        if ( empty( $_FILES['domain_list'] ) || $_FILES['domain_list']['error'] !== UPLOAD_ERR_OK ) {
            wp_send_json_error( __( 'No file received or upload error. Please try again.', 'dadsfam-antispam' ) );
        }
        $ext  = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
        $mime = mime_content_type( $file['tmp_name'] );

        if ( $ext !== 'txt' || ! in_array( $mime, [ 'text/plain', 'application/octet-stream' ], true ) ) {
            wp_send_json_error( __( 'File must be a plain .txt file — one domain per line.', 'dadsfam-antispam' ) );
        }

        if ( $file['size'] > 5 * 1024 * 1024 ) {
            wp_send_json_error( __( 'File is too large. Maximum 5 MB.', 'dadsfam-antispam' ) );
        }

        $content = file_get_contents( $file['tmp_name'] );
        if ( $content === false || empty( trim( $content ) ) ) {
            wp_send_json_error( __( 'File is empty or could not be read.', 'dadsfam-antispam' ) );
        }

        $domains = [];
        foreach ( explode( "\n", $content ) as $line ) {
            $line = strtolower( trim( $line ) );
            if ( ! $line || $line[0] === '#' ) continue;
            if ( preg_match( '/^[a-z0-9.\-]+\.[a-z]{2,}$/', $line ) ) {
                $domains[] = $line;
            }
        }
        $domains = array_unique( $domains );

        if ( empty( $domains ) ) {
            wp_send_json_error( __( 'No valid domains found. Make sure the file has one domain per line (e.g. mailinator.com) with no @ symbols.', 'dadsfam-antispam' ) );
        }

        update_option( self::OPTION_DOMAINS,    $domains,        false );
        update_option( self::OPTION_LAST_CHECK, time(),          false );
        update_option( self::OPTION_LAST_COUNT, count($domains), false );

        wp_send_json_success( [
            'message'      => sprintf(
                __( '✅ %s domains loaded successfully from your file.', 'dadsfam-antispam' ),
                number_format( count( $domains ) )
            ),
            'count'        => count( $domains ),
            'last_updated' => human_time_diff( time() ) . ' ago',
        ] );
    }
}
