<?php
/**
 * DNSBL (DNS Blacklist) Module — PRO feature.
 * Checks submitter IP against real-time reputation blacklists.
 * Spamhaus, SpamCop, SORBS etc.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class DFSAS_DNSBL {

    private array $opts;
    private array $servers;

    public function __construct( array $opts ) {
        if ( ! DFSAS_Helpers::is_pro() ) return;

        $this->opts    = $opts;
        $this->servers = DFSAS_Helpers::textarea_to_array( $opts['dnsbl_servers'] ?? '' );

        if ( empty( $this->servers ) ) return;

        $this->register_hooks();
    }

    private function register_hooks(): void {
        add_filter( 'wpcf7_spam',             [ $this, 'check_cf7'     ], 4, 2 );
        add_action( 'wpforms_process_before', [ $this, 'check_wpforms' ], 4, 2 );
        add_filter( 'registration_errors',    [ $this, 'check_registration' ], 6, 3 );
    }

    // ─── Core DNSBL Lookup ────────────────────────────────────────────────────

    public function is_blacklisted( string $ip ): array {
        if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
            return [ 'blacklisted' => false, 'server' => '' ];
        }

        // Cache per IP per request
        static $cache = [];
        if ( isset( $cache[ $ip ] ) ) return $cache[ $ip ];

        // Reverse the IP octets for DNSBL lookup
        $reversed = implode( '.', array_reverse( explode( '.', $ip ) ) );

        foreach ( $this->servers as $server ) {
            $lookup = "{$reversed}.{$server}";
            // checkdnsrr is fast and non-blocking
            if ( checkdnsrr( $lookup, 'A' ) ) {
                $result = [ 'blacklisted' => true, 'server' => $server ];
                $cache[ $ip ] = $result;
                return $result;
            }
        }

        $result = [ 'blacklisted' => false, 'server' => '' ];
        $cache[ $ip ] = $result;
        return $result;
    }

    // ─── Integration Hooks ────────────────────────────────────────────────────

    public function check_cf7( bool $spam, $submission ): bool {
        if ( $spam ) return true;
        $ip     = DFSAS_Helpers::get_client_ip();
        $result = $this->is_blacklisted( $ip );
        if ( $result['blacklisted'] ) {
            DFSAS_Logger::log( [
                'form_type' => 'contact-form-7',
                'ip'        => $ip,
                'reason'    => 'dnsbl_blocked',
                'details'   => [ 'server' => $result['server'] ],
                'score'     => 10,
            ] );
            return true;
        }
        return false;
    }

    public function check_wpforms( array $fields, array $form_data ): void {
        $ip     = DFSAS_Helpers::get_client_ip();
        $result = $this->is_blacklisted( $ip );
        if ( $result['blacklisted'] ) {
            DFSAS_Logger::log( [
                'form_type' => 'wpforms',
                'ip'        => $ip,
                'reason'    => 'dnsbl_blocked',
                'details'   => [ 'server' => $result['server'] ],
                'score'     => 10,
            ] );
            wpforms()->process->errors['header'] = __( 'Submission blocked.', 'dadsfam-antispam' );
        }
    }

    public function check_registration( \WP_Error $errors, string $login, string $email ): \WP_Error {
        $ip     = DFSAS_Helpers::get_client_ip();
        $result = $this->is_blacklisted( $ip );
        if ( $result['blacklisted'] ) {
            DFSAS_Logger::log( [
                'form_type' => 'wp-registration',
                'ip'        => $ip,
                'email'     => $email,
                'reason'    => 'dnsbl_blocked',
                'details'   => [ 'server' => $result['server'] ],
                'score'     => 10,
            ] );
            $errors->add( 'dfsas_dnsbl', __( '<strong>Error</strong>: Registration blocked.', 'dadsfam-antispam' ) );
        }
        return $errors;
    }
}
