<?php
namespace um\admin\core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\admin\core\Admin_Site_Health' ) ) {

	/**
	 * Class Admin_Settings
	 * @package um\admin\core
	 */
	class Admin_Site_Health {

		/**
		 * Admin_Settings constructor.
		 */
		public function __construct() {
			add_filter( 'debug_information', array( $this, 'debug_information' ), 20, 1 );
		}

		/**
		 * Add our data to Site Health information.
		 *
		 * @since 2.6.12
		 *
		 * @param array $info The Site Health information.
		 *
		 * @return array The updated Site Health information.
		 */
		public function debug_information( $info ) {
			$labels = array(
				'yes'     => __( 'Yes', 'ultimate-member' ),
				'no'      => __( 'No', 'ultimate-member' ),
				'all'     => __( 'All', 'ultimate-member' ),
				'default' => __( 'Default', 'ultimate-member' ),
				'nopages' => __( 'No predefined page', 'ultimate-member' ),
			);

			$info['ultimate-member'] = array(
				'label'       => __( 'Ultimate Member', 'ultimate-member' ),
				'description' => __( 'This debug information for your Ultimate Member installation can assist you in getting support.', 'ultimate-member' ),
				'fields'      => array(),
			);

			// Pages settings
			$pages = apply_filters(
				'um_debug_information_pages',
				array(
					'User'           => null !== UM()->options()->get( 'core_user' ) ? get_the_title( UM()->options()->get( 'core_user' ) ) . ' (ID#' . UM()->options()->get( 'core_user' ) . ') | ' . get_permalink( UM()->options()->get( 'core_user' ) ) : $labels['nopages'],
					'Login'          => null !== UM()->options()->get( 'core_login' ) ? get_the_title( UM()->options()->get( 'core_login' ) ) . ' (ID#' . UM()->options()->get( 'core_login' ) . ') | ' . get_permalink( UM()->options()->get( 'core_login' ) ) : $labels['nopages'],
					'Register'       => null !== UM()->options()->get( 'core_register' ) ? get_the_title( UM()->options()->get( 'core_register' ) ) . ' (ID#' . UM()->options()->get( 'core_register' ) . ') | ' . get_permalink( UM()->options()->get( 'core_register' ) ) : $labels['nopages'],
					'Members'        => null !== UM()->options()->get( 'core_members' ) ? get_the_title( UM()->options()->get( 'core_members' ) ) . ' (ID#' . UM()->options()->get( 'core_members' ) . ') | ' . get_permalink( UM()->options()->get( 'core_members' ) ) : $labels['nopages'],
					'Logout'         => null !== UM()->options()->get( 'core_logout' ) ? get_the_title( UM()->options()->get( 'core_logout' ) ) . ' (ID#' . UM()->options()->get( 'core_logout' ) . ') | ' . get_permalink( UM()->options()->get( 'core_logout' ) ) : $labels['nopages'],
					'Account'        => null !== UM()->options()->get( 'core_account' ) ? get_the_title( UM()->options()->get( 'core_account' ) ) . ' (ID#' . UM()->options()->get( 'core_account' ) . ') | ' . get_permalink( UM()->options()->get( 'core_account' ) ) : $labels['nopages'],
					'Password reset' => null !== UM()->options()->get( 'core_password' ) ? get_the_title( UM()->options()->get( 'core_password-reset' ) ) . ' (ID#' . UM()->options()->get( 'core_password-reset' ) . ') | ' . get_permalink( UM()->options()->get( 'core_password-reset' ) ) : $labels['nopages'],
				)
			);

			$pages_settings = array(
				'um-pages' => array(
					'label' => __( 'Pages', 'ultimate-member' ),
					'value' => $pages,
				),
			);

			// User settings
			$permalink_base = array(
				'user_login' => __( 'Username', 'ultimate-member' ),
				'name'       => __( 'First and Last Name with \'.\'', 'ultimate-member' ),
				'name_dash'  => __( 'First and Last Name with \'-\'', 'ultimate-member' ),
				'name_plus'  => __( 'First and Last Name with \'+\'', 'ultimate-member' ),
				'user_id'    => __( 'User ID', 'ultimate-member' ),
			);
			$display_name   = array(
				'default'        => __( 'Default WP Display Name', 'ultimate-member' ),
				'nickname'       => __( 'Nickname', 'ultimate-member' ),
				'username'       => __( 'Username', 'ultimate-member' ),
				'full_name'      => __( 'First name & last name', 'ultimate-member' ),
				'sur_name'       => __( 'Last name & first name', 'ultimate-member' ),
				'initial_name'   => __( 'First name & first initial of last name', 'ultimate-member' ),
				'initial_name_f' => __( 'First initial of first name & last name', 'ultimate-member' ),
				'first_name'     => __( 'First name only', 'ultimate-member' ),
				'field'          => __( 'Custom field(s)', 'ultimate-member' ),
			);

			$user_settings = array(
				'um-permalink_base'              => array(
					'label' => __( 'Profile Permalink Base', 'ultimate-member' ),
					'value' => isset( $permalink_base[ UM()->options()->get( 'permalink_base' ) ] ) ? $permalink_base[ UM()->options()->get( 'permalink_base' ) ] : $labels['no'],
				),
				'um-display_name'                => array(
					'label' => __( 'User Display Name', 'ultimate-member' ),
					'value' => isset( $display_name[ UM()->options()->get( 'display_name' ) ] ) ? $display_name[ UM()->options()->get( 'display_name' ) ] : $labels['no'],
				),
				'um-author_redirect'             => array(
					'label' => __( 'Automatically redirect author page to their profile?', 'ultimate-member' ),
					'value' => UM()->options()->get( 'author_redirect' ) ? $labels['yes'] : $labels['no'],
				),
				'um-members_page'                => array(
					'label' => __( 'Enable Members Directory', 'ultimate-member' ),
					'value' => UM()->options()->get( 'members_page' ) ? $labels['yes'] : $labels['no'],
				),
				'um-toggle_password'             => array(
					'label' => __( 'Show/hide password button', 'ultimate-member' ),
					'value' => UM()->options()->get( 'toggle_password' ) ? $labels['yes'] : $labels['no'],
				),
				'um-require_strongpass'          => array(
					'label' => __( 'Require Strong Passwords', 'ultimate-member' ),
					'value' => UM()->options()->get( 'require_strongpass' ) ? $labels['yes'] : $labels['no'],
				),
				'um-password_min_chars'          => array(
					'label' => __( 'Require Strong Passwords', 'ultimate-member' ),
					'value' => UM()->options()->get( 'password_min_chars' ),
				),
				'um-password_max_chars'          => array(
					'label' => __( 'Require Strong Passwords', 'ultimate-member' ),
					'value' => UM()->options()->get( 'password_max_chars' ),
				),
				'um-profile_noindex'             => array(
					'label' => __( 'Avoid indexing profile by search engines', 'ultimate-member' ),
					'value' => UM()->options()->get( 'profile_noindex' ) ? $labels['yes'] : $labels['no'],
				),
				'um-activation_link_expiry_time' => array(
					'label' => __( 'Activation link lifetime', 'ultimate-member' ),
					'value' => UM()->options()->get( 'activation_link_expiry_time' ),
				),
				'um-use_gravatars'               => array(
					'label' => __( 'Use Gravatars?', 'ultimate-member' ),
					'value' => UM()->options()->get( 'use_gravatars' ) ? $labels['yes'] : $labels['no'],
				),
			);

			if ( 1 === absint( UM()->options()->get( 'use_gravatars' ) ) ) {
				$gravatar_options = array(
					'default'   => __( 'Default', 'ultimate-member' ),
					'404'       => __( '404 ( File Not Found response )', 'ultimate-member' ),
					'mm'        => __( 'Mystery Man', 'ultimate-member' ),
					'identicon' => __( 'Identicon', 'ultimate-member' ),
					'monsterid' => __( 'Monsterid', 'ultimate-member' ),
					'wavatar'   => __( 'Wavatar', 'ultimate-member' ),
					'retro'     => __( 'Retro', 'ultimate-member' ),
					'blank'     => __( 'Blank ( a transparent PNG image )', 'ultimate-member' ),
				);

				$user_settings['um-use_um_gravatar_default_builtin_image'] = array(
					'label' => __( 'Use Gravatar builtin image', 'ultimate-member' ),
					'value' => $gravatar_options[ UM()->options()->get( 'use_um_gravatar_default_builtin_image' ) ],
				);
				if ( 'default' === UM()->options()->get( 'use_um_gravatar_default_builtin_image' ) ) {
					$user_settings['um-use_um_gravatar_default_image'] = array(
						'label' => __( 'Use Default plugin avatar as Gravatar\'s Default avatar', 'ultimate-member' ),
						'value' => UM()->options()->get( 'use_um_gravatar_default_image' ) ? $labels['yes'] : $labels['no'],
					);
				}
			}

			// Account settings
			$account_settings = array(
				'um-account_tab_password'                 => array(
					'label' => __( 'Password Account Tab', 'ultimate-member' ),
					'value' => UM()->options()->get( 'account_tab_password' ) ? $labels['yes'] : $labels['no'],
				),
				'um-account_tab_privacy'                  => array(
					'label' => __( 'Privacy Account Tab', 'ultimate-member' ),
					'value' => UM()->options()->get( 'account_tab_privacy' ) ? $labels['yes'] : $labels['no'],
				),
				'um-account_tab_notifications'            => array(
					'label' => __( 'Notifications Account Tab', 'ultimate-member' ),
					'value' => UM()->options()->get( 'account_tab_notifications' ) ? $labels['yes'] : $labels['no'],
				),
				'um-account_tab_delete'                   => array(
					'label' => __( 'Delete Account Tab', 'ultimate-member' ),
					'value' => UM()->options()->get( 'account_tab_delete' ) ? $labels['yes'] : $labels['no'],
				),
				'um-delete_account_text'                  => array(
					'label' => __( 'Account Deletion Custom Text', 'ultimate-member' ),
					'value' => UM()->options()->get( 'delete_account_text' ),
				),
				'um-delete_account_no_pass_required_text' => array(
					'label' => __( 'Account Deletion without password Custom Text', 'ultimate-member' ),
					'value' => UM()->options()->get( 'delete_account_no_pass_required_text' ),
				),
				'um-account_name'                         => array(
					'label' => __( 'Add a First & Last Name fields', 'ultimate-member' ),
					'value' => UM()->options()->get( 'account_name' ) ? $labels['yes'] : $labels['no'],
				),
			);

			if ( 1 === absint( UM()->options()->get( 'account_name' ) ) ) {
				$account_settings['um-account_name_disable'] = array(
					'label' => __( 'Disable First & Last name field editing', 'ultimate-member' ),
					'value' => UM()->options()->get( 'account_name_disable' ) ? $labels['yes'] : $labels['no'],
				);
				$account_settings['um-account_name_require'] = array(
					'label' => __( 'Require First & Last Name', 'ultimate-member' ),
					'value' => UM()->options()->get( 'account_name_require' ) ? $labels['yes'] : $labels['no'],
				);
			}

			$account_settings['um-account_hide_in_directory'] = array(
				'label' => __( 'Allow users to hide their profiles from directory', 'ultimate-member' ),
				'value' => UM()->options()->get( 'account_hide_in_directory' ) ? $labels['yes'] : $labels['no'],
			);

			if ( 1 === absint( UM()->options()->get( 'account_name' ) ) ) {
				$account_settings['um-account_hide_in_directory_default'] = array(
					'label' => __( 'Hide profiles from directory by default', 'ultimate-member' ),
					'value' => UM()->options()->get( 'account_hide_in_directory_default' ),
				);
			}


			$uploads_settings = array();
			$restrict_settings = array();
			$access_other_settings = array();
			$email_settings = array();
			$misc_settings = array();

			$info['ultimate-member']['fields'] = array_merge( $info['ultimate-member']['fields'], $pages_settings, $user_settings, $account_settings, $uploads_settings, $restrict_settings, $access_other_settings, $email_settings, $misc_settings );

			return $info;
		}
	}
}
