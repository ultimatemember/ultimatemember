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

// update member directories settings
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

				if ( false !== ( $user_rating_key = array_search( 'user_rating', $search_fields ) ) ) {
					unset( $search_fields[ $user_rating_key ] );
					$search_fields[] = 'filter_rating';
				}

				$filter_fields = array_intersect( $search_fields, array_keys( UM()->member_directory()->filter_fields ) );

				$general_search_fields = array_diff( $search_fields, array_keys( UM()->member_directory()->filter_fields ) );
				$search_active = count( $general_search_fields ) > 0 ? 1 : 0;
				update_post_meta( $id, '_um_search', $search_active );

				update_post_meta( $id, '_um_filters', 1 );
				update_post_meta( $id, '_um_roles_can_filter', $can_search_roles );
				update_post_meta( $id, '_um_search_fields', $filter_fields );
				update_post_meta( $id, '_um_search_fields_old', $search_fields_old );

				update_post_meta( $id, '_um_filters_expanded', 1 );
				update_post_meta( $id, '_um_filters_is_collapsible', 0 );
			} else {
				update_post_meta( $id, '_um_search', 0 );
				update_post_meta( $id, '_um_filters', 0 );
			}
		}

		$default_filters = get_post_meta( $id, '_um_search_filters', true );
		$default_filters_new = array();
		if ( ! empty( $default_filters ) ) {
			$default_filters_queries = explode( '&', $default_filters );
			foreach ( $default_filters_queries as $filter_query_part ) {
				$filter_query_parts = explode( '=', $filter_query_part );

				$filter_key = $filter_query_parts[0];
				$filter_value = $filter_query_parts[1];

				$default_filters_new[ $filter_key ] = $filter_value;
			}
		}

		update_post_meta( $id, '_um_search_filters', $default_filters_new );
		update_post_meta( $id, '_um_search_filters_old', $default_filters );

		update_post_meta( $id, '_um_search_old', $search_enabled );
	}
}


// for user tags settings
if ( UM()->options()->get( 'members_page' ) ) {
	$member_directory_id = false;

	$page_id = UM()->config()->permalinks['members'];
	if ( ! empty( $page_id ) ) {
		$members_page = get_post( $page_id );
		if ( ! empty( $members_page ) && ! is_wp_error( $members_page ) ) {
			if ( ! empty( $members_page->post_content ) ) {
				preg_match( '/\[ultimatemember[^\]]*?form_id\=[\'"]*?(\d+)[\'"]*?/i', $members_page->post_content, $matches );
				if ( ! empty( $matches[1] ) && is_numeric( $matches[1] ) ) {
					$member_directory_id = $matches[1];
				}
			}
		}
	}

	if ( $member_directory_id ) {
		UM()->options()->update( 'user_tags_base_directory' , $member_directory_id );
	}

	UM()->options()->update( 'user_tags_slug' , 'user-tags' );

	UM()->rewrite()->reset_rules();
}


// update groups settings
$groups_query = new WP_Query;
$groups = $groups_query->query( array(
	'post_type'         => 'um_groups',
	'posts_per_page'    => -1,
	'fields'            => 'ids',
) );

if ( ! empty( $groups ) && ! is_wp_error( $groups ) ) {
	foreach ( $groups as $id ) {
		$filters_enabled = get_post_meta( $id, '_um_groups_invites_search_fields', true );
		if ( ! empty( $filters_enabled ) ) {
			$filters = $filters_old = get_post_meta( $id, '_um_groups_invites_fields', true );
			if ( ! empty( $filters ) ) {
				if ( false !== ( $last_login_key = array_search( '_um_last_login', $filters ) ) ) {
					unset( $filters[ $last_login_key ] );
					$filters[] = 'last_login';
				}

				if ( false !== ( $user_rating_key = array_search( 'user_rating', $filters ) ) ) {
					unset( $filters[ $user_rating_key ] );
					$filters[] = 'filter_rating';
				}

				$filter_fields = array_intersect( $filters, array_keys( UM()->member_directory()->filter_fields ) );

				$general_search_fields = array_diff( $filters, array_keys( UM()->member_directory()->filter_fields ) );
				$search_active = count( $general_search_fields ) > 0 ? 1 : 0;

				update_post_meta( $id, '_um_groups_invites_fields', $filter_fields );
				update_post_meta( $id, '_um_groups_invites_fields_old', $filters_old );

				$search_enabled = get_post_meta( $id, '_um_groups_invites_search', true );
				if ( empty( $search_enabled ) && $search_active ) {
					update_post_meta( $id, '_um_groups_invites_search', true );
					update_post_meta( $id, '_um_groups_invites_search_old', $search_enabled );
				}
			}
		}
	}
}