<?php
namespace um\admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Enqueue
 *
 * @package um\admin
 */
final class Enqueue extends \um\common\Enqueue {


	/**
	 * @var string
	 * @deprecated 2.7.1
	 */
	public $js_url;


	/**
	 * @var string
	 */
	public $css_url;


	/**
	 * @var string
	 */
	public $front_js_baseurl;


	/**
	 * @var string
	 */
	public $front_css_baseurl;


	/**
	 * @var bool
	 */
	private static $um_cpt_form_screen = false;


	/**
	 * @var bool
	 * @deprecated 2.7.1
	 */
	public $post_page;


	/**
	 * Enqueue constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->front_js_baseurl  = UM_URL . 'assets/js/';
		$this->front_css_baseurl = UM_URL . 'assets/css/';

		add_filter( 'admin_body_class', array( &$this, 'admin_body_class' ), 999 );

		add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_scripts' ) );

		add_action( 'load-customize.php', array( &$this, 'navmenu_scripts' ) );
		add_action( 'load-edit.php', array( &$this, 'posts_page' ) );

		add_action( 'load-post-new.php', array( &$this, 'enqueue_cpt_scripts' ) );
		add_action( 'load-post.php', array( &$this, 'enqueue_cpt_scripts' ) );

		global $wp_version;
		if ( version_compare( $wp_version, '5.8', '>=' ) ) {
			add_filter( 'block_categories_all', array( &$this, 'blocks_category' ) );
		} else {
			add_filter( 'block_categories', array( &$this, 'blocks_category' ) );
		}
		add_action( 'enqueue_block_assets', array( &$this, 'block_editor' ), 11 );
	}

	/**
	 * Adds class to our admin pages
	 *
	 * @since 2.7.1
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
	 * Enqueue Gutenberg Block Editor assets
	 */
	public function block_editor() {
		$suffix  = self::get_suffix();
		$js_url  = self::get_url( 'js' );
		$css_url = self::get_url( 'css' );

		$this->load_gutenberg_js();

		wp_register_script( 'um_admin_blocks_shortcodes', $js_url . 'admin/block-renderer' . $suffix . '.js', array( 'wp-i18n', 'wp-blocks', 'wp-components' ), UM_VERSION, true );
		wp_set_script_translations( 'um_admin_blocks_shortcodes', 'ultimate-member' );

		if ( ! empty( UM()->account()->get_tab_fields( 'notifications', array() ) ) ) {
			$notifications_enabled = 1;
		} else {
			$notifications_enabled = 0;
		}

		$um_account_settings = array(
			'general'       => array(
				'label'   => __( 'General', 'ultimate-member' ),
				'enabled' => 1,
			),
			'password'      => array(
				'label'   => __( 'Password', 'ultimate-member' ),
				'enabled' => UM()->options()->get( 'account_tab_password' ),
			),
			'privacy'       => array(
				'label'   => __( 'Privacy', 'ultimate-member' ),
				'enabled' => UM()->options()->get( 'account_tab_privacy' ),
			),
			'notifications' => array(
				'label'   => __( 'Notifications', 'ultimate-member' ),
				'enabled' => $notifications_enabled,
			),
			'delete'        => array(
				'label'   => __( 'Delete', 'ultimate-member' ),
				'enabled' => UM()->options()->get( 'account_tab_delete' ),
			),
		);
		$um_account_settings = apply_filters( 'um_extend_account_settings', $um_account_settings );
		wp_localize_script( 'um_admin_blocks_shortcodes', 'um_account_settings', $um_account_settings );
		wp_enqueue_script( 'um_admin_blocks_shortcodes' );

		wp_register_script( 'um_conditional', UM_URL . 'assets/js/um-conditional' . $suffix . '.js', array( 'jquery', 'wp-hooks' ), UM_VERSION, true );
		wp_register_script( 'um_scripts', UM_URL . 'assets/js/um-scripts' . $suffix . '.js', array( 'jquery', 'wp-util', 'um_conditional', 'um_common', self::$select2_handle ), UM_VERSION, true );
		$max_upload_size = wp_max_upload_size();
		if ( ! $max_upload_size ) {
			$max_upload_size = 0;
		}

		$localize_data = apply_filters(
			'um_enqueue_localize_data',
			array(
				'max_upload_size' => $max_upload_size,
				'nonce'           => wp_create_nonce( 'um-frontend-nonce' ),
			)
		);
		wp_localize_script( 'um_scripts', 'um_scripts', $localize_data );

		wp_register_script( 'um_dropdown', UM_URL . 'assets/js/dropdown' . $suffix . '.js', array( 'jquery' ), UM_VERSION, true );
		wp_register_script( 'um_members', UM_URL . 'assets/js/um-members' . $suffix . '.js', array( 'jquery', 'wp-util', 'jquery-ui-slider', 'um_dropdown', 'wp-hooks', 'jquery-masonry', 'um_scripts' ), UM_VERSION, true );

		wp_register_script( 'um_account', UM_URL . 'assets/js/um-account' . $suffix . '.js', array( 'jquery', 'wp-hooks' ), UM_VERSION, true );

		wp_register_script( 'um_functions', UM_URL . 'assets/js/um-functions' . $suffix . '.js', array( 'jquery', 'jquery-masonry', 'wp-util' ), UM_VERSION, true );
		wp_register_script( 'um_responsive', UM_URL . 'assets/js/um-responsive' . $suffix . '.js', array( 'jquery', 'um_functions', 'um_crop' ), UM_VERSION, true );

		// render blocks
		wp_enqueue_script( 'um_conditional' );
		wp_enqueue_script( 'um_dropdown' );
		wp_enqueue_script( 'um_members' );
		wp_enqueue_script( 'um_account' );
		wp_enqueue_script( 'um_functions' );
		wp_enqueue_script( 'um_responsive' );

		wp_register_style( 'um_members', UM_URL . 'assets/css/um-members.css', array( 'um_ui' ), UM_VERSION );
		if ( is_rtl() ) {
			wp_register_style( 'um_members_rtl', UM_URL . 'assets/css/um-members-rtl.css', array( 'um_members' ), UM_VERSION );
		}
		wp_register_style( 'um_styles', UM_URL . 'assets/css/um-styles.css', array( 'um_ui', 'um_tipsy', 'um_raty', 'um_fonticons_ii', 'um_fonticons_fa', 'select2' ), UM_VERSION );
		wp_register_style( 'um_profile', UM_URL . 'assets/css/um-profile.css', array(), UM_VERSION );
		wp_register_style( 'um_responsive', UM_URL . 'assets/css/um-responsive.css', array( 'um_profile', 'um_crop' ), UM_VERSION );
		wp_register_style( 'um_account', UM_URL . 'assets/css/um-account.css', array(), UM_VERSION );
		wp_register_style( 'um_default_css', UM_URL . 'assets/css/um-old-default.css', array(), UM_VERSION );

		wp_enqueue_style( 'um_default_css' );
		wp_enqueue_style( 'um_members' );
		wp_enqueue_style( 'um_styles' );
		wp_enqueue_style( 'um_profile' );
		wp_enqueue_style( 'um_responsive' );
		wp_enqueue_style( 'um_account' );

		$custom_css = '.wp-block .um{opacity: 1;}.um_request_name {display: none !important;}';

		wp_add_inline_style( 'um_styles', $custom_css );
	}

