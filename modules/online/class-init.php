<?php
namespace umm\online;


if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class Init
 *
 * @package umm\online
 */
final class Init extends Functions {


	/**
	 * @var string
	 */
	private $slug = 'online';


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
		parent::__construct();

		$this->common()->includes();
		if ( ! UM()->is_request( 'ajax' ) && UM()->is_request( 'admin' ) ) {
			$this->admin()->includes();
		} elseif ( UM()->is_request( 'frontend' ) ) {
			$this->frontend()->includes();
		}
		$this->cross_modules()->includes();

		add_action( 'widgets_init', array( &$this, 'widgets_init' ) );
	}


	/**
	 * @return includes\common\Init()
	 */
	public function common() {
		if ( empty( UM()->classes['umm\online\includes\common\init'] ) ) {
			UM()->classes['umm\online\includes\common\init'] = new includes\common\Init();
		}
		return UM()->classes['umm\online\includes\common\init'];
	}


	/**
	 * @return includes\admin\Init()
	 */
	public function admin() {
		if ( empty( UM()->classes['umm\online\includes\admin\init'] ) ) {
			UM()->classes['umm\online\includes\admin\init'] = new includes\admin\Init();
		}
		return UM()->classes['umm\online\includes\admin\init'];
	}


	/**
	 * @return includes\frontend\Init()
	 */
	public function frontend() {
		if ( empty( UM()->classes['umm\online\includes\frontend\init'] ) ) {
			UM()->classes['umm\online\includes\frontend\init'] = new includes\frontend\Init();
		}
		return UM()->classes['umm\online\includes\frontend\init'];
	}


	/**
	 * @return includes\cross_modules\Init()
	 */
	public function cross_modules() {
		if ( empty( UM()->classes['umm\online\includes\cross_modules\init'] ) ) {
			UM()->classes['umm\online\includes\cross_modules\init'] = new includes\cross_modules\Init();
		}
		return UM()->classes['umm\online\includes\cross_modules\init'];
	}


	/**
	 * Init Online users widget
	 */
	function widgets_init() {
		register_widget( 'umm\online\includes\widgets\Online_List' );
	}
}
