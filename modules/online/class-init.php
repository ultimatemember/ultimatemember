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

		// common classes
		$this->common();
		$this->enqueue();
		$this->fields();
		$this->member_directory();
		// don't use construct here because there are helper functions inside User class
		$this->user()->hooks();

		$this->friends();
		$this->private_messages();

		if ( UM()->is_request( 'admin' ) ) {
			$this->admin();
		} elseif ( UM()->is_request( 'frontend' ) ) {
			$this->account();
			$this->profile();
			$this->shortcode();
		}

		add_action( 'widgets_init', array( &$this, 'widgets_init' ) );
	}


	/**
	 * Init Online users widget
	 */
	function widgets_init() {
		register_widget( 'umm\online\includes\widgets\Online_List' );
	}


	/**
	 * @return includes\Common()
	 */
	function common() {
		if ( empty( UM()->classes['umm\online\includes\common'] ) ) {
			UM()->classes['umm\online\includes\common'] = new includes\Common();
		}
		return UM()->classes['umm\online\includes\common'];
	}


	/**
	 * @return includes\Enqueue()
	 */
	function enqueue() {
		if ( empty( UM()->classes['umm\online\includes\enqueue'] ) ) {
			UM()->classes['umm\online\includes\enqueue'] = new includes\Enqueue();
		}
		return UM()->classes['umm\online\includes\enqueue'];
	}


	/**
	 * @return includes\Admin()
	 */
	function admin() {
		if ( empty( UM()->classes['umm\online\includes\admin'] ) ) {
			UM()->classes['umm\online\includes\admin'] = new includes\Admin();
		}
		return UM()->classes['umm\online\includes\admin'];
	}


	/**
	 * @return includes\Fields()
	 */
	function fields() {
		if ( empty( UM()->classes['umm\online\includes\fields'] ) ) {
			UM()->classes['umm\online\includes\fields'] = new includes\Fields();
		}
		return UM()->classes['umm\online\includes\fields'];
	}


	/**
	 * @return includes\Member_Directory()
	 */
	function member_directory() {
		if ( empty( UM()->classes['umm\online\includes\member_directory'] ) ) {
			UM()->classes['umm\online\includes\member_directory'] = new includes\Member_Directory();
		}
		return UM()->classes['umm\online\includes\member_directory'];
	}


	/**
	 * @return null|includes\cross_modules\Friends()
	 */
	function friends() {
		if ( ! UM()->modules()->is_active( 'friends' ) ) {
			return null;
		}

		if ( empty( UM()->classes['umm\online\includes\cross_modules\friends'] ) ) {
			UM()->classes['umm\online\includes\cross_modules\friends'] = new includes\cross_modules\Friends();
		}
		return UM()->classes['umm\online\includes\cross_modules\friends'];
	}


	/**
	 * @return null|includes\cross_modules\Private_Messages()
	 */
	function private_messages() {
		if ( ! UM()->modules()->is_active( 'private_messages' ) ) {
			return null;
		}

		if ( empty( UM()->classes['umm\online\includes\cross_modules\private_messages'] ) ) {
			UM()->classes['umm\online\includes\cross_modules\private_messages'] = new includes\cross_modules\Private_Messages();
		}
		return UM()->classes['umm\online\includes\cross_modules\private_messages'];
	}


	/**
	 * @return includes\User()
	 */
	function user() {
		if ( empty( UM()->classes['umm\online\includes\user'] ) ) {
			UM()->classes['umm\online\includes\user'] = new includes\User();
		}
		return UM()->classes['umm\online\includes\user'];
	}


	/**
	 * @return includes\Account()
	 */
	function account() {
		if ( empty( UM()->classes['umm\online\includes\account'] ) ) {
			UM()->classes['umm\online\includes\account'] = new includes\Account();
		}
		return UM()->classes['umm\online\includes\account'];
	}


	/**
	 * @return includes\Profile()
	 */
	function profile() {
		if ( empty( UM()->classes['umm\online\includes\profile'] ) ) {
			UM()->classes['umm\online\includes\profile'] = new includes\Profile();
		}
		return UM()->classes['umm\online\includes\profile'];
	}


	/**
	 * @return includes\Shortcode()
	 */
	function shortcode() {
		if ( empty( UM()->classes['umm\online\includes\shortcode'] ) ) {
			UM()->classes['umm\online\includes\shortcode'] = new includes\Shortcode();
		}
		return UM()->classes['umm\online\includes\shortcode'];
	}
}
