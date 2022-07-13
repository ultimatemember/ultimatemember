<?php
namespace umm\terms_conditions\includes\common;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Init
 * @package umm\terms_conditions\includes\common
 */
class Init {


	/**
	 * Init constructor.
	 */
	public function __construct() {
	}


	public function includes() {
		$this->forms();
		$this->user();
	}


	/**
	 * @return Forms()
	 */
	public function forms() {
		if ( empty( UM()->classes['umm\terms_conditions\includes\common\forms'] ) ) {
			UM()->classes['umm\terms_conditions\includes\common\forms'] = new Forms();
		}
		return UM()->classes['umm\terms_conditions\includes\common\forms'];
	}


	/**
	 * @return User()
	 */
	public function user() {
		if ( empty( UM()->classes['umm\terms_conditions\includes\common\user'] ) ) {
			UM()->classes['umm\terms_conditions\includes\common\user'] = new User();
		}
		return UM()->classes['umm\terms_conditions\includes\common\user'];
	}
}
