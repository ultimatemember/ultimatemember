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
							// phpcs:ignore WordPress.Security.SafeRedirect
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
								// translators: %s is a max length.
								$resetpass_form->add_error( 'user_password', sprintf( __( 'Your password must contain at least %d characters', 'ultimate-member' ), $min_length ) );
							}

							if ( mb_strlen( $user_password ) > $max_length ) {
								// translators: %s is a max length.
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
								// phpcs:ignore WordPress.Security.SafeRedirect
								wp_redirect( add_query_arg( array( 'error' => 'expiredkey' ), um_get_predefined_page_url( 'password-reset' ) ) );
							} else {
								// phpcs:ignore WordPress.Security.SafeRedirect
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

			if ( ! empty( $_POST['um-action-general-tab'] ) && 'account-general-tab' === sanitize_key( $_POST['um-action-general-tab'] ) ) {
				$tab_form = UM()->frontend()->form(
					array(
						'id' => 'um-general-tab',
					)
				);
				$tab_form->flush_errors();

				if ( empty( $_POST['general-tab-nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['general-tab-nonce'] ), 'um-general-tab' ) ) {
					$tab_form->add_error( 'global', __( 'Security issue, Please try again', 'ultimate-member' ) );
				}

				$user_id      = get_current_user_id();
				$current_user = wp_get_current_user();

				if ( isset( $_POST['first_name'] ) ) {
					$first_name = sanitize_text_field( $_POST['first_name'] );
				}
				if ( isset( $_POST['last_name'] ) ) {
					$last_name = sanitize_text_field( $_POST['last_name'] );
				}
				if ( isset( $_POST['user_email'] ) ) {
					$user_email = sanitize_email( $_POST['user_email'] );
				}
				if ( isset( $_POST['single_user_password'] ) ) {
					$single_user_password = trim( $_POST['single_user_password'] );
				}

				if ( ! UM()->options()->get( 'account_name' ) && UM()->options()->get( 'account_name_require' ) ) {
					if ( isset( $first_name ) && strlen( trim( $first_name ) ) === 0 ) {
						$tab_form->add_error( 'first_name', __( 'You must provide your first name', 'ultimate-member' ) );
					}
					if ( isset( $last_name ) && strlen( trim( $last_name ) ) === 0 ) {
						$tab_form->add_error( 'last_name', __( 'You must provide your last name', 'ultimate-member' ) );
					}
				}
				if ( UM()->options()->get( 'account_email' ) || um_user( 'can_edit_everyone' ) ) {
					if ( strlen( trim( $user_email ) ) === 0 ) {
						$tab_form->add_error( 'user_email', __( 'You must provide your e-mail', 'ultimate-member' ) );
					}

					if ( ! is_email( $user_email ) ) {
						$tab_form->add_error( 'user_email', __( 'Please provide a valid e-mail', 'ultimate-member' ) );
					}

					if ( email_exists( $user_email ) && email_exists( $user_email ) !== get_current_user_id() ) {
						$tab_form->add_error( 'user_email', __( 'Please provide a valid e-mail', 'ultimate-member' ) );
					}
				}
				if ( UM()->account()->current_password_is_required( 'general' ) ) {
					if ( strlen( $single_user_password ) === 0 ) {
						$tab_form->add_error( 'single_user_password', __( 'You must enter your password', 'ultimate-member' ) );
					} else {
						if ( ! wp_check_password( $single_user_password, $current_user->user_pass, $current_user->ID ) ) {
							$tab_form->add_error( 'single_user_password', __( 'This is not your password', 'ultimate-member' ) );
						}
					}
				}

				if ( ! $tab_form->has_errors() ) {
					if ( UM()->options()->get( 'account_name' ) && ! UM()->options()->get( 'account_name_disable' ) ) {
						update_user_meta( $user_id, 'first_name', $first_name );
						update_user_meta( $user_id, 'last_name', $last_name );
						if ( isset( $first_name ) || isset( $last_name ) ) {
							$changes = array(
								'first_name' => $first_name,
								'last_name'  => $last_name,
							);
							do_action( 'um_update_profile_full_name', $user_id, $changes );
						}
					}
					if ( UM()->options()->get( 'account_email' ) || um_user( 'can_edit_everyone' ) ) {
						add_filter( 'send_email_change_email', '__return_false' );

						$args = array(
							'ID'         => $user_id,
							'user_email' => $user_email,
						);
						wp_update_user( $args );

						// @todo check this function
						UM()->mail()->send( $user_email, 'changedaccount_email' );
					}
				}
			}

			if ( ! empty( $_POST['um-action-delete-tab'] ) && 'account-delete-tab' === sanitize_key( $_POST['um-action-delete-tab'] ) ) {
				$tab_form = UM()->frontend()->form(
					array(
						'id' => 'um-delete-tab',
					)
				);
				$tab_form->flush_errors();

				if ( empty( $_POST['delete-tab-nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['delete-tab-nonce'] ), 'um-delete-tab' ) ) {
					$tab_form->add_error( 'global', __( 'Security issue, Please try again', 'ultimate-member' ) );
				}

				$user_id      = get_current_user_id();
				$current_user = wp_get_current_user();

				if ( UM()->account()->current_password_is_required( 'delete' ) ) {
					if ( strlen( trim( $_POST['single_user_password'] ) ) === 0 ) {
						$tab_form->add_error( 'single_user_password', __( 'You must enter your password', 'ultimate-member' ) );
					} else {
						if ( ! wp_check_password( trim( $_POST['single_user_password'] ), $current_user->user_pass, $user_id ) ) {
							$tab_form->add_error( 'single_user_password', __( 'This is not your password', 'ultimate-member' ) );
						}
					}
				}

				if ( ! $tab_form->has_errors() ) {
					UM()->user()->delete();
				}
			}

			if ( ! empty( $_POST['um-action-password-tab'] ) && 'account-password-tab' === sanitize_key( $_POST['um-action-password-tab'] ) ) {
				$tab_form = UM()->frontend()->form(
					array(
						'id' => 'um-password-tab',
					)
				);
				$tab_form->flush_errors();

				if ( empty( $_POST['password-tab-nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['password-tab-nonce'] ), 'um-password-tab' ) ) {
					$tab_form->add_error( 'global', __( 'Security issue, Please try again', 'ultimate-member' ) );
				}

				if ( ! empty( $_POST['user_password'] ) && UM()->options()->get( 'change_password_request_limit' ) && is_user_logged_in() ) {
					$transient_id       = '_um_change_password_rate_limit__' . um_user( 'ID' );
					$last_request       = get_transient( $transient_id );
					$request_limit_time = apply_filters( 'um_change_password_attempt_limit_interval', 30 * MINUTE_IN_SECONDS );
					if ( ! $last_request ) {
						set_transient( $transient_id, time(), $request_limit_time );
					} else {
						$tab_form->add_error( 'user_password', __( 'Unable to change password because of password change limit. Please try again later.', 'ultimate-member' ) );
					}
				}

				$user_id               = get_current_user_id();
				$current_user          = wp_get_current_user();
				$user_password         = '';
				$confirm_user_password = '';

				if ( isset( $_POST['current_user_password'] ) && empty( $_POST['current_user_password'] ) ) {
					$tab_form->add_error( 'user_password', __( 'You must enter your current password', 'ultimate-member' ) );
				}
				if ( ! wp_check_password( $_POST['current_user_password'], $current_user->user_pass ) ) {
					$tab_form->add_error( 'current_user_password', __( 'This is not your password', 'ultimate-member' ) );
				}
				if ( isset( $_POST['user_password'] ) && empty( $_POST['user_password'] ) ) {
					$tab_form->add_error( 'user_password', __( 'You must enter a new password', 'ultimate-member' ) );
				}
				if ( isset( $_POST['confirm_user_password'] ) && empty( $_POST['confirm_user_password'] ) ) {
					$tab_form->add_error( 'confirm_user_password', __( 'You must confirm your new password', 'ultimate-member' ) );
				}

				if ( isset( $_POST['user_password'] ) ) {
					$user_password = trim( $_POST['user_password'] );
				}

				if ( isset( $_POST['confirm_user_password'] ) ) {
					$confirm_user_password = trim( $_POST['confirm_user_password'] );
				}

				// Check for "\" in password.
				if ( false !== strpos( wp_unslash( $user_password ), '\\' ) ) {
					$tab_form->add_error( 'user_password', __( 'Passwords may not contain the character "\\".', 'ultimate-member' ) );
				}

				$strongpass_required = UM()->options()->get( 'require_strongpass' );
				if ( ! empty( $strongpass_required ) ) {
					$min_length = UM()->options()->get( 'password_min_chars' );
					$min_length = ! empty( $min_length ) ? $min_length : 8;
					$max_length = UM()->options()->get( 'password_max_chars' );
					$max_length = ! empty( $max_length ) ? $max_length : 30;
					$user_login = um_user( 'user_login' );
					$user_email = um_user( 'user_email' );

					if ( mb_strlen( wp_unslash( $user_password ) ) < $min_length ) {
						// translators: %s is a min length.
						$tab_form->add_error( 'user_password', sprintf( __( 'Your password must contain at least %d characters', 'ultimate-member' ), $min_length ) );
					}

					if ( mb_strlen( wp_unslash( $user_password ) ) > $max_length ) {
						// translators: %s is a max length.
						$tab_form->add_error( 'user_password', sprintf( __( 'Your password must contain less than %d characters', 'ultimate-member' ), $max_length ) );
					}

					if ( strpos( strtolower( $user_login ), strtolower( $user_password ) ) > -1 ) {
						$tab_form->add_error( 'user_password', __( 'Your password cannot contain the part of your username', 'ultimate-member' ) );
					}

					if ( strpos( strtolower( $user_email ), strtolower( $user_password ) ) > -1 ) {
						$tab_form->add_error( 'user_password', __( 'Your password cannot contain the part of your email address', 'ultimate-member' ) );
					}

					if ( ! UM()->validation()->strong_pass( $user_password ) ) {
						$tab_form->add_error( 'user_password', __( 'Your password must contain at least one lowercase letter, one capital letter and one number', 'ultimate-member' ) );
					}
				}

				if ( $user_password !== $confirm_user_password ) {
					$tab_form->add_error( 'confirm_user_password', __( 'Your passwords do not match', 'ultimate-member' ) );
				}

				if ( ! $tab_form->has_errors() ) {

					// @todo check this function
					// UM()->user()->password_changed();

					add_filter( 'send_password_change_email', '__return_false' );

					//clear all sessions with old passwords
					wp_destroy_current_session();

					wp_set_password( $user_password, $user_id );

					do_action( 'um_before_signon_after_account_changes' );

					wp_signon(
						array(
							'user_login'    => um_user( 'user_login' ),
							'user_password' => $user_password,
						)
					);
				}
			}

			if ( ! empty( $_POST['um-action-privacy-tab'] ) && 'account-privacy-tab' === sanitize_key( $_POST['um-action-privacy-tab'] ) ) {
				$tab_form = UM()->frontend()->form(
					array(
						'id' => 'um-privacy-tab',
					)
				);
				$tab_form->flush_errors();

				if ( empty( $_POST['privacy-tab-nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['privacy-tab-nonce'] ), 'um-privacy-tab' ) ) {
					$tab_form->add_error( 'global', __( 'Security issue, Please try again', 'ultimate-member' ) );
				}

				if ( ! $tab_form->has_errors() ) {
					$changes = array(
						'profile_privacy' => sanitize_text_field( $_POST['profile_privacy'] ),
						'profile_noindex' => sanitize_key( $_POST['profile_noindex'] ),
						'hide_in_members' => array(
							sanitize_text_field( $_POST['hide_in_members'] ),
						),
					);

					/**
					 * Filters extend privacy data.
					 *
					 * @since 1.0
					 * @hook um_account_privacy_fields_update
					 *
					 * @param {string} $changes account privacy fields
					 *
					 * @return {array} account privacy fields.
					 */
					$changes = apply_filters( 'um_account_privacy_fields_update', $changes );

					UM()->user()->update_profile( $changes );
				}
			}

			if ( ! empty( $_POST['um-action-export-tab'] ) && 'account-privacy-export-tab' === sanitize_key( $_POST['um-action-export-tab'] ) ) {
				$tab_form = UM()->frontend()->form(
					array(
						'id' => 'um-privacy-export-tab',
					)
				);
				$tab_form->flush_errors();

				if ( empty( $_POST['export-tab-nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['export-tab-nonce'] ), 'um-privacy-export-tab' ) ) {
					$tab_form->add_error( 'global', __( 'Security issue, Please try again', 'ultimate-member' ) );
				}

				$user_id      = get_current_user_id();
				$current_user = wp_get_current_user();

				if ( UM()->account()->current_password_is_required( 'privacy_download_data' ) ) {
					if ( strlen( trim( $_POST['um-export-data'] ) ) === 0 ) {
						$tab_form->add_error( 'um-export-data', __( 'You must enter your password', 'ultimate-member' ) );
					} else {
						if ( ! wp_check_password( trim( $_POST['um-export-data'] ), $current_user->user_pass, $user_id ) ) {
							$tab_form->add_error( 'um-export-data', __( 'This is not your password', 'ultimate-member' ) );
						}
					}
				}

				if ( ! $tab_form->has_errors() ) {
					$request_id = wp_create_user_request( $current_user->user_email, 'export_personal_data' );
					if ( empty( $request_id ) ) {
						$tab_form->add_error( 'um-export-data', __( 'Wrong request', 'ultimate-member' ) );
					}
					if ( is_wp_error( $request_id ) ) {
						$tab_form->add_error( 'um-export-data', esc_html( $request_id->get_error_message() ) );
					}
					wp_send_user_request( $request_id );
				}
			}

			if ( ! empty( $_POST['um-action-erase-tab'] ) && 'account-privacy-erase-tab' === sanitize_key( $_POST['um-action-erase-tab'] ) ) {
				$tab_form = UM()->frontend()->form(
					array(
						'id' => 'um-privacy-erase-tab',
					)
				);
				$tab_form->flush_errors();

				if ( empty( $_POST['erase-tab-nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['erase-tab-nonce'] ), 'um-privacy-erase-tab' ) ) {
					$tab_form->add_error( 'global', __( 'Security issue, Please try again', 'ultimate-member' ) );
				}

				$user_id      = get_current_user_id();
				$current_user = wp_get_current_user();

				if ( UM()->account()->current_password_is_required( 'privacy_erase_data' ) ) {
					if ( strlen( trim( $_POST['um-erase-data'] ) ) === 0 ) {
						$tab_form->add_error( 'um-erase-data', __( 'You must enter your password', 'ultimate-member' ) );
					} else {
						if ( ! wp_check_password( trim( $_POST['um-erase-data'] ), $current_user->user_pass, $user_id ) ) {
							$tab_form->add_error( 'um-erase-data', __( 'This is not your password', 'ultimate-member' ) );
						}
					}
				}

				if ( ! $tab_form->has_errors() ) {
					$request_id = wp_create_user_request( $current_user->user_email, 'remove_personal_data' );
					if ( empty( $request_id ) ) {
						$tab_form->add_error( 'um-erase-data', __( 'Wrong request', 'ultimate-member' ) );
					}
					if ( is_wp_error( $request_id ) ) {
						$tab_form->add_error( 'um-erase-data', esc_html( $request_id->get_error_message() ) );
					}
					wp_send_user_request( $request_id );
				}
			}

			if ( ! empty( $_POST['um-action-notifications-tab'] ) && 'account-notifications-tab' === sanitize_key( $_POST['um-action-notifications-tab'] ) ) {
				$tab_form = UM()->frontend()->form(
					array(
						'id' => 'um-notifications-tab',
					)
				);
				$tab_form->flush_errors();

				if ( empty( $_POST['notifications-tab-nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['notifications-tab-nonce'] ), 'um-notifications-tab' ) ) {
					$tab_form->add_error( 'global', __( 'Security issue, Please try again', 'ultimate-member' ) );
				}

				$user_id = get_current_user_id();

				if ( ! $tab_form->has_errors() ) {
					/**
					 * Filters extend notifications data.
					 *
					 * @since 1.0
					 * @hook um_account_notifications_fields_update
					 *
					 * @param {string} $changes account notifications fields
					 *
					 * @return {array} account notifications fields.
					 */
					$changes = apply_filters( 'um_account_notifications_fields_update', array() );

					UM()->user()->update_profile( $changes );
				}
			}

			do_action( 'um_update_account_tab' );
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

			// phpcs:disable WordPress.Security.NonceVerification -- is verified above
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
					// phpcs:ignore WordPress.Security.SafeRedirect
					wp_redirect( add_query_arg( array( 'error' => 'expiredkey' ), um_get_predefined_page_url( 'password-reset' ) ) );
				} else {
					// phpcs:ignore WordPress.Security.SafeRedirect
					wp_redirect( add_query_arg( array( 'error' => 'invalidkey' ), um_get_predefined_page_url( 'password-reset' ) ) );
				}
				exit;
			}

			// this variable is used for populating the reset password form via the hash and login
			UM()->common()->shortcodes()->is_resetpass = true;
			// phpcs:enable WordPress.Security.NonceVerification -- is verified above
		}
	}
}
