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
	public function __construct() {
		$this->common()->includes();
		if ( ! UM()->is_request( 'ajax' ) && UM()->is_request( 'admin' ) ) {
			$this->admin()->includes();
		} elseif ( UM()->is_request( 'frontend' ) ) {
			$this->frontend()->includes();
		}
		$this->cross_modules()->includes();
	}


	/**
	 * @return includes\common\Init()
	 */
	public function common() {
		if ( empty( UM()->classes['umm\forumwp\includes\common\init'] ) ) {
			UM()->classes['umm\forumwp\includes\common\init'] = new includes\common\Init();
		}
		return UM()->classes['umm\forumwp\includes\common\init'];
	}


	/**
	 * @return includes\admin\Init()
	 */
	public function admin() {
		if ( empty( UM()->classes['umm\forumwp\includes\admin\init'] ) ) {
			UM()->classes['umm\forumwp\includes\admin\init'] = new includes\admin\Init();
		}
		return UM()->classes['umm\forumwp\includes\admin\init'];
	}


	/**
	 * @return includes\frontend\Init()
	 */
	public function frontend() {
		if ( empty( UM()->classes['umm\forumwp\includes\frontend\init'] ) ) {
			UM()->classes['umm\forumwp\includes\frontend\init'] = new includes\frontend\Init();
		}
		return UM()->classes['umm\forumwp\includes\frontend\init'];
	}


	/**
	 * @return includes\cross_modules\Init()
	 */
	public function cross_modules() {
		if ( empty( UM()->classes['umm\forumwp\includes\cross_modules\init'] ) ) {
			UM()->classes['umm\forumwp\includes\cross_modules\init'] = new includes\cross_modules\Init();
		}
		return UM()->classes['umm\forumwp\includes\cross_modules\init'];
	}
}
