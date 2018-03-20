<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Error procesing hook for login
 *
 * @param $args
 */
function um_submit_form_errors_hook_login( $args ) {
	$is_email = false;

	$form_id = $args['form_id'];
	$mode = $args['mode'];
	$user_password = $args['user_password'];


	if ( isset( $args['username'] ) && $args['username'] == '' ) {
		UM()->form()->add_error( 'username',  __('Please enter your username or email','ultimate-member') );
	}

	if ( isset( $args['user_login'] ) && $args['user_login'] == '' ) {
		UM()->form()->add_error( 'user_login',  __('Please enter your username','ultimate-member') );
	}

	if ( isset( $args['user_email'] ) && $args['user_email'] == '' ) {
		UM()->form()->add_error( 'user_email',  __('Please enter your email','ultimate-member') );
	}

	if ( isset( $args['username'] ) ) {
		$field = 'username';
		if ( is_email( $args['username'] ) ) {
			$is_email = true;
			$data = get_user_by('email', $args['username'] );
			$user_name = (isset ( $data->user_login ) ) ? $data->user_login : null;
		} else {
			$user_name  = $args['username'];
		}
	} else if ( isset( $args['user_email'] ) ) {
		$field = 'user_email';
		$is_email = true;
		$data = get_user_by('email', $args['user_email'] );
		$user_name = (isset ( $data->user_login ) ) ? $data->user_login : null;
	} else {
		$field = 'user_login';
		$user_name = $args['user_login'];
	}

	if ( !username_exists( $user_name ) ) {
		if ( $is_email ) {
			UM()->form()->add_error( $field,  __(' Sorry, we can\'t find an account with that email address','ultimate-member') );
		} else {
			UM()->form()->add_error( $field,  __(' Sorry, we can\'t find an account with that username','ultimate-member') );
		}
	} else {
		if ( $args['user_password'] == '' ) {
			UM()->form()->add_error( 'user_password',  __('Please enter your password','ultimate-member') );
		}
	}

	$user = get_user_by( 'login', $user_name );
	if ( $user && wp_check_password( $args['user_password'], $user->data->user_pass, $user->ID) ) {
		UM()->login()->auth_id = username_exists( $user_name );
	} else {
		UM()->form()->add_error( 'user_password',  __('Password is incorrect. Please try again.','ultimate-member') );
	}

	$user = apply_filters( 'authenticate', null, $user_name, $args['user_password'] );
		
	$authenticate_user = apply_filters( 'wp_authenticate_user', $user_name, $args['user_password'] );
		
	// @since 4.18 replacement for 'wp_login_failed' action hook
	// see WP function wp_authenticate()
	$ignore_codes = array('empty_username', 'empty_password');

	if ( is_wp_error( $user ) && ! in_array( $user->get_error_code(), $ignore_codes ) ) {
			
		UM()->form()->add_error( $user->get_error_code(),  __( $user->get_error_message() ,'ultimate-member') );
	}

	if( is_wp_error( $authenticate_user ) && ! in_array( $authenticate_user->get_error_code(), $ignore_codes ) ){

		UM()->form()->add_error( $authenticate_user->get_error_code(),  __( $authenticate_user->get_error_message() ,'ultimate-member') );
		
	}

	// if there is an error notify wp
	if( UM()->form()->has_error( $field ) || UM()->form()->has_error( $user_password ) || UM()->form()->count_errors() > 0 ) {
		do_action( 'wp_login_failed', $user_name );
	}
}
add_action( 'um_submit_form_errors_hook_login', 'um_submit_form_errors_hook_login', 10 );


/**
 * Display the login errors from other plugins
 *
 * @param $args
 */
