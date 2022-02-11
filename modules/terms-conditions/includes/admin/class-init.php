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
		add_action( 'um_admin_custom_register_metaboxes', array( &$this, 'add_metabox_register' ) );
	}


	/**
	 * @param $action
	 */
	function add_metabox_register( $action ) {
		$module_data = UM()->modules()->get_data( 'terms_conditions' );
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
}
