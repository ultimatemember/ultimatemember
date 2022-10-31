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
