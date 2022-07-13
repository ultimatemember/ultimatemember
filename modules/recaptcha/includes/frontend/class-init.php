<?php
namespace umm\recaptcha\includes\frontend;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Init
 *
 * @package umm\recaptcha\includes\frontend
 */
class Init {


	/**
	 * Init constructor.
	 */
	public function __construct() {
	}


	public function includes() {
		$this->account();
		$this->enqueue();
		$this->forms()->hooks();
	}


	/**
	 * @return Account()
	 */
	public function account() {
		if ( empty( UM()->classes['umm\recaptcha\includes\frontend\account'] ) ) {
			UM()->classes['umm\recaptcha\includes\frontend\account'] = new Account();
		}
		return UM()->classes['umm\recaptcha\includes\frontend\account'];
	}


	/**
	 * @return Enqueue()
	 */
	public function enqueue() {
		if ( empty( UM()->classes['umm\recaptcha\includes\frontend\enqueue'] ) ) {
			UM()->classes['umm\recaptcha\includes\frontend\enqueue'] = new Enqueue();
		}
		return UM()->classes['umm\recaptcha\includes\frontend\enqueue'];
	}


	/**
	 * @return Forms()
	 */
	public function forms() {
		if ( empty( UM()->classes['umm\recaptcha\includes\frontend\forms'] ) ) {
			UM()->classes['umm\recaptcha\includes\frontend\forms'] = new Forms();
		}
		return UM()->classes['umm\recaptcha\includes\frontend\forms'];
	}
}