	/**
	 * Load Gutenberg scripts.
	 */
	private function load_gutenberg_js() {
		/** This filter is documented in includes/core/class-blocks.php */
		$disable_script = apply_filters( 'um_disable_blocks_script', false );
		if ( $disable_script ) {
			return;
		}

		$restricted_blocks = UM()->options()->get( 'restricted_blocks' );
		if ( empty( $restricted_blocks ) ) {
			return;
		}

		$suffix  = self::get_suffix();
		$js_url  = self::get_url( 'js' );
		$css_url = self::get_url( 'css' );

		wp_register_script( 'um_block_restrictions', $js_url . 'admin/block-restrictions' . $suffix . '.js', array( 'wp-i18n', 'wp-blocks', 'wp-components', 'wp-hooks' ), UM_VERSION, true );
		wp_set_script_translations( 'um_block_restrictions', 'ultimate-member' );

		$restrict_options = array();
		$roles            = UM()->roles()->get_roles();
		if ( ! empty( $roles ) ) {
			foreach ( $roles as $role_key => $title ) {
				$restrict_options[] = array(
					'label' => $title,
					'value' => $role_key,
				);
			}
		}
		wp_localize_script( 'um_block_restrictions', 'um_restrict_roles', $restrict_options );
		wp_enqueue_script( 'um_block_restrictions' );

		wp_register_style( 'um_block_css', $css_url . 'admin/block' . $suffix . '.css', array(), UM_VERSION );
		wp_enqueue_style( 'um_block_css' );

		do_action( 'um_load_gutenberg_js' );
	}

	/**
	 * @since 2.7.1
	 */
	public function navmenu_scripts() {
		add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_navmenu_scripts' ) );
	}

