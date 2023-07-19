<?php
namespace um\core;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'um\core\Options' ) ) {


	/**
	 * Class Options
	 * @package um\core
	 */
	class Options {


		/**
		 * @var array
		 */
		var $options = array();


		/**
		 * Options constructor.
		 */
		function __construct() {
			$this->init_variables();
		}


		/**
		 * Set variables
		 */
		function init_variables() {
			$this->options = get_option( 'um_options', array() );
		}


		/**
		 * Get UM option value
		 *
		 * @param $option_id
		 * @return mixed|string|void
		 */
		function get( $option_id ) {
			if ( isset( $this->options[ $option_id ] ) ) {
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
				return apply_filters( "um_get_option_filter__{$option_id}", $this->options[ $option_id ] );
			}

			switch ( $option_id ) {
				case 'site_name':
					return get_bloginfo( 'name' );
					break;
				case 'admin_email':
					return get_bloginfo( 'admin_email' );
					break;
				default:
					return '';
					break;
			}
		}


		/**
		 * Update UM option value
		 *
		 * @param $option_id
		 * @param $value
		 */
		function update( $option_id, $value ) {
			$this->options[ $option_id ] = $value;
			update_option( 'um_options', $this->options );
		}


		/**
		 * Delete UM option
		 *
		 * @param $option_id
		 */
		function remove( $option_id ) {
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
		 * @param $option_id
		 * @return mixed
		 */
		function get_default( $option_id ) {
			$settings_defaults = UM()->config()->settings_defaults;
			if ( ! isset( $settings_defaults[ $option_id ] ) ) {
				return false;
			}

			return $settings_defaults[ $option_id ];
		}


		/**
		 * Get core page ID
		 *
		 * @param string $key
		 *
		 * @return mixed|void
		 */
		function get_core_page_id( $key ) {
			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_core_page_id_filter
			 * @description Change UM page slug
			 * @input_vars
			 * [{"var":"$slug","type":"array","desc":"UM page slug"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_core_page_id_filter', 'function_name', 10, 1 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_core_page_id_filter', 'my_core_page_id', 10, 1 );
			 * function my_core_page_id( $slug ) {
			 *     // your code here
			 *     return $slug;
			 * }
			 * ?>
			 */
			return apply_filters( 'um_core_page_id_filter', 'core_' . $key );
		}

	}
}
