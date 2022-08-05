<?php
namespace umm\jobboardwp\includes\admin;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'umm\jobboardwp\includes\admin\Site_Health' ) ) {


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
			add_filter( 'debug_information', array( $this, 'debug_information' ), 20, 1 );
			add_filter( 'um_debug_information_user_role', array( $this, 'um_debug_information_user_role' ), 20, 2 );
			add_filter( 'um_debug_extend_private_messages', array( $this, 'um_debug_extend_private_messages' ), 10, 1 );
			add_filter( 'um_debug_extend_verified_users', array( $this, 'um_debug_extend_verified_users' ), 10, 1 );
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
			$info['ultimate-member-jobboard'] = array(
				'label'       => __( 'Ultimate Member JobBoard', 'ultimate-member' ),
				'description' => __( 'This debug information about Ultimate Member JobBoard module.', 'ultimate-member' ),
				'fields'      => array(
					'um-account_tab_jobboardwp' => array(
						'label' => __( 'Account Tab', 'ultimate-member' ),
						'value' => UM()->options()->get('account_tab_jobboardwp') ? __( 'Yes', 'ultimate-member' ) : __( 'No', 'ultimate-member' ),
					),
				),
			);

			return $info;
		}


		/**
		 * Extend user role info.
		 *
		 * @since 3.0
		 *
		 * @param array $info The Site Health information.
		 *
		 * @return array The updated Site Health information.
		 */
		public function um_debug_information_user_role( $info, $key ) {
			$rolemeta = get_option( "um_role_{$key}_meta", false );

			$info['ultimate-member-' . $key ]['fields'] = array_merge(
				$info['ultimate-member-' . $key ]['fields'],
				array(
					'um-disable_jobs_tab' => array(
						'label' => __( 'JobBoard - Disable jobs tab?', 'ultimate-member' ),
						'value' => ! empty( $rolemeta['_um_disable_jobs_tab'] ) ? __( 'Yes', 'ultimate-member' ) : __( 'No', 'ultimate-member' ),
					),
				)
			);

			return $info;
		}


		/**
		 * Extend Private messages settings.
		 *
		 * @since 3.0
		 *
		 * @param array $info
		 *
		 * @return array
		 */
		public function um_debug_extend_private_messages( $info ) {
			$info['ultimate-member-private-messages' ]['fields'] = array_merge(
				$info['ultimate-member-private-messages' ]['fields'],
				array(
					'um-job_show_pm_button' => array(
						'label' => __( 'Show messages button in individual job post', 'ultimate-member-pro' ),
						'value' => UM()->options()->get('job_show_pm_button') ? __( 'Yes', 'ultimate-member-pro' ) : __( 'No', 'ultimate-member-pro' ),
					),
				)
			);

			return $info;
		}


		/**
		 * Extend Verified users settings.
		 *
		 * @since 3.0
		 *
		 * @param array $info
		 *
		 * @return array
		 */
		public function um_debug_extend_verified_users( $info ) {
			$info['ultimate-member-verified-users' ]['fields'] = array_merge(
				$info['ultimate-member-verified-users' ]['fields'],
				array(
					'um-job_apply_only_verified' => array(
						'label' => __( 'Only verified users can apply for jobs', 'ultimate-member-pro' ),
						'value' => UM()->options()->get('job_apply_only_verified') ? __( 'Yes', 'ultimate-member-pro' ) : __( 'No', 'ultimate-member-pro' ),
					),
				)
			);

			return $info;
		}
	}
}
