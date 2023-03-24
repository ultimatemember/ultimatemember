<?php
namespace um\admin;


if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'um\admin\Enqueue' ) ) {


	/**
	 * Class Enqueue
	 *
	 * @package um\admin
	 */
	final class Enqueue extends \um\common\Enqueue {

		/**
		 * Enqueue constructor.
		 */
		function __construct() {
			parent::__construct();

			add_filter( 'admin_body_class', array( &$this, 'admin_body_class' ), 999 );

			add_action( 'admin_enqueue_scripts', array( &$this, 'register' ), 10 );

			add_action( 'enqueue_block_editor_assets', array( &$this, 'block_editor' ) );

			global $wp_version;
			if ( version_compare( $wp_version, '5.8', '>=' ) ) {
				add_filter( 'block_categories_all', array( &$this, 'blocks_category' ), 10, 2 );
			} else {
				add_filter( 'block_categories', array( &$this, 'blocks_category' ), 10, 2 );
			}

			// @since 3.0
			add_action( 'load-ultimate-member_page_um-modules', array( &$this, 'modules_page' ) );
			add_action( 'load-ultimate-member_page_um_roles', array( &$this, 'roles_page' ) );
			add_action( 'load-ultimate-member_page_um_field_groups', array( &$this, 'field_groups_page' ) );
			add_action( 'load-toplevel_page_ultimatemember', array( &$this, 'settings_page' ) );

			add_action( 'load-customize.php', array( &$this, 'navmenu_page' ) );

			add_action( 'load-user-new.php', array( &$this, 'wp_user_page' ) );
			add_action( 'load-user-edit.php', array( &$this, 'wp_user_page' ) );
			add_action( 'load-users.php', array( &$this, 'wp_users_page' ) );

			add_action( 'load-edit.php', array( &$this, 'posts_page' ) );

			add_action( 'load-post-new.php', array( &$this, 'post_page' ) );
			add_action( 'load-post.php', array( &$this, 'post_page' ) );
		}

		/**
		 * Adds class to our admin pages
		 *
		 * @param $classes
		 *
		 * @return string
		 */
		public function admin_body_class( $classes ) {
			if ( UM()->admin()->screen()->is_own_screen() ) {
				return "$classes um um-admin";
			}
			return $classes;
		}

		/**
		 * @since 3.0
		 */
		function posts_page() {
			if ( isset( $_GET['post_type'] ) && 'um_form' === sanitize_key( $_GET['post_type'] ) ) {
				add_action( 'admin_enqueue_scripts', array( &$this, 'forms_page_scripts' ) );
			}
		}

		/**
		 * @since 3.0
		 */
		function post_page() {
			if ( ( isset( $_GET['post_type'] ) && 'um_form' === sanitize_key( $_GET['post_type'] ) ) ||
			     ( isset( $_GET['post'] ) && 'um_form' === get_post_type( absint( $_GET['post'] ) ) ) ) {
				add_action( 'admin_enqueue_scripts', array( &$this, 'form_page_scripts' ) );
				add_action( 'admin_footer', array( $this, 'form_builder_wp_editor' ), 20 );
			}
		}

		/**
		 * @since 3.0
		 */
		function modules_page() {
			add_action( 'admin_enqueue_scripts', array( &$this, 'modules_page_scripts' ) );
		}

		/**
		 * @since 3.0
		 */
		function roles_page() {
			add_action( 'admin_enqueue_scripts', array( &$this, 'roles_page_scripts' ) );
		}

		/**
		 * @since 3.0
		 */
		function field_groups_page() {
			add_action( 'admin_enqueue_scripts', array( &$this, 'field_groups_page_scripts' ) );
		}

		/**
		 * @since 3.0
		 */
		function settings_page() {
			add_action( 'admin_enqueue_scripts', array( &$this, 'settings_page_scripts' ) );
		}

		/**
		 * @since 3.0
		 */
		function navmenu_page() {
			add_action( 'admin_enqueue_scripts', array( &$this, 'navmenu_page_scripts' ) );
		}

		/**
		 * @since 2.0
		 */
		function wp_user_page() {
			add_action( 'admin_enqueue_scripts',  array( &$this, 'load_role_wrapper' ) );
		}

		/**
		 * @since 3.0
		 */
		function wp_users_page() {
			add_action( 'admin_enqueue_scripts',  array( &$this, 'users_page_scripts' ) );
		}

		/**
		 * @since 3.0
		 */
		function users_page_scripts() {
			wp_register_script( 'um_admin_users', $this->urls['js'] . 'admin/users' . $this->suffix . '.js', array( 'jquery', 'wp-i18n', 'um_admin_modal' ), UM_VERSION, true );
			wp_enqueue_script( 'um_admin_users' );
			wp_enqueue_style( 'um_admin_modal' );
		}

		/**
		 * @since 3.0
		 */
		function modules_page_scripts() {
			wp_register_style( 'um_admin_modules', $this->urls['css'] . 'admin-modules' . $this->suffix . '.css', array(), UM_VERSION );
			wp_enqueue_style( 'um_admin_modules' );
		}

		/**
		 * @since 3.0
		 */
		function roles_page_scripts() {
			wp_register_style( 'um_admin_roles', $this->urls['css'] . 'admin-roles' . $this->suffix . '.css', array(), UM_VERSION );
			wp_enqueue_style( 'um_admin_roles' );
		}

		private function get_all_field_types() {
			$static_settings = UM()->config()->get( 'static_field_settings' );
			$field_types     = UM()->config()->get( 'field_types' );

			foreach ( $field_types as $field_type => &$data ) {
				if ( ! empty( $data['settings'] ) ) {
					$data['settings'] = array_merge_recursive( $static_settings, $data['settings'] );
				} else {
					$data['settings'] = $static_settings;
				}

				$data['settings'] = apply_filters( 'um_fields_settings', $data['settings'], $field_type );

				foreach ( $data['settings'] as $tab_key => &$settings_data ) {
					foreach ( $settings_data as $setting_key => &$setting_data ) {
						if ( array_key_exists( $tab_key, $static_settings ) && array_key_exists( $setting_key, $static_settings[ $tab_key ] ) ) {
							$setting_data['static'] = true;
						}
					}

					if ( empty( $settings_data ) ) {
						unset( $data['settings'][ $tab_key ] );
					}
				}
			}

			return $field_types;
		}

		/**
		 * @since 3.0
		 */
		function field_groups_page_scripts() {
			// Assets for UM wp-admin forms that are used in settings pages and metaboxes
			// jquery is required for jQuery using
			// wp-util is required for wp.ajax.send function
			// um-tipsy is required for tipsy.js
			// wp-color-picker is required for colorpickers init
			// um-helptip is required for help tooltips
			$deps = array( 'jquery', 'wp-util', 'wp-i18n', 'wp-color-picker', 'jquery-ui-sortable', 'jquery-ui-slider', 'jquery-ui-draggable', 'select2', 'um-helptip' );
			wp_register_script( 'um_admin_forms', $this->urls['js'] . 'admin/forms' . $this->suffix . '.js', $deps, UM_VERSION, true );

			$deps = array( 'wp-color-picker', 'um-jquery-ui', 'select2' );
			wp_register_style( 'um_admin_forms', $this->urls['css'] . 'admin-forms' . $this->suffix . '.css', $deps, UM_VERSION );

			wp_register_script( 'um_admin_field_groups', $this->urls['js'] . 'admin/field-groups' . $this->suffix . '.js', array( 'jquery', 'wp-util', 'wp-i18n', 'jquery-ui-sortable', 'jquery-ui-draggable', 'um_admin_forms' ), UM_VERSION, true );
			$field_groups_data = array(
				'field_tabs'        => UM()->config()->get( 'field_settings_tabs' ),
				'field_types'       => $this->get_all_field_types(),
				'conditional_rules' => UM()->config()->get( 'field_conditional_rules' ),
			);
			wp_localize_script( 'um_admin_field_groups', 'um_admin_field_groups_data', $field_groups_data );
			wp_enqueue_script( 'um_admin_field_groups' );

			wp_register_style( 'um_admin_field_groups', $this->urls['css'] . 'admin-field-groups' . $this->suffix . '.css', array( 'um_admin_forms' ), UM_VERSION );
			wp_enqueue_style( 'um_admin_field_groups' );
		}

		/**
		 * @since 3.0
		 */
		function dashboard_page_scripts() {
			do_action( 'um_admin_dashboard_assets_enqueue' );

			wp_register_style( 'um_admin_dashboard', $this->urls['css'] . 'admin-dashboard' . $this->suffix . '.css', array(), UM_VERSION );
			wp_enqueue_style( 'um_admin_dashboard' );
		}

		/**
		 * @since 3.0
		 */
		function settings_page_scripts() {
			wp_register_script( 'um_admin_settings', $this->urls['js'] . 'admin/settings' . $this->suffix . '.js', array( 'jquery', 'wp-i18n' ), UM_VERSION, true );
			wp_register_style( 'um_admin_settings', $this->urls['css'] . 'admin-settings' . $this->suffix . '.css', array(), UM_VERSION );
			wp_enqueue_script( 'um_admin_settings' );
			wp_enqueue_style( 'um_admin_settings' );

			wp_register_style( 'um_admin_modules', $this->urls['css'] . 'admin-modules' . $this->suffix . '.css', array(), UM_VERSION );
			wp_enqueue_style( 'um_admin_modules' );
		}

		/**
		 * @since 3.0
		 */
		function navmenu_page_scripts() {
			wp_register_script( 'um_admin_navmenu', $this->urls['js'] . 'admin/navmenu' . $this->suffix . '.js', array( 'jquery', 'wp-util' ), UM_VERSION, true );
			$menu_restriction_data = array();
			$menus                 = get_posts( 'post_type=nav_menu_item&numberposts=-1' );
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
			wp_localize_script( 'um_admin_navmenu', 'um_menu_restriction_data', $menu_restriction_data );
			wp_enqueue_script( 'um_admin_navmenu' );

			wp_register_style( 'um_admin_navmenu', $this->urls['css'] . 'admin-navmenu' . $this->suffix . '.css', array(), UM_VERSION );
			wp_enqueue_style( 'um_admin_navmenu' );
		}

		/**
		 * @since 3.0
		 */
		function forms_page_scripts() {
			wp_register_script( 'um_admin_forms-screen', $this->urls['js'] . 'admin/forms-screen' . $this->suffix . '.js', array( 'jquery' ), UM_VERSION, true );
			wp_localize_script(
				'um_admin_forms-screen',
				'um_forms_buttons',
				array(
					'login'    => array(
						'title' => __( 'Add New Login Form', 'ultimate-member' ),
						'link'  => add_query_arg( array( 'post_type' => 'um_form', 'um_mode' => 'login' ), admin_url( 'post-new.php' ) ),
					),
					'register' => array(
						'title' => __( 'Add New Register Form', 'ultimate-member' ),
						'link'  => add_query_arg( array( 'post_type' => 'um_form', 'um_mode' => 'register' ), admin_url( 'post-new.php' ) ),
					),
					'profile'  => array(
						'title' => __( 'Add New Profile Form', 'ultimate-member' ),
						'link'  => add_query_arg( array( 'post_type' => 'um_form', 'um_mode' => 'profile' ), admin_url( 'post-new.php' ) ),
					),
				)
			);
			wp_enqueue_script( 'um_admin_forms-screen' );

			wp_register_style( 'um_admin_forms-screen', $this->urls['css'] . 'admin-forms-screen' . $this->suffix . '.css', array(), UM_VERSION );
			wp_enqueue_style( 'um_admin_forms-screen' );
		}

		/**
		 * @since 3.0
		 */
		function form_page_scripts() {
			global $pagenow;
			if ( ! empty( $pagenow ) && 'post.php' === $pagenow ) {
				wp_register_script( 'um_admin_forms-screen', $this->urls['js'] . 'admin/forms-screen' . $this->suffix . '.js', array( 'jquery' ), UM_VERSION, true );
				wp_localize_script(
					'um_admin_forms-screen',
					'um_forms_buttons',
					array(
						'login'    => array(
							'title' => __( 'Add New Login Form', 'ultimate-member' ),
							'link'  => add_query_arg( array( 'post_type' => 'um_form', 'um_mode' => 'login' ), admin_url( 'post-new.php' ) ),
						),
						'register' => array(
							'title' => __( 'Add New Register Form', 'ultimate-member' ),
							'link'  => add_query_arg( array( 'post_type' => 'um_form', 'um_mode' => 'register' ), admin_url( 'post-new.php' ) ),
						),
						'profile'  => array(
							'title' => __( 'Add New Profile Form', 'ultimate-member' ),
							'link'  => add_query_arg( array( 'post_type' => 'um_form', 'um_mode' => 'profile' ), admin_url( 'post-new.php' ) ),
						),
					)
				);
				wp_enqueue_script( 'um_admin_forms-screen' );
			}
			$this->enqueue_frontend_preview_assets();

			wp_register_script( 'um_admin_builder', $this->urls['js'] . 'admin/builder' . $this->suffix . '.js', array( 'jquery', 'wp-util', 'wp-i18n', 'jquery-ui-sortable', 'jquery-ui-draggable', 'um_admin_own', 'um-modal', 'um_responsive' ), UM_VERSION, true );
			wp_enqueue_script( 'um_admin_builder' );

			wp_register_style( 'um_admin_builder', $this->urls['css'] . 'admin-builder' . $this->suffix . '.css', array( 'um-jquery-ui', 'um_admin_own', 'um_admin_modal', 'um_default_css' ), UM_VERSION );
			wp_enqueue_style( 'um_admin_builder' );
		}

		/**
		 * Print editor scripts if they are not printed by default
		 */
		function form_builder_wp_editor() {
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

		/**
		 * @since 3.0
		 */
		function directories_page_scripts() {
			wp_register_style( 'um_admin_directories-screen', $this->urls['css'] . 'admin-directories-screen' . $this->suffix . '.css', array(), UM_VERSION );
			wp_enqueue_style( 'um_admin_directories-screen' );
		}

		/**
		 * Load JS for Add/Edit User form
		 *
		 * @since 2.0
		 */
		function load_role_wrapper() {
			wp_register_script( 'um_admin_role-wrapper', $this->urls['js'] . 'admin/role-wrapper' . $this->suffix . '.js', array( 'jquery', 'wp-hooks' ), UM_VERSION, true );
			$localize_roles_data = get_option( 'um_roles', array() );
			wp_localize_script( 'um_admin_role-wrapper', 'um_roles', (array) $localize_roles_data );
			wp_enqueue_script( 'um_admin_role-wrapper' );
		}

		/**
		 * Add Gutenberg category for UM shortcodes
		 *
		 * @param array $categories
		 * @param \WP_Block_Editor_Context $context
		 *
		 * @return array
		 */
		function blocks_category( $categories, $context ) {
			$enable_blocks = UM()->options()->get( 'enable_blocks' );
			if ( empty( $enable_blocks ) ) {
				return $categories;
			}

			return array_merge(
				$categories,
				array(
					array(
						'slug'  => 'um-blocks',
						'title' => __( 'Ultimate Member Blocks', 'ultimate-member' ),
					),
				)
			);
		}

		function block_editor() {
			/**
			 * Disable Gutenberg scripts to avoid the conflicts via filter.
			 *
			 * @since 2.x
			 * @hook  um_disable_blocks_script
			 *
			 * @param {bool} $disable_script Do the Gutenberg block scripts are disabled? Default false.
			 *
			 * @return {bool} If true then Gutenberg blocks scripts are disabled.
			 */
			$disable_script = apply_filters( 'um_disable_blocks_script', false );
			if ( $disable_script ) {
				return;
			}

			$restricted_blocks = UM()->options()->get( 'restricted_blocks' );
			if ( ! empty( $restricted_blocks ) ) {
				wp_register_script( 'um_admin_blocks', $this->urls['js'] . 'admin/blocks' . $this->suffix . '.js', array( 'wp-i18n', 'wp-blocks', 'wp-components', 'wp-hooks' ), UM_VERSION, true );
				wp_set_script_translations( 'um_admin_blocks', 'ultimate-member' );

				$restrict_options = array();
				$roles = UM()->roles()->get_roles( false );
				if ( ! empty( $roles ) ) {
					foreach ( $roles as $role_key => $title ) {
						$restrict_options[] = array(
							'label' => $title,
							'value' => $role_key,
						);
					}
				}
				wp_localize_script( 'um_admin_blocks', 'um_restrict_roles', $restrict_options );

				wp_enqueue_script( 'um_admin_blocks' );

				do_action( 'um_load_gutenberg_js' );
			}

			$enable_blocks = UM()->options()->get( 'enable_blocks' );
			if ( ! empty( $enable_blocks ) ) {
				wp_register_script( 'um_admin_blocks-shortcode', $this->urls['js'] . 'admin/blocks-shortcode' . $this->suffix . '.js', array( 'wp-i18n', 'wp-blocks', 'wp-components' ), UM_VERSION, true );
				wp_set_script_translations( 'um_admin_blocks-shortcode', 'ultimate-member' );

				$account_settings = array(
					'password'      => array(
						'label'     => __( 'Password', 'ultimate-member' ),
						'enabled'   => UM()->options()->get( 'account_tab_password' ),
					),
					'privacy'       => array(
						'label'     => __( 'Privacy', 'ultimate-member' ),
						'enabled'   => UM()->options()->get( 'account_tab_privacy' ),
					),
					'notifications' => array(
						'label'     => __( 'Notifications', 'ultimate-member' ),
						'enabled'   => UM()->options()->get( 'account_tab_notifications' ),
					),
					'delete'        => array(
						'label'     => __( 'Delete', 'ultimate-member' ),
						'enabled'   => UM()->options()->get( 'account_tab_delete' ),
					),
				);
				wp_localize_script( 'um_admin_blocks-shortcode', 'um_account_settings', $account_settings );

				wp_enqueue_script( 'um_admin_blocks-shortcode' );

				/**
				 * Create gutenberg blocks
				 */
				register_block_type( 'um-block/um-forms', array(
					'editor_script' => 'um_admin_blocks-shortcode',
				) );

				register_block_type( 'um-block/um-password-reset', array(
					'editor_script' => 'um_admin_blocks-shortcode',
				) );

				register_block_type( 'um-block/um-account', array(
					'editor_script' => 'um_admin_blocks-shortcode',
				) );
			}
		}

		/**
		 * wp-admin assets registration + common enqueue
		 */
		function register() {
			// jquery is required for jQuery using
			// wp-util is required for wp.ajax.send function
			wp_register_script( 'um_admin_global', $this->urls['js'] . 'admin/global' . $this->suffix . '.js', array( 'jquery', 'wp-util' ), UM_VERSION, true );
			$localize_data = apply_filters(
				'um_admin_enqueue_localize_data',
				array(
					'nonce' => wp_create_nonce( 'um-admin-nonce' ),
				)
			);
			wp_localize_script( 'um_admin_global', 'um_admin_scripts', $localize_data );

			// Global CSS
			wp_register_style( 'um_admin_global', $this->urls['css'] . 'admin-global' . $this->suffix . '.css', array(), UM_VERSION );

			// enqueue directly because it's global wp-admin scripts
			wp_enqueue_script( 'um_admin_global' );
			wp_enqueue_style( 'um_admin_global' );

			if ( UM()->admin()->screen()->is_own_screen() ) {
				// jquery is required for jQuery using
				// wp-util is required for wp.ajax.send function
				// um-tipsy is required for tipsy.js
				// wp-color-picker is required for colorpickers init
				// um-helptip is required for help tooltips
				$deps = array( 'jquery', 'wp-util', 'um-tipsy', 'wp-color-picker', 'um-helptip' );
				wp_register_script( 'um_admin_own', $this->urls['js'] . 'admin/own' . $this->suffix . '.js', $deps, UM_VERSION, true );

				$deps = array( 'wp-color-picker', 'um-tipsy', 'um-helptip', 'um-fontawesome', 'um-ionicons' );
				// Only UM pages CSS but general for all UM + Extensions
				wp_register_style( 'um_admin_own', $this->urls['css'] . 'admin-own' . $this->suffix . '.css', $deps, UM_VERSION );

				// Assets for UM wp-admin forms that are used in settings pages and metaboxes
				// jquery is required for jQuery using
				// wp-util is required for wp.ajax.send function
				// um-tipsy is required for tipsy.js
				// wp-color-picker is required for colorpickers init
				// um-helptip is required for help tooltips
				$deps = array( 'jquery', 'wp-util', 'wp-i18n', 'wp-color-picker', 'jquery-ui-sortable', 'jquery-ui-slider', 'jquery-ui-draggable', 'select2', 'um-helptip' );
				wp_register_script( 'um_admin_forms', $this->urls['js'] . 'admin/forms' . $this->suffix . '.js', $deps, UM_VERSION, true );

				$deps = array( 'wp-color-picker', 'um-jquery-ui', 'select2' );
				wp_register_style( 'um_admin_forms', $this->urls['css'] . 'admin-forms' . $this->suffix . '.css', $deps, UM_VERSION );

				wp_register_script( 'um_admin_modal', $this->urls['js'] . 'admin/modal' . $this->suffix . '.js', array( 'um-modal' ), UM_VERSION, true );
				wp_register_style( 'um_admin_modal', $this->urls['css'] . 'admin-modal' . $this->suffix . '.css', array( 'um-modal' ), UM_VERSION );

				wp_enqueue_script( 'um_admin_own' );
				wp_enqueue_style( 'um_admin_own' );

				wp_enqueue_script( 'um_admin_forms' );
				wp_enqueue_style( 'um_admin_forms' );

				if ( is_rtl() ) {
					wp_register_style( 'um_admin_rtl', $this->urls['css'] . 'admin-rtl' . $this->suffix . '.css', array(), UM_VERSION );
					wp_enqueue_style( 'um_admin_rtl' );
				}
			}
		}

		function enqueue_frontend_preview_assets() {
			wp_register_script( 'um_fileupload', $this->urls['libs'] . 'jquery-upload-file/jquery.uploadfile' . $this->suffix . '.js', array( 'jquery', 'jquery-form' ), UM_VERSION, true );
//			wp_register_script( 'um_crop', $this->urls['libs'] . 'cropper/cropper' . $this->suffix . '.js', array( 'jquery' ), UM_VERSION, true );
			wp_register_script( 'um_functions', $this->urls['js'] . 'um-functions' . $this->suffix . '.js', array( 'jquery', 'um-tipsy' ), UM_VERSION, true );

			$deps = array( 'um_functions', /*'um_crop',*/ 'um-raty', 'select2', 'um_fileupload' );
			wp_register_script( 'um_scripts', $this->urls['js'] . 'um-scripts' . $this->suffix . '.js', $deps, UM_VERSION, true );
			wp_register_script( 'um_responsive', $this->urls['js'] . 'um-responsive' . $this->suffix . '.js', array( 'um_scripts' ), UM_VERSION, true );

			//wp_register_style( 'um_crop', $this->urls['libs'] . 'cropper/cropper' . $this->suffix . '.css', array(), UM_VERSION );
			wp_register_style( 'um_responsive', $this->urls['css'] . 'um-responsive.css', array(), UM_VERSION );
			//wp_register_style( 'um_styles', $this->urls['css'] . 'um-styles' . $this->suffix . '.css', array(), UM_VERSION );
			wp_register_style( 'um_profile', $this->urls['css'] . 'um-profile.css', array(), UM_VERSION );
			wp_register_style( 'um_account', $this->urls['css'] . 'um-account.css', array(), UM_VERSION );
			wp_register_style( 'um_misc', $this->urls['css'] . 'um-misc.css', array(), UM_VERSION );

			$deps = array( /*'um_crop',*/ 'um-tipsy', 'um-raty', 'um_responsive', /*'um_styles',*/ 'um_profile', 'um_account', 'um_misc', 'select2' );
			wp_register_style( 'um_default_css', $this->urls['css'] . 'um-old-default.css', $deps, UM_VERSION );
		}
	}
}
