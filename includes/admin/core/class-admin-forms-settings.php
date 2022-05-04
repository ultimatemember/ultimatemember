<?php
/**
 * Extend admin forms for settings
 *
 * @package um\admin\core
 */

namespace um\admin\core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\admin\core\Admin_Forms_Settings' ) ) {


	/**
	 * Class Admin_Forms_Settings
	 */
	class Admin_Forms_Settings extends Admin_Forms {

		/**
		 * Class constructor
		 *
		 * @param array|bool $form_data  Form fields and settings.
		 */
		public function __construct( $form_data = false ) {

			parent::__construct( $form_data );

		}


		/**
		 * Get field value
		 *
		 * @param  array  $field_data  Field data and settings.
		 * @param  string $i           Field index.
		 *
		 * @return string|array
		 */
		public function get_field_value( $field_data, $i = '' ) {
			$default = isset( $field_data[ 'default' . $i ] ) ? $field_data[ 'default' . $i ] : UM()->options()->get_default( $field_data[ 'id' . $i ] );

			if ( 'checkbox' === $field_data['type'] || 'multi_checkbox' === $field_data['type'] ) {
				if ( isset( $field_data[ 'value' . $i ] ) ) {
					return $field_data[ 'value' . $i ];
				} else {
					$value = UM()->options()->get( $field_data[ 'id' . $i ] );
					$value = is_string( $value ) ? stripslashes( $value ) : $value;
					return '' !== $value ? $value : $default;
				}
			} else {
				if ( isset( $field_data[ 'value' . $i ] ) ) {
					return $field_data[ 'value' . $i ];
				} else {
					$value = UM()->options()->get( $field_data[ 'id' . $i ] );
					$value = is_string( $value ) ? stripslashes( $value ) : $value;
					return isset( $value ) ? $value : $default;
				}
			}
		}

	}
}
