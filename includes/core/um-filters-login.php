<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Filter to customize errors
 *
 * @param $message
 *
 * @return string
 */
function um_custom_wp_err_messages( $message ) {

	if ( ! empty( $_REQUEST['err'] ) ) {
		switch ( sanitize_key( $_REQUEST['err'] ) ) {
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
		/** This action is documented in includes/core/um-actions-form.php */
		do_action( 'um_submit_form_errors_hook__blockedips', array(), null );
		/** This action is documented in includes/core/um-actions-form.php */
		do_action( 'um_submit_form_errors_hook__blockedemails', array( 'username' => $username ), null );
	}

	return $user;
}
add_filter( 'authenticate', 'um_wp_form_errors_hook_ip_test', 10, 3 );

/**
 * Login checks through the WordPress admin login.
 *
 * @param WP_Error|WP_User $user
 *
 * @return WP_Error|WP_User
 */
function um_wp_form_errors_hook_logincheck( $user ) {
	if ( is_wp_error( $user ) ) {
		return $user;
	}

	if ( isset( $user->ID ) ) {
		$status = UM()->common()->users()->get_status( $user->ID );

		$error = null;
		switch ( $status ) {
			case 'inactive':
				$error = new WP_Error( $status, __( 'Your account has been disabled.', 'ultimate-member' ) );
				break;
			case 'awaiting_admin_review':
				$error = new WP_Error( $status, __( 'Your account has not been approved yet.', 'ultimate-member' ) );
				break;
			case 'awaiting_email_confirmation':
				$error = new WP_Error( $status, __( 'Your account is awaiting email verification.', 'ultimate-member' ) );
				break;
			case 'rejected':
				$error = new WP_Error( $status, __( 'Your membership request has been rejected.', 'ultimate-member' ) );
				break;
		}

		if ( null !== $error ) {
			return $error;
		}
	}

	return $user;
}
add_filter( 'authenticate', 'um_wp_form_errors_hook_logincheck', 50 );

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
