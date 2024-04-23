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
		 * Get UM option value.
		 *
		 * @param string $option_id
		 *
		 * @return mixed
		 */
		public function get( $option_id ) {
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
