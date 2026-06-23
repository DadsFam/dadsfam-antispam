<?php
/**
 * Static helper utilities used across the plugin.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class DFSAS_Helpers {

    /**
     * Is PRO version active?
     * Hook `dfsas_is_pro` to integrate with your DF Licensing plugin later.
     * Example: add_filter('dfsas_is_pro', fn() => df_license_is_valid('dadsfam-antispam'));
     */
    public static function is_pro(): bool {
        return (bool) apply_filters( 'dfsas_is_pro', false );
    }

    /**
     * Reliably get the real client IP, respecting proxies.
     */
    public static function get_client_ip(): string {
        $candidates = [
            'HTTP_CF_CONNECTING_IP',   // Cloudflare
            'HTTP_X_REAL_IP',
            'HTTP_X_FORWARDED_FOR',
            'REMOTE_ADDR',
        ];
        foreach ( $candidates as $key ) {
            $val = $_SERVER[ $key ] ?? '';
            if ( ! $val ) continue;
            // X-Forwarded-For can be a comma-separated list
            $ip = trim( explode( ',', $val )[0] );
            if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
                return $ip;
            }
        }
        // Fallback: allow private IPs (localhost dev)
        return trim( explode( ',', $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' )[0] );
    }

    /**
     * Get the current page URL.
     */
    public static function get_current_url(): string {
        $protocol = is_ssl() ? 'https' : 'http';
        return $protocol . '://' . ( $_SERVER['HTTP_HOST'] ?? '' ) . ( $_SERVER['REQUEST_URI'] ?? '' );
    }

    /**
     * Convert a textarea of lines into a clean array.
     */
    public static function textarea_to_array( string $text ): array {
        return array_filter( array_map( 'trim', explode( "\n", $text ) ) );
    }

    /**
     * Convert an array back to a trimmed textarea string.
     */
    public static function array_to_textarea( array $arr ): string {
        return implode( "\n", array_filter( array_map( 'trim', $arr ) ) );
    }

    /**
     * Wildcard IP match (supports CIDR /24 style prefix).
     * e.g. 192.168.1.* or 192.168.1.0/24
     */
    public static function ip_matches( string $ip, string $pattern ): bool {
        // CIDR
        if ( strpos( $pattern, '/' ) !== false ) {
            [ $subnet, $bits ] = explode( '/', $pattern );
            if ( ! filter_var( $subnet, FILTER_VALIDATE_IP ) ) return false;
            $ip_long  = ip2long( $ip );
            $sub_long = ip2long( $subnet );
            if ( $ip_long === false || $sub_long === false ) return false;
            $mask = (int) $bits >= 32 ? -1 : ~( ( 1 << ( 32 - (int) $bits ) ) - 1 );
            return ( $ip_long & $mask ) === ( $sub_long & $mask );
        }
        // Wildcard
        if ( strpos( $pattern, '*' ) !== false ) {
            $regex = '/^' . str_replace( '\*', '\d+', preg_quote( $pattern, '/' ) ) . '$/';
            return (bool) preg_match( $regex, $ip );
        }
        return $ip === $pattern;
    }

    /**
     * Generate a plugin-specific nonce field name that bots won't recognise.
     * Rotates daily so it's not static in source.
     */
    public static function honeypot_field_name(): string {
        return 'wp_' . substr( md5( 'dfsas_hp_' . wp_date( 'Ymd' ) ), 0, 8 );
    }

    /**
     * Generate the timestamp field name.
     */
    public static function timestamp_field_name(): string {
        return 'wp_' . substr( md5( 'dfsas_ts_' . wp_date( 'Ymd' ) ), 0, 8 );
    }

    /**
     * Encrypt a timestamp for the time-check field (prevents manipulation).
     */
    public static function encrypt_timestamp( int $time ): string {
        return base64_encode( $time . '|' . hash_hmac( 'sha256', (string) $time, wp_salt() ) );
    }

    /**
     * Decrypt and verify a timestamp value.
     * Returns the original timestamp or 0 on failure.
     */
    public static function decrypt_timestamp( string $value ): int {
        $decoded = base64_decode( $value, true );
        if ( ! $decoded ) return 0;
        $parts = explode( '|', $decoded, 2 );
        if ( count( $parts ) !== 2 ) return 0;
        [ $time, $hmac ] = $parts;
        if ( ! hash_equals( hash_hmac( 'sha256', $time, wp_salt() ), $hmac ) ) return 0;
        return (int) $time;
    }

    /**
     * Get the domain part of an email address.
     */
    public static function email_domain( string $email ): string {
        $parts = explode( '@', strtolower( trim( $email ) ) );
        return $parts[1] ?? '';
    }

    /**
     * Count URLs/links in a string.
     */
    public static function count_urls( string $text ): int {
        return preg_match_all(
            '#(https?://|www\.)[^\s<>"\']+#i',
            $text,
            $m
        );
    }

    /**
     * Sanitise and validate an IP address.
     */
    public static function is_valid_ip( string $ip ): bool {
        return (bool) filter_var( trim( $ip ), FILTER_VALIDATE_IP );
    }

    /**
     * Build a self-contained SVG area chart from daily data.
     * No external library, no CDN — pure inline SVG, WordPress.org safe.
     *
     * @param array $by_day  Rows of [ 'day' => 'YYYY-MM-DD', 'cnt' => int ]
     * @param int   $days    How many days back to render (fills gaps with 0)
     * @return array [ 'svg' => string, 'peak' => int, 'peak_day' => string, 'total' => int ]
     */
    public static function build_trend_chart( array $by_day, int $days = 30 ): array {
        // Build a date => count map, defaulting every day in range to 0
        $map = [];
        for ( $i = $days - 1; $i >= 0; $i-- ) {
            $d = gmdate( 'Y-m-d', strtotime( "-{$i} days" ) );
            $map[ $d ] = 0;
        }
        foreach ( $by_day as $row ) {
            $d = $row['day'] ?? '';
            if ( isset( $map[ $d ] ) ) {
                $map[ $d ] = (int) $row['cnt'];
            }
        }

        $values   = array_values( $map );
        $dates    = array_keys( $map );
        $count    = count( $values );
        $peak     = $count ? max( $values ) : 0;
        $peak_idx = $count ? array_search( $peak, $values, true ) : 0;
        $total    = array_sum( $values );

        // SVG geometry
        $w   = 720;
        $h   = 180;
        $pad = 8;
        $plot_h = $h - ( $pad * 2 );
        $plot_w = $w - ( $pad * 2 );
        $max = max( 1, $peak );

        $step = $count > 1 ? $plot_w / ( $count - 1 ) : $plot_w;

        $line_pts = [];
        $area_pts = [];
        foreach ( $values as $i => $v ) {
            $x = round( $pad + ( $i * $step ), 1 );
            $y = round( $pad + $plot_h - ( ( $v / $max ) * $plot_h ), 1 );
            $line_pts[] = "{$x},{$y}";
        }

        if ( empty( $line_pts ) ) {
            return [ 'svg' => '', 'peak' => 0, 'peak_day' => '', 'total' => 0 ];
        }

        $first_x = $pad;
        $last_x  = round( $pad + ( ( $count - 1 ) * $step ), 1 );
        $base_y  = $pad + $plot_h;
        $line_path = 'M' . implode( ' L', $line_pts );
        $area_path = "M{$first_x},{$base_y} L" . implode( ' L', $line_pts ) . " L{$last_x},{$base_y} Z";

        // Build SVG
        $svg  = '<svg viewBox="0 0 ' . $w . ' ' . $h . '" preserveAspectRatio="none" class="dfsas-chart-svg" role="img" aria-label="Spam blocked over time">';
        $svg .= '<defs><linearGradient id="dfsasGrad" x1="0" y1="0" x2="0" y2="1">';
        $svg .= '<stop offset="0%" stop-color="#1a5e9a" stop-opacity="0.28"/>';
        $svg .= '<stop offset="100%" stop-color="#1a5e9a" stop-opacity="0.02"/>';
        $svg .= '</linearGradient></defs>';

        // Subtle horizontal gridlines (quarters)
        for ( $g = 1; $g <= 3; $g++ ) {
            $gy = round( $pad + ( $plot_h * $g / 4 ), 1 );
            $svg .= '<line x1="' . $pad . '" y1="' . $gy . '" x2="' . ( $w - $pad ) . '" y2="' . $gy . '" stroke="#e9ecef" stroke-width="1"/>';
        }

        $svg .= '<path d="' . $area_path . '" fill="url(#dfsasGrad)"/>';
        $svg .= '<path d="' . $line_path . '" fill="none" stroke="#1a5e9a" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>';

        // Peak dot
        if ( $peak > 0 && isset( $line_pts[ $peak_idx ] ) ) {
            [ $px, $py ] = explode( ',', $line_pts[ $peak_idx ] );
            $svg .= '<circle cx="' . $px . '" cy="' . $py . '" r="4" fill="#1a5e9a" stroke="#fff" stroke-width="2"/>';
        }

        $svg .= '</svg>';

        return [
            'svg'      => $svg,
            'peak'     => $peak,
            'peak_day' => $dates[ $peak_idx ] ?? '',
            'total'    => $total,
        ];
    }
}
