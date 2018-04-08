<?php
$roles_associations = get_option( 'um_roles_associations' );

/*$um_social_login = get_posts( array(
	'post_type'     => 'um_social_login',
	'numberposts'   => -1,
	'fields'        => 'ids'
) );*/

$p_query = new WP_Query;
$um_social_login = $p_query->query( array(
	'post_type'         => 'um_social_login',
	'posts_per_page'    => -1,
	'fields'            => 'ids'
) );

foreach ( $um_social_login as $social_login_id ) {
	$assigned_role = get_post_meta( $social_login_id, '_um_assigned_role', true );

	if ( ! empty( $assigned_role ) ) {
		$assigned_role = $roles_associations[ $assigned_role ];
		update_post_meta( $social_login_id, '_um_assigned_role', $assigned_role );
	}
}