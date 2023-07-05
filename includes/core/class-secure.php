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
					'manage_options',
					'promote_users',
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
				)
			);

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
				// Remove all the session data for all users.
				$sessions_manager->drop_sessions();
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

				set_transient( 'um_secure_restore_account_notice_success', 1, 5 );

				wp_safe_redirect( wp_get_referer() );
				exit;

			}

			if ( isset( $_REQUEST['um_dismiss_security_first_time_notice'] ) ) {
				set_transient( 'um_secure_first_time_admin_notice', 1, 0 );
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
			$default_locked_cap_options        = array( 'manage_options', 'promote_users', 'level_10' );
			foreach ( $this->banned_admin_capabilities as $i => $cap ) {
				if ( in_array( $cap, $default_locked_cap_options, true ) ) {
					continue;
				}
				$banned_admin_capabilities_options[ $cap ] = $cap;
			}

			$settings['secure']['sections'] =
			array(
				'' =>
				array(
					'title'  => __( 'Secure Ultimate Member', 'ultimate-member' ),
					'fields' => array(
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
							'id'          => 'banned_capabilities',
							'type'        => 'multi_checkbox',
							'multi'       => true,
							'columns'     => 2,
							'options'     => $banned_admin_capabilities_options,
							'value'       => UM()->options()->get( 'banned_capabilities' ) ? array_keys( UM()->options()->get( 'banned_capabilities' ) ) : array_keys( $default_locked_cap_options ),
							'label'       => __( 'Banned Administrative Capabilities', 'ultimate-member' ),
							'description' => __( 'All the above are default Administrator & Super Admin capabilities. When someone tries to inject capabilities to the Account, Profile & Register forms submission, it will be flagged with this option. The <strong>manage_options</strong>, <strong>promote_users</strong> &amp; <strong>level_10</strong> capabilities are locked to ensure no users will be created with these capabilities.', 'ultimate-member' ),
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
		public function revoke_caps( $user ) {

			if ( ! class_exists( '\Browser' ) ) {
				require_once um_path . 'includes/lib/browser.php';
			}

			// Detect browser.
			$browser = new \Browser();
			// Capture details.
			$captured = array(
				'capabilities'   => $user->allcaps,
				'submitted'      => UM()->form()->post_form,
				'roles'          => $user->roles,
				'user_browser'   => $browser,
				'account_status' => get_user_meta( $user->get( 'ID' ), 'account_status', true ),
			);
			update_user_meta( $user->get( 'ID' ), 'um_user_blocked__metadata', $captured );

			$user->remove_all_caps();
			if ( is_user_logged_in() ) {
				UM()->user()->set_status( 'inactive' );
			} else {
				UM()->user()->set_status( 'rejected' );
			}
			update_user_meta( $user->get( 'ID' ), 'um_user_blocked', 'suspicious_activity' );
			update_user_meta( $user->get( 'ID' ), 'um_user_blocked__datetime', current_time( 'mysql' ) );
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
						esc_html_e( 'Account has been succesfully restored.', 'um-stripe' );
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
						esc_html_e( 'Ultimate Member has a new additional feature to secure your Ultimate Member forms to prevent attacks from injecting accounts with administrative roles &amp; capabilities.', 'um-stripe' );
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
		}

	}

}