function um_display_login_errors( $args ) {
	$error = '';
	
	if( UM()->form()->count_errors() > 0 ) {
		$errors = UM()->form()->errors;
		// hook for other plugins to display error
		$error_keys = array_keys( $errors );
		}

		if( isset( $args['custom_fields'] ) ){
			$custom_fields = $args['custom_fields'];
		}

		if( ! empty( $error_keys ) && ! empty( $custom_fields ) ){
			foreach( $error_keys as $error ){
				if( trim( $error ) && ! isset( $custom_fields[ $error ] )  && ! empty(  $errors[ $error ] ) ){
					$error_message = apply_filters( 'login_errors', $errors[ $error ]  );
					echo '<p class="um-notice err um-error-code-'.$error.'"><i class="um-icon-ios-close-empty" onclick="jQuery(this).parent().fadeOut();"></i>' . $error_message  . '</p>';
				}
			}
		}
	}
add_action( 'um_before_login_fields', 'um_display_login_errors' );


/**
 * Login checks thru the frontend login
 *
 * @param $args
 */
function um_submit_form_errors_hook_logincheck( $args ) {
	// Logout if logged in
	if ( is_user_logged_in() ) {
		wp_logout();
	}

	$user_id = ( isset( UM()->login()->auth_id ) ) ? UM()->login()->auth_id : '';
	um_fetch_user( $user_id );

	$status = um_user('account_status'); // account status
	switch( $status ) {

		// If user can't login to site...
		case 'inactive':
		case 'awaiting_admin_review':
		case 'awaiting_email_confirmation':
		case 'rejected':
		um_reset_user();
		exit( wp_redirect(  add_query_arg( 'err', esc_attr( $status ), UM()->permalinks()->get_current_url() ) ) );
		break;

	}

	if ( isset( $args['form_id'] ) && $args['form_id'] == UM()->shortcodes()->core_login_form() &&  UM()->form()->errors && !isset( $_POST[ UM()->honeypot ] ) ) {
		exit( wp_redirect( um_get_core_page('login') ) );
	}

}
add_action( 'um_submit_form_errors_hook_logincheck', 'um_submit_form_errors_hook_logincheck', 9999 );


/**
 * Store last login timestamp
 *
 * @param $user_id
 */
function um_store_lastlogin_timestamp( $user_id ) {
	update_user_meta( $user_id, '_um_last_login', current_time( 'timestamp' ) );
}
add_action( 'um_on_login_before_redirect', 'um_store_lastlogin_timestamp', 10, 1 );


/**
 * @param $login
 */
function um_store_lastlogin_timestamp_( $login ) {
	$user = get_user_by( 'login', $login );
	um_store_lastlogin_timestamp( $user->ID );
}
add_action( 'wp_login', 'um_store_lastlogin_timestamp_' );


/**
 * Login user process
 *
 * @param array $args
 */
