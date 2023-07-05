<?php
namespace um\core;

use WP_Error;
use WP_User;
use WP_User_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\core\Secure' ) ) {

	/**
	 * Class Secure
	 *
	 * @package um\core
	 *
	 * @since 2.6.8
	 */
	class Secure {

		/**
		 * Login constructor.
		 * @since 2.6.8
		 */
		public function __construct() {
			add_action( 'init', array( $this, 'init' ) );

			add_action( 'um_before_login_fields', array( $this, 'reset_password_notice' ), 1 );

			add_action( 'um_before_login_fields', array( $this, 'under_maintenance_notice' ), 1 );

			add_action( 'um_submit_form_register', array( $this, 'block_register_forms' ) );

			add_action( 'um_user_login', array( $this, 'login_validate_expired_pass' ), 1 );

			add_action( 'validate_password_reset', array( $this, 'avoid_old_password' ), 1, 2 );

			/**
			 *  WP Schedule Events for Notification
			 */
			add_action( 'wp', array( $this, 'schedule_events' ) );
		}

		/**
		 * Adds handlers on form submissions.
		 *
		 * @since 2.6.8
		 */
		public function init() {
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
				__( '<strong>Important:</strong> This site is currently under maintenance. Please log in or check back soon.', 'ultimate-member' ),
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
		 * @param array $args Form settings.
		 * @since 2.6.8
		 */
		public function block_register_forms( $args ) {
			if ( UM()->options()->get( 'lock_register_forms' ) ) {
				$login_url = add_query_arg( 'notice', 'maintenance', um_get_core_page( 'login' ) );
				nocache_headers();
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
				$expired_password_reset = get_user_meta( um_user( 'ID' ), 'um_secure_has_reset_password', true );
				if ( ! $expired_password_reset ) {
					$login_url = add_query_arg( 'notice', 'expired_password', um_get_core_page( 'login' ) );
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
					update_user_meta( $user->ID, 'um_secure_has_reset_password__timestamp', current_time( 'mysql' ) );
				}
			}
		}

		/**
		 * Secure user capabilities and revoke administrative ones.
		 *
		 * @since 2.6.8
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
			$arr_banned_caps = array();

			if ( UM()->options()->get( 'banned_capabilities' ) ) {
				$arr_banned_caps = array_keys( UM()->options()->get( 'banned_capabilities' ) );
			}

			// Add locked administrative capabilities.
			$arr_banned_caps = array_merge( $arr_banned_caps, UM()->options()->get_default( 'banned_capabilities' ) );

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

			if ( $has_admin_cap ) {
				$this->revoke_caps( $user );
				/**
				 * Notify Administrators Immediately
				 */
				if ( UM()->options()->get( 'secure_notify_admins_banned_accounts' ) ) {
					$interval = UM()->options()->get( 'secure_notify_admins_banned_accounts__interval' );
					if ( 'instant' === $interval ) {
						$this->send_email( array( $user_id ) );
					}
				}

				// Destroy Sessions & Redirect.
				wp_destroy_current_session();
				wp_logout();
				session_unset();
				$redirect = apply_filters( 'um_secure_blocked_user_redirect_immediately', true );
				if ( $redirect ) {
					$login_url = add_query_arg( 'err', 'inactive', um_get_core_page( 'login' ) );
					wp_safe_redirect( $login_url );
					exit;
				}
			} else {
				if ( UM()->options()->get( 'display_login_form_notice' ) ) {
					update_user_meta( $user_id, 'um_secure_has_reset_password', true );
					update_user_meta( $user_id, 'um_secure_has_reset_password__timestamp', current_time( 'mysql' ) );
				}
			}
		}

		/**
		 * Secure user capabilities and revoke administrative ones.
		 *
		 * @since 2.6.8
		 */
		public function maybe_set_whitelisted_password( $user_id ) {
			global $wpdb;
			$user = get_userdata( $user_id );
			if ( empty( $user ) ) {
				return;
			}

			if ( UM()->options()->get( 'display_login_form_notice' ) ) {
				update_user_meta( $user_id, 'um_secure_has_reset_password', true );
				update_user_meta( $user_id, 'um_secure_has_reset_password__timestamp', current_time( 'mysql' ) );
			}
		}

		/**
		 * Revoke Caps & Mark rejected as suspicious
		 *
		 * @param object $user \WP_User
		 *
		 * @since 2.6.8
		 */
		public function revoke_caps( $user ) {
			$user_agent = '';
			if ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
				$user_agent = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
			}
			// Capture details.
			$captured = array(
				'capabilities'   => $user->allcaps,
				'submitted'      => UM()->form()->post_form,
				'roles'          => $user->roles,
				'user_agent'     => $user_agent,
				'account_status' => get_user_meta( $user->ID, 'account_status', true ),
			);
			update_user_meta( $user->ID, 'um_user_blocked__metadata', $captured );

			$user->remove_all_caps();
			if ( is_user_logged_in() ) {
				UM()->user()->set_status( 'inactive' );
			} else {
				UM()->user()->set_status( 'rejected' );
			}
			update_user_meta( $user->ID, 'um_user_blocked', 'suspicious_activity' );
			update_user_meta( $user->ID, 'um_user_blocked__datetime', current_time( 'mysql' ) );
		}

		/**
		 * Add callbacks to Schedule Events.
		 *
		 * @since 2.6.8
		 */
		public function schedule_events() {
			if ( UM()->options()->get( 'secure_notify_admins_banned_accounts' ) ) {
				$notification_interval = UM()->options()->get( 'secure_notify_admins_banned_accounts__interval' );
				if ( 'instant' === $notification_interval ) {
					return;
				}

				if ( 'hourly' === $notification_interval ) {
					add_action( 'um_hourly_scheduled_events', array( $this, 'notify_administrators_hourly' ) );
				} elseif ( 'daily' === $notification_interval ) {
					add_action( 'um_daily_scheduled_events', array( $this, 'notify_administrators_daily' ) );
				}
			}
		}

		/**
		 * Notify Administrators hourly - Suspicious activities in an hour
		 *
		 * @since 2.6.8
		 */
		public function notify_administrators_hourly() {
			$args = array(
				'fields'     => 'ID',
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key'     => 'um_user_blocked__datetime',
						'value'   => gmdate( 'Y-m-d H:i:s', strtotime( '-1 hour' ) ),
						'compare' => '>=',
						'type'    => 'DATETIME',
					),
				),
			);

			$users = new WP_User_Query( $args );

			$this->send_email( array_values( $users->get_results() ) );
		}

		/**
		 * Notify Administrators daily - Today's suspicious activity
		 *
		 * @since 2.6.8
		 */
		public function notify_administrators_daily() {
			$args = array(
				'fields'     => 'ID',
				'relation'   => 'AND',
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key'     => 'um_user_blocked__datetime',
						'value'   => gmdate( 'Y-m-d H:i:s', strtotime( '-1 day' ) ),
						'compare' => '>=',
						'type'    => 'DATE',
					),
					array(
						'key'     => 'um_user_blocked__datetime',
						'value'   => gmdate( 'Y-m-d H:i:s', strtotime( 'now' ) ),
						'compare' => '<=',
						'type'    => 'DATE',
					),
				),
			);

			$users = new WP_User_Query( $args );

			$this->send_email( array_values( $users->get_results() ) );
		}

		/**
		 * Get Email template
		 *
		 * @param bool $single Whether the template is for single or multiple user activities
		 * @param array $profile_urls Profile URLs to include in the email body
		 *
		 * @since 2.6.8
		 */
		public function get_email_template( $single = true, $profile_urls = array() ) {
			$action = '';
			if ( ! is_user_logged_in() ) {
				$action = 'Rejected';
			} else {
				$action = 'Deactivated';
			}

			$body = '';
			if ( $single ) {
				$body  = 'This is to inform you that there\'s a suspicious activity with the following account: ';
				$body .= '<br/>';
				$body .= '{user_profile_link}';
				$body .= '<br/><br/>';
				$body .= 'Due to that we have set the account status to ' . $action . ', Revoked Roles & Destroyed the Login Session.';
				$body .= '</br>';
			} else {
				$body  = 'This is to inform you that there are suspicious activities with the following accounts: ';
				$body .= '</br>';
				$body .= '{user_profile_link}';
				$body .= '</br></br>';
				$body .= 'Due to that we have set each account\'s status to ' . $action . ', revoked roles & destroyed the login session.';
				$body .= '</br>';
			}

			$urls  = implode( '</br>', $profile_urls );
			$body  = str_replace( '{user_profile_link}', $urls, $body );
			$body .= '<br/><br/>- Sent via Ultimate Member plugin. ';

			return $body;
		}

		/**
		 * Send Email
		 *
		 * @param array $user_ids User IDs.
		 *
		 * @since 2.6.8
		 */
		public function send_email( $user_ids = array() ) {

			if ( empty( $user_ids ) ) {
				return '';
			}
			$multiple_recipients = array();
			$admins              = get_users( 'role=Administrator' );
			foreach ( $admins as $user ) {
				$multiple_recipients[] = $user->user_email;
			}

			$subject = _n( 'Suspicious Account Activity on ', 'Suspicious Accounts & Activities on ', count( $user_ids ), 'ultimate-member' ) . wp_parse_url( get_site_url() )['host'];

			if ( count( $user_ids ) <= 1 ) {
				$url  = UM()->user()->get_profile_link( $user_ids[0] );
				$body = $this->get_email_template( true, array( $url ) );
			} else {
				$arr_urls = array();
				foreach ( $user_ids as $i => $uid ) {
					$arr_urls[] = UM()->user()->get_profile_link( $uid );
				}
				$body = $this->get_email_template( false, $arr_urls );
			}

			wp_mail( $multiple_recipients, $subject, $body );

		}
	}
}
