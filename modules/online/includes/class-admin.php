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
		add_filter( 'um_settings_map', array( &$this, 'add_settings_sanitize' ), 10, 1 );
		add_filter( 'um_module_list_table_actions', array( &$this, 'extend_module_row_actions' ), 10, 2 );
	}


	/**
	 * @param array $actions
	 * @param string $module_slug
	 *
	 * @return array
	 */
	public function extend_module_row_actions( $actions, $module_slug ) {
		if ( 'online' === $module_slug ) {
			$actions = UM()->array_insert_after( $actions, 'docs', array( 'settings' => '<a href="admin.php?page=um_options&tab=modules&section=' . esc_attr( $module_slug ) . '">' . __( 'Settings', 'ultimate-member' ) . '</a>' ) );
		}
		return $actions;
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


	/**
	 * @param array $settings_map
	 *
	 * @return array
	 */
	public function add_settings_sanitize( $settings_map ) {
		$settings_map = array_merge(
			$settings_map,
			array(
				'online_show_stats' => array(
					'sanitize' => 'bool',
				),
			)
		);

		return $settings_map;
	}
}
