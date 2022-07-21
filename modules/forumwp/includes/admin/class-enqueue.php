<?php
namespace umm\forumwp\includes\admin;


if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class Enqueue
 *
 * @package umm\forumwp\includes\admin
 */
class Enqueue {


	/**
	 * Enqueue constructor.
	 */
	public function __construct() {
		add_filter( 'um_is_ultimatememeber_admin_screen', array( &$this, 'is_um_screen' ), 10, 1 );
	}


	/**
	 * Extends UM admin pages for enqueue scripts
	 *
	 * @param $is_um
	 *
	 * @return bool
	 */
	public function is_um_screen( $is_um ) {
		global $current_screen;

		if ( empty( $current_screen ) || empty( $current_screen->id ) ) {
			return $is_um;
		}

		if ( strstr( $current_screen->id, 'fmwp_forum' ) ) {
			$is_um = true;
		}

		return $is_um;
	}
}
