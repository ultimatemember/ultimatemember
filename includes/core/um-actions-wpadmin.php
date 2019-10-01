<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Checks if user can access the backend
 */
function um_block_wpadmin_by_user_role() {
	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
		$action = empty( $_REQUEST['action'] ) ? '' : $_REQUEST['action'];

		// filter that it's not admin_post or admin_post_nopriv request
		$url_attr = parse_url( UM()->permalinks()->get_current_url() );
		if ( is_user_logged_in() && ! empty( $action ) && $url_attr['path'] == '/wp-admin/admin-post.php' ) {
			return;
		}

		if ( um_user( 'ID' ) && ! um_user( 'can_access_wpadmin' ) && ! is_super_admin( um_user( 'ID' ) ) ) {
			um_redirect_home();
		}
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
	if ( is_user_logged_in() && um_user( 'can_not_see_adminbar' ) ) {
		return false;
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