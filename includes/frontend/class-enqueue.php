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
	 */
	var $js_baseurl = '';


	/**
	 * @var string
	 */
	var $css_baseurl = '';


	/**
	 * Enqueue constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->js_baseurl  = UM_URL . 'assets/js/';
		$this->css_baseurl = UM_URL . 'assets/css/';

		add_action( 'init', array( &$this, 'scripts_enqueue_priority' ) );
	}

	/**
	 *
	 */
	public function scripts_enqueue_priority() {
		add_action( 'wp_enqueue_scripts', array( &$this, 'wp_enqueue_scripts' ), $this->get_priority() );
	}

	/**
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
	 *
	 */
	public function register_scripts() {
		$suffix   = self::get_suffix();
		$libs_url = self::get_url( 'libs' );
		$js_url   = self::get_url( 'js' );
		$css_url  = self::get_url( 'css' );

		// Cropper.js
		wp_register_script( 'um_crop', $libs_url . 'cropper/cropper' . $suffix . '.js', array( 'jquery' ), '1.6.1', true );
		wp_register_style( 'um_crop', $libs_url . 'cropper/cropper' . $suffix . '.css', array(), '1.6.1' );

		wp_register_script( 'um_frontend_common', $js_url . 'common-frontend' . $suffix . '.js', array( 'um_common', 'um_crop' ), UM_VERSION, true );
		$um_common_variables = array();
		/**
		 * Filters data array for localize frontend common scripts.
		 *
		 * @since 2.7.1
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
		wp_register_style( 'um_fileupload', $css_url . 'um-fileupload' . $suffix . '.css', array(), UM_VERSION );

		wp_register_script( 'um_functions', $js_url . 'um-functions' . $suffix . '.js', array( 'um_frontend_common', 'um_fileupload' ), UM_VERSION, true );

		wp_register_script( 'um_modal', $this->js_baseurl . 'um-modal' . $suffix . '.js', array( 'um_frontend_common' ), UM_VERSION, true );

		wp_register_script( 'um_functions', $this->js_baseurl . 'um-functions' . $suffix . '.js', array( 'um_frontend_common', 'jquery-masonry' ), UM_VERSION, true );
		wp_register_script( 'um_responsive', $this->js_baseurl . 'um-responsive' . $suffix . '.js', array( 'um_functions' ), UM_VERSION, true );

		wp_register_script( 'um-gdpr', $this->js_baseurl . 'um-gdpr' . $suffix . '.js', array( 'jquery' ), UM_VERSION, false );
		wp_register_script( 'um_conditional', $this->js_baseurl . 'um-conditional' . $suffix . '.js', array( 'jquery', 'wp-hooks' ), UM_VERSION, true );
		wp_register_script( 'um_scripts', $this->js_baseurl . 'um-scripts' . $suffix . '.js', array( 'um_frontend_common', 'um_conditional', self::$select2_handle, 'um_raty' ), UM_VERSION, true );

		$max_upload_size = wp_max_upload_size();
		if ( ! $max_upload_size ) {
			$max_upload_size = 0;
		}

		/**
		 * UM hook
		 *
		 * @type filter
		 * @title um_enqueue_localize_data
		 * @description Extend UM localized data
		 * @input_vars
		 * [{"var":"$data","type":"array","desc":"Localize Array"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage add_filter( 'um_enqueue_localize_data', 'function_name', 10, 1 );
		 * @example
		 * <?php
		 * add_filter( 'um_enqueue_localize_data', 'my_enqueue_localize_data', 10, 1 );
		 * function my_enqueue_localize_data( $data ) {
		 *     // your code here
		 *     return $data;
		 * }
		 * ?>
		 */
		$localize_data = apply_filters( 'um_enqueue_localize_data', array(
			'max_upload_size'   => $max_upload_size,
			'nonce'             => wp_create_nonce( "um-frontend-nonce" ),
		) );
		wp_localize_script( 'um_scripts', 'um_scripts', $localize_data );

		wp_register_script('um_dropdown', $this->js_baseurl . 'dropdown' . $suffix . '.js', array( 'jquery' ), UM_VERSION, true );

		wp_register_script('um_members', $this->js_baseurl . 'um-members' . $suffix . '.js', array( 'jquery', 'wp-util', 'jquery-ui-slider', 'um_dropdown', 'wp-hooks', 'jquery-masonry', 'um_scripts' ), UM_VERSION, true );
		wp_register_script('um_profile', $this->js_baseurl . 'um-profile' . $suffix . '.js', array( 'jquery', 'wp-util', 'wp-i18n', 'um_scripts' ), UM_VERSION, true );
		wp_set_script_translations( 'um_profile', 'ultimate-member' );

		$account_deps = apply_filters( 'um_account_scripts_dependencies', array( 'jquery', 'wp-hooks', 'um_scripts' ) );
		wp_register_script('um_account', $this->js_baseurl . 'um-account' . $suffix . '.js', $account_deps, UM_VERSION, true );
	}

	/**
	 * Register styles
	 */
	public function register_styles() {
		//FontAwesome and FontIcons styles
		wp_register_style( 'um_rtl', $this->css_baseurl . 'um.rtl.css', array(), UM_VERSION );
		wp_register_style( 'um_default_css', $this->css_baseurl . 'um-old-default.css', array(), UM_VERSION );
		wp_register_style( 'um_modal', $this->css_baseurl . 'um-modal.css', array(), UM_VERSION );
		wp_register_style( 'um_responsive', $this->css_baseurl . 'um-responsive.css', array( 'um_profile' ), UM_VERSION );

		wp_register_style( 'um_styles', $this->css_baseurl . 'um-styles.css', array( 'um_ui', 'um_tipsy', 'um_raty', 'um_fonticons_ii', 'um_fonticons_fa', 'select2', 'um_fileupload', 'um_common' ), UM_VERSION );

		wp_register_style( 'um_members', $this->css_baseurl . 'um-members.css', array( 'um_styles' ), UM_VERSION );
		if ( is_rtl() ) {
			wp_register_style( 'um_members_rtl', $this->css_baseurl . 'um-members-rtl.css', array( 'um_members' ), UM_VERSION );
		}

		wp_register_style( 'um_profile', $this->css_baseurl . 'um-profile.css', array( 'um_styles', 'um_crop' ), UM_VERSION );
		wp_register_style( 'um_account', $this->css_baseurl . 'um-account.css', array( 'um_styles' ), UM_VERSION );
		wp_register_style( 'um_misc', $this->css_baseurl . 'um-misc.css', array( 'um_styles' ), UM_VERSION );
	}


	/**
	 * Enqueue scripts and styles
	 */
	function wp_enqueue_scripts() {

		$this->register_scripts();
		$this->register_styles();

		$this->load_original();

		// rtl style
		if ( is_rtl() ) {
			wp_enqueue_style( 'um_rtl' );
		}

		global $post;
		if ( is_object( $post ) && has_shortcode( $post->post_content,'ultimatemember' ) ) {
			wp_dequeue_script( 'jquery-form' );
		}

		//old settings before UM 2.0 CSS
		wp_enqueue_style( 'um_default_css' );

		$this->old_css_settings();
	}


	/**
	 *
	 */
	function old_css_settings() {
		$uploads        = wp_upload_dir();
		$upload_dir     = $uploads['basedir'] . DIRECTORY_SEPARATOR . 'ultimatemember' . DIRECTORY_SEPARATOR;
		if ( file_exists( $upload_dir . 'um_old_settings.css' ) ) {
			wp_register_style( 'um_old_css', UM_URL . '../../uploads/ultimatemember/um_old_settings.css' );
			wp_enqueue_style( 'um_old_css' );
		}
	}


	/**
	 * This will load original files (not minified)
	 */
	function load_original() {

		// $this->load_selectjs();

		$this->load_modal();

		$this->load_css();

		$this->load_fileupload();

		$this->load_functions();

		$this->load_responsive();

		$this->load_customjs();

	}

	/**
	 * Include Google charts
	 * @depecated 2.7.1
	 */
	function load_google_charts() {
	}

	/**
	 * Load plugin css
	 */
	function load_css() {
		wp_enqueue_style( 'um_styles' );
		/*if ( is_rtl() ) {
			wp_enqueue_style( 'um_members_rtl' );
		} else {
			wp_enqueue_style( 'um_members' );
		}*/

		wp_enqueue_style( 'um_profile' );
		wp_enqueue_style( 'um_account' );
		wp_enqueue_style( 'um_misc' );
	}


	/**
	 * Load select-dropdowns JS
	 * @depecated 2.7.0
	 */
	function load_selectjs() {
	}

	/**
	 * Load fileupload JS
	 * @depecated 2.7.1
	 */
	function load_fileupload() {
	}


	/**
	 * Load JS functions
	 */
	function load_functions() {
		wp_enqueue_script('um_functions' );
		wp_enqueue_script( 'um-gdpr' );
	}


	/**
	 * Load custom JS
	 */
	function load_customjs() {
		wp_enqueue_script('um_conditional');
		wp_enqueue_script('um_scripts');
		//wp_enqueue_script('um_members');
		wp_enqueue_script('um_profile');
		wp_enqueue_script('um_account');
	}


	/**
	 * Load date & time picker
	 * @depecated 2.7.1
	 */
	function load_datetimepicker() {
	}

	/**
	 * Load scrollbar
	 * @depecated 2.7.1
	 */
	function load_scrollbar(){
	}

	/**
	 * Load crop script
	 * @depecated 2.7.1
	 */
	function load_imagecrop() {
	}

	/**
	 * Load rating
	 *
	 * @depecated 2.7.0
	 */
	function load_raty() {
	}


	/**
	 * Load modal
	 */
	function load_modal() {
		wp_enqueue_script( 'um_modal' );
		wp_enqueue_style( 'um_modal' );
	}


	/**
	 * Load responsive styles
	 */
	function load_responsive() {
		wp_enqueue_script( 'um_responsive' );
		wp_enqueue_style( 'um_responsive' );
	}
}
