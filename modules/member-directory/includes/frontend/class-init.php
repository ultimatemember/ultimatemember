<?php
namespace umm\member_directory\includes\frontend;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Init
 *
 * @package umm\member_directory\includes\frontend
 */
class Init extends Helpers {


	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		parent::__construct();
	}


	function includes() {
		$this->shortcode();
	}


	/**
	 * @return Shortcode()
	 */
	function shortcode() {
		if ( empty( UM()->classes['umm\member_directory\includes\frontend\shortcode'] ) ) {
			UM()->classes['umm\member_directory\includes\frontend\shortcode'] = new Shortcode();
		}
		return UM()->classes['umm\member_directory\includes\frontend\shortcode'];
	}
}
