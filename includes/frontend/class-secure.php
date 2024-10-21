<?php
namespace um\frontend;

use WP_Error;
use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\frontend\Secure' ) ) {

	/**
	 * Class Secure
	 *
	 * @package um\frontend
	 *
	 * @since 2.6.8
	 */
	class Secure {

		/**
		 * Secure constructor.
		 * @since 2.6.8
		 */
		public function __construct() {
			add_action( 'init', array( $this, 'init' ) );

			add_action( 'um_before_login_fields', array( $this, 'reset_password_notice' ), 1 );

			add_action( 'um_before_login_fields', array( $this, 'under_maintenance_notice' ), 1 );

			add_action( 'um_submit_form_register', array( $this, 'block_register_forms' ) );

			add_action( 'um_user_login', array( $this, 'login_validate_expired_pass' ), 1 );

			add_action( 'validate_password_reset', array( $this, 'avoid_old_password' ), 1, 2 );
		}

		/**
		 * Adds handlers on form submissions.
		 *
		 * @since 2.6.8
		 */
		public function init() {
			if ( ! UM()->options()->get( 'secure_ban_admins_accounts' ) ) {
				return;
			}

			/**
			 * Checks the integrity of Current User's Capabilities
			 */
			add_action( 'um_after_save_registration_details', array( $this, 'secure_user_capabilities' ), 1 );
			add_action( 'um_after_save_registration_details', array( $this, 'maybe_set_whitelisted_password' ), 2 );
			if ( is_user_logged_in() && ! current_user_can( 'manage_options' ) ) { // Exclude current Logged-in Administrator from validation checks.
				add_action( 'um_after_user_updated', array( $this, 'secure_user_capabilities' ), 1 );
				add_action( 'um_after_user_account_updated', array( $this, 'secure_user_capabilities' ), 1 );
			}
		}

		/**
		 * Add Login notice for Reset Password
		 *
		 * @since 2.6.8
		 */
		public function reset_password_notice() {
			if ( ! UM()->options()->get( 'display_login_form_notice' ) ) {
				return;
			}

			// phpcs:disable WordPress.Security.NonceVerification
			if ( ! isset( $_REQUEST['notice'] ) || 'expired_password' !== $_REQUEST['notice'] ) {
				return;
			}
			// phpcs:enable WordPress.Security.NonceVerification

			echo "<p class='um-notice warning'>";
			echo wp_kses(
				sprintf(
				// translators: One-time change requires you to reset your password
					__( '<strong>Important:</strong> Your password has expired. This (one-time) change requires you to reset your password. Please <a href="%s">click here</a> to reset your password via Email.', 'ultimate-member' ),
					um_get_core_page( 'password-reset' )
				),
				array(
					'strong' => array(),
					'a'      => array(
						'href' => array(),
					),
				)
			);
			echo '</p>';
		}

		/**
		 * Add Login notice for Under Maintance
		 *
		 * @since 2.6.8
		 */
		public function under_maintenance_notice() {
			if ( ! UM()->options()->get( 'lock_register_forms' ) ) {
				return;
			}

			// phpcs:disable WordPress.Security.NonceVerification
			if ( ! isset( $_GET['notice'] ) || 'maintenance' !== $_GET['notice'] ) {
				return;
			}
			// phpcs:enable WordPress.Security.NonceVerification

			echo "<p class='um-notice warning'>";
			echo wp_kses(
				__( '<strong>Important:</strong> Our website is currently under maintenance. Please check back soon.', 'ultimate-member' ),
				array(
					'strong' => array(),
					'a'      => array(
						'href' => array(),
					),
				)
			);
			echo '</p>';
		}

		/**
		 * Block all UM Register form submissions.
		 *
		 * @since 2.6.8
		 */
		public function block_register_forms() {
			if ( UM()->options()->get( 'lock_register_forms' ) ) {
				$login_url = add_query_arg( 'notice', 'maintenance', um_get_core_page( 'login' ) );
				nocache_headers();
				// Not `um_safe_redirect()` because predefined login page is situated on the same host.
				wp_safe_redirect( $login_url );
				exit;
			}
		}

		/**
		 * Validate when user has expired password
		 *
		 * @since 2.6.8
		 */
		public function login_validate_expired_pass() {
			if ( UM()->options()->get( 'display_login_form_notice' ) ) {
				$user_id = isset( UM()->login()->auth_id ) ? UM()->login()->auth_id : '';
				if ( empty( $user_id ) ) {
					return;
				}

				$expired_password_reset = get_user_meta( $user_id, 'um_secure_has_reset_password', true );
				if ( ! $expired_password_reset ) {
					$login_url = add_query_arg( 'notice', 'expired_password', um_get_core_page( 'login' ) );
					// Not `um_safe_redirect()` because predefined login page is situated on the same host.
					wp_safe_redirect( $login_url );
					exit;
				}
			}
		}

		/**
		 * Prevent users from using Old Passwords on UM Password Reset form.
		 *
		 * @param WP_Error         $errors
		 * @param WP_User|WP_Error $user
		 *
		 * @since 2.6.8
		 */
		public function avoid_old_password( $errors, $user ) {
			if ( empty( $_POST['_um_password_change'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				return;
			}

			if ( ! UM()->options()->get( 'display_login_form_notice' ) ) {
				return;
			}

			if ( isset( $_REQUEST['user_password'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$new_user_pass = wp_unslash( $_REQUEST['user_password'] ); // phpcs:ignore WordPress.Security.NonceVerification
				if ( wp_check_password( $new_user_pass, $user->data->user_pass, $user->ID ) ) {
					UM()->form()->add_error( 'user_password', __( 'Your new password cannot be same as old password.', 'ultimate-member' ) );
					$errors->add( 'um_block_old_password', __( 'Your new password cannot be same as old password.', 'ultimate-member' ) );
				} else {
					update_user_meta( $user->ID, 'um_secure_has_reset_password', true );
					update_user_meta( $user->ID, 'um_secure_has_reset_password__timestamp', current_time( 'mysql', true ) );
				}
			}
		}

		/**
		 * Secure user capabilities and revoke administrative ones.
		 *
		 * @since 2.6.8
		 *
		 * @param int $user_id
		 */
		public function secure_user_capabilities( $user_id ) {
			global $wpdb;
			$user = get_userdata( $user_id );
			if ( empty( $user ) ) {
				return;
			}

			// Fetch the WP_User object of our user.
			um_fetch_user( $user_id );
			$has_admin_cap   = false;
			$arr_banned_caps = UM()->options()->get( 'banned_capabilities' );

			if ( is_array( $arr_banned_caps ) ) {
				foreach ( $arr_banned_caps as $cap ) {
					/**
					 * When there's at least one administrator cap added to the user,
					 * immediately revoke caps and mark as rejected.
					 */
					if ( $user->has_cap( $cap ) ) {
						$has_admin_cap = true;
						break;
					}
				}
			}

			if ( ! $has_admin_cap ) {
				/**
				 * Double-check if *_user_level has been modified with the highest level
				 * when user has no administrative capabilities.
				 */
				$user_level = um_user( $wpdb->get_blog_prefix() . 'user_level' );
				if ( ! empty( $user_level ) && 10 === absint( $user_level ) ) {
					$has_admin_cap = true;
				}
			}

			if ( ! $has_admin_cap ) {
				/**
				 * Validate submitted raw data for banned metakeys.
				 */
				if ( ! empty( UM()->form()->post_form['submitted'] ) && is_array( UM()->form()->post_form['submitted'] ) ) {
					foreach ( array_keys( UM()->form()->post_form['submitted'] ) as $submitted_metakey ) {
						if ( UM()->user()->is_metakey_banned( $submitted_metakey ) ) {
							$has_admin_cap = true;
							break;
						}
					}
				}
			}

			if ( $has_admin_cap ) {
				UM()->common()->secure()->revoke_caps( $user );
				/**
				 * Notify Administrators Immediately
				 */
				if ( UM()->options()->get( 'secure_notify_admins_banned_accounts' ) ) {
					$interval = UM()->options()->get( 'secure_notify_admins_banned_accounts__interval' );
					if ( 'instant' === $interval ) {
						UM()->common()->secure()->send_notification( array( $user_id ) );
					}
				}

				// Destroy Sessions & Redirect.
				wp_destroy_current_session();
				wp_logout();
				session_unset();
				$redirect = apply_filters( 'um_secure_blocked_user_redirect_immediately', true );
				if ( $redirect ) {
					$login_url = add_query_arg( 'err', 'inactive', um_get_core_page( 'login' ) );
					// Not `um_safe_redirect()` because predefined login page is situated on the same host.
					wp_safe_redirect( $login_url );
					exit;
				}
			}
		}

		/**
		 * Set meta (no need to reset his password) if the user is a new registered.
		 *
		 * @since 2.6.8
		 *
		 * @param int $user_id
		 */
		public function maybe_set_whitelisted_password( $user_id ) {
			$user = get_userdata( $user_id );
			if ( empty( $user ) ) {
				return;
			}

			if ( UM()->options()->get( 'display_login_form_notice' ) ) {
				update_user_meta( $user_id, 'um_secure_has_reset_password', true );
				update_user_meta( $user_id, 'um_secure_has_reset_password__timestamp', current_time( 'mysql', true ) );
			}
		}
	}
}
