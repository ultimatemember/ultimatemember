<?php
namespace umm\jobboardwp\includes\admin;


if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class Init
 *
 * @package umm\jobboardwp\includes\admin
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
		$this->metabox();
		$this->settings();
		$this->site_health();
	}


	/**
	 * @return Metabox()
	 */
	public function metabox() {
		if ( empty( UM()->classes['umm\jobboardwp\includes\admin\metabox'] ) ) {
			UM()->classes['umm\jobboardwp\includes\admin\metabox'] = new Metabox();
		}
		return UM()->classes['umm\jobboardwp\includes\admin\metabox'];
	}


	/**
	 * @return Settings()
	 */
	public function settings() {
		if ( empty( UM()->classes['umm\jobboardwp\includes\admin\settings'] ) ) {
			UM()->classes['umm\jobboardwp\includes\admin\settings'] = new Settings();
		}
		return UM()->classes['umm\jobboardwp\includes\admin\settings'];
	}


	/**
	 * @return Site_Health()
	 */
	public function site_health() {
		if ( empty( UM()->classes['umm\jobboardwp\includes\admin\site_health'] ) ) {
			UM()->classes['umm\jobboardwp\includes\admin\site_health'] = new Site_Health();
		}
		return UM()->classes['umm\jobboardwp\includes\admin\site_health'];
	}
}
