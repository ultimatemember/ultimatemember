<?php
namespace um\frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Enqueue.
 *
 * @package um\frontend
 */
final class Enqueue extends \um\common\Enqueue {

	/**
	 * @var string
	 * @deprecated 2.8.0
	 */
	public $js_baseurl = '';

	/**
	 * @var string
	 * @deprecated 2.8.0
	 */
	public $css_baseurl = '';

	/**
	 * Enqueue constructor.
	 */
	public function __construct() {
		parent::__construct();
		add_action( 'init', array( &$this, 'scripts_enqueue_priority' ) );
		add_filter( 'body_class', array( &$this, 'body_class' ), 0 );
	}

	/**
	 * @since 2.1.3
	 */
	public function scripts_enqueue_priority() {
		add_action( 'wp_enqueue_scripts', array( &$this, 'wp_enqueue_scripts' ), $this->get_priority() );
		add_action( 'enqueue_block_assets', array( &$this, 'add_to_global_styles' ) );
	}

	/**
	 * Extend body classes.
	 *
	 * @param array $classes
	 *
	 * @return array
	 */
	public function body_class( $classes ) {
		$array = UM()->config()->get( 'predefined_pages' );
		if ( empty( $array ) || ! is_array( $array ) ) {
			return $classes;
		}

		foreach ( array_keys( $array ) as $slug ) {
			if ( um_is_core_page( $slug ) ) {
				$classes[] = 'um-page';
				$classes[] = 'um-page-' . $slug;

				if ( is_user_logged_in() ) {
					$classes[] = 'um-page-loggedin';

					if ( 'user' === $slug && um_is_user_himself() ) {
						$classes[] = 'um-own-profile';
					}
				} else {
					$classes[] = 'um-page-loggedout';
				}
			}
		}

		return $classes;
	}

	/**
	 * @since 2.1.3
	 * @return int
	 */
	public function get_priority() {
		/**
		 * Filters Ultimate Member frontend scripts enqueue priority.
		 *
		 * @since 1.3.x
		 * @hook um_core_enqueue_priority
		 *
		 * @param {int} $priority Ultimate Member frontend scripts enqueue priority.
		 *
		 * @return {int} Ultimate Member frontend scripts enqueue priority.
		 *
		 * @example <caption>Change Ultimate Member frontend enqueue scripts priority.</caption>
		 * function custom_um_core_enqueue_priority( $priority ) {
		 *     $priority = 101;
		 *     return $priority;
		 * }
		 * add_filter( 'um_core_enqueue_priority', 'custom_um_core_enqueue_priority' );
		 */
		return apply_filters( 'um_core_enqueue_priority', 100 );
	}

