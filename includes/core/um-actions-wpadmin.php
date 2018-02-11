<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Checks if user can access the backend
 */
function um_block_wpadmin_by_user_role() {
	if ( is_admin() && ! defined( 'DOING_AJAX' ) && um_user( 'ID' ) && ! um_user( 'can_access_wpadmin' ) && ! is_super_admin( um_user( 'ID' ) ) ) {
		um_redirect_home();
	}
}
add_action( 'init', 'um_block_wpadmin_by_user_role', 99 );


/**
 * Hide admin bar appropriately
 *
 * @param $content
 *
 * @return bool
 */
function um_control_admin_bar( $content ) {
	if ( is_user_logged_in() ) {
		if ( um_user( 'can_not_see_adminbar' ) ) {
			return false;
		}
		return true;
	}

	return $content;
}
add_filter( 'show_admin_bar' , 'um_control_admin_bar', 9999, 1 );


/**
 * Fix permission for admin bar
 */
function um_force_admin_bar() {
	um_reset_user();
}
add_action( 'wp_footer', 'um_force_admin_bar' );