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
		add_filter( 'um_module_list_table_actions', array( &$this, 'extend_module_row_actions' ), 10, 2 );
		add_filter( 'um_admin_role_metaboxes', array( &$this, 'add_role_metabox' ), 10, 1 );
	}


	public function extend_module_row_actions( $actions, $module_slug ) {
		if ( 'jobboardwp' === $module_slug ) {
			$actions = UM()->array_insert_after( $actions, 'docs', array( 'settings' => '<a href="admin.php?page=um_options&tab=modules&section=' . esc_attr( $module_slug ) . '">' . __( 'Settings', 'ultimate-member' ) . '</a>' ) );
		}
		return $actions;
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
}
