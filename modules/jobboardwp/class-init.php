<?php
namespace umm\jobboardwp;


if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class Init
 *
 * @package umm\jobboardwp
 */
final class Init {


	/**
	 * @var string
	 */
	private $slug = 'jobboardwp';


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
		if ( empty( UM()->classes['umm\jobboardwp\includes\form'] ) ) {
			UM()->classes['umm\jobboardwp\includes\form'] = new includes\Form();
		}
		return UM()->classes['umm\jobboardwp\includes\form'];
	}




}