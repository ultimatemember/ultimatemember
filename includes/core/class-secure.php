<?php
namespace um\core;

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
		 * Banned Administrative Capabilities
		 *
		 * @since 2.6.8
		 *
		 * @var array
		 */
		public $banned_admin_capabilities = array();

		/**
		 * Banned Administrative Capabilities
		 *
		 * @since 2.6.8
		 *
		 * @var array
		 */
		public $banned_locked_capabilities = array();

		/**
		 * Login constructor.
		 * @since 2.6.8
		 */
		public function __construct() {
			add_action( 'admin_init', array( $this, 'admin_init' ) );

			add_action( 'um_before_login_fields', array( $this, 'reset_password_notice' ) );

			add_action( 'um_before_login_fields', array( $this, 'under_maintanance_notice' ) );

			add_filter( 'um_settings_structure', array( $this, 'add_settings' ) );

			add_action( 'um_submit_form_register', array( $this, 'block_register_forms' ) );

			add_action( 'um_user_login', array( $this, 'login_validate_expired_pass' ), 1 );

			add_action( 'validate_password_reset', array( $this, 'avoid_old_password' ), 1, 2 );

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
			$this->banned_admin_capabilities = apply_filters(
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

			$this->banned_locked_capabilities = array( 'manage_options', 'promote_users', 'level_10' );

			/**
			 * Add blocked status in the User column list.
			 */
			add_filter( 'manage_users_custom_column', array( $this, 'manage_users_custom_column' ), 10, 3 );

			/**
			 *  WP Schedule Events for Notification
			 */
			add_action( 'wp', array( $this, 'schedule_events' ) );

			/**
			 * Init
			 */
			add_action( 'init', array( $this, 'init' ) );

			/**
			 * Admin Notice
			 */
			add_action( 'admin_notices', array( $this, 'admin_notice' ) );

			/**
			 * Enqueue Scripts
			 */
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );

			/**
			 * Ajax
			 */
			add_action( 'wp_ajax_um_secure_scan_affected_users', array( $this, 'ajax_scanner' ) );

		}

		public function admin_scripts( $hook ) {
			// phpcs:disable WordPress.Security.NonceVerification
			if ( 'ultimate-member_page_um_options' !== $hook || isset( $_REQUEST['tab'] ) && 'secure' !== $_REQUEST['tab'] ) {
				return;
			}
			// phpcs:enable WordPress.Security.NonceVerification

			wp_register_script( 'um_admin_secure', UM()->admin_enqueue()->js_url . 'um-admin-secure.js', array( 'jquery' ), ultimatemember_version, true );
			wp_enqueue_script( 'um_admin_secure' );
		}

		/**
		 * Init
		 *
		 * @since 2.6.8
		 */
		public function init() {

			/**
			 * Checks the integrity of Current User's Capabilities
			 */
			add_action( 'um_after_save_registration_details', array( $this, 'secure_user_capabilities' ), 10 );
			if ( is_user_logged_in() && ! current_user_can( 'manage_options' ) ) { // Exclude current Logged-in Administrator from validation checks.
				add_action( 'um_after_user_updated', array( $this, 'secure_user_capabilities' ), 1 );
				add_action( 'um_after_user_account_updated', array( $this, 'secure_user_capabilities' ), 1 );
			}

		}

		/**
		 * Admin Init
		 *
		 * @since 2.6.8
		 */
		public function admin_init() {

			if ( isset( $_REQUEST['um_secure_expire_all_sessions'] ) && ( ! wp_doing_ajax() ) ) {
				if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'um-secure-expire-session-nonce' ) || ! current_user_can( 'manage_options' ) ) {
					// This nonce is not valid or current logged-in user has no administrativerights.
					wp_die( esc_html_e( 'Security check', 'ultimate-member' ) );
				}

				// Get an instance of WP_User_Meta_Session_Tokens
				$sessions_manager = \WP_Session_Tokens::get_instance( null );
				$user_id          = get_current_user_id();
				$sessions_manager->drop_sessions();
				UM()->user()->auto_login( $user_id );

				set_transient( 'um_secure_sessions_destroyed_admin_notice', 1, 0 );
				wp_safe_redirect( admin_url() );
				exit;
			}

			if ( isset( $_REQUEST['um_secure_restore_account'] ) && isset( $_REQUEST['user_id'] ) && ( ! wp_doing_ajax() ) ) {
				$user_id = $_REQUEST['user_id'];
				if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'um-security-restore-account-nonce-' . $user_id ) || ! current_user_can( 'manage_options' ) ) {
					// This nonce is not valid or current logged-in user has no administrativerights.
					wp_die( esc_html_e( 'Security check', 'ultimate-member' ) );
				}

				$user     = new WP_User( $user_id );
				$metadata = $user->get( 'um_user_blocked__metadata' );
				$user->update_user_level_from_caps();

				// Restore Roles.
				if ( isset( $metadata['roles'] ) ) {
					foreach ( $metadata['roles'] as $role ) {
						$user->add_role( $role );
					}
				}
				// Restore Account Status.
				if ( isset( $metadata['account_status'] ) ) {
					update_user_meta( $user_id, 'account_status', $metadata['account_status'] );
				}
				// Clear Cache
				UM()->user()->remove_cache( $user_id );

				// Remove block
				delete_user_meta( $user_id, 'um_user_blocked' );

				set_transient( 'um_secure_restore_account_notice_success', 1, 5 );

				wp_safe_redirect( wp_get_referer() );
				exit;

			}

			if ( isset( $_REQUEST['um_dismiss_security_first_time_notice'] ) ) {
				set_transient( 'um_secure_first_time_admin_notice', 1, 0 );
			}

			if ( isset( $_REQUEST['um_secure_lock_register_forms'] ) ) {
				if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'um_secure_lock_register_forms' ) || ! current_user_can( 'manage_options' ) ) {
					// This nonce is not valid or current logged-in user has no administrativerights.
					wp_die( esc_html_e( 'Security check', 'ultimate-member' ) );
				}
				UM()->options()->update( 'lock_register_forms', 1 );
				set_transient( 'um_secure_locked_register_forms_admin_notice', 1, 0 );
			}

			if ( isset( $_REQUEST['um_secure_enable_reset_password'] ) ) {
				if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'um-secure-enable-reset-pass-nonce' ) || ! current_user_can( 'manage_options' ) ) {
					// This nonce is not valid or current logged-in user has no administrativerights.
					wp_die( esc_html_e( 'Security check', 'ultimate-member' ) );
				}
				UM()->options()->update( 'display_login_form_notice', 1 );
				set_transient( 'um_secure_enable_reset_password_admin_notice', 1, 0 );
			}
		}

		/**
		 * Add Login notice for Reset Password
		 *
		 * @param array $args
		 * @since 2.6.8
		 */
		public function reset_password_notice( $args ) {

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
		 * @param array $args
		 * @since 2.6.8
		 */
		public function under_maintanance_notice( $args ) {

			if ( ! UM()->options()->get( 'lock_register_forms' ) ) {
				return;
			}

			// phpcs:disable WordPress.Security.NonceVerification
			if ( ! isset( $_REQUEST['notice'] ) || 'maintanance' !== $_REQUEST['notice'] ) {
				return;
			}
			// phpcs:enable WordPress.Security.NonceVerification

			echo "<p class='um-notice warning'>";
			echo wp_kses(
				sprintf(
					// translators: One-time change requires you to reset your password
					__( '<strong>Important:</strong> This site is currently under maintenance. Please check back soon.', 'ultimate-member' ),
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
		 * Register Secure Settings
		 *
		 * @param array $settings
		 * @since 2.6.8
		 */
		public function add_settings( $settings ) {
			$nonce                       = wp_create_nonce( 'um-secure-expire-session-nonce' );
			$count_users                 = count_users();
			$settings['secure']['title'] = __( 'Secure', 'ultimate-member' );

			$banned_admin_capabilities_options = array();
			$default_locked_cap_options        = $this->banned_locked_capabilities;
			foreach ( $this->banned_admin_capabilities as $i => $cap ) {
				$banned_admin_capabilities_options[ $cap ] = $cap;
			}

			$scanner_content   = '<button class="button-primary um-secure-scan-content">' . __( 'Scan Now', 'ultimate-member' ) . '</button>';
			$scanner_content  .= '<span class="um-secure-scan-results">';
			$scanner_content  .= __( 'Last scan:', 'ultimate-member' ) . ' ';
			$scan_status       = get_option( 'um_secure_scan_status' );
			$last_scanned_time = get_option( 'um_secure_last_time_scanned' );
			if ( ! empty( $last_scanned_time ) ) {
				$scanner_content .= human_time_diff( strtotime( $last_scanned_time ), strtotime( current_time( 'mysql' ) ) ) . ' ' . __( 'ago', 'ultimate-member' );
				if ( 'started' === $scan_status ) {
					$scanner_content .= ' - ' . __( 'Not Completed.', 'ultimate-member' );
				}
			} else {
				$scanner_content .= __( 'Not Scanned yet.', 'ultimate-member' );
			}
			$scanner_content .= '</span>';

			$settings['secure']['sections'] =
			array(
				'' =>
				array(
					'title'  => __( 'Secure Ultimate Member', 'ultimate-member' ),
					'fields' => array(
						array(
							'id'          => 'secure_scan_affected_users',
							'type'        => 'info_text',
							'label'       => __( 'Scanner', 'ultimate-member' ),
							'value'       => $scanner_content,
							'description' => __( 'Scan your site to check for vulnerabilities prior to Ultimate Member version 2.6.7 and get recommendations to secure your site.', 'ultimate-member' ),
						),
						array(
							'id'               => 'banned_capabilities',
							'type'             => 'multi_checkbox',
							'multi'            => true,
							'columns'          => 2,
							'options_disabled' => $default_locked_cap_options,
							'options'          => $banned_admin_capabilities_options,
							'value'            => UM()->options()->get( 'banned_capabilities' ) ? array_keys( UM()->options()->get( 'banned_capabilities' ) ) : array_keys( $default_locked_cap_options ),
							'label'            => __( 'Banned Administrative Capabilities', 'ultimate-member' ),
							'description'      => __( 'All the above are default Administrator & Super Admin capabilities. When someone tries to inject capabilities to the Account, Profile & Register forms submission, it will be flagged with this option. The <strong>manage_options</strong>, <strong>promote_users</strong> &amp; <strong>level_10</strong> capabilities are locked to ensure no users will be created with these capabilities.', 'ultimate-member' ),
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
						array(
							'id'          => 'force_reset_passwords',
							'type'        => 'info_text',
							'label'       => __( 'Expire All Users Sessions', 'ultimate-member' ),
							'value'       => '<a href="' . admin_url( '?um_secure_expire_all_sessions=1&_wpnonce=' . esc_attr( $nonce ) ) . '" class="button">Logout Users(' . esc_attr( $count_users['total_users'] ) . ') </a>',
							'description' => __( 'This will logout all users on your site and forces them to reset passwords <br/>when <strong>"Display Login form notice to reset passwords" is enabled/checked.</strong>', 'ultimate-member' ),
						),
						array(
							'id'          => 'secure_notify_admins_banned_accounts',
							'type'        => 'checkbox',
							'label'       => __( 'Notify Administrators', 'ultimate-member' ),
							'description' => __( 'When enabled, All administrators will be notified when someone has suspicious activities in the Account, Profile & Register forms.', 'ultimate-member' ),
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
					),
				),
			);

			return $settings;
		}

		/**
		 * Block all UM Register form submissions.
		 *
		 * @param array $args Form settings.
		 * @since 2.6.8
		 */
		public function block_register_forms( $args ) {
			if ( UM()->options()->get( 'lock_register_forms' ) ) {
				$login_url = add_query_arg( 'notice', 'maintanance', um_get_core_page( 'login' ) );
				nocache_headers();
				wp_safe_redirect( $login_url );
				exit;
			}
		}

		/**
		 * Validate when user has expired password
		 *
		 * @param array $submitted_data
		 * @since 2.6.8
		 */
		public function login_validate_expired_pass( $submitted_data ) {

			if ( UM()->options()->get( 'display_login_form_notice' ) ) {
				$has_expired = get_user_meta( um_user( 'ID' ), 'um_secure_has_reset_password', true );
				if ( ! $has_expired ) {
					$login_url = add_query_arg( 'notice', 'expired_password', um_get_core_page( 'login' ) );
					wp_safe_redirect( $login_url );
					exit;
				}
			}
		}

		/**
		 * Prevent users from using Old Passwords on Password Reset form
		 *
		 * @param object $errors
		 * @param object $user
		 * @since 2.6.8
		 */
		public function avoid_old_password( $errors, $user ) {
			$wp_hasher = new \PasswordHash( 8, true );

			if ( isset( $_REQUEST['user_password'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$new_user_pass = $_REQUEST['user_password']; // phpcs:ignore WordPress.Security.NonceVerification
				if ( $wp_hasher->CheckPassword( $new_user_pass, $user->data->user_pass ) ) {
					UM()->form()->add_error( 'user_password', __( 'Your new password cannot be same as old password.', 'ultimate-member' ) );
					$errors->add( 'block_old_password', __( 'Your new password cannot be same as old password.', 'ultimate-member' ) );
				} else {
					update_user_meta( $user->data->ID, 'um_secure_has_reset_password', true );
					update_user_meta( $user->data->ID, 'um_secure_has_reset_password__timestamp', current_time( 'mysql' ) );
				}
			}
		}

		/**
		 * Secure user capabilities and revoke administrative ones
		 * @since 2.6.8
		 */
		public function secure_user_capabilities( $user_id ) {
			global $wpdb;
			// Fetch the WP_User object of our user.
			um_fetch_user( $user_id );
			$user            = new WP_User( $user_id );
			$has_admin_cap   = false;
			$arr_banned_caps = array();

			if ( UM()->options()->get( 'banned_capabilities' ) ) {
				$arr_banned_caps = array_keys( UM()->options()->get( 'banned_capabilities' ) );
			}

			// Add locked administratrive capabilities
			$arr_banned_caps[] = 'manage_options';
			$arr_banned_caps[] = 'promote_users';
			$arr_banned_caps[] = 'level_10';

			foreach ( $arr_banned_caps as $i => $cap ) {
				/**
				 * When there's at least one administrator cap added to the user,
				 * immediately revoke caps and mark as rejected.
				 */
				if ( $user->has_cap( $cap ) ) {
					$has_admin_cap = true;
					$this->revoke_caps( $user );
					break;
				}
			}

			/**
			 * Double-check if *_user_level has been modified with the highest level
			 * when user has no administrative capabilities.
			 */
			$user_level = um_user( $wpdb->get_blog_prefix() . 'user_level' );
			if ( ! empty( $user_level ) ) {
				if ( 10 === $user_level ) {
					$this->revoke_caps( $user );
					$has_admin_cap = true;
				}
			}

			if ( $has_admin_cap ) {
				/**
				 * Notify Administrators Immediately
				 */
				if ( UM()->options()->get( 'secure_notify_admins_banned_accounts' ) ) {
					$interval = UM()->options()->get( 'secure_notify_admins_banned_accounts__interval' );
					if ( 'instant' === $interval ) {
						$this->send_email( array( $user->get( 'ID' ) ) );
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

				return true;

			}

			return false;
		}

		/**
		 * Revoke Caps & Mark rejected as suspicious
		 *
		 * @param string $cap Capability slug
		 * @param object $user \WP_User
		 *
		 * @since 2.6.8
		 */
		public function revoke_caps( $um_user_query ) {

			// Detect browser.
			if ( current_user_can( 'manage_options' ) ) {
				$browser = 'Ultimate Member Scanner';
			} else {

				if ( ! class_exists( '\Browser' ) ) {
					require_once um_path . 'includes/lib/browser.php';
				}

				$browser = new \Browser();
			}
			// Capture details.
			$captured = array(
				'capabilities'   => $um_user_query->allcaps,
				'submitted'      => wp_doing_ajax() ? '' : UM()->form()->post_form,
				'roles'          => $um_user_query->roles,
				'user_browser'   => $browser,
				'account_status' => get_user_meta( $um_user_query->get( 'ID' ), 'account_status', true ),
			);

			update_user_meta( $um_user_query->get( 'ID' ), 'um_user_blocked__metadata', $captured );

			$um_user_query->remove_all_caps();
			$um_user_query->update_user_level_from_caps();
			um_fetch_user( $um_user_query->get( 'ID' ) );
			if ( is_user_logged_in() ) {
				UM()->user()->set_status( 'inactive' );
			} else {
				UM()->user()->set_status( 'rejected' );
			}
			um_reset_user();
			update_user_meta( $um_user_query->get( 'ID' ), 'um_user_blocked', 'suspicious_activity' );
			update_user_meta( $um_user_query->get( 'ID' ), 'um_user_blocked__datetime', current_time( 'mysql' ) );
		}

		/**
		 * Append blocked status to the `account_status` column rows
		 *
		 * @param string $val Default column row value
		 * @param string $column_name Current column name
		 * @param integer $user_id   User ID in loop
		 *
		 * @since 2.6.8
		 *
		 * @return string $val
		 */
		public function manage_users_custom_column( $val, $column_name, $user_id ) {
			if ( 'account_status' === $column_name ) {
				um_fetch_user( $user_id );
				$is_blocked     = um_user( 'um_user_blocked' );
				$account_status = um_user( 'account_status' );
				if ( ! empty( $is_blocked ) && in_array( $account_status, array( 'rejected', 'inactive' ), true ) ) {
					$datetime            = um_user( 'um_user_blocked__datetime' );
					$val                 = $val . '<div><small>' . __( 'Blocked Due to Suspicious Activity', 'ultimate-member' ) . '</small></div>';
					$nonce               = wp_create_nonce( 'um-security-restore-account-nonce-' . $user_id );
					$restore_account_url = admin_url( 'users.php?user_id=' . $user_id . '&um_secure_restore_account=1&_wpnonce=' . $nonce );
					$action              = ' &#183; <a href=" ' . esc_attr( $restore_account_url ) . ' " onclick=\'return confirm("' . __( 'Are you sure that you want to restore this account after getting flagged for suspicious activity?', 'utimate-member' ) . '");\'><small>' . __( 'Restore Account', 'ultimate-member' ) . '</small></a>';
					if ( ! empty( $datetime ) ) {
						$val = $val . '<div><small>' . human_time_diff( strtotime( $datetime ), strtotime( current_time( 'mysql' ) ) ) . ' ' . __( 'ago', 'ultimate-member' ) . '</small>' . $action . '</div>';
					}
				}
				um_reset_user();
			}
			return $val;
		}

		/**
		 * Register Events
		 *
		 * @since 2.6.8
		 */
		public function schedule_events() {

			if ( UM()->options()->get( 'secure_notify_admins_banned_accounts' ) ) {
				add_action( 'um_secure_notify_administrator_hourly', array( $this, 'notify_administrators_hourly' ) );
				add_action( 'um_secure_notify_administrator_daily', array( $this, 'notify_administrators_daily' ) );

				if ( ! wp_next_scheduled( 'um_secure_notify_administrator_hourly' ) ) {
					wp_schedule_event( current_time( 'mysql' ), 'hourly', 'um_secure_notify_administrator_hourly' );
				}

				if ( ! wp_next_scheduled( 'um_secure_notify_administrator_daily' ) ) {
					wp_schedule_event( current_time( 'mysql' ), 'daily', 'um_secure_notify_administrator_daily' );
				}
			}
		}

		/**
		 * Notify Administrators hourly - Suspicious activities in an hour
		 *
		 * @since 2.6.8
		 */
		public function notify_administrators_hourly() {

			$interval = UM()->options()->get( 'secure_notify_admins_banned_accounts__interval' );
			if ( 'hourly' === $interval ) {
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

		}

		/**
		 * Notify Administrators daily - Today's suspicious activity
		 *
		 * @since 2.6.8
		 */
		public function notify_administrators_daily() {

			$interval = UM()->options()->get( 'secure_notify_admins_banned_accounts__interval' );
			if ( 'daily' === $interval ) {
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

		public function admin_notice() {
			if ( get_transient( 'um_secure_restore_account_notice_success' ) ) { ?>
				<div class="updated notice is-dismissible">
					<p>
					<?php
						esc_html_e( 'Account has been succesfully restored.', 'ultimate-member' );
					?>
					</p>
				</div>
				<?php
				// Delete transient, only display this notice once.
				delete_transient( 'um_secure_restore_account_notice_success' );
			}
			// phpcs:disable WordPress.Security.NonceVerification
			if ( ! get_transient( 'um_secure_first_time_admin_notice' ) && ( ! isset( $_REQUEST['page'] ) || 'um_options' !== $_REQUEST['page'] ) ) {
				?>
				<div class="warning notice">
					<p>
						<strong> <?php esc_html_e( 'Important Update', 'ultimate-member' ); ?> </strong><br/>
					<?php
						esc_html_e( 'Ultimate Member has a new additional feature to secure your Ultimate Member forms to prevent attacks from injecting accounts with administrative roles &amp; capabilities.', 'ultimate-member' );
					?>
					</p>
					<p>
					<a class="button button-primary" href="<?php echo esc_attr( admin_url( 'admin.php?page=um_options&tab=secure&um_dismiss_security_first_time_notice=1' ) ); ?>"><?php esc_html_e( 'Manage Security Settings', 'ultimate-member' ); ?></a>
					<a class="button" href="https://docs.ultimatemember.com/article/1869-security-feature"><?php esc_html_e( 'Read the documentation', 'ultimate-member' ); ?></a>
					</p>
				</div>
				<?php
			// phpcs:enable WordPress.Security.NonceVerification
			}

			if ( get_transient( 'um_secure_locked_register_forms_admin_notice' ) ) {
				?>
				<div class="updated notice is-dismissible">
					<p>
					<?php
						esc_html_e( 'Your Register forms are now locked. You can unlock them in Ultimate Member > Settings > Secure > Lock All Register Forms.', 'ultimate-member' );
					?>
					</p>
				</div>
				<?php
				// Delete transient, only display this notice once.
				delete_transient( 'um_secure_locked_register_forms_admin_notice' );
			}

			if ( get_transient( 'um_secure_sessions_destroyed_admin_notice' ) ) {
				?>
				<div class="updated notice is-dismissible">
					<p>
					<?php
						esc_html_e( 'Sessions have been succesfully destroyed.', 'ultimate-member' );
					?>
					</p>
				</div>
				<?php
				// Delete transient, only display this notice once.
				delete_transient( 'um_secure_sessions_destroyed_admin_notice' );
			}

			if ( get_transient( 'um_secure_enable_reset_password_admin_notice' ) ) {
				?>
				<div class="updated notice is-dismissible">
					<p>
					<?php
						esc_html_e( 'Mandatory password changes has been enabled.', 'ultimate-member' );
					?>
					</p>
				</div>
				<?php
				// Delete transient, only display this notice once.
				delete_transient( 'um_secure_enable_reset_password_admin_notice' );
			}

		}

		/**
		 * Scan affected users
		 */
		public function ajax_scanner() {

			if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'um-admin-nonce' ) || ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_attr_e( 'Security Check', 'ultimate-member' ) );
			}

			$last_scanned_capability = $_REQUEST['last_scanned_capability'];

			if ( empty( $last_scanned_capability ) ) {
				delete_option( 'um_secure_scanned_details' );
				update_option( 'um_secure_scan_status', 'started' );
				update_option( 'um_secure_last_time_scanned', current_time( 'mysql' ) );
			}

			$scan_details = get_option( 'um_secure_scanned_details' );

			$arr_banned_caps = array();
			if ( UM()->options()->get( 'banned_capabilities' ) ) {
				$arr_banned_caps = array_keys( UM()->options()->get( 'banned_capabilities' ) );
			} else {
				$arr_banned_caps = UM()->secure()->banned_admin_capabilities;
			}

			$arr_banned_caps = array_merge( $arr_banned_caps, $this->banned_locked_capabilities );

			$proceed       = false;
			$completed     = false;
			$scanned_cap   = '';
			$user_affected = false;
			$count_users   = 0;

			$last_element = end( $arr_banned_caps );

			foreach ( $arr_banned_caps as $k => $cap ) {

				if ( empty( $last_scanned_capability ) ) {
					$proceed = true;
				} else {
					if ( $last_scanned_capability === $cap ) { // if this was the last capability, skip this and proceed the next loop.
						$proceed = true;
						continue;
					}
				}

				if ( ! $proceed ) {
					continue;
				}

				$args          = array(
					'capability'   => $cap,
					'role__not_in' => array( 'administrator' ),
					'fields'       => 'ids',
				);
				$wp_user_query = new \WP_User_Query( $args );
				$count_users   = $wp_user_query->get_total();
				if ( $count_users <= 0 ) {
					$message = '<strong>`' . $cap . '`</strong> <span style="color:green">' . __( ' is safe ' ) . '</span>';
				} else {
					$user_affected = true;
					$message       = '<strong>`' . $cap . '`</strong> <span style="color:red">' . sprintf( /* translators: has affected %d user account */ _n( 'has affected %d user account.', ' has affected %d user accounts.', $count_users, 'ultimate-member' ), $count_users ) . '</span>';
				}

				if ( $last_element === $cap ) {
					$completed = true;
					update_option( 'um_secure_scan_status', 'completed' );
				}

				$scanned_cap = $cap;

				break;
			}

			if ( $user_affected ) {
				$scan_details['affected_caps'][] = $scanned_cap;

				$scan_details['scanned_caps'][ $scanned_cap ] = array(
					'total_affected_users' => $count_users,
					'users'                => $wp_user_query->get_results(),
				);

				if ( ! isset( $scan_details['scanned_caps']['total_all_cap_flagged'] ) ) {
					$scan_details['scanned_caps']['total_all_cap_flagged'] = 1;
				} else {
					++$scan_details['scanned_caps']['total_all_cap_flagged'];
				}
			}

			if ( ! isset( $scan_details['scanned_caps']['total_all_affected_users'] ) ) {
				$scan_details['scanned_caps']['total_all_affected_users'] = absint( $count_users );
			} else {
				$scan_details['scanned_caps']['total_all_affected_users'] = absint( $scan_details['scanned_caps']['total_all_affected_users'] ) + absint( $count_users );
			}

			update_option( 'um_secure_scanned_details', $scan_details );

			return wp_send_json_success(
				array(
					'last_scanned_capability' => $cap,
					'message'                 => $message,
					'completed'               => $completed,
					'recommendations'         => $completed ? $this->scan_recommendations() : '',
				)
			);
		}

		/**
		 * Recommendations after the scan completed.
		 */
		public function scan_recommendations() {
			global $wp_roles, $wpdb;
			$br      = '</br>';
			$check   = '<span class="dashicons dashicons-yes-alt" style="color:green"></span>';
			$flag    = '<span class="dashicons dashicons-flag" style="color:red"></span>';
			$warning = '<span class="dashicons dashicons-warning" style="color:red"></span>';

			$all_plugins    = get_plugins();
			$active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );

			$content = '-----' . $br . $br;

			$suspicious_accounts = new WP_User_Query(
				array(
					'relation'   => 'AND',
					'number'     => -1,
					'meta_query' => array(
						'relation' => 'OR',
						array(
							'key'     => 'submitted',
							'value'   => sprintf( ':"%s";', 'wp_capabilities' ),
							'compare' => 'LIKE',
						),
						array(
							'key'     => 'submitted',
							'value'   => sprintf( ':"%s";', 'wp_user_level' ),
							'compare' => 'LIKE',
						),
						array(
							'key'     => 'submitted',
							'value'   => sprintf( ':"%s";', $wpdb->prefix . '_capabilities' ),
							'compare' => 'LIKE',
						),
						array(
							'key'     => 'submitted',
							'value'   => sprintf( ':"%s";', $wpdb->prefix . '_user_level' ),
							'compare' => 'LIKE',
						),
						array(
							'key'     => 'submitted',
							'value'   => sprintf( '.*%s";', '_capabilities' ),
							'compare' => 'REGEXP',
						),
						array(
							'key'     => 'submitted',
							'value'   => sprintf( '.*%s";', '_user_level' ),
							'compare' => 'LIKE',
						),
					),
				)
			);

			$suspicious_accounts_count = $suspicious_accounts->get_total();
			$susp_accounts             = $suspicious_accounts->get_results();

			/**
			 * Disable and Kickout Suspicious accounts.
			 */
			if ( $suspicious_accounts_count > 0 ) {
				$arr_might_lookout_accounts = array();
				$arr_dates_registered       = array();
				$arr_suspected_accounts     = array();
				if ( ! empty( $susp_accounts ) ) {
					foreach ( $susp_accounts as $user ) {

						$arr_suspected_accounts[] = $user->ID;
						$arr_dates_registered[]   = strtotime( $user->user_registered );

						if ( $user->__get( 'um_user_blocked' ) ) {
							continue;
						}

						$this->revoke_caps( $user );
						// Get an instance of WP_User_Meta_Session_Tokens
						$sessions_manager = \WP_Session_Tokens::get_instance( $user->ID );
						// Remove all the session data for all users.
						$sessions_manager->destroy_all();
					}
				}

				$date_query  = array();
				$oldest_date = min( $arr_dates_registered );
				$newest_date = max( $arr_dates_registered );

				$might_affected_users = new WP_User_Query(
					array(
						'number'     => -1,
						'relation'   => 'AND',
						'date_query' => array(
							'after' => human_time_diff( $oldest_date, strtotime( current_time( 'mysql' ) ) ) . ' ago',
						),
					)
				);
			}

			$content .= '<span style="font-size:19px;padding-bottom:10px;display:block;border-bottom:1px solid #ccc;" id="um-secure-scanner-complete">' . ( $suspicious_accounts_count > 0 ? $warning : $check ) . __( 'Scan Complete.', 'ultimate-member' ) . '</span>';

			$option       = get_option( 'um_secure_scanned_details', $susp_accounts );
			$scan_details = $option['scanned_caps'];

			if ( $suspicious_accounts_count > 0 ) {
				update_option( 'um_secure_found_suspicious_accounts', true );
				$content .= $br . $flag . '<strong>Suspcious Accounts Detected!</strong> <br/>';
				$content .= $br . __( 'We have found ', 'ultimate-member' ) . '<strong style="color:red;">' . /* translators: %s suspcious account */ sprintf( _n( '%s suspcious account', '%s suspcious accounts', $suspicious_accounts_count, 'ultimate-member' ), $suspicious_accounts_count ) . '</strong> ' . __( 'created on your site via Ultimate Member Forms.', 'ultimate-member' );
				$content .= $br . __( 'We\'ve temporarily disabled the suspcious account(s) for you to <strong>take actions</strong>.', 'ultimate-member' );

				if ( $might_affected_users->get_total() > 0 ) {
					$od = gmdate( 'F m, Y', $oldest_date );
					$nd = gmdate( 'F m, Y', $newest_date );
					if ( $od !== $nd ) {
						$date_registered = $od . ' to ' . $nd;
					} else {
						$date_registered = $od;
					}
					$content .= $br . $br . __( 'Also, We\'ve found ', 'ultimate-member' ) . '<strong style="color:red;">' . /* translators: %s suspcious account */ sprintf( _n( '%s account', '%s accounts', $might_affected_users->get_total(), 'ultimate-member' ), $might_affected_users->get_total() ) . '</strong> ' . sprintf( _n( 'created on %s when the suspicious account was created.', 'created on %s when the suspicious accounts were created.', $suspicious_accounts_count, 'ultimate-member' ), $date_registered );

				}
			} else {
				$content .= $br . '<strong>Suspcious Accounts</strong> <br/>';
				$content .= $br . $check . ' No suspicious accounts found. <br/>';
			}

			$content .= $br . $br . __( 'PLEASE READ OUR <strong>RECOMMENDATIONS</strong> BELOW: ', 'ultimate-member' ) . $br;

			$content .= $br . '<div style="padding:10px; border:1px solid #ccc;"><strong style="color:red">WARNING:</strong> Ensure that you\'ve created a full backup of your site as your restoration point before changing anything on your site with our recommendations.</div>';
			if ( $suspicious_accounts_count > 0 ) {
				$lock_register_forms_url = admin_url( 'admin.php?page=um_options&tab=secure&um_secure_lock_register_forms=1&_wpnonce=' . wp_create_nonce( 'um_secure_lock_register_forms' ) );
				$content                .= $br . '1. Please temporarily lock all your active Register forms. <a href="' . esc_attr( $lock_register_forms_url ) . '" target="_blank">Click here to lock them now.</a> You can unblock the Register forms later. Just go to Ultimate Member > Settings > Secure > uncheck the option "Lock All Register Forms".';
				$content                .= $br . $br;
				$suspicious_accounts_url = admin_url( 'users.php?um_status=inactive' );

				$content .= '2. Review all suspicious accounts and delete them completely. <a href="' . esc_attr( $suspicious_accounts_url ) . '" target="_blank">Click here to review accounts.</a>';
				$content .= $br . $br;

				$nonce                    = wp_create_nonce( 'um-secure-expire-session-nonce' );
				$destroy_all_sessions_url = admin_url( '?um_secure_expire_all_sessions=1&_wpnonce=' . esc_attr( $nonce ) . '&except_me=1' );
				$content                 .= '3. If accounts are suspicious to you, please destroy all user sessions to logout active users on your site. <a href="' . esc_attr( $destroy_all_sessions_url ) . '" target="_blanl">Click here to Destroy Sessions now</a>';

				$content .= $br . $br;
				$content .= '4. Run a complete scan on your site using third-party Security plugins such as <a target="_blank" href="' . esc_attr( admin_url( 'plugin-install.php?s=Jetpack%2520Protect%2520WP%2520Scan&tab=search&type=term' ) ) . '">WPScan/Jetpack Protect or WordFence Security</a>.';

				$content                .= $br . $br;
				$nonce                   = wp_create_nonce( 'um-secure-enable-reset-pass-nonce' );
				$reset_pass_sessions_url = admin_url( '?um_secure_enable_reset_password=1&_wpnonce=' . esc_attr( $nonce ) . '&except_me=1' );

				$content .= '5. Force users to Reset their Passwords. <a target="_blank" href="' . esc_attr( $reset_pass_sessions_url ) . '">Click here to enable this option</a>. When this option is enabled, users will be asked to reset their passwords(one-time) on the next login in the UM Login form.';
				$content .= $br . $br;

				$content .= '6. Once your site is secured, please create or enable Daily Backups of your server/site. You can contact your hosting provider to assist you on this matter.';
				$content .= $br . $br;

				$content .= '👇 Read more Recommendations below.';
				$content .= $br;
			}

			if ( get_option( 'users_can_register' ) ) {
				$content .= $br . $flag . '<strong>Default WP Register Form is Enabled</strong>';
				$content .= $br . 'The default WordPress Register form is enabled. If you\'re getting Spam User Registrations, we recommend that you enable a Challenge-Response plugin such as our <a href="https://wordpress.org/plugins/um-recaptcha/" target="_blank">Ultimate Member - ReCaptcha</a> extension.';
				$content .= $br;
			}

			$content .= $br . '<strong>Block Disposable Email Addresses/Domains</strong>';
			if ( empty( UM()->options()->get( 'blocked_emails' ) ) ) {
				$content .= $br . $flag . 'You are not blocking email addresses or disposable email domains that are mostly used for Spam Account Registrations. You can get the list of disposable email domains from <a href="https://github.com/champsupertramp/disposable-email-domains/blob/master/um_disposable_email_blocklist.txt" target="_blank">this repository</a> and then add them to <a target="_blank" href="' . esc_attr( 'admin.php?page=um_options&tab=access&section=other' ) . '">Blocked Email Addresses</a> options.';
				$content .= $br;
			} else {
				$content .= $br . 'The default WordPress Register form is enabled. If you\'re getting Spam User Registrations, we recommend that you enable a Challenge-Response plugin such as our <a href="https://wordpress.org/plugins/um-recaptcha/" target="_blank">Ultimate Member - ReCaptcha</a> extension.';
				$content .= $br;
			}

			$content .= $br . '<strong>Manage User Roles & Capabilities</strong> <br/>';
			if ( absint( $scan_details['total_all_affected_users'] ) > 0 ) {
				$count_flagged_caps = $scan_details['total_all_cap_flagged'];
				$count_users        = $scan_details['total_all_affected_users'];
				$affected_caps      = $option['affected_caps'];
				$affected_roles     = array();

				$all_roles      = $wp_roles->roles;
				$editable_roles = apply_filters( 'editable_roles', $all_roles );
				foreach ( $affected_caps as $cap ) {
					foreach ( $editable_roles as $role_key => $role ) {
						if ( in_array( $cap, array_keys( $role['capabilities'] ), true ) ) {
							$affected_roles[ $role_key ] = $role['name'];
						}
					}
				}
				$content .= $br . $flag . 'We have found ' . sprintf( /* translators: */ _n( ' %d user account', ' %d user accounts ', $count_users, 'ultimate-member' ), $count_users );
				$content .= sprintf( /* translators: */ _n( ' affected by %d capability selected in the Banned Administrative Capabilities.', ' affected by one of the %d capabilities selected in the Banned Administrative Capabilities.', $count_flagged_caps, 'ultimate-member' ), $count_flagged_caps );

				$content .= $br . '- ' . implode( '<br/> - ', $affected_caps );

				$content .= $br . $br . 'The flagged capabilities are related to the following roles: ' . $br . ' - ' . implode( '<br/> - ', array_values( $affected_roles ) );

				$content .= $br . $br . 'The affected user accounts will be flagged as suspicious when they update their Profile/Account. If you are not using these capabilities, you may remove them from the roles in the <a target="_blank" href="' . admin_url( 'admin.php?page=um_roles' ) . '">User Role settings</a>. If the roles are not created via Ultimate Member > User Roles, you can use a <a href="' . admin_url( 'plugin-install.php?s=User%2520Role%2520Editor%2520WordPress%2520&tab=search&type=term' ) . '" target="_blank">third-party plugin</a> to modify the role capability.';
				$content .= $br . $br . 'We strongly recommend that you never assign roles with the same capabilities as your administrators for your members/users and that may allow them to access the admin-side features and functionalities of your WordPress site.';
			} else {
				$content .= $check . 'Roles & Capabilities are all secured. No users are using the same capabilities as your administrators.';
			}

			$content .= $br . $br . '<strong>Require Strong Passwords</strong>';
			if ( ! UM()->options()->get( 'require_strongpass' ) ) {
				$content .= $br . $flag . 'We recommend that you enable and require "Strong Password" feature for all the Register, Reset Password & Account forms.';
				$content .= $br . ' <a href="' . admin_url( 'admin.php?page=um_options&section=users' ) . '" target="_blank" >Click here to enable.</a>';
			} else {
				$content .= $br . $check . 'Your forms are already configured to require of using strong passwords.';
			}

			$content .= $br . $br . '<strong>Secure Site\'s Connection</strong>';
			if ( ! isset( $_SERVER['HTTPS'] ) || 'on' !== $_SERVER['HTTPS'] ) {
				$content .= $br . $flag . 'Your site cannot provide a secure connection. Please contact your hosting provider to enable SSL certifications on your server.';
			}

			$content .= $br . $br . '<strong>Install Challenge-Response plugin to Login & Register Forms</strong>';
			if ( ! array_key_exists( 'um-recaptcha/um-recaptcha.php', $all_plugins ) ) {
				if ( ! isset( $_SERVER['HTTPS'] ) || 'on' !== $_SERVER['HTTPS'] ) {
					$content .= $br . $flag . 'We recommend that you install and enable ReCaptcha to Login & Register forms.';
				}
			} else {
				if ( in_array( 'um-recaptcha/um-recaptcha.php', $active_plugins, true ) ) {
					$content .= $br . $check . 'Ultimate Member ReCaptcha is actived.';
					$um_forms = get_posts( 'post_type=um_form&numberposts=-1&fields=ids' );
					foreach ( $um_forms as $fid ) {
						switch ( get_post_meta( $fid, '_um_mode', true ) ) {
							case 'register':
								$has_captcha = absint( get_post_meta( $fid, '_um_register_g_recaptcha_status', true ) );
								$content    .= $br . '&nbsp;&nbsp;- Register: <a target="_blank" href="' . get_edit_post_link( $fid ) . '">' . get_the_title( $fid ) . '</a> recaptcha ' . ( 1 === $has_captcha ? ' is <strong>enabled</strong> ' . $check : 'is <strong>disabled</strong> ' . $flag );
								break;
							case 'login':
								$has_captcha = absint( get_post_meta( $fid, '_um_login_g_recaptcha_status', true ) );
								$content    .= $br . '&nbsp;&nbsp;- Login: <a target="_blank" href="' . get_edit_post_link( $fid ) . '">' . get_the_title( $fid ) . '</a> recaptcha ' . ( 1 === $has_captcha ? ' is <strong>enabled</strong> ' . $check : 'is <strong>disabled</strong> ' . $flag );
								break;
						}
					}
					$reset_pass_form = absint( UM()->options()->get( 'g_recaptcha_password_reset' ) );
					$content        .= $br . '&nbsp;&nbsp;- Reset Password Form\'s recaptcha ' . ( 1 === $reset_pass_form ? ' is <strong>enabled</strong> ' . $check : 'is <strong>disabled</strong> ' . $flag );

				} elseif ( array_key_exists( 'um-recaptcha/um-recaptcha.php', $all_plugins ) ) {
					$content .= $br . $flag . 'Ultimate Member ReCaptcha is installed but not activated.';
				} else {
					$content .= $br . $flag . 'We recommend that you install and enable <a href="https://wordpress.org/plugins/um-recaptcha/" target="_blank">ReCaptcha</a> to Login & Register forms.';
				}
			}

			$update_plugins = get_site_transient( 'update_plugins' );
			$update_themes  = get_site_transient( 'update_themes' );
			$update_wp_core = get_site_transient( 'update_core' );
			global $wp_version;
			$content .= $br . $br . '<strong>Keep Themes & Plugins up to date.</strong>';
			$content .= $br . __( 'It is important that you update your themes/plugins if the theme/plugin creators update is aimed at fixing security, bug and vulnerability issues. It is not a good idea to ignore available updates as this may give hackers an advantage when trying to access your website.', 'ultimate-member' );

			if ( isset( $update_plugins->response ) && ! empty( $update_plugins->response ) ) {
				$content .= $br . $br . $flag . sprintf( /* translators: */ _n( 'There\'s %d plugin that requires an update.', 'There are %d plugins that require updates', count( $update_plugins->response ), 'ultimate-member' ), count( $update_plugins->response ) ) . ' <a target="_blank" href="' . admin_url( 'update-core.php' ) . '">Update Plugins Now</a>';
				foreach ( $update_plugins->response as $plugin_name => $data ) {
					$content .= $br . '&nbsp;&nbsp;- ' . $plugin_name;
				}
			} else {
				$content .= $br . $br . $check . __( 'Plugins are up to date.', 'ultimate-member' );
			}

			if ( isset( $update_themes->response ) && ! empty( $update_themes->response ) ) {
				$content .= $br . $br . $flag . sprintf( /* translators: */ _n( 'There\'s %d theme that requires an update.', 'There are %d themes that require updates', count( $update_plugins->response ), 'ultimate-member' ), count( $update_plugins->response ) ) . ' <a target="_blank" href="' . admin_url( 'update-core.php' ) . '">Update Themes Now</a>';
				foreach ( $update_themes->response as $theme_name => $data ) {
					$content .= $br . '&nbsp;&nbsp;- ' . $theme_name;
				}
			} else {
				$content .= $br . $br . $check . __( 'Themes are up to date.', 'ultimate-member' );
			}

			if ( isset( $update_themes->current ) && $wp_version !== $update_themes->current ) {
				$content .= $br . $br . $flag . __( 'There\'s a new version of WordPress.', 'ultimate-member' ) . '<a target="_blank" href="' . admin_url( 'update-core.php' ) . '">Update WordPress Now</a>';
			} else {
				$content .= $br . $br . $check . __( 'You\'re using the latet version of WordPress', 'ultimate-member' ) . '(' . esc_attr( $wp_version ) . ')';
			}

			$content .= $br . $br . __( 'That\'s all. If you have any recommendation on how to secure your site or have questions, please contact us on our <a href="https://ultimatemember.com/feedback/" target="_blank">feedback page</a>. ', 'ultimate-member' );

			update_option( 'um_secure_scan_result_contenet', $content );

			return $content;
		}

	}

}
