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
