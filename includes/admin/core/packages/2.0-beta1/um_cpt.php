<?php
$roles_associations = get_option( 'um_roles_associations' );

//for metadata for all UM forms
//"use_global" meta  change to "_use_custom_settings"

//also update for forms metadata where "member" or "admin"
$forms_query = new WP_Query;
$forms = $forms_query->query( array(
	'post_type'         => 'um_form',
	'posts_per_page'    => -1,
	'fields'            => 'ids'
) );

foreach ( $forms as $form_id ) {
	$form_type = get_post_meta( $form_id, '_um_mode', true );

	if ( ! empty( $form_type ) ) {
		$use_globals = get_post_meta( $form_id, "_um_{$form_type}_use_globals", true );
		$use_custom_settings = empty( $use_globals ) ? true : false;

		update_post_meta( $form_id, "_um_{$form_type}_use_custom_settings", $use_custom_settings );
		delete_post_meta( $form_id, "_um_{$form_type}_use_globals" );

		$role = get_post_meta( $form_id, "_um_{$form_type}_role", true );
		if ( $role ) {
			update_post_meta( $form_id, "_um_{$form_type}_role", $roles_associations[ $role ] );
		}
	}
}

//for metadata for all UM Member Directories
//also update for forms metadata where "member" or "admin"
$forms_query = new WP_Query;
$member_directories = $forms_query->query( array(
	'post_type'         => 'um_directory',
	'posts_per_page'    => -1,
	'fields'            => 'ids'
) );

foreach ( $member_directories as $directory_id ) {
	$directory_roles = get_post_meta( $directory_id, '_um_roles', true );

	if ( ! empty( $directory_roles ) ) {
		foreach ( $directory_roles as $i => $role_k ) {
			$directory_roles[ $i ] = $roles_associations[ $role_k ];
		}

		update_post_meta( $directory_id, '_um_roles', $directory_roles );
	}

	$um_roles_can_search = get_post_meta( $directory_id, '_um_roles_can_search', true );

	if ( ! empty( $um_roles_can_search ) ) {
		foreach ( $um_roles_can_search as $i => $role_k ) {
			$um_roles_can_search[ $i ] = $roles_associations[ $role_k ];
		}

		update_post_meta( $directory_id, '_um_roles_can_search', $um_roles_can_search );
	}
}