	/**
	 * @since 2.7.1
	 */
	public function enqueue_navmenu_scripts() {
		$suffix  = self::get_suffix();
		$js_url  = self::get_url( 'js' );
		$css_url = self::get_url( 'css' );

		wp_register_script( 'um_admin_nav_menu', $js_url . 'admin/nav-menu' . $suffix . '.js', array( 'jquery', 'wp-util' ), UM_VERSION, true );
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
		wp_localize_script( 'um_admin_nav_menu', 'um_menu_restriction_data', $menu_restriction_data );
		wp_enqueue_script( 'um_admin_nav_menu' );

		wp_register_style( 'um_admin_nav_menu', $css_url . 'admin/nav-menu' . $suffix . '.css', array(), UM_VERSION );
		wp_enqueue_style( 'um_admin_nav_menu' );
	}

	/**
	 * @since 2.7.1
	 */
	public function posts_page() {
		if ( isset( $_GET['post_type'] ) && 'um_form' === sanitize_key( $_GET['post_type'] ) ) {
			add_action( 'admin_enqueue_scripts', array( &$this, 'forms_page_scripts' ) );
		} elseif ( isset( $_GET['post_type'] ) && 'um_directory' === sanitize_key( $_GET['post_type'] ) ) {
			add_action( 'admin_enqueue_scripts', array( &$this, 'directories_page_scripts' ) );
		}
	}

	/**
	 * @since 2.7.1
	 */
	public function forms_page_scripts() {
		$suffix  = self::get_suffix();
		$css_url = self::get_url( 'css' );
		wp_register_style( 'um_admin_forms-screen', $css_url . 'admin/forms-screen' . $suffix . '.css', array(), UM_VERSION );
		wp_enqueue_style( 'um_admin_forms-screen' );
	}

	/**
	 * @since 2.7.1
	 */
	public function directories_page_scripts() {
		$suffix  = self::get_suffix();
		$css_url = self::get_url( 'css' );
		wp_register_style( 'um_admin_directories-screen', $css_url . 'admin/directories-screen' . $suffix . '.css', array(), UM_VERSION );
		wp_enqueue_style( 'um_admin_directories-screen' );
	}

	/**
	 * Load Forms
	 */
	public function load_forms() {
		$suffix  = self::get_suffix();
		$js_url  = self::get_url( 'js' );
		$css_url = self::get_url( 'css' );

		wp_register_script( 'um_admin_forms', $js_url . 'admin/forms' . $suffix . '.js', array( 'um_admin_common', self::$select2_handle, 'jquery-ui-draggable', 'jquery-ui-sortable', 'jquery-ui-slider' ), UM_VERSION, true );
		wp_set_script_translations( 'um_admin_forms', 'ultimate-member' );
		$forms_data = array(
			'successfully_redirect' => add_query_arg(
				array(
					'page' => 'um_options',
					'tab'  => 'misc',
					'msg'  => 'updated',
				),
				admin_url( 'admin.php' )
			),
		);
		$forms_data = apply_filters( 'um_admin_forms_data_localize', $forms_data );

		wp_localize_script( 'um_admin_forms', 'um_forms_data', $forms_data );
		wp_enqueue_script( 'um_admin_forms' );

		wp_register_style( 'um_admin_forms', $css_url . 'admin/forms' . $suffix . '.css', array( 'wp-color-picker', 'um_ui', 'select2' ), UM_VERSION );
		// RTL styles.
		if ( is_rtl() ) {
			wp_style_add_data( 'um_admin_forms', 'rtl', true );
			wp_style_add_data( 'um_admin_forms', 'suffix', $suffix );
		}
		wp_enqueue_style( 'um_admin_forms' );
	}

	/**
	 * Load modal
	 */
	public function load_modal() {
		$suffix  = self::get_suffix();
		$js_url  = self::get_url( 'js' );
		$css_url = self::get_url( 'css' );

		wp_register_script( 'um_admin_modal', $js_url . 'admin/modal' . $suffix . '.js', array( 'um_admin_common' ), UM_VERSION, true );
		wp_set_script_translations( 'um_admin_modal', 'ultimate-member' );
		wp_enqueue_script( 'um_admin_modal' );

		wp_register_style( 'um_admin_modal', $css_url . 'admin/modal' . $suffix . '.css', array( 'um_admin_common' ), UM_VERSION );
		wp_enqueue_style( 'um_admin_modal' );
	}

