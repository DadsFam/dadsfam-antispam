<?php
/**
 * Honeypot module — injects invisible trap fields into forms.
 * If a bot fills in the honeypot field → spam. Humans never see it.
 *
 * Supports: CF7, WPForms, Ninja Forms, Gravity Forms, Fluent Forms, generic HTML.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class DFSAS_Honeypot {

    private array $opts;

    public function __construct( array $opts ) {
        $this->opts = $opts;
        $this->register_hooks();
    }

    private function register_hooks(): void {
        // ── Contact Form 7 ────────────────────────────────────────────────────
        if ( $this->opts['honeypot_cf7'] ) {
            add_filter( 'wpcf7_form_elements',          [ $this, 'inject_into_cf7' ] );
            add_filter( 'wpcf7_spam',                   [ $this, 'check_cf7' ], 10, 2 );
        }

        // ── WPForms ───────────────────────────────────────────────────────────
        if ( $this->opts['honeypot_wpforms'] ) {
            add_action( 'wpforms_frontend_output_after_form_open', [ $this, 'inject_into_wpforms' ] );
            add_action( 'wpforms_process_before',                  [ $this, 'check_wpforms' ], 10, 2 );
        }

        // ── Ninja Forms ───────────────────────────────────────────────────────
        if ( $this->opts['honeypot_ninjaforms'] ) {
            add_action( 'ninja_forms_display_after_fields', [ $this, 'inject_into_ninjaforms' ] );
            add_filter( 'ninja_forms_submit_data',          [ $this, 'check_ninjaforms' ] );
        }

        // ── Gravity Forms ─────────────────────────────────────────────────────
        if ( $this->opts['honeypot_gravityforms'] ) {
            add_filter( 'gform_pre_render',        [ $this, 'inject_into_gravityforms' ] );
            add_filter( 'gform_validation',        [ $this, 'check_gravityforms' ] );
        }

        // ── Fluent Forms ──────────────────────────────────────────────────────
        if ( $this->opts['honeypot_fluentforms'] ) {
            add_filter( 'fluentform/rendering_field_html_submit_button', [ $this, 'inject_into_fluentforms' ], 10, 3 );
            add_filter( 'fluentform/before_insert_submission',           [ $this, 'check_fluentforms' ], 10, 3 );
        }

        // ── Generic (JS injection on all forms) ───────────────────────────────
        if ( $this->opts['honeypot_generic'] ) {
            add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_script' ] );
            // wp_mail last-resort check
            add_filter( 'wp_mail', [ $this, 'check_wp_mail' ] );
        }
    }

    // ─── Field Generation ─────────────────────────────────────────────────────

    private function honeypot_html(): string {
        $hp  = esc_attr( DFSAS_Helpers::honeypot_field_name() );
        $ts  = esc_attr( DFSAS_Helpers::timestamp_field_name() );
        $enc = esc_attr( DFSAS_Helpers::encrypt_timestamp( time() ) );

        return <<<HTML
<div class="dfsas-hp-wrapper" aria-hidden="true" style="position:absolute;left:-9999px;top:-9999px;width:1px;height:1px;overflow:hidden;" tabindex="-1">
    <label for="{$hp}">Leave this field empty</label>
    <input type="text" id="{$hp}" name="{$hp}" value="" autocomplete="off" tabindex="-1" />
</div>
<input type="hidden" name="{$ts}" value="{$enc}" />
HTML;
    }

    // ─── Validation ───────────────────────────────────────────────────────────

    /**
     * Core check: returns [ 'spam' => bool, 'reason' => string ]
     */
    private function run_check(): array {
        $hp_name = DFSAS_Helpers::honeypot_field_name();
        $ts_name = DFSAS_Helpers::timestamp_field_name();

        // 1. Honeypot filled
        if ( isset( $_POST[ $hp_name ] ) && '' !== $_POST[ $hp_name ] ) {
            return [ 'spam' => true, 'reason' => 'honeypot_filled' ];
        }

        // 2. Timestamp missing or tampered
        $raw = isset( $_POST[ $ts_name ] ) ? sanitize_text_field( wp_unslash( $_POST[ $ts_name ] ) ) : '';
        if ( ! $raw ) {
            // Generic non-CF7 forms may not have timestamp; don't hard-block, just note
            return [ 'spam' => false, 'reason' => '' ];
        }

        $submitted = DFSAS_Helpers::decrypt_timestamp( $raw );
        if ( ! $submitted ) {
            return [ 'spam' => true, 'reason' => 'timestamp_invalid' ];
        }

        $min = max( 1, (int) ( $this->opts['time_check_min_seconds'] ?? 3 ) );
        if ( ( time() - $submitted ) < $min ) {
            return [ 'spam' => true, 'reason' => 'submitted_too_fast' ];
        }

        return [ 'spam' => false, 'reason' => '' ];
    }

    private function block_and_log( string $form_type, string $reason, array $extra = [] ): void {
        DFSAS_Logger::log( array_merge( [
            'form_type' => $form_type,
            'ip'        => DFSAS_Helpers::get_client_ip(),
            'email'     => sanitize_email( $_POST['your-email'] ?? $_POST['email'] ?? '' ),
            'reason'    => $reason,
            'score'     => 10,
        ], $extra ) );
    }

    // ─── CF7 ──────────────────────────────────────────────────────────────────

    public function inject_into_cf7( string $content ): string {
        return str_replace( '</form>', $this->honeypot_html() . '</form>', $content );
    }

    public function check_cf7( bool $spam, $submission ): bool {
        if ( $spam ) return true; // already caught by CF7 itself
        $result = $this->run_check();
        if ( $result['spam'] ) {
            $this->block_and_log( 'contact-form-7', $result['reason'] );
            return true;
        }
        return false;
    }

    // ─── WPForms ──────────────────────────────────────────────────────────────

    public function inject_into_wpforms(): void {
        echo $this->honeypot_html(); // phpcs:ignore
    }

    public function check_wpforms( array $fields, array $form_data ): void {
        $result = $this->run_check();
        if ( $result['spam'] ) {
            $this->block_and_log( 'wpforms', $result['reason'] );
            wpforms()->process->errors['header'] = __( 'Submission blocked.', 'dadsfam-antispam' );
        }
    }

    // ─── Ninja Forms ──────────────────────────────────────────────────────────

    public function inject_into_ninjaforms(): void {
        echo $this->honeypot_html(); // phpcs:ignore
    }

    public function check_ninjaforms( array $data ): array {
        $result = $this->run_check();
        if ( $result['spam'] ) {
            $this->block_and_log( 'ninja-forms', $result['reason'] );
            $data['errors']['fields']['dfsas_spam'] = __( 'Submission blocked.', 'dadsfam-antispam' );
        }
        return $data;
    }

    // ─── Gravity Forms ────────────────────────────────────────────────────────

    public function inject_into_gravityforms( array $form ): array {
        add_action( 'gform_field_content', function( $content ) {
            static $done = false;
            if ( ! $done ) { $done = true; return $content . $this->honeypot_html(); }
            return $content;
        } );
        return $form;
    }

    public function check_gravityforms( array $validation_result ): array {
        $result = $this->run_check();
        if ( $result['spam'] ) {
            $this->block_and_log( 'gravity-forms', $result['reason'] );
            $validation_result['is_valid'] = false;
        }
        return $validation_result;
    }

    // ─── Fluent Forms ─────────────────────────────────────────────────────────

    public function inject_into_fluentforms( string $html ): string {
        return $this->honeypot_html() . $html;
    }

    public function check_fluentforms( array $insert_data, array $data, $form ): array {
        $result = $this->run_check();
        if ( $result['spam'] ) {
            $this->block_and_log( 'fluent-forms', $result['reason'] );
            wp_send_json( [ 'errors' => [ 'restricted' => __( 'Submission blocked.', 'dadsfam-antispam' ) ] ], 422 );
        }
        return $insert_data;
    }

    // ─── Generic / wp_mail fallback ───────────────────────────────────────────

    public function enqueue_frontend_script(): void {
        wp_enqueue_script(
            'dfsas-frontend',
            DFSAS_URL . 'assets/frontend.js',
            [],
            DFSAS_VERSION,
            true
        );
        wp_localize_script( 'dfsas-frontend', 'dfsasVars', [
            'hp_name'  => DFSAS_Helpers::honeypot_field_name(),
            'ts_name'  => DFSAS_Helpers::timestamp_field_name(),
            'ts_value' => DFSAS_Helpers::encrypt_timestamp( time() ),
        ] );
    }

    /**
     * Last resort: if generic form bypasses all hooks above,
     * we flag the outgoing wp_mail before it fires.
     */
    public function check_wp_mail( array $atts ): array {
        // Only block if we have POST data with a filled honeypot
        $hp = DFSAS_Helpers::honeypot_field_name();
        if ( isset( $_POST[ $hp ] ) && '' !== $_POST[ $hp ] ) {
            $this->block_and_log( 'generic-form', 'honeypot_filled_generic' );
            $atts['to'] = 'blocked@localhost'; // divert to nowhere
        }
        return $atts;
    }
}
