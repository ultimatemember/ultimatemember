<?php
namespace umm\recaptcha\includes\admin;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Init
 *
 * @package umm\recaptcha\includes\admin
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
		$this->notices();
		$this->settings();
		$this->site_health();
	}


	/**
	 * @since 3.0
	 *
	 * @return Site_Health
	 */
	function site_health() {
		if ( empty( UM()->classes['umm\recaptcha\includes\admin\site_health'] ) ) {
			UM()->classes['umm\recaptcha\includes\admin\site_health'] = new Site_Health();
		}
		return UM()->classes['umm\recaptcha\includes\admin\site_health'];
	}


	/**
	 * @return Metabox()
	 */
	public function metabox() {
		if ( empty( UM()->classes['umm\recaptcha\includes\admin\metabox'] ) ) {
			UM()->classes['umm\recaptcha\includes\admin\metabox'] = new Metabox();
		}
		return UM()->classes['umm\recaptcha\includes\admin\metabox'];
	}


	/**
	 * @return Notices()
	 */
	public function notices() {
		if ( empty( UM()->classes['umm\recaptcha\includes\admin\notices'] ) ) {
			UM()->classes['umm\recaptcha\includes\admin\notices'] = new Notices();
		}
		return UM()->classes['umm\recaptcha\includes\admin\notices'];
	}


	/**
	 * @return Settings()
	 */
	public function settings() {
		if ( empty( UM()->classes['umm\recaptcha\includes\admin\settings'] ) ) {
			UM()->classes['umm\recaptcha\includes\admin\settings'] = new Settings();
		}
		return UM()->classes['umm\recaptcha\includes\admin\settings'];
	}
}