	/**
	 * Load Builder
	 */
	public function load_builder() {
		$this->enqueue_frontend_preview_assets();

		$suffix  = self::get_suffix();
		$js_url  = self::get_url( 'js' );
		$css_url = self::get_url( 'css' );

		wp_register_script( 'um_admin_builder', $js_url . 'admin/builder' . $suffix . '.js', array( 'um_admin_modal', 'jquery-ui-draggable', 'jquery-ui-sortable', 'editor', 'wp-tinymce', self::$select2_handle, 'um_raty' ), UM_VERSION, true );
		wp_set_script_translations( 'um_admin_builder', 'ultimate-member' );
		wp_enqueue_script( 'um_admin_builder' );

		wp_register_style( 'um_admin_builder', $css_url . 'admin/builder' . $suffix . '.css', array( 'um_admin_modal', 'select2', 'um_raty' ), UM_VERSION );
		// RTL styles.
		if ( is_rtl() ) {
			wp_style_add_data( 'um_admin_builder', 'rtl', true );
			wp_style_add_data( 'um_admin_builder', 'suffix', $suffix );
		}
		wp_enqueue_style( 'um_admin_builder' );
	}

	/**
	 * Assets for FRONTEND PREVIEW.
	 */
	private function enqueue_frontend_preview_assets() {
		$suffix  = self::get_suffix();
		$css_url = self::get_url( 'css' );

		wp_register_style( 'um_fileupload', $css_url . 'um-fileupload' . $suffix . '.css', array(), UM_VERSION );
		wp_register_style( 'um_responsive', $css_url . 'um-responsive' . $suffix . '.css', array(), UM_VERSION );
		wp_register_style( 'um_styles', $css_url . 'um-styles' . $suffix . '.css', array(), UM_VERSION );
		wp_register_style( 'um_profile', $css_url . 'um-profile' . $suffix . '.css', array(), UM_VERSION );
		wp_register_style( 'um_misc', $css_url . 'um-misc' . $suffix . '.css', array(), UM_VERSION );
		wp_register_style( 'um_modal', $css_url . 'um-modal' . $suffix . '.css', array(), UM_VERSION );
		wp_register_style( 'um_default_css', $css_url . 'um-old-default' . $suffix . '.css', array( 'um_fileupload', 'um_responsive', 'um_modal', 'um_styles', 'um_profile', 'um_misc' ), UM_VERSION );

		wp_enqueue_style( 'um_default_css' );
	}

	/**
	 * Load global css
	 */
	public function load_global_scripts() {
		$suffix  = self::get_suffix();
		$js_url  = self::get_url( 'js' );
		$css_url = self::get_url( 'css' );

		wp_register_script( 'um_admin_global', $js_url . 'admin/global' . $suffix . '.js', array( 'jquery' ), UM_VERSION, true );
		$localize_data = array(
			'nonce' => wp_create_nonce( 'um-admin-nonce' ),
		);
		/**
		 * Filters data array for localize wp-admin global scripts.
		 *
		 * @since 2.0
		 * @hook um_admin_enqueue_localize_data
		 *
		 * @param {array} $variables Data to localize.
		 *
		 * @return {array} Data to localize.
		 *
		 * @example <caption>Add `my_custom_variable` to wp-admin global scripts to be callable via `um_admin_scripts.my_custom_variable` in JS.</caption>
		 * function um_custom_admin_enqueue_localize_data( $variables ) {
		 *     $variables['{my_custom_variable}'] = '{my_custom_variable_value}';
		 *     return $variables;
		 * }
		 * add_filter( 'um_admin_enqueue_localize_data', 'um_custom_admin_enqueue_localize_data' );
		 */
		$localize_data = apply_filters( 'um_admin_enqueue_localize_data', $localize_data );
		wp_localize_script( 'um_admin_global', 'um_admin_scripts', $localize_data );
		wp_enqueue_script( 'um_admin_global' );

		wp_register_style( 'um_admin_global', $css_url . 'admin/global' . $suffix . '.css', array(), UM_VERSION );
		wp_enqueue_style( 'um_admin_global' );
	}

