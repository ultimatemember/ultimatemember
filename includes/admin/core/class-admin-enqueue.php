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

			$this->front_js_baseurl = um_url . 'assets/js/';
			$this->front_css_baseurl = um_url . 'assets/css/';

			$this->suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || defined( 'UM_SCRIPT_DEBUG' ) ) ? '' : '.min';

			$this->um_cpt_form_screen = false;

			add_action( 'admin_head', array( &$this, 'admin_head' ), 9 );

			add_action( 'admin_enqueue_scripts',  array( &$this, 'admin_enqueue_scripts' ) );

			add_filter( 'enter_title_here', array( &$this, 'enter_title_here' ) );

			add_action( 'load-user-new.php', array( &$this, 'enqueue_role_wrapper' ) );
			add_action( 'load-user-edit.php', array( &$this, 'enqueue_role_wrapper' ) );

			add_action( 'load-post-new.php', array( &$this, 'enqueue_cpt_scripts' ) );
			add_action( 'load-post.php', array( &$this, 'enqueue_cpt_scripts' ) );

			add_filter( 'block_categories', array( &$this, 'blocks_category' ), 10, 2 );
		}


		function enqueue_role_wrapper() {
			add_action( 'admin_enqueue_scripts',  array( &$this, 'load_role_wrapper' ) );
		}


		/**
		 *
		 */
		function enqueue_cpt_scripts() {
			if ( ( isset( $_GET['post_type'] ) && 'um_form' == sanitize_key( $_GET['post_type'] ) ) ||
			     ( isset( $_GET['post'] ) && 'um_form' == get_post_type( absint( $_GET['post'] ) ) ) ) {
				$this->um_cpt_form_screen = true;
				add_action( 'admin_footer',  array( $this, 'admin_footer_scripts' ), 20 );
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


			wp_register_script( 'select2', $this->front_js_baseurl . 'select2/select2.full.min.js', array( 'jquery', 'jquery-masonry' ), ultimatemember_version, true );
			wp_register_script( 'um_jquery_form', $this->front_js_baseurl . 'um-jquery-form' . $this->suffix . '.js', array( 'jquery' ), ultimatemember_version, true );
			wp_register_script( 'um_fileupload', $this->front_js_baseurl . 'um-fileupload' . $this->suffix . '.js', array( 'jquery' ), ultimatemember_version, true );
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


			wp_register_style( 'select2', $this->front_css_baseurl . 'select2/select2.min.css', array(), ultimatemember_version );
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
		 * Load js for Add/Edit User form
		 */
		function load_role_wrapper() {
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
		 * Runs on admin head
		 */
		function admin_head() {
			if ( UM()->admin()->is_plugin_post_type() ) { ?>
				<style type="text/css">
					.um-admin.post-type-<?php echo esc_attr( get_post_type() ); ?> div#slugdiv,
					.um-admin.post-type-<?php echo esc_attr( get_post_type() ); ?> div#minor-publishing,
					.um-admin.post-type-<?php echo esc_attr( get_post_type() ); ?> div#screen-meta-links
					{display:none}
				</style>
			<?php }
		}


		/**
		 * Load Form
		 */
		function load_form() {
			wp_register_style( 'um_admin_form', $this->css_url . 'um-admin-form.css', array(), ultimatemember_version );
			wp_enqueue_style( 'um_admin_form' );

			wp_register_script( 'um_admin_form', $this->js_url . 'um-admin-form.js', array( 'jquery' ), ultimatemember_version, true );
			wp_enqueue_script( 'um_admin_form' );
		}


		/**
		 * Load Forms
		 */
		function load_forms() {
			wp_register_style( 'um_ui', $this->front_css_baseurl . 'jquery-ui.css', array(), ultimatemember_version );
			wp_register_style( 'um_admin_forms', $this->css_url . 'um-admin-forms.css', array( 'wp-color-picker', 'um_ui' ), ultimatemember_version );
			wp_enqueue_style( 'um_admin_forms' );

			wp_register_script( 'um_admin_forms', $this->js_url . 'um-admin-forms.js', array( 'jquery', 'wp-i18n' ), ultimatemember_version, true );

			wp_localize_script( 'um_admin_forms', 'um_forms_data', array(
				'successfully_redirect' => add_query_arg( array( 'page' => 'um_options', 'tab' => 'misc', 'msg' => 'updated' ), admin_url( 'admin.php' ) ),
			) );

			wp_enqueue_script( 'um_admin_forms' );
		}


		/**
		 * Load dashboard
		 */
		function load_dashboard() {
			wp_register_style( 'um_admin_dashboard', $this->css_url . 'um-admin-dashboard.css', array(), ultimatemember_version );
			wp_enqueue_style( 'um_admin_dashboard' );
		}


		/**
		 * Load settings
		 */
		function load_settings() {
			wp_register_style( 'um_admin_settings', $this->css_url . 'um-admin-settings.css', array(), ultimatemember_version );
			wp_enqueue_style( 'um_admin_settings' );

			wp_register_script( 'um_admin_settings', $this->js_url . 'um-admin-settings.js', array( 'jquery', 'wp-i18n' ), ultimatemember_version, true );
			wp_enqueue_script( 'um_admin_settings' );
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
		 * Field Processing
		 */
		function load_field() {
			wp_register_script( 'um_admin_field', $this->js_url . 'um-admin-field.js', array('jquery', 'wp-util'), ultimatemember_version, true );
			wp_enqueue_script( 'um_admin_field' );
		}


		/**
		 * Load Builder
		 */
		function load_builder() {
			wp_register_script( 'um_admin_builder', $this->js_url . 'um-admin-builder.js', array('jquery', 'wp-util'), ultimatemember_version, true );
			wp_enqueue_script( 'um_admin_builder' );

			//hide footer text on add/edit UM Forms
			//layouts crashed because we load and hide metaboxes
			//and WP calculate page height
			$hide_footer = false;
			global $pagenow, $post;
			if ( ( 'post.php' == $pagenow || 'post-new.php' == $pagenow ) &&
			     ( ( isset( $_GET['post_type'] ) && 'um_form' == sanitize_key( $_GET['post_type'] ) ) ||
			       ( isset( $post->post_type ) && 'um_form' == $post->post_type ) ) ) {
				$hide_footer = true;
			}

			$localize_data = array(
				'hide_footer' => $hide_footer,
			);
			wp_localize_script( 'um_admin_builder', 'um_admin_builder_data', $localize_data );

			wp_register_script( 'um_admin_dragdrop', $this->js_url . 'um-admin-dragdrop.js', array('jquery', 'wp-util'), ultimatemember_version, true );
			wp_enqueue_script( 'um_admin_dragdrop' );

			wp_register_style( 'um_admin_builder', $this->css_url . 'um-admin-builder.css', array(), ultimatemember_version );
			wp_enqueue_style( 'um_admin_builder' );
		}


		/**
		 * Load core WP styles/scripts
		 */
		function load_core_wp() {
			wp_enqueue_script( 'jquery-ui-draggable' );
			wp_enqueue_script( 'jquery-ui-sortable' );

			wp_enqueue_script( 'jquery-ui-tooltip' );
		}


		/**
		 * Load Admin Styles
		 */
		function load_css() {
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
		 * Load global css
		 */
		function load_global_scripts() {
			wp_register_script( 'um_admin_global', $this->js_url . 'um-admin-global.js', array('jquery'), ultimatemember_version, true );
			wp_enqueue_script( 'um_admin_global' );

			wp_register_style( 'um_admin_global', $this->css_url . 'um-admin-global.css', array(), ultimatemember_version );
			wp_enqueue_style( 'um_admin_global' );
		}


		/**
		 * Load jQuery custom code
		 */
		function load_custom_scripts() {
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

			wp_register_style( 'um_datetime', $this->front_css_baseurl . 'pickadate/default.css', array(), ultimatemember_version );
			wp_register_style( 'um_datetime_date', $this->front_css_baseurl . 'pickadate/default.date.css', array( 'um_datetime' ), ultimatemember_version );
			wp_register_style( 'um_datetime_time', $this->front_css_baseurl . 'pickadate/default.time.css', array( 'um_datetime' ), ultimatemember_version );

			wp_enqueue_style( 'um_datetime_date', 'um_datetime_time' );

			wp_register_script( 'um_admin_scripts', $this->js_url . 'um-admin-scripts.js',  array('jquery','wp-util', 'wp-color-picker', 'um_datetime', 'um_datetime_date', 'um_datetime_time'/*, 'um_datetime_legacy'*/ ), ultimatemember_version, true );
			wp_enqueue_script( 'um_admin_scripts' );
		}


		/**
		 * Load jQuery custom code
		 */
		function load_nav_manus_scripts() {
			wp_register_script( 'um_admin_nav_manus', $this->js_url . 'um-admin-nav-menu.js', array('jquery','wp-util'), ultimatemember_version, true );
			wp_enqueue_script( 'um_admin_nav_manus' );
		}


		/**
		 * Load AJAX
		 */
		function load_ajax_js() {
			wp_register_script( 'um_admin_ajax', $this->js_url . 'um-admin-ajax.js', array('jquery','wp-util'), ultimatemember_version, true );
			wp_enqueue_script( 'um_admin_ajax' );
		}


		/**
		 * Load Gutenberg scripts
		 */
		function load_gutenberg_js() {
			//disable Gutenberg scripts to avoid the conflicts
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
			$roles = UM()->roles()->get_roles( false );
			if ( ! empty( $roles ) ) {
				foreach ( $roles as $role_key => $title ) {
					$restrict_options[] = array(
						'label' => $title,
						'value' => $role_key
					);
				}
			}
			wp_localize_script( 'um_block_js', 'um_restrict_roles', $restrict_options );

			wp_enqueue_script( 'um_block_js' );

			do_action( 'um_load_gutenberg_js' );
		}


		/**
		 * Load Gutenberg blocks js
		 */
		function load_gutenberg_shortcode_blocks() {
			if ( ! function_exists( 'register_block_type' ) ) {
				// Gutenberg is not active.
				return;
			}

			//disable Gutenberg scripts to avoid the conflicts
			$disable_script = apply_filters( 'um_disable_blocks_script', false );
			if ( $disable_script ) {
				return;
			}

			$enable_blocks = UM()->options()->get( 'enable_blocks' );
			if ( empty( $enable_blocks ) ) {
				return;
			}

			wp_register_script( 'um-blocks-shortcode-js', $this->js_url . 'um-admin-blocks-shortcode.js', array( 'wp-i18n', 'wp-blocks', 'wp-components', /*'rich-text'*/ ), ultimatemember_version, true );
			wp_set_script_translations( 'um-blocks-shortcode-js', 'ultimate-member' );
			wp_enqueue_script( 'um-blocks-shortcode-js' );

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
			wp_localize_script( 'um-blocks-shortcode-js', 'um_account_settings', $account_settings );

			/**
			 * create gutenberg blocks
			 */
			register_block_type( 'um-block/um-forms', array(
				'editor_script' => 'um-blocks-shortcode-js',
			) );

			register_block_type( 'um-block/um-member-directories', array(
				'editor_script' => 'um-blocks-shortcode-js',
			) );

			register_block_type( 'um-block/um-password-reset', array(
				'editor_script' => 'um-blocks-shortcode-js',
			) );

			register_block_type( 'um-block/um-account', array(
				'editor_script' => 'um-blocks-shortcode-js',
			) );
		}


		/**
		 * Add Gutenberg category for UM shortcodes
		 *
		 * @param array $categories
		 * @param $post
		 *
		 * @return array
		 */
		 function blocks_category( $categories, $post ) {
			 $enable_blocks = UM()->options()->get( 'enable_blocks' );
			 if ( empty( $enable_blocks ) ) {
				 return $categories;
			 }

		 	return array_merge(
				 $categories,
				 array(
					 array(
						 'slug'     => 'um-blocks',
						 'title'    => __( 'Ultimate Member Blocks', 'ultimate-member' ),
					 ),
				 )
			 );
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
			$localize_data = apply_filters( 'um_admin_enqueue_localize_data', array(
					'nonce' => wp_create_nonce( "um-admin-nonce" )
				)
			);

			wp_localize_script( 'um_admin_global', 'um_admin_scripts', $localize_data );
		}


		/**
		 * Enqueue scripts and styles
		 */
		function admin_enqueue_scripts() {
			if ( UM()->admin()->is_um_screen() ) {

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
				$this->load_fonticons();
				$this->load_localize_scripts();


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

				if ( is_rtl() ) {
					wp_register_style( 'um_admin_rtl', $this->css_url . 'um-admin-rtl.css', array(), ultimatemember_version );
					wp_enqueue_style( 'um_admin_rtl' );
				}

			} else {

				$this->load_global_scripts();
				$this->load_localize_scripts();

			}

			global $wp_version, $current_screen;
			if ( version_compare( $wp_version, '5.0', '>=' ) && ! empty( $this->post_page ) ) {

				if ( $current_screen->is_block_editor() ) {
					$this->load_gutenberg_js();
					$this->load_gutenberg_shortcode_blocks();
				}
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
