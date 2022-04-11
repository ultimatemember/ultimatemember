<?php
namespace umm\member_directory\includes\frontend;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Enqueue
 *
 * @package umm\member_directory\includes\frontend
 */
class Enqueue {


	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( &$this, 'register' ), UM()->frontend()->enqueue()->get_priority() + 1 );
	}


	/**
	 * frontend assets registration
	 */
	function register() {
		$data = UM()->modules()->get_data( 'member-directory' );

		wp_register_script('um_members', $data['url'] . 'assets/js/um-members' . UM()->frontend()->enqueue()->suffix . '.js', array( 'jquery', 'jquery-ui-slider', 'wp-hooks', 'jquery-masonry', 'um_scripts' ), UM_VERSION, true );
		wp_enqueue_script( 'um_members' );

		wp_register_style( 'um_members', $data['url'] . 'assets/css/um-members' . UM()->frontend()->enqueue()->suffix . '.css', array( 'um_styles', 'um-tipsy' ), UM_VERSION );
		wp_enqueue_style( 'um_members' );

		if ( is_rtl() ) {
			wp_register_style( 'um_members_rtl', $data['url'] . 'assets/css/um-members-rtl' . UM()->frontend()->enqueue()->suffix . '.css', array( 'um_members' ), UM_VERSION );
			wp_enqueue_style( 'um_members_rtl' );
		}
	}
}
