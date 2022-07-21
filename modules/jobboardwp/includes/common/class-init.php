<?php
namespace umm\jobboardwp\includes\common;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Init
 *
 * @package umm\jobboardwp\includes\common
 */
class Init {


	/**
	 * Init constructor.
	 */
	public function __construct() {
	}


	/**
	 *
	 */
	public function includes() {
		$this->profile();
	}


	/**
	 * @return Profile()
	 */
	public function profile() {
		if ( empty( UM()->classes['umm\jobboardwp\includes\common\profile'] ) ) {
			UM()->classes['umm\jobboardwp\includes\common\profile'] = new Profile();
		}
		return UM()->classes['umm\jobboardwp\includes\common\profile'];
	}
}
