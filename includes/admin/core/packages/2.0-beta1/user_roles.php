<?php
global $wpdb;

$response_roles_data = array();

//UM Roles to WP Roles
//all UM Roles from post type
$role_keys = array();

register_post_type( 'um_role', array(
	'labels' => array(
		'name'                  => __( 'User Roles' ),
		'singular_name'         => __( 'User Role' ),
		'add_new'               => __( 'Add New' ),
		'add_new_item'          => __('Add New User Role' ),
		'edit_item'             => __('Edit User Role'),
		'not_found'             => __('You did not create any user roles yet'),
		'not_found_in_trash'    => __('Nothing found in Trash'),
		'search_items'          => __('Search User Roles')
	),
	'show_ui' => true,
	'show_in_menu' => false,
	'public' => false,
	'supports' => array('title')
) );


$um_roles = get_posts( array(
	'post_type'         => 'um_role',
	'posts_per_page'    => -1,
	'post_status'       => 'publish'
) );

$roles_associations = array();

$all_wp_roles = array_keys( get_editable_roles() );

if ( ! empty( $um_roles ) ) {
	foreach ( $um_roles as $um_role ) {
		//old role key which inserted for each user to usermeta "role"
		$key_in_meta = $um_role->post_name;

		if ( preg_match( "/[a-z0-9]+$/i", $um_role->post_title ) ) {
			$role_key = sanitize_title( $um_role->post_title );
		} else {
			$auto_increment = UM()->options()->get( 'custom_roles_increment' );
			$auto_increment = ! empty( $auto_increment ) ? $auto_increment : 1;
			$role_key = 'custom_role_' . $auto_increment;

			$auto_increment++;
			UM()->options()->update( 'custom_roles_increment', $auto_increment );
		}

		if ( ! in_array( $role_key, $all_wp_roles ) ) {
			$role_keys[] = $role_key;
		}

		$all_role_metadata = $wpdb->get_results( $wpdb->prepare(
			"SELECT pm.meta_key,
                    pm.meta_value
            FROM {$wpdb->postmeta} pm
            WHERE pm.post_id = %d AND
                  pm.meta_key LIKE %s",
			$um_role->ID,
			"_um_%"
		), ARRAY_A );

		$role_metadata = array();
		if ( ! empty( $all_role_metadata ) ) {
			foreach ( $all_role_metadata as $metadata ) {

				if ( '_um_can_edit_roles' == $metadata['meta_key'] || '_um_can_delete_roles' == $metadata['meta_key']
				     || '_um_can_view_roles' == $metadata['meta_key'] || '_um_can_follow_roles' == $metadata['meta_key']
				     || '_um_can_friend_roles' == $metadata['meta_key'] || '_um_can_review_roles' == $metadata['meta_key'] ) {
					$metadata['meta_value'] = maybe_unserialize( $metadata['meta_value'] );
				}

				$role_metadata[ $metadata['meta_key'] ] = $metadata['meta_value'];
			}
		}

		if ( ! in_array( $role_key, $all_wp_roles ) ) {
			$role_meta = array_merge( $role_metadata, array(
				'name'              => $um_role->post_title,
				'wp_capabilities'   => array( 'read' => true ),
				'_um_is_custom'     => true,
			) );
		} else {
			$role_meta = $role_metadata;
		}

		//$old_key = ! empty( $role_meta['_um_core'] ) ? $role_meta['_um_core'] : $role_key;
		if ( ! in_array( $role_key, $all_wp_roles ) ) {
			$roles_associations[ $key_in_meta ] = 'um_' . $role_key;
		} else {
			$roles_associations[ $key_in_meta ] = $role_key;
		}


		$response_roles_data[] = array(
			'role_key'      => $role_key,
			'key_in_meta'   => $key_in_meta
		);

		if ( ! empty( $role_meta['_um_core'] ) )
			unset( $role_meta['_um_core'] );

		update_option( "um_role_{$role_key}_meta", $role_meta );
	}

	//update user role meta where role keys stored
	foreach ( $um_roles as $um_role ) {

		$key_in_meta = $um_role->post_name;

		$role_key = $roles_associations[ $key_in_meta ];
		if ( strpos( $role_key, 'um_' ) === 0 )
			$role_key = substr( $role_key, 3 );

		$role_meta = get_option( "um_role_{$role_key}_meta" );

		$role_metadata = array();
		if ( ! empty( $role_meta ) ) {
			foreach ( $role_meta as $metakey => $metadata ) {

				if ( '_um_can_edit_roles' == $metakey || '_um_can_delete_roles' == $metakey
				     || '_um_can_view_roles' == $metakey || '_um_can_follow_roles' == $metakey
				     || '_um_can_friend_roles' == $metakey || '_um_can_review_roles' == $metakey ) {

					if ( ! empty( $metadata ) ) {
						foreach ( $metadata as $i => $role_k ) {
							$metadata[ $i ] = $roles_associations[ $role_k ];
						}
					}
				} elseif ( '_um_profilec_upgrade_role' == $metakey ) {
					if ( isset( $roles_associations[ $metadata ] ) ) {
						$metadata = $roles_associations[ $metadata ];
					} else {
						$metadata = '';
					}
				}

				$role_meta[ $metakey ] = $metadata;
			}
		}

		update_option( "um_role_{$role_key}_meta", $role_meta );
	}
}

update_option( 'um_roles', $role_keys );

global $wp_roles, $wp_version;
if ( version_compare( $wp_version, '4.9', '<' ) ) {
	$wp_roles->_init();
	$wp_roles->reinit();
} elseif ( method_exists( $wp_roles, 'for_site' ) ) {
	$wp_roles->for_site( get_current_blog_id() );
}


//temporary option
update_option( 'um_roles_associations', $roles_associations );