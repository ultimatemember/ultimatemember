<?php
namespace umm\terms_conditions\includes\admin;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Init
 * @package umm\terms_conditions\includes\admin
 */
class Init {


	/**
	 * Init constructor.
	 */
	public function __construct() {
	}


	public function includes() {
		$this->metabox();
		$this->site_health();
	}


	/**
	 * @since 3.0
	 *
	 * @return Site_Health
	 */
	function site_health() {
		if ( empty( UM()->classes['umm\terms_conditions\includes\admin\site_health'] ) ) {
			UM()->classes['umm\terms_conditions\includes\admin\site_health'] = new Site_Health();
		}
		return UM()->classes['umm\terms_conditions\includes\admin\site_health'];
	}


	/**
	 * @return Metabox()
	 */
	public function metabox() {
		if ( empty( UM()->classes['umm\terms_conditions\includes\admin\metabox'] ) ) {
			UM()->classes['umm\terms_conditions\includes\admin\metabox'] = new Metabox();
		}
		return UM()->classes['umm\terms_conditions\includes\admin\metabox'];
	}
}
