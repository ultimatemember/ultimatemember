<?php
namespace umm\online\includes\admin;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'umm\online\includes\admin\Site_Health' ) ) {


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
			add_action( 'debug_information', array( &$this, 'debug_information' ), 25, 1 );
			add_filter( 'um_debug_member_directory_profile_extend', array( $this, 'um_debug_member_directory_profile_extend' ), 10, 2 );
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
			$info['ultimate-member-online'] = array(
				'label'       => __( 'Ultimate Member Online', 'ultimate-member' ),
				'description' => __( 'This debug information about Ultimate Member Online module.', 'ultimate-member' ),
				'fields'      => array(
					'um-online_show_stats' => array(
						'label' => __( 'Show online stats in member directory', 'ultimate-member' ),
						'value' => UM()->options()->get('online_show_stats') ? __( 'Yes', 'ultimate-member' ) : __( 'No', 'ultimate-member' ),
					),
				),
			);

			return $info;
		}


		/**
		 * Extend profile card for member directory.
		 *
		 * @since 3.0
		 *
		 * @param array $info The Site Health information.
		 *
		 * @return array The updated Site Health information.
		 */
		public function um_debug_member_directory_profile_extend( $info, $key ) {
			$info['ultimate-member-directory-' . $key ]['fields'] = array_merge(
				$info['ultimate-member-directory-' . $key ]['fields'],
				array(
					'um-directory-online_hide_stats'     => array(
						'label' => __( 'Hide online stats', 'ultimate-member' ),
						'value' => get_post_meta( $key,'_um_online_hide_stats', true ) ? __( 'Yes', 'ultimate-member-pro' ) : __( 'No', 'ultimate-member-pro' ),
					),
				)
			);

			return $info;
		}
	}
}
