<?php
namespace umm\online\includes;


if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class Enqueue
 *
 * @package umm\online\includes
 */
class Enqueue {


	/**
	 * Enqueue constructor.
	 */
	function __construct() {
		add_filter( 'um_modules_min_styles_dependencies', array( &$this, 'extends_styles_dependencies' ), 10, 1 );
	}


	/**
	 * @param array $deps
	 *
	 * @return array
	 */
	function extends_styles_dependencies( $deps = array() ) {
		$deps = array_merge( $deps, array( 'um_styles' ) );
		return $deps;
	}
}
