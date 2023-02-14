<?php
/**
 * The main file for sync actions (not AJAX) handler on the frontend
 *
 * @package um\frontend
 */

namespace um\frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'um\frontend\Actions_Listener' ) ) {


	/**
	 * Class Actions_Listener
	 *
	 * @package um\frontend
	 */
	class Actions_Listener {

		/**
		 * Actions_Listener constructor.
		 */
		public function __construct() {
			add_action( 'wp_loaded', array( $this, 'actions_listener' ), 10 );

			add_action( 'template_redirect', array( &$this, 'handle_reset_password' ), 10001 );
		}

		/**
		 * Main frontend action listener
		 *
		 * @since 3.0
		 */
		public function actions_listener() {
			if ( ! empty( $_POST['um-action'] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification -- there is nonce verification, but for each case see below
				switch ( sanitize_key( $_POST['um-action'] ) ) {
					case 'password-reset-request':
						if ( '' !== $_POST[ UM()->honeypot ] ) {
							wp_die( esc_html__( 'Hello, spam bot!', 'ultimate-member' ) );
						}

						$lostpassword_form = UM()->frontend()->form(
							array(
								'id' => 'um-lostpassword',
							)
						);

						$lostpassword_form->flush_errors();

						if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'um-lostpassword' ) ) {
							$lostpassword_form->add_error( 'global', __( 'Security issue, Please try again', 'ultimate-member' ) );
						}

						if ( empty( $_POST['user_login'] ) ) {
							$lostpassword_form->add_error( 'user_login', __( 'Username or Email Address is required', 'ultimate-member' ) );
						}

						$user_login = sanitize_text_field( wp_unslash( $_POST['user_login'] ) );
						if ( empty( $user_login ) ) {
							$lostpassword_form->add_error( 'user_login', __( 'Username or Email Address is required', 'ultimate-member' ) );
						}

						if ( ( ! is_email( $user_login ) && username_exists( $user_login ) ) || ( is_email( $user_login ) && email_exists( $user_login ) ) ) {
							if ( is_email( $user_login ) ) {
								$user_id = email_exists( $user_login );
							} else {
								$user_id = username_exists( $user_login );
							}

							$is_admin = user_can( $user_id, 'manage_options' );

							// Fires when reset password limit is enabled and a user hasn't 'manage_options' capabilities
							if ( UM()->options()->get( 'enable_reset_password_limit' ) && ! $is_admin ) {
								$limit    = UM()->options()->get( 'reset_password_limit_number' );
								$attempts = get_user_meta( $user_id, 'password_rst_attempts', true );
								$attempts = ! empty( $attempts ) && is_numeric( $attempts ) ? (int) $attempts : 0;
								if ( $attempts >= (int) $limit ) {
									$lostpassword_form->add_error( 'user_login', __( 'You have reached the limit for requesting password change for this user already. Contact support if you cannot open the email.', 'ultimate-member' ) );
								} else {
									update_user_meta( $user_id, 'password_rst_attempts', $attempts + 1 );
									update_user_meta( $user_id, 'password_rst_attempts_timeout', time() + UM()->config()->get( 'password_reset_attempts_timeout' ) );
								}
							}
						}

						/**
						 * Fires after Ultimate Member native Lost Password form validations are completed.
						 *
						 * Note: Use this hook for adding custom validations to your Lost Password form.
						 *
						 * @since 3.0.0
						 * @hook um_lostpassword_errors_hook
						 *
						 * @param {object} $lostpassword_form Frontend form class (\um\frontend\Form) instance.
						 */
						do_action( 'um_lostpassword_errors_hook', $lostpassword_form );

						if ( ! $lostpassword_form->has_errors() ) {
							$hook_user_id = isset( $user_id ) ? $user_id : null;
							/**
							 * Fires just before the Lost Password process when Ultimate Member Lost Password form data is validated.
							 * Legacy v2.x hooks: 'um_reset_password_process_hook'
							 *
							 * Note: Use this hook for adding custom actions before lost user's password form. It's the first hook after the validating form data.
							 *
							 * @since 3.0.0
							 * @hook um_before_send_lostpassword_link
							 *
							 * @param {int|null} $user_id User ID whose password was lost or null if user doesn't exist.
							 */
							do_action( 'um_before_send_lostpassword_link', $hook_user_id );

							if ( isset( $user_id ) ) {
								$userdata = get_userdata( $user_id );

								if ( is_a( $userdata, '\WP_User' ) ) {
									UM()->common()->mail()->send(
										$userdata->user_email,
										'reset-password',
										array(
											'tags'         => array(
												'{password_reset_link}',
											),
											'tags_replace' => array(
												UM()->common()->user()->get_reset_password_url( $userdata ),
											),
										)
									);
								}
							}

							/**
							 * Fires just after the Lost Password process when probably lost user's password sending.
							 *
							 * Note: Use this hook for adding custom actions after probably lost user's password sending.
							 *
							 * @since 3.0.0
							 * @hook um_after_send_lostpassword_link
							 *
							 * @param {int|null} $user_id User ID whose password was lost or null if user doesn't exist.
							 */
							do_action( 'um_after_send_lostpassword_link', $hook_user_id );

							// redirect anyway even there isn't a user in `$userdata`. We don't need to show that user exists due to security
							$url = add_query_arg( array( 'checkemail' => 'confirm' ), um_get_predefined_page_url( 'password-reset' ) );
							wp_safe_redirect( $url );
							exit;
						}
						break;
					case 'password-reset':
						if ( '' !== $_POST[ UM()->honeypot ] ) {
							wp_die( esc_html__( 'Hello, spam bot!', 'ultimate-member' ) );
						}

						if ( empty( $_POST['rp_key'] ) || empty( $_POST['login'] ) ) {
							wp_redirect( add_query_arg( array( 'error' => 'invalidkey' ), um_get_predefined_page_url( 'password-reset' ) ) );
							exit;
						}

						$resetpass_form = UM()->frontend()->form(
							array(
								'id' => 'um-resetpass',
							)
						);

						$resetpass_form->flush_errors();

						if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'um-resetpass' ) ) {
							$resetpass_form->add_error( 'global', __( 'Security issue, Please try again', 'ultimate-member' ) );
						}