	/**
	 * Register JS scripts.
	 *
	 * @since 2.0.30
	 */
	public function register_scripts() {
		$suffix   = self::get_suffix();
		$libs_url = self::get_url( 'libs' );
		$js_url   = self::get_url( 'js' );
		$css_url  = self::get_url( 'css' );

		if ( defined( 'UM_DEV_MODE' ) && UM_DEV_MODE && UM()->options()->get( 'enable_new_ui' ) ) {
			// New one.
			wp_register_script( 'um_dropdown', $libs_url . 'dropdown/dropdown' . $suffix . '.js', array( 'jquery', 'wp-hooks' ), UM_VERSION, true );
			wp_register_style( 'um_dropdown', $libs_url . 'dropdown/dropdown' . $suffix . '.css', array(), UM_VERSION );

			wp_register_script( 'um_choices', $libs_url . 'choices-js/choices' . $suffix . '.js', array(), '10.2.0', true );
			wp_register_style( 'um_choices', $css_url . 'um-choices' . $suffix . '.css', array(), UM_VERSION );

			wp_register_script( 'um-gdpr', $js_url . 'um-gdpr' . $suffix . '.js', array( 'jquery' ), UM_VERSION, false );

			//wp_register_style( 'um_new_profile', $css_url . 'new-profile' . $suffix . '.css', array(), UM_VERSION );

			// Cropper.js
			wp_register_script( 'um_crop', $libs_url . 'cropper/cropper' . $suffix . '.js', array( 'jquery' ), '1.6.1', true );
			wp_register_style( 'um_crop', $libs_url . 'cropper/cropper' . $suffix . '.css', array(), '1.6.1' );

			wp_register_script( 'um_frontend_common', $js_url . 'common-frontend-new' . $suffix . '.js', array( 'um_common', 'um_crop', 'um_dropdown' ), UM_VERSION, true );

			wp_register_script( 'um_modal', $libs_url . 'modal/modal' . $suffix . '.js', array( 'um_frontend_common' ), UM_VERSION, true );
			wp_register_style( 'um_modal', $libs_url . 'modal/modal' . $suffix . '.css', array(), UM_VERSION );

			wp_register_script( 'um_new_design', $js_url . 'new-design' . $suffix . '.js', array( 'um_frontend_common', 'plupload', 'um_choices', 'um_confirm' ), UM_VERSION, true );
			wp_register_style( 'um_new_design', $css_url . 'new-design' . $suffix . '.css', array( 'um_tipsy', 'um_dropdown', 'um_crop', 'um_modal', 'um_choices', 'um_confirm' ), UM_VERSION );

			wp_register_script( 'um_members', $js_url . 'um-members' . $suffix . '.js', array( 'jquery', 'wp-util', 'jquery-ui-slider', 'wp-hooks', 'jquery-masonry' ), UM_VERSION, true );
			wp_register_script( 'um_directory', $js_url . 'directory' . $suffix . '.js', array( 'jquery', 'wp-util', 'jquery-ui-slider', 'wp-hooks', 'jquery-masonry' ), UM_VERSION, true );

			// uploadFiles scripts + UM custom styles for uploader.
			wp_register_script( 'um_jquery_form', $libs_url . 'jquery-form/jquery-form' . $suffix . '.js', array( 'jquery' ), UM_VERSION, true );
			wp_register_script( 'um_fileupload', $libs_url . 'fileupload/fileupload.js', array( 'um_jquery_form' ), UM_VERSION, true );

//			$um_common_variables = array();
//			/**
//			 * Filters data array for localize frontend common scripts.
//			 *
//			 * @since 2.8.0
//			 * @hook um_frontend_common_js_variables
//			 *
//			 * @param {array} $variables Data to localize.
//			 *
//			 * @return {array} Data to localize.
//			 *
//			 * @example <caption>Add `my_custom_variable` to common frontend scripts to be callable via `um_frontend_common_variables.my_custom_variable` in JS.</caption>
//			 * function um_custom_frontend_common_js_variables( $variables ) {
//			 *     $variables['{my_custom_variable}'] = '{my_custom_variable_value}';
//			 *     return $variables;
//			 * }
//			 * add_filter( 'um_frontend_common_js_variables', 'um_custom_frontend_common_js_variables' );
//			 */
//			$um_common_variables = apply_filters( 'um_frontend_common_js_variables', $um_common_variables );
//			wp_localize_script( 'um_frontend_common', 'um_frontend_common_variables', $um_common_variables );
//
//			// uploadFiles scripts + UM custom styles for uploader.
//			wp_register_script( 'um_jquery_form', $libs_url . 'jquery-form/jquery-form' . $suffix . '.js', array( 'jquery' ), UM_VERSION, true );
//			wp_register_script( 'um_fileupload', $libs_url . 'fileupload/fileupload.js', array( 'um_jquery_form' ), UM_VERSION, true );
//
//			wp_register_script( 'um_functions', $js_url . 'um-functions' . $suffix . '.js', array( 'um_frontend_common', 'um_fileupload' ), UM_VERSION, true );
//
//			wp_register_script( 'um_modal', $js_url . 'um-modal' . $suffix . '.js', array( 'um_frontend_common' ), UM_VERSION, true );
//
//			wp_register_script( 'um_functions', $js_url . 'um-functions' . $suffix . '.js', array( 'um_frontend_common', 'jquery-masonry' ), UM_VERSION, true );
//			wp_register_script( 'um_responsive', $js_url . 'um-responsive' . $suffix . '.js', array( 'um_functions' ), UM_VERSION, true );
//
//			wp_register_script( 'um_conditional', $js_url . 'um-conditional' . $suffix . '.js', array( 'jquery', 'wp-hooks' ), UM_VERSION, true );
//			wp_register_script( 'um_scripts', $js_url . 'um-scripts' . $suffix . '.js', array( 'um_frontend_common', 'um_conditional', self::$select2_handle, 'um_raty' ), UM_VERSION, true );
//
//			$max_upload_size = wp_max_upload_size();
//			if ( ! $max_upload_size ) {
//				$max_upload_size = 0;
//			}
//
//			$localize_data = array(
//				'max_upload_size' => $max_upload_size,
//				'nonce'           => wp_create_nonce( 'um-frontend-nonce' ),
//			);
//			/**
//			 * Filters data array for localize frontend scripts.
//			 *
//			 * @param {array} $variables Data to localize.
//			 *
//			 * @return {array} Data to localize.
//			 *
//			 * @since 2.0.0
//			 * @hook um_enqueue_localize_data
//			 *
//			 * @example <caption>Extend UM localized data.</caption>
//			 * function my_enqueue_localize_data( $variables ) {
//			 *     // your code here
//			 *     return $variables;
//			 * }
//			 * add_filter( 'um_enqueue_localize_data', 'my_enqueue_localize_data' );
//			 */
//			$localize_data = apply_filters( 'um_enqueue_localize_data', $localize_data );
//			wp_localize_script( 'um_scripts', 'um_scripts', $localize_data );
//
//			wp_register_script( 'um_profile', $js_url . 'um-profile' . $suffix . '.js', array( 'jquery', 'wp-util', 'wp-i18n', 'um_scripts' ), UM_VERSION, true );
//			wp_set_script_translations( 'um_profile', 'ultimate-member' );
		} else {
			// Cropper.js
			wp_register_script( 'um_crop', $libs_url . 'cropper/cropper' . $suffix . '.js', array( 'jquery' ), '1.6.1', true );
			wp_register_style( 'um_crop', $libs_url . 'cropper/cropper' . $suffix . '.css', array(), '1.6.1' );

			wp_register_script( 'um_frontend_common', $js_url . 'common-frontend' . $suffix . '.js', array( 'um_common', 'um_crop' ), UM_VERSION, true );
			$um_common_variables = array();
			/**
			 * Filters data array for localize frontend common scripts.
			 *
			 * @since 2.8.0
			 * @hook um_frontend_common_js_variables
			 *
			 * @param {array} $variables Data to localize.
			 *
			 * @return {array} Data to localize.
			 *
			 * @example <caption>Add `my_custom_variable` to common frontend scripts to be callable via `um_frontend_common_variables.my_custom_variable` in JS.</caption>
			 * function um_custom_frontend_common_js_variables( $variables ) {
			 *     $variables['{my_custom_variable}'] = '{my_custom_variable_value}';
			 *     return $variables;
			 * }
			 * add_filter( 'um_frontend_common_js_variables', 'um_custom_frontend_common_js_variables' );
			 */
			$um_common_variables = apply_filters( 'um_frontend_common_js_variables', $um_common_variables );
			wp_localize_script( 'um_frontend_common', 'um_frontend_common_variables', $um_common_variables );

			// uploadFiles scripts + UM custom styles for uploader.
			wp_register_script( 'um_jquery_form', $libs_url . 'jquery-form/jquery-form' . $suffix . '.js', array( 'jquery' ), UM_VERSION, true );
			wp_register_script( 'um_fileupload', $libs_url . 'fileupload/fileupload.js', array( 'um_jquery_form' ), UM_VERSION, true );

			wp_register_script( 'um_functions', $js_url . 'um-functions' . $suffix . '.js', array( 'um_frontend_common', 'um_fileupload' ), UM_VERSION, true );

			wp_register_script( 'um_modal', $js_url . 'um-modal' . $suffix . '.js', array( 'um_frontend_common' ), UM_VERSION, true );

			wp_register_script( 'um_functions', $js_url . 'um-functions' . $suffix . '.js', array( 'um_frontend_common', 'jquery-masonry' ), UM_VERSION, true );
			wp_register_script( 'um_responsive', $js_url . 'um-responsive' . $suffix . '.js', array( 'um_functions' ), UM_VERSION, true );

			wp_register_script( 'um-gdpr', $js_url . 'um-gdpr' . $suffix . '.js', array( 'jquery' ), UM_VERSION, false );
			wp_register_script( 'um_conditional', $js_url . 'um-conditional' . $suffix . '.js', array( 'jquery', 'wp-hooks' ), UM_VERSION, true );
			wp_register_script( 'um_scripts', $js_url . 'um-scripts' . $suffix . '.js', array( 'um_frontend_common', 'um_conditional', self::$select2_handle, 'um_raty' ), UM_VERSION, true );

			$max_upload_size = wp_max_upload_size();
			if ( ! $max_upload_size ) {
				$max_upload_size = 0;
			}

			$localize_data = array(
				'max_upload_size' => $max_upload_size,
				'nonce'           => wp_create_nonce( 'um-frontend-nonce' ),
			);
			/**
			 * Filters data array for localize frontend scripts.
			 *
			 * @param {array} $variables Data to localize.
			 *
			 * @return {array} Data to localize.
			 *
			 * @since 2.0.0
			 * @hook um_enqueue_localize_data
			 *
			 * @example <caption>Extend UM localized data.</caption>
			 * function my_enqueue_localize_data( $variables ) {
			 *     // your code here
			 *     return $variables;
			 * }
			 * add_filter( 'um_enqueue_localize_data', 'my_enqueue_localize_data' );
			 */
			$localize_data = apply_filters( 'um_enqueue_localize_data', $localize_data );
			wp_localize_script( 'um_scripts', 'um_scripts', $localize_data );

			wp_register_script( 'um_dropdown', $js_url . 'dropdown' . $suffix . '.js', array( 'jquery' ), UM_VERSION, true );

			wp_register_script( 'um_members', $js_url . 'um-members' . $suffix . '.js', array( 'jquery', 'wp-util', 'jquery-ui-slider', 'um_dropdown', 'wp-hooks', 'jquery-masonry', 'um_scripts' ), UM_VERSION, true );
			wp_register_script( 'um_profile', $js_url . 'um-profile' . $suffix . '.js', array( 'jquery', 'wp-util', 'wp-i18n', 'um_scripts' ), UM_VERSION, true );
			wp_set_script_translations( 'um_profile', 'ultimate-member' );

			/**
			 * Filters account script dependencies.
			 *
			 * @since 2.1.8
			 * @hook um_account_scripts_dependencies
			 *
			 * @param {array} $deps JS script dependencies.
			 *
			 * @return {array} JS script dependencies.
			 *
			 * @example <caption>Add `wp-util` as a dependencies script.</caption>
			 * function um_custom_account_scripts_dependencies( $deps ) {
			 *     $deps[] = 'wp-util';
			 *     return $deps;
			 * }
			 * add_filter( 'um_account_scripts_dependencies', 'um_custom_account_scripts_dependencies' );
			 */
			$account_deps = apply_filters( 'um_account_scripts_dependencies', array( 'jquery', 'wp-hooks', 'um_scripts' ) );
			wp_register_script( 'um_account', $js_url . 'um-account' . $suffix . '.js', $account_deps, UM_VERSION, true );
		}
	}

