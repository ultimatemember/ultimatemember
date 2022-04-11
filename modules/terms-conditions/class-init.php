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
	private $slug = 'terms-conditions';


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
		if ( empty( UM()->classes['umm\terms_conditions\includes\common'] ) ) {
			UM()->classes['umm\terms_conditions\includes\common'] = new includes\Common();
		}
		return UM()->classes['umm\terms_conditions\includes\common'];
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
