<?php
namespace umm\jobboardwp\includes\frontend;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Enqueue
 *
 * @package umm\jobboardwp\includes\frontend
 */
class Enqueue {


	/**
	 * Enqueue constructor.
	 */
	function __construct() {
		add_filter( 'um_modules_min_scripts_dependencies', array( &$this, 'extends_scripts_dependencies' ), 10, 1 );
		add_filter( 'um_account_scripts_dependencies', array( &$this, 'add_js_scripts' ), 10, 1 );
	}


	/**
	 * @param array $deps
	 *
	 * @return array
	 */
	function extends_scripts_dependencies( $deps = array() ) {
		$deps[] = 'jb-front-global';
		return $deps;
	}


	/**
	 * @param array $scripts
	 *
	 * @return array
	 *
	 * @since 1.0
	 */
	function add_js_scripts( $scripts ) {
		$data = UM()->modules()->get_data( 'jobboardwp' );
		if ( empty( $data ) ) {
			return $scripts;
		}

		wp_register_script('um-jb-account', $data['url'] . 'assets/js/account' . UM()->frontend()->enqueue()->suffix . '.js', array( 'wp-hooks' ), UM_VERSION, true );

		$scripts[] = 'um-jb-account';
		return $scripts;
	}
}
