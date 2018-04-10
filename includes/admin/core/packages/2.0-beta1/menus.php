<?php
$roles_associations = get_option( 'um_roles_associations' );

/**
 * Transferring menu restriction data
 */
$menus = get_posts( array(
	'post_type' => 'nav_menu_item',
	'meta_query' => array(
		array(
			'key' => 'menu-item-um_nav_roles',
			'compare' => 'EXISTS',
		)
	),
	'numberposts' => -1,
) );

foreach ( $menus as $menu ) {
	$menu_roles = get_post_meta( $menu->ID, 'menu-item-um_nav_roles', true );

	foreach ( $menu_roles as $i => $role_k ) {
		$menu_roles[ $i ] = $roles_associations[ $role_k ];
	}

	update_post_meta( $menu->ID, 'menu-item-um_nav_roles', $menu_roles );
}