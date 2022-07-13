<?php
namespace umm\forumwp\includes\frontend;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Init
 *
 * @package umm\forumwp\includes\frontend
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
		$this->enqueue();
		$this->profile();
		$this->user();
	}


	/**
	 * @return Enqueue()
	 */
	public function enqueue() {
		if ( empty( UM()->classes['umm\forumwp\includes\frontend\enqueue'] ) ) {
			UM()->classes['umm\forumwp\includes\frontend\enqueue'] = new Enqueue();
		}
		return UM()->classes['umm\forumwp\includes\frontend\enqueue'];
	}


	/**
	 * @return Profile()
	 */
	public function profile() {
		if ( empty( UM()->classes['umm\forumwp\includes\frontend\profile'] ) ) {
			UM()->classes['umm\forumwp\includes\frontend\profile'] = new Profile();
		}
		return UM()->classes['umm\forumwp\includes\frontend\profile'];
	}


	/**
	 * @return User()
	 */
	public function user() {
		if ( empty( UM()->classes['umm\forumwp\includes\frontend\user'] ) ) {
			UM()->classes['umm\forumwp\includes\frontend\user'] = new User();
		}
		return UM()->classes['umm\forumwp\includes\frontend\user'];
	}
}
