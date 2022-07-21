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
		$this->friends();
		$this->member_directory();
		$this->private_messages();
	}


	/**
	 * @return null|Friends()
	 */
	function friends() {
		if ( ! UM()->modules()->is_active( 'friends' ) ) {
			return null;
		}

		if ( empty( UM()->classes['umm\online\includes\cross_modules\friends'] ) ) {
			UM()->classes['umm\online\includes\cross_modules\friends'] = new Friends();
		}
		return UM()->classes['umm\online\includes\cross_modules\friends'];
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


	/**
	 * @return null|Private_Messages()
	 */
	function private_messages() {
		if ( ! UM()->modules()->is_active( 'private-messages' ) ) {
			return null;
		}

		if ( empty( UM()->classes['umm\online\includes\cross_modules\private_messages'] ) ) {
			UM()->classes['umm\online\includes\cross_modules\private_messages'] = new Private_Messages();
		}
		return UM()->classes['umm\online\includes\cross_modules\private_messages'];
	}
}
