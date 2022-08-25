<?php
namespace umm\terms_conditions;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Init
 *
 * @package umm\terms_conditions
 */
final class Init {


	/**
	 * @var string
	 */
	private $slug = 'terms-conditions';


	/**
	 * @var
	 */
	private static $instance;


	/**
	 * @return Init
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	/**
	 * Init constructor.
	 */
	function __construct() {
		$this->common()->includes();
		if ( UM()->is_request( 'admin' ) ) {
			$this->admin()->includes();
		}
	}


	/**
	 * @return includes\common\Init()
	 */
	function common() {
		if ( empty( UM()->classes['umm\terms_conditions\includes\common\init'] ) ) {
			UM()->classes['umm\terms_conditions\includes\common\init'] = new includes\common\Init();
		}
		return UM()->classes['umm\terms_conditions\includes\common\init'];
	}


	/**
	 * @return includes\admin\Init()
	 */
	function admin() {
		if ( empty( UM()->classes['umm\terms_conditions\includes\admin\init'] ) ) {
			UM()->classes['umm\terms_conditions\includes\admin\init'] = new includes\admin\Init();
		}
		return UM()->classes['umm\terms_conditions\includes\admin\init'];
	}
}
