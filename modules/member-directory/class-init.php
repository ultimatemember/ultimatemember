<?php
namespace umm\member_directory;


if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class Init
 *
 * @package umm\member_directory
 */
final class Init extends Functions {


	/**
	 * @var string
	 */
	private $slug = 'member-directory';


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
		parent::__construct();

		$this->common()->includes();
		if ( UM()->is_request( 'ajax' ) ) {
			$this->ajax()->includes();
		} elseif ( UM()->is_request( 'admin' ) ) {
			$this->admin()->includes();
		} elseif ( UM()->is_request( 'frontend' ) ) {
			$this->frontend()->includes();
		}

		add_action( 'widgets_init', array( &$this, 'widgets' ) );
	}


	/**
	 * @return includes\common\Init()
	 */
	function common() {
		if ( empty( UM()->classes['umm\member_directory\includes\common\init'] ) ) {
			UM()->classes['umm\member_directory\includes\common\init'] = new includes\common\Init();
		}
		return UM()->classes['umm\member_directory\includes\common\init'];
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


	/**
	 * @return includes\ajax\Init()
	 */
	function ajax() {
		if ( empty( UM()->classes['umm\member_directory\includes\ajax\init'] ) ) {
			UM()->classes['umm\member_directory\includes\ajax\init'] = new includes\ajax\Init();
		}
		return UM()->classes['umm\member_directory\includes\ajax\init'];
	}


	/**
	 * @return includes\frontend\Init()
	 */
	function frontend() {
		if ( empty( UM()->classes['umm\member_directory\includes\frontend\init'] ) ) {
			UM()->classes['umm\member_directory\includes\frontend\init'] = new includes\frontend\Init();
		}
		return UM()->classes['umm\member_directory\includes\frontend\init'];
	}


	/**
	 * @return Config()
	 */
	function config() {
		if ( empty( UM()->classes['umm\member_directory\config'] ) ) {
			UM()->classes['umm\member_directory\config'] = new Config();
		}
		return UM()->classes['umm\member_directory\config'];
	}


	/**
	 *
	 */
	function widgets() {
		register_widget( 'umm\member_directory\includes\widgets\Search' );
	}
}
