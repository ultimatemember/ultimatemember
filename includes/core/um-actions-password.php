<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


/**
 * Process a new request
 *
 * @param $args
 */
function um_reset_password_process_hook( $args ) {
	$user = null;
		
	foreach ( $_POST as $key => $val ) {
		if( strstr( $key, "username_b") ){
			$user = trim( $val );
		}
	}

	if ( username_exists( $user ) ) {
		$data = get_user_by( 'login', $user );
		$user_email = $data->user_email;
	} else if( email_exists( $user ) ) {
		$data = get_user_by( 'email', $user );
		$user_email = $user;
	}

	UM()->password()->reset_request['user_id'] = $data->ID;
	UM()->password()->reset_request['user_email'] = $user_email;

	um_fetch_user( $data->ID );

	UM()->user()->password_reset();

	um_reset_user();

}
add_action( 'um_reset_password_process_hook', 'um_reset_password_process_hook' );


/**
 * Process a change request
 *
 * @param $args
 */
function um_change_password_process_hook( $args ) {
	extract(  $args );

	wp_set_password( $args['user_password'], $args['user_id'] );

	delete_user_meta( $args['user_id'], 'reset_pass_hash');
	delete_user_meta( $args['user_id'], 'reset_pass_hash_token');
	delete_user_meta( $args['user_id'], 'password_rst_attempts');

	/**
	 * UM hook
	 *
	 * @type action
	 * @title um_after_changing_user_password
	 * @description Hook that runs after user change their password
	 * @input_vars
	 * [{"var":"$user_id","type":"int","desc":"User ID"}]
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage add_action( 'um_after_changing_user_password', 'function_name', 10, 1 );
	 * @example
	 * <?php
	 * add_action( 'um_after_changing_user_password', 'my_after_changing_user_password', 10, 1 );
	 * function my_user_login_extra( $user_id ) {
	 *     // your code here
	 * }
	 * ?>
	 */
	do_action( 'um_after_changing_user_password', $args['user_id'] );


	if ( is_user_logged_in() ) {
		wp_logout();
	}

	exit( wp_redirect( um_get_core_page('login', 'password_changed') ) );
}
add_action( 'um_change_password_process_hook','um_change_password_process_hook' );


/**
 * Overrides password changed notification
 *
 * @param $args
 *
 * @return bool
 */
function um_send_password_change_email( $args ) {

	if ( ! is_array( $args ) )
		return false;

	/**
	 * @var $user_id
	 */
	extract( $args );

	if ( ! isset( $user_id ) )
		return false;

	um_fetch_user( $user_id );

	UM()->user()->password_changed();

	um_reset_user();

	return false;
}
add_action( 'send_password_change_email','um_send_password_change_email', 10, 1 );


/**
 * This is executed after changing password
 *
 * @param $user_id
 */
function um_after_changing_user_password( $user_id ) {

}
add_action( 'um_after_changing_user_password', 'um_after_changing_user_password' );


/**
 * Error handler: reset password
 *
 * @param $args
 */
function um_reset_password_errors_hook( $args ) {

	if ( $_POST[ UM()->honeypot ] != '' )
		wp_die('Hello, spam bot!','ultimate-member');

	$form_timestamp  = trim($_POST['timestamp']);
	$live_timestamp  = current_time( 'timestamp' );

	if ( $form_timestamp == '' && UM()->options()->get( 'enable_timebot' ) == 1 )
		wp_die( __('Hello, spam bot!','ultimate-member') );

	if ( $live_timestamp - $form_timestamp < 3 && UM()->options()->get( 'enable_timebot' ) == 1 )
		wp_die( __('Whoa, slow down! You\'re seeing this message because you tried to submit a form too fast and we think you might be a spam bot. If you are a real human being please wait a few seconds before submitting the form. Thanks!','ultimate-member') );
        
	$user = "";

	foreach ( $_POST as $key => $val ) {
		if( strstr( $key, "username_b") ){
			$user = trim( $val );
		}
	}

	if ( empty( $user ) ) {
		UM()->form()->add_error('username_b', __('Please provide your username or email','ultimate-member') );
	}

	if ( ( !is_email( $user ) && !username_exists( $user ) ) || ( is_email( $user ) && !email_exists( $user ) ) ) {
		UM()->form()->add_error('username_b', __('We can\'t find an account registered with that address or username','ultimate-member') );
	} else {

		if ( is_email( $user ) ) {
			$user_id = email_exists( $user );
		} else {
			$user_id = username_exists( $user );
		}

		$attempts = (int)get_user_meta( $user_id, 'password_rst_attempts', true );
		$is_admin = user_can( intval( $user_id ),'manage_options' );

		if ( UM()->options()->get( 'enable_reset_password_limit' ) ) { // if reset password limit is set

			if ( UM()->options()->get( 'disable_admin_reset_password_limit' ) &&  $is_admin ) {
				// Triggers this when a user has admin capabilities and when reset password limit is disabled for admins
			} else {
				$limit = UM()->options()->get( 'reset_password_limit_number' );
				if ( $attempts >= $limit ) {
					UM()->form()->add_error('username_b', __('You have reached the limit for requesting password change for this user already. Contact support if you cannot open the email','ultimate-member') );
				} else {
					update_user_meta( $user_id, 'password_rst_attempts', $attempts + 1 );
				}
			}

		}
	}

}
add_action( 'um_reset_password_errors_hook', 'um_reset_password_errors_hook' );


/**
 * Error handler: changing password
 *
 * @param $args
 */
