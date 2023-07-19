<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Error processing hook for login.
 *
 * @param $submitted_data
 */
function um_submit_form_errors_hook_login( $submitted_data ) {
	$user_password = $submitted_data['user_password'];

	if ( isset( $submitted_data['username'] ) && $submitted_data['username'] == '' ) {
		UM()->form()->add_error( 'username', __( 'Please enter your username or email', 'ultimate-member' ) );
	}

	if ( isset( $submitted_data['user_login'] ) && $submitted_data['user_login'] == '' ) {
		UM()->form()->add_error( 'user_login', __( 'Please enter your username', 'ultimate-member' ) );
	}

	if ( isset( $submitted_data['user_email'] ) && $submitted_data['user_email'] == '' ) {
		UM()->form()->add_error( 'user_email', __( 'Please enter your email', 'ultimate-member' ) );
	}

	if ( isset( $submitted_data['username'] ) ) {
		$authenticate = $submitted_data['username'];
		$field = 'username';
		if ( is_email( $submitted_data['username'] ) ) {
			$data = get_user_by('email', $submitted_data['username'] );
			$user_name = isset( $data->user_login ) ? $data->user_login : null;
		} else {
			$user_name  = $submitted_data['username'];
		}
	} elseif ( isset( $submitted_data['user_email'] ) ) {
		$authenticate = $submitted_data['user_email'];
		$field = 'user_email';
		$data = get_user_by('email', $submitted_data['user_email'] );
		$user_name = isset( $data->user_login ) ? $data->user_login : null;
	} else {
		$field = 'user_login';
		$user_name = $submitted_data['user_login'];
		$authenticate = $submitted_data['user_login'];
	}

	if ( $submitted_data['user_password'] == '' ) {
		UM()->form()->add_error( 'user_password', __( 'Please enter your password', 'ultimate-member' ) );
	}

	$user = get_user_by( 'login', $user_name );
	if ( $user && wp_check_password( $submitted_data['user_password'], $user->data->user_pass, $user->ID ) ) {
		UM()->login()->auth_id = username_exists( $user_name );
	} else {
		UM()->form()->add_error( 'user_password', __( 'Password is incorrect. Please try again.', 'ultimate-member' ) );
	}

	// Integration with 3rd-party login handlers e.g. 3rd-party reCAPTCHA etc.
	$third_party_codes = apply_filters( 'um_custom_authenticate_error_codes', array() );

	// @since 4.18 replacement for 'wp_login_failed' action hook
	// see WP function wp_authenticate()
	$ignore_codes = array( 'empty_username', 'empty_password' );

	$user = apply_filters( 'authenticate', null, $authenticate, $submitted_data['user_password'] );
	if ( is_wp_error( $user ) && ! in_array( $user->get_error_code(), $ignore_codes ) ) {
		if ( ! empty( $third_party_codes ) && in_array( $user->get_error_code(), $third_party_codes ) ) {
			UM()->form()->add_error( $user->get_error_code(), $user->get_error_message() );
		} else {
			UM()->form()->add_error( 'user_password', __( 'Password is incorrect. Please try again.', 'ultimate-member' ) );
		}
	}

	$user = apply_filters( 'wp_authenticate_user', $user, $submitted_data['user_password'] );
	if ( is_wp_error( $user ) && ! in_array( $user->get_error_code(), $ignore_codes ) ) {
		if ( ! empty( $third_party_codes ) && in_array( $user->get_error_code(), $third_party_codes ) ) {
			UM()->form()->add_error( $user->get_error_code(), $user->get_error_message() );
		} else {
			UM()->form()->add_error( 'user_password', __( 'Password is incorrect. Please try again.', 'ultimate-member' ) );
		}
	}

	// if there is an error notify wp
	if ( UM()->form()->has_error( $field ) || UM()->form()->has_error( $user_password ) || UM()->form()->count_errors() > 0 ) {
		do_action( 'wp_login_failed', $user_name, UM()->form()->get_wp_error() );
	}
}
add_action( 'um_submit_form_errors_hook_login', 'um_submit_form_errors_hook_login' );


