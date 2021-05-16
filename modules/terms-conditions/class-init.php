<?php
namespace umm\terms_conditions;


if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class Init
 *
 * @package umm\terms_conditions
 */
final class Init {


	/**
	 * @var string
	 */
	private $slug = 'terms_conditions';


	/**
	 * @var
	 */
	private static $instance;


	/**
	 * @return Init
	 */
	static public function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	/**
	 * Init constructor.
	 */
	function __construct() {

	}


	/**
	 * @return includes\Form()
	 */
	function form() {
		if ( empty( UM()->classes['umm\terms_conditions\includes\form'] ) ) {
			UM()->classes['umm\terms_conditions\includes\form'] = new includes\Form();
		}
		return UM()->classes['umm\terms_conditions\includes\form'];
	}




}