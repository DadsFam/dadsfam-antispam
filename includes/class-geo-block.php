<?php
/**
 * Geo-Blocking Module — PRO feature.
 * Blocks form submissions from specified countries using the free GeoLite2 DB
 * (or falls back to ip-api.com if no local DB is present).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class DFSAS_GeoBlock {

    private array $opts;
    private array $blocked_countries; // ISO 3166-1 alpha-2 codes, e.g. ['RU','CN','KP']

    public function __construct( array $opts ) {
        if ( ! DFSAS_Helpers::is_pro() ) return;

        $this->opts             = $opts;
        $this->blocked_countries = array_map( 'strtoupper', DFSAS_Helpers::textarea_to_array( $opts['geo_blocked_countries'] ?? '' ) );

        if ( empty( $this->blocked_countries ) ) return;

        $this->register_hooks();
    }

    private function register_hooks(): void {
        add_filter( 'wpcf7_spam',             [ $this, 'check_cf7'          ], 3, 2 );
        add_action( 'wpforms_process_before', [ $this, 'check_wpforms'      ], 3, 2 );
        add_filter( 'registration_errors',    [ $this, 'check_registration' ], 5, 3 );
    }

    // ─── Country Lookup ───────────────────────────────────────────────────────

    public function get_country( string $ip ): string {
        static $cache = [];
        if ( isset( $cache[ $ip ] ) ) return $cache[ $ip ];

        // Cloudflare provides country in header — free and fast
        if ( ! empty( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) {
            $country          = strtoupper( sanitize_text_field( $_SERVER['HTTP_CF_IPCOUNTRY'] ) );
            $cache[ $ip ]     = $country;
            return $country;
        }

        // Local MaxMind GeoLite2 DB (if installed by site admin)
        $mmdb = DFSAS_PATH . 'data/GeoLite2-Country.mmdb';
        if ( file_exists( $mmdb ) && class_exists( '\GeoIp2\Database\Reader' ) ) {
            try {
                $reader  = new \GeoIp2\Database\Reader( $mmdb );
                $record  = $reader->country( $ip );
                $country = $record->country->isoCode ?? '';
                $cache[ $ip ] = strtoupper( $country );
                return $cache[ $ip ];
            } catch ( \Exception $e ) {
                // fall through
            }
        }

        // Fallback: ip-api.com (free, 45 req/min — suitable as last resort only)
        $response = wp_remote_get(
            "http://ip-api.com/json/{$ip}?fields=countryCode",
            [ 'timeout' => 3, 'sslverify' => false ]
        );
        if ( ! is_wp_error( $response ) ) {
            $body    = json_decode( wp_remote_retrieve_body( $response ), true );
            $country = strtoupper( $body['countryCode'] ?? '' );
            $cache[ $ip ] = $country;
            return $country;
        }

        $cache[ $ip ] = '';
        return '';
    }

    public function is_blocked_country( string $ip ): array {
        $country = $this->get_country( $ip );
        if ( $country && in_array( $country, $this->blocked_countries, true ) ) {
            return [ 'blocked' => true, 'country' => $country ];
        }
        return [ 'blocked' => false, 'country' => $country ];
    }

    // ─── Integration Hooks ────────────────────────────────────────────────────

    public function check_cf7( bool $spam, $submission ): bool {
        if ( $spam ) return true;
        $ip     = DFSAS_Helpers::get_client_ip();
        $result = $this->is_blocked_country( $ip );
        if ( $result['blocked'] ) {
            DFSAS_Logger::log( [
                'form_type' => 'contact-form-7',
                'ip'        => $ip,
                'reason'    => 'geo_blocked',
                'details'   => [ 'country' => $result['country'] ],
                'score'     => 10,
            ] );
            return true;
        }
        return false;
    }

    public function check_wpforms( array $fields, array $form_data ): void {
        $ip     = DFSAS_Helpers::get_client_ip();
        $result = $this->is_blocked_country( $ip );
        if ( $result['blocked'] ) {
            DFSAS_Logger::log( [
                'form_type' => 'wpforms',
                'ip'        => $ip,
                'reason'    => 'geo_blocked',
                'details'   => [ 'country' => $result['country'] ],
                'score'     => 10,
            ] );
            wpforms()->process->errors['header'] = __( 'Submission not available in your region.', 'dadsfam-antispam' );
        }
    }

    public function check_registration( \WP_Error $errors, string $login, string $email ): \WP_Error {
        $ip     = DFSAS_Helpers::get_client_ip();
        $result = $this->is_blocked_country( $ip );
        if ( $result['blocked'] ) {
            DFSAS_Logger::log( [
                'form_type' => 'wp-registration',
                'ip'        => $ip,
                'email'     => $email,
                'reason'    => 'geo_blocked',
                'details'   => [ 'country' => $result['country'] ],
                'score'     => 10,
            ] );
            $errors->add( 'dfsas_geo', __( '<strong>Error</strong>: Registration not available in your region.', 'dadsfam-antispam' ) );
        }
        return $errors;
    }
}
