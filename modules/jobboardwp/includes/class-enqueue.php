<?php
namespace umm\jobboardwp\includes;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Enqueue
 *
 * @package umm\jobboardwp\includes
 */
class Enqueue {


	/**
	 * Enqueue constructor.
	 */
	function __construct() {
		add_filter( 'um_modules_min_scripts_dependencies', array( &$this, 'extends_scripts_dependencies' ), 10, 1 );
	}


	/**
	 * @param array $deps
	 *
	 * @return array
	 */
	function extends_scripts_dependencies( $deps = array() ) {
		$deps[] = 'jb-front-global';
		return $deps;
	}
}