						if ( empty( $_POST['user_password'] ) ) {
							$resetpass_form->add_error( 'user_password', __( 'You must enter a new password', 'ultimate-member' ) );
						}

						$user_password = trim( wp_unslash( $_POST['user_password'] ) );
						if ( empty( $user_password ) ) {
							$resetpass_form->add_error( 'user_password', __( 'You must enter a new password', 'ultimate-member' ) );
						}

						// Check for "\" in password.
						if ( false !== strpos( $user_password, '\\' ) ) {
							$resetpass_form->add_error( 'user_password', __( 'Passwords may not contain the character "\\".', 'ultimate-member' ) );
						}

						$strongpass_required = UM()->options()->get( 'require_strongpass' );
						if ( ! empty( $strongpass_required ) ) {
							$min_length = UM()->options()->get( 'password_min_chars' );
							$min_length = ! empty( $min_length ) ? $min_length : 8;
							$max_length = UM()->options()->get( 'password_max_chars' );
							$max_length = ! empty( $max_length ) ? $max_length : 30;

							if ( mb_strlen( $user_password ) < $min_length ) {
								$resetpass_form->add_error( 'user_password', sprintf( __( 'Your password must contain at least %d characters', 'ultimate-member' ), $min_length ) );
							}

							if ( mb_strlen( $user_password ) > $max_length ) {
								$resetpass_form->add_error( 'user_password', sprintf( __( 'Your password must contain less than %d characters', 'ultimate-member' ), $max_length ) );
							}

							if ( ! UM()->validation()->strong_pass( $user_password ) ) {
								$resetpass_form->add_error( 'user_password', __( 'Your password must contain at least one lowercase letter, one capital letter and one number', 'ultimate-member' ) );
							}
						}

						if ( empty( $_POST['confirm_user_password'] ) ) {
							$resetpass_form->add_error( 'confirm_user_password', __( 'You must confirm your new password', 'ultimate-member' ) );
						}

						$confirm_user_password = trim( wp_unslash( $_POST['confirm_user_password'] ) );
						if ( empty( $confirm_user_password ) ) {
							$resetpass_form->add_error( 'confirm_user_password', __( 'You must confirm your new password', 'ultimate-member' ) );
						}

						if ( $user_password !== $confirm_user_password ) {
							$resetpass_form->add_error( 'confirm_user_password', __( 'Your passwords do not match', 'ultimate-member' ) );
						}

						// it only works on the Password Reset Shortcode form
						$rp_cookie = 'wp-resetpass-' . COOKIEHASH;

						if ( isset( $_COOKIE[ $rp_cookie ] ) && 0 < strpos( $_COOKIE[ $rp_cookie ], ':' ) ) {
							list( $rp_login, $rp_key ) = explode( ':', wp_unslash( $_COOKIE[ $rp_cookie ] ), 2 );

							$user = check_password_reset_key( $rp_key, $rp_login );

							if ( isset( $user_password ) && ! hash_equals( $rp_key, $_POST['rp_key'] ) ) {
								$user = false;
							}
						} else {
							$user = false;
						}

