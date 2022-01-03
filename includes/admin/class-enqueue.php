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

			add_action( 'admin_enqueue_scripts', array( &$this, 'register' ), 10 );
			add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue' ), 11 );

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
			add_action( 'load-ultimate-member_page_ultimatemember', array( &$this, 'dashboard_page' ) );
			add_action( 'load-ultimate-member_page_um_form', array( &$this, 'forms_page' ) );
			add_action( 'load-ultimate-member_page_um_directory', array( &$this, 'directories_page' ) );

			add_action( 'load-nav-menus.php', array( &$this, 'navmenu_page' ) );
			if ( $wp_version >= '5.4' ) {
				add_action( 'load-customize.php', array( &$this, 'navmenu_page' ) );
			}

			add_action( 'load-user-new.php', array( &$this, 'enqueue_role_wrapper' ) );
			add_action( 'load-user-edit.php', array( &$this, 'enqueue_role_wrapper' ) );

			add_action( 'load-ultimate-member_page_um_options', array( &$this, 'options_page' ) );
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
		function dashboard_page() {
			add_action( 'admin_enqueue_scripts', array( &$this, 'dashboard_page_scripts' ) );
		}


		/**
		 * @since 3.0
		 */
		function options_page() {
			add_action( 'admin_enqueue_scripts', array( &$this, 'options_page_scripts' ) );
		}


		/**
		 * @since 3.0
		 */
		function navmenu_page() {
			add_action( 'admin_enqueue_scripts', array( &$this, 'navmenu_page_scripts' ) );
		}


		/**
		 * @since 3.0
		 */
		function forms_page() {
			add_action( 'admin_enqueue_scripts', array( &$this, 'forms_page_scripts' ) );
		}


		/**
		 * @since 3.0
		 */
		function directories_page() {
			add_action( 'admin_enqueue_scripts', array( &$this, 'directories_page_scripts' ) );
		}


		function enqueue_role_wrapper() {
			add_action( 'admin_enqueue_scripts',  array( &$this, 'load_role_wrapper' ) );
		}


		/**
		 * @since 3.0
		 */
		function modules_page_scripts() {
			$handle_prefix = 'um_admin_';
			wp_enqueue_style( "{$handle_prefix}modules" );
		}


		/**
		 * @since 3.0
		 */
		function roles_page_scripts() {
			$handle_prefix = 'um_admin_';
			wp_enqueue_style( "{$handle_prefix}roles" );
		}


		/**
		 * @since 3.0
		 */
		function dashboard_page_scripts() {
			$handle_prefix = 'um_admin_';
			wp_enqueue_style( "{$handle_prefix}dashboard" );
		}


		/**
		 * @since 3.0
		 */
		function options_page_scripts() {
			$handle_prefix = 'um_admin_';
			wp_enqueue_script( "{$handle_prefix}settings" );
			wp_enqueue_style( "{$handle_prefix}settings" );
		}


		/**
		 * @since 3.0
		 */
		function navmenu_page_scripts() {
			$handle_prefix = 'um_admin_';
			wp_enqueue_script( "{$handle_prefix}navmenu" );
			wp_enqueue_style( "{$handle_prefix}navmenu" );
		}


		/**
		 * @since 3.0
		 */
		function forms_page_scripts() {
			$handle_prefix = 'um_admin_';
			wp_enqueue_style( "{$handle_prefix}forms-screen" );
		}


		/**
		 * @since 3.0
		 */
		function directories_page_scripts() {
			$handle_prefix = 'um_admin_';
			wp_enqueue_style( "{$handle_prefix}directories-screen" );
		}


		/**
		 * Load js for Add/Edit User form
		 */
		function load_role_wrapper() {
			$handle_prefix = 'um_admin_';
			$js_url        = trailingslashit( trailingslashit( $this->urls['js'] ) . 'admin' );

			wp_register_script( "{$handle_prefix}role-wrapper", $js_url . 'role-wrapper' . $this->scripts_prefix . '.js', array( 'jquery', 'wp-hooks' ), ultimatemember_version, true );
			$localize_roles_data = get_option( 'um_roles', array() );
			wp_localize_script( "{$handle_prefix}role-wrapper", 'um_roles', (array) $localize_roles_data );
			wp_enqueue_script( "{$handle_prefix}role-wrapper" );
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
			$handle_prefix = 'um_admin_';
			$js_url        = trailingslashit( trailingslashit( $this->urls['js'] ) . 'admin' );

			//disable Gutenberg scripts to avoid the conflicts
			$disable_script = apply_filters( 'um_disable_blocks_script', false );
			if ( $disable_script ) {
				return;
			}

			$restricted_blocks = UM()->options()->get( 'restricted_blocks' );
			if ( ! empty( $restricted_blocks ) ) {
				wp_register_script( "{$handle_prefix}blocks", $js_url . 'blocks' . $this->scripts_prefix . '.js', array( 'wp-i18n', 'wp-blocks', 'wp-components', 'wp-hooks' ), ultimatemember_version, true );
				wp_set_script_translations( "{$handle_prefix}blocks", 'ultimate-member' );

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
				wp_localize_script( "{$handle_prefix}blocks", 'um_restrict_roles', $restrict_options );

				wp_enqueue_script( "{$handle_prefix}blocks" );

				do_action( 'um_load_gutenberg_js' );
			}


			$enable_blocks = UM()->options()->get( 'enable_blocks' );
			if ( ! empty( $enable_blocks ) ) {
				wp_register_script( "{$handle_prefix}blocks-shortcode", $js_url . 'blocks-shortcode' . $this->scripts_prefix . '.js', array( 'wp-i18n', 'wp-blocks', 'wp-components' ), ultimatemember_version, true );
				wp_set_script_translations( "{$handle_prefix}blocks-shortcode", 'ultimate-member' );

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
				wp_localize_script( "{$handle_prefix}blocks-shortcode", 'um_account_settings', $account_settings );

				wp_enqueue_script( "{$handle_prefix}blocks-shortcode" );


				/**
				 * create gutenberg blocks
				 */
				register_block_type( 'um-block/um-forms', array(
					'editor_script' => "{$handle_prefix}blocks-shortcode",
				) );

				register_block_type( 'um-block/um-member-directories', array(
					'editor_script' => "{$handle_prefix}blocks-shortcode",
				) );

				register_block_type( 'um-block/um-password-reset', array(
					'editor_script' => "{$handle_prefix}blocks-shortcode",
				) );

				register_block_type( 'um-block/um-account', array(
					'editor_script' => "{$handle_prefix}blocks-shortcode",
				) );
			}
		}


		/**
		 * wp-admin assets registration
		 */
		function register() {
			$handle_prefix = 'um_admin_';
			$js_url        = trailingslashit( trailingslashit( $this->urls['js'] ) . 'admin' );
			$css_url       = trailingslashit( $this->urls['css'] );


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

			wp_register_script( 'select2', $this->front_js_baseurl . 'select2/select2.full' . $this->suffix . '.js', array( 'jquery', 'jquery-masonry' ), '4.0.13', true );
			wp_register_style( 'select2', $this->front_css_baseurl . 'select2/select2' . $this->suffix . '.css', array(), '4.0.13' );

			wp_register_style( 'um_ui', $this->front_css_baseurl . 'jquery-ui.css', array(), ultimatemember_version );

			wp_register_script( 'um_tipsy', $this->front_js_baseurl . 'um-tipsy' . $this->scripts_prefix . '.js', array( 'jquery' ), ultimatemember_version, true );
			wp_register_style( 'um_tipsy', $this->front_css_baseurl . 'um-tipsy' . $this->scripts_prefix . '.css', array(), ultimatemember_version );

			wp_register_style( 'um_datetime', $this->front_css_baseurl . 'pickadate/default.css', array(), ultimatemember_version );
			wp_register_style( 'um_datetime_date', $this->front_css_baseurl . 'pickadate/default.date.css', array( 'um_datetime' ), ultimatemember_version );
			wp_register_style( 'um_datetime_time', $this->front_css_baseurl . 'pickadate/default.time.css', array( 'um_datetime' ), ultimatemember_version );





			wp_register_script( 'um_admin_builder', $this->js_url . 'um-admin-builder.js', array('jquery', 'wp-util', 'jquery-ui-sortable', 'jquery-ui-draggable'), ultimatemember_version, true );
			wp_enqueue_script( 'um_admin_builder' );

			//hide footer text on add/edit UM Forms
			//layouts crashed because we load and hide metaboxes
			//and WP calculate page height
			$hide_footer = false;
			global $pagenow, $post;
			if ( ( 'post.php' === $pagenow || 'post-new.php' === $pagenow ) &&
			     ( ( isset( $_GET['post_type'] ) && 'um_form' === sanitize_key( $_GET['post_type'] ) ) ||
			       ( isset( $post->post_type ) && 'um_form' === $post->post_type ) ) ) {
				$hide_footer = true;
			}

			$localize_data = array(
				'hide_footer' => $hide_footer,
			);
			wp_localize_script( 'um_admin_builder', 'um_admin_builder_data', $localize_data );

			wp_register_style( 'um_admin_builder', $this->css_url . 'um-admin-builder.css', array(), ultimatemember_version );
			wp_enqueue_style( 'um_admin_builder' );

//			wp_register_style( 'um_admin_builder_v3', $this->css_url_v3 . 'admin-builder.css', [], ultimatemember_version );
//			wp_enqueue_style( 'um_admin_builder_v3' );

//			wp_register_script( 'um_admin_builder_v3', $this->js_url_v3 . 'admin-builder.js', ['jquery', 'wp-util'], ultimatemember_version, true );
//			wp_enqueue_script( 'um_admin_builder_v3' );

			wp_register_script( 'um_common_v3', $this->js_url_v3 . 'common.js', array( 'jquery' ), ultimatemember_version, true );

			$um_common_variables = apply_filters( 'um_common_js_variables', array(
				'locale' => get_locale(),
			) );
			wp_localize_script( 'um_common_v3', 'um_common_variables', $um_common_variables );

			wp_enqueue_script( 'um_common_v3' );


//			um_path . 'assets/v3/fonts/fontawesome/css/regular'
//			um_path . 'assets/v3/fonts/fontawesome/css/solid'
//			um_path . 'assets/v3/fonts/fontawesome/css/brands'
//			um_path . 'assets/v3/fonts/fontawesome/css/v4-shims'
//			um_path . 'assets/v3/fonts/fontawesome/css/fontawesome'
//
//			wp_register_style( 'um-far', $this->url['common'] . 'libs/fontawesome/css/regular' . $this->scripts_prefix . '.css', [], $this->fa_version );
//			wp_register_style( 'um-fas', $this->url['common'] . 'libs/fontawesome/css/solid' . $this->scripts_prefix . '.css', [], $this->fa_version );
//			wp_register_style( 'um-fab', $this->url['common'] . 'libs/fontawesome/css/brands' . $this->scripts_prefix . '.css', [], $this->fa_version );
//			wp_register_style( 'um-fa', $this->url['common'] . 'libs/fontawesome/css/v4-shims' . $this->scripts_prefix . '.css', [], $this->fa_version );
//			wp_register_style( 'um-font-awesome', $this->url['common'] . 'libs/fontawesome/css/fontawesome' . $this->scripts_prefix . '.css', [ 'fmwp-fa', 'fmwp-far', 'fmwp-fas', 'fmwp-fab' ], $this->fa_version );


			// jquery is required for jQuery using
			// wp-util is required for wp.ajax.send function
			wp_register_script( "{$handle_prefix}global", $js_url . 'global' . $this->scripts_prefix . '.js', array( 'jquery', 'wp-util' ), ultimatemember_version, true );

			$localize_data = apply_filters(
				'um_admin_enqueue_localize_data',
				array(
					'nonce' => wp_create_nonce( 'um-admin-nonce' ),
				)
			);

			wp_localize_script( "{$handle_prefix}global", 'um_admin_scripts', $localize_data );


			wp_register_script( "{$handle_prefix}own", $js_url . 'own' . $this->scripts_prefix . '.js', array( 'jquery', 'wp-util', 'um_tipsy', 'um_datetime_date', 'um_datetime_time', 'um_datetime_locale', 'wp-color-picker', 'jquery-ui-tooltip' ), ultimatemember_version, true );

			wp_register_script( "{$handle_prefix}navmenu", $js_url . 'navmenu' . $this->scripts_prefix . '.js', array( 'jquery', 'wp-util' ), ultimatemember_version, true );
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
			wp_localize_script( "{$handle_prefix}navmenu", 'um_menu_restriction_data', $menu_restriction_data );


			wp_register_script( "{$handle_prefix}settings", $js_url . 'settings' . $this->scripts_prefix . '.js', array( 'jquery', 'wp-i18n' ), ultimatemember_version, true );


			wp_register_script( "{$handle_prefix}forms", $js_url . 'forms' . $this->scripts_prefix . '.js', array( 'jquery', 'wp-util', 'wp-i18n', 'select2', 'jquery-ui-sortable', 'jquery-ui-slider', 'um_datetime_date', 'um_datetime_time', 'um_datetime_locale', 'wp-color-picker', 'jquery-ui-draggable' ), ultimatemember_version, true );
			wp_localize_script( "{$handle_prefix}forms", 'um_forms_data', array(
				'successfully_redirect' => add_query_arg( array( 'page' => 'um_options', 'tab' => 'misc', 'msg' => 'updated' ), admin_url( 'admin.php' ) ),
			) );


			wp_register_script( "{$handle_prefix}builder", $js_url . 'builder' . $this->scripts_prefix . '.js', array( 'jquery', 'wp-util', 'wp-i18n' ), ultimatemember_version, true );

//			wp_register_script( 'select2', $this->url['common'] . 'libs/select2/js/select2.full' . $this->scripts_prefix . '.js', [], fmwp_version, true );
//			wp_register_script( 'fmwp-global', $this->js_url['admin'] . 'global' . $this->scripts_prefix . '.js', [ 'jquery', 'wp-util' ], fmwp_version, true );
//
//			wp_register_script( 'fmwp-common', $this->js_url['admin'] . 'common' . $this->scripts_prefix . '.js', [ 'jquery', 'wp-color-picker' ], fmwp_version, true );
//			wp_register_script( 'fmwp-forms', $this->js_url['admin'] . 'forms' . $this->scripts_prefix . '.js', [ 'jquery', 'wp-util', 'fmwp-helptip', 'select2' ], fmwp_version, true );
//
//			$localize_data = apply_filters( 'fmwp_admin_enqueue_localize', [
//				'nonce' => wp_create_nonce( 'fmwp-backend-nonce' ),
//			] );
//
//			wp_localize_script( 'fmwp-global', 'fmwp_admin_data', $localize_data );
//			wp_enqueue_script( 'fmwp-global' );
//
//
//			wp_register_style( 'select2', $this->url['common'] . 'libs/select2/css/select2' . $this->scripts_prefix . '.css', [], fmwp_version );
//
//			$common_admin_deps = apply_filters( 'fmwp_admin_common_styles_deps', [ 'wp-color-picker', 'fmwp-helptip', 'select2' ] );
//			wp_register_style( 'fmwp-common', $this->css_url['admin'] .'common' . $this->scripts_prefix . '.css', $common_admin_deps, fmwp_version );
//			wp_register_style( 'fmwp-forms', $this->css_url['admin'] . 'forms' . $this->scripts_prefix . '.css', [ 'fmwp-common' ], fmwp_version );
//
//			if ( FMWP()->admin()->is_own_screen() ) {
//				//wp_enqueue_media();
//				wp_enqueue_script( 'fmwp-common' );
//				wp_enqueue_script( 'fmwp-forms' );
//
//				wp_enqueue_style( 'fmwp-common' );
//				wp_enqueue_style( 'fmwp-forms' );
//			}

			// Global CSS
			wp_register_style( "{$handle_prefix}global", $css_url . 'admin-global' . $this->scripts_prefix . '.css', array(), ultimatemember_version );

			// Only UM pages CSS but general for all UM + Extensions
			wp_register_style( "{$handle_prefix}own", $css_url . 'admin-own' . $this->scripts_prefix . '.css', array( 'um_tipsy', 'wp-color-picker' ), ultimatemember_version );

			wp_register_style( "{$handle_prefix}modules", $css_url . 'admin-modules' . $this->scripts_prefix . '.css', array(), ultimatemember_version );
			wp_register_style( "{$handle_prefix}roles", $css_url . 'admin-roles' . $this->scripts_prefix . '.css', array(), ultimatemember_version );
			wp_register_style( "{$handle_prefix}dashboard", $css_url . 'admin-dashboard' . $this->scripts_prefix . '.css', array(), ultimatemember_version );
			wp_register_style( "{$handle_prefix}navmenu", $css_url . 'admin-navmenu' . $this->scripts_prefix . '.css', array(), ultimatemember_version );
			wp_register_style( "{$handle_prefix}forms-screen", $css_url . 'admin-forms-screen' . $this->scripts_prefix . '.css', array(), ultimatemember_version );
			wp_register_style( "{$handle_prefix}directories-screen", $css_url . 'admin-directories-screen' . $this->scripts_prefix . '.css', array(), ultimatemember_version );
			wp_register_style( "{$handle_prefix}settings", $css_url . 'admin-settings' . $this->scripts_prefix . '.css', array(), ultimatemember_version );
			wp_register_style( "{$handle_prefix}forms", $css_url . 'admin-forms' . $this->scripts_prefix . '.css', array( 'wp-color-picker', 'um_ui', 'select2', 'um_datetime_date', 'um_datetime_time' ), ultimatemember_version );
			if ( is_rtl() ) {
				wp_register_style( "{$handle_prefix}rtl", $css_url . 'admin-rtl' . $this->scripts_prefix . '.css', array(), ultimatemember_version );
				wp_enqueue_style( "{$handle_prefix}rtl" );
			}
		}


		function enqueue() {
			$handle_prefix = 'um_admin_';

			// enqueue directly because it's global wp-admin scripts
			wp_enqueue_script( "{$handle_prefix}global" );
			wp_enqueue_style( "{$handle_prefix}global" );

			if ( UM()->admin()->is_own_screen() ) {
				wp_enqueue_script( "{$handle_prefix}forms" );
				wp_enqueue_style( "{$handle_prefix}forms" );

				wp_enqueue_style( "{$handle_prefix}own" );
			}
		}
	}
}
