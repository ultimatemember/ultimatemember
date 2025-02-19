<?php
namespace um\frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Enqueue.
 *
 * @package um\frontend
 */
final class Enqueue extends \um\common\Enqueue {

	/**
	 * @var string
	 * @deprecated 2.8.0
	 */
	public $js_baseurl = '';

	/**
	 * @var string
	 * @deprecated 2.8.0
	 */
	public $css_baseurl = '';

	/**
	 * Enqueue constructor.
	 */
	public function __construct() {
		parent::__construct();
		add_action( 'init', array( &$this, 'scripts_enqueue_priority' ) );
	}

	/**
	 * @since 2.1.3
	 */
	public function scripts_enqueue_priority() {
		add_action( 'wp_enqueue_scripts', array( &$this, 'wp_enqueue_scripts' ), $this->get_priority() );
	}

	/**
	 * @since 2.1.3
	 * @return int
	 */
	public function get_priority() {
		/**
		 * Filters Ultimate Member frontend scripts enqueue priority.
		 *
		 * @since 1.3.x
		 * @hook um_core_enqueue_priority
		 *
		 * @param {int} $priority Ultimate Member frontend scripts enqueue priority.
		 *
		 * @return {int} Ultimate Member frontend scripts enqueue priority.
		 *
		 * @example <caption>Change Ultimate Member frontend enqueue scripts priority.</caption>
		 * function custom_um_core_enqueue_priority( $priority ) {
		 *     $priority = 101;
		 *     return $priority;
		 * }
		 * add_filter( 'um_core_enqueue_priority', 'custom_um_core_enqueue_priority' );
		 */
		return apply_filters( 'um_core_enqueue_priority', 100 );
	}

