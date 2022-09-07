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
	}
}
