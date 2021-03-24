<?php
namespace umm\forumwp;


if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class Init
 *
 * @package umm\forumwp
 */
final class Init {


	/**
	 * @var string
	 */
	private $slug = 'forumwp';


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
		if ( empty( UM()->classes['umm\forumwp\includes\form'] ) ) {
			UM()->classes['umm\forumwp\includes\form'] = new includes\Form();
		}
		return UM()->classes['umm\forumwp\includes\form'];
	}




}