	/**
	 * Register JS scripts.
	 *
	 * @since 2.0.30
	 */
	public function register_scripts() {
		$suffix   = self::get_suffix();
		$libs_url = self::get_url( 'libs' );
		$js_url   = self::get_url( 'js' );

		// Cropper.js
		wp_register_script( 'um_crop', $libs_url . 'cropper/cropper' . $suffix . '.js', array( 'jquery' ), '1.6.1', true );
		wp_register_style( 'um_crop', $libs_url . 'cropper/cropper' . $suffix . '.css', array(), '1.6.1' );

		wp_register_script( 'um_frontend_common', $js_url . 'common-frontend' . $suffix . '.js', array( 'um_common', 'um_crop' ), UM_VERSION, true );
		$um_common_variables = array();
		/**
		 * Filters data array for localize frontend common scripts.
		 *
		 * @since 2.8.0
		 * @hook um_frontend_common_js_variables
		 *
		 * @param {array} $variables Data to localize.
		 *
		 * @return {array} Data to localize.
		 *
		 * @example <caption>Add `my_custom_variable` to common frontend scripts to be callable via `um_frontend_common_variables.my_custom_variable` in JS.</caption>
		 * function um_custom_frontend_common_js_variables( $variables ) {
		 *     $variables['{my_custom_variable}'] = '{my_custom_variable_value}';
		 *     return $variables;
		 * }
		 * add_filter( 'um_frontend_common_js_variables', 'um_custom_frontend_common_js_variables' );
		 */
		$um_common_variables = apply_filters( 'um_frontend_common_js_variables', $um_common_variables );
		wp_localize_script( 'um_frontend_common', 'um_frontend_common_variables', $um_common_variables );

		// uploadFiles scripts + UM custom styles for uploader.
		wp_register_script( 'um_jquery_form', $libs_url . 'jquery-form/jquery-form' . $suffix . '.js', array( 'jquery' ), UM_VERSION, true );
		wp_register_script( 'um_fileupload', $libs_url . 'fileupload/fileupload.js', array( 'um_jquery_form' ), UM_VERSION, true );

		wp_register_script( 'um_functions', $js_url . 'um-functions' . $suffix . '.js', array( 'um_frontend_common', 'um_fileupload' ), UM_VERSION, true );

		wp_register_script( 'um_modal', $js_url . 'um-modal' . $suffix . '.js', array( 'um_frontend_common' ), UM_VERSION, true );

		wp_register_script( 'um_functions', $js_url . 'um-functions' . $suffix . '.js', array( 'um_frontend_common', 'jquery-masonry' ), UM_VERSION, true );
		wp_register_script( 'um_responsive', $js_url . 'um-responsive' . $suffix . '.js', array( 'um_functions' ), UM_VERSION, true );

		wp_register_script( 'um-gdpr', $js_url . 'um-gdpr' . $suffix . '.js', array( 'jquery' ), UM_VERSION, false );
		wp_register_script( 'um_conditional', $js_url . 'um-conditional' . $suffix . '.js', array( 'jquery', 'wp-hooks' ), UM_VERSION, true );
		wp_register_script( 'um_scripts', $js_url . 'um-scripts' . $suffix . '.js', array( 'um_frontend_common', 'um_conditional', self::$select2_handle, 'um_raty' ), UM_VERSION, true );

		$max_upload_size = wp_max_upload_size();
		if ( ! $max_upload_size ) {
			$max_upload_size = 0;
		}

		$localize_data = array(
			'max_upload_size' => $max_upload_size,
			'nonce'           => wp_create_nonce( 'um-frontend-nonce' ),
		);
		/**
		 * Filters data array for localize frontend scripts.
		 *
		 * @param {array} $variables Data to localize.
		 *
		 * @return {array} Data to localize.
		 *
		 * @since 2.0.0
		 * @hook um_enqueue_localize_data
		 *
		 * @example <caption>Extend UM localized data.</caption>
		 * function my_enqueue_localize_data( $variables ) {
		 *     // your code here
		 *     return $variables;
		 * }
		 * add_filter( 'um_enqueue_localize_data', 'my_enqueue_localize_data' );
		 */
		$localize_data = apply_filters( 'um_enqueue_localize_data', $localize_data );
		wp_localize_script( 'um_scripts', 'um_scripts', $localize_data );

		wp_register_script( 'um_dropdown', $js_url . 'dropdown' . $suffix . '.js', array( 'jquery' ), UM_VERSION, true );

		wp_register_script( 'um_members', $js_url . 'um-members' . $suffix . '.js', array( 'jquery', 'wp-util', 'jquery-ui-slider', 'um_dropdown', 'wp-hooks', 'jquery-masonry', 'um_scripts' ), UM_VERSION, true );
		wp_register_script( 'um_profile', $js_url . 'um-profile' . $suffix . '.js', array( 'jquery', 'wp-util', 'wp-i18n', 'um_scripts' ), UM_VERSION, true );
		wp_set_script_translations( 'um_profile', 'ultimate-member' );

		/**
		 * Filters account script dependencies.
		 *
		 * @since 2.1.8
		 * @hook um_account_scripts_dependencies
		 *
		 * @param {array} $deps JS script dependencies.
		 *
		 * @return {array} JS script dependencies.
		 *
		 * @example <caption>Add `wp-util` as a dependencies script.</caption>
		 * function um_custom_account_scripts_dependencies( $deps ) {
		 *     $deps[] = 'wp-util';
		 *     return $deps;
		 * }
		 * add_filter( 'um_account_scripts_dependencies', 'um_custom_account_scripts_dependencies' );
		 */
		$account_deps = apply_filters( 'um_account_scripts_dependencies', array( 'jquery', 'wp-hooks', 'um_scripts' ) );
		wp_register_script( 'um_account', $js_url . 'um-account' . $suffix . '.js', $account_deps, UM_VERSION, true );
	}

	/**
	 * Register styles.
	 *
	 * @since 2.0.30
	 */
	public function register_styles() {
		$suffix  = self::get_suffix();
		$css_url = self::get_url( 'css' );

		wp_register_style( 'um_fileupload', $css_url . 'um-fileupload' . $suffix . '.css', array(), UM_VERSION );

		//FontAwesome and FontIcons styles
		wp_register_style( 'um_rtl', $css_url . 'um.rtl' . $suffix . '.css', array(), UM_VERSION );
		wp_register_style( 'um_default_css', $css_url . 'um-old-default' . $suffix . '.css', array(), UM_VERSION );
		wp_register_style( 'um_modal', $css_url . 'um-modal' . $suffix . '.css', array(), UM_VERSION );
		wp_register_style( 'um_responsive', $css_url . 'um-responsive' . $suffix . '.css', array(), UM_VERSION );

		// Workaround when select2 deregistered (e.g. Woo + Impreza theme activated).
		$this->register_select2();

		$deps = array_merge( array( 'um_ui', 'um_tipsy', 'um_raty', 'select2', 'um_fileupload', 'um_common', 'um_responsive', 'um_modal' ), self::$fonticons_handlers );
		wp_register_style( 'um_styles', $css_url . 'um-styles' . $suffix . '.css', $deps, UM_VERSION );

		wp_register_style( 'um_members', $css_url . 'um-members' . $suffix . '.css', array( 'um_styles' ), UM_VERSION );
		// RTL styles.
		if ( is_rtl() ) {
			wp_style_add_data( 'um_members', 'rtl', true );
			wp_style_add_data( 'um_members', 'suffix', $suffix );
		}

		wp_register_style( 'um_profile', $css_url . 'um-profile' . $suffix . '.css', array( 'um_styles', 'um_crop' ), UM_VERSION );
		wp_register_style( 'um_account', $css_url . 'um-account' . $suffix . '.css', array( 'um_styles' ), UM_VERSION );
		wp_register_style( 'um_misc', $css_url . 'um-misc' . $suffix . '.css', array( 'um_styles' ), UM_VERSION );
	}

