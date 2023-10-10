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
	 * @var string scripts' Standard or Minified versions.
	 *
	 * @since 2.7.0
	 */
	public static $suffix = '';

	/**
	 * @var array URLs for easy using.
	 *
	 * @since 2.7.0
	 */
	public static $urls = array(
		'js'   => UM_URL . 'assets/js/',
		'css'  => UM_URL . 'assets/css/',
		'libs' => UM_URL . 'assets/libs/',
	);

	/**
	 * @var string scripts' Standard or Minified versions.
	 *
	 * @since 2.7.0
	 */
	public static $select2_handle = 'select2';

	/**
	 * Enqueue constructor.
	 *
	 * @since 2.7.0
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( &$this, 'common_libs' ), 9 );
		add_action( 'wp_enqueue_scripts', array( &$this, 'common_libs' ), 9 );
		add_action( 'enqueue_block_assets', array( &$this, 'common_libs' ), 9 );
	}

	public static function get_url( $type ) {
		if ( ! in_array( $type, array( 'js', 'css', 'libs' ), true ) ) {
			return '';
		}

		return self::$urls[ $type ];
	}

	public static function get_suffix() {
		if ( empty( self::$suffix ) ) {
			self::$suffix = ( ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) || ( defined( 'UM_SCRIPT_DEBUG' ) && UM_SCRIPT_DEBUG ) ) ? '' : '.min';
		}
		return self::$suffix;
	}

	/**
	 * Register jQuery-UI styles.
	 *
	 * @since 2.7.0
	 */
	protected function register_jquery_ui() {
		wp_register_style( 'um_ui', self::get_url( 'libs' ) . 'jquery-ui/jquery-ui' . self::get_suffix() . '.css', array(), '1.12.1' );
	}

	/**
	 * Register common JS/CSS libraries.
	 *
	 * @since 2.7.0
	 */
	public function common_libs() {
		$this->register_jquery_ui();

		$suffix = self::get_suffix();

		wp_register_script( 'um_tipsy', self::get_url( 'libs' ) . 'tipsy/tipsy' . $suffix . '.js', array( 'jquery' ), '1.0.0a', true );
		wp_register_style( 'um_tipsy', self::get_url( 'libs' ) . 'tipsy/tipsy' . $suffix . '.css', array(), '1.0.0a' );

		// Raty JS for rating field-type.
		wp_register_script( 'um_raty', self::get_url( 'libs' ) . 'raty/um-raty' . $suffix . '.js', array( 'jquery', 'wp-i18n' ), '2.6.0', true );
		wp_register_style( 'um_raty', self::get_url( 'libs' ) . 'raty/um-raty' . $suffix . '.css', array(), '2.6.0' );

		// Legacy FontIcons.
		wp_register_style( 'um_fonticons_ii', self::get_url( 'libs' ) . 'legacy/fonticons/fonticons-ii' . $suffix . '.css', array(), UM_VERSION ); // Ionicons
		wp_register_style( 'um_fonticons_fa', self::get_url( 'libs' ) . 'legacy/fonticons/fonticons-fa' . $suffix . '.css', array(), UM_VERSION ); // FontAwesome

//		wp_register_script( 'um-helptip', $this->urls['libs'] . 'helptip/helptip' . $this->suffix . '.js', array( 'jquery', 'jquery-ui-tooltip' ), '1.0.0', true );
//		wp_register_style( 'um-helptip', $this->urls['libs'] . 'helptip/helptip' . $this->suffix . '.css', array( 'dashicons', 'um_ui' ), '1.0.0' );

		// Select2 JS.
		$dequeue_select2 = apply_filters( 'um_dequeue_select2_scripts', false );
		if ( class_exists( 'WooCommerce' ) || $dequeue_select2 ) {
			wp_dequeue_style( self::$select2_handle );
			wp_deregister_style( self::$select2_handle );

			wp_dequeue_script( self::$select2_handle );
			wp_deregister_script( self::$select2_handle );
		}
		wp_register_script( self::$select2_handle, self::get_url( 'libs' ) . 'select2/select2.full' . $suffix . '.js', array( 'jquery' ), '4.0.13', true );
		// Load a localized version for Select2.
		$locale      = get_locale();
		$base_locale = get_locale();
		if ( $locale ) {
			if ( ! file_exists( UM_PATH . 'assets/libs/select2/i18n/' . $locale . '.js' ) ) {
				$locale = explode( '_', $base_locale );
				$locale = $locale[0];

				if ( ! file_exists( UM_PATH . 'assets/libs/select2/i18n/' . $locale . '.js' ) ) {
					$locale = explode( '_', $base_locale );
					$locale = implode( '-', $locale );
				}
			}

			if ( file_exists( UM_PATH . 'assets/libs/select2/i18n/' . $locale . '.js' ) ) {
				wp_register_script( 'um_select2_locale', self::get_url( 'libs' ) . 'select2/i18n/' . $locale . '.js', array( 'jquery', self::$select2_handle ), '4.0.13', true );
				self::$select2_handle = 'um_select2_locale';
			}
		}

		wp_register_style( 'select2', self::get_url( 'libs' ) . 'select2/select2' . $suffix . '.css', array(), '4.0.13' );
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
