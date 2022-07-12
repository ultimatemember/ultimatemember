<?php
namespace um\admin;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'um\admin\Site_Health' ) ) {


	/**
	 * Class Site_Health
	 *
	 * @package um\admin
	 */
	class Site_Health {


		/**
		 * Site_Health constructor.
		 */
		public function __construct() {
			add_filter( 'debug_information', array( $this, 'debug_information' ) );
		}


		private function get_roles() {
			return UM()->roles()->get_roles();
		}


		/**
		 * Add our data to Site Health information.
		 *
		 * @since 3.0
		 *
		 * @param array $info The Site Health information.
		 *
		 * @return array The updated Site Health information.
		 */
		public function debug_information( $info ) {
			$labels = array(
				'yes'     => __( 'Yes', 'ultimate-member' ),
				'no'      => __( 'No', 'ultimate-member' ),
				'enable'  => __( 'Enable', 'ultimate-member' ),
				'disable' => __( 'Disable', 'ultimate-member' ),
			);

			$info['ultimate-member'] = array(
				'label'       => __( 'Ultimate Member', 'ultimate-member' ),
				'description' => __( 'This debug information for your Ultimate Member installation can assist you in getting support.', 'ultimate-member' ),
				'fields'      => array(
					'um-roles'         => array(
						'label' => __( 'User Roles', 'ultimate-member' ),
						'value' => $this->get_roles(),
					),
					'um-register_role' => array(
						'label' => __( 'Default New User Role', 'ultimate-member' ),
						'value' => get_option( 'default_role' ),
					),
				),
			);

			// User settings
			$user_settings = array(
				'um-permalink_base'              => array(
					'label' => __( 'Profile Permalink Base', 'ultimate-member' ),
					'value' => UM()->options()->get('permalink_base'),
				),
				'um-display_name'                => array(
					'label' => __( 'User Display Name', 'ultimate-member' ),
					'value' => UM()->options()->get('display_name'),
				),
				'um-author_redirect'             => array(
					'label' => __( 'Automatically redirect author page to their profile?', 'ultimate-member' ),
					'value' => UM()->options()->get('author_redirect') ? $labels['yes'] : $labels['no'],
				),
				'um-profile_noindex'             => array(
					'label' => __( 'Avoid indexing profile by search engines', 'ultimate-member' ),
					'value' => UM()->options()->get('profile_noindex') ? $labels['yes'] : $labels['no'],
				),
				'um-activation_link_expiry_time' => array(
					'label' => __( 'Email activation link expiration (days)', 'ultimate-member' ),
					'value' => UM()->options()->get('activation_link_expiry_time'),
				),
				'um-default_avatar'              => array(
					'label' => __( 'Default Profile Photo', 'ultimate-member' ),
					'value' => um_get_default_avatar_uri(),
				),
				'um-default_cover'               => array(
					'label' => __( 'Default Cover Photo', 'ultimate-member' ),
					'value' => um_get_default_cover_uri(),
				),
				'um-require_strongpass'          => array(
					'label' => __( 'Require Strong Passwords', 'ultimate-member' ),
					'value' => UM()->options()->get('require_strongpass') == 1 ? $labels['yes'] : $labels['no'],
				),
			);

			if ( 1 == UM()->options()->get('require_strongpass') ) {
				$user_settings['um-password_min_chars'] =  array(
					'label' => __( 'Password minimum length', 'ultimate-member' ),
					'value' => UM()->options()->get('password_min_chars'),
				);
				$user_settings['um-password_max_chars'] =  array(
					'label' => __( 'Password maximum length', 'ultimate-member' ),
					'value' => UM()->options()->get('password_max_chars'),
				);
			}

			$user_settings['um-use_gravatars'] = array(
				'label' => __( 'Use Gravatars', 'ultimate-member' ),
				'value' => UM()->options()->get('use_gravatars') ? $labels['yes'] : $labels['no'],
			);

			if ( 1 == UM()->options()->get('use_gravatars') ) {
				$user_settings['um-use_um_gravatar_default_builtin_image'] = array(
					'label' => __( 'Use Gravatar builtin image', 'ultimate-member' ),
					'value' => UM()->options()->get('use_um_gravatar_default_builtin_image'),
				);
				if ( 'default' == UM()->options()->get('use_um_gravatar_default_builtin_image') ) {
					$user_settings['um-use_um_gravatar_default_image'] = array(
						'label' => __( 'Use Default plugin avatar as Gravatar\'s Default avatar', 'ultimate-member' ),
						'value' => UM()->options()->get('use_um_gravatar_default_image') ? $labels['yes'] : $labels['no'],
					);
				}
			}

			// Account settings
			$account_settings = array(
				'um-account_tab_password'              => array(
					'label' => __( 'Password Account Tab', 'ultimate-member' ),
					'value' => UM()->options()->get('account_tab_password') ? $labels['yes'] : $labels['no'],
				),
				'um-account_tab_privacy'              => array(
					'label' => __( 'Privacy Account Tab', 'ultimate-member' ),
					'value' => UM()->options()->get('account_tab_privacy') ? $labels['yes'] : $labels['no'],
				),
				'um-account_tab_notifications'              => array(
					'label' => __( 'Notifications Account Tab', 'ultimate-member' ),
					'value' => UM()->options()->get('account_tab_notifications') ? $labels['yes'] : $labels['no'],
				),
				'um-account_email'              => array(
					'label' => __( 'Allow users to change email', 'ultimate-member' ),
					'value' => UM()->options()->get('account_email') ? $labels['yes'] : $labels['no'],
				),
				'um-account_general_password'              => array(
					'label' => __( 'Require password to update account', 'ultimate-member' ),
					'value' => UM()->options()->get('account_general_password') ? $labels['yes'] : $labels['no'],
				),
				'um-account_name'              => array(
					'label' => __( 'Display First & Last name fields', 'ultimate-member' ),
					'value' => UM()->options()->get('account_name') ? $labels['yes'] : $labels['no'],
				),
			);

			if ( 1 == UM()->options()->get('account_name') ) {
				$account_settings['um-account_name_disable'] = array(
					'label' => __( 'Disable First & Last name field editing', 'ultimate-member' ),
					'value' => UM()->options()->get('account_name_disable') ? $labels['yes'] : $labels['no'],
				);
				$account_settings['um-account_name_require'] = array(
					'label' => __( 'Require First & Last Name', 'ultimate-member' ),
					'value' => UM()->options()->get('account_name_require') ? $labels['yes'] : $labels['no'],
				);
			}

			$account_settings['um-account_tab_delete'] = array(
				'label' => __( 'Delete Account Tab', 'ultimate-member' ),
				'value' => UM()->options()->get('account_tab_delete') ? $labels['yes'] : $labels['no'],
			);

			if ( 1 == UM()->options()->get('account_tab_delete') ) {
				$account_settings['um-delete_account_password_requires'] = array(
					'label' => __( 'Account deletion password requires', 'ultimate-member' ),
					'value' => UM()->options()->get('delete_account_password_requires') ? $labels['yes'] : $labels['no'],
				);
				$account_settings['um-delete_account_text'] = array(
					'label' => __( 'Account Deletion Text', 'ultimate-member' ),
					'value' => UM()->options()->get('delete_account_text'),
				);
			}

			$info['ultimate-member']['fields'] = array_merge( $info['ultimate-member']['fields'], $user_settings, $account_settings );

			return $info;
		}
	}
}
