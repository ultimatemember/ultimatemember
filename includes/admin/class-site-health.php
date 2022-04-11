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
					'um-roles'            => array(
						'label' => __( 'User Roles', 'ultimate-member' ),
						'value' => $this->get_roles(),
					),
					'um-register_role'            => array(
						'label' => __( 'Default New User Role', 'ultimate-member' ),
						'value' => get_option( 'default_role' ),
					),
					'um-permalink_base'            => array(
						'label' => __( 'Profile Permalink Base', 'ultimate-member' ),
						'value' => UM()->options()->get('permalink_base'),
					),
					'um-display_name'            => array(
						'label' => __( 'User Display Name', 'ultimate-member' ),
						'value' => UM()->options()->get('display_name'),
					),
				),
			);

			return $info;
		}
	}
}
