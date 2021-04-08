<?php
namespace um\core;


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'um\core\Enqueue' ) ) {


	/**
	 * Class Enqueue
	 * @package um\core
	 */
	class Enqueue {


		/**
		 * @var string
		 */
		var $suffix = '';


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
		function __construct() {
			$this->suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || defined( 'UM_SCRIPT_DEBUG' ) ) ? '' : '.min';

			$this->js_baseurl = um_url . 'assets/js/';
			$this->css_baseurl = um_url . 'assets/css/';

			add_action( 'init',  array( &$this, 'scripts_enqueue_priority' ) );
		}


		/**
		 *
		 */
		function scripts_enqueue_priority() {
			add_action( 'wp_enqueue_scripts',  array( &$this, 'wp_enqueue_scripts' ), $this->get_priority() );
		}


		/**
		 * @return int
		 */
		function get_priority() {
			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_core_enqueue_priority
			 * @description Change Enqueue scripts priority
			 * @input_vars
			 * [{"var":"$priority","type":"int","desc":"Priority"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_core_enqueue_priority', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_filter( 'um_core_enqueue_priority', 'my_core_enqueue_priority', 10, 1 );
			 * function my_core_enqueue_priority( $priority ) {
			 *     // your code here
			 *     return $priority;
			 * }
			 * ?>
			 */
			return apply_filters( 'um_core_enqueue_priority', 100 );
		}


		/**
		 *
		 */
		function register_scripts() {
			$dequeue_select2 = apply_filters( 'um_dequeue_select2_scripts', false );
			if ( class_exists( 'WooCommerce' ) || $dequeue_select2 ) {
				wp_dequeue_style( 'select2' );
				wp_deregister_style( 'select2' );

				wp_dequeue_script( 'select2');
				wp_deregister_script('select2');
			}
			wp_register_script( 'select2', $this->js_baseurl . 'select2/select2.full' . $this->suffix . '.js', array( 'jquery' ), '4.0.13', true );

			wp_register_script( 'um_scrollbar', $this->js_baseurl . 'simplebar' . $this->suffix . '.js', array( 'jquery' ), ultimatemember_version, true );

			wp_register_script( 'um_jquery_form', $this->js_baseurl . 'um-jquery-form' . $this->suffix . '.js', array( 'jquery' ), ultimatemember_version, true );
			wp_register_script( 'um_fileupload', $this->js_baseurl . 'um-fileupload' . $this->suffix . '.js', array( 'jquery', 'um_jquery_form' ), ultimatemember_version, true );


			wp_register_script( 'um_datetime', $this->js_baseurl . 'pickadate/picker.js', array( 'jquery' ), ultimatemember_version, true );
			wp_register_script( 'um_datetime_date', $this->js_baseurl . 'pickadate/picker.date.js', array( 'jquery', 'um_datetime' ), ultimatemember_version, true );
			wp_register_script( 'um_datetime_time', $this->js_baseurl . 'pickadate/picker.time.js', array( 'jquery', 'um_datetime' ), ultimatemember_version, true );
//			wp_register_script( 'um_datetime_legacy', $this->js_baseurl . 'pickadate/legacy.js', array( 'jquery', 'um_datetime' ), ultimatemember_version, true );
			// load a localized version for date/time
			$locale = get_locale();
			if ( $locale ) {
				if ( file_exists( WP_LANG_DIR . '/plugins/ultimate-member/assets/js/pickadate/' . $locale . '.js' ) ) {
					wp_register_script('um_datetime_locale', content_url() . '/languages/plugins/ultimate-member/assets/js/pickadate/' . $locale . '.js', array( 'jquery', 'um_datetime' ), ultimatemember_version, true );
				} elseif ( file_exists( um_path . 'assets/js/pickadate/translations/' . $locale . '.js' ) ) {
					wp_register_script('um_datetime_locale', um_url . 'assets/js/pickadate/translations/' . $locale . '.js', array( 'jquery', 'um_datetime' ), ultimatemember_version, true );
				}
			}

			wp_register_script( 'um_tipsy', $this->js_baseurl . 'um-tipsy' . $this->suffix . '.js', array( 'jquery' ), ultimatemember_version, true );
			wp_register_script( 'um_raty', $this->js_baseurl . 'um-raty' . $this->suffix . '.js', array( 'jquery', 'wp-i18n' ), ultimatemember_version, true );
			wp_register_script( 'um_crop', $this->js_baseurl . 'um-crop' . $this->suffix . '.js', array( 'jquery' ), ultimatemember_version, true );

			wp_register_script( 'um_modal', $this->js_baseurl . 'um-modal' . $this->suffix . '.js', array( 'jquery', 'wp-util', 'um_crop' ), ultimatemember_version, true );

			wp_register_script('um_functions', $this->js_baseurl . 'um-functions' . $this->suffix . '.js', array( 'jquery', 'jquery-masonry', 'wp-util', 'um_scrollbar' ), ultimatemember_version, true );
			wp_register_script( 'um_responsive', $this->js_baseurl . 'um-responsive' . $this->suffix . '.js', array( 'jquery', 'um_functions', 'um_crop' ), ultimatemember_version, true );

			wp_register_script( 'um-gdpr', $this->js_baseurl . 'um-gdpr' . $this->suffix . '.js', array( 'jquery' ), ultimatemember_version, false );
			wp_register_script('um_conditional', $this->js_baseurl . 'um-conditional' . $this->suffix . '.js', array( 'jquery', 'wp-hooks' ), ultimatemember_version, true );
			wp_register_script('um_scripts', $this->js_baseurl . 'um-scripts' . $this->suffix . '.js', array( 'jquery', 'wp-util', 'um_conditional', 'um_datetime', 'um_datetime_date', 'um_datetime_time', /*'um_datetime_legacy',*/ 'select2' ), ultimatemember_version, true );
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
				'nonce' => wp_create_nonce( "um-frontend-nonce" ),
			) );
			wp_localize_script( 'um_scripts', 'um_scripts', $localize_data );

			wp_register_script('um_dropdown', $this->js_baseurl . 'dropdown' . $this->suffix . '.js', array( 'jquery' ), ultimatemember_version, true );

			wp_register_script('um_members', $this->js_baseurl . 'um-members' . $this->suffix . '.js', array( 'jquery', 'wp-util', 'jquery-ui-slider', 'um_dropdown', 'wp-hooks', 'jquery-masonry', 'um_scripts' ), ultimatemember_version, true );
			wp_register_script('um_profile', $this->js_baseurl . 'um-profile' . $this->suffix . '.js', array( 'jquery', 'wp-util', 'wp-i18n' ), ultimatemember_version, true );

			$account_deps = apply_filters( 'um_account_scripts_dependencies', array( 'jquery', 'wp-hooks' ) );
			wp_register_script('um_account', $this->js_baseurl . 'um-account' . $this->suffix . '.js', $account_deps, ultimatemember_version, true );

			wp_register_script( 'um_gchart', 'https://www.google.com/jsapi', array(), ultimatemember_version, true );
		}


		/**
		 * Register styles
		 */
		function register_styles() {

			//FontAwesome and FontIcons styles
			wp_register_style( 'um_fonticons_ii', $this->css_baseurl . 'um-fonticons-ii.css', array(), ultimatemember_version );
			wp_register_style( 'um_fonticons_fa', $this->css_baseurl . 'um-fonticons-fa.css', array(), ultimatemember_version );
			wp_register_style( 'um_crop', $this->css_baseurl . 'um-crop.css', array(), ultimatemember_version );
			wp_register_style( 'um_tipsy', $this->css_baseurl . 'um-tipsy.css', array(), ultimatemember_version );
			wp_register_style( 'um_raty', $this->css_baseurl . 'um-raty.css', array(), ultimatemember_version );
			wp_register_style( 'select2', $this->css_baseurl . 'select2/select2.min.css', array(), '4.0.13' );
			wp_register_style( 'um_fileupload', $this->css_baseurl . 'um-fileupload.css', array(), ultimatemember_version );
			wp_register_style( 'um_datetime', $this->css_baseurl . 'pickadate/default.css', array(), ultimatemember_version );
			wp_register_style( 'um_datetime_date', $this->css_baseurl . 'pickadate/default.date.css', array( 'um_datetime' ), ultimatemember_version );
			wp_register_style( 'um_datetime_time', $this->css_baseurl . 'pickadate/default.time.css', array( 'um_datetime' ), ultimatemember_version );
			wp_register_style( 'um_scrollbar', $this->css_baseurl . 'simplebar.css', array(), ultimatemember_version );

			wp_register_style( 'um_rtl', $this->css_baseurl . 'um.rtl.css', array(), ultimatemember_version );
			wp_register_style( 'um_default_css', $this->css_baseurl . 'um-old-default.css', array(), ultimatemember_version );
			wp_register_style( 'um_modal', $this->css_baseurl . 'um-modal.css', array( 'um_crop' ), ultimatemember_version );
			wp_register_style( 'um_responsive', $this->css_baseurl . 'um-responsive.css', array( 'um_profile', 'um_crop' ), ultimatemember_version );

			wp_register_style( 'um_styles', $this->css_baseurl . 'um-styles.css', array(), ultimatemember_version );

			wp_register_style( 'um_ui', $this->css_baseurl . 'jquery-ui.css', array(), ultimatemember_version );

			wp_register_style( 'um_members', $this->css_baseurl . 'um-members.css', array( 'um_ui' ), ultimatemember_version );
			if ( is_rtl() ) {
				wp_register_style( 'um_members_rtl', $this->css_baseurl . 'um-members-rtl.css', array( 'um_members' ), ultimatemember_version );
			}

			wp_register_style( 'um_profile', $this->css_baseurl . 'um-profile.css', array(), ultimatemember_version );
			wp_register_style( 'um_account', $this->css_baseurl . 'um-account.css', array(), ultimatemember_version );
			wp_register_style( 'um_misc', $this->css_baseurl . 'um-misc.css', array(), ultimatemember_version );
		
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
				wp_register_style( 'um_old_css', um_url . '../../uploads/ultimatemember/um_old_settings.css' );
				wp_enqueue_style( 'um_old_css' );
			}
		}


		/**
		 * This will load original files (not minified)
		 */
		function load_original() {

			//maybe deprecated
			//$this->load_google_charts();

			$this->load_fonticons();

			$this->load_selectjs();

			$this->load_modal();

			$this->load_css();

			$this->load_fileupload();

			$this->load_datetimepicker();

			$this->load_raty();

			//$this->load_scrollto();

			$this->load_scrollbar();

			$this->load_imagecrop();

			$this->load_tipsy();

			$this->load_functions();

			$this->load_responsive();

			$this->load_customjs();

		}


		/**
		 * Include Google charts
		 */
		function load_google_charts() {
			wp_enqueue_script( 'um_gchart' );
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
		 */
		function load_selectjs() {
			wp_enqueue_script( 'select2' );
			wp_enqueue_style( 'select2' );
		}


		/**
		 * Load Fonticons
		 */
		function load_fonticons() {
			wp_enqueue_style( 'um_fonticons_ii' );
			wp_enqueue_style( 'um_fonticons_fa' );
		}


		/**
		 * Load fileupload JS
		 */
		function load_fileupload() {
			wp_enqueue_script( 'um_fileupload' );
			wp_enqueue_style( 'um_fileupload' );
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
		 */
		function load_datetimepicker() {
			wp_enqueue_script( 'um_datetime' );
			wp_enqueue_script( 'um_datetime_date' );
			wp_enqueue_script( 'um_datetime_time' );
			//wp_enqueue_script( 'um_datetime_legacy' );

			// load a localized version for date/time
			$locale = get_locale();
			if ( $locale && ( file_exists( WP_LANG_DIR . '/plugins/ultimate-member/assets/js/pickadate/' . $locale . '.js' ) || file_exists( um_path . 'assets/js/pickadate/translations/' . $locale . '.js' ) ) ) {
				wp_enqueue_script('um_datetime_locale' );
			}

			wp_enqueue_style( 'um_datetime' );
			wp_enqueue_style( 'um_datetime_date' );
			wp_enqueue_style( 'um_datetime_time' );
		}


		/**
		 * Load scrollbar
		 */
		function load_scrollbar(){
			wp_enqueue_style('um_scrollbar');
		}


		/**
		 * Load rating
		 */
		function load_raty() {
			wp_enqueue_script('um_raty');
			wp_enqueue_style('um_raty');
		}


		/**
		 * Load crop script
		 */
		function load_imagecrop() {
			wp_enqueue_script( 'um_crop' );
			wp_enqueue_style( 'um_crop' );
		}


		/**
		 * Load tipsy
		 */
		function load_tipsy() {
			wp_enqueue_script( 'um_tipsy' );
			wp_enqueue_style( 'um_tipsy' );
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
}