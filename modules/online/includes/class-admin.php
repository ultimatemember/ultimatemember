<?php
namespace umm\online\includes;


if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class Admin
 *
 * @package umm\online\includes
 */
class Admin {


	/**
	 * Admin constructor.
	 */
	function __construct() {
		add_filter( 'um_settings_structure', array( $this, 'admin_settings' ), 10, 1 );
	}


	/**
	 * @param array $settings
	 *
	 * @return array
	 */
	function admin_settings( $settings ) {
		$settings['modules']['sections']['online'] = array(
			'title'  => __( 'Online', 'ultimate-member' ),
			'fields' => array(
				array(
					'id'    => 'online_show_stats',
					'type'  => 'checkbox',
					'label' => __( 'Show online stats in member directory', 'ultimate-member' ),
				),
			)
		);

		return $settings;
	}
}
