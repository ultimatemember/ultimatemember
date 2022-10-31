<?php
namespace umm\recaptcha\includes\common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Init
 *
 * @package umm\recaptcha\includes\common
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
		$this->um_login_form();
		$this->wp_login_php();
	}

	/**
	 * @return UM_Login_Form()
	 */
	public function um_login_form() {
		if ( empty( UM()->classes['umm\recaptcha\includes\common\um_login_form'] ) ) {
			UM()->classes['umm\recaptcha\includes\common\um_login_form'] = new UM_Login_Form();
		}
		return UM()->classes['umm\recaptcha\includes\common\um_login_form'];
	}

	/**
	 * @return WP_Login_PHP()
	 */
	public function wp_login_php() {
		if ( empty( UM()->classes['umm\recaptcha\includes\common\wp_login_php'] ) ) {
			UM()->classes['umm\recaptcha\includes\common\wp_login_php'] = new WP_Login_PHP();
		}
		return UM()->classes['umm\recaptcha\includes\common\wp_login_php'];
	}
}