						if ( ! $user || is_wp_error( $user ) ) {
							UM()->setcookie( $rp_cookie, false );
							if ( $user && 'expired_key' === $user->get_error_code() ) {
								wp_redirect( add_query_arg( array( 'error' => 'expiredkey' ), um_get_predefined_page_url( 'password-reset' ) ) );
							} else {
								wp_redirect( add_query_arg( array( 'error' => 'invalidkey' ), um_get_predefined_page_url( 'password-reset' ) ) );
							}
							exit;
						}

						$errors = new \WP_Error();

						/** This action is documented in wp-login.php */
						do_action( 'validate_password_reset', $errors, $user );

						if ( $errors->get_error_code() ) {
							$resetpass_form->add_error( 'global', $errors->get_error_code() );
						}

						/**
						 * Fires after Ultimate Member native Reset Password form validations are completed.
						 *
						 * Note: Use this hook for adding custom validations to your Reset Password form. It's the latest hook before the validating form data.
						 *
						 * @since 3.0.0
						 * @hook um_resetpass_errors_hook
						 *
						 * @param {object} $resetpass_form Frontend form class (\um\frontend\Form) instance.
						 */
						do_action( 'um_resetpass_errors_hook', $resetpass_form );

						if ( ! $resetpass_form->has_errors() ) {

							/**
							 * Fires just before the Reset Password process when Ultimate Member Reset Password form data is validated.
							 * Legacy v2.x hooks: 'um_change_password_process_hook'
							 *
							 * Note: Use this hook for adding custom actions before reset user's password form. It's the first hook after the validating form data.
							 *
							 * @since 3.0.0
							 * @hook um_before_changing_user_password
							 *
							 * @param {int} $user_id User ID whose password was reset.
							 */
							do_action( 'um_before_changing_user_password', $user->ID );

							reset_password( $user, $user_password );

							// send the Password Changed Email
							UM()->common()->mail()->send( $user->user_email, 'password-changed' );

							// clear 'password_rst_attempts' meta data
							UM()->common()->user()->flush_reset_password_attempts( $user->ID );

							UM()->setcookie( $rp_cookie, false );

							/**
							 * Fires after an user reset their password via Password Reset Form.
							 *
							 * @since 2.0.0
							 * @hook um_after_changing_user_password
							 *
							 * @param {int} $user_id User ID whose password was reset.
							 */
							do_action( 'um_after_changing_user_password', $user->ID );

							if ( ! is_user_logged_in() ) {
								$url = add_query_arg( array( 'checklogin' => 'password_changed' ), um_get_predefined_page_url( 'login' ) );
							} else {
								$url = add_query_arg( array( 'checklogin' => 'password_changed' ), um_get_predefined_page_url( 'password-reset' ) );
							}
							wp_safe_redirect( $url );
							exit;
						}
						break;
				}
			}
		}

		/**
		 * Handle Password Reset Form before loading
		 *
		 * @since 3.0
		 */
		public function handle_reset_password() {
			if ( ! um_is_predefined_page( 'password-reset' ) ) {
				return;
			}

			if ( ! isset( $_GET['action'] ) || 'rp' !== $_GET['action'] ) {
				return;
			}

			wp_fix_server_vars();

			$rp_cookie = 'wp-resetpass-' . COOKIEHASH;

			if ( isset( $_GET['key'] ) && isset( $_GET['login'] ) ) {
				$value = sprintf( '%s:%s', wp_unslash( $_GET['login'] ), wp_unslash( $_GET['key'] ) );
				UM()->setcookie( $rp_cookie, $value );

				wp_safe_redirect( remove_query_arg( array( 'key', 'login' ) ) );
				exit;
			}

			if ( isset( $_COOKIE[ $rp_cookie ] ) && 0 < strpos( $_COOKIE[ $rp_cookie ], ':' ) ) {
				list( $rp_login, $rp_key ) = explode( ':', wp_unslash( $_COOKIE[ $rp_cookie ] ), 2 );

				$user = check_password_reset_key( $rp_key, $rp_login );

				if ( isset( $_POST['user_password'] ) && ! hash_equals( $rp_key, $_POST['rp_key'] ) ) {
					$user = false;
				}
			} else {
				$user = false;
			}

			if ( ! $user || is_wp_error( $user ) ) {
				UM()->setcookie( $rp_cookie, false );
				if ( $user && 'expired_key' === $user->get_error_code() ) {
					wp_redirect( add_query_arg( array( 'error' => 'expiredkey' ), um_get_predefined_page_url( 'password-reset' ) ) );
				} else {
					wp_redirect( add_query_arg( array( 'error' => 'invalidkey' ), um_get_predefined_page_url( 'password-reset' ) ) );
				}
				exit;
			}

			// this variable is used for populating the reset password form via the hash and login
			UM()->common()->shortcodes()->is_resetpass = true;
		}
	}
}
