<?php
/**
 * Usermeta which we use:
 *
 * um_user_blocked__metadata
 * um_user_blocked
 * um_user_blocked__timestamp
 *
 * um_secure_has_reset_password
 * um_secure_has_reset_password__timestamp
 */
namespace um\admin;

use WP_Session_Tokens;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\admin\Secure' ) ) {

	/**
	 * Class Secure
	 *
	 * @package um\admin
	 *
	 * @since 2.6.8
	 */
	class Secure {

		/**
		 * Used for flushing user metas.
		 *
		 * @var bool
		 */
		private $need_flush_meta = false;

		/**
		 * Secure constructor.
		 *
		 * @since 2.6.8
		 */
		public function __construct() {
			add_action( 'admin_init', array( $this, 'admin_init' ) );
			add_filter( 'um_settings_structure', array( $this, 'add_settings' ) );
			add_filter( 'manage_users_custom_column', array( $this, 'add_restore_account' ), 9999, 3 );
			add_filter( 'pre_get_users', array( $this, 'filter_users_by_date_registered' ) );

			add_action( 'um_settings_before_save', array( $this, 'check_secure_changes' ) );
			add_action( 'um_settings_save', array( $this, 'on_settings_save' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );

			add_action( 'wp_ajax_um_secure_scan_affected_users', array( $this, 'ajax_scanner' ) );
		}

		public function admin_scripts( $hook ) {
			// phpcs:disable WordPress.Security.NonceVerification
			if ( 'ultimate-member_page_um_options' !== $hook || ( isset( $_GET['tab'] ) && 'secure' !== $_GET['tab'] ) ) {
				return;
			}
			// phpcs:enable WordPress.Security.NonceVerification

			wp_register_script( 'um_admin_secure', UM()->admin()->enqueue()->js_url . 'um-admin-secure.js', array( 'jquery' ), UM_VERSION, true );
			wp_enqueue_script( 'um_admin_secure' );
		}

		/**
		 * Filter users by Register Date
		 *
		 * @since 2.6.8
		 * @param object $query WP query `pre_get_users`
		 */
		public function filter_users_by_date_registered( $query ) {
			global $pagenow;
			if ( 'users.php' === $pagenow && is_admin() ) {
				// phpcs:disable WordPress.Security.NonceVerification
				$date_from = isset( $_GET['um_secure_date_from'] ) ? $_GET['um_secure_date_from'] : null;
				$date_to   = isset( $_GET['um_secure_date_to'] ) ? $_GET['um_secure_date_to'] : null;
				// phpcs:enable WordPress.Security.NonceVerification
				if ( $date_from ) {
					$date_query_attr = array(
						'after'     => human_time_diff( $date_from, strtotime( current_time( 'mysql' ) ) ) . ' ago',
						'inclusive' => true,
					);
					if ( $date_to ) {
						$date_query_attr['before'] = human_time_diff( $date_to, strtotime( current_time( 'mysql' ) ) ) . ' ago';
					}
					$query->set( 'date_query', $date_query_attr );
				}
			}

			return $query;
		}

		/**
		 * Handle secure actions.
		 *
		 * @since 2.6.8
		 */
		public function admin_init() {
			global $wpdb;
			// Dismiss admin notice after the first visit to Secure settings page.
			if ( isset( $_REQUEST['page'] ) && isset( $_REQUEST['tab'] ) &&
				'um_options' === sanitize_key( $_REQUEST['page'] ) && 'secure' === sanitize_key( $_REQUEST['tab'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				UM()->admin()->notices()->dismiss( 'secure_settings' );
			}

			if ( isset( $_REQUEST['um_secure_expire_all_sessions'] ) && ! wp_doing_ajax() ) {
				if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'um-secure-expire-session-nonce' ) || ! current_user_can( 'manage_options' ) ) {
					// This nonce is not valid or current logged-in user has no administrative rights.
					wp_die( esc_html__( 'Security check', 'ultimate-member' ) );
				}

				/**
				 * Destroy all user sessions except the current logged-in user.
				 */
				$wpdb->query(
					$wpdb->prepare(
						"DELETE
						FROM {$wpdb->usermeta}
						WHERE meta_key='session_tokens' AND
							  user_id != %d",
						get_current_user_id()
					)
				);

				if ( UM()->options()->get( 'display_login_form_notice' ) ) {
					global $wpdb;
					$wpdb->query(
						$wpdb->prepare(
							"DELETE
							FROM {$wpdb->usermeta}
							WHERE user_id != %d AND
								  ( meta_key = 'um_secure_has_reset_password' OR meta_key = 'um_secure_has_reset_password__timestamp' )",
							get_current_user_id()
						)
					);
				}

				wp_safe_redirect( add_query_arg( 'update', 'um_secure_expire_sessions', wp_get_referer() ) );
				exit;
			}

			if ( isset( $_REQUEST['um_secure_restore_account'], $_REQUEST['user_id'] ) && ! wp_doing_ajax() ) {
				$user_id = absint( $_REQUEST['user_id'] );
				if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'um-security-restore-account-nonce-' . $user_id ) || ! current_user_can( 'manage_options' ) ) {
					// This nonce is not valid or current logged-in user has no administrative rights.
					wp_die( esc_html__( 'Security check', 'ultimate-member' ) );
				}

				$user = get_userdata( $user_id );
				if ( ! $user ) {
					wp_die( esc_html__( 'Invalid user.', 'ultimate-member' ) );
				}

				um_fetch_user( $user_id );
				$metadata = get_user_meta( $user_id, 'um_user_blocked__metadata', true );
				$user->update_user_level_from_caps();

				// Restore Roles.
				if ( isset( $metadata['roles'] ) ) {
					foreach ( $metadata['roles'] as $role ) {
						$user->add_role( $role );
					}
				}
				// Restore Account Status.
				if ( isset( $metadata['account_status'] ) ) {
					UM()->user()->set_status( $metadata['account_status'] );
				}

				// Delete blocked meta.
				delete_user_meta( $user_id, 'um_user_blocked__metadata' );
				delete_user_meta( $user_id, 'um_user_blocked' );
				delete_user_meta( $user_id, 'um_user_blocked__timestamp' );

				// Don't need to reset a password.
				if ( UM()->options()->get( 'display_login_form_notice' ) ) {
					update_user_meta( $user_id, 'um_secure_has_reset_password', true );
					update_user_meta( $user_id, 'um_secure_has_reset_password__timestamp', current_time( 'mysql' ) );
				}

				// Clear Cache.
				UM()->user()->remove_cache( $user_id );
				um_reset_user();
				wp_safe_redirect( add_query_arg( 'update', 'um_secure_restore', wp_get_referer() ) );
				exit;
			}
		}

		/**
		 * Register Secure Settings
		 *
		 * @since 2.6.8
		 *
		 * @param array $settings
		 * @return array
		 */
		public function add_settings( $settings ) {
			$nonce       = wp_create_nonce( 'um-secure-expire-session-nonce' );
			$count_users = count_users();

			$banned_capabilities       = array();
			$banned_admin_capabilities = UM()->common()->secure()->get_banned_capabilities_list();
			foreach ( $banned_admin_capabilities as $cap ) {
				$banned_capabilities[ $cap ] = $cap;
			}

			$disabled_capabilities      = UM()->options()->get_default( 'banned_capabilities' );
			$disabled_capabilities_text = '<strong>' . implode( '</strong>, <strong>', $disabled_capabilities ) . '</strong>';

			$scanner_content   = '<button class="button um-secure-scan-content">' . esc_html__( 'Scan Now', 'ultimate-member' ) . '</button>';
			$scanner_content  .= '<span class="um-secure-scan-results">';
			$scanner_content  .= esc_html__( 'Last scan:', 'ultimate-member' ) . ' ';
			$scan_status       = get_option( 'um_secure_scan_status' );
			$last_scanned_time = get_option( 'um_secure_last_time_scanned' );
			if ( ! empty( $last_scanned_time ) ) {
				$scanner_content .= human_time_diff( strtotime( $last_scanned_time ), strtotime( current_time( 'mysql' ) ) ) . ' ' . esc_html__( 'ago', 'ultimate-member' );
				if ( 'started' === $scan_status ) {
					$scanner_content .= ' - ' . esc_html__( 'Not Completed.', 'ultimate-member' );
				}
			} else {
				$scanner_content .= esc_html__( 'Not Scanned yet.', 'ultimate-member' );
			}
			$scanner_content .= '</span>';

			$secure_fields = array(
				array(
					'id'               => 'banned_capabilities',
					'type'             => 'multi_checkbox',
					'multi'            => true,
					'assoc'            => true,
					'checkbox_key'     => true,
					'columns'          => 2,
					'options_disabled' => $disabled_capabilities,
					'options'          => $banned_capabilities,
					'label'            => __( 'Banned Administrative Capabilities', 'ultimate-member' ),
					// translators: %s are disabled default capabilities that are enabled by default.
					'description'      => sprintf( __( 'All the above are default Administrator & Super Admin capabilities. When someone tries to inject capabilities to the Account, Profile & Register forms submission, it will be flagged with this option. The %s capabilities are locked to ensure no users will be created with these capabilities.', 'ultimate-member' ), $disabled_capabilities_text ),
				),
				array(
					'id'          => 'secure_scan_affected_users',
					'type'        => 'info_text',
					'label'       => __( 'Scanner', 'ultimate-member' ),
					'value'       => $scanner_content,
					'description' => __( 'Scan your site to check for vulnerabilities prior to Ultimate Member version 2.6.7 and get recommendations to secure your site.', 'ultimate-member' ),
				),
				array(
					'id'          => 'lock_register_forms',
					'type'        => 'checkbox',
					'label'       => __( 'Lock All Register Forms', 'ultimate-member' ),
					'description' => __( 'This prevents all users from registering with Ultimate Member on your site.', 'ultimate-member' ),
				),
				array(
					'id'          => 'display_login_form_notice',
					'type'        => 'checkbox',
					'label'       => __( 'Display Login form notice to reset passwords', 'ultimate-member' ),
					'description' => __( 'Enforces users to reset their passwords( one-time ) and prevent from entering old password.', 'ultimate-member' ),
				),
			);

			$count_users_exclude_me = $count_users['total_users'] - 1;
			if ( $count_users_exclude_me > 0 ) {
				$secure_fields[] = array(
					'id'          => 'force_reset_passwords',
					'type'        => 'info_text',
					'label'       => __( 'Expire All Users Sessions', 'ultimate-member' ),
					// translators: %d is the users count.
					'value'       => '<a class="button um_secure_force_reset_passwords" href="' . admin_url( '?um_secure_expire_all_sessions=1&_wpnonce=' . esc_attr( $nonce ) ) . '" onclick=\'return confirm("' . esc_js( __( 'Are you sure that you want to make all users sessions expired?', 'ultimate-member' ) ) . '");\'>' . esc_html( sprintf( __( 'Logout Users (%d)', 'ultimate-member' ), $count_users_exclude_me ) ) . '</a>',
					'description' => __( 'This will log out all users on your site and forces them to reset passwords <br/>when <strong>"Display Login form notice to reset passwords" is enabled/checked.</strong>', 'ultimate-member' ),
				);
			}

			$secure_fields = array_merge(
				$secure_fields,
				array(
					array(
						'id'          => 'secure_ban_admins_accounts',
						'type'        => 'checkbox',
						'label'       => __( 'Enable ban for administrative capabilities', 'ultimate-member' ),
						'description' => __( ' When someone tries to inject capabilities to the Account, Profile & Register forms submission, it will be banned.', 'ultimate-member' ),
					),
					array(
						'id'          => 'secure_notify_admins_banned_accounts',
						'type'        => 'checkbox',
						'label'       => __( 'Notify Administrators', 'ultimate-member' ),
						'description' => __( 'When enabled, All administrators will be notified when someone has suspicious activities in the Account, Profile & Register forms.', 'ultimate-member' ),
						'conditional' => array( 'secure_ban_admins_accounts', '=', 1 ),
					),
					array(
						'id'          => 'secure_notify_admins_banned_accounts__interval',
						'type'        => 'select',
						'options'     => array(
							'instant' => __( 'Send Immediately', 'ultimate-member' ),
							'hourly'  => __( 'Hourly', 'ultimate-member' ),
							'daily'   => __( 'Daily', 'ultimate-member' ),
						),
						'label'       => __( 'Notification Schedule', 'ultimate-member' ),
						'conditional' => array( 'secure_notify_admins_banned_accounts', '=', 1 ),
					),
					array(
						'id'          => 'secure_allowed_redirect_hosts',
						'type'        => 'textarea',
						'label'       => __( 'Allowed hosts for safe redirect (one host per line)', 'ultimate-member' ),
						'description' => __( 'Extend allowed hosts for frontend pages redirects', 'ultimate-member' ),
					),
				)
			);

			$settings['secure'] = array(
				'title'  => __( 'Secure', 'ultimate-member' ),
				'fields' => $secure_fields,
			);

			return $settings;
		}

		/**
		 * Append blocked status to the `account_status` column rows.
		 *
		 * @param string $val         Default column row value.
		 * @param string $column_name Current column name.
		 * @param int    $user_id     User ID in loop.
		 *
		 * @since 2.6.8
		 *
		 * @return string
		 */
		public function add_restore_account( $val, $column_name, $user_id ) {
			if ( 'account_status' === $column_name ) {
				um_fetch_user( $user_id );
				$is_blocked     = um_user( 'um_user_blocked' );
				$account_status = um_user( 'account_status' );
				if ( ! empty( $is_blocked ) && in_array( $account_status, array( 'rejected', 'inactive' ), true ) ) {
					$datetime            = um_user( 'um_user_blocked__timestamp' );
					$val                .= '<div><small>' . esc_html__( 'Blocked Due to Suspicious Activity', 'ultimate-member' ) . '</small></div>';
					$nonce               = wp_create_nonce( 'um-security-restore-account-nonce-' . $user_id );
					$restore_account_url = admin_url( 'users.php?user_id=' . $user_id . '&um_secure_restore_account=1&_wpnonce=' . $nonce );
					$action              = ' &#183; <a href=" ' . esc_attr( $restore_account_url ) . ' " onclick=\'return confirm("' . esc_js( __( 'Are you sure that you want to restore this account after getting flagged for suspicious activity?', 'ultimate-member' ) ) . '");\'><small>' . esc_html__( 'Restore Account', 'ultimate-member' ) . '</small></a>';
					if ( ! empty( $datetime ) ) {
						$val .= '<div><small>' . human_time_diff( strtotime( $datetime ), strtotime( current_time( 'mysql' ) ) ) . ' ' . __( 'ago', 'ultimate-member' ) . '</small>' . $action . '</div>';
					}
				}
				um_reset_user();
			}
			return $val;
		}

		/**
		 *
		 */
		public function check_secure_changes() {
			if ( isset( $_POST['um_options']['display_login_form_notice'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification
				$current_option_value = UM()->options()->get( 'display_login_form_notice' );
				if ( empty( $current_option_value ) ) {
					return;
				}

				if ( empty( $_POST['um_options']['display_login_form_notice'] ) ) {  //phpcs:ignore WordPress.Security.NonceVerification
					$this->need_flush_meta = true;
				}
			}
		}

		/**
		 *
		 */
		public function on_settings_save() {
			if ( isset( $_POST['um_options']['display_login_form_notice'] ) && ! empty( $this->need_flush_meta ) ) {  //phpcs:ignore WordPress.Security.NonceVerification
				global $wpdb;
				$wpdb->query(
					"DELETE FROM {$wpdb->usermeta} WHERE meta_key = 'um_secure_has_reset_password' OR meta_key = 'um_secure_has_reset_password__timestamp'"
				);
			}

			if ( isset( $_POST['um_options']['secure_notify_admins_banned_accounts'] ) ) {   //phpcs:ignore WordPress.Security.NonceVerification
				if ( ! empty( $_POST['um_options']['secure_notify_admins_banned_accounts'] ) ) {  //phpcs:ignore WordPress.Security.NonceVerification
					UM()->options()->update( 'suspicious-activity_on', 1 );
				} else {
					UM()->options()->update( 'suspicious-activity_on', 0 );
				}
			}
		}
	}
}
