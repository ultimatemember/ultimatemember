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
		var $front_js_baseurl;


		/**
		 * @var string
		 */
		var $front_css_baseurl;


		/**
		 * @var string
		 */
		var $js_baseurl;


		/**
		 * @var string
		 */
		var $css_baseurl;


		/**
		 * @var string
		 */
		var $suffix;


		/**
		 * @var bool
		 */
		var $wp_user_screen;


		/**
		 * @var bool
		 */
		var $um_cpt_form_screen;


		/**
		 * @var bool
		 */
		var $um_settings_screen;


		/**
		 * @var bool
		 */
		var $um_dashboard_screen;


		/**
		 * @var bool
		 */
		var $wp_nav_menus_screen;


		/**
		 * Admin_Enqueue constructor.
		 */
		function __construct() {
			$this->js_baseurl = um_url . 'includes/admin/assets/js/';
			$this->css_baseurl = um_url . 'includes/admin/assets/css/';

			$this->front_js_baseurl = um_url . 'assets/js/';
			$this->front_css_baseurl = um_url . 'assets/css/';

			$this->suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || defined( 'UM_SCRIPT_DEBUG' ) ) ? '' : '.min';

			$this->wp_user_screen = false;
			$this->um_cpt_form_screen = false;
			$this->um_settings_screen = false;
			$this->um_dashboard_screen = false;
			$this->wp_nav_menus_screen = false;
			$this->um_extensions_screen = false;
			$this->um_roles_screen = false;

			add_action( 'admin_head', array( &$this, 'admin_head' ), 9 );

			add_action( 'load-user-new.php', array( &$this, 'enqueue_role_wrapper' ) );
			add_action( 'load-user-edit.php', array( &$this, 'enqueue_role_wrapper' ) );

			add_action( 'load-post-new.php', array( &$this, 'enqueue_cpt_scripts' ) );
			add_action( 'load-post.php', array( &$this, 'enqueue_cpt_scripts' ) );

			add_action( 'load-nav-menus.php', array( &$this, 'enqueue_nav_menus_scripts' ) );

			add_action( 'admin_init',  array( &$this, 'enqueue_um_settings_scripts' ) );

			add_action( 'admin_enqueue_scripts',  array( &$this, 'admin_enqueue_scripts' ) );
		}


		/**
		 * Add inline style on UM CPT pages
		 */
		function admin_head() {
			if ( UM()->admin()->is_plugin_post_type() ) { ?>
				<style type="text/css">
					.um-admin.post-type-<?php echo get_post_type(); ?> div#slugdiv,
					.um-admin.post-type-<?php echo get_post_type(); ?> div#minor-publishing,
					.um-admin.post-type-<?php echo get_post_type(); ?> div#screen-meta-links {
						display: none;
					}
				</style>
			<?php }
		}


		/**
		 *
		 */
		function enqueue_role_wrapper() {
			$this->wp_user_screen = true;
		}


		/**
		 *
		 */
		function enqueue_nav_menus_scripts() {
			$this->wp_nav_menus_screen = true;
		}


		/**
		 *
		 */
		function enqueue_cpt_scripts() {
			if ( ( isset( $_GET['post_type'] ) && 'um_form' == $_GET['post_type'] ) || ( isset( $_GET['post'] ) && 'um_form' == get_post_type( $_GET['post'] ) ) ) {
				$this->um_cpt_form_screen = true;
			}
		}


		/**
		 *
		 */
		function enqueue_um_settings_scripts() {
			if ( isset( $_GET['page'] ) && 'um_options' == $_GET['page'] ) {
				$this->um_settings_screen = true;
			} elseif ( isset( $_GET['page'] ) && 'ultimatemember' == $_GET['page'] ) {
				$this->um_dashboard_screen = true;
			} elseif ( isset( $_GET['page'] ) && 'ultimatemember-extensions' == $_GET['page'] ) {
				$this->um_extensions_screen = true;
			} elseif ( isset( $_GET['page'] ) && 'um_roles' == $_GET['page'] ) {
				$this->um_roles_screen = true;
			}
		}


		function enqueue_frontend_preview_assets() {
			//scripts for FRONTEND PREVIEW
			wp_register_script( 'um-scrollbar', $this->front_js_baseurl . 'um-scrollbar' . $this->suffix . '.js', array( 'jquery' ), ultimatemember_version, true );

			if ( class_exists( 'WooCommerce' ) ) {
				wp_dequeue_style( 'select2' );
				wp_deregister_style( 'select2' );

				wp_dequeue_script( 'select2' );
				wp_deregister_script( 'select2' );
			}

			wp_register_script( 'select2', $this->front_js_baseurl . 'select2/select2.full.min.js', array( 'jquery', 'jquery-masonry' ), ultimatemember_version, true );


			wp_register_script( 'um-jquery-form', $this->front_js_baseurl . 'um-jquery-form' . $this->suffix . '.js', array( 'jquery' ), ultimatemember_version, true );
			wp_register_script( 'um-fileupload', $this->front_js_baseurl . 'um-fileupload' . $this->suffix . '.js', array( 'jquery' ), ultimatemember_version, true );

			wp_register_script( 'um-crop', $this->front_js_baseurl . 'um-crop' . $this->suffix . '.js', array( 'jquery' ), ultimatemember_version, true );
			wp_register_script( 'um-raty', $this->front_js_baseurl . 'um-raty' . $this->suffix . '.js', array( 'jquery' ), ultimatemember_version, true );

			wp_register_script( 'um-functions', $this->front_js_baseurl . 'um-functions' . $this->suffix . '.js', array( 'jquery', 'um-tipsy', 'um-scrollbar' ), ultimatemember_version, true );
			wp_register_script( 'um-scripts', $this->front_js_baseurl . 'um-scripts' . $this->suffix . '.js', array( 'um-functions', 'um-tipsy', 'um-raty', 'um-crop', 'select2', 'um-jquery-form', 'um-fileupload' ), ultimatemember_version, true );
			wp_register_script( 'um-responsive', $this->front_js_baseurl . 'um-responsive' . $this->suffix . '.js', array( 'um-scripts' ), ultimatemember_version, true );
			wp_register_script( 'um-modal', $this->front_js_baseurl . 'um-modal' . $this->suffix . '.js', array( 'um-responsive' ), ultimatemember_version, true );

			wp_enqueue_script( 'um-modal' );

			wp_register_style( 'um-crop', $this->front_css_baseurl . 'um-crop.css', array(), ultimatemember_version );
			wp_register_style( 'um-raty', $this->front_css_baseurl . 'um-raty.css', array(), ultimatemember_version );
			wp_register_style( 'um-responsive', $this->front_css_baseurl . 'um-responsive.css', array(), ultimatemember_version );
			wp_register_style( 'um-modal', $this->front_css_baseurl . 'um-modal.css', array(), ultimatemember_version );

			wp_register_style( 'um-styles', $this->front_css_baseurl . 'um-styles.css', array(), ultimatemember_version );
			wp_register_style( 'um-members', $this->front_css_baseurl . 'um-members.css', array(), ultimatemember_version );
			wp_register_style( 'um-profile', $this->front_css_baseurl . 'um-profile.css', array(), ultimatemember_version );
			wp_register_style( 'um-account', $this->front_css_baseurl . 'um-account.css', array(), ultimatemember_version );
			wp_register_style( 'um-misc', $this->front_css_baseurl . 'um-misc.css', array(), ultimatemember_version );

			//old settings before UM 2.0 CSS
			wp_register_style( 'um-default-css', $this->front_css_baseurl . 'um-old-default.css', array( 'um-crop', 'um-tipsy', 'um-raty', 'um-responsive', 'um-modal', 'um-styles', 'um-members', 'um-profile', 'um-account', 'um-misc' ), ultimatemember_version, 'all' );
			wp_enqueue_style( 'um-default-css' );
		}


		/**
		 * Enqueue scripts and styles
		 */
		function admin_enqueue_scripts() {

			wp_register_script( 'um-admin-global', $this->js_baseurl . 'um-admin-global.js', array('jquery', 'wp-util'), ultimatemember_version, true );
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
			$localize_data = apply_filters( 'um_admin_enqueue_localize_data',
				array(
					'nonce' => wp_create_nonce( "um-admin-nonce" )
				)
			);

			wp_localize_script( 'um-admin-global', 'um_admin_scripts', $localize_data );
			wp_enqueue_script( 'um-admin-global' );

			wp_register_style( 'um-admin-global', $this->css_baseurl . 'um-admin-global.css', array(), ultimatemember_version );
			wp_enqueue_style( 'um-admin-global' );


			if ( UM()->admin()->is_um_screen() ) {

				wp_register_script( 'um-tipsy', $this->front_js_baseurl . 'um-tipsy' . $this->suffix . '.js', array( 'jquery' ), ultimatemember_version, true );
				wp_register_script( 'um-admin-scripts', $this->js_baseurl . 'um-admin-scripts.js', array( 'jquery', 'wp-util', 'jquery-ui-tooltip', 'wp-color-picker', 'um-tipsy' ), ultimatemember_version, true );

				$modal_deps = array( 'um-admin-scripts' );
				if ( $this->um_cpt_form_screen ) {
					$this->enqueue_frontend_preview_assets();
					$modal_deps[] = 'um-responsive';
				}

				wp_register_script( 'um-admin-modal', $this->js_baseurl . 'um-admin-modal.js', $modal_deps, ultimatemember_version, true );
				$localize_data = array(
					'texts' => array(
						'remove_condition_title'    => __( 'Remove condition', 'ultimate-member' ),
						'rules_limit'               => __( 'You already have 5 rules', 'ultimate-member' ),
						'no_icon'                   => __( 'No Icon', 'ultimate-member' ),
					)
				);
				wp_localize_script( 'um-admin-modal', 'um_admin_modal_data', $localize_data );
				wp_enqueue_script( 'um-admin-modal' );

				wp_register_script( 'um-admin-forms', $this->js_baseurl . 'um-admin-forms.js', array( 'um-admin-scripts', 'media-upload' ), ultimatemember_version, true );
				$localize_data = array(
					'texts' => array(
						'remove' => __( 'Remove', 'ultimate-member' ),
						'select' => __( 'Select', 'ultimate-member' )
					)
				);
				wp_localize_script( 'um-admin-forms', 'um_admin_forms_data', $localize_data );
				wp_enqueue_script( 'um-admin-forms' );




				//FontAwesome and FontIcons styles
				wp_register_style( 'um-fonticons-ii', $this->front_css_baseurl . 'um-fonticons-ii.css', array(), ultimatemember_version );
				wp_register_style( 'um-fonticons-fa', $this->front_css_baseurl . 'um-fonticons-fa.css', array(), ultimatemember_version );
				wp_register_style( 'um-tipsy', $this->front_css_baseurl . 'um-tipsy.css', array(), ultimatemember_version );

				wp_register_style( 'um-admin-forms', $this->css_baseurl . 'um-admin-forms.css', array(), ultimatemember_version );
				wp_register_style( 'um-admin-modal', $this->css_baseurl . 'um-admin-modal.css', array(), ultimatemember_version );
				wp_register_style( 'um-admin-misc', $this->css_baseurl . 'um-admin-misc.css', array( 'um-fonticons-ii', 'um-fonticons-fa', 'um-tipsy', 'um-admin-forms', 'um-admin-modal' ), ultimatemember_version );

				if ( is_rtl() ) {
					wp_register_style( 'um-admin-rtl', $this->css_baseurl . 'um-admin-rtl.css', array(), ultimatemember_version );
				}
			}

			if ( $this->um_cpt_form_screen ) {

				wp_register_script( 'um-admin-builder', $this->js_baseurl . 'um-admin-builder.js', array( 'um-admin-scripts', 'um-admin-modal', 'jquery-ui-draggable', 'jquery-ui-sortable' ), ultimatemember_version, true );
				$localize_data = array(
					'texts' => array(
						'delete_row' => __( 'Delete Row', 'ultimate-member' ),
						'add_field' => __( 'Add Field', 'ultimate-member' ),
						'remove_confirm' => __( 'This will permanently delete this custom field from database', 'ultimate-member' ),
					),
					'hide_footer' => true,
				);
				wp_localize_script( 'um-admin-builder', 'um_admin_builder_data', $localize_data );
				wp_enqueue_script( 'um-admin-builder' );

				wp_register_style( 'um-admin-builder', $this->css_baseurl . 'um-admin-builder.css', array(), ultimatemember_version );
				wp_enqueue_style( 'um-admin-builder' );

			} elseif ( $this->wp_user_screen ) {

				wp_register_script( 'um-admin-role-wrapper', $this->js_baseurl . 'um-admin-role-wrapper.js', array( 'um-admin-scripts' ), ultimatemember_version, true );
				wp_enqueue_script( 'um-admin-role-wrapper' );

				$localize_roles_data = get_option( 'um_roles' );

				wp_localize_script( 'um-admin-role-wrapper', 'um_roles', $localize_roles_data );

			} elseif ( $this->um_settings_screen ) {

				wp_register_script( 'um-admin-settings', $this->js_baseurl . 'um-admin-settings.js', array( 'um-admin-scripts' ), ultimatemember_version, true );
				wp_enqueue_script( 'um-admin-settings' );

				$localize_data = array(
					'texts' => array(
						'beforeunload'  => __( 'Are sure, maybe some settings not saved', 'ultimate-member' )
					)
				);

				wp_localize_script( 'um-admin-settings', 'um_admin_settings_data', $localize_data );

				wp_register_style( 'um-admin-settings', $this->css_baseurl . 'um-admin-settings.css', array(), ultimatemember_version );
				wp_enqueue_style( 'um-admin-settings' );

			} elseif ( $this->um_dashboard_screen ) {

				wp_register_style( 'um-admin-dashboard', $this->css_baseurl . 'um-admin-dashboard.css', array(), ultimatemember_version );
				wp_enqueue_style( 'um-admin-dashboard' );

			} elseif ( $this->um_extensions_screen ) {

				wp_register_style( 'um-admin-extensions', $this->css_baseurl . 'um-admin-extensions.css', array(), ultimatemember_version );
				wp_enqueue_style( 'um-admin-extensions' );

			} elseif ( $this->um_roles_screen ) {

				wp_register_style( 'um-admin-roles', $this->css_baseurl . 'um-admin-roles.css', array(), ultimatemember_version );
				wp_enqueue_style( 'um-admin-roles' );

			} elseif ( $this->wp_nav_menus_screen ) {

				wp_register_script( 'um-admin-nav-menus', $this->js_baseurl . 'um-admin-nav-menu.js', array( 'um-admin-scripts' ), ultimatemember_version, true );
				wp_enqueue_script( 'um-admin-nav-menus' );

				$menu_restriction_data = array();
				$menus = get_posts( 'post_type=nav_menu_item&numberposts=-1' );
				foreach ( $menus as $data ) {
					$_nav_roles_meta = get_post_meta( $data->ID, 'menu-item-um_nav_roles', true );

					$um_nav_roles = array();
					if ( $_nav_roles_meta ) {
						foreach ( $_nav_roles_meta as $key => $value ) {
							if ( is_int( $key ) ) {
								$um_nav_roles[] = $value;
							}
						}
					}

					$menu_restriction_data[ $data->ID ] = array(
						'um_nav_public' => get_post_meta( $data->ID, 'menu-item-um_nav_public', true ),
						'um_nav_roles'  => $um_nav_roles,
					);
				}
				wp_localize_script( 'um-admin-nav-menus', 'um_menu_restriction_data', $menu_restriction_data );

				wp_register_style( 'um-admin-nav-menu', $this->css_baseurl . 'um-admin-nav-menu.css', array(), ultimatemember_version );
				wp_enqueue_style( 'um-admin-nav-menu' );
			}

			if ( UM()->admin()->is_um_screen() ) {
				wp_enqueue_style( 'um-admin-misc' );

				if ( is_rtl() ) {
					wp_enqueue_style( 'um-admin-rtl' );
				}
			}

		}

	}
}