/**
 * Display the login errors from other plugins
 *
 * @param $args
 */
function um_display_login_errors( $args ) {
	if ( UM()->form()->count_errors() > 0 ) {
		$errors = UM()->form()->errors;
		// hook for other plugins to display error
		$error_keys = array_keys( $errors );
	}

	if ( isset( $args['custom_fields'] ) ) {
		$custom_fields = $args['custom_fields'];
	}

	if ( ! empty( $error_keys ) && ! empty( $custom_fields ) ) {
		foreach ( $error_keys as $error ) {
			if ( trim( $error ) && ! isset( $custom_fields[ $error ] ) && ! empty( $errors[ $error ] ) ) {
				$error_message = apply_filters( 'login_errors', $errors[ $error ], $error );
				if ( empty( $error_message ) ) {
					return;
				}
				echo '<p class="um-notice err um-error-code-' . esc_attr( $error ) . '"><i class="um-icon-ios-close-empty" onclick="jQuery(this).parent().fadeOut();"></i>' . $error_message  . '</p>';
			}
		}
	}
}
add_action( 'um_before_login_fields', 'um_display_login_errors' );

/**
 * Login checks through the frontend login
 *
 * @param array $submitted_data
 * @param array $form_data
 */
function um_submit_form_errors_hook_logincheck( $submitted_data, $form_data ) {
	// Logout if logged in
	if ( is_user_logged_in() ) {
		wp_logout();
	}

	$user_id = ( isset( UM()->login()->auth_id ) ) ? UM()->login()->auth_id : '';
	um_fetch_user( $user_id );

	$status = um_user( 'account_status' ); // account status
	switch ( $status ) {
		// If user can't log in to site...
		case 'inactive':
		case 'awaiting_admin_review':
		case 'awaiting_email_confirmation':
		case 'rejected':
			um_reset_user();
			// Not `um_safe_redirect()` because UM()->permalinks()->get_current_url() is situated on the same host.
			wp_safe_redirect( add_query_arg( 'err', esc_attr( $status ), UM()->permalinks()->get_current_url() ) );
			exit;
	}

	if ( isset( $form_data['form_id'] ) && absint( $form_data['form_id'] ) === absint( UM()->shortcodes()->core_login_form() ) && UM()->form()->errors && ! isset( $_POST[ UM()->honeypot ] ) ) {
		// Not `um_safe_redirect()` because predefined login page is situated on the same host.
		wp_safe_redirect( um_get_core_page( 'login' ) );
		exit;
	}

}
add_action( 'um_submit_form_errors_hook_logincheck', 'um_submit_form_errors_hook_logincheck', 9999, 2 );

/**
 * Store last login timestamp
 *
 * @param $user_id
 */
function um_store_lastlogin_timestamp( $user_id ) {
	update_user_meta( $user_id, '_um_last_login', current_time( 'timestamp' ) );
	// Flush user cache after updating last_login timestamp.
	UM()->user()->remove_cache( $user_id );
}
add_action( 'um_on_login_before_redirect', 'um_store_lastlogin_timestamp', 10, 1 );


/**
 * @param $login
 */
function um_store_lastlogin_timestamp_( $login ) {
	$user = get_user_by( 'login', $login );

	if ( false !== $user ) {
		um_store_lastlogin_timestamp( $user->ID );

		$attempts = (int) get_user_meta( $user->ID, 'password_rst_attempts', true );
		if ( $attempts ) {
			//don't create meta but update if it's exists only
			update_user_meta( $user->ID, 'password_rst_attempts', 0 );
		}
	}
}
add_action( 'wp_login', 'um_store_lastlogin_timestamp_' );

/**
 * Login user process.
 *
 * @param array $submitted_data
 */
