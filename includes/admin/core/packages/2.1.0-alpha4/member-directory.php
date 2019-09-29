<?php if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;

//update show messages button setting to hide messages button
$postmeta = $wpdb->get_results( "SELECT * FROM {$wpdb->postmeta} WHERE meta_key='_um_show_pm_button'", ARRAY_A );
if ( ! empty( $postmeta ) ) {
	foreach ( $postmeta as $row ) {
		$value = ( $row['meta_value'] == 0 ) ? 1 : 0;
		update_post_meta( $row['post_id'], '_um_hide_pm_button', $value );
	}

	$wpdb->delete( "{$wpdb->postmeta}",
		array(
			'meta_value'    => '_um_show_pm_button',
		),
		array(
			'%s'
		)
	);
}

// Update role_select and role_radio filters to role
$postmeta = $wpdb->get_results( "SELECT * FROM {$wpdb->postmeta} WHERE meta_key='_um_search_fields'", ARRAY_A );
if ( ! empty( $postmeta ) ) {
	foreach ( $postmeta as $row ) {
		$meta_value = maybe_unserialize( $row['meta_value'] );

		if ( is_array( $meta_value ) ) {
			$update = false;

			if ( false !== ( $index = array_search( 'role_select', $meta_value ) ) ) {
				unset( $meta_value[ array_search( 'role_select', $meta_value ) ] );
				$meta_value[] = 'role';
				$update = true;
			}

			if ( false !== ( $index = array_search( 'role_radio', $meta_value ) ) ) {
				unset( $meta_value[ array_search( 'role_radio', $meta_value ) ] );
				$meta_value[] = 'role';
				$update = true;
			}

			if ( $update ) {
				update_post_meta( $row['post_id'], '_um_search_fields', array_unique( $meta_value ) );
			}
		}
	}
}


$forms_query = new WP_Query;
$member_directories = $forms_query->query( array(
	'post_type'         => 'um_directory',
	'posts_per_page'    => -1,
	'fields'            => 'ids',
) );

if ( ! empty( $member_directories ) && ! is_wp_error( $member_directories ) ) {
	foreach ( $member_directories as $id ) {
		update_post_meta( $id, '_um_view_types', array( 'grid' ) );
		update_post_meta( $id, '_um_default_view', 'grid' );

		$search_enabled = get_post_meta( $id, '_um_search', true );
		if ( $search_enabled ) {
			$search_fields = $search_fields_old = get_post_meta( $id, '_um_search_fields', true );
			if ( ! empty( $search_fields ) ) {
				$can_search_roles = get_post_meta( $id, '_um_roles_can_search', true );

				if ( false !== ( $last_login_key = array_search( '_um_last_login', $search_fields ) ) ) {
					unset( $search_fields[ $last_login_key ] );
					$search_fields[] = 'last_login';
				}

				$filter_fields = array_intersect( $search_fields, array_keys( UM()->member_directory()->filter_fields ) );

				$general_search_fields = array_diff( $search_fields, array_keys( UM()->member_directory()->filter_fields ) );
				$search_active = count( $general_search_fields ) > 0 ? 1 : 0;
				update_post_meta( $id, '_um_search', $search_active );

				update_post_meta( $id, '_um_filters', 1 );
				update_post_meta( $id, '_um_roles_can_filter', $can_search_roles );
				update_post_meta( $id, '_um_search_fields', $filter_fields );
				update_post_meta( $id, '_um_search_fields_old', $search_fields_old );
			} else {
				update_post_meta( $id, '_um_search', 0 );
				update_post_meta( $id, '_um_filters', 0 );
			}
		}

		update_post_meta( $id, '_um_search_old', $search_enabled );
	}
}