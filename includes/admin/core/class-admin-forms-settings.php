<?php
namespace um\admin\core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\admin\core\Admin_Forms_Settings' ) ) {

	/**
	 * Class Admin_Forms_Settings
	 * @package um\admin\core
	 */
	class Admin_Forms_Settings extends Admin_Forms {

		/**
		 * Admin_Forms constructor.
		 * @param bool $form_data
		 */
		public function __construct( $form_data = false ) {
			parent::__construct( $form_data );
		}

		/**
		 * Get field value.
		 *
		 * @param array  $field_data
		 * @param string $i
		 *
		 * @return string|array
		 */
		public function get_field_value( $field_data, $i = '' ) {
			$default_key = 'default' . $i;
			$value_key   = 'value' . $i;
			$id_key      = 'id' . $i;

			$default = isset( $field_data[ $default_key ] ) ? $field_data[ $default_key ] : UM()->options()->get_default( $field_data[ $id_key ] );

			if ( in_array( $field_data['type'], array( 'checkbox', 'multi_checkbox' ), true ) ) {
				if ( isset( $field_data[ $value_key ] ) ) {
					return $field_data[ $value_key ];
				}

				$value = UM()->options()->get( $field_data[ $id_key ] );
				$value = is_string( $value ) ? stripslashes( $value ) : $value;
				return '' !== $value ? $value : $default;
			}

			if ( isset( $field_data[ $value_key ] ) ) {
				return $field_data[ $value_key ];
			}

			$value = UM()->options()->get( $field_data[ $id_key ] );
			$value = is_string( $value ) ? stripslashes( $value ) : $value;
			return isset( $value ) ? $value : $default;
		}
	}
}
