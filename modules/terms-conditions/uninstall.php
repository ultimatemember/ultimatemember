<?php
/**
 * Uninstall UM Terms Conditions
 *
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;


if ( ! defined( 'um_terms_conditions_path' ) ) {
	define( 'um_terms_conditions_path', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'um_terms_conditions_url' ) ) {
	define( 'um_terms_conditions_url', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'um_terms_conditions_plugin' ) ) {
	define( 'um_terms_conditions_plugin', plugin_basename( __FILE__ ) );
}

$options = get_option( 'um_options', array() );

if ( ! empty( $options['uninstall_on_delete'] ) ) {
	global $wpdb;
	$wpdb->query(
		"DELETE 
		FROM {$wpdb->postmeta} 
		WHERE meta_key LIKE '_um_register_use_gdpr' OR 
			  meta_key LIKE '_um_register_use_gdpr_content_id' OR
			  meta_key LIKE '_um_register_use_gdpr_toggle_show' OR
			  meta_key LIKE '_um_register_use_gdpr_toggle_hide' OR
			  meta_key LIKE '_um_register_use_gdpr_agreement' OR
			  meta_key LIKE '_um_register_use_gdpr_error_text'"
	);

	delete_option( 'um_terms_conditions_last_version_upgrade' );
	delete_option( 'um_terms_conditions_version' );
}
