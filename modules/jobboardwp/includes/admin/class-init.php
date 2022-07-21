<?php
namespace umm\jobboardwp\includes\admin;


if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class Init
 *
 * @package umm\jobboardwp\includes\admin
 */
class Init {


	/**
	 * Init constructor.
	 */
	function __construct() {
		add_filter( 'um_settings_structure', array( &$this, 'extend_settings' ), 10, 1 );
		add_filter( 'um_settings_map', array( &$this, 'add_settings_sanitize' ), 10, 1 );
		add_filter( 'um_admin_role_metaboxes', array( &$this, 'add_role_metabox' ), 10, 1 );
		add_filter( 'um_role_meta_map', array( &$this, 'add_role_meta_sanitize' ), 10, 1 );
		add_filter( 'debug_information', array( $this, 'debug_information' ), 20, 1 );
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


	/**
	 * Creates options in Role page
	 *
	 * @param array $roles_metaboxes
	 *
	 * @return array
	 *
	 * @since 1.0
	 */
	function add_role_metabox( $roles_metaboxes ) {
		$module_data = UM()->modules()->get_data( 'jobboardwp' );
		if ( ! $module_data ) {
			return $roles_metaboxes;
		}

		$roles_metaboxes[] = array(
			'id'        => 'um-admin-form-jobboardwp{' . $module_data['path'] . '}',
			'title'     => __( 'JobBoardWP', 'ultimate-member' ),
			'callback'  => array( UM()->admin()->metabox(), 'load_metabox_role' ),
			'screen'    => 'um_role_meta',
			'context'   => 'normal',
			'priority'  => 'default',
		);

		return $roles_metaboxes;
	}


	/**
	 * @param array $meta_map
	 *
	 * @return array
	 */
	public function add_role_meta_sanitize( $meta_map ) {
		$meta_map = array_merge(
			$meta_map,
			array(
				'_um_disable_jobs_tab' => array(
					'sanitize' => 'bool',
				),
			)
		);
		return $meta_map;
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
}
