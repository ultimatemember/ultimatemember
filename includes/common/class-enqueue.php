<?php namespace um\common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Enqueue
 *
 * @package um\common
 */
class Enqueue {

	/**
	 * @var string scripts' Standard or Minified versions
	 *
	 * @since 2.7.0
	 */
	public $suffix;

	/**
	 * @var array URLs for easy using
	 *
	 * @since 2.7.0
	 */
	public $urls;

	/**
	 * Enqueue constructor.
	 *
	 * @since 2.7.0
	 */
	public function __construct() {
		add_action( 'um_core_loaded', array( $this, 'init_variables' ) );

		add_action( 'admin_enqueue_scripts', array( &$this, 'common_libs' ), 9 );
		add_action( 'wp_enqueue_scripts', array( &$this, 'common_libs' ), 9 );
	}

	/**
	 * Init variables for enqueue scripts.
	 *
	 * @since 2.7.0
	 */
	public function init_variables() {
		$this->suffix = ( ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) || ( defined( 'UM_SCRIPT_DEBUG' ) && UM_SCRIPT_DEBUG ) ) ? '' : '.min';

		$this->urls['js']   = UM_URL . 'assets/js/';
		$this->urls['css']  = UM_URL . 'assets/css/';
		$this->urls['libs'] = UM_URL . 'assets/libs/';
	}

	protected function register_jquery_ui() {
		wp_register_style( 'um_ui', $this->urls['libs'] . 'jquery-ui/jquery-ui' . $this->suffix . '.css', array(), '1.12.1' );
	}

	/**
	 * Register common JS/CSS libraries.
	 *
	 * @since 2.7.0
	 */
	public function common_libs() {
		$this->register_jquery_ui();

//		wp_register_script( 'um-tipsy', $this->urls['libs'] . 'tipsy/um-tipsy' . $this->suffix . '.js', array( 'jquery' ), '1.0.0a', true );
//		wp_register_style( 'um-tipsy', $this->urls['libs'] . 'tipsy/um-tipsy' . $this->suffix . '.css', array(), '1.0.0a' );
//
//		wp_register_script( 'um-helptip', $this->urls['libs'] . 'helptip/helptip' . $this->suffix . '.js', array( 'jquery', 'jquery-ui-tooltip' ), '1.0.0', true );
//		wp_register_style( 'um-helptip', $this->urls['libs'] . 'helptip/helptip' . $this->suffix . '.css', array( 'dashicons', 'um_ui' ), '1.0.0' );
//
//		// Legacy Fonticons
//		wp_register_style( 'um-fonticons-ii', $this->urls['libs'] . 'fonticons/um-fonticons-ii' . $this->suffix . '.css', array(), UM_VERSION );
//		wp_register_style( 'um-fonticons-fa', $this->urls['libs'] . 'fonticons/um-fonticons-fa' . $this->suffix . '.css', array(), UM_VERSION );
//
//		// Select2
//		$dequeue_select2 = apply_filters( 'um_dequeue_select2_scripts', false );
//		if ( class_exists( 'WooCommerce' ) || $dequeue_select2 ) {
//			wp_dequeue_style( 'select2' );
//			wp_deregister_style( 'select2' );
//
//			wp_dequeue_script( 'select2' );
//			wp_deregister_script( 'select2' );
//		}
//		wp_register_script( 'select2', $this->urls['libs'] . 'select2/select2.full' . $this->suffix . '.js', array( 'jquery' ), '4.0.13', true );
//		wp_register_style( 'select2', $this->urls['libs'] . 'select2/select2' . $this->suffix . '.css', array(), '4.0.13' );
//
//		// Raty JS for rating field-type
//		wp_register_script( 'um-raty', $this->urls['libs'] . 'raty/um-raty' . $this->suffix . '.js', array( 'jquery', 'wp-i18n' ), '2.6.0', true );
//		wp_register_style( 'um-raty', $this->urls['libs'] . 'raty/um-raty' . $this->suffix . '.css', array(), '2.6.0' );
//
//		// Modal
//		wp_register_script( 'um-modal', $this->urls['libs'] . 'modal/um-modal' . $this->suffix . '.js', array( 'jquery', 'wp-i18n', 'wp-hooks' ), UM_VERSION, true );
//		wp_register_style( 'um-modal', $this->urls['libs'] . 'modal/um-modal' . $this->suffix . '.css', array(), UM_VERSION );
//
//		// Common JS scripts for wp-admin and frontend both
//		wp_register_script( 'um-common', $this->urls['js'] . 'common' . $this->suffix . '.js', array( 'jquery' ), UM_VERSION, true );
//
//		$um_common_variables = array(
//			'locale' => get_locale(),
//		);
//		$um_common_variables = apply_filters( 'um_common_js_variables', $um_common_variables );
//		wp_localize_script( 'um-common', 'um_common_variables', $um_common_variables );
//		wp_enqueue_script( 'um-common' );
	}
}
