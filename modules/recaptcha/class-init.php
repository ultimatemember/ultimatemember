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
		if ( UM()->is_request( 'admin' ) ) {
			$this->admin();
		} elseif ( UM()->is_request( 'frontend' ) ) {
			$this->enqueue();
			$this->form();
		}

		$this->private_messages();
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
	 * @return includes\Enqueue()
	 */
	function enqueue() {
		if ( empty( UM()->classes['umm\recaptcha\includes\enqueue'] ) ) {
			UM()->classes['umm\recaptcha\includes\enqueue'] = new includes\Enqueue();
		}
		return UM()->classes['umm\recaptcha\includes\enqueue'];
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


	/**
	 * @return null|includes\cross_modules\Private_Messages()
	 */
	function private_messages() {
		if ( ! UM()->modules()->is_active( 'private_messages' ) ) {
			return null;
		}

		if ( empty( UM()->classes['umm\recaptcha\includes\cross_modules\private_messages'] ) ) {
			UM()->classes['umm\recaptcha\includes\cross_modules\private_messages'] = new includes\cross_modules\Private_Messages();
		}
		return UM()->classes['umm\recaptcha\includes\cross_modules\private_messages'];
	}
}
