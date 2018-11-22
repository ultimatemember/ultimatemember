<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Add a force redirect to from $_get
 *
 * @param $args
 */
function um_browser_url_redirect_to( $args ) {
	$url = '';

	if ( ! empty( $_REQUEST['redirect_to'] ) ) {

		$url = $_REQUEST['redirect_to'];

	} elseif ( ! empty( $args['after_login'] ) ) {

		switch ( $args['after_login'] ) {

			case 'redirect_admin':
				$url = admin_url();
				break;

			case 'redirect_profile':
				$url = um_user_profile_url();
				break;

			case 'redirect_url':
				$url = $args['redirect_url'];
				break;

			case 'refresh':
				$url = UM()->permalinks()->get_current_url();
				break;

		}
	}

	/**
	 * UM hook
	 *
	 * @type filter
	 * @title um_browser_url_redirect_to__filter
	 * @description Add redirect to field to form and change URL for it
	 * @input_vars
	 * [{"var":"$url","type":"string","desc":"Redirect to URL"}]
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage
	 * <?php add_filter( 'um_browser_url_redirect_to__filter', 'function_name', 10, 1 ); ?>
	 * @example
	 * <?php
	 * add_filter( 'um_browser_url_redirect_to__filter', 'my_browser_url_redirect_to', 10, 1 );
	 * function my_browser_url_redirect_to( $url ) {
	 *     // your code here
	 *     return $url;
	 * }
	 * ?>
	 */
	$url = apply_filters( 'um_browser_url_redirect_to__filter', $url );
	if ( ! empty( $url ) ) {
		echo '<input type="hidden" name="redirect_to" id="redirect_to" value="' . esc_url( $url ) . '" />';
	}
}
add_action( 'um_after_form_fields', 'um_browser_url_redirect_to' );


/**
 * Add a notice to form
 *
 * @param $args
 */
function um_add_update_notice( $args ) {
	extract( $args );

	$output 	= '';
	$err 		= '';
	$success 	= '';

	if ( isset( $_REQUEST['updated'] ) && !empty( $_REQUEST['updated'] ) && ! UM()->form()->errors ) {
		switch ( $_REQUEST['updated'] ) {
			default:
				/**
				 * UM hook
				 *
				 * @type filter
				 * @title um_custom_success_message_handler
				 * @description Add custom success message
				 * @input_vars
				 * [{"var":"$success","type":"string","desc":"Message"},
				 * {"var":"$updated","type":"array","desc":"Updated data"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage
				 * <?php add_filter( 'um_custom_success_message_handler', 'function_name', 10, 2 ); ?>
				 * @example
				 * <?php
				 * add_filter( 'um_custom_success_message_handler', 'my_custom_success_message', 10, 2 );
				 * function my_custom_success_message( $success, $updated ) {
				 *     // your code here
				 *     return $success;
				 * }
				 * ?>
				 */
				$success = apply_filters( "um_custom_success_message_handler", $success, $_REQUEST['updated'] );
				break;

			case 'account':
				$success = __('Your account was updated successfully.','ultimate-member');
				break;

			case 'password_changed':
				$success = __('You have successfully changed your password.','ultimate-member');
				break;

			case 'account_active':
				$success = __('Your account is now active! You can login.','ultimate-member');
				break;

		}
	}

	if ( isset( $_REQUEST['err'] ) && !empty( $_REQUEST['err'] ) && ! UM()->form()->errors ) {
		switch( $_REQUEST['err'] ) {

			default:
				/**
				 * UM hook
				 *
				 * @type filter
				 * @title um_custom_error_message_handler
				 * @description Add custom error message
				 * @input_vars
				 * [{"var":"$error","type":"string","desc":"Error message"},
				 * {"var":"$request_error","type":"array","desc":"Error data"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage
				 * <?php add_filter( 'um_custom_error_message_handler', 'function_name', 10, 2 ); ?>
				 * @example
				 * <?php
				 * add_filter( 'um_custom_error_message_handler', 'my_custom_error_message', 10, 2 );
				 * function my_custom_error_message( $error, $request_error ) {
				 *     // your code here
				 *     return $error;
				 * }
				 * ?>
				 */
				$err = apply_filters( "um_custom_error_message_handler", $err, $_REQUEST['err'] );
				if ( !$err )
					$err = __( 'An error has been encountered', 'ultimate-member' );
				break;

			case 'registration_disabled':
				$err = __('Registration is currently disabled','ultimate-member');
				break;

			case 'blocked_email':
				$err = __('This email address has been blocked.','ultimate-member');
				break;

			case 'blocked_domain':
				$err = __('We do not accept registrations from that domain.','ultimate-member');
				break;

			case 'blocked_ip':
				$err = __('Your IP address has been blocked.','ultimate-member');
				break;

			case 'inactive':
				$err = __('Your account has been disabled.','ultimate-member');
				break;

			case 'awaiting_admin_review':
				$err = __('Your account has not been approved yet.','ultimate-member');
				break;

			case 'awaiting_email_confirmation':
				$err = __('Your account is awaiting e-mail verification.','ultimate-member');
				break;

			case 'rejected':
				$err = __('Your membership request has been rejected.','ultimate-member');
				break;

		}
	}

	if ( isset( $err ) && !empty( $err ) ) {
		$output .= '<p class="um-notice err"><i class="um-icon-ios-close-empty" onclick="jQuery(this).parent().fadeOut();"></i>' . $err . '</p>';
	}

	if ( isset( $success ) && !empty( $success ) ) {
		$output .= '<p class="um-notice success"><i class="um-icon-ios-close-empty" onclick="jQuery(this).parent().fadeOut();"></i>' . $success . '</p>';
	}

	echo $output;
}
add_action( 'um_before_form', 'um_add_update_notice', 500 );