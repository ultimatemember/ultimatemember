<?php
namespace umm\online\includes\admin;


if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class Init
 *
 * @package umm\online\includes\admin
 */
class Init {


	/**
	 * Init constructor.
	 */
	public function __construct() {
	}


	public function includes() {
		$this->enqueue();
		$this->settings();
		$this->site_health();
	}


	/**
	 * @since 3.0
	 *
	 * @return Site_Health
	 */
	function site_health() {
		if ( empty( UM()->classes['umm\online\includes\admin\site_health'] ) ) {
			UM()->classes['umm\online\includes\admin\site_health'] = new Site_Health();
		}
		return UM()->classes['umm\online\includes\admin\site_health'];
	}


	/**
	 * @return Enqueue()
	 */
	public function enqueue() {
		if ( empty( UM()->classes['umm\online\includes\admin\enqueue'] ) ) {
			UM()->classes['umm\online\includes\admin\enqueue'] = new Enqueue();
		}
		return UM()->classes['umm\online\includes\admin\enqueue'];
	}


	/**
	 * @return Settings()
	 */
	public function settings() {
		if ( empty( UM()->classes['umm\online\includes\admin\settings'] ) ) {
			UM()->classes['umm\online\includes\admin\settings'] = new Settings();
		}
		return UM()->classes['umm\online\includes\admin\settings'];
	}
}