	/**
	 * Register styles.
	 *
	 * @since 2.0.30
	 */
	public function register_styles() {
		$suffix   = self::get_suffix();
		$libs_url = self::get_url( 'libs' );
		$css_url  = self::get_url( 'css' );

		if ( defined( 'UM_DEV_MODE' ) && UM_DEV_MODE && UM()->options()->get( 'enable_new_ui' ) ) {
			wp_register_style( 'um_fileupload', $css_url . 'um-fileupload' . $suffix . '.css', array(), UM_VERSION );

			wp_register_style( 'um_directory', $css_url . 'directory' . $suffix . '.css', array( 'um_new_design' ), UM_VERSION );

			//FontAwesome and FontIcons styles
//			wp_register_style( 'um_rtl', $css_url . 'um.rtl' . $suffix . '.css', array(), UM_VERSION );
//			wp_register_style( 'um_default_css', $css_url . 'um-old-default' . $suffix . '.css', array(), UM_VERSION );
//			wp_register_style( 'um_modal', $css_url . 'um-modal' . $suffix . '.css', array(), UM_VERSION );
//			wp_register_style( 'um_responsive', $css_url . 'um-responsive' . $suffix . '.css', array(), UM_VERSION );
//
//			// Workaround when select2 deregistered (e.g. Woo + Impreza theme activated).
//			$this->register_select2();
//
//			wp_register_style( 'um_styles', $css_url . 'um-styles' . $suffix . '.css', array( 'um_ui', 'um_tipsy', 'um_raty', 'um_fonticons_ii', 'um_fonticons_fa', 'select2', 'um_fileupload', 'um_common', 'um_responsive', 'um_modal' ), UM_VERSION );
//
//			wp_register_style( 'um_profile', $css_url . 'um-profile' . $suffix . '.css', array( 'um_styles', 'um_crop' ), UM_VERSION );
//			wp_register_style( 'um_misc', $css_url . 'um-misc' . $suffix . '.css', array( 'um_styles' ), UM_VERSION );
		} else {
			wp_register_style( 'um_fileupload', $css_url . 'um-fileupload' . $suffix . '.css', array(), UM_VERSION );

			//FontAwesome and FontIcons styles
			wp_register_style( 'um_rtl', $css_url . 'um.rtl' . $suffix . '.css', array(), UM_VERSION );
			wp_register_style( 'um_default_css', $css_url . 'um-old-default' . $suffix . '.css', array(), UM_VERSION );
			wp_register_style( 'um_modal', $css_url . 'um-modal' . $suffix . '.css', array(), UM_VERSION );
			wp_register_style( 'um_responsive', $css_url . 'um-responsive' . $suffix . '.css', array(), UM_VERSION );

			// Workaround when select2 deregistered (e.g. Woo + Impreza theme activated).
			$this->register_select2();

			$deps = array_merge( array( 'um_ui', 'um_tipsy', 'um_raty', 'select2', 'um_fileupload', 'um_common', 'um_responsive', 'um_modal' ), self::$fonticons_handlers );
			wp_register_style( 'um_styles', $css_url . 'um-styles' . $suffix . '.css', $deps, UM_VERSION );

			wp_register_style( 'um_members', $css_url . 'um-members' . $suffix . '.css', array( 'um_styles' ), UM_VERSION );
			// RTL styles.
			if ( is_rtl() ) {
				wp_style_add_data( 'um_members', 'rtl', true );
				wp_style_add_data( 'um_members', 'suffix', $suffix );
			}

			wp_register_style( 'um_profile', $css_url . 'um-profile' . $suffix . '.css', array( 'um_styles', 'um_crop' ), UM_VERSION );
			wp_register_style( 'um_account', $css_url . 'um-account' . $suffix . '.css', array( 'um_styles' ), UM_VERSION );
			wp_register_style( 'um_misc', $css_url . 'um-misc' . $suffix . '.css', array( 'um_styles' ), UM_VERSION );
		}
	}