function um_change_password_errors_hook( $args ) {
	if ( isset(  $_POST[ UM()->honeypot ]  ) && $_POST[ UM()->honeypot ] != '' ){
		wp_die('Hello, spam bot!','ultimate-member');
	}

	$form_timestamp  = trim($_POST['timestamp']);
	$live_timestamp  = current_time( 'timestamp' );

	if ( $form_timestamp == '' && UM()->options()->get( 'enable_timebot' ) == 1 )
		wp_die( __('Hello, spam bot!','ultimate-member') );

	if ( $live_timestamp - $form_timestamp < 3 && UM()->options()->get( 'enable_timebot' ) == 1 ) {
		wp_die( __('Whoa, slow down! You\'re seeing this message because you tried to submit a form too fast and we think you might be a spam bot. If you are a real human being please wait a few seconds before submitting the form. Thanks!','ultimate-member') );
	}

	$reset_pass_hash = '';

	if( isset( $_REQUEST['act'] ) && $_REQUEST['act']  == 'reset_password' && um_is_core_page('password-reset')  ){
		$reset_pass_hash = get_user_meta( $args['user_id'], 'reset_pass_hash', true );

	}

	if( !is_user_logged_in() && isset( $args ) && ! um_is_core_page('password-reset') ||
	    is_user_logged_in() && isset( $args['user_id'] ) && $args['user_id'] != get_current_user_id() ||
	    !is_user_logged_in() && isset( $_REQUEST['hash'] ) && $reset_pass_hash != $_REQUEST['hash'] && um_is_core_page('password-reset')
	){
		wp_die( __( 'This is not possible for security reasons.','ultimate-member') );
	}

	if ( isset( $args['user_password'] ) && empty( $args['user_password'] ) ) {
		UM()->form()->add_error('user_password', __('You must enter a new password','ultimate-member') );
	}

	if ( UM()->options()->get( 'reset_require_strongpass' ) ) {

		if ( strlen( utf8_decode( $args['user_password'] ) ) < 8 ) {
			UM()->form()->add_error('user_password', __('Your password must contain at least 8 characters','ultimate-member') );
		}

		if ( strlen( utf8_decode( $args['user_password'] ) ) > 30 ) {
			UM()->form()->add_error('user_password', __('Your password must contain less than 30 characters','ultimate-member') );
		}

		if ( ! UM()->validation()->strong_pass( $args['user_password'] ) ) {
			UM()->form()->add_error('user_password', __('Your password must contain at least one lowercase letter, one capital letter and one number','ultimate-member') );
		}

	}

	if ( isset( $args['confirm_user_password'] ) && empty( $args['confirm_user_password'] ) ) {
		UM()->form()->add_error('confirm_user_password', __('You must confirm your new password','ultimate-member') );
	}

	if ( isset( $args['user_password'] ) && isset( $args['confirm_user_password'] ) && $args['user_password'] != $args['confirm_user_password'] ) {
		UM()->form()->add_error('confirm_user_password', __('Your passwords do not match','ultimate-member') );
	}

}
add_action( 'um_change_password_errors_hook', 'um_change_password_errors_hook' );


/**
 * Hidden fields
 *
 * @param $args
 */
function um_change_password_page_hidden_fields( $args ) {
	?>

	<input type="hidden" name="_um_password_change" id="_um_password_change" value="1" />

	<input type="hidden" name="user_id" id="user_id" value="<?php echo $args['user_id']; ?>" />

	<?php
}
add_action( 'um_change_password_page_hidden_fields', 'um_change_password_page_hidden_fields' );


/**
 * Hidden fields
 *
 * @param $args
 */
function um_reset_password_page_hidden_fields( $args ) {
	?>

	<input type="hidden" name="_um_password_reset" id="_um_password_reset" value="1" />

	<?php
}
add_action( 'um_reset_password_page_hidden_fields', 'um_reset_password_page_hidden_fields' );


/**
 * Form content
 *
 * @param $args
 */
function um_reset_password_form( $args ) {

	$fields = UM()->builtin()->get_specific_fields('password_reset_text,username_b'); ?>

	<?php $output = null;
	foreach( $fields as $key => $data ) {
		$output .= UM()->fields()->edit_field( $key, $data );
	} echo $output; ?>

	<?php
	/**
	 * UM hook
	 *
	 * @type action
	 * @title um_after_password_reset_fields
	 * @description Hook that runs after user reset their password
	 * @input_vars
	 * [{"var":"$args","type":"array","desc":"Form data"}]
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage add_action( 'um_after_password_reset_fields', 'function_name', 10, 1 );
	 * @example
	 * <?php
	 * add_action( 'um_after_password_reset_fields', 'my_after_password_reset_fields', 10, 1 );
	 * function my_after_password_reset_fields( $args ) {
	 *     // your code here
	 * }
	 * ?>
	 */
	do_action( 'um_after_password_reset_fields', $args ); ?>

	<div class="um-col-alt um-col-alt-b">

		<div class="um-center"><input type="submit" value="<?php _e('Reset my password','ultimate-member'); ?>" class="um-button" id="um-submit-btn" /></div>

		<div class="um-clear"></div>

	</div>

	<?php

}
add_action( 'um_reset_password_form', 'um_reset_password_form' );


/**
 * Change password form
 */
function um_change_password_form() {

	$fields = UM()->builtin()->get_specific_fields('user_password'); ?>

	<?php $output = null;
	foreach( $fields as $key => $data ) {
		$output .= UM()->fields()->edit_field( $key, $data );
	}echo $output; ?>

	<div class="um-col-alt um-col-alt-b">

		<div class="um-center"><input type="submit" value="<?php _e('Change my password','ultimate-member'); ?>" class="um-button" id="um-submit-btn" /></div>

		<div class="um-clear"></div>

	</div>

	<?php

}
add_action( 'um_change_password_form', 'um_change_password_form' );