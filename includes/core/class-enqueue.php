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
			wp_register_script( 'um-datetime-date', $this->js_baseurl . 'pickadate/picker.date.js', array( 'jquery' ), ultimatemember_version, true );
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

			wp_register_script( 'um-functions', $this->js_baseurl . 'um-functions' . $this->suffix . '.js', array( 'jquery', 'wp-util', 'um-tipsy', 'um-scrollbar' ), ultimatemember_version, true );
			wp_register_script( 'um-scripts', $this->js_baseurl . 'um-scripts' . $this->suffix . '.js', array( 'um-functions', 'um-tipsy', 'um-raty', 'um-crop', 'select2', 'um-jquery-form', 'um-fileupload' ), ultimatemember_version, true );

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
			wp_register_script( 'um-gdpr', $this->js_baseurl . 'um-gdpr' . $this->suffix . '.js', array( 'jquery' ), ultimatemember_version, true );

			//todo: Only Profile Page (Forms)
			wp_register_script( 'um-profile', $this->js_baseurl . 'um-profile' . $this->suffix . '.js', array( 'um-responsive', 'um-modal', 'um-conditional', 'um-gdpr' ), ultimatemember_version, true );


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

			wp_localize_script( 'um-scripts', 'um_scripts', $localize_data );



			//STYLES
			//old settings before UM 2.0 CSS
			wp_register_style( 'um-default-css', $this->css_baseurl . 'um-old-default.css', array(), ultimatemember_version, 'all' );

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


			$style_deps = array(
				'searchform'    => array( 'um-default-css', 'um-misc', 'um-fonticons-ii', 'um-fonticons-fa' ),
				'account'       => array( 'um-default-css', 'um-misc', 'um-fonticons-ii', 'um-fonticons-fa', 'select2', 'um-raty', 'um-tipsy', 'um-responsive', 'um-modal' ),
				'members'       => array( 'um-default-css', 'um-misc', 'um-fonticons-ii', 'um-fonticons-fa', 'select2', 'um-responsive', 'um-modal' ),
				'profile'       => array( 'um-default-css', 'um-misc', 'um-fonticons-ii', 'um-fonticons-fa', 'select2', 'um-raty', 'um-tipsy', 'um-crop', 'um-fileupload', 'um-datetime-time', 'um-datetime-date', 'um-scrollbar', 'um-responsive', 'um-modal' ),
			);

			// until 2.0 style
			$uploads        = wp_upload_dir();
			$upload_dir     = $uploads['basedir'] . DIRECTORY_SEPARATOR . 'ultimatemember' . DIRECTORY_SEPARATOR;
			if ( file_exists( $upload_dir . 'um_old_settings.css' ) ) {
				//was the issues with HTTPS
				//wp_register_style('um_old_css', $uploads['baseurl'] . '/ultimatemember/um_old_settings.css' );
				//fixed using "../../"
				wp_register_style( 'um-old-css', um_url . '../../uploads/ultimatemember/um_old_settings.css', array(), ultimatemember_version );

				foreach ( $style_deps as &$deps ) {
					$deps[] = 'um-old-css';
				}
			}

			// rtl style
			if ( is_rtl() ) {
				wp_register_style( 'um-rtl', $this->css_baseurl . 'um.rtl.css', array(), ultimatemember_version );

				foreach ( $style_deps as &$deps ) {
					$deps[] = 'um-rtl';
				}
			}

			wp_register_style( 'um-searchform', $this->css_baseurl . 'um-searchform.css', $style_deps['searchform'], ultimatemember_version );

			wp_register_style( 'um-account', $this->css_baseurl . 'um-account.css', $style_deps['account'], ultimatemember_version );

			wp_register_style( 'um-members', $this->css_baseurl . 'um-members.css', $style_deps['members'], ultimatemember_version );

			wp_register_style( 'um-profile', $this->css_baseurl . 'um-profile.css', $style_deps['profile'], ultimatemember_version );
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
	}
}