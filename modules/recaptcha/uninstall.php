<?php
/**
 * Uninstall UM Recaptcha
 *
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;


if ( ! defined( 'um_recaptcha_path' ) ) {
	define( 'um_recaptcha_path', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'um_recaptcha_url' ) ) {
	define( 'um_recaptcha_url', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'um_recaptcha_plugin' ) ) {
	define( 'um_recaptcha_plugin', plugin_basename( __FILE__ ) );
}

$options = get_option( 'um_options', array() );
if ( ! empty( $options['uninstall_on_delete'] ) ) {
	if ( ! class_exists( 'um_ext\um_recaptcha\core\Recaptcha_Setup' ) ) {
		require_once um_recaptcha_path . 'includes/core/class-recaptcha-setup.php';
	}

	$recaptcha_setup = new um_ext\um_recaptcha\core\Recaptcha_Setup();

	//remove settings
	foreach ( $recaptcha_setup->settings_defaults as $k => $v ) {
		unset( $options[$k] );
	}

	update_option( 'um_options', $options );

	delete_option( 'um_recaptcha_last_version_upgrade' );
	delete_option( 'um_recaptcha_version' );
}
