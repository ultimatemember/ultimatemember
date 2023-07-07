<?php
namespace um\ajax;

use WP_Session_Tokens;
use WP_User_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Secure
 *
 * @package um\ajax
 *
 * @since 2.6.8
 */
class Secure {

	/**
	 * Secure constructor.
	 *
	 * @since 2.6.8
	 */
	public function __construct() {
		add_action( 'wp_ajax_um_secure_scan_affected_users', array( $this, 'ajax_scanner' ) );
	}

	/**
	 * Scan affected users
	 */
	public function ajax_scanner() {
		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'um-admin-nonce' ) || ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_attr__( 'Security Check', 'ultimate-member' ) );
		}

		$last_scanned_capability = sanitize_key( $_REQUEST['last_scanned_capability'] );

		if ( empty( $last_scanned_capability ) ) {
			delete_option( 'um_secure_scanned_details' );
			update_option( 'um_secure_scan_status', 'started' );
			update_option( 'um_secure_last_time_scanned', current_time( 'mysql' ) );
		}

		$scan_details = get_option( 'um_secure_scanned_details' );

		if ( ! empty( $_REQUEST['capabilities'] ) ) {
			$request_capabilities = is_array( $_REQUEST['capabilities'] ) ? $_REQUEST['capabilities'] : array( $_REQUEST['capabilities'] );
			$arr_banned_caps      = array_map( 'sanitize_key', $request_capabilities );
		} else {
			$arr_banned_caps = UM()->options()->get( 'banned_capabilities' );
		}

		$proceed       = false;
		$completed     = false;
		$message       = '';
		$scanned_cap   = '';
		$user_affected = false;
		$count_users   = 0;

		$last_element = end( $arr_banned_caps );

		foreach ( $arr_banned_caps as $cap ) {

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
			$wp_user_query = new WP_User_Query( $args );
			$count_users   = $wp_user_query->get_total();
			if ( $count_users <= 0 ) {
				$message = '<strong>`' . esc_html( $cap ) . '`</strong> <span style="color:green">' . esc_html__( ' is safe ' ) . '</span>';
			} else {
				$user_affected = true;
				$message       = '<strong>`' . esc_html( $cap ) . '`</strong> <span style="color:red">' . sprintf( /* translators: has affected %d user account */ _n( 'has affected %d user account.', ' has affected %d user accounts.', $count_users, 'ultimate-member' ), $count_users ) . '</span>';
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

		wp_send_json_success(
			array(
				'last_scanned_capability' => $scanned_cap,
				'message'                 => $message,
				'completed'               => $completed,
				'recommendations'         => $completed ? wp_kses( $this->scan_recommendations(), UM()->get_allowed_html( 'templates' ) ) : '',
			)
		);
	}

	/**
	 * Recommendations after the scan completed.
	 *
	 * @return string
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
						'compare' => 'REGEXP',
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
			$arr_dates_registered   = array();
			$arr_suspected_accounts = array();
			if ( ! empty( $susp_accounts ) ) {
				foreach ( $susp_accounts as $user ) {

					$arr_suspected_accounts[] = $user->ID;
					$arr_dates_registered[]   = strtotime( $user->user_registered );

					if ( $user->__get( 'um_user_blocked' ) ) {
						continue;
					}

					UM()->common()->secure()->revoke_caps( $user );
					// Get an instance of WP_User_Meta_Session_Tokens
					$sessions_manager = WP_Session_Tokens::get_instance( $user->ID );
					// Remove all the session data for all users.
					$sessions_manager->destroy_all();
				}
			}

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

		/**
		 * Get Site Health's Total issues to resolve
		 */
		$get_issues = get_transient( 'health-check-site-status-result' );

		$issue_counts = array();

		if ( false !== $get_issues ) {
			$issue_counts = json_decode( $get_issues, true );
		}

		if ( ! is_array( $issue_counts ) || ! $issue_counts ) {
			$issue_counts = array(
				'good'        => 0,
				'recommended' => 0,
				'critical'    => 0,
			);
		}

		$site_health_issues_total = $issue_counts['recommended'] + $issue_counts['critical'];

		$content .= '<div style="font-size:19px;padding-bottom:10px;width:100%; display:block;border-bottom:1px solid #ccc;" id="um-secure-scanner-complete">' . ( $suspicious_accounts_count > 0 ? $warning : $check ) . __( 'Scan Complete.', 'ultimate-member' ) . '</div>';

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

			$content .= '👇 MORE RECOMMENDATIONS BELOW.';
		}

		$content .= $br . $br . '<strong>Review & Resolve Issues with Site Health Check tool</strong>';
		$content .= $br . __( 'Site Health is a tool in WordPress that helps you monitor how your site is doing. It shows critical information about your WordPress configuration and items that require your attention.', 'ultimate-member' );
		if ( $site_health_issues_total > 0 ) {
			$content .= $br . $flag . sprintf( /* translators: %d issue in the Site Health status  */ _n( 'There\s %d issue in the Site Health status', 'There are %d issues in the Site Health status', $site_health_issues_total ), $site_health_issues_total );
			$content .= ': <a target="_blank" href="' . admin_url( 'site-health.php' ) . '">Review Site Health Status</a>';
		} else {
			$content .= $br . $check . __( 'There are no issues found in the Site Health status', 'ultimate-member' );
		}

		$content .= $br . $br . '<strong>Default WP Register Form</strong>';
		if ( get_option( 'users_can_register' ) ) {
			$content .= $br . $flag . 'The default WordPress Register form is enabled. If you\'re getting Spam User Registrations, we recommend that you enable a Challenge-Response plugin such as our <a href="https://wordpress.org/plugins/um-recaptcha/" target="_blank">Ultimate Member - ReCaptcha</a> extension.';
			$content .= $br;
		} else {
			$content .= $br . $check . 'The default WordPress Register form is disabled.' . $br;
		}

		$content .= $br . '<strong>Block Disposable Email Addresses/Domains</strong>';
		if ( empty( UM()->options()->get( 'blocked_emails' ) ) ) {
			$content .= $br . $flag . 'You are not blocking email addresses or disposable email domains that are mostly used for Spam Account Registrations. You can get the list of disposable email domains from <a href="https://github.com/champsupertramp/disposable-email-domains/blob/master/um_disposable_email_blocklist.txt" target="_blank">this repository</a> and then add them to <a target="_blank" href="' . esc_attr( 'admin.php?page=um_options&tab=access&section=other' ) . '">Blocked Email Addresses</a> options.';
			$content .= $br;
		} else {
			$content .= $br . 'The default WordPress Register form is enabled. If you\'re getting Spam User Registrations, we recommend that you enable a Challenge-Response plugin such as our <a href="https://wordpress.org/plugins/um-recaptcha/" target="_blank">Ultimate Member - ReCaptcha</a> extension.';
			$content .= $br;
		}

		$content .= $br . '<strong>Manage User Roles & Capabilities</strong>';
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
			$content .= $br . $check . 'Roles & Capabilities are all secured. No users are using the same capabilities as your administrators.';
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
		} else {
			$content .= $br . $check . 'Your site provides a secure connection with SSL.';
		}

		$content .= $br . $br . '<strong>Install Challenge-Response plugin to Login & Register Forms</strong>';
		if ( ! array_key_exists( 'um-recaptcha/um-recaptcha.php', $all_plugins ) ) {
			$content .= $br . $flag . 'We recommend that you install and enable <a href="https://wordpress.org/plugins/um-recaptcha/" target="_blank">ReCaptcha</a> to your Reset Password, Login & Register forms.';
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
			$content .= $br . $br . $check . __( 'You\'re using the latest version of WordPress', 'ultimate-member' ) . '(' . esc_attr( $wp_version ) . ')';
		}

		$content .= $br . $br . __( 'That\'s all. If you have any recommendation on how to secure your site or have questions, please contact us on our <a href="https://ultimatemember.com/feedback/" target="_blank">feedback page</a>. ', 'ultimate-member' );

		update_option( 'um_secure_scan_result_content', $content );

		return $content;
	}
}
