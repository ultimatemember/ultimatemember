<?php
namespace umm\recaptcha\includes\cross_modules;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Init
 *
 * @package umm\recaptcha\includes\cross_modules
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
		$this->private_messages();
	}


	/**
	 * @return null|Private_Messages()
	 */
	function private_messages() {
		if ( ! UM()->modules()->is_active( 'private-messages' ) ) {
			return null;
		}

		if ( empty( UM()->classes['umm\recaptcha\includes\cross_modules\private_messages'] ) ) {
			UM()->classes['umm\recaptcha\includes\cross_modules\private_messages'] = new Private_Messages();
		}
		return UM()->classes['umm\recaptcha\includes\cross_modules\private_messages'];
	}
}
