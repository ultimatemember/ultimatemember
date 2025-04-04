<?php
$roles_associations = get_option( 'um_roles_associations' );

/*$mc_lists = get_posts( array(
	'post_type'     => 'um_mailchimp',
	'numberposts'   => -1,
	'fields'        => 'ids'
) );*/

$p_query = new WP_Query;
$mc_lists = $p_query->query( array(
	'post_type'         => 'um_mailchimp',
	'posts_per_page'    => -1,
	'fields'            => 'ids'
) );

foreach ( $mc_lists as $list_id ) {
	$um_roles = get_post_meta( $list_id, '_um_roles', true );
	$um_roles = ! $um_roles ? array() : $um_roles;
	if ( ! empty( $um_roles ) ) {
		foreach ( $um_roles as $i => $role_k ) {
			$um_roles[ $i ] = $roles_associations[ $role_k ];
		}

		update_post_meta( $list_id, '_um_roles', $um_roles );
	}
}