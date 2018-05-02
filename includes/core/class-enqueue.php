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
		 * Enqueue constructor.
		 */
		function __construct() {
			$this->js_baseurl = um_url . 'assets/js/';
			$this->css_baseurl = um_url . 'assets/css/';

			$this->suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || defined( 'UM_SCRIPT_DEBUG' ) ) ? '' : '.min';

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
			$priority = apply_filters( 'um_core_enqueue_priority', 100 );
			//add_action( 'wp_enqueue_scripts',  array( &$this, 'wp_enqueue_scripts' ), $priority );
			add_action( 'wp_enqueue_scripts',  array( &$this, 'register_scripts' ), $priority );
		}


		/**
		 *
		 */
		function register_scripts() {

			// SCRIPTS
			//used in social-activity plugin
			wp_register_script( 'um-scrollto', $this->js_baseurl . 'um-scrollto' . $this->suffix . '.js', array( 'jquery' ), ultimatemember_version, true );

			wp_register_script( 'um-scrollbar', $this->js_baseurl . 'um-scrollbar' . $this->suffix . '.js', array( 'jquery' ), ultimatemember_version, true );

			wp_register_script( 'um-jquery-form', $this->js_baseurl . 'um-jquery-form' . $this->suffix . '.js', array( 'jquery' ), ultimatemember_version, true );
			wp_register_script( 'um-fileupload', $this->js_baseurl . 'um-fileupload' . $this->suffix . '.js', array( 'jquery' ), ultimatemember_version, true );

			wp_register_script( 'um-datetime', $this->js_baseurl . 'pickadate/picker.js', array( 'jquery' ), ultimatemember_version, true );
			wp_register_script( 'um-datetime-date', $this->js_baseurl . 'pickadate/picker.date.js'. array( 'jquery' ), ultimatemember_version, true );
			wp_register_script( 'um-datetime-time', $this->js_baseurl . 'pickadate/picker.time.js', array( 'jquery' ), ultimatemember_version, true );
			wp_register_script( 'um-datetime-legacy', $this->js_baseurl . 'pickadate/legacy.js', array( 'jquery' ), ultimatemember_version, true );

			// load a localized version for date/time
			$locale = get_locale();
			if ( $locale && file_exists( um_path . 'assets/js/pickadate/translations/' . $locale . '.js' ) ) {
				wp_register_script( 'um-datetime-locale', $this->js_baseurl . 'pickadate/translations/' . $locale . '.js', array( 'um-datetime' ), ultimatemember_version, true );
			}

			if ( class_exists( 'WooCommerce' ) ) {
				wp_dequeue_style( 'select2' );
				wp_deregister_style( 'select2' );

				wp_dequeue_script( 'select2' );
				wp_deregister_script( 'select2' );
			}

			wp_register_script( 'select2', $this->js_baseurl . 'select2/select2.full.min.js', array( 'jquery', 'jquery-masonry' ), ultimatemember_version, true );
			wp_register_script( 'um-tipsy', $this->js_baseurl . 'um-tipsy' . $this->suffix . '.js', array( 'jquery' ), ultimatemember_version, true );
			wp_register_script( 'um-raty', $this->js_baseurl . 'um-raty' . $this->suffix . '.js', array( 'jquery' ), ultimatemember_version, true );
			wp_register_script( 'um-crop', $this->js_baseurl . 'um-crop' . $this->suffix . '.js', array( 'jquery' ), ultimatemember_version, true );

			//wp_register_script( 'um-functions', $this->js_baseurl . 'um-functions' . $this->suffix . '.js', array( 'jquery-masonry', 'um-fileupload', 'um-crop' ), ultimatemember_version, true );
			wp_register_script( 'um-scripts', $this->js_baseurl . 'um-scripts' . $this->suffix . '.js', array( 'um-tipsy', 'um-raty', 'um-crop', 'select2', 'um-jquery-form', 'um-fileupload' ), ultimatemember_version, true );

			wp_register_script( 'um-responsive', $this->js_baseurl . 'um-responsive' . $this->suffix . '.js', array( 'um-scripts' ), ultimatemember_version, true );
			wp_register_script( 'um-modal', $this->js_baseurl . 'um-modal' . $this->suffix . '.js', array( 'um-responsive' ), ultimatemember_version, true );

			//registered searchform scripts
			wp_register_script( 'um-searchform', $this->js_baseurl . 'um-searchform' . $this->suffix . '.js', array( 'jquery' ), ultimatemember_version, true );

			//register account scripts
			//todo: Only Account
			wp_register_script( 'um-account', $this->js_baseurl . 'um-account' . $this->suffix . '.js', array( 'um-modal' ), ultimatemember_version, true );



			//register member directory scripts
			//todo: Only Members Directory
			wp_register_script( 'um-members', $this->js_baseurl . 'um-members' . $this->suffix . '.js', array( 'um-modal' ), ultimatemember_version, true );



			wp_register_script( 'um-conditional', $this->js_baseurl . 'um-conditional' . $this->suffix . '.js', array( 'jquery' ), ultimatemember_version, true );

			//register profile scripts
			//todo: Only Profile Page (Forms)
			wp_register_script( 'um-profile', $this->js_baseurl . 'um-profile' . $this->suffix . '.js', array( 'um-responsive', 'um-modal', 'um-conditional' ), ultimatemember_version, true );


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
				'ajaxurl'               => admin_url( 'admin-ajax.php' ),
				'fileupload'            => UM()->get_ajax_route( 'um\core\Files', 'ajax_file_upload' ),
				'imageupload'           => UM()->get_ajax_route( 'um\core\Files', 'ajax_image_upload' ),
				'remove_file'           => UM()->get_ajax_route( 'um\core\Files', 'ajax_remove_file' ),
				'delete_profile_photo'  => UM()->get_ajax_route( 'um\core\Profile', 'ajax_delete_profile_photo' ),
				'delete_cover_photo'    => UM()->get_ajax_route( 'um\core\Profile', 'ajax_delete_cover_photo' ),
				'resize_image'          => UM()->get_ajax_route( 'um\core\Files', 'ajax_resize_image' ),
				'muted_action'          => UM()->get_ajax_route( 'um\core\Form', 'ajax_muted_action' ),
				'ajax_paginate'         => UM()->get_ajax_route( 'um\core\Query', 'ajax_paginate' ),
				'ajax_select_options'   => UM()->get_ajax_route( 'um\core\Form', 'ajax_select_options' ),
			) );

			wp_localize_script( 'um-scripts', 'um_scripts', $localize_data );








			//STYLES

			wp_register_style( 'um-styles', $this->css_baseurl . 'um-styles.css', array(), ultimatemember_version );
			wp_register_style( 'um-misc', $this->css_baseurl . 'um-misc.css', array( 'um-styles' ), ultimatemember_version );

			//FontAwesome and FontIcons styles
			wp_register_style( 'um-fonticons-ii', $this->css_baseurl . 'um-fonticons-ii.css', array(), ultimatemember_version );
			wp_register_style( 'um-fonticons-fa', $this->css_baseurl . 'um-fonticons-fa.css', array(), ultimatemember_version );
			wp_register_style( 'um-crop', $this->css_baseurl . 'um-crop.css', array(), ultimatemember_version );
			wp_register_style( 'um-tipsy', $this->css_baseurl . 'um-tipsy.css', array(), ultimatemember_version );
			wp_register_style( 'um-raty', $this->css_baseurl . 'um-raty.css', array(), ultimatemember_version );
			wp_register_style( 'select2', $this->css_baseurl . 'select2/select2.min.css', array(), ultimatemember_version );
			wp_register_style( 'um-fileupload', $this->css_baseurl . 'um-fileupload.css', array(), ultimatemember_version );
			wp_register_style( 'um-datetime', $this->css_baseurl . 'pickadate/default.css', array(), ultimatemember_version );
			wp_register_style( 'um-datetime-date', $this->css_baseurl . 'pickadate/default.date.css', array( 'um-datetime' ), ultimatemember_version );
			wp_register_style( 'um-datetime-time', $this->css_baseurl . 'pickadate/default.time.css', array( 'um-datetime' ), ultimatemember_version );
			wp_register_style( 'um-scrollbar', $this->css_baseurl . 'um-scrollbar.css', array(), ultimatemember_version );

			wp_register_style( 'um-responsive', $this->css_baseurl . 'um-responsive.css', array(), ultimatemember_version );
			wp_register_style( 'um-modal', $this->css_baseurl . 'um-modal.css', array(), ultimatemember_version );

			$searchform_deps = array( 'um-misc', 'um-fonticons-ii', 'um-fonticons-fa' );
			$account_deps = array( 'select2', 'um-raty', 'um-tipsy', 'um-misc', 'um-fonticons-ii', 'um-fonticons-fa', 'um-responsive', 'um-modal' );
			$members_deps = array( 'select2', 'um-misc', 'um-fonticons-ii', 'um-fonticons-fa', 'um-responsive', 'um-modal' );
			$profile_deps = array( 'select2', 'um-raty', 'um-crop', 'um-tipsy', 'um-misc', 'um-fonticons-ii', 'um-fonticons-fa', 'um-responsive', 'um-modal' );

			// rtl style
			if ( is_rtl() ) {
				wp_register_style( 'um-rtl', $this->css_baseurl . 'um.rtl.css', array(), ultimatemember_version );

				$searchform_deps = array_merge( $searchform_deps, array( 'um-rtl' ) );
				$account_deps = array_merge( $account_deps, array( 'um-rtl' ) );
				$members_deps = array_merge( $members_deps, array( 'um-rtl' ) );
				$profile_deps = array_merge( $profile_deps, array( 'um-rtl' ) );
			}

			wp_register_style( 'um-searchform', $this->css_baseurl . 'um-searchform.css', $searchform_deps, ultimatemember_version );

			wp_register_style( 'um-account', $this->css_baseurl . 'um-account.css', $account_deps, ultimatemember_version );


			wp_register_style( 'um-members', $this->css_baseurl . 'um-members.css', $members_deps, ultimatemember_version );


			wp_register_style( 'um-profile', $this->css_baseurl . 'um-profile.css', $profile_deps, ultimatemember_version );
		}


		/**
		 * Minify css string
		 *
		 * @param $css
		 *
		 * @return mixed
		 */
		function minify( $css ) {
			$css = str_replace(array("\r", "\n"), '', $css);
			$css = str_replace(' {','{', $css );
			$css = str_replace('{ ','{', $css );
			$css = str_replace('; ',';', $css );
			$css = str_replace(';}','}', $css );
			$css = str_replace(': ',':', $css );
			return $css;
		}


		/**
		 * Enqueue scripts and styles
		 */
		function wp_enqueue_scripts() {
			global $post;

			$this->suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || defined( 'UM_SCRIPT_DEBUG' ) ) ? '' : '.min';

			if ( ! is_admin() ) {
				$c_url = UM()->permalinks()->get_current_url( get_option( 'permalink_structure' ) );

				$exclude = UM()->options()->get( 'js_css_exclude' );
				if ( is_array( $exclude ) ) {
					array_filter( $exclude );
				}

				if ( $exclude && is_array( $exclude ) ) {
					foreach ( $exclude as $match ) {
						$sub_match = untrailingslashit( $match );
						if ( ! empty( $c_url ) && ! empty( $sub_match ) && strstr( $c_url, $sub_match ) ) {
							return;
						}
					}
				}

				$include = UM()->options()->get( 'js_css_include' );
				if ( is_array( $include ) ) {
					array_filter( $include );
				}

				if ( $include && is_array( $include ) ) {
					foreach ( $include as $match ) {
						$sub_match = untrailingslashit( $match );
						if ( ! empty( $c_url ) && ! empty( $sub_match ) && strstr( $c_url, $sub_match ) ) {
							$force_load = true;
						} else {
							if ( ! isset( $force_load ) ) {
								$force_load = false;
							}
						}
					}
				}
			}

			if ( isset( $force_load ) && $force_load == false ) {
				return;
			}




			$this->load_original();
			//wp_localize_script( 'um_scripts', 'um_scripts', $localize_data );

			// rtl style
			if ( is_rtl() ) {
				wp_register_style('um_rtl', um_url . 'assets/css/um.rtl.css', '', ultimatemember_version, 'all' );
				wp_enqueue_style('um_rtl');
			}

			// load a localized version for date/time
			$locale = get_locale();
			if ( $locale && file_exists( um_path . 'assets/js/pickadate/translations/' . $locale . '.js' ) ) {
				wp_register_script('um_datetime_locale', um_url . 'assets/js/pickadate/translations/' . $locale . '.js', '', ultimatemember_version, true );
				wp_enqueue_script('um_datetime_locale');
			}

			if(is_object($post) && has_shortcode($post->post_content,'ultimate-member')) {
				wp_dequeue_script('jquery-form');
			}

			//old settings before UM 2.0 CSS
			wp_register_style('um_default_css', um_url . 'assets/css/um-old-default.css', '', ultimatemember_version, 'all' );
			wp_enqueue_style('um_default_css');

			$uploads        = wp_upload_dir();
			$upload_dir     = $uploads['basedir'] . DIRECTORY_SEPARATOR . 'ultimatemember' . DIRECTORY_SEPARATOR;
			if ( file_exists( $upload_dir . 'um_old_settings.css' ) ) {
				//was the issues with HTTPS
				//wp_register_style('um_old_css', $uploads['baseurl'] . '/ultimatemember/um_old_settings.css' );
				//fixed using "../../"
				wp_register_style('um_old_css', um_url . '../../uploads/ultimatemember/um_old_settings.css' );
				wp_enqueue_style('um_old_css');
			}
		}


		/**
		 * This will load original files (not minified)
		 */
		function load_original() {

			//maybe deprecated
			//$this->load_google_charts();

			//$this->load_fonticons();

			//$this->load_selectjs();

			//$this->load_modal();

			//$this->load_css();

			//$this->load_fileupload();

			//$this->load_datetimepicker();

			//$this->load_raty();

			//$this->load_scrollto();

			//$this->load_scrollbar();

			//$this->load_imagecrop();

			//$this->load_tipsy();

			//$this->load_functions();

			//$this->load_responsive();

			//$this->load_customjs();

		}


		/**
		 * Include Google charts
		 */
		function load_google_charts() {

			wp_register_script('um_gchart', 'https://www.google.com/jsapi' );
			wp_enqueue_script('um_gchart');

		}


		/**
		 * Load plugin css
		 */
		/*function load_css() {

			wp_register_style('um_styles', um_url . 'assets/css/um-styles.css' );
			wp_enqueue_style('um_styles');

			wp_register_style('um_members', um_url . 'assets/css/um-members.css' );
			wp_enqueue_style('um_members');

			wp_register_style('um_profile', um_url . 'assets/css/um-profile.css' );
			wp_enqueue_style('um_profile');

			wp_register_style('um_account', um_url . 'assets/css/um-account.css' );
			wp_enqueue_style('um_account');

			wp_register_style('um_misc', um_url . 'assets/css/um-misc.css' );
			wp_enqueue_style('um_misc');

		}*/


		/**
		 * Load select-dropdowns JS
		 */
		/*function load_selectjs() {

			if ( class_exists( 'WooCommerce' ) ) {
				wp_dequeue_style( 'select2' );
				wp_deregister_style( 'select2' );

				wp_dequeue_script( 'select2');
				wp_deregister_script('select2');
			}

			wp_register_script('select2', um_url . 'assets/js/select2/select2.full.min.js', array('jquery', 'jquery-masonry') );
			wp_enqueue_script('select2');

			wp_register_style('select2', um_url . 'assets/css/select2/select2.min.css' );
			wp_enqueue_style('select2');

		}*/


		/**
		 * Load Fonticons
		 */
		/*function load_fonticons(){

			wp_register_style('um_fonticons_ii', um_url . 'assets/css/um-fonticons-ii.css' );
			wp_enqueue_style('um_fonticons_ii');

			wp_register_style('um_fonticons_fa', um_url . 'assets/css/um-fonticons-fa.css' );
			wp_enqueue_style('um_fonticons_fa');

		}*/


		/**
		 * Load fileupload JS
		 */
		/*function load_fileupload() {

			wp_register_script('um_jquery_form', um_url . 'assets/js/um-jquery-form' . $this->suffix . '.js' );
			wp_enqueue_script('um_jquery_form');

			wp_register_script('um_fileupload', um_url . 'assets/js/um-fileupload' . $this->suffix . '.js' );
			wp_enqueue_script('um_fileupload');

			wp_register_style('um_fileupload', um_url . 'assets/css/um-fileupload.css' );
			wp_enqueue_style('um_fileupload');

			wp_register_script('um_datetime', um_url . 'assets/js/pickadate/picker.js' );
			wp_enqueue_script('um_datetime');

			wp_register_script('um_datetime_date', um_url . 'assets/js/pickadate/picker.date.js' );
			wp_enqueue_script('um_datetime_date');

			wp_register_script('um_datetime_time', um_url . 'assets/js/pickadate/picker.time.js' );
			wp_enqueue_script('um_datetime_time');

			wp_register_script('um_datetime_legacy', um_url . 'assets/js/pickadate/legacy.js' );
			wp_enqueue_script('um_datetime_legacy');

			wp_register_style('um_datetime', um_url . 'assets/css/pickadate/default.css' );
			wp_enqueue_style('um_datetime');

			wp_register_style('um_datetime_date', um_url . 'assets/css/pickadate/default.date.css' );
			wp_enqueue_style('um_datetime_date');

			wp_register_style('um_datetime_time', um_url . 'assets/css/pickadate/default.time.css' );
			wp_enqueue_style('um_datetime_time');

		}*/


		/**
		 * Load JS functions
		 */
		/*function load_functions() {

			wp_register_script('um_functions', um_url . 'assets/js/um-functions' . $this->suffix . '.js', array('jquery', 'jquery-masonry') );
			wp_enqueue_script('um_functions');

		}*/


		/**
		 * Load custom JS
		 */
		/*function load_customjs() {

			wp_register_script('um_conditional', um_url . 'assets/js/um-conditional' . $this->suffix . '.js' );
			wp_enqueue_script('um_conditional');

			wp_register_script('um_scripts', um_url . 'assets/js/um-scripts' . $this->suffix . '.js' );
			wp_enqueue_script('um_scripts');

			wp_register_script('um_members', um_url . 'assets/js/um-members' . $this->suffix . '.js' );
			wp_enqueue_script('um_members');

			wp_register_script('um_profile', um_url . 'assets/js/um-profile' . $this->suffix . '.js' );
			wp_enqueue_script('um_profile');

			wp_register_script('um_account', um_url . 'assets/js/um-account' . $this->suffix . '.js' );
			wp_enqueue_script('um_account');

		}*/


		/**
		 * Load date & time picker
		 */
		/*function load_datetimepicker() {

			wp_register_script('um_datetime', um_url . 'assets/js/pickadate/picker.js' );
			wp_enqueue_script('um_datetime');

			wp_register_script('um_datetime_date', um_url . 'assets/js/pickadate/picker.date.js' );
			wp_enqueue_script('um_datetime_date');

			wp_register_script('um_datetime_time', um_url . 'assets/js/pickadate/picker.time.js' );
			wp_enqueue_script('um_datetime_time');

			wp_register_script('um_datetime_legacy', um_url . 'assets/js/pickadate/legacy.js' );
			wp_enqueue_script('um_datetime_legacy');

			wp_register_style('um_datetime', um_url . 'assets/css/pickadate/default.css' );
			wp_enqueue_style('um_datetime');

			wp_register_style('um_datetime_date', um_url . 'assets/css/pickadate/default.date.css' );
			wp_enqueue_style('um_datetime_date');

			wp_register_style('um_datetime_time', um_url . 'assets/css/pickadate/default.time.css' );
			wp_enqueue_style('um_datetime_time');

		}*/


		/**
		 * Load scrollto
		 */
		/*function load_scrollto(){

			wp_register_script('um_scrollto', um_url . 'assets/js/um-scrollto' . $this->suffix . '.js' );
			wp_enqueue_script('um_scrollto');

		}*/


		/**
		 * Load scrollbar
		 */
		/*function load_scrollbar(){

			wp_register_script('um_scrollbar', um_url . 'assets/js/um-scrollbar' . $this->suffix . '.js' );
			wp_enqueue_script('um_scrollbar');

			wp_register_style('um_scrollbar', um_url . 'assets/css/um-scrollbar.css' );
			wp_enqueue_style('um_scrollbar');

		}*/


		/**
		 * Load rating
		 */
		/*function load_raty(){

			wp_register_script('um_raty', um_url . 'assets/js/um-raty' . $this->suffix . '.js' );
			wp_enqueue_script('um_raty');

			wp_register_style('um_raty', um_url . 'assets/css/um-raty.css' );
			wp_enqueue_style('um_raty');

		}*/


		/**
		 * Load crop script
		 */
		/*function load_imagecrop(){

			wp_register_script('um_crop', um_url . 'assets/js/um-crop' . $this->suffix . '.js' );
			wp_enqueue_script('um_crop');

			wp_register_style('um_crop', um_url . 'assets/css/um-crop.css' );
			wp_enqueue_style('um_crop');

		}*/


		/**
		 * Load tipsy
		 */
		/*function load_tipsy(){

			wp_register_script('um_tipsy', um_url . 'assets/js/um-tipsy' . $this->suffix . '.js' );
			wp_enqueue_script('um_tipsy');

			wp_register_style('um_tipsy', um_url . 'assets/css/um-tipsy.css' );
			wp_enqueue_style('um_tipsy');

		}*/


		/**
		 * Load modal
		 */
		/*function load_modal(){

			wp_register_style('um_modal', um_url . 'assets/css/um-modal.css' );
			wp_enqueue_style('um_modal');

			wp_register_script('um_modal', um_url . 'assets/js/um-modal' . $this->suffix . '.js' );
			wp_enqueue_script('um_modal');

		}*/


		/**
		 * Load responsive styles
		 */
		/*function load_responsive(){

			wp_register_script('um_responsive', um_url . 'assets/js/um-responsive' . $this->suffix . '.js' );
			wp_enqueue_script('um_responsive');

			wp_register_style('um_responsive', um_url . 'assets/css/um-responsive.css' );
			wp_enqueue_style('um_responsive');

		}*/

	}
}