	/**
	 * Enqueue scripts and styles.
	 *
	 * @since 2.0.0
	 */
	public function wp_enqueue_scripts() {
		$this->register_scripts();
		$this->register_styles();

		$this->load_original();

		// rtl style
		if ( is_rtl() ) {
			wp_enqueue_style( 'um_rtl' );
		}

		global $post;
		if ( is_object( $post ) && has_shortcode( $post->post_content, 'ultimatemember' ) ) {
			wp_dequeue_script( 'jquery-form' );
		}

		//old settings before UM 2.0 CSS
		wp_enqueue_style( 'um_default_css' );

		$this->old_css_settings();
	}

	/**
	 * @since 2.0.30
	 */
	public function old_css_settings() {
		$uploads    = wp_upload_dir();
		$upload_dir = $uploads['basedir'] . DIRECTORY_SEPARATOR . 'ultimatemember' . DIRECTORY_SEPARATOR;
		if ( file_exists( $upload_dir . 'um_old_settings.css' ) ) {
			wp_register_style( 'um_old_css', UM_URL . '../../uploads/ultimatemember/um_old_settings.css', array(), '2.0.0' );
			wp_enqueue_style( 'um_old_css' );
		}
	}

	/**
	 * This will load original files (not minified)
	 *
	 * @since 2.0.0
	 */
	public function load_original() {
		$this->load_modal();
		$this->load_css();
		$this->load_functions();
		$this->load_responsive();
		$this->load_customjs();
	}

	/**
	 * Load plugin CSS
	 *
	 * @since 2.0.0
	 */
	public function load_css() {
		wp_enqueue_style( 'um_styles' );
		wp_enqueue_style( 'um_profile' );
		wp_enqueue_style( 'um_account' );
		wp_enqueue_style( 'um_misc' );
	}

	/**
	 * Load JS functions.
	 *
	 * @since 2.0.0
	 */
	public function load_functions() {
		wp_enqueue_script( 'um_functions' );
		wp_enqueue_script( 'um-gdpr' );
	}

	/**
	 * Load custom JS.
	 *
	 * @since 2.0.0
	 */
	public function load_customjs() {
		wp_enqueue_script( 'um_conditional' );
		wp_enqueue_script( 'um_scripts' );
		wp_enqueue_script( 'um_profile' );
		wp_enqueue_script( 'um_account' );
	}

	/**
	 * Load modal.
	 *
	 * @since 2.0.0
	 */
	public function load_modal() {
		wp_enqueue_script( 'um_modal' );
		wp_enqueue_style( 'um_modal' );
	}

	/**
	 * Load responsive styles.
	 *
	 * @since 2.0.0
	 */
	public function load_responsive() {
		wp_enqueue_script( 'um_responsive' );
		wp_enqueue_style( 'um_responsive' );
	}

	/**
	 * Include Google charts
	 * @deprecated 2.8.0
	 */
	public function load_google_charts() {
	}

	/**
	 * Load fileupload JS
	 * @deprecated 2.8.0
	 */
	public function load_fileupload() {
	}

	/**
	 * Load date & time picker
	 * @deprecated 2.8.0
	 */
	public function load_datetimepicker() {
	}

	/**
	 * Load scrollbar
	 * @deprecated 2.8.0
	 */
	public function load_scrollbar() {
	}

	/**
	 * Load crop script
	 * @deprecated 2.8.0
	 */
	public function load_imagecrop() {
	}
}
