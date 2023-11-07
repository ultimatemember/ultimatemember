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
		wp_register_style( 'um_ui', self::get_url( 'libs' ) . 'jquery-ui/jquery-ui' . self::get_suffix() . '.css', array(), '1.13.2' );
	}

	/**
	 * Register common JS/CSS libraries.
	 *
	 * @since 2.7.0
	 */
	public function common_libs() {
		$this->register_jquery_ui();

		$suffix   = self::get_suffix();
		$libs_url = self::get_url( 'libs' );
		$js_url   = self::get_url( 'js' );
		$css_url  = self::get_url( 'css' );

		wp_register_script( 'um_tipsy', $libs_url . 'tipsy/tipsy' . $suffix . '.js', array( 'jquery' ), '1.0.0a', true );
		wp_register_style( 'um_tipsy', $libs_url . 'tipsy/tipsy' . $suffix . '.css', array(), '1.0.0a' );

		// Raty JS for rating field-type.
		wp_register_script( 'um_raty', $libs_url . 'raty/um-raty' . $suffix . '.js', array( 'jquery', 'wp-i18n' ), '2.6.0', true );
		wp_register_style( 'um_raty', $libs_url . 'raty/um-raty' . $suffix . '.css', array(), '2.6.0' );

		// Legacy FontIcons.
		wp_register_style( 'um_fonticons_ii', $libs_url . 'legacy/fonticons/fonticons-ii' . $suffix . '.css', array(), UM_VERSION ); // Ionicons
		wp_register_style( 'um_fonticons_fa', $libs_url . 'legacy/fonticons/fonticons-fa' . $suffix . '.css', array(), UM_VERSION ); // FontAwesome

		// Select2 JS.
		$dequeue_select2 = apply_filters( 'um_dequeue_select2_scripts', false );
		if ( class_exists( 'WooCommerce' ) || $dequeue_select2 ) {
			wp_dequeue_style( self::$select2_handle );
			wp_deregister_style( self::$select2_handle );

			wp_dequeue_script( self::$select2_handle );
			wp_deregister_script( self::$select2_handle );
		}
		wp_register_script( self::$select2_handle, $libs_url . 'select2/select2.full' . $suffix . '.js', array( 'jquery' ), '4.0.13', true );
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
				wp_register_script( 'um_select2_locale', $libs_url . 'select2/i18n/' . $locale . '.js', array( 'jquery', self::$select2_handle ), '4.0.13', true );
				self::$select2_handle = 'um_select2_locale';
			}
		}
		wp_register_style( 'select2', $libs_url . 'select2/select2' . $suffix . '.css', array(), '4.0.13' );

		// Date-time picker (Pickadate.JS)
		wp_register_script( 'um_datetime', $libs_url . 'pickadate/picker' . $suffix . '.js', array( 'jquery' ), UM_VERSION, true );
		wp_register_script( 'um_datetime_date', $libs_url . 'pickadate/picker.date' . $suffix . '.js', array( 'um_datetime' ), UM_VERSION, true );
		wp_register_script( 'um_datetime_time', $libs_url . 'pickadate/picker.time' . $suffix . '.js', array( 'um_datetime' ), UM_VERSION, true );
		// Load a localized version for date/time.
		$locale = get_locale();
		if ( $locale ) {
			if ( file_exists( WP_LANG_DIR . '/plugins/ultimate-member/assets/js/pickadate/' . $locale . $suffix . '.js' ) ) {
				wp_register_script( 'um_datetime_locale', content_url() . '/languages/plugins/ultimate-member/assets/js/pickadate/' . $locale . $suffix . '.js', array( 'jquery', 'um_datetime' ), UM_VERSION, true );
			} elseif ( file_exists( UM_PATH . 'assets/libs/pickadate/translations/' . $locale . $suffix . '.js' ) ) {
				wp_register_script( 'um_datetime_locale', $libs_url . 'pickadate/translations/' . $locale . $suffix . '.js', array( 'jquery', 'um_datetime' ), UM_VERSION, true );
			}
		}

		wp_register_style( 'um_datetime', $libs_url . 'pickadate/default' . $suffix . '.css', array(), UM_VERSION );
		wp_register_style( 'um_datetime_date', $libs_url . 'pickadate/default.date' . $suffix . '.css', array( 'um_datetime' ), UM_VERSION );
		wp_register_style( 'um_datetime_time', $libs_url . 'pickadate/default.time' . $suffix . '.css', array( 'um_datetime' ), UM_VERSION );

		wp_register_script( 'um_common', $js_url . 'common' . $suffix . '.js', array( 'jquery', 'wp-util', 'wp-hooks', 'wp-i18n', 'um_tipsy', 'um_datetime_date', 'um_datetime_time' ), UM_VERSION, true );
		$um_common_variables = array(
			'locale' => get_locale(),
		);
		/**
		 * Filters data array for localize frontend common scripts.
		 *
		 * @since 2.7.1
		 * @hook um_common_js_variables
		 *
		 * @param {array} $variables Data to localize.
		 *
		 * @return {array} Data to localize.
		 *
		 * @example <caption>Add `my_custom_variable` to common scripts to be callable via `um_common_variables.my_custom_variable` in JS.</caption>
		 * function um_custom_common_js_variables( $variables ) {
		 *     $variables['{my_custom_variable}'] = '{my_custom_variable_value}';
		 *     return $variables;
		 * }
		 * add_filter( 'um_common_js_variables', 'um_custom_common_js_variables' );
		 */
		$um_common_variables = apply_filters( 'um_common_js_variables', $um_common_variables );
		wp_localize_script( 'um_common', 'um_common_variables', $um_common_variables );

		wp_register_style( 'um_common', $css_url . 'common' . $suffix . '.css', array( 'um_tipsy', 'um_datetime_date', 'um_datetime_time' ), UM_VERSION );
	}
}
