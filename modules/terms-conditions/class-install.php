<?php
namespace umm\terms_conditions;


if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class Install
 *
 * @package umm\terms_conditions
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
		$this->settings_defaults = array();
	}


	/**
	 *
	 */
	function start() {
		UM()->options()->set_defaults( $this->settings_defaults );
	}
}