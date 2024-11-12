<?php
namespace um\common;

use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\common\Secure' ) ) {

	/**
	 * Class Secure
	 *
	 * @package um\common
	 *
	 * @since 2.6.8
	 */
	class Secure {

		public function hooks() {
			add_action( 'wp', array( $this, 'schedule_events' ) );
			add_filter( 'um_get_option_filter__banned_capabilities', array( $this, 'add_default_capabilities' ) );
		}

		/**
		 * Add callbacks to Schedule Events.
		 *
		 * @since 2.6.8
		 */
		public function schedule_events() {
			if ( ! UM()->options()->get( 'secure_ban_admins_accounts' ) ) {
				return;
			}

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
			$user_ids = get_users(
				array(
					'fields'     => 'ids',
					'meta_query' => array(
						'relation' => 'AND',
						array(
							'key'     => 'um_user_blocked__timestamp',
							'value'   => gmdate( 'Y-m-d H:i:s', strtotime( '-1 hour' ) ),
							'compare' => '>=',
							'type'    => 'DATETIME',
						),
					),
				)
			);

			$this->send_notification( $user_ids );
		}

		/**
		 * Notify Administrators daily - Today's suspicious activity
		 *
		 * @since 2.6.8
		 */
		public function notify_administrators_daily() {
			$user_ids = get_users(
				array(
					'fields'     => 'ids',
					'relation'   => 'AND',
					'meta_query' => array(
						'relation' => 'AND',
						array(
							'key'     => 'um_user_blocked__timestamp',
							'value'   => gmdate( 'Y-m-d H:i:s', strtotime( '-1 day' ) ),
							'compare' => '>=',
							'type'    => 'DATE',
						),
						array(
							'key'     => 'um_user_blocked__timestamp',
							'value'   => gmdate( 'Y-m-d H:i:s' ),
							'compare' => '<=',
							'type'    => 'DATE',
						),
					),
				)
			);

			$this->send_notification( $user_ids );
		}

		public function send_notification( $user_ids ) {
			$banned_profile_links = '';
			foreach ( $user_ids as $uid ) {
				um_fetch_user( $uid );
				$banned_profile_links .= UM()->user()->get_profile_link( $uid ) . ' ' . UM()->common()->users()->get_status( $uid ) . '<br />';
			}
			um_reset_user();

			$emails = um_multi_admin_email();
			if ( ! empty( $emails ) ) {
				foreach ( $emails as $email ) {
					UM()->maybe_action_scheduler()->enqueue_async_action(
						'um_dispatch_email',
						array(
							$email,
							'suspicious-activity',
							array(
								'admin'        => true,
								'tags'         => array(
									'{banned_profile_links}',
								),
								'tags_replace' => array(
									$banned_profile_links,
								),
							),
						)
					);
				}
			}
		}

		/**
		 * Get the banned capabilities list.
		 *
		 * @return array
		 */
		public function get_banned_capabilities_list() {
			/**
			 * Filters the banned capabilities for UM Register forms.
			 *
			 * @param {array} $capabilities WordPress Administrative Capabilities.
			 *
			 * @return {array} Banned admin capabilities.
			 *
			 * @since 2.6.8
			 * @hook um_secure_register_form_banned_capabilities
			 *
			 * @example <caption>Added `read` capability as banned.</caption>
			 * function my_banned_capabilities( $capabilities ) {
			 *     $capabilities[] = 'read';
			 *     return $capabilities;
			 * }
			 * add_filter( 'um_secure_register_form_banned_capabilities', 'my_banned_capabilities' );
			 */
			$banned_admin_capabilities = apply_filters(
				'um_secure_register_form_banned_capabilities',
				array(
					'create_sites',
					'delete_sites',
					'manage_network',
					'manage_sites',
					'manage_network_users',
					'manage_network_plugins',
					'manage_network_themes',
					'manage_network_options',
					'upgrade_network',
					'setup_network',
					'activate_plugins',
					'edit_dashboard',
					'edit_theme_options',
					'export',
					'import',
					'list_users',
					'remove_users',
					'switch_themes',
					'customize',
					'delete_site',
					'update_core',
					'update_plugins',
					'update_themes',
					'install_plugins',
					'install_themes',
					'delete_themes',
					'delete_plugins',
					'edit_plugins',
					'edit_themes',
					'edit_files',
					'edit_users',
					'add_users',
					'create_users',
					'delete_users',
					'level_10',
					'manage_options',
					'promote_users',
				)
			);
			return $banned_admin_capabilities;
		}

		/**
		 * Revoke Caps & Mark rejected as suspicious
		 *
		 * @param WP_User $user
		 *
		 * @since 2.6.8
		 */
		public function revoke_caps( $user ) {
			$user_agent = '';
			if ( isset( $_REQUEST['nonce'], $_REQUEST['action'] ) && 'um_secure_scan_affected_users' === $_REQUEST['action'] && wp_verify_nonce( $_REQUEST['nonce'], 'um-admin-nonce' ) && current_user_can( 'manage_options' ) ) {
				$user_agent = __( 'Ultimate Member Scanner', 'ultimate-member' );
			} elseif ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
				$user_agent = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
			}

			um_fetch_user( $user->ID );

			// Capture details.
			$captured = array(
				'capabilities'   => $user->allcaps,
				'submitted'      => ! empty( UM()->form()->post_form ) ? UM()->form()->post_form : '',
				'roles'          => $user->roles,
				'user_agent'     => $user_agent,
				'account_status' => UM()->common()->users()->get_status( $user->ID ),
			);
			update_user_meta( $user->ID, 'um_user_blocked__metadata', $captured );

			$user->remove_all_caps();
			$user->update_user_level_from_caps();

			// Force update of the user status without email notifications.
			if ( is_user_logged_in() ) {
				UM()->common()->users()->set_status( $user->ID, 'inactive' );
			} else {
				UM()->common()->users()->set_status( $user->ID, 'rejected' );
			}

			um_reset_user();
			update_user_meta( $user->ID, 'um_user_blocked', 'suspicious_activity' );
			update_user_meta( $user->ID, 'um_user_blocked__timestamp', current_time( 'mysql', true ) );
			UM()->user()->remove_cache( $user->ID );
		}

		/**
		 * Always add default banned capabilities.
		 *
		 * @param mixed $option_value
		 *
		 * @return mixed
		 *
		 * @since 2.6.8
		 */
		public function add_default_capabilities( $option_value ) {
			if ( is_array( $option_value ) ) {
				$option_value = array_merge( $option_value, UM()->options()->get_default( 'banned_capabilities' ) );
			}
			return $option_value;
		}
	}
}
