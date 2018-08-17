<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Change lost password url in UM Login form
 * @param  string $lostpassword_url 
 * @return string                  
 */
function um_lostpassword_url( $lostpassword_url ) {

	if( um_is_core_page("login") ){
	    return um_get_core_page("password-reset");
	}

	return $lostpassword_url;
}
add_filter( 'lostpassword_url',  'um_lostpassword_url', 10, 1 );

/**
 * Login checks thru the frontend login
 *
 * @param $args
 */
function um_login_check_user_data( $user ) {
	if ( ! isset( $user->ID ) ) {
		return $user;
	}

	if( !UM()->form()->validate_blocked_ips() ) {
		return new WP_Error( 'blocked_ip', UM()->form()->get_notice_by_code( 'blocked_ip' ) );
	}

	if ( $emails = UM()->options()->get( 'blocked_emails' ) ) {
		$domain       = explode( '@', $user->user_email );
		$check_domain = str_replace( $domain[0], '*', $user->user_email );

		if ( in_array( $user->user_email, $emails ) ) {
			return new WP_Error( 'blocked_email', UM()->form()->get_notice_by_code( 'blocked_email' ) );
		}

		if ( in_array( $check_domain, $emails ) ) {
			return new WP_Error( 'blocked_domain', UM()->form()->get_notice_by_code( 'blocked_domain' ) );
		}
	}

	um_fetch_user( $user->ID );

	$status = um_user('account_status'); // account status
	switch( $status ) {

		// If user can't login to site...
		case 'inactive':
		case 'awaiting_admin_review':
		case 'awaiting_email_confirmation':
		case 'rejected':
			um_reset_user();
			return new WP_Error( $status, UM()->form()->get_notice_by_code( $status ) );
	}

	return $user;
}
add_filter( 'authenticate', 'um_login_check_user_data', 9999 );