function um_user_login( $submitted_data ) {
	// phpcs:disable WordPress.Security.NonceVerification -- already verified here
	$rememberme = ( isset( $_REQUEST['rememberme'], $submitted_data['rememberme'] ) && 1 === (int) $submitted_data['rememberme'] ) ? 1 : 0;

	// @todo check using the 'deny_admin_frontend_login' option
	if ( false !== strrpos( um_user( 'wp_roles' ), 'administrator' ) && ( ! isset( $_GET['provider'] ) && UM()->options()->get( 'deny_admin_frontend_login' ) ) ) {
		wp_die( esc_html__( 'This action has been prevented for security measures.', 'ultimate-member' ) );
	}

	UM()->user()->auto_login( um_user( 'ID' ), $rememberme );

	/**
	 * Fires after successful login and before user is redirected.
	 *
	 * @since 1.3.x
	 * @hook  um_on_login_before_redirect
	 *
	 * @param {int} $user_id User ID.
	 *
	 * @example <caption>Make any custom action after successful login and before user is redirected.</caption>
	 * function my_on_login_before_redirect( $user_id ) {
	 *     // your code here
	 * }
	 * add_action( 'um_on_login_before_redirect', 'my_on_login_before_redirect', 10, 1 );
	 */
	do_action( 'um_on_login_before_redirect', um_user( 'ID' ) );

	// Priority redirect from $_GET attribute.
	if ( ! empty( $submitted_data['redirect_to'] ) ) {
		um_safe_redirect( $submitted_data['redirect_to'] );
		exit;
	}

	// Role redirect
	$after_login = um_user( 'after_login' );
	if ( empty( $after_login ) ) {
		// Not `um_safe_redirect()` because predefined user profile page is situated on the same host.
		wp_safe_redirect( um_user_profile_url() );
		exit;
	}

	switch ( $after_login ) {
		case 'redirect_admin':
			// Not `um_safe_redirect()` because is redirected to wp-admin.
			wp_safe_redirect( admin_url() );
			exit;
		case 'redirect_url':
			/**
			 * Filters change redirect URL after successful login.
			 *
			 * @since 2.0
			 * @hook  um_login_redirect_url
			 *
			 * @param {string} $can_view Redirect URL.
			 * @param {int}    $user_id  User ID.
			 *
			 * @return {string} Redirect URL.
			 *
			 * @example <caption>Change redirect URL.</caption>
			 * function my_login_redirect_url( $url, $id ) {
			 *     // your code here
			 *     return $url;
			 * }
			 * add_filter( 'um_login_redirect_url', 'my_login_redirect_url', 10, 2 );
			 */
			$redirect_url = apply_filters( 'um_login_redirect_url', um_user( 'login_redirect_url' ), um_user( 'ID' ) );
			um_safe_redirect( $redirect_url );
			exit;
		case 'refresh':
			// Not `um_safe_redirect()` because UM()->permalinks()->get_current_url() is situated on the same host.
			wp_safe_redirect( UM()->permalinks()->get_current_url() );
			exit;
		case 'redirect_profile':
		default:
			// Not `um_safe_redirect()` because predefined user profile page is situated on the same host.
			wp_safe_redirect( um_user_profile_url() );
			exit;
	}
	// phpcs:enable WordPress.Security.NonceVerification -- already verified here
}
add_action( 'um_user_login', 'um_user_login' );

/**
 * Form processing
 *
 * @param array $submitted_data
 * @param array $form_data
 */
