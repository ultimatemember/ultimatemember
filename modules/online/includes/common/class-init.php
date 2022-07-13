<?php
namespace umm\online\includes\common;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Init
 *
 * @package umm\online\includes\common
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
		$this->fields();
		// don't use construct here because there are helper functions inside User class
		$this->user()->hooks();
	}


	/**
	 * @return Fields()
	 */
	public function fields() {
		if ( empty( UM()->classes['umm\online\includes\common\fields'] ) ) {
			UM()->classes['umm\online\includes\common\fields'] = new Fields();
		}
		return UM()->classes['umm\online\includes\common\fields'];
	}


	/**
	 * @return REST()
	 */
	public function rest() {
		if ( empty( UM()->classes['umm\online\includes\common\rest'] ) ) {
			UM()->classes['umm\online\includes\common\rest'] = new REST();
		}
		return UM()->classes['umm\online\includes\common\rest'];
	}


	/**
	 * @return User()
	 */
	public function user() {
		if ( empty( UM()->classes['umm\online\includes\common\user'] ) ) {
			UM()->classes['umm\online\includes\common\user'] = new User();
		}
		return UM()->classes['umm\online\includes\common\user'];
	}
}
