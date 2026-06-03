<?php
/**
 * Google reCAPTCHA module — FREE feature.
 *
 * Supports:
 *   v2 Checkbox  — classic "I'm not a robot" tick box
 *   v2 Invisible — fires silently on submit, no user interaction
 *   v3           — fully invisible, score-based (0.0–1.0), configurable threshold
 *
 * Integrations: CF7, WPForms, Ninja Forms, Gravity Forms, Fluent Forms,
 *               WP Login, WP Registration, WP Lost Password, Generic HTML forms
 *
 * Note: if a form plugin already has its OWN reCAPTCHA enabled (e.g. CF7 built-in),
 * disable it in that plugin's settings and let this one handle it — avoid double verify.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class DFSAS_ReCaptcha {

    private array  $opts;
    private string $site_key;
    private string $secret_key;
    private string $version;   // 'v2_checkbox' | 'v2_invisible' | 'v3'
    private float  $threshold; // v3 only — block if score < this

    public function __construct( array $opts ) {
        $this->opts       = $opts;
        $this->site_key   = trim( $opts['recaptcha_site_key']     ?? '' );
        $this->secret_key = trim( $opts['recaptcha_secret_key']   ?? '' );
        $this->version    = $opts['recaptcha_version']            ?? 'v3';
        $this->threshold  = (float) ( $opts['recaptcha_v3_threshold'] ?? 0.5 );

        if ( empty( $this->site_key ) || empty( $this->secret_key ) ) return;

        $this->register_hooks();
    }

    // ─── Script Loading ───────────────────────────────────────────────────────

    private function register_hooks(): void {
        add_action( 'wp_enqueue_scripts',    [ $this, 'enqueue_script' ] );
        add_action( 'login_enqueue_scripts', [ $this, 'enqueue_script' ] );

        // Contact Form 7
        if ( ! empty( $this->opts['recaptcha_cf7'] ) ) {
            add_filter( 'wpcf7_form_elements', [ $this, 'inject_cf7'      ] );
            add_filter( 'wpcf7_spam',          [ $this, 'verify_cf7'      ], 2, 2 );
        }

        // WPForms
        if ( ! empty( $this->opts['recaptcha_wpforms'] ) ) {
            add_action( 'wpforms_frontend_output_after_form_open', [ $this, 'inject_widget_echo' ] );
            add_action( 'wpforms_process_before', [ $this, 'verify_wpforms' ], 2, 2 );
        }

        // Ninja Forms
        if ( ! empty( $this->opts['recaptcha_ninjaforms'] ) ) {
            add_action( 'ninja_forms_display_after_fields', [ $this, 'inject_widget_echo' ] );
            add_filter( 'ninja_forms_submit_data',          [ $this, 'verify_ninjaforms' ] );
        }

        // Gravity Forms
        if ( ! empty( $this->opts['recaptcha_gravityforms'] ) ) {
            add_filter( 'gform_submit_button', [ $this, 'inject_gravity'    ], 10, 2 );
            add_filter( 'gform_validation',    [ $this, 'verify_gravity'    ] );
        }

        // Fluent Forms
        if ( ! empty( $this->opts['recaptcha_fluentforms'] ) ) {
            add_filter( 'fluentform/rendering_field_html_submit_button', [ $this, 'inject_fluent'  ], 10, 3 );
            add_filter( 'fluentform/before_insert_submission',           [ $this, 'verify_fluent'  ], 2, 3 );
        }

        // WP Login — native wp-login.php AND any theme login form
        if ( ! empty( $this->opts['recaptcha_wp_login'] ) ) {
            add_action( 'login_form',           [ $this, 'inject_widget_echo' ] );
            add_action( 'login_footer',         [ $this, 'inject_generic_js'  ], 25 );
            add_filter( 'wp_authenticate_user', [ $this, 'verify_wp_login'   ], 10, 2 );
            // WooCommerce My Account login (front-end form — different from login_form)
            add_action( 'woocommerce_login_form',           [ $this, 'inject_widget_echo' ] );
            add_filter( 'woocommerce_process_login_errors', [ $this, 'verify_woo_login'   ], 10, 3 );
        }

        // WP Registration
        if ( ! empty( $this->opts['recaptcha_wp_registration'] ) ) {
            add_action( 'register_form',       [ $this, 'inject_widget_echo'    ] );
            add_filter( 'registration_errors', [ $this, 'verify_wp_registration'], 2, 3 );
        }

        // WP Lost Password
        if ( ! empty( $this->opts['recaptcha_wp_lostpassword'] ) ) {
            add_action( 'lostpassword_form', [ $this, 'inject_widget_echo'   ] );
            add_filter( 'lostpassword_post', [ $this, 'verify_lostpassword'  ] );
        }

        // WooCommerce Checkout
        if ( ! empty( $this->opts['recaptcha_woo_checkout'] ) ) {
            add_action( 'woocommerce_after_checkout_billing_form', [ $this, 'inject_widget_echo'  ] );
            add_action( 'woocommerce_checkout_process',            [ $this, 'verify_woo_checkout' ] );
        }

        // Generic JS injection for all other forms
        if ( ! empty( $this->opts['recaptcha_generic'] ) ) {
            add_action( 'wp_footer',    [ $this, 'inject_generic_js' ], 25 ); // priority 25 = after wp_print_footer_scripts at 20
            add_action( 'login_footer', [ $this, 'inject_generic_js' ], 25 );
        }
    }

    public function enqueue_script(): void {
        $url = $this->version === 'v3'
            ? 'https://www.google.com/recaptcha/api.js?render=' . esc_attr( $this->site_key )
            : 'https://www.google.com/recaptcha/api.js';

        wp_enqueue_script( 'google-recaptcha', $url, [], null, true );

        // Pass vars for JS
        wp_localize_script( 'google-recaptcha', 'dfsasRecaptcha', [
            'site_key' => $this->site_key,
            'version'  => $this->version,
            'field'    => $this->token_field_name(),
        ] );
    }

    // ─── Widget HTML ──────────────────────────────────────────────────────────

    private function token_field_name(): string {
        return 'dfsas_rc_token';
    }

    private function widget_html( string $action = 'submit' ): string {
        $key = esc_attr( $this->site_key );

        if ( $this->version === 'v3' ) {
            // v3: hidden field populated by JS
            return '<input type="hidden" name="' . esc_attr( $this->token_field_name() ) . '" class="dfsas-rc-v3-token" data-action="' . esc_attr( $action ) . '" />';
        }

        if ( $this->version === 'v2_invisible' ) {
            return '<div class="g-recaptcha" data-sitekey="' . $key . '" data-badge="bottomright" data-size="invisible"></div>';
        }

        // v2 checkbox
        return '<div class="g-recaptcha" style="margin:10px 0;" data-sitekey="' . $key . '"></div>';
    }

    public function inject_widget_echo(): void {
        echo $this->widget_html(); // phpcs:ignore
    }

    // ─── CF7 ──────────────────────────────────────────────────────────────────

    public function inject_cf7( string $content ): string {
        return str_replace( '</form>', $this->widget_html( 'cf7' ) . '</form>', $content );
    }

    public function verify_cf7( bool $spam, $submission ): bool {
        if ( $spam ) return true;
        $result = $this->verify_token( $_POST[ $this->token_field_name() ] ?? ( $_POST['g-recaptcha-response'] ?? '' ) );
        if ( ! $result['success'] ) {
            DFSAS_Logger::log( [ 'form_type' => 'contact-form-7', 'ip' => DFSAS_Helpers::get_client_ip(), 'reason' => 'recaptcha_failed', 'details' => [ 'score' => $result['score'] ?? null ], 'score' => 10 ] );
            return true;
        }
        return false;
    }

    // ─── WPForms ──────────────────────────────────────────────────────────────

    public function verify_wpforms( array $fields, array $form_data ): void {
        $result = $this->verify_token( $_POST[ $this->token_field_name() ] ?? ( $_POST['g-recaptcha-response'] ?? '' ) );
        if ( ! $result['success'] ) {
            DFSAS_Logger::log( [ 'form_type' => 'wpforms', 'ip' => DFSAS_Helpers::get_client_ip(), 'reason' => 'recaptcha_failed', 'score' => 10 ] );
            wpforms()->process->errors['header'] = __( 'reCAPTCHA verification failed. Please try again.', 'dadsfam-antispam' );
        }
    }

    // ─── Ninja Forms ──────────────────────────────────────────────────────────

    public function verify_ninjaforms( array $data ): array {
        $result = $this->verify_token( $_POST[ $this->token_field_name() ] ?? ( $_POST['g-recaptcha-response'] ?? '' ) );
        if ( ! $result['success'] ) {
            DFSAS_Logger::log( [ 'form_type' => 'ninja-forms', 'ip' => DFSAS_Helpers::get_client_ip(), 'reason' => 'recaptcha_failed', 'score' => 10 ] );
            $data['errors']['fields']['dfsas_rc'] = __( 'reCAPTCHA verification failed.', 'dadsfam-antispam' );
        }
        return $data;
    }

    // ─── Gravity Forms ────────────────────────────────────────────────────────

    public function inject_gravity( string $button, array $form ): string {
        return $this->widget_html( 'gravity' ) . $button;
    }

    public function verify_gravity( array $validation_result ): array {
        $result = $this->verify_token( $_POST[ $this->token_field_name() ] ?? ( $_POST['g-recaptcha-response'] ?? '' ) );
        if ( ! $result['success'] ) {
            DFSAS_Logger::log( [ 'form_type' => 'gravity-forms', 'ip' => DFSAS_Helpers::get_client_ip(), 'reason' => 'recaptcha_failed', 'score' => 10 ] );
            $validation_result['is_valid'] = false;
        }
        return $validation_result;
    }

    // ─── Fluent Forms ─────────────────────────────────────────────────────────

    public function inject_fluent( string $html ): string {
        return $this->widget_html( 'fluent' ) . $html;
    }

    public function verify_fluent( array $insert_data, array $data, $form ): array {
        $result = $this->verify_token( $_POST[ $this->token_field_name() ] ?? ( $_POST['g-recaptcha-response'] ?? '' ) );
        if ( ! $result['success'] ) {
            DFSAS_Logger::log( [ 'form_type' => 'fluent-forms', 'ip' => DFSAS_Helpers::get_client_ip(), 'reason' => 'recaptcha_failed', 'score' => 10 ] );
            wp_send_json( [ 'errors' => [ 'restricted' => __( 'reCAPTCHA verification failed.', 'dadsfam-antispam' ) ] ], 422 );
        }
        return $insert_data;
    }

    // ─── WooCommerce Login ────────────────────────────────────────────────────

    public function verify_woo_login( WP_Error $errors, string $username, string $password ): WP_Error {
        if ( $errors->has_errors() ) return $errors;
        $result = $this->verify_token( $_POST[ $this->token_field_name() ] ?? ( $_POST['g-recaptcha-response'] ?? '' ) );
        if ( ! $result['success'] ) {
            DFSAS_Logger::log( [
                'form_type' => 'woo-login',
                'ip'        => DFSAS_Helpers::get_client_ip(),
                'reason'    => 'recaptcha_failed',
                'details'   => [ 'google_error' => $result['error'] ?? 'unknown', 'token_present' => ! empty( $_POST[ $this->token_field_name() ] ) ? 'yes' : 'no' ],
                'score'     => 10,
            ] );
            $errors->add( 'recaptcha_failed', __( 'reCAPTCHA verification failed. Please try again.', 'dadsfam-antispam' ) );
        }
        return $errors;
    }

    // ─── WP Login ─────────────────────────────────────────────────────────────

    public function verify_wp_login( $user, string $password ) {
        if ( is_wp_error( $user ) ) return $user;

        // WooCommerce My Account login is verified by verify_woo_login() via
        // woocommerce_process_login_errors BEFORE wp_authenticate_user fires.
        // reCAPTCHA tokens are single-use — verifying twice always fails the second time.
        if ( ! empty( $_POST['woocommerce-login-nonce'] ) ) return $user;

        $result = $this->verify_token( $_POST[ $this->token_field_name() ] ?? ( $_POST['g-recaptcha-response'] ?? '' ) );
        if ( ! $result['success'] ) {
            DFSAS_Logger::log( [ 'form_type' => 'wp-login', 'ip' => DFSAS_Helpers::get_client_ip(), 'reason' => 'recaptcha_failed', 'score' => 10 ] );
            return new WP_Error( 'recaptcha_failed', __( '<strong>Error</strong>: reCAPTCHA verification failed.', 'dadsfam-antispam' ) );
        }
        return $user;
    }

    // ─── WP Registration ──────────────────────────────────────────────────────

    public function verify_wp_registration( WP_Error $errors, string $login, string $email ): WP_Error {
        $result = $this->verify_token( $_POST[ $this->token_field_name() ] ?? ( $_POST['g-recaptcha-response'] ?? '' ) );
        if ( ! $result['success'] ) {
            DFSAS_Logger::log( [ 'form_type' => 'wp-registration', 'ip' => DFSAS_Helpers::get_client_ip(), 'reason' => 'recaptcha_failed', 'score' => 10 ] );
            $errors->add( 'recaptcha_failed', __( '<strong>Error</strong>: reCAPTCHA verification failed.', 'dadsfam-antispam' ) );
        }
        return $errors;
    }

    // ─── WP Lost Password ─────────────────────────────────────────────────────

    public function verify_lostpassword( WP_Error $errors ): WP_Error {
        $result = $this->verify_token( $_POST[ $this->token_field_name() ] ?? ( $_POST['g-recaptcha-response'] ?? '' ) );
        if ( ! $result['success'] ) {
            DFSAS_Logger::log( [ 'form_type' => 'wp-lostpassword', 'ip' => DFSAS_Helpers::get_client_ip(), 'reason' => 'recaptcha_failed', 'score' => 10 ] );
            $errors->add( 'recaptcha_failed', __( '<strong>Error</strong>: reCAPTCHA verification failed.', 'dadsfam-antispam' ) );
        }
        return $errors;
    }

    // ─── WooCommerce Checkout ─────────────────────────────────────────────────

    public function verify_woo_checkout(): void {
        $result = $this->verify_token( $_POST[ $this->token_field_name() ] ?? ( $_POST['g-recaptcha-response'] ?? '' ) );
        if ( ! $result['success'] ) {
            DFSAS_Logger::log( [ 'form_type' => 'woo-checkout', 'ip' => DFSAS_Helpers::get_client_ip(), 'reason' => 'recaptcha_failed', 'score' => 10 ] );
            wc_add_notice( __( 'reCAPTCHA verification failed. Please try again.', 'dadsfam-antispam' ), 'error' );
        }
    }

    // ─── Generic JS injection ─────────────────────────────────────────────────

    public function inject_generic_js(): void {
        $key   = esc_attr( $this->site_key );
        $field = esc_js( $this->token_field_name() );
        ?>
        <script>
        (function() {
            var DFSAS_RC_FIELD = '<?php echo $field; ?>';
            var DFSAS_RC_KEY   = '<?php echo $key; ?>';

            function initRecaptcha() {
                if (typeof grecaptcha === 'undefined') {
                    return; // reCAPTCHA script not loaded
                }
                <?php if ( $this->version === 'v3' ) : ?>
                grecaptcha.ready(function() {
                    document.querySelectorAll('form').forEach(function(form) {
                        var id = form.getAttribute('id') || '';
                        if (id === 'adminmenuwrap' || form.closest && form.closest('#adminmenuwrap')) return;

                        var tokenField = form.querySelector('[name="' + DFSAS_RC_FIELD + '"]');
                        if (!tokenField) {
                            tokenField = document.createElement('input');
                            tokenField.type  = 'hidden';
                            tokenField.name  = DFSAS_RC_FIELD;
                            form.appendChild(tokenField);
                        }

                        function fetchToken(cb) {
                            grecaptcha.execute(DFSAS_RC_KEY, {action: 'submit'})
                                .then(function(token) {
                                    tokenField.value = token;
                                    if (cb) cb();
                                })
                                .catch(function() {
                                    if (cb) cb(); // fail open — don't block the user
                                });
                        }

                        fetchToken();

                        form.addEventListener('submit', function(e) {
                            if (!tokenField.value) {
                                e.preventDefault();
                                fetchToken(function() { form.submit(); });
                            }
                        }, true);
                    });
                });
                <?php elseif ( $this->version === 'v2_invisible' ) : ?>
                document.querySelectorAll('form').forEach(function(form) {
                    var widget = form.querySelector('.g-recaptcha');
                    if (!widget) return;
                    form.addEventListener('submit', function(e) {
                        e.preventDefault();
                        grecaptcha.execute();
                    });
                });
                <?php endif; ?>
            }

            // window.load fires after ALL scripts (including external Google reCAPTCHA) are loaded
            // This is critical — our inline script must not run before Google's script initialises
            if (document.readyState === 'complete') {
                initRecaptcha();
            } else {
                window.addEventListener('load', initRecaptcha);
            }
        })();
        </script>
        <?php
    }

    // ─── Server-Side Token Verification ──────────────────────────────────────

    public function verify_token( string $token ): array {
        if ( empty( trim( $token ) ) ) {
            return [ 'success' => false, 'score' => 0, 'error' => 'missing_token' ];
        }

        $response = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', [
            'timeout' => 10,
            'body'    => [
                'secret'   => $this->secret_key,
                'response' => $token,
                'remoteip' => DFSAS_Helpers::get_client_ip(),
            ],
        ] );

        if ( is_wp_error( $response ) ) {
            // Network error — fail open (don't block the user)
            return [ 'success' => true, 'score' => 1, 'error' => 'network_error' ];
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $body['success'] ) ) {
            $error_codes = implode( ',', $body['error-codes'] ?? ['unknown'] );
            return [ 'success' => false, 'score' => 0, 'error' => $error_codes ];
        }

        // v3 score check
        if ( $this->version === 'v3' ) {
            $score = (float) ( $body['score'] ?? 0 );
            return [
                'success' => $score >= $this->threshold,
                'score'   => $score,
                'error'   => $score < $this->threshold ? 'score_too_low' : '',
            ];
        }

        return [ 'success' => true, 'score' => 1, 'error' => '' ];
    }

    // ─── Helper for admin AJAX test ───────────────────────────────────────────

    public static function get_keys_set(): bool {
        $opts = get_option( 'dfsas_options', [] );
        return ! empty( trim( $opts['recaptcha_site_key'] ?? '' ) )
            && ! empty( trim( $opts['recaptcha_secret_key'] ?? '' ) );
    }
}
