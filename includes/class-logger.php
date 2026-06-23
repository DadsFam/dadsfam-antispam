<?php
/**
 * Manages the custom spam log database table and logging methods.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class DFSAS_Logger {

    const TABLE_SUFFIX = 'dfsas_spam_log';

    public static function table_name(): string {
        global $wpdb;
        return $wpdb->prefix . self::TABLE_SUFFIX;
    }

    /**
     * Create (or upgrade) the spam log table on activation.
     */
    public static function create_table(): void {
        global $wpdb;
        $table        = self::table_name();
        $charset      = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id          BIGINT(20)   UNSIGNED NOT NULL AUTO_INCREMENT,
            blocked_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            form_type   VARCHAR(60)  NOT NULL DEFAULT '',
            page_url    VARCHAR(512) NOT NULL DEFAULT '',
            ip_address  VARCHAR(45)  NOT NULL DEFAULT '',
            email       VARCHAR(255) NOT NULL DEFAULT '',
            name        VARCHAR(255) NOT NULL DEFAULT '',
            subject     VARCHAR(255) NOT NULL DEFAULT '',
            reason      VARCHAR(255) NOT NULL DEFAULT '',
            score       TINYINT(3)   UNSIGNED NOT NULL DEFAULT 0,
            details     LONGTEXT,
            PRIMARY KEY  (id),
            KEY ip_address (ip_address(20)),
            KEY blocked_at (blocked_at),
            KEY reason     (reason(40)),
            KEY form_type  (form_type)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Log a blocked/flagged submission.
     */
    public static function log( array $data ): void {
        $opts = get_option( 'dfsas_options', [] );
        if ( empty( $opts['log_blocked'] ) ) return;

        global $wpdb;

        $wpdb->insert(
            self::table_name(),
            [
                'blocked_at' => current_time( 'mysql' ),
                'form_type'  => sanitize_text_field( $data['form_type']  ?? 'unknown' ),
                'page_url'   => esc_url_raw( $data['page_url']  ?? DFSAS_Helpers::get_current_url() ),
                'ip_address' => sanitize_text_field( $data['ip']         ?? DFSAS_Helpers::get_client_ip() ),
                'email'      => sanitize_email( $data['email']    ?? '' ),
                'name'       => sanitize_text_field( $data['name']       ?? '' ),
                'subject'    => sanitize_text_field( $data['subject']    ?? '' ),
                'reason'     => sanitize_text_field( $data['reason']     ?? '' ),
                'score'      => absint( $data['score']   ?? 0 ),
                'details'    => wp_json_encode( $data['details'] ?? [] ),
            ],
            [ '%s','%s','%s','%s','%s','%s','%s','%s','%d','%s' ]
        );

        // Enforce free-tier retention cap
        if ( ! DFSAS_Helpers::is_pro() ) {
            self::enforce_free_cap();
        }
    }

    /**
     * Free plan: keep only the 200 most recent entries.
     */
    private static function enforce_free_cap(): void {
        global $wpdb;
        $table = self::table_name();
        $wpdb->query(
            "DELETE FROM {$table}
             WHERE id NOT IN (
                SELECT id FROM (
                    SELECT id FROM {$table} ORDER BY id DESC LIMIT 200
                ) AS t
             )"
        );
    }

    /**
     * PRO: delete logs older than N days.
     */
    public static function cleanup_old_logs(): void {
        global $wpdb;
        $days  = absint( get_option( 'dfsas_options', [] )['log_retention_days'] ?? 30 );
        $table = self::table_name();
        $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$table} WHERE blocked_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ) );
    }

    /**
     * Get paginated log entries.
     */
    public static function get_entries( array $args = [] ): array {
        global $wpdb;
        $table   = self::table_name();
        $limit   = absint( $args['per_page'] ?? 25 );
        $offset  = absint( $args['offset']   ?? 0 );
        $reason  = sanitize_text_field( $args['reason'] ?? '' );
        $search  = sanitize_text_field( $args['search'] ?? '' );

        $where   = '1=1';
        $values  = [];

        if ( $reason ) {
            $where   .= ' AND reason = %s';
            $values[] = $reason;
        }
        if ( $search ) {
            $where   .= ' AND (ip_address LIKE %s OR email LIKE %s OR name LIKE %s)';
            $values[] = '%' . $wpdb->esc_like( $search ) . '%';
            $values[] = '%' . $wpdb->esc_like( $search ) . '%';
            $values[] = '%' . $wpdb->esc_like( $search ) . '%';
        }

        $sql = "SELECT * FROM {$table} WHERE {$where} ORDER BY id DESC LIMIT %d OFFSET %d";
        $values[] = $limit;
        $values[] = $offset;

        $rows = $wpdb->get_results( $wpdb->prepare( $sql, $values ), ARRAY_A );
        return $rows ?: [];
    }

    /**
     * Count total entries (optionally filtered).
     */
    public static function count_entries( array $args = [] ): int {
        global $wpdb;
        $table  = self::table_name();
        $reason = sanitize_text_field( $args['reason'] ?? '' );
        $search = sanitize_text_field( $args['search'] ?? '' );

        $where  = '1=1';
        $values = [];

        if ( $reason ) {
            $where   .= ' AND reason = %s';
            $values[] = $reason;
        }
        if ( $search ) {
            $where   .= ' AND (ip_address LIKE %s OR email LIKE %s OR name LIKE %s)';
            $s         = '%' . $wpdb->esc_like( $search ) . '%';
            $values[] = $s; $values[] = $s; $values[] = $s;
        }

        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
        return (int) ( $values
            ? $wpdb->get_var( $wpdb->prepare( $sql, $values ) )
            : $wpdb->get_var( $sql )
        );
    }

    /**
     * Summary stats for the dashboard.
     */
    public static function get_stats(): array {
        global $wpdb;
        $table = self::table_name();

        return [
            'total'      => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ),
            'today'      => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE DATE(blocked_at) = CURDATE()" ),
            'this_week'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE blocked_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)" ),
            'top_reason' => (string) $wpdb->get_var( "SELECT reason FROM {$table} GROUP BY reason ORDER BY COUNT(*) DESC LIMIT 1" ),
            'top_ip'     => (string) $wpdb->get_var( "SELECT ip_address FROM {$table} GROUP BY ip_address ORDER BY COUNT(*) DESC LIMIT 1" ),
            'by_reason'  => (array)  $wpdb->get_results(
                "SELECT reason, COUNT(*) AS cnt FROM {$table} GROUP BY reason ORDER BY cnt DESC LIMIT 10",
                ARRAY_A
            ),
            'by_day'     => (array) $wpdb->get_results(
                "SELECT DATE(blocked_at) AS day, COUNT(*) AS cnt FROM {$table}
                 WHERE blocked_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                 GROUP BY day ORDER BY day ASC",
                ARRAY_A
            ),
            'by_form'    => (array) $wpdb->get_results(
                "SELECT form_type, COUNT(*) AS cnt FROM {$table}
                 GROUP BY form_type ORDER BY cnt DESC LIMIT 12",
                ARRAY_A
            ),
            'this_month' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE blocked_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)" ),
            'yesterday'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE DATE(blocked_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)" ),
        ];
    }

    /**
     * PRO: Export all logs to a CSV string.
     */
    public static function export_csv(): string {
        global $wpdb;
        $rows = $wpdb->get_results( "SELECT * FROM " . self::table_name() . " ORDER BY id DESC", ARRAY_A );
        if ( ! $rows ) return '';

        $out = fopen( 'php://temp', 'r+' );
        fputcsv( $out, array_keys( $rows[0] ) );
        foreach ( $rows as $row ) {
            fputcsv( $out, $row );
        }
        rewind( $out );
        $csv = stream_get_contents( $out );
        fclose( $out );
        return $csv;
    }

    /**
     * Delete a single log entry.
     */
    public static function delete_entry( int $id ): void {
        global $wpdb;
        $wpdb->delete( self::table_name(), [ 'id' => $id ], [ '%d' ] );
    }

    /**
     * Clear all log entries.
     */
    public static function clear_all(): void {
        global $wpdb;
        $wpdb->query( "TRUNCATE TABLE " . self::table_name() );
    }
}
