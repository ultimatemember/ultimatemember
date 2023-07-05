<?php
namespace um\admin\core;

use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\admin\core\Secure' ) ) {

	/**
	 * Class Secure
	 *
	 * @package um\admin\core
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
			add_filter( 'manage_users_custom_column', array( $this, 'add_restore_account' ), 10, 3 );

			add_action( 'um_settings_before_save', array( $this, 'check_secure_changes' ) );
			add_action( 'um_settings_save', array( $this, 'on_settings_save' ) );
		}

		/**
		 * Handle secure actions.
		 *
		 * @since 2.6.8
		 */
		public function admin_init() {
			// Dismiss admin notice after the first visit to Secure settings page.
			if ( isset( $_REQUEST['page'] ) && isset( $_REQUEST['tab'] ) &&
				'um_options' === sanitize_key( $_REQUEST['page'] ) && 'secure' === sanitize_key( $_REQUEST['tab'] ) ) {
				UM()->admin()->notices()->dismiss( 'secure_settings' );
			}

			if ( isset( $_REQUEST['um_secure_expire_all_sessions'] ) && ! wp_doing_ajax() ) {
				if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'um-secure-expire-session-nonce' ) || ! current_user_can( 'manage_options' ) ) {
					// This nonce is not valid or current logged-in user has no administrative rights.
					wp_die( esc_html__( 'Security check', 'ultimate-member' ) );
				}

				$users = get_users(
					array(
						'fields' => 'ids',
					)
				);

				$users = array_values( array_diff( $users, array( get_current_user_id() ) ) );

				if ( ! empty( $users ) ) {
					foreach ( $users as $user_id ) {
						// Get an instance of WP_User_Meta_Session_Tokens
						$sessions_manager = \WP_Session_Tokens::get_instance( $user_id );
						// Remove all the session for instance user.
						$sessions_manager->destroy_all();

						// Remove all the session data for all users.
						//$sessions_manager::drop_sessions();
					}

					if ( UM()->options()->get( 'display_login_form_notice' ) ) {
						global $wpdb;
						$wpdb->query(
							$wpdb->prepare(
								"DELETE FROM {$wpdb->usermeta} WHERE user_id != %d AND ( meta_key = 'um_secure_has_reset_password' OR meta_key = 'um_secure_has_reset_password__timestamp' )",
								get_current_user_id()
							)
						);
					}
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

				$metadata = get_user_meta( $user_id, 'um_user_blocked__metadata', true );

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
				delete_user_meta( $user_id, 'um_user_blocked__datetime' );

				// Don't need to reset a password.
				update_user_meta( $user_id, 'um_secure_has_reset_password', true );
				update_user_meta( $user_id, 'um_secure_has_reset_password__timestamp', current_time( 'mysql' ) );

				// Clear Cache.
				UM()->user()->remove_cache( $user_id );
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

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_secure_register_form_banned_capabilities
			 * @description Modify banned capabilities for Register forms
			 * @input_vars
			 * [{"var":"$capabilities","type":"array","desc":"WordPress Administratrive Capabilities"}]
			 * @change_log
			 * ["Since: 2.6.8"]
			 * @usage
			 * <?php add_filter( 'um_secure_register_form_banned_capabilities', 'function_name', 10, 1 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_secure_register_form_banned_capabilities', 'my_banned_capabilities', 10, 1 );
			 * function my_banned_capabilities( $capabiities ) {
			 *     // your code here
			 *     $capabiities[ ] = 'read'; // rejects all users with `read` capabilitiy.
			 *     return $capabiities;
			 * }
			 * ?>
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

			$banned_capabilities = array();
			foreach ( $banned_admin_capabilities as $cap ) {
				$banned_capabilities[ $cap ] = $cap;
			}

			$secure_fields = array(
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

			$disabled_capabilities      = UM()->options()->get_default( 'banned_capabilities' );
			$disabled_capabilities_text = '<strong>' . implode( '</strong>, <strong>', $disabled_capabilities ) . '</strong>';

			$secure_fields = array_merge(
				$secure_fields,
				array(
					array(
						'id'               => 'banned_capabilities',
						'type'             => 'multi_checkbox',
						'multi'            => true,
						'columns'          => 2,
						'options_disabled' => UM()->options()->get_default( 'banned_capabilities' ),
						'options'          => $banned_capabilities,
						'label'            => __( 'Banned Administrative Capabilities', 'ultimate-member' ),
						// translators: %s are disabled default capabilities that are enabled by default.
						'description'      => sprintf( __( 'All the above are default Administrator & Super Admin capabilities. When someone tries to inject capabilities to the Account, Profile & Register forms submission, it will be flagged with this option. The %s capabilities are locked to ensure no users will be created with these capabilities.', 'ultimate-member' ), $disabled_capabilities_text ),
					),
					array(
						'id'          => 'secure_notify_admins_banned_accounts',
						'type'        => 'checkbox',
						'label'       => __( 'Notify Administrators', 'ultimate-member' ),
						'description' => __( 'When enabled, All administrators will be notified when someone has suspicious activities in Profile & Register forms.', 'ultimate-member' ),
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
					$datetime            = um_user( 'um_user_blocked__datetime' );
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
			if ( isset( $_POST['um_options']['display_login_form_notice'] ) ) {
				$current_option_value = UM()->options()->get( 'display_login_form_notice' );
				if ( empty( $current_option_value ) ) {
					return;
				}

				if ( empty( $_POST['um_options']['display_login_form_notice'] ) ) {
					$this->need_flush_meta = true;
				}
			}
		}

		/**
		 *
		 */
		public function on_settings_save() {
			if ( isset( $_POST['um_options']['display_login_form_notice'] ) && ! empty( $this->need_flush_meta ) ) {
				global $wpdb;
				$wpdb->query(
					"DELETE FROM {$wpdb->usermeta} WHERE meta_key = 'um_secure_has_reset_password' OR meta_key = 'um_secure_has_reset_password__timestamp'"
				);
			}
		}
	}
}
