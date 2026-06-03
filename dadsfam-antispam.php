<?php
/**
 * Plugin Name:       DadsFam Anti-Spam
 * Plugin URI:        https://dadsfam.co.za/plugins/anti-spam
 * Description:       Pro-grade form & email spam protection. Honeypots, time checks, IP/email/keyword blocklists, rate limiting, disposable email detection, Google reCAPTCHA (v2/v3), DNSBL, geo-blocking, and a full spam log — all on your own server. No subscriptions. No data sent anywhere. Supports Contact Form 7, WPForms, Ninja Forms, Gravity Forms, Fluent Forms, Pagelayer, WooCommerce, WordPress Login/Registration, and all generic HTML forms.
 * Version:           1.6.5
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            DadsFam
 * Author URI:        https://dadsfam.co.za
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       dadsfam-antispam
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'DFSAS_VERSION',  '1.6.5' );
define( 'DFSAS_FILE',     __FILE__ );
define( 'DFSAS_PATH',     plugin_dir_path( __FILE__ ) );
define( 'DFSAS_URL',      plugin_dir_url( __FILE__ ) );
define( 'DFSAS_BASENAME', plugin_basename( __FILE__ ) );

spl_autoload_register( static function ( string $class ): void {
    $map = [
        'DFSAS_Core'           => 'includes/class-core.php',
        'DFSAS_Honeypot'       => 'includes/class-honeypot.php',
        'DFSAS_TimeCheck'      => 'includes/class-time-check.php',
        'DFSAS_Blocklist'      => 'includes/class-blocklist.php',
        'DFSAS_RateLimiter'    => 'includes/class-rate-limiter.php',
        'DFSAS_ContentFilter'  => 'includes/class-content-filter.php',
        'DFSAS_EmailValidator' => 'includes/class-email-validator.php',
        'DFSAS_DNSBL'          => 'includes/class-dnsbl.php',
        'DFSAS_GeoBlock'       => 'includes/class-geo-block.php',
        'DFSAS_Logger'         => 'includes/class-logger.php',
        'DFSAS_Helpers'        => 'includes/class-helpers.php',
        'DFSAS_Pagelayer'      => 'includes/class-pagelayer.php',
        'DFSAS_ReCaptcha'      => 'includes/class-recaptcha.php',
        'DFSAS_License'        => 'includes/class-license.php',
        'DFSAS_ListUpdater'    => 'includes/class-list-updater.php',
        'DFSAS_Admin'          => 'admin/class-admin.php',
    ];
    if ( isset( $map[ $class ] ) ) {
        require_once DFSAS_PATH . $map[ $class ];
    }
} );

register_activation_hook( __FILE__, static function (): void {
    DFSAS_Logger::create_table();
    if ( ! get_option( 'dfsas_options' ) ) {
        update_option( 'dfsas_options', DFSAS_Core::default_options(), false );
    }
    update_option( 'dfsas_db_version', '1.0', false );
    update_option( 'dfsas_version', DFSAS_VERSION, false );
} );

register_deactivation_hook( __FILE__, static function (): void {
    wp_clear_scheduled_hook( 'dfsas_cleanup_logs' );
    wp_clear_scheduled_hook( 'dfsas_email_digest' );
    DFSAS_License::unschedule();
} );

add_action( 'plugins_loaded', static function (): void {
    // Boot license first so dfsas_is_pro filter is registered before modules load
    ( new DFSAS_License() )->init();
    DFSAS_Core::instance()->init();
}, 5 );
