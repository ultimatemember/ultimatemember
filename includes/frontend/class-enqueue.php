<?php
namespace um\frontend;


if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'um\frontend\Enqueue' ) ) {


	/**
	 * Class Enqueue
	 *
	 * @package um\frontend
	 */
	final class Enqueue extends \um\common\Enqueue {


		/**
		 * Enqueue constructor.
		 */
		function __construct() {
			parent::__construct();

			add_action( 'init', array( &$this, 'scripts_enqueue_priority' ) );
			add_action( 'enqueue_block_assets', array( &$this, 'add_to_global_styles' ) );
		}

		/**
		 * Adds our custom EDD button colors to the global stylesheet.
		 *
		 * @since 2.0
		 * @return void
		 */
		public function add_to_global_styles() {
			$styling = UM()->options()->get( 'form_styling' );
			if ( ! empty( $styling ) ) {
				return;
			}

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
			$rules  = array();

			$backcolor = UM()->options()->get( 'button_backcolor' );
			if ( empty( $backcolor ) ) {
				$backcolor = '#eee';
			}
			$styles[] = "--um-blocks-button-bg-color:{$backcolor};";

			$backcolor_hover = UM()->options()->get( 'button_backcolor_hover' );
			if ( empty( $backcolor_hover ) ) {
				$backcolor_hover = '#ddd';
			}
			$styles[] = "--um-blocks-button-bg-hover-color:{$backcolor_hover};";

			$forecolor = UM()->options()->get( 'button_forecolor' );
			if ( empty( $forecolor ) ) {
				$forecolor = '#333';
			}
			$styles[] = "--um-blocks-button-fg-color:{$forecolor};";

//			$rules[] = '.um .um-form .um-form-buttons-section input, .um .um-form .um-form-buttons-section button{color: var(--um-blocks-button-fg-color);background-color: var(--um-blocks-button-bg-color);}';

			if ( empty( $styles ) ) {
				return;
			}
			$inline_style = 'body{' . implode( ' ', $styles ) . '}';
			if ( ! empty( $rules ) ) {
				$inline_style .= implode( ' ', $rules );
			}
			//$stylesheet = wp_style_is( 'um_styles', 'registered' ) ? 'um_styles' : 'wp-block-library';
			$stylesheet = 'wp-block-library';
			wp_add_inline_style( $stylesheet, $inline_style );
		}

		/**
		 *
		 */
		public function scripts_enqueue_priority() {
			add_action( 'wp_enqueue_scripts', array( &$this, 'register' ), $this->get_priority() );
		}

		/**
		 * frontend assets registration
		 */
		public function register() {
			$password_reset = array(
				'js'  => array(
					//'path' => $this->urls['js'] . 'um-scripts' . $this->suffix . '.js',
					'path' => $this->urls['js'] . 'password-reset/compiler-regular.js',
					'deps' => array( 'jquery' ),
					'vars' => array(
						'honeypot' => UM()->honeypot,
					),
				),
				'css' => array(
					//'path' => $this->urls['css'] . 'forms' . $this->suffix . '.css',
					'path' => array(
						'base' => $this->urls['css'] . 'password-reset/compiler-regular.css',
						'full' => $this->urls['css'] . 'password-reset/compiler-regular-full.css',
					),
					'deps' => array(),
				),
			);
			$password_reset = apply_filters( 'um_password_reset_assets', $password_reset );

			wp_register_script( 'um-password-reset', $password_reset['js']['path'], $password_reset['js']['deps'], UM_VERSION, true );
			if ( ! empty( $password_reset['js']['vars'] ) ) {
				// localize data if doesn't empty
				wp_localize_script( 'um-password-reset', 'umPasswordReset', $password_reset['js']['vars'] );
			}
			wp_register_style( 'um-password-reset-base', $password_reset['css']['path']['base'], $password_reset['css']['deps'], UM_VERSION );
			wp_register_style( 'um-password-reset-full', $password_reset['css']['path']['full'], $password_reset['css']['deps'], UM_VERSION );

			$login = array(
				'js'  => array(
					//'path' => $this->urls['js'] . 'um-scripts' . $this->suffix . '.js',
					'path' => $this->urls['js'] . 'login/compiler-regular.js',
					'deps' => array( 'jquery' ),
					'vars' => array(
						'honeypot' => UM()->honeypot,
					),
				),
				'css' => array(
					//'path' => $this->urls['css'] . 'forms' . $this->suffix . '.css',
					'path' => array(
						'base' => $this->urls['css'] . 'login/compiler-regular.css',
						'full' => $this->urls['css'] . 'login/compiler-regular-full.css',
					),
					'deps' => array(),
				),
			);
			$login = apply_filters( 'um_login_assets', $login );
			wp_register_script( 'um-login', $login['js']['path'], $login['js']['deps'], UM_VERSION, true );
			if ( ! empty( $login['js']['vars'] ) ) {
				// localize data if doesn't empty
				wp_localize_script( 'um-login', 'umLogin', $login['js']['vars'] );
			}
			wp_register_style( 'um-login-base', $login['css']['path']['base'], $login['css']['deps'], UM_VERSION );
			wp_register_style( 'um-login-full', $login['css']['path']['full'], $login['css']['deps'], UM_VERSION );


			//wp_register_style( 'um_forms', $this->urls['css'] . 'forms' . $this->suffix . '.css', array(), UM_VERSION );




			wp_register_script( 'um_fileupload', $this->urls['libs'] . 'jquery-upload-file/jquery.uploadfile' . $this->suffix . '.js', array( 'jquery', 'jquery-form' ), UM_VERSION, true );
			wp_register_script( 'um_crop', $this->urls['libs'] . 'cropper/cropper' . $this->suffix . '.js', array( 'jquery' ), UM_VERSION, true );
			wp_register_script('um-dropdown', $this->urls['libs'] . 'dropdown/dropdown' . $this->suffix . '.js', array( 'jquery' ), UM_VERSION, true );

			wp_register_script('um_functions', $this->urls['js'] . 'um-functions' . $this->suffix . '.js', array( 'jquery', 'jquery-masonry', 'wp-util' ), UM_VERSION, true );
			wp_register_script( 'um_responsive', $this->urls['js'] . 'um-responsive' . $this->suffix . '.js', array( 'jquery', 'um_functions', 'um_crop' ), UM_VERSION, true );

			wp_register_script( 'um-gdpr', $this->urls['js'] . 'um-gdpr' . $this->suffix . '.js', array( 'jquery' ), UM_VERSION, false );
			wp_register_script('um-conditional', $this->urls['js'] . 'um-conditional' . $this->suffix . '.js', array( 'jquery', 'wp-hooks' ), UM_VERSION, true );

			$deps = array( 'jquery', 'wp-util', 'um_fileupload', 'um_crop', 'um-conditional', 'select2', 'um-modal', 'um-dropdown', 'um-raty', 'um-tipsy', 'um-gdpr', 'um_responsive' );
			$deps = array_merge( $deps, $this->pickadate_deps['js'] );

			wp_register_script('um_scripts', $this->urls['js'] . 'um-scripts' . $this->suffix . '.js', $deps, UM_VERSION, true );

			$max_upload_size = wp_max_upload_size();
			if ( ! $max_upload_size ) {
				$max_upload_size = 0;
			}
			$localize_data = apply_filters( 'um_enqueue_localize_data', array(
				'max_upload_size' => $max_upload_size,
				'nonce'           => wp_create_nonce( 'um-frontend-nonce' ),
			) );
			wp_localize_script( 'um_scripts', 'um_scripts', $localize_data );

			wp_register_script('um_profile', $this->urls['js'] . 'um-profile' . $this->suffix . '.js', array( 'jquery', 'wp-i18n', 'um_scripts' ), UM_VERSION, true );

			$account_deps = apply_filters( 'um_account_scripts_dependencies', array( 'jquery', 'wp-hooks', 'um_scripts' ) );
			wp_register_script('um_account', $this->urls['js'] . 'um-account' . $this->suffix . '.js', $account_deps, UM_VERSION, true );

			wp_enqueue_script( 'um_profile' );
			wp_enqueue_script( 'um_account' );

			// old before 2.0 styles
			wp_register_style( 'um_default_css', $this->urls['css'] . 'um-old-default.css', array(), UM_VERSION );

			wp_register_style( 'um_crop', $this->urls['libs'] . 'cropper/cropper' . $this->suffix . '.css', array(), UM_VERSION );
			wp_register_style( 'um_fileupload', $this->urls['css'] . 'um-fileupload.css', array(), UM_VERSION );

			wp_register_style( 'um_responsive', $this->urls['css'] . 'um-responsive.css', array(), UM_VERSION );

			$deps = array( 'um-jquery-ui', 'um-fontawesome', 'um-ionicons', 'um_crop', 'um_fileupload', 'um-modal', 'um_responsive' );
			$deps = array_merge( $deps, $this->pickadate_deps['css'] );

			// Old FontAwesome and FontIcons styles only for 3rd-party integrations for old customers.
			// All UM core and modules have updated icons
			$um_is_legacy = get_option( 'um_is_legacy' );
			if ( $um_is_legacy ) {
				$deps = array_merge( $deps, array( 'um-fonticons-ii', 'um-fonticons-fa', 'um_default_css' ) );
			}

			wp_register_style( 'um_styles', $this->urls['css'] . 'um-styles' . $this->suffix . '.css', $deps, UM_VERSION );

			if ( is_rtl() ) {
				wp_register_style( 'um_rtl', $this->urls['css'] . 'um.rtl.css', array( 'um_styles' ), UM_VERSION );
				wp_enqueue_style( 'um_rtl' );
			}

//			wp_register_style( 'um_profile', $this->urls['css'] . 'um-profile.css', array( 'um_styles', 'um-tipsy', 'select2' ), UM_VERSION );
//			wp_register_style( 'um_account', $this->urls['css'] . 'um-account.css', array( 'um_styles', 'um-tipsy', 'select2' ), UM_VERSION );
//			wp_register_style( 'um_misc', $this->urls['css'] . 'um-misc.css', array( 'um_styles' ), UM_VERSION );
//
//			wp_enqueue_style( 'um_profile' );
//			wp_enqueue_style( 'um_account' );
//			wp_enqueue_style( 'um_misc' );

//			if ( ! empty( $this->modules_hash ) ) {
//				$modules_min_deps = apply_filters( 'um_modules_min_scripts_dependencies', array( 'jquery', 'wp-hooks', 'wp-i18n' ) );
//				wp_register_script( 'um-modules-min', $this->urls['modules'] . $this->modules_hash . $this->suffix . '.js', $modules_min_deps, UM_VERSION, true );
//
//				$modules_min_variables = apply_filters( 'um_modules_min_scripts_variables', array() );
//				wp_localize_script( 'um-modules-min', 'um_modules_variables', $modules_min_variables );
//
//				$modules_css_deps = apply_filters( 'um_modules_min_styles_dependencies', array() );
//				wp_register_style( 'um-modules-min', $this->urls['modules'] . $this->modules_hash . $this->suffix . '.css', $modules_css_deps, UM_VERSION );
//
//				wp_enqueue_script( 'um-modules-min' );
//				wp_enqueue_style( 'um-modules-min' );
//			}

			$this->old_css_settings();
		}


		/**
		 * @return int
		 */
		public function get_priority() {
			return apply_filters( 'um_core_enqueue_priority', 100 );
		}


		/**
		 *
		 */
		private function old_css_settings() {
			$uploads        = wp_upload_dir();
			$upload_dir     = $uploads['basedir'] . DIRECTORY_SEPARATOR . 'ultimatemember' . DIRECTORY_SEPARATOR;
			if ( file_exists( $upload_dir . 'um_old_settings.css' ) ) {
				wp_register_style( 'um_old_css', um_url . '../../uploads/ultimatemember/um_old_settings.css' );
				wp_enqueue_style( 'um_old_css' );
			}
		}
	}
}
