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
		$this->site_health();
	}


	/**
	 * @since 3.0
	 *
	 * @return Site_Health
	 */
	function site_health() {
		if ( empty( UM()->classes['umm\forumwp\includes\admin\site_health'] ) ) {
			UM()->classes['umm\forumwp\includes\admin\site_health'] = new Site_Health();
		}
		return UM()->classes['umm\forumwp\includes\admin\site_health'];
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
