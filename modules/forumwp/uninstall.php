<?php
/**
* Uninstall UM ForumWP
*
*/

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) return;


if ( ! defined( 'um_forumwp_path' ) ) {
	define( 'um_forumwp_path', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'um_forumwp_url' ) ) {
	define( 'um_forumwp_url', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'um_forumwp_plugin' ) ) {
	define( 'um_forumwp_plugin', plugin_basename( __FILE__ ) );
}

$options = get_option( 'um_options', array() );

if ( ! empty( $options['uninstall_on_delete'] ) ) {
	if ( ! class_exists( 'um_ext\um_forumwp\core\ForumWP_Setup' ) ) {
		require_once um_forumwp_path . 'includes/core/class-forumwp-setup.php';
	}

	$fmwp_setup = new um_ext\um_forumwp\core\ForumWP_Setup();

	//remove settings
	foreach ( $fmwp_setup->settings_defaults as $k => $v ) {
		unset( $options[ $k ] );
	}

	update_option( 'um_options', $options );

	global $wpdb;
	$wpdb->query(
		"DELETE 
		FROM {$wpdb->postmeta} 
		WHERE meta_key = '_um_forumwp_can_topic' OR 
		      meta_key = '_um_forumwp_can_reply'"
	);

	delete_option( 'um_forumwp_last_version_upgrade' );
	delete_option( 'um_forumwp_version' );
}