	/**
	 * Add Gutenberg category for UM shortcodes.
	 *
	 * @param array $categories
	 *
	 * @return array
	 */
	public function blocks_category( $categories ) {
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

	/**
	 * Enqueue scripts and styles.
	 *
	 * @param string $hook wp-admin screen.
	 */
	public function admin_enqueue_scripts( $hook ) {
		$suffix  = self::get_suffix();
		$js_url  = self::get_url( 'js' );
		$css_url = self::get_url( 'css' );

		$this->load_global_scripts();

		if ( UM()->admin()->screen()->is_own_screen() ) {
			wp_register_script( 'um_admin_common', $js_url . 'admin/common' . $suffix . '.js', array( 'wp-color-picker', 'jquery-ui-tooltip', 'um_common' ), UM_VERSION, true );
			wp_enqueue_script( 'um_admin_common' );

			wp_register_style( 'um_admin_common', $css_url . 'admin/common' . $suffix . '.css', array( 'um_common', 'um_ui', 'dashicons' ), UM_VERSION );
			wp_enqueue_style( 'um_admin_common' );

			if ( self::$um_cpt_form_screen ) {
				$this->load_builder();
				$this->load_modal();
			}

			$this->load_forms();
		}

		if ( 'users.php' === $hook ) {
			wp_register_style( 'um_admin_users', $css_url . 'admin/users' . $suffix . '.css', array(), UM_VERSION );
			wp_enqueue_style( 'um_admin_users' );

			$this->load_modal();
		} elseif ( 'user-new.php' === $hook || 'user-edit.php' === $hook ) {
			wp_register_script( 'um_admin_role_wrapper', $js_url . 'admin/user' . $suffix . '.js', array( 'jquery', 'wp-hooks' ), UM_VERSION, true );
			$localize_roles_data = get_option( 'um_roles', array() );
			wp_localize_script( 'um_admin_role_wrapper', 'um_roles', (array) $localize_roles_data );
			wp_enqueue_script( 'um_admin_role_wrapper' );
		} elseif ( 'toplevel_page_ultimatemember' === $hook ) {
			wp_register_style( 'um_admin_dashboard', $css_url . 'admin/dashboard' . $suffix . '.css', array(), UM_VERSION );
			wp_enqueue_style( 'um_admin_dashboard' );
		} elseif ( 'ultimate-member_page_um_roles' === $hook ) {
			wp_register_style( 'um_admin_roles', $css_url . 'admin/roles' . $suffix . '.css', array(), UM_VERSION );
			wp_enqueue_style( 'um_admin_roles' );
		} elseif ( 'ultimate-member_page_um_options' === $hook ) {
			// phpcs:ignore WordPress.Security.NonceVerification
			if ( isset( $_GET['tab'] ) && 'secure' === $_GET['tab'] ) {
				wp_register_script( 'um_admin_secure', $js_url . 'admin/secure' . $suffix . '.js', array( 'jquery', 'wp-i18n' ), UM_VERSION, true );
				wp_set_script_translations( 'um_admin_secure', 'ultimate-member' );
				wp_enqueue_script( 'um_admin_secure' );
			}

			wp_register_style( 'um_admin_settings', $css_url . 'admin/settings' . $suffix . '.css', array(), UM_VERSION );
			wp_enqueue_style( 'um_admin_settings' );

			wp_register_script( 'um_admin_settings', $js_url . 'admin/settings' . $suffix . '.js', array( 'jquery', 'wp-i18n' ), UM_VERSION, true );
			wp_set_script_translations( 'um_admin_settings', 'ultimate-member' );
			wp_enqueue_script( 'um_admin_settings' );
		} elseif ( 'ultimate-member_page_ultimatemember-extensions' === $hook ) {
			wp_register_style( 'um_admin_extensions', $css_url . 'admin/extensions' . $suffix . '.css', array(), UM_VERSION );
			wp_enqueue_style( 'um_admin_extensions' );
		}
	}

	/**
	 *
	 */
	public function enqueue_cpt_scripts() {
		if ( ( isset( $_GET['post_type'] ) && 'um_form' === sanitize_key( $_GET['post_type'] ) ) || ( isset( $_GET['post'] ) && 'um_form' === get_post_type( absint( $_GET['post'] ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			self::$um_cpt_form_screen = true;
			add_action( 'admin_footer', array( $this, 'admin_footer_scripts' ), 20 );
		} elseif ( ( isset( $_GET['post_type'] ) && 'um_directory' === sanitize_key( $_GET['post_type'] ) ) || ( isset( $_GET['post'] ) && 'um_directory' === get_post_type( absint( $_GET['post'] ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			add_filter( 'um_admin_forms_data_localize', array( &$this, 'directory_forms_data_localize' ) );
		}
	}

	/**
	 * Print editor scripts if they are not printed by default
	 */
	public function admin_footer_scripts() {
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
			require_once ABSPATH . WPINC . '/class-wp-editor.php';
		}

		$class::force_uncompressed_tinymce();
		$class::enqueue_scripts();
		$class::editor_js();
	}

	/**
	 * @since 2.7.1
	 * @param array $form_data
	 *
	 * @return array
	 */
	public function directory_forms_data_localize( $form_data ) {
		$form_data['md_sorting_data_types'] = UM()->member_directory()->sort_data_types;
		return $form_data;
	}
}
