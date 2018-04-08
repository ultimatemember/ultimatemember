<?php
$roles_associations = get_option( 'um_roles_associations' );

//Content Restriction transfer

//for check all post types and taxonomies
$all_post_types = get_post_types( array( 'public' => true ) );

$all_taxonomies = get_taxonomies( array( 'public' => true ) );
$exclude_taxonomies = UM()->excluded_taxonomies();

foreach ( $all_taxonomies as $key => $taxonomy ) {
	if ( in_array( $key, $exclude_taxonomies ) ) {
		unset( $all_taxonomies[ $key ] );
	}
}

foreach ( $all_post_types as $key => $value ) {
	$all_post_types[ $key ] = true;
}

foreach ( $all_taxonomies as $key => $value ) {
	$all_taxonomies[ $key ] = true;
}

UM()->options()->update( 'restricted_access_post_metabox', $all_post_types );
UM()->options()->update( 'restricted_access_taxonomy_metabox', $all_taxonomies );


$roles_array = UM()->roles()->get_roles( false, array( 'administrator' ) );

/*$posts = get_posts( array(
	'post_type'     => 'any',
	'meta_key'      => '_um_custom_access_settings',
	'meta_value'    => '1',
	'fields'        => 'ids',
	'numberposts'   => -1
) );*/

$p_query = new WP_Query;
$posts = $p_query->query( array(
	'post_type'         => 'any',
	'meta_key'          => '_um_custom_access_settings',
	'meta_value'        => '1',
	'posts_per_page'    => -1,
	'fields'            => 'ids'
) );

if ( ! empty( $posts ) ) {
	foreach ( $posts as $post_id ) {
		$um_accessible = get_post_meta( $post_id, '_um_accessible', true );
		$um_access_roles = get_post_meta( $post_id, '_um_access_roles', true );
		$um_access_redirect = ( $um_accessible == '2' ) ? get_post_meta( $post_id, '_um_access_redirect', true ) : get_post_meta( $post_id, '_um_access_redirect2', true );

		$access_roles = array();
		if ( ! empty( $um_access_roles ) ) {
			foreach ( $roles_array as $role => $role_label ) {
				//if ( in_array( substr( $role, 3 ), $um_access_roles ) )
				if ( false !== array_search( $role, $roles_associations ) && in_array( array_search( $role, $roles_associations ), $um_access_roles ) )
					$access_roles[ $role ] = '1';
				else
					$access_roles[ $role ] = '0';
			}
		} else {
			foreach ( $roles_array as $role => $role_label ) {
				$access_roles[ $role ] = '0';
			}
		}

		$restrict_options = array(
			'_um_custom_access_settings'        => '1',
			'_um_accessible'                    => $um_accessible,
			'_um_access_roles'                  => $access_roles,
			'_um_noaccess_action'               => '1',
			'_um_restrict_by_custom_message'    => '0',
			'_um_restrict_custom_message'       => '',
			'_um_access_redirect'               => '1',
			'_um_access_redirect_url'           => ! empty( $um_access_redirect ) ? $um_access_redirect : '',
			'_um_access_hide_from_queries'      => '0',
		);

		update_post_meta( $post_id, 'um_content_restriction', $restrict_options );
	}
}


$all_taxonomies = get_taxonomies( array( 'public' => true ) );
$exclude_taxonomies = UM()->excluded_taxonomies();

foreach ( $all_taxonomies as $key => $taxonomy ) {
	if ( in_array( $key , $exclude_taxonomies ) )
		continue;

	$terms = get_terms( array(
		'taxonomy'      => $taxonomy,
		'hide_empty'    => false,
		'fields'        => 'ids'
	) );

	if ( empty( $terms ) )
		continue;

	foreach ( $terms as $term_id ) {
		$term_meta = get_option( "category_{$term_id}" );

		if ( empty( $term_meta ) )
			continue;

		$um_accessible = ! empty( $term_meta['_um_accessible'] ) ? $term_meta['_um_accessible'] : false;
		$um_access_roles = ! empty( $term_meta['_um_roles'] ) ? $term_meta['_um_roles'] : array();
		$redirect = ! empty( $term_meta['_um_redirect'] ) ? $term_meta['_um_redirect'] : '';
		$redirect2 = ! empty( $term_meta['_um_redirect2'] ) ? $term_meta['_um_redirect2'] : '';
		$um_access_redirect = ( $um_accessible == '2' ) ? $redirect : $redirect2;

		$access_roles = array();
		if ( ! empty( $um_access_roles ) ) {
			foreach ( $roles_array as $role => $role_label ) {
				if ( false !== array_search( $role, $roles_associations ) && in_array( array_search( $role, $roles_associations ), $um_access_roles ) )
					$access_roles[ $role ] = '1';
				else
					$access_roles[ $role ] = '0';
			}
		} else {
			foreach ( $roles_array as $role => $role_label ) {
				$access_roles[ $role ] = '0';
			}
		}

		$restrict_options = array(
			'_um_custom_access_settings'        => '1',
			'_um_accessible'                    => $um_accessible,
			'_um_access_roles'                  => $access_roles,
			'_um_noaccess_action'               => '1',
			'_um_restrict_by_custom_message'    => '0',
			'_um_restrict_custom_message'       => '',
			'_um_access_redirect'               => '1',
			'_um_access_redirect_url'           => ! empty( $um_access_redirect ) ? $um_access_redirect : '',
			'_um_access_hide_from_queries'      => '0',
		);

		update_term_meta( $term_id, 'um_content_restriction', $restrict_options );
	}
}
