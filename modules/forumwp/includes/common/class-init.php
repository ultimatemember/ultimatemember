<?php
namespace umm\forumwp\includes\common;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Init
 *
 * @package umm\forumwp\includes\common
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
		$this->profile();
		$this->reply();
		$this->topic();
		$this->user();
	}


	/**
	 * @return Profile()
	 */
	public function profile() {
		if ( empty( UM()->classes['umm\forumwp\includes\common\profile'] ) ) {
			UM()->classes['umm\forumwp\includes\common\profile'] = new Profile();
		}
		return UM()->classes['umm\forumwp\includes\common\profile'];
	}


	/**
	 * @return Reply()
	 */
	public function reply() {
		if ( empty( UM()->classes['umm\forumwp\includes\common\reply'] ) ) {
			UM()->classes['umm\forumwp\includes\common\reply'] = new Reply();
		}
		return UM()->classes['umm\forumwp\includes\common\reply'];
	}


	/**
	 * @return Topic()
	 */
	public function topic() {
		if ( empty( UM()->classes['umm\forumwp\includes\common\topic'] ) ) {
			UM()->classes['umm\forumwp\includes\common\topic'] = new Topic();
		}
		return UM()->classes['umm\forumwp\includes\common\topic'];
	}


	/**
	 * @return User()
	 */
	public function user() {
		if ( empty( UM()->classes['umm\forumwp\includes\common\user'] ) ) {
			UM()->classes['umm\forumwp\includes\common\user'] = new User();
		}
		return UM()->classes['umm\forumwp\includes\common\user'];
	}
}