	/**
	 * Enqueue scripts and styles.
	 *
	 * @since 2.0.0
	 */
	public function wp_enqueue_scripts() {
		$this->register_scripts();
		$this->register_styles();

		$this->load_original();

		// rtl style
		if ( is_rtl() ) {
			wp_enqueue_style( 'um_rtl' );
		}

		global $post;
		if ( is_object( $post ) && has_shortcode( $post->post_content, 'ultimatemember' ) ) {
			wp_dequeue_script( 'jquery-form' );
		}

		//old settings before UM 2.0 CSS
		wp_enqueue_style( 'um_default_css' );

		$this->old_css_settings();
	}

	/**
	 * Adds our custom button colors to the global stylesheet.
	 *
	 * @since 2.8.4
	 */
	public function add_to_global_styles() {
		if ( defined( 'UM_DEV_MODE' ) && UM_DEV_MODE && UM()->options()->get( 'enable_new_ui' ) ) {
			$styles = apply_filters(
				'um_inline_styles_variables',
				array(
					'--um-gray-25:#fcfcfd;',
					'--um-gray-50:#f9fafb;',
					'--um-gray-100:#f2f4f7;',
					'--um-gray-200:#eaecf0;',
					'--um-gray-300:#d0d5dd;',
					'--um-gray-400:#98a2b3;',
					'--um-gray-500:#667085;',
					'--um-gray-600:#475467;',
					'--um-gray-700:#344054;',
					'--um-gray-800:#1d2939;',
					'--um-gray-900:#101828;',
				)
			);

			$shadow_shades = array( 3, 5, 6, 8, 10, 14, 18 );
			foreach ( $shadow_shades as $opacity ) {
				$alpha    = $opacity / 100;
				$styles[] = "--um-gray-900-a-{$opacity}:rgba(16,24,40,{$alpha});";
			}

			$gray_ring_shades = array( 14, 20 );
			foreach ( $gray_ring_shades as $opacity ) {
				$alpha    = $opacity / 100;
				$styles[] = "--um-gray-400-a-{$opacity}:rgba(152,162,179,{$alpha});";
			}

			$error_ring_shades = array( 24 );
			foreach ( $error_ring_shades as $opacity ) {
				$alpha    = $opacity / 100;
				$styles[] = "--um-error-500-a-{$opacity}:rgba(240,68,56,{$alpha});";
			}

			$palette = UM()->common()::color()->generate_palette( UM()->options()->get( 'primary_color' ) );
			foreach ( $palette as $title => $colors ) {
				$styles[] = '--um-primary-' . $title . '-bg:' . esc_attr( $colors['bg'] ) . ';';
				$styles[] = '--um-primary-' . $title . '-fg:' . esc_attr( $colors['fg'] ) . ';';
			}

			$primary_ring_shades = array( 24 );
			foreach ( $primary_ring_shades as $opacity ) {
				$alpha = $opacity / 100;
				if ( ! empty( $palette['500']['bg'] ) ) {
					$rgb      = UM()->common()::color()->hex2rgb( $palette['500']['bg'] );
					$rgb      = implode( ',', $rgb );
					$styles[] = "--um-primary-500-a-{$opacity}:rgba({$rgb},{$alpha});";
				} else {
					$styles[] = "--um-primary-500-a-{$opacity}:rgba(158,119,237,{$alpha});";
				}
			}

			$rules = array();
			if ( empty( $styles ) ) {
				return;
			}
			$inline_style = 'body{' . implode( ' ', $styles ) . '}';
			if ( ! empty( $rules ) ) {
				$inline_style .= implode( ' ', $rules );
			}

			$stylesheet = 'wp-block-library';
			wp_add_inline_style( $stylesheet, $inline_style );

			$dynamic_styles = '';
			$forms_query    = get_posts(
				array(
					'post_type'      => array( 'um_form', 'um_directory' ),
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'fields'         => 'ids',
				)
			);

			foreach ( $forms_query as $form_id ) {
				$form_data = UM()->query()->post_data( $form_id );

				if ( isset( $form_data['max_width'] ) && $form_data['max_width'] ) {
					$dynamic_styles .= '.um-' . esc_attr( $form_data['form_id'] ) . '.um {max-width: ' . esc_attr( $form_data['max_width'] ) . ';}';
				}
				if ( isset( $form_data['align'] ) && in_array( $form_data['align'], array( 'left', 'right' ), true ) ) {
					$dynamic_styles .= '.um-' . esc_attr( $form_data['form_id'] ) . '.um {margin-' . esc_attr( $form_data['align'] ) . ': 0px !important;}';
				}

				if ( array_key_exists( 'mode', $form_data ) && 'profile' === $form_data['mode'] ) {

					if ( ! isset( $form_data['photosize'] ) || 'original' === $form_data['photosize'] ) {
						$form_data['photosize'] = um_get_metadefault( 'profile_photosize' ); // Cannot be more than metadefault value.
					}

					$form_data['photosize'] = absint( $form_data['photosize'] );

					$photosize_up = ( $form_data['photosize'] / 2 ) + 10;
					$meta_padding = ( $form_data['photosize'] + 60 ) . 'px';

					if ( ! empty( $form_data['area_max_width'] ) ) {
						$dynamic_styles .= '.um-' . esc_attr( $form_data['form_id'] ) . '.um .um-profile-body {max-width: ' . esc_attr( $form_data['area_max_width'] ) . ';}';
						$dynamic_styles .= '.um-' . esc_attr( $form_data['form_id'] ) . '.um .um-profile-body .um-form-new {width: ' . esc_attr( $form_data['area_max_width'] ) . ';}';
					}

					$dynamic_styles .= '.um-' . esc_attr( $form_data['form_id'] ) . '.um .um-profile-photo a.um-profile-photo-img {width: ' . esc_attr( $form_data['photosize'] ) . 'px; height: ' . esc_attr( $form_data['photosize'] ) . 'px;}';
					$dynamic_styles .= '.um-' . esc_attr( $form_data['form_id'] ) . '.um .um-profile-photo a.um-profile-photo-img {top: -' . esc_attr( $photosize_up ) . 'px;}';

					if ( is_rtl() ) {
						$dynamic_styles .= '.um-' . esc_attr( $form_data['form_id'] ) . '.um .um-profile-meta {padding-right: ' . esc_attr( $meta_padding ) . ';}';
					} else {
						$dynamic_styles .= '.um-' . esc_attr( $form_data['form_id'] ) . '.um .um-profile-meta {padding-left: ' . esc_attr( $meta_padding ) . ';}';
					}
				}
			}

			wp_add_inline_style( $stylesheet, $dynamic_styles );
		}
	}

