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

			$user_settings = array();
			$account_settings = array();
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
