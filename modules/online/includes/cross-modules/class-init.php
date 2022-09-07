<?php namespace umm\online\includes\cross_modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Init
 *
 * @package umm\online\includes\cross_modules
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
		$this->member_directory();
	}

	/**
	 * @return null|Member_Directory()
	 */
	function member_directory() {
		if ( ! UM()->modules()->is_active( 'member-directory' ) ) {
			return null;
		}

		if ( empty( UM()->classes['umm\online\includes\cross_modules\member_directory'] ) ) {
			UM()->classes['umm\online\includes\cross_modules\member_directory'] = new Member_Directory();
		}
		return UM()->classes['umm\online\includes\cross_modules\member_directory'];
	}
}
