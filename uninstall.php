<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

global $wpdb;

// Remove options
delete_option( 'dfsas_options' );
delete_option( 'dfsas_version' );
delete_option( 'dfsas_db_version' );
delete_option( 'dfsas_license_key' );
delete_option( 'dfsas_license_status' );
delete_option( 'dfsas_license_expiry' );
delete_option( 'dfsas_license_hash' );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_dfsas_%'" );
delete_option( 'dfsas_disposable_domains' );
delete_option( 'dfsas_domains_last_updated' );
delete_option( 'dfsas_domains_last_count' );

// Drop log table
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}dfsas_spam_log" );

// Clear any transients
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_dfsas_%'" );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_dfsas_%'" );
