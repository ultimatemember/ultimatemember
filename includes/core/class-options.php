<?php
namespace um\core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\core\Options' ) ) {

	/**
	 * Class Options
	 * @package um\core
	 */
	class Options {

		/**
		 * @var array
		 */
		private $options = array();

		/**
		 * Cached list of option ids whose value is stored as a wp-config.php constant (the `api_key`
		 * settings fields). Null until first resolved. Populated from the `um_api_key_option_ids`
		 * option (kept in sync by {@see \um\admin\core\Admin_Settings::init_variables()}).
		 *
		 * @var array|null
		 */
		private $constant_backed_ids = null;

		/**
		 * Options constructor.
		 */
		public function __construct() {
			$this->init_variables();
		}

		/**
		 * Set variables
		 */
		private function init_variables() {
			$this->options = get_option( 'um_options', array() );
		}

		/**
		 * Get the list of option ids whose value is stored as a wp-config.php constant.
		 *
		 * These are the `api_key` settings fields. The list is derived from the settings structure
		 * (admin) and persisted to the `um_api_key_option_ids` option so it is also available on the
		 * frontend, where the settings structure is not built. Extensions can add ids via the filter.
		 *
		 * @since 2.12.1
		 *
		 * @return array List of option ids.
		 */
		public function get_constant_backed_ids() {
			if ( null === $this->constant_backed_ids ) {
				$stored = get_option( 'um_api_key_option_ids', array() );
				$this->constant_backed_ids = is_array( $stored ) ? $stored : array();
			}

			/**
			 * Filters the list of option ids whose value is stored as a wp-config.php constant.
			 *
			 * @since 2.12.1
			 * @hook um_api_key_option_ids
			 *
			 * @param {array} $ids Option ids.
			 *
			 * @return {array} Option ids.
			 */
			return apply_filters( 'um_api_key_option_ids', $this->constant_backed_ids );
		}

		/**
		 * Persist the list of constant-backed option ids and refresh the in-memory cache.
		 *
		 * @since 2.12.1
		 *
		 * @param array $ids Option ids of the `api_key` fields.
		 */
		public function set_constant_backed_ids( $ids ) {
			$ids = is_array( $ids ) ? array_values( array_unique( $ids ) ) : array();

			if ( get_option( 'um_api_key_option_ids', array() ) !== $ids ) {
				update_option( 'um_api_key_option_ids', $ids );
			}

			$this->constant_backed_ids = $ids;
		}

		/**
		 * Whether an option's value is stored as a wp-config.php constant (i.e. it is an `api_key` field).
		 *
		 * @since 2.12.1
		 *
		 * @param string $option_id
		 *
		 * @return bool
		 */
		public function is_constant_backed( $option_id ) {
			if ( ! is_string( $option_id ) || '' === $option_id ) {
				return false;
			}

			return in_array( $option_id, $this->get_constant_backed_ids(), true );
		}

		/**
		 * Get the wp-config.php constant name that backs a given option.
		 *
		 * Only `api_key` settings fields are constant-backed (see {@see is_constant_backed()}); for any
		 * other option this returns an empty string so the constant lookup in {@see get()} is skipped.
		 * The mapping is convention based: the uppercased option id prefixed with `UM_OPTION_`
		 * (e.g. `stripe_test_secret_key` → `UM_OPTION_STRIPE_TEST_SECRET_KEY`).
		 *
		 * @since 2.12.1
		 *
		 * @param string $option_id
		 *
		 * @return string Constant name, or empty string when the option is not constant-backed.
		 */
		public function get_constant_name( $option_id ) {
			if ( ! $this->is_constant_backed( $option_id ) ) {
				return '';
			}

			$constant = 'UM_OPTION_' . strtoupper( $option_id );

			/**
			 * Filters the wp-config.php constant name that backs a UM option.
			 *
			 * @since 2.12.1
			 * @hook um_option_constant_name
			 *
			 * @param {string} $constant  Constant name (or empty to disable constant backing).
			 * @param {string} $option_id Option id.
			 *
			 * @return {string} Constant name.
			 */
			return apply_filters( 'um_option_constant_name', $constant, $option_id );
		}

		/**
		 * Get UM option value.
		 *
		 * @param string $option_id
		 *
		 * @return mixed
		 */
		public function get( $option_id ) {
			// A defined wp-config.php constant (e.g. for `api_key` secret fields) always wins over the DB.
			$constant = $this->get_constant_name( $option_id );
			if ( $constant && defined( $constant ) ) {
				return apply_filters( "um_get_option_filter__{$option_id}", constant( $constant ) );
			}

			if ( isset( $this->options[ $option_id ] ) ) {
				/**
				 * Filters the plugin option.
				 *
				 * @param {mixed} $value Option value.
				 *
				 * @return {mixed} Option value.
				 *
				 * @since 1.3.67
				 * @hook um_get_option_filter__{$option_id}
				 *
				 * @example <caption>Change `option_1` value.</caption>
				 * function my_custom_option_1( $value ) {
				 *     $value = 'option_1_custom_value';
				 *     return $value;
				 * }
				 * add_filter( 'um_get_option_filter__option_1', 'my_custom_option_1' );
				 */
				return apply_filters( "um_get_option_filter__{$option_id}", $this->options[ $option_id ] );
			}

			switch ( $option_id ) {
				case 'site_name':
					return get_bloginfo( 'name' );
				case 'admin_email':
					return get_bloginfo( 'admin_email' );
				default:
					return '';
			}
		}

		/**
		 * Update UM option value
		 *
		 * @param $option_id
		 * @param $value
		 */
		public function update( $option_id, $value ) {
			$this->options[ $option_id ] = $value;
			update_option( 'um_options', $this->options );
		}

		/**
		 * Delete UM option
		 *
		 * @param $option_id
		 */
		public function remove( $option_id ) {
			if ( ! empty( $this->options[ $option_id ] ) ) {
				unset( $this->options[ $option_id ] );
			}

			update_option( 'um_options', $this->options );
		}

		/**
		 * Get UM option default value
		 *
		 * @use UM()->config()
		 *
		 * @param string $option_id
		 * @return mixed
		 */
		public function get_default( $option_id ) {
			$settings_defaults = UM()->config()->settings_defaults;
			if ( ! isset( $settings_defaults[ $option_id ] ) ) {
				return false;
			}

			return $settings_defaults[ $option_id ];
		}

		/**
		 * Get predefined page option key
		 *
		 * @since 2.8.3
		 *
		 * @param string $slug
		 *
		 * @return string
		 */
		public function get_predefined_page_option_key( $slug ) {
			/**
			 * Filters the predefined page option key.
			 *
			 * @param {string} $option_key Predefined page option key.
			 *
			 * @return {string} Predefined page option key.
			 *
			 * @since 2.8.3
			 * @hook um_predefined_page_option_key
			 *
			 * @example <caption>Change option key for login predefined page.</caption>
			 * function my_um_predefined_page_option_key( $option_key ) {
			 *     if ( 'core_login' === $option_key ) {
			 *         $option_key = 'core_login_custom';
			 *     }
			 *     return $option_key;
			 * }
			 * add_filter( 'um_predefined_page_option_key', 'my_um_predefined_page_option_key' );
			 */
			return apply_filters( 'um_predefined_page_option_key', "core_{$slug}" );
		}

		/**
		 * Get core page ID
		 *
		 * @todo Deprecate soon
		 *
		 * @param string $key
		 *
		 * @return string
		 */
		public function get_core_page_id( $key ) {
			/**
			 * Filters the predefined page option key.
			 *
			 * @param {string} $page_key Predefined page option key.
			 *
			 * @return {string} Predefined page option key.
			 *
			 * @since 1.3.x
			 * @hook um_core_page_id_filter
			 *
			 * @example <caption>Change option key for login predefined page.</caption>
			 * function my_um_core_page_id_filter( $page_key ) {
			 *     if ( 'core_login' === $page_key ) {
			 *         $page_key = 'core_login_custom';
			 *     }
			 *     return $page_key;
			 * }
			 * add_filter( 'um_core_page_id_filter', 'my_um_core_page_id_filter' );
			 */
			return apply_filters( 'um_core_page_id_filter', $this->get_predefined_page_option_key( $key ) );
		}
	}
}
