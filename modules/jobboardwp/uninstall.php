<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$install_class = UM()->call_class( 'umm\jobboardwp\Install' );

// Remove settings
$options = get_option( 'um_options', array() );
foreach ( $install_class->settings_defaults as $k => $v ) {
	unset( $options[ $k ] );
}
update_option( 'um_options', $options );

// Remove notifications if exists
$table_exists = $wpdb->query("SHOW TABLES LIKE '{$wpdb->prefix}um_notifications'" );
if (  ! empty( $table_exists ) ) {
	$wpdb->query(
		"DELETE
		FROM {$wpdb->prefix}um_notifications
		WHERE type = 'jb_job_approved' OR 
		      type = 'jb_job_expired'"
	);
}

// Remove UM Role metadata
$all_roles = UM()->roles()->get_roles();
$role_keys = array_keys( $all_roles );
foreach ( $role_keys as $role_key ) {
	$role_meta = get_option( "um_role_{$role_key}_meta", array() );

	$need_upgrade = false;
	$remove_keys  = array(
		'_um_disable_jobs_tab',
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
