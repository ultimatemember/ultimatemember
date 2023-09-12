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

		$scan_details = get_option( 'um_secure_scanned_details', array() );

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
				// translators: %s capability name
				$message = wp_kses( sprintf( __( '<strong>`%s`</strong> <span style="color:green">is safe.</span>', 'ultimate-member' ), $cap ), UM()->get_allowed_html( 'admin_notice' ) );
			} else {
				$user_affected = true;
				// translators: %1$s capability name, has affected %2$d user account
				$message = wp_kses( sprintf( _n( '<strong>`%1$s`</strong> <span style="color:red">has affected %2$d user account.</span>', '<strong>`%1$s`</strong> <span style="color:red">has affected %2$d user accounts.</span>', $count_users, 'ultimate-member' ), $cap, $count_users ), UM()->get_allowed_html( 'admin_notice' ) );
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
		$um_forms       = get_posts( 'post_type=um_form&numberposts=-1&fields=ids' );

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
		$arr_dates_registered      = array();
		$arr_suspected_accounts    = array();

		/**
		 * Disable and Kick-out Suspicious accounts.
		 */
		if ( $suspicious_accounts_count > 0 ) {
			if ( ! empty( $susp_accounts ) ) {
				foreach ( $susp_accounts as $user ) {

					$arr_suspected_accounts[] = $user->ID;
					$arr_dates_registered[]   = $user->user_registered;

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

			$arr_dates_in_timestamp = array_map( 'strtotime', $arr_dates_registered );

			$oldest_date = min( $arr_dates_in_timestamp );
			$newest_date = max( $arr_dates_in_timestamp );

			$might_affected_users = new WP_User_Query(
				array(
					'number'     => -1,
					'exclude'    => $arr_suspected_accounts,
					'date_query' => array(
						'after'  => gmdate( 'F d, Y', strtotime( '-1 day', $oldest_date ) ),
						'before' => gmdate( 'F d, Y', strtotime( '+1 day', $newest_date ) ),
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
				'recommended' => 0,
				'critical'    => 0,
			);
		}

		$site_health_issues_total = $issue_counts['recommended'] + $issue_counts['critical'];

		$content .= '<div style="font-size:19px;padding-bottom:10px;width:100%; display:block;border-bottom:1px solid #ccc;" id="um-secure-scanner-complete">' . ( $suspicious_accounts_count > 0 ? $warning : $check ) . esc_html__( 'Scan Complete.', 'ultimate-member' ) . '</div>';

		$option       = get_option( 'um_secure_scanned_details', $susp_accounts );
		$scan_details = $option['scanned_caps'];

		if ( $suspicious_accounts_count > 0 ) {
			update_option( 'um_secure_found_suspicious_accounts', true );
			$content .= $br . $flag . '<strong>' . esc_html__( 'Suspicious Accounts Detected!', 'ultimate-member' ) . '</strong> <br/>';
			// translators: %s suspicious account
			$content .= $br . wp_kses( sprintf( _n( 'We have found <strong style="color:red;">%s suspicious account</strong> created on your site via Ultimate Member Forms.', 'We have found <strong style="color:red;">%s suspicious accounts</strong> created on your site via Ultimate Member Forms.', $suspicious_accounts_count, 'ultimate-member' ), $suspicious_accounts_count ), UM()->get_allowed_html( 'admin_notice' ) );
			$content .= $br . wp_kses( __( 'We\'ve temporarily disabled the suspicious account(s) for you to <strong>take actions</strong>.', 'ultimate-member' ), UM()->get_allowed_html( 'admin_notice' ) );

			if ( $might_affected_users->get_total() > 0 ) {
				$od = gmdate( 'F d, Y', $oldest_date );
				$nd = gmdate( 'F d, Y', $newest_date );
				if ( $od !== $nd ) {
					$date_registered = $od . ' to ' . $nd;
				} else {
					$date_registered = $od;
				}
				// translators: %s suspicious account
				$content .= $br . $br . wp_kses( sprintf( _n( 'Also, We\'ve found <strong style="color:red;">%s account</strong>', 'Also, We\'ve found <strong style="color:red;">%s accounts</strong>', $might_affected_users->get_total(), 'ultimate-member' ), $might_affected_users->get_total() ), UM()->get_allowed_html( 'admin_notice' ) );
				// translators: %s account creation date
				$content .= ' ' . wp_kses( sprintf( _n( 'created on %s when the suspicious account was created.', 'created on %s when the suspicious accounts were created.', $suspicious_accounts_count, 'ultimate-member' ), $date_registered ), UM()->get_allowed_html( 'admin_notice' ) );
			}
		} else {
			$content .= $br . '<strong>' . esc_html__( 'Suspicious Accounts', 'ultimate-member' ) . '</strong> <br/>';
			$content .= $br . $check . ' ' . esc_html__( 'No suspicious accounts found', 'ultimate-member' ) . '<br/>';
		}

		$content .= $br . $br . wp_kses( __( 'PLEASE READ OUR <strong>RECOMMENDATIONS</strong> BELOW: ', 'ultimate-member' ), UM()->get_allowed_html( 'admin_notice' ) ) . $br;

		$content .= $br . '<div style="padding:10px; border:1px solid #ccc;">' . wp_kses( __( '<strong style="color:red">WARNING:</strong> Ensure that you\'ve created a full backup of your site as your restoration point before changing anything on your site with our recommendations.', 'ultimate-member' ), UM()->get_allowed_html( 'admin_notice' ) ) . '</div>';
		if ( $suspicious_accounts_count > 0 ) {
			$lock_register_forms_url = admin_url( 'admin.php?page=um_options&tab=secure&um_secure_lock_register_forms=1&_wpnonce=' . wp_create_nonce( 'um_secure_lock_register_forms' ) );
			$content                .= $br . esc_html__( '1. Please temporarily lock all your active Register forms.', 'ultimate-member' );
			$content                .= ' <a href="' . esc_attr( $lock_register_forms_url ) . '" target="_blank">' . esc_html__( 'Click here to lock them now.', 'ultimate-member' ) . '</a>';
			$content                .= ' ' . esc_html__( 'You can unblock the Register forms later. Just go to Ultimate Member > Settings > Secure > uncheck the option "Lock All Register Forms".', 'ultimate-member' );
			$content                .= $br . $br;
			$suspicious_accounts_url = admin_url( 'users.php?um_status=inactive' );

			if ( $might_affected_users->get_total() > 0 ) {
				$od = gmdate( 'F d, Y', $oldest_date );
				$nd = gmdate( 'F d, Y', $newest_date );
				if ( $od !== $nd ) {
					$suspicious_accounts_url = admin_url( 'users.php?um_secure_date_from=' . $oldest_date . '&um_secure_date_to=' . $newest_date );
				} else {
					$suspicious_accounts_url = admin_url( 'users.php?um_secure_date_from=' . $oldest_date );
				}
			}

			$content .= esc_html__( '2. Review all suspicious accounts and delete them completely.', 'ultimate-member' );
			$content .= ' <a href="' . esc_attr( $suspicious_accounts_url ) . '" target="_blank">' . esc_html__( 'Click here to review accounts.', 'ultimate-member' ) . '</a>';
			$content .= $br . $br;

			$nonce                    = wp_create_nonce( 'um-secure-expire-session-nonce' );
			$destroy_all_sessions_url = admin_url( '?um_secure_expire_all_sessions=1&_wpnonce=' . esc_attr( $nonce ) . '&except_me=1' );
			$content                 .= esc_html__( '3. If accounts are suspicious to you, please destroy all user sessions to logout active users on your site.', 'ultimate-member' );
			$content                 .= ' <a href="' . esc_attr( $destroy_all_sessions_url ) . '" target="_blank">' . esc_html__( 'Click here to Destroy Sessions now', 'ultimate-member' ) . '</a>';

			$content .= $br . $br;
			$content .= esc_html__( '4. Run a complete scan on your site using third-party Security plugins such as', 'ultimate-member' );
			$content .= ' <a target="_blank" href="' . esc_attr( admin_url( 'plugin-install.php?s=Jetpack%2520Protect%2520WP%2520Scan&tab=search&type=term' ) ) . '">' . esc_html__( 'WPScan/Jetpack Protect or WordFence Security', 'ultimate-member' ) . '</a>.';

			$content                .= $br . $br;
			$nonce                   = wp_create_nonce( 'um-secure-enable-reset-pass-nonce' );
			$reset_pass_sessions_url = admin_url( '?um_secure_enable_reset_password=1&_wpnonce=' . esc_attr( $nonce ) . '&except_me=1' );

			$content .= esc_html__( '5. Force users to Reset their Passwords.', 'ultimate-member' );
			$content .= ' <a target="_blank" href="' . esc_attr( $reset_pass_sessions_url ) . '">' . esc_html__( 'Click here to enable this option', 'ultimate-member' ) . '</a>.';
			$content .= ' ' . esc_html__( 'When this option is enabled, users will be asked to reset their passwords(one-time) on the next login in the UM Login form.', 'ultimate-member' );
			$content .= $br . $br;

			$content .= esc_html__( '6. Once your site is secured, please create or enable Daily Backups of your server/site. You can contact your hosting provider to assist you on this matter.', 'ultimate-member' );
			$content .= $br . $br;

			$content .= esc_html__( '👇 MORE RECOMMENDATIONS BELOW.', 'ultimate-member' );
		}

		$content .= $br . $br . '<strong>' . esc_html__( 'Review & Resolve Issues with Site Health Check tool', 'ultimate-member' ) . '</strong>';
		$content .= $br . esc_html__( 'Site Health is a tool in WordPress that helps you monitor how your site is doing. It shows critical information about your WordPress configuration and items that require your attention.', 'ultimate-member' );
		if ( $site_health_issues_total > 0 ) {
			// translators: %d issue in the Site Health status
			$content .= $br . $flag . esc_html( sprintf( _n( 'There\'s %d issue in the Site Health status', 'There are %d issues in the Site Health status', $site_health_issues_total, 'ultimate-member' ), $site_health_issues_total ) );
			$content .= ': <a target="_blank" href="' . admin_url( 'site-health.php' ) . '">' . esc_html__( 'Review Site Health Status', 'ultimate-member' ) . '</a>';
		} else {
			$content .= $br . $check . esc_html__( 'There are no issues found in the Site Health status', 'ultimate-member' );
		}

		$content .= $br . $br . '<strong>' . esc_html__( 'Default WP Register Form', 'ultimate-member' ) . '</strong>';
		if ( get_option( 'users_can_register' ) ) {
			$content .= $br . $flag . wp_kses( __( 'The default WordPress Register form is enabled. If you\'re getting Spam User Registrations, we recommend that you enable a Challenge-Response plugin such as our <a href="https://wordpress.org/plugins/um-recaptcha/" target="_blank">Ultimate Member - Google reCAPTCHA</a> extension.', 'ultimate-member' ), UM()->get_allowed_html( 'admin_notice' ) );
			$content .= $br;
		} else {
			$content .= $br . $check . esc_html__( 'The default WordPress Register form is disabled.', 'ultimate-member' ) . $br;
		}

		$content .= $br . '<strong>' . esc_html__( 'Secure Register Forms', 'ultimate-member' ) . '</strong>';
		$content .= $br . esc_html__( 'We\'ve removed the assignment of administrative roles for Register forms due to vulnerabilities in previous versions of the plugin. If your Register forms still have Administrative roles, we recommend that you assign a non-admin roles to secure the forms.', 'ultimate-member' ) . $br;
		foreach ( $um_forms as $fid ) {
			switch ( get_post_meta( $fid, '_um_mode', true ) ) {
				case 'register':
					$is_customized   = absint( get_post_meta( $fid, '_um_register_use_custom_settings', true ) );
					$arr_banned_caps = UM()->options()->get( 'banned_capabilities' );
					$has_banned_cap  = false;
					$role            = get_post_meta( $fid, '_um_register_role', true );

					if ( ! empty( $is_customized ) && ! empty( $role ) ) {
						$caps = get_role( $role )->capabilities;
						if ( is_array( $arr_banned_caps ) ) {
							foreach ( array_keys( $caps ) as $cap ) {
								if ( in_array( $cap, $arr_banned_caps, true ) ) {
									$content       .= $br . '<a target="_blank" href="' . get_edit_post_link( $fid ) . '">' . get_the_title( $fid ) . '</a> ' . wp_kses( __( 'contains <strong>administrative role</strong>', 'ultimate-member' ), UM()->get_allowed_html( 'admin_notice' ) ) . ' ' . $flag;
									$has_banned_cap = true;
									break;
								}
							}
						}
					}

					if ( ! $has_banned_cap || ! $is_customized ) {
						$content .= $br . '<a target="_blank" href="' . get_edit_post_link( $fid ) . '">' . get_the_title( $fid ) . '</a> ' . wp_kses( __( 'is <strong>secured</strong>', 'ultimate-member' ), UM()->get_allowed_html( 'admin_notice' ) ) . ' ' . $check;
					}
					break;
			}
		}
		$content .= $br;

		$content .= $br . '<strong>' . esc_html__( 'Block Disposable Email Addresses/Domains', 'ultimate-member' ) . '</strong>';
		if ( empty( UM()->options()->get( 'blocked_emails' ) ) ) {
			$content .= $br . $flag . wp_kses( __( 'You are not blocking email addresses or disposable email domains that are mostly used for Spam Account Registrations. You can get the list of disposable email domains with our basic extension <a href="https://docs.ultimatemember.com/article/1870-block-disposable-email-domains" target="_blank">Block Disposable Email Domains</a>.', 'ultimate-member' ), UM()->get_allowed_html( 'admin_notice' ) );
			$content .= $br;
		} else {
			$content .= $br . $check . esc_html__( 'Blocked Emails option is already set.', 'ultimate-member' );
			$content .= $br;
		}

		$content .= $br . '<strong>' . esc_html__( 'Manage User Roles & Capabilities', 'ultimate-member' ) . '</strong>';
		if ( absint( $scan_details['total_all_affected_users'] ) > 0 ) {
			$count_flagged_caps = $scan_details['total_all_cap_flagged'];
			$count_users        = $scan_details['total_all_affected_users'];
			$affected_caps      = $option['affected_caps'];
			$affected_roles     = array();

			$all_roles      = $wp_roles->roles;
			$editable_roles = apply_filters( 'editable_roles', $all_roles );
			foreach ( $affected_caps as $cap ) {
				foreach ( $editable_roles as $role_key => $role ) {
					if ( array_key_exists( $cap, $role['capabilities'] ) ) {
						$affected_roles[ $role_key ] = $role['name'];
					}
				}
			}
			// translators: %d users count
			$content .= $br . $flag . esc_html( sprintf( _n( 'We have found %d user account', 'We have found %d user accounts', $count_users, 'ultimate-member' ), $count_users ) );
			// translators: %d count of banned capabilities
			$content .= sprintf( _n( ' affected by %d capability selected in the Banned Administrative Capabilities.', ' affected by one of the %d capabilities selected in the Banned Administrative Capabilities.', $count_flagged_caps, 'ultimate-member' ), $count_flagged_caps );

			$content .= $br . '- ' . implode( '<br/> - ', $affected_caps );

			$content .= $br . $br . esc_html__( 'The flagged capabilities are related to the following roles: ', 'ultimate-member' ) . $br . ' - ' . implode( '<br/> - ', array_values( $affected_roles ) );
			// translators: %s is the role settings URL
			$content .= $br . $br . wp_kses( sprintf( __( 'The affected user accounts will be flagged as suspicious when they update their Profile/Account. If you are not using these capabilities, you may remove them from the roles in the <a target="_blank" href="%s">User Role settings</a>.', 'ultimate-member' ), admin_url( 'admin.php?page=um_roles' ) ), UM()->get_allowed_html( 'admin_notice' ) );
			// translators: %s is the plugins page URL
			$content .= ' ' . wp_kses( sprintf( __( 'If the roles are not created via Ultimate Member > User Roles, you can use a <a href="%s" target="_blank">third-party plugin</a> to modify the role capability.', 'ultimate-member' ), admin_url( 'plugin-install.php?s=User%2520Role%2520Editor%2520WordPress%2520&tab=search&type=term' ) ), UM()->get_allowed_html( 'admin_notice' ) );
			$content .= $br . $br . esc_html__( 'We strongly recommend that you never assign roles with the same capabilities as your administrators for your members/users and that may allow them to access the admin-side features and functionalities of your WordPress site.', 'ultimate-member' );
		} else {
			$content .= $br . $check . esc_html__( 'Roles & Capabilities are all secured. No users are using the same capabilities as your administrators.', 'ultimate-member' );
		}

		$content .= $br . $br . '<strong>' . esc_html__( 'Require Strong Passwords', 'ultimate-member' ) . '</strong>';
		if ( ! UM()->options()->get( 'require_strongpass' ) ) {
			$content .= $br . $flag . esc_html__( 'We recommend that you enable and require "Strong Password" feature for all the Register, Reset Password & Account forms.', 'ultimate-member' );
			$content .= $br . ' <a href="' . admin_url( 'admin.php?page=um_options&section=users' ) . '" target="_blank" >' . esc_html__( 'Click here to enable.', 'ultimate-member' ) . '</a>';
		} else {
			$content .= $br . $check . esc_html__( 'Your forms are already configured to require of using strong passwords.', 'ultimate-member' );
		}

		$content .= $br . $br . '<strong>' . esc_html__( 'Secure Site\'s Connection', 'ultimate-member' ) . '</strong>';
		if ( ! isset( $_SERVER['HTTPS'] ) || 'on' !== $_SERVER['HTTPS'] ) {
			$content .= $br . $flag . esc_html__( 'Your site cannot provide a secure connection. Please contact your hosting provider to enable SSL certifications on your server.', 'ultimate-member' );
		} else {
			$content .= $br . $check . esc_html__( 'Your site provides a secure connection with SSL.', 'ultimate-member' );
		}

		$content .= $br . $br . '<strong>' . esc_html__( 'Install Challenge-Response plugin to Login & Register Forms', 'ultimate-member' ) . '</strong>';
		if ( ! array_key_exists( 'um-recaptcha/um-recaptcha.php', $all_plugins ) ) {
			$content .= $br . $flag . wp_kses( __( 'We recommend that you install and enable <a href="https://wordpress.org/plugins/um-recaptcha/" target="_blank">Ultimate Member - Google reCAPTCHA</a> to your Reset Password, Login & Register forms.', 'ultimate-member' ), UM()->get_allowed_html( 'admin_notice' ) );
		} else {
			if ( in_array( 'um-recaptcha/um-recaptcha.php', $active_plugins, true ) ) {
				$content .= $br . $check . esc_html__( 'Ultimate Member - Google reCAPTCHA is active.', 'ultimate-member' );
				foreach ( $um_forms as $fid ) {
					switch ( get_post_meta( $fid, '_um_mode', true ) ) {
						case 'register':
							$has_captcha = get_post_meta( $fid, '_um_register_g_recaptcha_status', true );
							if ( ! empty( $has_captcha ) ) {
								// translators: %1$s is UM form edit link, %2$s is the UM form title
								$content .= $br . '&nbsp;&nbsp;' . wp_kses( sprintf( __( '- Register: <a target="_blank" href="%1$s">%2$s</a> reCAPTCHA is <strong>enabled</strong>', 'ultimate-member' ), get_edit_post_link( $fid ), get_the_title( $fid ) ), UM()->get_allowed_html( 'admin_notice' ) ) . ' ' . $check;
							} else {
								// translators: %1$s is UM form edit link, %2$s is the UM form title
								$content .= $br . '&nbsp;&nbsp;' . wp_kses( sprintf( __( '- Register: <a target="_blank" href="%1$s">%2$s</a> reCAPTCHA is <strong>disabled</strong>', 'ultimate-member' ), get_edit_post_link( $fid ), get_the_title( $fid ) ), UM()->get_allowed_html( 'admin_notice' ) ) . ' ' . $flag;
							}
							break;
						case 'login':
							$has_captcha = get_post_meta( $fid, '_um_login_g_recaptcha_status', true );
							if ( ! empty( $has_captcha ) ) {
								// translators: %1$s is UM form edit link, %2$s is the UM form title
								$content .= $br . '&nbsp;&nbsp;' . wp_kses( sprintf( __( '- Login: <a target="_blank" href="%1$s">%2$s</a> reCAPTCHA is <strong>enabled</strong>', 'ultimate-member' ), get_edit_post_link( $fid ), get_the_title( $fid ) ), UM()->get_allowed_html( 'admin_notice' ) ) . ' ' . $check;
							} else {
								// translators: %1$s is UM form edit link, %2$s is the UM form title
								$content .= $br . '&nbsp;&nbsp;' . wp_kses( sprintf( __( '- Login: <a target="_blank" href="%1$s">%2$s</a> reCAPTCHA is <strong>disabled</strong>', 'ultimate-member' ), get_edit_post_link( $fid ), get_the_title( $fid ) ), UM()->get_allowed_html( 'admin_notice' ) ) . ' ' . $flag;
							}
							break;
					}
				}
				$reset_pass_form = UM()->options()->get( 'g_recaptcha_password_reset' );
				if ( ! empty( $reset_pass_form ) ) {
					$content .= $br . '&nbsp;&nbsp;' . wp_kses( __( '- Reset Password Form\'s Google reCAPTCHA is <strong>enabled</strong>', 'ultimate-member' ), UM()->get_allowed_html( 'admin_notice' ) ) . ' ' . $check;
				} else {
					$content .= $br . '&nbsp;&nbsp;' . wp_kses( __( '- Reset Password Form\'s Google reCAPTCHA is <strong>disabled</strong>', 'ultimate-member' ), UM()->get_allowed_html( 'admin_notice' ) ) . ' ' . $flag;
				}
			} else {
				$content .= $br . $flag . esc_html__( 'Ultimate Member - Google reCAPTCHA is installed but not activated.', 'ultimate-member' );
			}
		}

		$update_plugins = get_site_transient( 'update_plugins' );
		$update_themes  = get_site_transient( 'update_themes' );
		$update_wp_core = get_site_transient( 'update_core' );
		global $wp_version;
		$content .= $br . $br . '<strong>' . esc_html__( 'Keep Themes & Plugins up to date.', 'ultimate-member' ) . '</strong>';
		$content .= $br . esc_html__( 'It is important that you update your themes/plugins if the theme/plugin creators update is aimed at fixing security, bug and vulnerability issues. It is not a good idea to ignore available updates as this may give hackers an advantage when trying to access your website.', 'ultimate-member' );

		if ( ! empty( $update_plugins->response ) ) {
			// translators: %d count of plugins for update
			$content .= $br . $br . $flag . sprintf( _n( 'There\'s %d plugin that requires an update.', 'There are %d plugins that require updates', count( $update_plugins->response ), 'ultimate-member' ), count( $update_plugins->response ) ) . ' <a target="_blank" href="' . admin_url( 'update-core.php' ) . '">' . esc_html__( 'Update Plugins Now', 'ultimate-member' ) . '</a>';
			foreach ( $update_plugins->response as $plugin_name => $data ) {
				$content .= $br . '&nbsp;&nbsp;- ' . $plugin_name;
			}
		} else {
			$content .= $br . $br . $check . esc_html__( 'Plugins are up to date.', 'ultimate-member' );
		}

		if ( ! empty( $update_themes->response ) ) {
			// translators: %d count of themes for update
			$content .= $br . $br . $flag . sprintf( _n( 'There\'s %d theme that requires an update.', 'There are %d themes that require updates', count( $update_plugins->response ), 'ultimate-member' ), count( $update_plugins->response ) ) . ' <a target="_blank" href="' . admin_url( 'update-core.php' ) . '">' . esc_html__( 'Update Themes Now', 'ultimate-member' ) . '</a>';
			foreach ( $update_themes->response as $theme_name => $data ) {
				$content .= $br . '&nbsp;&nbsp;- ' . $theme_name;
			}
		} else {
			$content .= $br . $br . $check . esc_html__( 'Themes are up to date.', 'ultimate-member' );
		}

		if ( isset( $update_wp_core->updates[0]->current ) && $wp_version !== $update_wp_core->updates[0]->current ) {
			$content .= $br . $br . $flag . esc_html__( 'There\'s a new version of WordPress.', 'ultimate-member' ) . ' <a target="_blank" href="' . admin_url( 'update-core.php' ) . '">' . esc_html__( 'Update WordPress Now', 'ultimate-member' ) . '</a>';
		} else {
			$content .= $br . $br . $check . esc_html__( 'You\'re using the latest version of WordPress', 'ultimate-member' ) . '(' . esc_attr( $wp_version ) . ')';
		}

		$content .= $br . $br . wp_kses( __( 'That\'s all. If you have any recommendation on how to secure your site or have questions, please contact us on our <a href="https://ultimatemember.com/feedback/" target="_blank">feedback page</a>.', 'ultimate-member' ), UM()->get_allowed_html( 'admin_notice' ) );

		update_option( 'um_secure_scan_result_content', $content );

		return $content;
	}
}
