<?php
namespace umm\member_directory\includes\admin;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Init
 *
 * @package umm\member_directory\includes\admin
 */
class Init {


	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
	}


	function includes() {
		$this->menu();
		$this->enqueue();
		$this->metabox();
		$this->forms();
		$this->settings();
		$this->columns();
		$this->site_health();
	}


	/**
	 * @since 3.0
	 *
	 * @return Site_Health
	 */
	function site_health() {
		if ( empty( UM()->classes['umm\member_directory\includes\admin\site_health'] ) ) {
			UM()->classes['umm\member_directory\includes\admin\site_health'] = new Site_Health();
		}
		return UM()->classes['umm\member_directory\includes\admin\site_health'];
	}


	/**
	 * @return Columns()
	 */
	function columns() {
		if ( empty( UM()->classes['umm\member_directory\includes\admin\columns'] ) ) {
			UM()->classes['umm\member_directory\includes\admin\columns'] = new Columns();
		}
		return UM()->classes['umm\member_directory\includes\admin\columns'];
	}


	/**
	 * @return Menu()
	 */
	function menu() {
		if ( empty( UM()->classes['umm\member_directory\includes\admin\menu'] ) ) {
			UM()->classes['umm\member_directory\includes\admin\menu'] = new Menu();
		}
		return UM()->classes['umm\member_directory\includes\admin\menu'];
	}


	/**
	 * @return Settings()
	 */
	function settings() {
		if ( empty( UM()->classes['umm\member_directory\includes\admin\settings'] ) ) {
			UM()->classes['umm\member_directory\includes\admin\settings'] = new Settings();
		}
		return UM()->classes['umm\member_directory\includes\admin\settings'];
	}


	/**
	 * @return Forms()
	 */
	function forms() {
		if ( empty( UM()->classes['umm\member_directory\includes\admin\forms'] ) ) {
			UM()->classes['umm\member_directory\includes\admin\forms'] = new Forms();
		}
		return UM()->classes['umm\member_directory\includes\admin\forms'];
	}


	/**
	 * @return Metabox()
	 */
	function metabox() {
		if ( empty( UM()->classes['umm\member_directory\includes\admin\metabox'] ) ) {
			UM()->classes['umm\member_directory\includes\admin\metabox'] = new Metabox();
		}
		return UM()->classes['umm\member_directory\includes\admin\metabox'];
	}


	/**
	 * @return Enqueue()
	 */
	function enqueue() {
		if ( empty( UM()->classes['umm\member_directory\includes\admin\enqueue'] ) ) {
			UM()->classes['umm\member_directory\includes\admin\enqueue'] = new Enqueue();
		}
		return UM()->classes['umm\member_directory\includes\admin\enqueue'];
	}
}
