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
	public $um_cpt_form_screen;


	/**
	 * @var bool
	 */
	public $post_page;


	/**
	 * Admin_Enqueue constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->js_url  = UM_URL . 'includes/admin/assets/js/';
		$this->css_url = UM_URL . 'includes/admin/assets/css/';

		$this->front_js_baseurl  = UM_URL . 'assets/js/';
		$this->front_css_baseurl = UM_URL . 'assets/css/';

		$this->um_cpt_form_screen = false;

		add_action( 'admin_head', array( &$this, 'admin_head' ), 9 );

		add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_scripts' ) );

		add_filter( 'enter_title_here', array( &$this, 'enter_title_here' ) );

		add_action( 'load-user-new.php', array( &$this, 'enqueue_role_wrapper' ) );
		add_action( 'load-user-edit.php', array( &$this, 'enqueue_role_wrapper' ) );

		add_action( 'load-post-new.php', array( &$this, 'enqueue_cpt_scripts' ) );
		add_action( 'load-post.php', array( &$this, 'enqueue_cpt_scripts' ) );

		global $wp_version;
		if ( version_compare( $wp_version, '5.8', '>=' ) ) {
			add_filter( 'block_categories_all', array( &$this, 'blocks_category' ), 10, 2 );
		} else {
			add_filter( 'block_categories', array( &$this, 'blocks_category' ), 10, 2 );
		}
		add_action( 'enqueue_block_assets', array( &$this, 'block_editor' ), 11 );
	}


	/**
	 * Enqueue Gutenberg Block Editor assets
	 */
	public function block_editor() {
		$suffix = self::get_suffix();
		$this->register_jquery_ui();
		wp_register_style( 'um_members', UM_URL . 'assets/css/um-members.css', array( 'um_ui' ), UM_VERSION );
		if ( is_rtl() ) {
			wp_register_style( 'um_members_rtl', UM_URL . 'assets/css/um-members-rtl.css', array( 'um_members' ), UM_VERSION );
		}
		wp_register_style( 'um_styles', UM_URL . 'assets/css/um-styles.css', array( 'um_ui', 'um_tipsy', 'um_raty', 'um_fonticons_ii', 'um_fonticons_fa', 'select2' ), UM_VERSION );
		wp_register_style( 'um_profile', UM_URL . 'assets/css/um-profile.css', array(), UM_VERSION );
		wp_register_style( 'um_crop', UM_URL . 'assets/css/um-crop.css', array(), UM_VERSION );
		wp_register_style( 'um_responsive', UM_URL . 'assets/css/um-responsive.css', array( 'um_profile', 'um_crop' ), UM_VERSION );
		wp_register_style( 'um_account', UM_URL . 'assets/css/um-account.css', array(), UM_VERSION );
		wp_register_style( 'um_default_css', UM_URL . 'assets/css/um-old-default.css', array(), UM_VERSION );

		wp_register_script( 'um_admin_blocks_shortcodes', UM_URL . 'assets/js/um-blocks' . $suffix . '.js', array( 'wp-i18n', 'wp-blocks', 'wp-components' ), UM_VERSION, true );
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

		wp_register_script( 'um_datetime', UM_URL . 'assets/js/pickadate/picker.js', array( 'jquery' ), UM_VERSION, true );
		wp_register_script( 'um_datetime_date', UM_URL . 'assets/js/pickadate/picker.date.js', array( 'jquery', 'um_datetime' ), UM_VERSION, true );
		wp_register_script( 'um_datetime_time', UM_URL . 'assets/js/pickadate/picker.time.js', array( 'jquery', 'um_datetime' ), UM_VERSION, true );
		wp_register_script( 'um_conditional', UM_URL . 'assets/js/um-conditional' . $suffix . '.js', array( 'jquery', 'wp-hooks' ), UM_VERSION, true );
		wp_register_script( 'um_scripts', UM_URL . 'assets/js/um-scripts' . $suffix . '.js', array( 'jquery', 'wp-util', 'um_conditional', 'um_datetime', 'um_datetime_date', 'um_datetime_time', self::$select2_handle ), UM_VERSION, true );
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
		wp_register_script( 'um_scrollbar', UM_URL . 'assets/js/simplebar' . $suffix . '.js', array( 'jquery' ), UM_VERSION, true );
		wp_register_script( 'um_crop', UM_URL . 'assets/js/um-crop' . $suffix . '.js', array( 'jquery' ), UM_VERSION, true );
		wp_register_script( 'um_functions', UM_URL . 'assets/js/um-functions' . $suffix . '.js', array( 'jquery', 'jquery-masonry', 'wp-util', 'um_scrollbar' ), UM_VERSION, true );
		wp_register_script( 'um_responsive', UM_URL . 'assets/js/um-responsive' . $suffix . '.js', array( 'jquery', 'um_functions', 'um_crop' ), UM_VERSION, true );

		// render blocks
		wp_enqueue_script( 'um_datetime' );
		wp_enqueue_script( 'um_datetime_date' );
		wp_enqueue_script( 'um_datetime_time' );
		wp_enqueue_script( 'um_conditional' );
		wp_enqueue_script( 'um_dropdown' );
		wp_enqueue_script( 'um_members' );
		wp_enqueue_script( 'um_account' );
		wp_enqueue_script( 'um_scrollbar' );
		wp_enqueue_script( 'um_crop' );
		wp_enqueue_script( 'um_functions' );
		wp_enqueue_script( 'um_responsive' );

		wp_enqueue_style( 'um_default_css' );
		wp_enqueue_style( 'um_members' );
		wp_enqueue_style( 'um_styles' );
		wp_enqueue_style( 'um_profile' );
		wp_enqueue_style( 'um_crop' );
		wp_enqueue_style( 'um_responsive' );
		wp_enqueue_style( 'um_account' );

		$custom_css = '.um{opacity: 1;}.um_request_name {display: none !important;}';

		wp_add_inline_style( 'um_styles', $custom_css );
	}


	public function enqueue_role_wrapper() {
		add_action( 'admin_enqueue_scripts', array( &$this, 'load_role_wrapper' ) );
	}


	/**
	 *
	 */
	public function enqueue_cpt_scripts() {
		// phpcs:ignore WordPress.Security.NonceVerification
		if ( ( isset( $_GET['post_type'] ) && 'um_form' === sanitize_key( $_GET['post_type'] ) ) || ( isset( $_GET['post'] ) && 'um_form' === get_post_type( absint( $_GET['post'] ) ) ) ) {
			$this->um_cpt_form_screen = true;
			add_action( 'admin_footer', array( $this, 'admin_footer_scripts' ), 20 );
		}
	}


	/**
	 *
	 */
	public function enqueue_frontend_preview_assets() {
		$suffix = self::get_suffix();

		//scripts for FRONTEND PREVIEW
		wp_register_script( 'um_jquery_form', $this->front_js_baseurl . 'um-jquery-form' . $suffix . '.js', array( 'jquery' ), UM_VERSION, true );
		wp_register_script( 'um_fileupload', $this->front_js_baseurl . 'um-fileupload.js', array( 'jquery' ), UM_VERSION, true );
		wp_register_script( 'um_crop', $this->front_js_baseurl . 'um-crop' . $suffix . '.js', array( 'jquery' ), UM_VERSION, true );
		wp_register_script( 'um_functions', $this->front_js_baseurl . 'um-functions' . $suffix . '.js', array( 'jquery', 'um_tipsy', 'um_scrollbar' ), UM_VERSION, true );

		wp_register_script( 'um_datetime', $this->front_js_baseurl . 'pickadate/picker.js', array( 'jquery' ), UM_VERSION, true );
		wp_register_script( 'um_datetime_date', $this->front_js_baseurl . 'pickadate/picker.date.js', array( 'jquery', 'um_datetime' ), UM_VERSION, true );
		wp_register_script( 'um_datetime_time', $this->front_js_baseurl . 'pickadate/picker.time.js', array( 'jquery', 'um_datetime' ), UM_VERSION, true );
		//wp_register_script( 'um_datetime_legacy', $this->front_js_baseurl . 'pickadate/legacy.js', array( 'jquery', 'um_datetime' ), UM_VERSION, true );
		// load a localized version for date/time
		$locale = get_locale();
		if ( $locale ) {
			if ( file_exists( WP_LANG_DIR . '/plugins/ultimate-member/assets/js/pickadate/' . $locale . '.js' ) ) {
				wp_register_script( 'um_datetime_locale', content_url() . '/languages/plugins/ultimate-member/assets/js/pickadate/' . $locale . '.js', array( 'jquery', 'um_datetime' ), UM_VERSION, true );
			} elseif ( file_exists( UM_PATH . 'assets/js/pickadate/translations/' . $locale . '.js' ) ) {
				wp_register_script( 'um_datetime_locale', UM_URL . 'assets/js/pickadate/translations/' . $locale . '.js', array( 'jquery', 'um_datetime' ), UM_VERSION, true );
			}
		}

		wp_register_script( 'um_scripts', $this->front_js_baseurl . 'um-scripts' . $suffix . '.js', array( 'um_functions', 'um_crop', 'um_raty', self::$select2_handle, 'um_jquery_form', 'um_fileupload', 'um_datetime', 'um_datetime_date', 'um_datetime_time'/*, 'um_datetime_legacy'*/ ), UM_VERSION, true );
		wp_register_script( 'um_responsive', $this->front_js_baseurl . 'um-responsive' . $suffix . '.js', array( 'um_scripts' ), UM_VERSION, true );
		wp_register_script( 'um_modal', $this->front_js_baseurl . 'um-modal' . $suffix . '.js', array( 'um_responsive' ), UM_VERSION, true );

		wp_register_style( 'um_datetime', $this->front_css_baseurl . 'pickadate/default.css', array(), UM_VERSION );
		wp_register_style( 'um_datetime_date', $this->front_css_baseurl . 'pickadate/default.date.css', array( 'um_datetime' ), UM_VERSION );
		wp_register_style( 'um_datetime_time', $this->front_css_baseurl . 'pickadate/default.time.css', array( 'um_datetime' ), ultimatemember_version );

		wp_register_style( 'um_scrollbar', $this->front_css_baseurl . 'simplebar.css', array(), ultimatemember_version );
		wp_register_style( 'um_crop', $this->front_css_baseurl . 'um-crop.css', array(), ultimatemember_version );
		wp_register_style( 'um_responsive', $this->front_css_baseurl . 'um-responsive.css', array(), ultimatemember_version );
		wp_register_style( 'um_modal', $this->front_css_baseurl . 'um-modal.css', array(), ultimatemember_version );
		wp_register_style( 'um_styles', $this->front_css_baseurl . 'um-styles.css', array( 'um_ui', 'um_tipsy', 'um_raty', 'um_fonticons_ii', 'um_fonticons_fa', 'select2' ), ultimatemember_version );
		wp_register_style( 'um_members', $this->front_css_baseurl . 'um-members.css', array(), ultimatemember_version );
		wp_register_style( 'um_profile', $this->front_css_baseurl . 'um-profile.css', array(), ultimatemember_version );
		wp_register_style( 'um_account', $this->front_css_baseurl . 'um-account.css', array(), ultimatemember_version );
		wp_register_style( 'um_misc', $this->front_css_baseurl . 'um-misc.css', array(), ultimatemember_version );
		wp_register_style( 'um_default_css', $this->front_css_baseurl . 'um-old-default.css', array( 'um_crop', 'um_tipsy', 'um_raty', 'um_responsive', 'um_modal', 'um_styles', 'um_members', 'um_profile', 'um_account', 'um_misc', 'um_datetime_date', 'um_datetime_time', 'um_scrollbar', 'select2' ), UM_VERSION );

		wp_enqueue_script( 'um_modal' );
		wp_enqueue_style( 'um_default_css' );
	}


	/**
	 * Load js for Add/Edit User form
	 */
	public function load_role_wrapper() {
		wp_register_script( 'um_admin_role_wrapper', $this->js_url . 'um-admin-role-wrapper.js', array( 'jquery' ), ultimatemember_version, true );
		$localize_roles_data = get_option( 'um_roles', array() );
		wp_localize_script( 'um_admin_role_wrapper', 'um_roles', (array) $localize_roles_data );
		wp_enqueue_script( 'um_admin_role_wrapper' );
	}


	/**
	 * Enter title placeholder
	 *
	 * @param $title
	 *
	 * @return string
	 */
	public function enter_title_here( $title ) {
		$screen = get_current_screen();
		if ( 'um_directory' === $screen->post_type ) {
			$title = __( 'e.g. Member Directory', 'ultimate-member' );
		} elseif ( 'um_form' === $screen->post_type ) {
			$title = __( 'e.g. New Registration Form', 'ultimate-member' );
		}
		return $title;
	}


	/**
	 * Runs on admin head
	 */
	public function admin_head() {
		if ( UM()->admin()->is_plugin_post_type() ) { ?>
			<style type="text/css">
				.um-admin.post-type-<?php echo esc_attr( get_post_type() ); ?> div#slugdiv,
				.um-admin.post-type-<?php echo esc_attr( get_post_type() ); ?> div#minor-publishing,
				.um-admin.post-type-<?php echo esc_attr( get_post_type() ); ?> div#screen-meta-links
				{display:none}
			</style>
			<?php
		}
	}


	/**
	 * Load Form
	 */
	public function load_form() {
		wp_register_style( 'um_admin_form', $this->css_url . 'um-admin-form.css', array(), ultimatemember_version );
		wp_enqueue_style( 'um_admin_form' );

		wp_register_script( 'um_admin_form', $this->js_url . 'um-admin-form.js', array( 'jquery' ), ultimatemember_version, true );
		wp_enqueue_script( 'um_admin_form' );
	}


	/**
	 * Load Forms
	 */
	public function load_forms() {
		$suffix = self::get_suffix();

		wp_register_style( 'um_admin_forms', $this->css_url . 'um-admin-forms.css', array( 'wp-color-picker', 'um_ui', 'select2' ), UM_VERSION );
		wp_enqueue_style( 'um_admin_forms' );

		wp_register_script( 'um_admin_forms', $this->js_url . 'um-admin-forms.js', array( 'jquery', 'wp-i18n', self::$select2_handle ), UM_VERSION, true );

		wp_localize_script(
			'um_admin_forms',
			'um_forms_data',
			array(
				'successfully_redirect' => add_query_arg(
					array(
						'page' => 'um_options',
						'tab'  => 'misc',
						'msg'  => 'updated',
					),
					admin_url( 'admin.php' )
				),
				'md_sorting_data_types' => UM()->member_directory()->sort_data_types,
			)
		);

		wp_enqueue_script( 'um_admin_forms' );
	}


	/**
	 * Load dashboard
	 */
	public function load_dashboard() {
		wp_register_style( 'um_admin_dashboard', $this->css_url . 'um-admin-dashboard.css', array(), ultimatemember_version );
		wp_enqueue_style( 'um_admin_dashboard' );
	}


	/**
	 * Load settings
	 */
	public function load_settings() {
		wp_register_style( 'um_admin_settings', $this->css_url . 'um-admin-settings.css', array(), ultimatemember_version );
		wp_enqueue_style( 'um_admin_settings' );

		wp_register_script( 'um_admin_settings', $this->js_url . 'um-admin-settings.js', array( 'jquery', 'wp-i18n' ), ultimatemember_version, true );
		wp_enqueue_script( 'um_admin_settings' );
	}


	/**
	 * Load modal
	 */
	public function load_modal() {
		wp_register_style( 'um_admin_modal', $this->css_url . 'um-admin-modal.css', array( 'wp-color-picker' ), ultimatemember_version );
		wp_enqueue_style( 'um_admin_modal' );

		wp_register_script( 'um_admin_modal', $this->js_url . 'um-admin-modal.js', array( 'jquery', 'editor', 'wp-util', 'wp-color-picker', 'wp-tinymce', 'wp-i18n', 'jquery-ui-tooltip', 'um_admin_scripts' ), ultimatemember_version, true );
		wp_enqueue_script( 'um_admin_modal' );
	}


	/**
	 * Field Processing
	 */
	public function load_field() {
		wp_register_script( 'um_admin_field', $this->js_url . 'um-admin-field.js', array( 'jquery', 'wp-util', 'wp-i18n' ), ultimatemember_version, true );
		wp_enqueue_script( 'um_admin_field' );
	}


	/**
	 * Load Builder
	 */
	public function load_builder() {
		wp_register_script( 'um_admin_builder', $this->js_url . 'um-admin-builder.js', array( 'jquery', 'wp-util' ), ultimatemember_version, true );
		wp_enqueue_script( 'um_admin_builder' );

		//hide footer text on add/edit UM Forms
		//layouts crashed because we load and hide metaboxes
		//and WP calculate page height
		$hide_footer = false;
		global $pagenow, $post;
		// phpcs:ignore WordPress.Security.NonceVerification
		if ( ( 'post.php' === $pagenow || 'post-new.php' === $pagenow ) && ( ( isset( $_GET['post_type'] ) && 'um_form' === sanitize_key( $_GET['post_type'] ) ) || ( isset( $post->post_type ) && 'um_form' === $post->post_type ) ) ) {
			$hide_footer = true;
		}

		$localize_data = array(
			'hide_footer' => $hide_footer,
		);
		wp_localize_script( 'um_admin_builder', 'um_admin_builder_data', $localize_data );

		wp_register_script( 'um_admin_dragdrop', $this->js_url . 'um-admin-dragdrop.js', array( 'jquery', 'wp-util' ), ultimatemember_version, true );
		wp_enqueue_script( 'um_admin_dragdrop' );

		wp_register_style( 'um_admin_builder', $this->css_url . 'um-admin-builder.css', array(), ultimatemember_version );
		wp_enqueue_style( 'um_admin_builder' );
	}


	/**
	 * Load core WP styles/scripts
	 */
	public function load_core_wp() {
		wp_enqueue_script( 'jquery-ui-draggable' );
		wp_enqueue_script( 'jquery-ui-sortable' );

		wp_enqueue_script( 'jquery-ui-tooltip' );
	}


	/**
	 * Load Admin Styles
	 */
	public function load_css() {
		wp_register_style( 'um_admin_menu', $this->css_url . 'um-admin-menu.css', array(), ultimatemember_version );
		wp_enqueue_style( 'um_admin_menu' );

		wp_register_style( 'um_admin_columns', $this->css_url . 'um-admin-columns.css', array(), ultimatemember_version );
		wp_enqueue_style( 'um_admin_columns' );

		wp_register_style( 'um_admin_misc', $this->css_url . 'um-admin-misc.css', array(), ultimatemember_version );
		wp_enqueue_style( 'um_admin_misc' );
	}


	/**
	 * Load functions js
	 */
	public function load_functions() {
		wp_register_script( 'um_scrollbar', UM_URL . 'assets/js/simplebar.js', array( 'jquery' ), ultimatemember_version, true );
		wp_register_script( 'um_functions', UM_URL . 'assets/js/um-functions.js', array( 'jquery', 'jquery-masonry', 'wp-util', 'um_scrollbar' ), ultimatemember_version, true );
		wp_enqueue_script( 'um_functions' );
	}


	/**
	 * Load Fonticons
	 *
	 * @depecated 2.7.0
	 */
	public function load_fonticons() {
	}


	/**
	 * Load global css
	 */
	public function load_global_scripts() {
		wp_register_script( 'um_admin_global', $this->js_url . 'um-admin-global.js', array( 'jquery' ), ultimatemember_version, true );
		wp_enqueue_script( 'um_admin_global' );

		wp_register_style( 'um_admin_global', $this->css_url . 'um-admin-global.css', array( 'um_fonticons_ii', 'um_fonticons_fa' ), ultimatemember_version );
		wp_enqueue_style( 'um_admin_global' );
	}


	/**
	 * Load jQuery custom code
	 */
	public function load_custom_scripts() {
		wp_register_script( 'um_datetime', $this->front_js_baseurl . 'pickadate/picker.js', array( 'jquery' ), ultimatemember_version, true );
		wp_register_script( 'um_datetime_date', $this->front_js_baseurl . 'pickadate/picker.date.js', array( 'jquery', 'um_datetime' ), ultimatemember_version, true );
		wp_register_script( 'um_datetime_time', $this->front_js_baseurl . 'pickadate/picker.time.js', array( 'jquery', 'um_datetime' ), ultimatemember_version, true );
		//wp_register_script( 'um_datetime_legacy', $this->front_js_baseurl . 'pickadate/legacy.js', array( 'jquery', 'um_datetime' ), ultimatemember_version, true );
		// load a localized version for date/time
		$locale = get_locale();
		if ( $locale ) {
			if ( file_exists( WP_LANG_DIR . '/plugins/ultimate-member/assets/js/pickadate/' . $locale . '.js' ) ) {
				wp_register_script( 'um_datetime_locale', content_url() . '/languages/plugins/ultimate-member/assets/js/pickadate/' . $locale . '.js', array( 'jquery', 'um_datetime' ), ultimatemember_version, true );
			} elseif ( file_exists( UM_PATH . 'assets/js/pickadate/translations/' . $locale . '.js' ) ) {
				wp_register_script( 'um_datetime_locale', UM_URL . 'assets/js/pickadate/translations/' . $locale . '.js', array( 'jquery', 'um_datetime' ), ultimatemember_version, true );
			}
		}

		wp_register_style( 'um_datetime', $this->front_css_baseurl . 'pickadate/default.css', array(), ultimatemember_version );
		wp_register_style( 'um_datetime_date', $this->front_css_baseurl . 'pickadate/default.date.css', array( 'um_datetime' ), ultimatemember_version );
		wp_register_style( 'um_datetime_time', $this->front_css_baseurl . 'pickadate/default.time.css', array( 'um_datetime' ), ultimatemember_version );

		wp_enqueue_style( 'um_datetime_date' );
		wp_enqueue_style( 'um_datetime_time' );

		wp_register_script( 'um_admin_scripts', $this->js_url . 'um-admin-scripts.js', array( 'jquery', 'wp-util', 'wp-color-picker', 'um_datetime', 'um_datetime_date', 'um_datetime_time'/*, 'um_datetime_legacy'*/ ), ultimatemember_version, true );
		wp_enqueue_script( 'um_admin_scripts' );
	}


	/**
	 * Load jQuery custom code
	 */
	public function load_nav_manus_scripts() {
		wp_register_script( 'um_admin_nav_manus', $this->js_url . 'um-admin-nav-menu.js', array( 'jquery', 'wp-util' ), ultimatemember_version, true );
		wp_enqueue_script( 'um_admin_nav_manus' );
	}


	/**
	 * Load AJAX
	 */
	public function load_ajax_js() {
		wp_register_script( 'um_admin_ajax', $this->js_url . 'um-admin-ajax.js', array( 'jquery', 'wp-util' ), ultimatemember_version, true );
		wp_enqueue_script( 'um_admin_ajax' );
	}


	/**
	 * Load Gutenberg scripts
	 */
	public function load_gutenberg_js() {
		/** This filter is documented in includes/core/class-blocks.php */
		$disable_script = apply_filters( 'um_disable_blocks_script', false );
		if ( $disable_script ) {
			return;
		}

		$restricted_blocks = UM()->options()->get( 'restricted_blocks' );
		if ( empty( $restricted_blocks ) ) {
			return;
		}

		wp_register_script( 'um_block_js', $this->js_url . 'um-admin-blocks.js', array( 'wp-i18n', 'wp-blocks', 'wp-components', 'wp-hooks' ), ultimatemember_version, true );
		wp_set_script_translations( 'um_block_js', 'ultimate-member' );

		$restrict_options = array();
		$roles            = UM()->roles()->get_roles( false );
		if ( ! empty( $roles ) ) {
			foreach ( $roles as $role_key => $title ) {
				$restrict_options[] = array(
					'label' => $title,
					'value' => $role_key,
				);
			}
		}
		wp_localize_script( 'um_block_js', 'um_restrict_roles', $restrict_options );

		wp_enqueue_script( 'um_block_js' );

		do_action( 'um_load_gutenberg_js' );
	}


	/**
	 * Add Gutenberg category for UM shortcodes
	 *
	 * @param array $categories
	 * @param \WP_Block_Editor_Context $context
	 *
	 * @return array
	 */
	public function blocks_category( $categories, $context ) {
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
	 * Load localize scripts
	 */
	public function load_localize_scripts() {

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
		$localize_data = apply_filters( 'um_admin_enqueue_localize_data', array( 'nonce' => wp_create_nonce( 'um-admin-nonce' ) ) );

		wp_localize_script( 'um_admin_global', 'um_admin_scripts', $localize_data );
	}


	/**
	 * Enqueue scripts and styles
	 */
	public function admin_enqueue_scripts() {
		if ( UM()->admin()->is_um_screen() ) {
			$suffix = self::get_suffix();

			/*if ( get_post_type() != 'shop_order' ) {
				UM()->enqueue()->wp_enqueue_scripts();
			}*/

			$modal_deps = array( 'um-admin-scripts' );
			if ( $this->um_cpt_form_screen ) {
				$this->enqueue_frontend_preview_assets();
				$modal_deps[] = 'um-responsive';
			}

			$this->load_functions();
			$this->load_global_scripts();
			$this->load_form();
			$this->load_forms();
			$this->load_custom_scripts();
			$this->load_modal();
			$this->load_dashboard();
			$this->load_settings();
			$this->load_field();
			$this->load_builder();
			$this->load_css();
			$this->load_core_wp();
			$this->load_ajax_js();
			//$this->load_fonticons();
			$this->load_localize_scripts();

			//scripts for frontend preview
			UM()->frontend()->enqueue()->load_imagecrop();
			UM()->frontend()->enqueue()->load_css();
			// UM()->frontend()->enqueue()->load_tipsy();
			UM()->frontend()->enqueue()->load_modal();
			UM()->frontend()->enqueue()->load_responsive();

			//wp_register_script( 'um_raty', UM_URL . 'assets/js/um-raty' . $suffix . '.js', array( 'jquery', 'wp-i18n' ), ultimatemember_version, true );
			//wp_register_style( 'um_raty', UM_URL . 'assets/css/um-raty.css', array(), ultimatemember_version );

			wp_register_style( 'um_default_css', UM_URL . 'assets/css/um-old-default.css', '', ultimatemember_version, 'all' );
			wp_enqueue_style( 'um_default_css' );

			if ( is_rtl() ) {
				wp_register_style( 'um_admin_rtl', $this->css_url . 'um-admin-rtl.css', array(), ultimatemember_version );
				wp_enqueue_style( 'um_admin_rtl' );
			}
		} else {

			$this->load_global_scripts();
			$this->load_localize_scripts();

		}

		global $wp_version, $current_screen;

		if ( version_compare( $wp_version, '5.0', '>=' ) ) {
			if ( isset( $current_screen ) && $current_screen->is_block_editor() ) {
				$this->load_gutenberg_js();
			}
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
			require_once( ABSPATH . WPINC . '/class-wp-editor.php' );
		}

		$class::force_uncompressed_tinymce();
		$class::enqueue_scripts();
		$class::editor_js();
	}

}
