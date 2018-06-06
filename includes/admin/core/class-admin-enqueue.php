<?php
namespace um\admin\core;

// Exit if accessed directly.
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
		 * Admin_Enqueue constructor.
		 */
		function __construct() {
			$this->slug = 'ultimatemember';

			$this->js_url = um_url . 'includes/admin/assets/js/';
			$this->css_url = um_url . 'includes/admin/assets/css/';

			add_action( 'admin_head', array( &$this, 'admin_head' ), 9 );

			add_action( 'admin_enqueue_scripts',  array( &$this, 'admin_enqueue_scripts' ) );

			add_filter( 'admin_body_class', array( &$this, 'admin_body_class' ), 999 );

			add_filter( 'enter_title_here', array( &$this, 'enter_title_here' ) );

			add_action( 'load-user-new.php', array( &$this, 'enqueue_role_wrapper' ) );
			add_action( 'load-user-edit.php', array( &$this, 'enqueue_role_wrapper' ) );
		}


		function enqueue_role_wrapper() {
			add_action( 'admin_enqueue_scripts',  array( &$this, 'load_role_wrapper' ) );
		}


		/**
		 * Load js for Add/Edit User form
		 */
		function load_role_wrapper() {
			wp_register_script( 'um_admin_role_wrapper', $this->js_url . 'um-admin-role-wrapper.js', '', '', true );
			wp_enqueue_script( 'um_admin_role_wrapper' );

			$localize_roles_data =  get_option( 'um_roles' );

			wp_localize_script( 'um_admin_settings', 'um_roles', $localize_roles_data );
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
			if ( 'um_directory' == $screen->post_type ){
				$title = 'e.g. Member Directory';
			}
			if ( 'um_form' == $screen->post_type ){
				$title = 'e.g. New Registration Form';
			}
			return $title;
		}


		/**
		 * Runs on admin head
		 */
		function admin_head() {
			if ( UM()->admin()->is_plugin_post_type() ) { ?>
				<style type="text/css">
					.um-admin.post-type-<?php echo get_post_type(); ?> div#slugdiv,
					.um-admin.post-type-<?php echo get_post_type(); ?> div#minor-publishing,
					.um-admin.post-type-<?php echo get_post_type(); ?> div#screen-meta-links
					{display:none}
				</style>
			<?php }
		}


		/**
		 * Load Form
		 */
		function load_form() {
			wp_register_style( 'um_admin_form', $this->css_url . 'um-admin-form.css' );
			wp_enqueue_style( 'um_admin_form' );

			wp_register_script( 'um_admin_form', $this->js_url . 'um-admin-form.js', '', '', true );
			wp_enqueue_script( 'um_admin_form' );
		}


		/**
		 * Load Forms
		 */
		function load_forms() {
			wp_register_style( 'um_admin_forms', $this->css_url . 'um-admin-forms.css' );
			wp_enqueue_style( 'um_admin_forms' );

			wp_register_script( 'um_admin_forms', $this->js_url . 'um-admin-forms.js', '', '', true );
			wp_enqueue_script( 'um_admin_forms' );

			$localize_data = array(
				'texts' => array(
					'remove' => __( 'Remove', 'ultimate-member' ),
					'select' => __( 'Select', 'ultimate-member' )
				)
			);

			wp_localize_script( 'um_admin_forms', 'php_data', $localize_data );
		}


		/**
		 * Load dashboard
		 */
		function load_dashboard() {
			wp_register_style( 'um_admin_dashboard', $this->css_url . 'um-admin-dashboard.css' );
			wp_enqueue_style( 'um_admin_dashboard' );

			wp_register_script( 'um_admin_dashboard', $this->js_url . 'um-admin-dashboard.js', '', '', true );
			wp_enqueue_script( 'um_admin_dashboard' );
		}


		/**
		 * Load settings
		 */
		function load_settings() {
			wp_register_style( 'um_admin_settings', $this->css_url . 'um-admin-settings.css' );
			wp_enqueue_style( 'um_admin_settings' );

			wp_register_script( 'um_admin_settings', $this->js_url . 'um-admin-settings.js', '', '', true );
			wp_enqueue_script( 'um_admin_settings' );

			$localize_data = array(
				'delete_email_template' => UM()->get_ajax_route( 'um\core\Mail', 'delete_email_template' ),
				'onbeforeunload_text' => __( 'Are sure, maybe some settings not saved', 'ultimate-member' ),
				'texts' => array(
					'remove' => __( 'Remove', 'ultimate-member' ),
					'select' => __( 'Select', 'ultimate-member' )
				)
			);

			wp_localize_script( 'um_admin_settings', 'php_data', $localize_data );
		}


		/**
		 * Load modal
		 */
		function load_modal() {
			wp_register_style( 'um_admin_modal', $this->css_url . 'um-admin-modal.css' );
			wp_enqueue_style( 'um_admin_modal' );

			wp_register_script( 'um_admin_modal', $this->js_url . 'um-admin-modal.js', '', '', true );
			wp_enqueue_script( 'um_admin_modal' );

			$localize_data = array(
				'ajax_url' => UM()->get_ajax_route( 'um\admin\core\Admin_Builder', 'dynamic_modal_content' ),
				'dropdown_ajax_url' => UM()->get_ajax_route( 'um\admin\core\Admin_Builder', 'populate_dropdown_options' ),
			);
			wp_localize_script( 'um_admin_modal', 'um_admin_modal_data', $localize_data );
		}


		/**
		 * Field Processing
		 */
		function load_field() {
			wp_register_script( 'um_admin_field', $this->js_url . 'um-admin-field.js', '', '', true );
			wp_enqueue_script( 'um_admin_field' );

			$localize_data = array(
				'ajax_url' => UM()->get_ajax_route( 'um\admin\core\Admin_Builder', 'update_field' ),
				'do_ajax_url' => UM()->get_ajax_route( 'um\core\Fields', 'do_ajax_action' ),
			);
			wp_localize_script( 'um_admin_field', 'um_admin_field_data', $localize_data );
		}


		/**
		 * Load Builder
		 */
		function load_builder() {
			wp_register_script( 'um_admin_builder', $this->js_url . 'um-admin-builder.js', '', '', true );
			wp_enqueue_script( 'um_admin_builder' );

			//hide footer text on add/edit UM Forms
			//layouts crashed because we load and hide metaboxes
			//and WP calculate page height
			$hide_footer = false;
			global $pagenow, $post;
			if ( ( 'post.php' == $pagenow || 'post-new.php' == $pagenow ) &&
			     ( ( isset( $_GET['post_type'] ) && 'um_form' == $_GET['post_type'] ) ||
			       ( isset( $post->post_type ) && 'um_form' == $post->post_type ) ) ) {
				$hide_footer = true;
			}

			$localize_data = array(
				'ajax_url' => UM()->get_ajax_route( 'um\admin\core\Admin_Builder', 'update_builder' ),
				'hide_footer' => $hide_footer,
			);
			wp_localize_script( 'um_admin_builder', 'um_admin_builder_data', $localize_data );

			wp_register_script( 'um_admin_dragdrop', $this->js_url . 'um-admin-dragdrop.js', '', '', true );
			wp_enqueue_script( 'um_admin_dragdrop' );


			$localize_data = array(
				'ajax_url' => UM()->get_ajax_route( 'um\admin\core\Admin_DragDrop', 'update_order' ),
			);
			wp_localize_script( 'um_admin_dragdrop', 'um_admin_dragdrop_data', $localize_data );


			wp_register_style( 'um_admin_builder', $this->css_url . 'um-admin-builder.css' );
			wp_enqueue_style( 'um_admin_builder' );
		}


		/**
		 * Load core WP styles/scripts
		 */
		function load_core_wp() {
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'wp-color-picker' );

			wp_enqueue_script( 'jquery-ui-draggable' );
			wp_enqueue_script( 'jquery-ui-sortable' );

			wp_enqueue_script( 'jquery-ui-tooltip' );
		}


		/**
		 * Load Admin Styles
		 */
		function load_css() {
			wp_register_style( 'um_admin_menu', $this->css_url . 'um-admin-menu.css' );
			wp_enqueue_style( 'um_admin_menu' );

			wp_register_style( 'um_admin_columns', $this->css_url . 'um-admin-columns.css' );
			wp_enqueue_style( 'um_admin_columns' );

			wp_register_style( 'um_admin_misc', $this->css_url . 'um-admin-misc.css' );
			wp_enqueue_style( 'um_admin_misc' );
		}


		/**
		 * Load functions js
		 */
		function load_functions() {
			wp_register_script( 'um_functions', um_url . 'assets/js/um-functions' . '.js' );
			wp_enqueue_script( 'um_functions' );
		}


		/**
		 * Load Fonticons
		 */
		function load_fonticons() {
			wp_register_style( 'um_fonticons_ii', um_url . 'assets/css/um-fonticons-ii.css' );
			wp_enqueue_style( 'um_fonticons_ii' );

			wp_register_style( 'um_fonticons_fa', um_url . 'assets/css/um-fonticons-fa.css' );
			wp_enqueue_style( 'um_fonticons_fa' );
		}


		/**
		 * Load global css
		 */
		function load_global_css() {
			wp_register_style( 'um_admin_global', $this->css_url . 'um-admin-global.css' );
			wp_enqueue_style( 'um_admin_global' );
		}


		/**
		 * Load jQuery custom code
		 */
		function load_custom_scripts() {
			wp_register_script( 'um_admin_scripts', $this->js_url . 'um-admin-scripts.js', '', '', true );
			wp_enqueue_script( 'um_admin_scripts' );
		}


		/**
		 * Load jQuery custom code
		 */
		function load_nav_manus_scripts() {
			wp_register_script( 'um_admin_nav_manus', $this->js_url . 'um-admin-nav-menu.js', array('jquery','wp-util'), '', true );
			wp_enqueue_script( 'um_admin_nav_manus' );
		}


		/**
		 * Load AJAX
		 */
		function load_ajax_js() {
			wp_register_script( 'um_admin_ajax', $this->js_url . 'um-admin-ajax.js', '', '', true );
			wp_enqueue_script( 'um_admin_ajax' );

			$localize_data = array(
				'ajax_url' => UM()->get_ajax_route( 'um\core\Fields', 'do_ajax_action' ),
			);
			wp_localize_script( 'um_admin_ajax', 'um_admin_ajax_data', $localize_data );
		}


		/**
		 * Load localize scripts
		 */
		function load_localize_scripts() {

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_admin_enqueue_localize_data
			 * @description Extend localize data at wp-admin side
			 * @input_vars
			 * [{"var":"$localize_data","type":"array","desc":"Localize Data"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_admin_enqueue_localize_data', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_filter( 'um_admin_enqueue_localize_data', 'my_admin_enqueue_localize_data', 10, 1 );
			 * function my_admin_enqueue_localize_data( $localize_data ) {
			 *     // your code here
			 *     return $localize_data;
			 * }
			 * ?>
			 */
			$localize_data = apply_filters('um_admin_enqueue_localize_data', array(
					'ajaxurl' => admin_url( 'admin-ajax.php' )
				)
			);

			wp_localize_script( 'um_admin_scripts', 'um_admin_scripts', $localize_data );
		}


		/**
		 * Adds class to our admin pages
		 *
		 * @param $classes
		 *
		 * @return string
		 */
		function admin_body_class( $classes ) {
			if ( UM()->admin()->is_um_screen() ) {
				return "$classes um-admin";
			}
			return $classes;
		}


		/**
		 * Enqueue scripts and styles
		 */
		function admin_enqueue_scripts() {
			if ( UM()->admin()->is_um_screen() ) {

				/*if ( get_post_type() != 'shop_order' ) {
                    UM()->enqueue()->wp_enqueue_scripts();
                }*/

				$this->load_functions();
				$this->load_global_css();
				$this->load_form();
				$this->load_forms();
				$this->load_modal();
				$this->load_dashboard();
				$this->load_settings();
				$this->load_field();
				$this->load_builder();
				$this->load_css();
				$this->load_core_wp();
				$this->load_ajax_js();
				$this->load_custom_scripts();
				$this->load_fonticons();
				$this->load_localize_scripts();


				//scripts for frontend preview
				UM()->enqueue()->load_imagecrop();
				UM()->enqueue()->load_css();
				UM()->enqueue()->load_tipsy();
				UM()->enqueue()->load_modal();
				UM()->enqueue()->load_responsive();

				wp_register_style( 'um_default_css', um_url . 'assets/css/um-old-default.css', '', ultimatemember_version, 'all' );
				wp_enqueue_style( 'um_default_css' );

				if ( is_rtl() ) {
					wp_register_style( 'um_admin_rtl', $this->css_url . 'um-admin-rtl.css' );
					wp_enqueue_style( 'um_admin_rtl' );
				}
            
			} else {

				$this->load_global_css();

			}

		}

	}
}