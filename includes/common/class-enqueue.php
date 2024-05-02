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

	public static $fonticons_handlers = array();

	/**
	 * FontAwesome version.
	 *
	 * @var string
	 */
	public static $fa_version = '6.5.2';

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

	/**
	 * Get assets URL.
	 * @since 2.7.0
	 *
	 * @param string $type Can be "js", "css" or "libs".
	 *
	 * @return string
	 */
	public static function get_url( $type ) {
		if ( ! in_array( $type, array( 'js', 'css', 'libs' ), true ) ) {
			return '';
		}

		return self::$urls[ $type ];
	}

	/**
	 * Get scripts minified suffix.
	 *
	 * @since 2.7.0
	 *
	 * @return string
	 */
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
	 * Get Pickadate.JS locale.
	 *
	 * @since 2.8.0
	 *
	 * @return string
	 */
	private function get_pickadate_locale() {
		$suffix = self::get_suffix();
		$locale = get_locale();
		if ( file_exists( WP_LANG_DIR . '/plugins/ultimate-member/assets/js/pickadate/' . $locale . $suffix . '.js' ) || file_exists( UM_PATH . 'assets/libs/pickadate/translations/' . $locale . $suffix . '.js' ) ) {
			return $locale;
		}

		if ( false !== strpos( $locale, 'es_' ) ) {
			$locale = 'es_ES';
		} elseif ( false !== strpos( $locale, 'de_' ) ) {
			$locale = 'de_DE';
		} else {
			switch ( $locale ) {
				case 'uk':
					$locale = 'uk_UA';
					break;
				case 'ja':
					$locale = 'ja_JP';
					break;
				case 'ka_GE':
					$locale = 'ge_GEO';
					break;
				case 'ary':
					$locale = 'ar';
					break;
				case 'ca':
					$locale = 'ca_ES';
					break;
				case 'el':
					$locale = 'el_GR';
					break;
				case 'et':
					$locale = 'et_EE';
					break;
				case 'eu':
					$locale = 'eu_ES';
					break;
				case 'fa_AF':
					$locale = 'fa_IR';
					break;
				case 'fi':
					$locale = 'fi_FI';
					break;
				case 'hr':
					$locale = 'hr_HR';
					break;
				case 'km':
					$locale = 'km_KH';
					break;
				case 'lv':
					$locale = 'lv_LV';
					break;
				case 'th':
					$locale = 'th_TH';
					break;
				case 'vi':
					$locale = 'vi_VN';
					break;
				case 'sr_SR':
					$locale = 'sr_RS_lt';
					break;
			}
		}

		/**
		 * Filters Ultimate Member Pickadate.JS locale.
		 *
		 * @since 2.8.0
		 * @hook um_get_pickadate_locale
		 *
		 * @param {string} $locale Pickadate.JS locale.
		 * @param {string} $suffix Ultimate Member scripts suffix.
		 *
		 * @return {string} Pickadate.JS locale.
		 *
		 * @example <caption>Change Ultimate Member Pickadate.JS locale.</caption>
		 * function custom_um_get_pickadate_locale( $locale, $suffix ) {
		 *     $locale = 'th_TH';
		 *     return $locale;
		 * }
		 * add_filter( 'um_get_pickadate_locale', 'custom_um_get_pickadate_locale', 10, 2 );
		 */
		return apply_filters( 'um_get_pickadate_locale', $locale, $suffix );
	}

	/**
	 * Select2 JS and CSS assets register function.
	 *
	 *
	 */
	public function register_select2() {
		$suffix   = self::get_suffix();
		$libs_url = self::get_url( 'libs' );

		/**
		 * Filters marker for dequeue select2.JS library.
		 *
		 * @since 2.0.0
		 * @hook um_dequeue_select2_scripts
		 *
		 * @param {bool} $dequeue_select2 Dequeue select2 assets marker. Set to `true` for dequeue scripts.
		 *
		 * @return {bool} Dequeue select2 assets. By default `false`.
		 *
		 * @example <caption>Dequeue select2 assets.</caption>
		 * function custom_um_dequeue_select2_scripts( $dequeue_select2 ) {
		 *     $dequeue_select2 = true;
		 *     return $dequeue_select2;
		 * }
		 * add_filter( 'um_dequeue_select2_scripts', 'custom_um_dequeue_select2_scripts' );
		 */
		$dequeue_select2 = apply_filters( 'um_dequeue_select2_scripts', false );
		if ( class_exists( 'WooCommerce' ) || $dequeue_select2 ) {
			wp_dequeue_style( 'select2' );
			wp_deregister_style( 'select2' );

			wp_dequeue_script( 'select2' );
			wp_deregister_script( 'select2' );
		}
		wp_register_script( 'select2', $libs_url . 'select2/select2.full' . $suffix . '.js', array( 'jquery' ), '4.0.13', true );
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
				wp_register_script( 'um_select2_locale', $libs_url . 'select2/i18n/' . $locale . '.js', array( 'jquery', 'select2' ), '4.0.13', true );
				self::$select2_handle = 'um_select2_locale';
			}
		}
		wp_register_style( 'select2', $libs_url . 'select2/select2' . $suffix . '.css', array(), '4.0.13' );
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

		wp_register_script( 'um_confirm', $libs_url . 'um-confirm/um-confirm' . $suffix . '.js', array( 'jquery' ), '1.0', true );
		wp_register_style( 'um_confirm', $libs_url . 'um-confirm/um-confirm' . $suffix . '.css', array(), '1.0' );

		// Raty JS for rating field-type.
		wp_register_script( 'um_raty', $libs_url . 'raty/um-raty' . $suffix . '.js', array( 'jquery', 'wp-i18n' ), '2.6.0', true );
		wp_set_script_translations( 'um_raty', 'ultimate-member' );
		wp_register_style( 'um_raty', $libs_url . 'raty/um-raty' . $suffix . '.css', array(), '2.6.0' );

		// Legacy FontIcons.
		wp_register_style( 'um_fonticons_ii', $libs_url . 'legacy/fonticons/fonticons-ii' . $suffix . '.css', array(), UM_VERSION ); // Ionicons
		wp_register_style( 'um_fonticons_fa', $libs_url . 'legacy/fonticons/fonticons-fa' . $suffix . '.css', array(), UM_VERSION ); // FontAwesome
		$fonticons_handlers = array( 'um_fonticons_ii', 'um_fonticons_fa' );
		// New FontIcons from FontAwesome.
		// @todo new version
		// First install set this option to true by default and use new FontAwesome icons
		wp_register_style( 'um_fontawesome', $css_url . 'um-fontawesome' . $suffix . '.css', array(), self::$fa_version ); // New FontAwesome
		$fonticons_handlers[]     = 'um_fontawesome';
		self::$fonticons_handlers = $fonticons_handlers;

		// Select2 JS.
		$this->register_select2();

		// Date-time picker (Pickadate.JS)
		wp_register_script( 'um_datetime', $libs_url . 'pickadate/picker' . $suffix . '.js', array( 'jquery' ), '3.6.2', true );
		wp_register_script( 'um_datetime_date', $libs_url . 'pickadate/picker.date' . $suffix . '.js', array( 'um_datetime' ), '3.6.2', true );
		wp_register_script( 'um_datetime_time', $libs_url . 'pickadate/picker.time' . $suffix . '.js', array( 'um_datetime' ), '3.6.2', true );

		$common_js_deps = array( 'jquery', 'wp-util', 'wp-hooks', 'wp-i18n', 'um_tipsy', 'um_confirm', 'um_datetime_date', 'um_datetime_time' );

		// Load a localized version for date/time.
		$locale = $this->get_pickadate_locale();
		if ( $locale ) {
			if ( file_exists( WP_LANG_DIR . '/plugins/ultimate-member/assets/js/pickadate/' . $locale . $suffix . '.js' ) ) {
				wp_register_script( 'um_datetime_locale', content_url() . '/languages/plugins/ultimate-member/assets/js/pickadate/' . $locale . $suffix . '.js', array( 'jquery', 'um_datetime' ), '3.6.2', true );
				$common_js_deps[] = 'um_datetime_locale';
			} elseif ( file_exists( UM_PATH . 'assets/libs/pickadate/translations/' . $locale . $suffix . '.js' ) ) {
				wp_register_script( 'um_datetime_locale', $libs_url . 'pickadate/translations/' . $locale . $suffix . '.js', array( 'jquery', 'um_datetime' ), '3.6.2', true );
				$common_js_deps[] = 'um_datetime_locale';
			}
		}

		wp_register_style( 'um_datetime', $libs_url . 'pickadate/default' . $suffix . '.css', array(), '3.6.2' );
		wp_register_style( 'um_datetime_date', $libs_url . 'pickadate/default.date' . $suffix . '.css', array( 'um_datetime' ), '3.6.2' );
		wp_register_style( 'um_datetime_time', $libs_url . 'pickadate/default.time' . $suffix . '.css', array( 'um_datetime' ), '3.6.2' );

		wp_register_script( 'um_common', $js_url . 'common' . $suffix . '.js', $common_js_deps, UM_VERSION, true );
		$um_common_variables = array(
			'locale' => get_locale(),
		);
		/**
		 * Filters data array for localize frontend common scripts.
		 *
		 * @since 2.8.0
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

		$common_css_deps = array_merge( array( 'um_tipsy', 'um_confirm', 'um_datetime_date', 'um_datetime_time' ), self::$fonticons_handlers );
		wp_register_style( 'um_common', $css_url . 'common' . $suffix . '.css', $common_css_deps, UM_VERSION );
	}
}
