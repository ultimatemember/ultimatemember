<?php if ( ! defined( 'ABSPATH' ) ) exit;

$role_keys = get_option( 'um_roles', array() );
$role_keys = array_map( function( $item ) {
	return 'um_' . $item;
}, $role_keys );

global $wp_roles;
foreach ( $wp_roles->roles as $roleID => $role_data ) {
	if ( in_array( $roleID, $role_keys ) ) {
		continue;
	}

	$role_meta = get_option( "um_role_{$roleID}_meta", array() );
	if ( ! empty( $role_meta ) ) {
		if ( $role_meta['name'] === null ) {
			unset( $role_meta['name'] );
			update_option( "um_role_{$roleID}_meta", $role_meta );
		}
	}
}