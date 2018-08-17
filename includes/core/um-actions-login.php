<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Error procesing hook for login
 *
 * @param $args
 */
function um_submit_form_errors_hook_login( $args ) {
    if( $args['mode'] != 'login' ) return true;

	if ( ( UM()->options()->get('deny_admin_frontend_login')   && ! isset( $_GET['provider'] ) ) && strrpos( um_user('wp_roles' ), 'administrator' ) !== false ) {
		wp_die( __('This action has been prevented for security measures.','ultimate-member') );
	}

	if( isset( $args['username'] ) ) {
		$login_or_email = $args['username'];
		$field = 'username';
	}
	if( isset( $args['user_email'] ) ) {
		$login_or_email = $args['user_email'];
		$field = 'user_email';
	}
	if( isset( $args['user_login'] ) ) {
		$login_or_email = $args['user_login'];
		$field = 'user_login';
	}

	if ( empty( $login_or_email )  ) {
		if( isset( $field ) && $field == 'user_email' ) {
			$message = __('Please enter your email','ultimate-member');
		} else if( isset( $field ) && $field == 'user_login' ) {
			$message = __('Please enter your username','ultimate-member');
		} else {
			$message = __('Please enter your username or email','ultimate-member');
		}
		UM()->form()->add_error( $field,  $message );
		return false;
	}

	if ( empty( $args['user_password'] ) ) {
		UM()->form()->add_error( 'user_password',  __('Please enter your password','ultimate-member') );
		return false;
	}

	$user = wp_signon( array(
		'user_login'    => $login_or_email,
		'user_password' => $args['user_password'],
		'remember' => !empty( $args['rememberme'] )
	) );

	if ( is_wp_error( $user ) ) {
		UM()->form()->add_error( $user->get_error_code(),  __( $user->get_error_message() ,'ultimate-member') );
		return false;
	}
	return true;
}
add_action( 'um_submit_form_errors_hook', 'um_submit_form_errors_hook_login', 40 );


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
function um_user_after_login_redirect( $args ) {
    if ( UM()->form()->count_errors() ) return false;

	extract( $args );

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
add_action( 'um_submit_form_login', 'um_user_after_login_redirect', 9999 );


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
function um_after_login_submit_button( $args ) {
	if ( $args['forgot_pass_link'] == 0 ) return;

	?>

	<div class="um-col-alt-b">
		<a href="<?php echo um_get_core_page('password-reset'); ?>" class="um-link-alt"><?php _e('Forgot your password?','ultimate-member'); ?></a>
		</div>

		<?php
	}
add_action( 'um_after_login_fields', 'um_after_login_submit_button', 1001 );


/**
 * Show Fields
 *
 * @param $args
 */
function um_add_login_fields($args){
	echo UM()->fields()->display( 'login', $args );
}
add_action('um_main_login_fields', 'um_add_login_fields', 100);