<?php
namespace umm\terms_conditions\includes\admin;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Init
 * @package umm\terms_conditions\includes\admin
 */
class Init {


	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		add_action( 'um_admin_add_form_metabox', array( &$this, 'add_metabox_register' ) );
		add_filter( 'um_form_meta_map', array( &$this, 'add_form_meta_sanitize' ), 10, 1 );
	}


	public function add_metabox_register() {
		$module_data = UM()->modules()->get_data( 'terms-conditions' );
		if ( ! $module_data ) {
			return;
		}

		add_meta_box(
			"um-admin-form-register_terms-conditions{" . $module_data['path'] . "}",
			__( 'Terms & Conditions', 'ultimate-member' ),
			array( UM()->admin()->metabox(), 'load_metabox_form' ),
			'um_form',
			'side',
			'default'
		);
	}


	/**
	 * @param array $meta_map
	 *
	 * @return array
	 */
	public function add_form_meta_sanitize( $meta_map ) {
		$meta_map = array_merge(
			$meta_map,
			array(
				'_um_register_use_terms_conditions'             => array(
					'sanitize' => 'bool',
				),
				'_um_register_use_terms_conditions_content_id'  => array(
					'sanitize' => 'absint',
				),
				'_um_register_use_terms_conditions_toggle_show' => array(
					'sanitize' => 'text',
				),
				'_um_register_use_terms_conditions_toggle_hide' => array(
					'sanitize' => 'text',
				),
				'_um_register_use_terms_conditions_agreement'   => array(
					'sanitize' => 'text',
				),
				'_um_register_use_terms_conditions_error_text'  => array(
					'sanitize' => 'text',
				),
			)
		);
		return $meta_map;
	}
}
