<?php
namespace umm\member_directory;


if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class Init
 *
 * @package umm\member_directory
 */
final class Init {


	/**
	 * @var string
	 */
	private $slug = 'member_directory';


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
		// common classes
		$this->common();

		if ( UM()->is_request( 'admin' ) ) {
			$this->admin();
		}
	}


	/**
	 * @return includes\Common()
	 */
	function common() {
		if ( empty( UM()->classes['umm\member_directory\includes\common'] ) ) {
			UM()->classes['umm\member_directory\includes\common'] = new includes\Common();
		}
		return UM()->classes['umm\member_directory\includes\common'];
	}


	/**
	 * @return includes\admin\Init()
	 */
	function admin() {
		if ( empty( UM()->classes['umm\member_directory\includes\admin\init'] ) ) {
			UM()->classes['umm\member_directory\includes\admin\init'] = new includes\admin\Init();
		}
		return UM()->classes['umm\member_directory\includes\admin\init'];
	}
}
