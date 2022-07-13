<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$install_class = UM()->call_class( 'umm\forumwp\Install' );

// Remove settings
$options = get_option( 'um_options', array() );
foreach ( $install_class->settings_defaults as $k => $v ) {
	unset( $options[ $k ] );
}
update_option( 'um_options', $options );


// Remove postmeta
global $wpdb;
$wpdb->query(
	"DELETE 
	FROM {$wpdb->postmeta} 
	WHERE meta_key = '_um_forumwp_can_topic' OR 
	      meta_key = '_um_forumwp_can_reply' OR
	      meta_key = 'fmwp_um_notifications_need_mention' OR
	      meta_key = 'fmwp_um_notifications_mentioned' OR
	      meta_key = 'fmwp_um_notifications_subscribers_need_notified' OR
	      meta_key = 'fmwp_um_notifications_subscribers_notified'"
);

// Remove notifications if exists
$table_exists = $wpdb->query("SHOW TABLES LIKE '{$wpdb->prefix}um_notifications'" );
if (  ! empty( $table_exists ) ) {
	$wpdb->query(
		"DELETE
		FROM {$wpdb->prefix}um_notifications
		WHERE type = 'fmwp_mention' OR 
		      type = 'fmwp_new_reply' OR 
		      type = 'fmwp_new_topic'"
	);
}

// Remove UM Role metadata
$all_roles = UM()->roles()->get_roles();
$role_keys = array_keys( $all_roles );
foreach ( $role_keys as $role_key ) {
	$role_meta = get_option( "um_role_{$role_key}_meta", array() );

	$need_upgrade = false;
	$remove_keys  = array(
		'_um_disable_forumwp_tab',
		'_um_disable_create_forumwp_topics',
		'_um_lock_create_forumwp_topics_notice',
		'_um_disable_create_forumwp_replies',
		'_um_lock_create_forumwp_replies_notice',
	);

	foreach ( $remove_keys as $remove_key ) {
		if ( array_key_exists( $remove_key, $role_meta ) ) {
			$need_upgrade = true;
			unset( $role_meta[ $remove_key ] );
		}
	}

	if ( $need_upgrade ) {
		update_option( "um_role_{$role_key}_meta", $role_meta );
	}
}
