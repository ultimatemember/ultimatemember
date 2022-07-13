<?php
namespace umm\online\includes\frontend;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Init
 *
 * @package umm\online\includes\frontend
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
		$this->shortcode();
	}


	/**
	 * @return Account()
	 */
	public function account() {
		if ( empty( UM()->classes['umm\online\includes\frontend\account'] ) ) {
			UM()->classes['umm\online\includes\frontend\account'] = new Account();
		}
		return UM()->classes['umm\online\includes\frontend\account'];
	}


	/**
	 * @return Enqueue()
	 */
	public function enqueue() {
		if ( empty( UM()->classes['umm\online\includes\frontend\enqueue'] ) ) {
			UM()->classes['umm\online\includes\frontend\enqueue'] = new Enqueue();
		}
		return UM()->classes['umm\online\includes\frontend\enqueue'];
	}


	/**
	 * @return Profile()
	 */
	public function profile() {
		if ( empty( UM()->classes['umm\online\includes\frontend\profile'] ) ) {
			UM()->classes['umm\online\includes\frontend\profile'] = new Profile();
		}
		return UM()->classes['umm\online\includes\frontend\profile'];
	}


	/**
	 * @return Shortcode()
	 */
	public function shortcode() {
		if ( empty( UM()->classes['umm\online\includes\frontend\shortcode'] ) ) {
			UM()->classes['umm\online\includes\frontend\shortcode'] = new Shortcode();
		}
		return UM()->classes['umm\online\includes\frontend\shortcode'];
	}
}
