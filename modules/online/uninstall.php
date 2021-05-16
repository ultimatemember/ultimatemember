<?php
/**
 * Uninstall Ultimate Member - Online
 *
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	return;
}

if ( ! defined( 'um_online_path' ) ) {
	define( 'um_online_path', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'um_online_url' ) ) {
	define( 'um_online_url', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'um_online_plugin' ) ) {
	define( 'um_online_plugin', plugin_basename( __FILE__ ) );
}

$options = get_option( 'um_options', array() );
if ( ! empty( $options['uninstall_on_delete'] ) ) {
	if ( ! class_exists( 'um_ext\um_online\core\Online_Setup' ) ) {
		require_once um_online_path . 'includes/core/class-online-setup.php';
	}

	$online_setup = new um_ext\um_online\core\Online_Setup();

	//remove settings
	foreach ( $online_setup->settings_defaults as $k => $v ) {
		unset( $options[ $k ] );
	}

	update_option( 'um_options', $options );

	delete_option( 'um_online_last_version_upgrade' );
	delete_option( 'um_online_version' );
	delete_option( 'um_online_users_last_updated' );
	delete_option( 'widget_um_online_users' );
	delete_option( 'um_online_users' );
}