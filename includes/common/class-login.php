<?php
namespace um\common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\common\Login' ) ) {

	/**
	 * Class Login
	 *
	 * @package um\common
	 */
	class Login {

		/**
		 * Logged-in user ID
		 */
		var $auth_id = '';

		/**
		 * Login constructor.
		 */
		function __construct() {
			add_filter( 'login_form_defaults', array( &$this, 'add_defaults' ), 10, 1 );
			add_filter( 'login_form_middle', array( &$this, 'add_forgot_password' ), 999, 2 );
			add_filter( 'login_form_bottom', array( &$this, 'add_hidden_field' ), 10, 2 );

			add_action( 'wp_authenticate', array( &$this, 'validate_before_login' ), 2, 2 );

			add_filter( 'authenticate', array( &$this, 'validate_login' ), 100, 3 );

			add_action( 'wp_login_failed', array( &$this, 'login_failed' ), 10, 2 );
			add_action( 'wp_login', array( &$this, 'login_successfully' ), 10, 2 );

			add_filter( 'login_redirect', array( &$this, 'change_login_redirect' ), 10, 3 );
		}

		/**
		 * Extends defaults related to the UM Login form.
		 *
		 * @param array $defaults `wp_login_form()` function's defaults
		 *
		 * @return array
		 */
		public function add_defaults( $defaults ) {
			$defaults['um_login_form']     = false;
			$defaults['um_login_form_id']  = '';
			$defaults['um_login_redirect'] = '';

			return $defaults;
		}

		/**
		 * Add forgot password link.
		 *
		 * @param string $content
		 * @param array $args
		 *
		 * @return string
		 */
		public function add_forgot_password( $content, $args ) {
			if ( array_key_exists( 'um_login_form', $args ) && true === $args['um_login_form'] ) {
				if ( ! empty( $args['um_show_forgot'] ) ) {
					$content .= '<p class="login-forgot"><a class="um-link" href="' . esc_url( um_get_predefined_page_url( 'password-reset' ) ) . '">' . esc_html__( 'Forgot password', 'ultimate-member' ) . '</a></p>';
				}
			}

			return $content;
		}

		/**
		 * Add necessary hidden fields to the UM Login form.
		 *
		 * @param string $content
		 * @param array $args
		 *
		 * @return string
		 */
		public function add_hidden_field( $content, $args ) {
			if ( array_key_exists( 'um_login_form', $args ) && true === $args['um_login_form'] ) {
				$url = UM()->permalinks()->get_current_url();

				$content .= '<input type="hidden" name="um_login_form" value="1" />';
				$content .= '<input type="hidden" name="um_login_redirect" value="' . esc_attr( $args['um_login_redirect'] ) . '" />';
				$content .= '<input type="hidden" name="um_login_form_id" value="' . esc_attr( $args['um_login_form_id'] ) . '" />';
				$content .= '<input type="hidden" name="um_login_nonce" value="' . esc_attr( wp_create_nonce( 'um-login-form' . $args['um_login_form_id'] ) ) . '" />';
				$content .= '<input type="hidden" name="um_current_login_url" value="' . esc_url( $url ) . '" />';
			}

			return $content;
		}

		/**
		 * Run before the authenticate process of the user via Ultimate Member - Login form
		 *
		 * @param string $username
		 * @param string $password
		 */
		public function validate_before_login( $username, $password ) {
			// phpcs:disable WordPress.Security.NonceVerification -- already verified here via wp-login.php or wp_login_form()
			if ( empty( $username ) || empty( $password ) ) {
				return;
			}

			if ( um_is_api_request() ) {
				return;
			}

			if ( empty( $_REQUEST['um_login_form'] ) ) {
				return;
			}

			$current_url = esc_url_raw( $_REQUEST['um_current_login_url'] );

			if ( empty( $_REQUEST['um_login_nonce'] ) ) {
				wp_safe_redirect( add_query_arg( array( 'login' => 'failed' ), $current_url ) );
				exit;
			}

			$form_id = null;
			if ( ! empty( $_REQUEST['um_login_form_id'] ) ) {
				$form_id = absint( $_REQUEST['um_login_form_id'] );
			}

			if ( ! wp_verify_nonce( sanitize_key( $_REQUEST['um_login_nonce'] ), 'um-login-form' . $form_id ) ) {
				wp_safe_redirect( add_query_arg( array( 'login' => 'failed' ), $current_url ) );
				exit;
			}

			do_action( 'um_login_form_wp_authenticate', $form_id, $username, $password );
		}

		/**
		 * Checks the login credentials or user for login on authenticate
		 *
		 * @since 3.0.0
		 *
		 * @param null|\WP_User|\WP_Error $user     WP_User if the user is authenticated.
		 *                                          WP_Error or null otherwise.
		 * @param string                  $username Username or email address.
		 * @param string                  $password User password
		 *
		 * @return \WP_User|\WP_Error A WP_User object is returned if the credentials authenticate a user. WP_Error or null otherwise.
		 */
		public function validate_login( $user, $username, $password ) {
			if ( ! empty( $_REQUEST['um_login_form'] ) ) {
				// Workaround the 'wp_login_failed' hook for the UM Login form and redirect with empty error message.
				$ignore_codes = array( 'empty_username', 'empty_password' );
				if ( is_wp_error( $user ) && in_array( $user->get_error_code(), $ignore_codes, true ) ) {
					$logout_link = add_query_arg( array( 'login' => 'empty' ), esc_url_raw( $_REQUEST['um_current_login_url'] ) );
					wp_safe_redirect( $logout_link );
					exit;
				}
			}

			if ( isset( $user->ID ) ) {
				// Checks for blocked Email on authenticate process.
				list( $is_blocked, $reason ) = UM()->common()->validation()->email_is_blocked( $user->user_email );
				if ( $is_blocked ) {
					if ( 'email' === $reason ) {
						return new \WP_Error( 'um_blocked_email', __( 'This email address has been blocked.', 'ultimate-member' ) );
					} elseif ( 'domain' === $reason ) {
						return new \WP_Error( 'um_blocked_domain', __( 'This email address domain has been blocked.', 'ultimate-member' ) );
					}
				}

				um_fetch_user( $user->ID );
				$status = um_user( 'account_status' );

				// Checks for not active user account status.
				switch( $status ) {
					case 'inactive':
						return new \WP_Error( 'um_account_' . $status, __( 'Your account has been disabled.', 'ultimate-member' ) );
						break;
					case 'awaiting_admin_review':
						return new \WP_Error( 'um_account_' . $status, __( 'Your account has not been approved yet.', 'ultimate-member' ) );
						break;
					case 'awaiting_email_confirmation':
						return new \WP_Error( 'um_account_' . $status, __( 'Your account is awaiting e-mail verification.', 'ultimate-member' ) );
						break;
					case 'rejected':
						return new \WP_Error( 'um_account_' . $status, __( 'Your membership request has been rejected.', 'ultimate-member' ) );
						break;
				}
			}
			return $user;
		}

		/**
		 * Redirects visitor to the UM Login form with login failed status.
		 * Only in the case login is failed from UM Login form.
		 *
		 * @param string    $username Username or email address.
		 * @param \WP_Error $error    A WP_Error object with the authentication failure details.
		 */
		public function login_failed( $username, $error ) {
			if ( ! empty( $_REQUEST['um_login_form'] ) ) {
				if ( in_array( $error->get_error_code(), array( 'um_blocked_email', 'um_blocked_domain', 'um_account_inactive', 'um_account_awaiting_admin_review', 'um_account_awaiting_email_confirmation', 'um_account_rejected' ), true ) ) {
					$logout_link = add_query_arg( array( 'login' => $error->get_error_code() ), esc_url_raw( $_REQUEST['um_current_login_url'] ) );
				} else {
					$logout_link = add_query_arg( array( 'login' => 'failed' ), esc_url_raw( $_REQUEST['um_current_login_url'] ) );
				}

				wp_safe_redirect( $logout_link );
				exit;
			}
		}

		/**
		 * Fires after the user has successfully logged in through `wp_signon()` function.
		 * This method uses by WordPress wp-login.php and wp_login_form() forms.
		 *
		 * @param string   $user_login Username.
		 * @param \WP_User $user       WP_User object of the logged-in user.
		 */
		public function login_successfully( $user_login, $user ) {
			// Set the Last Login time.
			update_user_meta( $user->ID, '_um_last_login', time() );
			// Flush reset password attempts.
			UM()->common()->user()->flush_reset_password_attempts( $user->ID );
		}

		/**
		 * @param string             $redirect_to           The redirect destination URL.
		 * @param string             $requested_redirect_to The requested redirect destination URL passed as a parameter.
		 * @param \WP_User|\WP_Error $user                  WP_User object if login was successful, WP_Error object otherwise.
		 *
		 * @return string
		 */
		public function change_login_redirect( $redirect_to, $requested_redirect_to, $user ) {
			// If $_REQUEST['redirect_to'] isn't empty then use default redirect.
			if ( ! empty( $requested_redirect_to ) ) {
				if ( ! ( 'wp-admin/' === $requested_redirect_to || admin_url() === $requested_redirect_to ) ) {
					// There is a workaround for wp-login.php with default wp-admin redirect firstly check the role options. But return here for other cases with UM Login Form
					return $redirect_to;
				}
			}

			if ( is_wp_error( $user ) || ! isset( $user->ID ) ) {
				return $redirect_to;
			}

			// maybe unnecessary because $requested_redirect_to can be filled via the form settings
			$form_setting = false;
			$redirect     = $redirect_to;
			// If $user isn't WP_Error.
			if ( ! empty( $_REQUEST['um_login_form'] ) && ! empty( $_REQUEST['um_login_redirect'] ) ) {
				// UM Login for is detected. Check if redirect isn't Default.
				$login_redirect_type = sanitize_key( $_REQUEST['um_login_redirect'] );
				if ( in_array( $login_redirect_type, array_keys( UM()->config()->get( 'login_redirect_options' ) ), true ) ) {
					switch ( $login_redirect_type ) {
						case 'redirect_profile':
							$form_setting = true;
							$redirect     = um_get_predefined_page_url( 'user' );
							break;
						case 'redirect_url':
							$form_setting = true;
							$redirect     = esc_url_raw( $_REQUEST['redirect_to'] );
							break;
						case 'redirect_admin':
							$form_setting = true;
							$redirect     = get_admin_url();
							break;
						case 'refresh':
							$form_setting = true;
							$redirect     = '';
							break;
						default:
							do_action_ref_array( "um_login_form_change_login_redirect_{$login_redirect_type}", array( &$redirect, &$form_setting, $user ) );
							break;
					}
				}
			}

			// Ignore login redirect base on the user settings if UM Login form setting isn't "Default"
			if ( true === $form_setting ) {
				return $redirect;
			}

			// Redirect user base on its UM priority role. It works for UM Login form with "Default" setting or other login process through not UM login forms
			$priority_user_role = UM()->common()->user()->get_priority_user_role( $user->ID );
			if ( ! empty( $priority_user_role ) ) {
				$role_meta = UM()->roles()->role_data( $priority_user_role );
				if ( array_key_exists( 'after_login', $role_meta ) && in_array( $role_meta['after_login'], array_keys( UM()->config()->get( 'login_redirect_options' ) ), true ) ) {
					switch ( $role_meta['after_login'] ) {
						case 'redirect_profile':
							$redirect_to = um_get_predefined_page_url( 'user' );
							break;
						case 'redirect_url':
							$redirect_to = esc_url_raw( $role_meta['login_redirect_url'] );
							break;
						case 'redirect_admin':
							$redirect_to = get_admin_url();
							break;
						case 'refresh':
							$redirect_to = '';
							break;
						default:
							$redirect_to = apply_filters( "um_login_form_change_role_login_redirect_{$role_meta['login_redirect']}", $redirect, $role_meta );
							break;
					}
				}
			}

			return $redirect_to;
		}
	}
}
