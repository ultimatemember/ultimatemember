<?php
/**
 * Provides class for getting operated with Ultimate Member options.
 *
 * @package um\common
 */
namespace um\common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\common\Options' ) ) {

	/**
	 * Class Options.
	 *
	 * @example UM()->options();
	 * @package um\common
	 */
	class Options {

		/**
		 * Options constructor.
		 */
		public function __construct() {
		}

		/**
		 * @return array
		 */
		private function get_options() {
			$options = get_option( 'um_options', array() );
			// Handle the case if there aren't properly installed options or they were crashed
			if ( ! is_array( $options ) ) {
				$options = UM()->config()->get( 'default_settings' );
				update_option( 'um_options', $options );
			}

			return $options;
		}

		/**
		 * Get UM option value
		 *
		 * @param string $option_id  Name of the option to retrieve. Expected to not be SQL-escaped.
		 * @param mixed  $default Optional. Default value to return if the option does not exist.
		 *
		 * @return mixed Value of the option. A value of any type may be returned, including
		 *               scalar (string, boolean, float, integer), null, array, object.
		 *               Scalar and null values will be returned as strings as long as they originate
		 *               from a database stored option value. If there is no option in the database,
		 *               boolean `false` is returned.
		 *
		 */
		public function get( $option_id, $default = false ) {
			switch ( $option_id ) {
				case 'site_name':
					return get_bloginfo( 'name' );
				case 'admin_email':
					return get_bloginfo( 'admin_email' );
				default:
					$options = $this->get_options();
					$value   = array_key_exists( $option_id, $options ) ? $options[ $option_id ] : $default;
					/**
					 * UM hook
					 *
					 * @type filter
					 * @title um_get_option_filter__{$option_id}
					 * @description Change UM option on get by $option_id
					 * @input_vars
					 * [{"var":"$option","type":"array","desc":"Option Value"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage
					 * <?php add_filter( 'um_get_option_filter__{$option_id}', 'function_name', 10, 1 ); ?>
					 * @example
					 * <?php
					 * add_filter( 'um_get_option_filter__{$option_id}', 'my_get_option_filter', 10, 1 );
					 * function my_get_option_filter( $option ) {
					 *     // your code here
					 *     return $option;
					 * }
					 * ?>
					 */
					return apply_filters( "um_get_option_filter__{$option_id}", $value, $option_id );
			}
		}

		/**
		 * Add UM option value
		 *
		 * @param $option_id
		 * @param $value
		 */
		public function add( $option_id, $value ) {
			$options = $this->get_options();
			if ( ! array_key_exists( $option_id, $options ) ) {
				$options[ $option_id ] = $value;
				update_option( 'um_options', $options );
			}
		}

		/**
		 * Update UM option value
		 *
		 * @param $option_id
		 * @param $value
		 */
		public function update( $option_id, $value ) {
			$options               = $this->get_options();
			$options[ $option_id ] = $value;
			update_option( 'um_options', $options );
		}

		/**
		 * Delete UM option
		 *
		 * @param $option_id
		 */
		public function remove( $option_id ) {
			$options = $this->get_options();
			if ( array_key_exists( $option_id, $options ) ) {
				unset( $options[ $option_id ] );
				update_option( 'um_options', $options );
			}
		}

		/**
		 * Get UM option default value
		 *
		 * @use UM()->config()
		 *
		 * @param $option_id
		 * @return bool
		 */
		public function get_default( $option_id ) {
			$settings_defaults = UM()->config()->get( 'default_settings' );
			if ( ! array_key_exists( $option_id, $settings_defaults ) ) {
				return false;
			}

			return $settings_defaults[ $option_id ];
		}

		/**
		 * Get predefined page option key
		 *
		 * @param string $slug
		 *
		 * @return string
		 */
		public function get_predefined_page_option_key( $slug ) {
			return apply_filters( 'um_predefined_page_option_key', "core_{$slug}" );
		}

		/**
		 * Set default UM settings
		 *
		 * @since 3.0
		 *
		 * @param array $defaults
		 */
		public function set_defaults( $defaults ) {
			$need_update = false;
			$options     = get_option( 'um_options', array() );

			if ( ! empty( $defaults ) ) {
				foreach ( $defaults as $key => $value ) {
					//set new options to default
					if ( ! array_key_exists( $key, $options ) ) {
						$options[ $key ] = $value;
						$need_update     = true;
					}
				}
			}

			if ( $need_update ) {
				update_option( 'um_options', $options );
			}
		}

		/**
		 * Get core page ID
		 *
		 * @deprecated 3.0
		 *
		 * @param string $key
		 *
		 * @return string
		 */
		function get_core_page_id( $key ) {
			_deprecated_function( __METHOD__, '3.0', 'UM()->options()->get_predefined_page_option_key()' );
			return apply_filters( 'um_core_page_id_filter', $this->get_predefined_page_option_key( $key ) );
		}
	}
}
