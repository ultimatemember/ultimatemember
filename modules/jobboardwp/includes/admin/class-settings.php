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
	public function __construct() {
		add_filter( 'um_settings_structure', array( &$this, 'extend_settings' ), 20, 1 );
		add_filter( 'um_settings_map', array( &$this, 'add_settings_sanitize' ), 10, 1 );
	}

	/**
	 * Extend settings
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public function extend_settings( $settings ) {
		$account_fields = array(
			array(
				'type'          => 'separator',
				'value'         => __( 'JobBoard tab', 'ultimate-member' ),
				'without_label' => 1,
			),
			array(
				'id'          => 'account_tab_jobboardwp',
				'type'        => 'checkbox',
				'label'       => __( 'Jobs Dashboard Account Tab', 'ultimate-member' ),
				'description' => __( 'Enable/disable the Jobs Dashboard account tab on the account page.', 'ultimate-member' ),
			),
		);

		$settings['']['sections']['account']['fields'] = array_merge( $settings['']['sections']['account']['fields'], $account_fields );

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
