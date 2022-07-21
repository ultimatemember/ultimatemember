<?php
namespace umm\jobboardwp\includes\frontend;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Init
 *
 * @package umm\jobboardwp\includes\frontend
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
		$this->account();
		$this->enqueue();
		$this->profile();
	}


	/**
	 * @return Account()
	 */
	public function account() {
		if ( empty( UM()->classes['umm\jobboardwp\includes\frontend\account'] ) ) {
			UM()->classes['umm\jobboardwp\includes\frontend\account'] = new Account();
		}
		return UM()->classes['umm\jobboardwp\includes\frontend\account'];
	}


	/**
	 * @return Enqueue()
	 */
	public function enqueue() {
		if ( empty( UM()->classes['umm\jobboardwp\includes\frontend\enqueue'] ) ) {
			UM()->classes['umm\jobboardwp\includes\frontend\enqueue'] = new Enqueue();
		}
		return UM()->classes['umm\jobboardwp\includes\frontend\enqueue'];
	}


	/**
	 * @return Profile()
	 */
	public function profile() {
		if ( empty( UM()->classes['umm\jobboardwp\includes\frontend\profile'] ) ) {
			UM()->classes['umm\jobboardwp\includes\frontend\profile'] = new Profile();
		}
		return UM()->classes['umm\jobboardwp\includes\frontend\profile'];
	}
}
