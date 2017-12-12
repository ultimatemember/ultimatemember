<?php
namespace um\core;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Options' ) ) {
	class Options {

		var $options = array();

		/**
		 * Access constructor.
		 */
		function __construct() {
			$this->init_variables();
		}


		/**
		 * Set variables
		 */
		function init_variables() {
			$this->options = get_option( 'um_options' );
		}


		/**
		 * Get UM option value
		 *
		 * @param $option_id
		 * @return mixed|string|void
		 */
		function get( $option_id ) {
			if ( isset( $this->options[ $option_id ] ) )
				return apply_filters( "um_get_option_filter__{$option_id}", $this->options[ $option_id ] );

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
			if ( ! empty( $this->options[ $option_id ] ) )
				unset( $this->options[ $option_id ] );

			update_option( 'um_options', $this->options );
		}


		/**
		 * Get UM option default value
		 *
		 * @use UM()->config()
		 *
		 * @param $option_id
		 * @return bool
		 */
		function get_default( $option_id ) {
			$settings_defaults = UM()->config()->settings_defaults;
			if ( ! isset( $settings_defaults[ $option_id ] ) )
				return false;

			return $settings_defaults[ $option_id ];
		}

	}
}