	/**
	 * @since 2.0.30
	 */
	public function old_css_settings() {
		$uploads    = wp_upload_dir();
		$upload_dir = $uploads['basedir'] . DIRECTORY_SEPARATOR . 'ultimatemember' . DIRECTORY_SEPARATOR;
		if ( file_exists( $upload_dir . 'um_old_settings.css' ) ) {
			wp_register_style( 'um_old_css', UM_URL . '../../uploads/ultimatemember/um_old_settings.css', array(), '2.0.0' );
			wp_enqueue_style( 'um_old_css' );
		}
	}

	/**
	 * This will load original files (not minified)
	 *
	 * @since 2.0.0
	 */
	public function load_original() {
		$this->load_modal();
		$this->load_css();
		$this->load_functions();
		$this->load_responsive();
		$this->load_customjs();
	}

	/**
	 * Load plugin CSS
	 *
	 * @since 2.0.0
	 */
	public function load_css() {
		wp_enqueue_style( 'um_styles' );
		wp_enqueue_style( 'um_profile' );
		wp_enqueue_style( 'um_account' );
		wp_enqueue_style( 'um_misc' );
	}

	/**
	 * Load JS functions.
	 *
	 * @since 2.0.0
	 */
	public function load_functions() {
		wp_enqueue_script( 'um_functions' );
		wp_enqueue_script( 'um-gdpr' );
	}