function um_submit_form_login( $submitted_data, $form_data ) {
	if ( ! isset( UM()->form()->errors ) ) {
		/**
		 * Fires after successful submit login form.
		 *
		 * Internal Ultimate Member callbacks (Priority -> Callback name -> Excerpt):
		 * * 10 - `um_user_login()` Login form main handler.
		 *
		 * @since 1.3.x
		 * @hook um_user_login
		 *
		 * @param {array} $submitted_data $_POST Submission array.
		 * @param {array} $form_data      UM form data. Since 2.6.7
		 *
		 * @example <caption>Make any custom login action if submission is valid.</caption>
		 * function my_user_login( $submitted_data, $form_data ) {
		 *     // your code here
		 * }
		 * add_action( 'um_user_login', 'my_user_login', 10, 2 );
		 */
		do_action( 'um_user_login', $submitted_data, $form_data );
	}
	/**
	 * Fires after submit login form.
	 *
	 * Internal Ultimate Member callbacks (Priority -> Callback name -> Excerpt):
	 * * 10 - um-messaging.
	 *
	 * @since 1.3.x
	 * @hook um_user_login_extra_hook
	 *
	 * @param {array} $submitted_data $_POST Submission array.
	 * @param {array} $form_data      UM form data. Since 2.6.7
	 *
	 * @example <caption>Make any custom login action.</caption>
	 * function my_user_login_extra( $submitted_data, $form_data ) {
	 *     // your code here
	 * }
	 * add_action( 'um_user_login_extra_hook', 'my_user_login_extra', 10, 2 );
	 */
	do_action( 'um_user_login_extra_hook', $submitted_data, $form_data );
}
add_action( 'um_submit_form_login', 'um_submit_form_login', 10, 2 );

/**
 * Show the submit button
 *
 * @param $args
 */
function um_add_submit_button_to_login( $args ) {
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
	$primary_btn_word = apply_filters('um_login_form_button_one', $args['primary_btn_word'], $args );

	if ( ! isset( $primary_btn_word ) || $primary_btn_word == '' ){
		$primary_btn_word = UM()->options()->get( 'login_primary_btn_word' );
	}

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
	$secondary_btn_word = apply_filters( 'um_login_form_button_two', $args['secondary_btn_word'], $args );

	if ( ! isset( $secondary_btn_word ) || $secondary_btn_word == '' ){
		$secondary_btn_word = UM()->options()->get( 'login_secondary_btn_word' );
	}

	$secondary_btn_url = ! empty( $args['secondary_btn_url'] ) ? $args['secondary_btn_url'] : um_get_core_page( 'register' );
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
	$secondary_btn_url = apply_filters( 'um_login_form_button_two_url', $secondary_btn_url, $args ); ?>

	<div class="um-col-alt">

		<?php if ( ! empty( $args['show_rememberme'] ) ) {
			UM()->fields()->checkbox( 'rememberme', __( 'Keep me signed in', 'ultimate-member' ), false ); ?>
			<div class="um-clear"></div>
		<?php }

		if ( ! empty( $args['secondary_btn'] ) ) { ?>

			<div class="um-left um-half">
				<input type="submit" value="<?php esc_attr_e( wp_unslash( $primary_btn_word ), 'ultimate-member' ); ?>" class="um-button" id="um-submit-btn" />
			</div>
			<div class="um-right um-half">
				<a href="<?php echo esc_url( $secondary_btn_url ); ?>" class="um-button um-alt">
					<?php _e( wp_unslash( $secondary_btn_word ), 'ultimate-member' ); ?>
				</a>
			</div>

		<?php } else { ?>

			<div class="um-center">
				<input type="submit" value="<?php esc_attr_e( wp_unslash( $primary_btn_word ), 'ultimate-member' ); ?>" class="um-button" id="um-submit-btn" />
			</div>

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
	if ( empty( $args['forgot_pass_link'] ) ) {
		return;
	} ?>

	<div class="um-col-alt-b">
		<a href="<?php echo esc_url( um_get_core_page( 'password-reset' ) ); ?>" class="um-link-alt">
			<?php _e( 'Forgot your password?', 'ultimate-member' ); ?>
		</a>
	</div>

	<?php
}
add_action( 'um_after_login_fields', 'um_after_login_submit', 1001 );


/**
 * Show Fields
 *
 * @param $args
 */
function um_add_login_fields( $args ) {
	echo UM()->fields()->display( 'login', $args );
}
add_action( 'um_main_login_fields', 'um_add_login_fields', 100 );
