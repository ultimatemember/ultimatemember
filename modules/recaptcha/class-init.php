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
		} elseif ( UM()->is_request( 'frontend' ) ) {
			$this->frontend()->includes();
		}

		$this->cross_modules()->includes();
	}


	/**
	 * @return includes\admin\Init()
	 */
	function common() {
		if ( empty( UM()->classes['umm\recaptcha\includes\common\init'] ) ) {
			UM()->classes['umm\recaptcha\includes\common\init'] = new includes\common\Init();
		}
		return UM()->classes['umm\recaptcha\includes\common\init'];
	}


	/**
	 * @return includes\admin\Init()
	 */
	function admin() {
		if ( empty( UM()->classes['umm\recaptcha\includes\admin\init'] ) ) {
			UM()->classes['umm\recaptcha\includes\admin\init'] = new includes\admin\Init();
		}
		return UM()->classes['umm\recaptcha\includes\admin\init'];
	}


	/**
	 * @return includes\frontend\Init()
	 */
	public function frontend() {
		if ( empty( UM()->classes['umm\recaptcha\includes\frontend\init'] ) ) {
			UM()->classes['umm\recaptcha\includes\frontend\init'] = new includes\frontend\Init();
		}
		return UM()->classes['umm\recaptcha\includes\frontend\init'];
	}


	/**
	 * @return includes\cross_modules\Init()
	 */
	public function cross_modules() {
		if ( empty( UM()->classes['umm\recaptcha\includes\cross_modules\init'] ) ) {
			UM()->classes['umm\recaptcha\includes\cross_modules\init'] = new includes\cross_modules\Init();
		}
		return UM()->classes['umm\recaptcha\includes\cross_modules\init'];
	}


	/**
	 * @return Config()
	 */
	function config() {
		if ( empty( UM()->classes['umm\recaptcha\config'] ) ) {
			UM()->classes['umm\recaptcha\config'] = new Config();
		}
		return UM()->classes['umm\recaptcha\config'];
	}
}
