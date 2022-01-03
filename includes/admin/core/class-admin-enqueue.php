<?php
namespace um\admin\core;


if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'um\admin\core\Admin_Enqueue' ) ) {


	/**
	 * Class Admin_Enqueue
	 * @package um\admin\core
	 */
	class Admin_Enqueue {


		/**
		 * @var string
		 */
		var $js_url;


		/**
		 * @var string
		 */
		var $css_url;


		/**
		 * @var string
		 */
		var $css_url_v3;


		/**
		 * @var string
		 */
		var $js_url_v3;


		/**
		 * @var string
		 */
		var $front_js_baseurl;


		/**
		 * @var string
		 */
		var $front_css_baseurl;


		/**
		 * @var string
		 */
		var $suffix;


		/**
		 * @var bool
		 */
		var $um_cpt_form_screen;


		/**
		 * @var bool
		 */
		var $post_page;


		/**
		 * Admin_Enqueue constructor.
		 */
		function __construct() {
			$this->js_url = um_url . 'includes/admin/assets/js/';
			$this->css_url = um_url . 'includes/admin/assets/css/';

			$this->js_url_v3 = um_url . 'assets/v3/js/';
			$this->css_url_v3 = um_url . 'assets/v3/css/';

			$this->front_js_baseurl = um_url . 'assets/js/';
			$this->front_css_baseurl = um_url . 'assets/css/';

			$this->suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || defined( 'UM_SCRIPT_DEBUG' ) ) ? '' : '.min';

			$this->um_cpt_form_screen = false;

			add_action( 'admin_enqueue_scripts',  array( &$this, 'admin_enqueue_scripts' ) );

			add_filter( 'enter_title_here', array( &$this, 'enter_title_here' ) );

			add_action( 'load-post-new.php', array( &$this, 'enqueue_cpt_scripts' ) );
			add_action( 'load-post.php', array( &$this, 'enqueue_cpt_scripts' ) );


		}











		/**
		 *
		 */
		function enqueue_cpt_scripts() {
			if ( ( isset( $_GET['post_type'] ) && 'um_form' === sanitize_key( $_GET['post_type'] ) ) ||
			     ( isset( $_GET['post'] ) && 'um_form' === get_post_type( absint( $_GET['post'] ) ) ) ) {
				$this->um_cpt_form_screen = true;
				add_action( 'admin_footer', array( $this, 'admin_footer_scripts' ), 20 );
			}

			$this->post_page = true;
		}


		/**
		 *
		 */
		function enqueue_frontend_preview_assets() {
			//scripts for FRONTEND PREVIEW
			if ( class_exists( 'WooCommerce' ) ) {
				wp_dequeue_style( 'select2' );
				wp_deregister_style( 'select2' );

				wp_dequeue_script( 'select2' );
				wp_deregister_script( 'select2' );
			}


			wp_register_script( 'select2', $this->front_js_baseurl . 'select2/select2.full' . $this->suffix . '.js', array( 'jquery', 'jquery-masonry' ), '4.0.13', true );
			wp_register_script( 'um_jquery_form', $this->front_js_baseurl . 'um-jquery-form' . $this->suffix . '.js', array( 'jquery' ), ultimatemember_version, true );
			wp_register_script( 'um_fileupload', $this->front_js_baseurl . 'um-fileupload.js', array( 'jquery' ), ultimatemember_version, true );
			wp_register_script( 'um_crop', $this->front_js_baseurl . 'um-crop' . $this->suffix . '.js', array( 'jquery' ), ultimatemember_version, true );
			wp_register_script( 'um_tipsy', $this->front_js_baseurl . 'um-tipsy' . $this->suffix . '.js', array( 'jquery' ), ultimatemember_version, true );
			wp_register_script( 'um_functions', $this->front_js_baseurl . 'um-functions' . $this->suffix . '.js', array( 'jquery', 'um_tipsy', 'um_scrollbar' ), ultimatemember_version, true );

			wp_register_script( 'um_datetime', $this->front_js_baseurl . 'pickadate/picker.js', array( 'jquery' ), ultimatemember_version, true );
			wp_register_script( 'um_datetime_date', $this->front_js_baseurl . 'pickadate/picker.date.js', array( 'jquery', 'um_datetime' ), ultimatemember_version, true );
			wp_register_script( 'um_datetime_time', $this->front_js_baseurl . 'pickadate/picker.time.js', array( 'jquery', 'um_datetime' ), ultimatemember_version, true );
			//wp_register_script( 'um_datetime_legacy', $this->front_js_baseurl . 'pickadate/legacy.js', array( 'jquery', 'um_datetime' ), ultimatemember_version, true );
			// load a localized version for date/time
			$locale = get_locale();
			if ( $locale ) {
				if ( file_exists( WP_LANG_DIR . '/plugins/ultimate-member/assets/js/pickadate/' . $locale . '.js' ) ) {
					wp_register_script('um_datetime_locale', content_url() . '/languages/plugins/ultimate-member/assets/js/pickadate/' . $locale . '.js', array( 'jquery', 'um_datetime' ), ultimatemember_version, true );
				} elseif ( file_exists( um_path . 'assets/js/pickadate/translations/' . $locale . '.js' ) ) {
					wp_register_script('um_datetime_locale', um_url . 'assets/js/pickadate/translations/' . $locale . '.js', array( 'jquery', 'um_datetime' ), ultimatemember_version, true );
				}
			}

			wp_register_script( 'um_scripts', $this->front_js_baseurl . 'um-scripts' . $this->suffix . '.js', array( 'um_functions', 'um_crop', 'um_raty', 'select2', 'um_jquery_form', 'um_fileupload', 'um_datetime', 'um_datetime_date', 'um_datetime_time'/*, 'um_datetime_legacy'*/ ), ultimatemember_version, true );
			wp_register_script( 'um_responsive', $this->front_js_baseurl . 'um-responsive' . $this->suffix . '.js', array( 'um_scripts' ), ultimatemember_version, true );
			wp_register_script( 'um_modal', $this->front_js_baseurl . 'um-modal' . $this->suffix . '.js', array( 'um_responsive' ), ultimatemember_version, true );


			wp_register_style( 'select2', $this->front_css_baseurl . 'select2/select2' . $this->suffix . '.css', array(), '4.0.13' );
			wp_register_style( 'um_datetime', $this->front_css_baseurl . 'pickadate/default.css', array(), ultimatemember_version );
			wp_register_style( 'um_datetime_date', $this->front_css_baseurl . 'pickadate/default.date.css', array( 'um_datetime' ), ultimatemember_version );
			wp_register_style( 'um_datetime_time', $this->front_css_baseurl . 'pickadate/default.time.css', array( 'um_datetime' ), ultimatemember_version );

			wp_register_style( 'um_scrollbar', $this->front_css_baseurl . 'simplebar.css', array(), ultimatemember_version );
			wp_register_style( 'um_crop', $this->front_css_baseurl . 'um-crop.css', array(), ultimatemember_version );
			wp_register_style( 'um_tipsy', $this->front_css_baseurl . 'um-tipsy.css', array(), ultimatemember_version );
			wp_register_style( 'um_responsive', $this->front_css_baseurl . 'um-responsive.css', array(), ultimatemember_version );
			wp_register_style( 'um_modal', $this->front_css_baseurl . 'um-modal.css', array(), ultimatemember_version );
			wp_register_style( 'um_styles', $this->front_css_baseurl . 'um-styles.css', array(), ultimatemember_version );
			wp_register_style( 'um_members', $this->front_css_baseurl . 'um-members.css', array(), ultimatemember_version );
			wp_register_style( 'um_profile', $this->front_css_baseurl . 'um-profile.css', array(), ultimatemember_version );
			wp_register_style( 'um_account', $this->front_css_baseurl . 'um-account.css', array(), ultimatemember_version );
			wp_register_style( 'um_misc', $this->front_css_baseurl . 'um-misc.css', array(), ultimatemember_version );
			wp_register_style( 'um_default_css', $this->front_css_baseurl . 'um-old-default.css', array( 'um_crop', 'um_tipsy', 'um_raty', 'um_responsive', 'um_modal', 'um_styles', 'um_members', 'um_profile', 'um_account', 'um_misc', 'um_datetime_date', 'um_datetime_time', 'um_scrollbar', 'select2' ), ultimatemember_version );

			wp_enqueue_script( 'um_modal' );
			wp_enqueue_style( 'um_default_css' );
		}


		/**
		 * Enter title placeholder
		 *
		 * @param $title
		 *
		 * @return string
		 */
		function enter_title_here( $title ) {
			$screen = get_current_screen();
			if ( 'um_directory' == $screen->post_type ) {
				$title = __( 'e.g. Member Directory', 'ultimate-member' );
			} elseif ( 'um_form' == $screen->post_type ) {
				$title = __( 'e.g. New Registration Form', 'ultimate-member' );
			}
			return $title;
		}


		/**
		 * Load modal
		 */
		function load_modal() {
			wp_register_style( 'um_admin_modal', $this->css_url . 'um-admin-modal.css', array( 'wp-color-picker' ), ultimatemember_version );
			wp_enqueue_style( 'um_admin_modal' );

			wp_register_script( 'um_admin_modal', $this->js_url . 'um-admin-modal.js', array( 'jquery', 'editor', 'wp-util', 'wp-color-picker', 'wp-tinymce', 'wp-i18n' ), ultimatemember_version, true );
			wp_enqueue_script( 'um_admin_modal' );
		}



		/**
		 * Load core WP styles/scripts
		 */
		function load_core_wp() {
			wp_enqueue_script(  );
			wp_enqueue_script(  );

			wp_enqueue_script( 'jquery-ui-tooltip' );
		}


		/**
		 * Load functions js
		 */
		function load_functions() {
			wp_register_script( 'um_scrollbar', um_url . 'assets/js/simplebar.js', array( 'jquery' ), ultimatemember_version, true );
			wp_register_script( 'um_functions', um_url . 'assets/js/um-functions.js', array( 'jquery', 'jquery-masonry', 'wp-util', 'um_scrollbar' ), ultimatemember_version, true );
			wp_enqueue_script( 'um_functions' );
		}


		/**
		 * Load Fonticons
		 */
		function load_fonticons() {
			wp_register_style( 'um_fonticons_ii', um_url . 'assets/css/um-fonticons-ii.css', array(), ultimatemember_version );
			wp_enqueue_style( 'um_fonticons_ii' );

			wp_register_style( 'um_fonticons_fa', um_url . 'assets/css/um-fonticons-fa.css', array(), ultimatemember_version );
			wp_enqueue_style( 'um_fonticons_fa' );
		}


		/**
		 * Enqueue scripts and styles
		 */
		function admin_enqueue_scripts() {

			if ( UM()->admin()->is_own_screen() ) {

				$modal_deps = array( 'um-admin-scripts' );
				if ( $this->um_cpt_form_screen ) {
					$this->enqueue_frontend_preview_assets();
					$modal_deps[] = 'um-responsive';
				}

				$this->load_functions();
				$this->load_modal();
				$this->load_core_wp();
				$this->load_fonticons();


				//scripts for frontend preview
				UM()->enqueue()->load_imagecrop();
				UM()->enqueue()->load_css();
				UM()->enqueue()->load_tipsy();
				UM()->enqueue()->load_modal();
				UM()->enqueue()->load_responsive();

				wp_register_script( 'um_raty', um_url . 'assets/js/um-raty' . UM()->enqueue()->suffix . '.js', array( 'jquery', 'wp-i18n' ), ultimatemember_version, true );
				wp_register_style( 'um_raty', um_url . 'assets/css/um-raty.css', array(), ultimatemember_version );

				wp_register_style( 'um_default_css', um_url . 'assets/css/um-old-default.css', '', ultimatemember_version, 'all' );
				wp_enqueue_style( 'um_default_css' );

			}
		}


		/**
		 * Print editor scripts if they are not printed by default
		 */
		function admin_footer_scripts() {
			/**
			 * @var $class \_WP_Editors
			 */
			$class = '\_WP_Editors';

			if ( did_action( 'print_default_editor_scripts' ) ) {
				return;
			}
			if ( did_action( 'wp_tiny_mce_init' ) ) {
				return;
			}
			if ( has_action( 'admin_print_footer_scripts', array( $class, 'editor_js' ) ) ) {
				return;
			}

			if ( ! class_exists( $class, false ) ) {
				require_once( ABSPATH . WPINC . '/class-wp-editor.php' );
			}

			$class::force_uncompressed_tinymce();
			$class::enqueue_scripts();
			$class::editor_js();
		}

	}
}
