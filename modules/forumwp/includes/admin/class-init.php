<?php
namespace umm\forumwp\includes\admin;


if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class Init
 *
 * @package umm\forumwp\includes\admin
 */
class Init {


	/**
	 * Init constructor.
	 */
	public function __construct() {
	}


	public function includes() {
		$this->enqueue();
		$this->metabox();
	}


	/**
	 * @return Enqueue()
	 */
	public function enqueue() {
		if ( empty( UM()->classes['umm\forumwp\includes\admin\enqueue'] ) ) {
			UM()->classes['umm\forumwp\includes\admin\enqueue'] = new Enqueue();
		}
		return UM()->classes['umm\forumwp\includes\admin\enqueue'];
	}


	/**
	 * @return Metabox()
	 */
	public function metabox() {
		if ( empty( UM()->classes['umm\forumwp\includes\admin\metabox'] ) ) {
			UM()->classes['umm\forumwp\includes\admin\metabox'] = new Metabox();
		}
		return UM()->classes['umm\forumwp\includes\admin\metabox'];
	}
}
