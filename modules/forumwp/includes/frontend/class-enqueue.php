<?php
namespace umm\forumwp\includes\frontend;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Enqueue
 *
 * @package umm\forumwp\includes\frontend
 */
class Enqueue {


	/**
	 * Enqueue constructor.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( &$this, 'register_scripts' ) );
	}


	/**
	 *
	 */
	public function register_scripts() {
		$data = UM()->modules()->get_data( 'forumwp' );
		if ( empty( $data ) ) {
			return;
		}

		if ( um_is_predefined_page( 'user' ) ) {
			wp_register_style( 'um-forumwp', $data['url'] . 'assets/css/profile' . UM()->frontend()->enqueue()->suffix . '.css', array(), UM_VERSION );
			wp_enqueue_style( 'um-forumwp' );
		}
	}
}
