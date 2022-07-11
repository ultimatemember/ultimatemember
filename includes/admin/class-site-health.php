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
					'value' => UM()->options()->get('author_redirect') ? 'Yes' : 'No',
				),
				'um-profile_noindex'             => array(
					'label' => __( 'Avoid indexing profile by search engines', 'ultimate-member' ),
					'value' => UM()->options()->get('profile_noindex') ? 'Yes' : 'No',
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
					'value' => UM()->options()->get('require_strongpass') == 1 ? 'Yes' : 'No',
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
				'value' => UM()->options()->get('use_gravatars') ? 'Yes' : 'No',
			);

			if ( 1 == UM()->options()->get('use_gravatars') ) {
				$user_settings['um-use_um_gravatar_default_builtin_image'] = array(
					'label' => __( 'Use Gravatar builtin image', 'ultimate-member' ),
					'value' => UM()->options()->get('use_um_gravatar_default_builtin_image'),
				);
				if ( 'default' == UM()->options()->get('use_um_gravatar_default_builtin_image') ) {
					$user_settings['um-use_um_gravatar_default_image'] = array(
						'label' => __( 'Use Default plugin avatar as Gravatar\'s Default avatar', 'ultimate-member' ),
						'value' => UM()->options()->get('use_um_gravatar_default_image') ? 'Yes' : 'No',
					);
				}
			}

			$info['ultimate-member']['fields'] = array_merge( $info['ultimate-member']['fields'], $user_settings);

			return $info;
		}
	}
}
