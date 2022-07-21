<?php
namespace umm\jobboardwp\includes\admin;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Metabox
 *
 * @package umm\jobboardwp\includes\admin
 */
class Metabox {


	/**
	 * Metabox constructor.
	 */
	public function __construct() {
		add_filter( 'um_admin_role_metaboxes', array( &$this, 'add_role_metabox' ), 10, 1 );
		add_filter( 'um_role_meta_map', array( &$this, 'add_role_meta_sanitize' ), 10, 1 );
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
	public function add_role_metabox( $roles_metaboxes ) {
		$module_data = UM()->modules()->get_data( 'jobboardwp' );
		if ( ! $module_data ) {
			return $roles_metaboxes;
		}

		$roles_metaboxes[] = array(
			'id'       => 'um-admin-form-jobboardwp{' . $module_data['path'] . '}',
			'title'    => __( 'JobBoardWP', 'ultimate-member' ),
			'callback' => array( UM()->admin()->metabox(), 'load_metabox_role' ),
			'screen'   => 'um_role_meta',
			'context'  => 'normal',
			'priority' => 'default',
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
}