	/**
	 * Load custom JS.
	 *
	 * @since 2.0.0
	 */
	public function load_customjs() {
		wp_enqueue_script( 'um_conditional' );
		wp_enqueue_script( 'um_scripts' );
		wp_enqueue_script( 'um_profile' );
		wp_enqueue_script( 'um_account' );
	}

	/**
	 * Load modal.
	 *
	 * @since 2.0.0
	 */
	public function load_modal() {
		wp_enqueue_script( 'um_modal' );
		wp_enqueue_style( 'um_modal' );
	}

	/**
	 * Load responsive styles.
	 *
	 * @since 2.0.0
	 */
	public function load_responsive() {
		wp_enqueue_script( 'um_responsive' );
		wp_enqueue_style( 'um_responsive' );
	}

	/**
	 * Include Google charts
	 * @deprecated 2.8.0
	 */
	public function load_google_charts() {
	}

	/**
	 * Load fileupload JS
	 * @deprecated 2.8.0
	 */
	public function load_fileupload() {
	}

	/**
	 * Load date & time picker
	 * @deprecated 2.8.0
	 */
	public function load_datetimepicker() {
	}

	/**
	 * Load scrollbar
	 * @deprecated 2.8.0
	 */
	public function load_scrollbar() {
	}

	/**
	 * Load crop script
	 * @deprecated 2.8.0
	 */
	public function load_imagecrop() {
	}
}
