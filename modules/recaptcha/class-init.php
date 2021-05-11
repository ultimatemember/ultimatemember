<?php
namespace umm\recaptcha;


if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class Init
 *
 * @package umm\recaptcha
 */
final class Init {


	/**
	 * @var string
	 */
	private $slug = 'recaptcha';


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
		if ( empty( UM()->classes['umm\recaptcha\includes\form'] ) ) {
			UM()->classes['umm\recaptcha\includes\form'] = new includes\Form();
		}
		return UM()->classes['umm\recaptcha\includes\form'];
	}




}