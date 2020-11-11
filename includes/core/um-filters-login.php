<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


/**
 * Filter to allow whitelisted IP to access the wp-admin login
 *
 * @param $allowed
 *
 * @return int
 */
function um_whitelisted_wpadmin_access( $allowed ) {
	$ips = UM()->options()->get( 'wpadmin_allow_ips' );
		
	if ( !$ips )
		return $allowed;
		
	$ips = array_map("rtrim", explode("\n", $ips));
	$user_ip = um_user_ip();

	if ( in_array( $user_ip, $ips ) )
		$allowed = 1;
		
	return $allowed;
}
add_filter( 'um_whitelisted_wpadmin_access', 'um_whitelisted_wpadmin_access' );


/**
 * Filter to customize errors
 *
 * @param $message
 *
 * @return string
 */
function um_custom_wp_err_messages( $message ) {

	if ( ! empty( $_REQUEST['err'] ) ) {
		switch( $_REQUEST['err'] ) {
			case 'blocked_email':
				$err = __( 'This email address has been blocked.', 'ultimate-member' );
				break;
			case 'blocked_ip':
				$err = __( 'Your IP address has been blocked.', 'ultimate-member' );
				break;
		}
	}

	if ( isset( $err ) ) {
		$message = '<div class="login" id="login_error">' . $err . '</div>';
	}

	return $message;
}
add_filter( 'login_message', 'um_custom_wp_err_messages' );


/**
 * Check for blocked IPs or Email on wp-login.php form
 *
 * @param $user
 * @param $username
 * @param $password
 *
 * @return mixed
 */
function um_wp_form_errors_hook_ip_test( $user, $username, $password ) {
	if ( ! empty( $username ) ) {
		do_action( 'um_submit_form_errors_hook__blockedips', array() );
		do_action( 'um_submit_form_errors_hook__blockedemails', array( 'username' => $username ) );
	}

	return $user;
}
add_filter( 'authenticate', 'um_wp_form_errors_hook_ip_test', 10, 3 );


/**
 * Login checks thru the wordpress admin login
 *
 * @param $user
 * @param $username
 * @param $password
 *
 * @return WP_Error|WP_User
 */
function um_wp_form_errors_hook_logincheck( $user, $username, $password ) {

	if ( isset( $user->ID ) ) {

		um_fetch_user( $user->ID );
		$status = um_user( 'account_status' );

		switch( $status ) {
			case 'inactive':
				return new WP_Error( $status, __( 'Your account has been disabled.', 'ultimate-member' ) );
				break;
			case 'awaiting_admin_review':
				return new WP_Error( $status, __( 'Your account has not been approved yet.', 'ultimate-member' ) );
				break;
			case 'awaiting_email_confirmation':
				return new WP_Error( $status, __( 'Your account is awaiting e-mail verification.', 'ultimate-member' ) );
				break;
			case 'rejected':
				return new WP_Error( $status, __( 'Your membership request has been rejected.', 'ultimate-member' ) );
				break;
		}

	}

	return $user;

}
add_filter( 'authenticate', 'um_wp_form_errors_hook_logincheck', 50, 3 );


/**
 * Change lost password url in UM Login form
 * @param  string $lostpassword_url 
 * @return string                  
 */
function um_lostpassword_url( $lostpassword_url ) {

	if ( um_is_core_page( 'login' ) ) {
		return um_get_core_page( 'password-reset' );
	}

	return $lostpassword_url;
}
add_filter( 'lostpassword_url', 'um_lostpassword_url', 10, 1 );


/**
 * When a user is logged out, ensure they have a unique nonce by using browser and IP
 *
 * @since  2.1.13
 *
 * @param  int $uid User ID.
 * @return string
 */
function um_nonce_user_logged_out( $uid, $action ) {
	if ( !is_user_logged_in() && empty( $uid ) && strpos( $action, 'um' ) === 0 ) {
		$u = '';
		$u .= isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '';
		$u .= isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';
		$key = md5( $u );
		$uid = get_transient( $key );
		if ( empty( $uid ) ) {
			$uid = md5( $key . rand() );
			set_transient( $key, $uid, WEEK_IN_SECONDS );
		}
	}
	return $uid;
}
add_filter( 'nonce_user_logged_out', 'um_nonce_user_logged_out', 20, 2 );