function um_user_login( $args ) {
	extract( $args );

	$rememberme = ( isset( $args['rememberme'] ) && 1 ==  $args['rememberme']  && isset( $_REQUEST['rememberme'] ) ) ? 1 : 0;

	if ( ( UM()->options()->get('deny_admin_frontend_login')   && ! isset( $_GET['provider'] ) ) && strrpos( um_user('wp_roles' ), 'administrator' ) !== false ) {
		wp_die( __('This action has been prevented for security measures.','ultimate-member') );
	}

	UM()->user()->auto_login( um_user( 'ID' ), $rememberme );

	/**
	 * UM hook
	 *
	 * @type action
	 * @title um_on_login_before_redirect
	 * @description Hook that runs after successful login and before user is redirected
	 * @input_vars
	 * [{"var":"$user_id","type":"int","desc":"User ID"}]
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage add_action( 'um_on_login_before_redirect', 'function_name', 10, 1 );
	 * @example
	 * <?php
	 * add_action( 'um_on_login_before_redirect', 'my_on_login_before_redirect', 10, 1 );
	 * function my_on_login_before_redirect( $user_id ) {
	 *     // your code here
	 * }
	 * ?>
	 */
	do_action( 'um_on_login_before_redirect', um_user( 'ID' ) );

	// Priority redirect
	if ( ! empty( $args['redirect_to']  ) ) {
		exit( wp_redirect( $args['redirect_to'] ) );
	}

	// Role redirect
	$after_login = um_user( 'after_login' );
	if ( empty( $after_login ) )
		exit( wp_redirect( um_user_profile_url() ) );

	switch( $after_login ) {

		case 'redirect_admin':
			exit( wp_redirect( admin_url() ) );
			break;

		case 'redirect_url':
			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_login_redirect_url
			 * @description Change redirect URL after successful login
			 * @input_vars
			 * [{"var":"$url","type":"string","desc":"Redirect URL"},
			 * {"var":"$id","type":"int","desc":"User ID"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_login_redirect_url', 'function_name', 10, 2 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_login_redirect_url', 'my_login_redirect_url', 10, 2 );
			 * function my_login_redirect_url( $url, $id ) {
			 *     // your code here
			 *     return $url;
			 * }
			 * ?>
			 */
			$redirect_url = apply_filters( 'um_login_redirect_url', um_user( 'login_redirect_url' ), um_user( 'ID' ) );
			exit( wp_redirect( $redirect_url ) );
			break;

		case 'refresh':
			exit( wp_redirect( UM()->permalinks()->get_current_url() ) );
			break;

		case 'redirect_profile':
		default:
		exit( wp_redirect( um_user_profile_url() ) );
		break;

	}
}
add_action( 'um_user_login', 'um_user_login', 10 );


/**
 * Form processing
 *
 * @param $args
 */
function um_submit_form_login( $args ) {

	if ( ! isset( UM()->form()->errors ) ) {
		/**
		 * UM hook
		 *
		 * @type action
		 * @title um_user_login
		 * @description Hook that runs after successful submit login form
		 * @input_vars
		 * [{"var":"$args","type":"array","desc":"Form data"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage add_action( 'um_user_login', 'function_name', 10, 1 );
		 * @example
		 * <?php
		 * add_action( 'um_user_login', 'my_user_login', 10, 1 );
		 * function my_user_login( $args ) {
		 *     // your code here
		 * }
		 * ?>
		 */
		do_action( 'um_user_login', $args );
	}

	/**
	 * UM hook
	 *
	 * @type action
	 * @title um_user_login_extra_hook
	 * @description Hook that runs after successful submit login form
	 * @input_vars
	 * [{"var":"$args","type":"array","desc":"Form data"}]
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage add_action( 'um_user_login_extra_hook', 'function_name', 10, 1 );
	 * @example
	 * <?php
	 * add_action( 'um_user_login_extra_hook', 'my_user_login_extra', 10, 1 );
	 * function my_user_login_extra( $args ) {
	 *     // your code here
	 * }
	 * ?>
	 */
	do_action( 'um_user_login_extra_hook', $args );
}
add_action( 'um_submit_form_login', 'um_submit_form_login', 10 );


/**
 * Show the submit button
 *
 * @param $args
 */
function um_add_submit_button_to_login( $args ) {
	// DO NOT add when reviewing user's details
	if ( UM()->user()->preview == true && is_admin() ) return;

	$primary_btn_word = $args['primary_btn_word'];
	/**
	 * UM hook
	 *
	 * @type filter
	 * @title um_login_form_button_one
	 * @description Change Login Form Primary button
	 * @input_vars
	 * [{"var":"$primary_btn_word","type":"string","desc":"Button text"},
	 * {"var":"$args","type":"array","desc":"Login Form arguments"}]
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage
	 * <?php add_filter( 'um_login_form_button_one', 'function_name', 10, 2 ); ?>
	 * @example
	 * <?php
	 * add_filter( 'um_login_form_button_one', 'my_login_form_button_one', 10, 2 );
	 * function my_login_form_button_one( $primary_btn_word, $args ) {
	 *     // your code here
	 *     return $primary_btn_word;
	 * }
	 * ?>
	 */
	$primary_btn_word = apply_filters('um_login_form_button_one', $primary_btn_word, $args );

	$secondary_btn_word = $args['secondary_btn_word'];
	/**
	 * UM hook
	 *
	 * @type filter
	 * @title um_login_form_button_two
	 * @description Change Login Form Secondary button
	 * @input_vars
	 * [{"var":"$secondary_btn_word","type":"string","desc":"Button text"},
	 * {"var":"$args","type":"array","desc":"Login Form arguments"}]
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage
	 * <?php add_filter( 'um_login_form_button_two', 'function_name', 10, 2 ); ?>
	 * @example
	 * <?php
	 * add_filter( 'um_login_form_button_two', 'my_login_form_button_two', 10, 2 );
	 * function my_login_form_button_two( $secondary_btn_word, $args ) {
	 *     // your code here
	 *     return $secondary_btn_word;
	 * }
	 * ?>
	 */
	$secondary_btn_word = apply_filters('um_login_form_button_two', $secondary_btn_word, $args );

	$secondary_btn_url = ( isset( $args['secondary_btn_url'] ) && $args['secondary_btn_url'] ) ? $args['secondary_btn_url'] : um_get_core_page('register');
	/**
	 * UM hook
	 *
	 * @type filter
	 * @title um_login_form_button_two_url
	 * @description Change Login Form Secondary button URL
	 * @input_vars
	 * [{"var":"$secondary_btn_url","type":"string","desc":"Button URL"},
	 * {"var":"$args","type":"array","desc":"Login Form arguments"}]
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage
	 * <?php add_filter( 'um_login_form_button_two_url', 'function_name', 10, 2 ); ?>
	 * @example
	 * <?php
	 * add_filter( 'um_login_form_button_two_url', 'my_login_form_button_two_url', 10, 2 );
	 * function my_login_form_button_two_url( $secondary_btn_url, $args ) {
	 *     // your code here
	 *     return $secondary_btn_url;
	 * }
	 * ?>
	 */
	$secondary_btn_url = apply_filters('um_login_form_button_two_url', $secondary_btn_url, $args ); ?>

	<div class="um-col-alt">

		<?php if ( isset( $args['show_rememberme'] ) && $args['show_rememberme'] ) {
			echo UM()->fields()->checkbox('rememberme', __('Keep me signed in','ultimate-member') );
			echo '<div class="um-clear"></div>';
		} ?>

		<?php if ( isset($args['secondary_btn']) && $args['secondary_btn'] != 0 ) { ?>

			<div class="um-left um-half"><input type="submit" value="<?php echo __( $primary_btn_word,'ultimate-member'); ?>" class="um-button" id="um-submit-btn" /></div>
			<div class="um-right um-half"><a href="<?php echo $secondary_btn_url; ?>" class="um-button um-alt"><?php echo __( $secondary_btn_word,'ultimate-member'); ?></a></div>

		<?php } else { ?>

			<div class="um-center"><input type="submit" value="<?php echo __( $args['primary_btn_word'],'ultimate-member'); ?>" class="um-button" id="um-submit-btn" /></div>

		<?php } ?>

		<div class="um-clear"></div>

	</div>

	<?php
}
add_action( 'um_after_login_fields', 'um_add_submit_button_to_login', 1000 );


/**
 * Display a forgot password link
 *
 * @param $args
 */
function um_after_login_submit( $args ) {
	if ( $args['forgot_pass_link'] == 0 ) return;

	?>

	<div class="um-col-alt-b">
		<a href="<?php echo um_get_core_page('password-reset'); ?>" class="um-link-alt"><?php _e('Forgot your password?','ultimate-member'); ?></a>
		</div>

		<?php
	}
add_action( 'um_after_login_fields', 'um_after_login_submit', 1001 );


/**
 * Show Fields
 *
 * @param $args
 */
function um_add_login_fields($args){
	echo UM()->fields()->display( 'login', $args );
}
add_action('um_main_login_fields', 'um_add_login_fields', 100);


/**
 * Remove authenticate filter
 * @uses 'wp_authenticate_username_password_before'
 *
 * @param $user
 * @param $username
 * @param $password
 */
function um_auth_username_password_before( $user, $username, $password ) {
	remove_filter( 'authenticate', 'wp_authenticate_username_password', 20 );
}
add_action( 'wp_authenticate_username_password_before', 'um_auth_username_password_before', 10, 3 );