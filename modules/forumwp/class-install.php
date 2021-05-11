<?php
namespace umm\forumwp;


if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class Install
 *
 * @package umm\forumwp
 */
class Install {


	/**
	 * @var array Default module settings
	 */
	var $settings_defaults;


	/**
	 * Install constructor.
	 */
	function __construct() {
		//settings defaults
		$this->settings_defaults = [

		];
	}


	/**
	 *
	 */
	function start() {
		UM()->options()->set_defaults( $this->settings_defaults );
	}
}