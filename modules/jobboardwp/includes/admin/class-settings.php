<?php
namespace umm\jobboardwp\includes\admin;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Settings
 *
 * @package umm\jobboardwp\includes\admin
 */
class Settings {


	/**
	 * Settings constructor.
	 */
	function __construct() {
		add_filter( 'um_settings_structure', array( &$this, 'extend_settings' ), 10, 1 );
		add_filter( 'um_settings_map', array( &$this, 'add_settings_sanitize' ), 10, 1 );
	}


	/**
	 * Extend settings
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	function extend_settings( $settings ) {
		$settings['modules']['sections']['jobboardwp'] = array(
			'title'  => __( 'JobBoardWP', 'ultimate-member' ),
			'fields' => array(
				array(
					'id'          => 'account_tab_jobboardwp',
					'type'        => 'checkbox',
					'label'       => __( 'Account Tab', 'ultimate-member' ),
					'description' => __( 'Show or hide an account tab that shows the jobs dashboard.', 'ultimate-member' ),
				),
			),
		);

		return $settings;
	}


	/**
	 * @param array $settings_map
	 *
	 * @return array
	 */
	public function add_settings_sanitize( $settings_map ) {
		$settings_map = array_merge(
			$settings_map,
			array(
				'account_tab_jobboardwp' => array(
					'sanitize' => 'bool',
				),
			)
		);

		return $settings_map;
	}
}
