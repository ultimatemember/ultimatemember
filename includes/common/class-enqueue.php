<?php namespace um\common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\common\Enqueue' ) ) {

	/**
	 * Class Enqueue
	 *
	 * @package um\common
	 */
	class Enqueue {

		/**
		 * @var string scripts' Standard or Minified versions
		 *
		 * @since 3.0
		 */
		var $suffix;

		/**
		 * @var array URLs for easy using
		 *
		 * @since 3.0
		 */
		var $urls;

		/**
		 * @var string FontAwesome version
		 *
		 * @since 3.0
		 */
		var $fa_version = '5.15.4';

		/**
		 * @var string Ionicons version
		 */
		var $ion_version = '4.5.10-1';

		/**
		 * @var string
		 *
		 *@since 3.0
		 */
		var $modules_hash = '';

		/**
		 * Enqueue constructor.
		 *
		 * @since 3.0
		 */
		public function __construct() {
			add_action( 'um_core_loaded', array( $this, 'init_variables' ) );

			add_action( 'admin_enqueue_scripts', array( &$this, 'common_libs' ), 9 );
			add_action( 'wp_enqueue_scripts', array( &$this, 'common_libs' ), 9 );
		}

		/**
		 * Init variables for enqueue scripts
		 *
		 * @since 3.0
		 */
		public function init_variables() {
			$this->suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

			$this->urls['js']      = UM_URL . 'assets/js/';
			$this->urls['css']     = UM_URL . 'assets/css/';
			$this->urls['libs']    = UM_URL . 'assets/libs/';
			$this->urls['modules'] = UM_URL . 'assets/modules/';

			$modules        = UM()->config()->get( 'modules' );
			$modules_inited = UM()->modules()->get_list();
			$modules        = array_keys( array_intersect_key( $modules, $modules_inited ) );

			$hash_array = array();
			if ( ! empty( $modules ) ) {
				foreach ( $modules as $slug ) {
					if ( ! UM()->modules()->is_active( $slug ) ) {
						continue;
					}

					$hash_array[] = $slug;
				}
			}

			if ( ! empty( $hash_array ) ) {
				$this->modules_hash = md5( implode( '', $hash_array ) );
			}
		}


		/**
		 * Register common JS/CSS libraries
		 *
		 * @since 3.0
		 */
		function common_libs() {
			wp_register_style( 'um-jquery-ui', $this->urls['libs'] . 'jquery-ui/jquery-ui' . $this->suffix . '.css', array(), '1.12.1' );

			wp_register_script( 'um-tipsy', $this->urls['libs'] . 'tipsy/um-tipsy' . $this->suffix . '.js', array( 'jquery' ), '1.0.0a', true );
			wp_register_style( 'um-tipsy', $this->urls['libs'] . 'tipsy/um-tipsy' . $this->suffix . '.css', array(), '1.0.0a' );

			wp_register_script( 'um-helptip', $this->urls['libs'] . 'helptip/helptip' . $this->suffix . '.js', array( 'jquery', 'jquery-ui-tooltip' ), '1.0.0', true );
			wp_register_style( 'um-helptip', $this->urls['libs'] . 'helptip/helptip' . $this->suffix . '.css', array( 'dashicons', 'um-jquery-ui' ), '1.0.0' );

			// old fonticons
			$um_is_legacy = get_option( 'um_is_legacy' );
			if ( $um_is_legacy ) {
				wp_register_style( 'um-fonticons-ii', $this->urls['libs'] . 'fonticons/um-fonticons-ii' . $this->suffix . '.css', array(), UM_VERSION );
				wp_register_style( 'um-fonticons-fa', $this->urls['libs'] . 'fonticons/um-fonticons-fa' . $this->suffix . '.css', array(), UM_VERSION );
			}

			// new fonticons since 3.0
			wp_register_style( 'um-far', $this->urls['libs'] . 'fontawesome/css/regular' . $this->suffix . '.css', array(), $this->fa_version );
			wp_register_style( 'um-fas', $this->urls['libs'] . 'fontawesome/css/solid' . $this->suffix . '.css', array(), $this->fa_version );
			wp_register_style( 'um-fab', $this->urls['libs'] . 'fontawesome/css/brands' . $this->suffix . '.css', array(), $this->fa_version );
			wp_register_style( 'um-fa', $this->urls['libs'] . 'fontawesome/css/v4-shims' . $this->suffix . '.css', array(), $this->fa_version );
			wp_register_style( 'um-fontawesome', $this->urls['libs'] . 'fontawesome/css/fontawesome' . $this->suffix . '.css', array( 'um-fa', 'um-far', 'um-fas', 'um-fab' ), $this->fa_version );

			wp_register_style( 'um-ionicons', $this->urls['libs'] . 'ionicons/ionicons' . $this->suffix . '.css', array(), $this->ion_version );

			// Select2
			$dequeue_select2 = apply_filters( 'um_dequeue_select2_scripts', false );
			if ( class_exists( 'WooCommerce' ) || $dequeue_select2 ) {
				wp_dequeue_style( 'select2' );
				wp_deregister_style( 'select2' );

				wp_dequeue_script( 'select2');
				wp_deregister_script('select2');
			}
			wp_register_script( 'select2', $this->urls['libs'] . 'select2/select2.full' . $this->suffix . '.js', array( 'jquery' ), '4.0.13', true );
			wp_register_style( 'select2', $this->urls['libs'] . 'select2/select2' . $this->suffix . '.css', array(), '4.0.13' );

			// Raty JS for rating field-type
			wp_register_script( 'um-raty', $this->urls['libs'] . 'raty/um-raty' . $this->suffix . '.js', array( 'jquery', 'wp-i18n' ), '2.6.0', true );
			wp_register_style( 'um-raty', $this->urls['libs'] . 'raty/um-raty' . $this->suffix . '.css', array(), '2.6.0' );

			// Modal
			wp_register_script( 'um-modal', $this->urls['libs'] . 'modal/um-modal' . $this->suffix . '.js', array( 'jquery', 'wp-i18n', 'wp-hooks' ), UM_VERSION, true );
			wp_register_style( 'um-modal', $this->urls['libs'] . 'modal/um-modal' . $this->suffix . '.css', array(), UM_VERSION );

			// Common JS scripts for wp-admin and frontend both
			wp_register_script( 'um-common', $this->urls['js'] . 'common' . $this->suffix . '.js', array( 'jquery' ), UM_VERSION, true );
			$um_common_variables = apply_filters(
				'um_common_js_variables',
				array(
					'locale' => get_locale(),
				)
			);
			wp_localize_script( 'um-common', 'um_common_variables', $um_common_variables );
			wp_enqueue_script( 'um-common' );
		}
	}
}
