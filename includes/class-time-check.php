<?php
/**
 * Time Check module — bots submit instantly; humans need a few seconds.
 * Works independently of the honeypot for forms that don't carry the HP field.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class DFSAS_TimeCheck {

    private array $opts;
    private int   $min_seconds;

    public function __construct( array $opts ) {
        $this->opts        = $opts;
        $this->min_seconds = max( 1, (int) ( $opts['time_check_min_seconds'] ?? 3 ) );
        $this->register_hooks();
    }

    private function register_hooks(): void {
        // CF7
        add_filter( 'wpcf7_spam', [ $this, 'check_cf7' ], 9, 2 );
        // WPForms
        add_action( 'wpforms_process_before', [ $this, 'check_wpforms' ], 9, 2 );
        // Registration (WordPress native)
        add_filter( 'registration_errors', [ $this, 'check_registration' ], 10, 3 );
    }

    private function is_too_fast(): bool {
        $ts_name = DFSAS_Helpers::timestamp_field_name();
        $raw     = isset( $_POST[ $ts_name ] ) ? sanitize_text_field( wp_unslash( $_POST[ $ts_name ] ) ) : '';
        if ( ! $raw ) return false; // can't determine — don't block

        $submitted = DFSAS_Helpers::decrypt_timestamp( $raw );
        if ( ! $submitted ) return true; // tampered

        return ( time() - $submitted ) < $this->min_seconds;
    }

    public function check_cf7( bool $spam, $submission ): bool {
        if ( $spam ) return true;
        if ( $this->is_too_fast() ) {
            DFSAS_Logger::log( [
                'form_type' => 'contact-form-7',
                'ip'        => DFSAS_Helpers::get_client_ip(),
                'reason'    => 'submitted_too_fast',
                'score'     => 10,
            ] );
            return true;
        }
        return false;
    }

    public function check_wpforms( array $fields, array $form_data ): void {
        if ( $this->is_too_fast() ) {
            DFSAS_Logger::log( [
                'form_type' => 'wpforms',
                'ip'        => DFSAS_Helpers::get_client_ip(),
                'reason'    => 'submitted_too_fast',
                'score'     => 10,
            ] );
            wpforms()->process->errors['header'] = __( 'Submission blocked.', 'dadsfam-antispam' );
        }
    }

    public function check_registration( \WP_Error $errors, string $sanitized_user_login, string $user_email ): \WP_Error {
        if ( $this->is_too_fast() ) {
            DFSAS_Logger::log( [
                'form_type' => 'wp-registration',
                'ip'        => DFSAS_Helpers::get_client_ip(),
                'email'     => $user_email,
                'reason'    => 'submitted_too_fast',
                'score'     => 10,
            ] );
            $errors->add( 'dfsas_toofast', __( '<strong>Error</strong>: Registration blocked.', 'dadsfam-antispam' ) );
        }
        return $errors;
    }
}
