<?php
namespace umm\recaptcha\includes\admin;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Metabox
 *
 * @package umm\recaptcha\includes\admin
 */
class Metabox {


	/**
	 * Metabox constructor.
	 */
	public function __construct() {
		add_action( 'um_admin_add_form_metabox', array( &$this, 'add_form_metaboxes' ), 10, 1 );
		add_filter( 'um_form_meta_map', array( &$this, 'add_form_meta_sanitize' ), 10, 1 );
	}


	/**
	 * @param string $mode
	 */
	public function add_form_metaboxes( $mode ) {
		$module_data = UM()->modules()->get_data( 'recaptcha' );
		if ( ! $module_data ) {
			return;
		}

		switch ( $mode ) {
			case 'login':
				add_meta_box(
					"um-admin-form-login-recaptcha{" . $module_data['path'] . "}",
					__( 'Google reCAPTCHA', 'ultimate-member' ),
					array( UM()->admin()->metabox(), 'load_metabox_form' ),
					'um_form',
					'side',
					'default'
				);
				break;
			case 'register':
				add_meta_box(
					'um-admin-form-register-recaptcha{' . $module_data['path'] . '}',
					__( 'Google reCAPTCHA', 'ultimate-member' ),
					array( UM()->admin()->metabox(), 'load_metabox_form' ),
					'um_form',
					'side',
					'default'
				);
				break;
		}
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
				'_um_login_g_recaptcha_status'    => array(
					'sanitize' => 'text',
				),
				'_um_login_g_recaptcha_score'     => array(
					'sanitize' => 'text',
				),
				'_um_register_g_recaptcha_status' => array(
					'sanitize' => 'text',
				),
				'_um_register_g_recaptcha_score'  => array(
					'sanitize' => 'text',
				),
			)
		);
		return $meta_map;
	}
}
