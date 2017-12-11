<?php
namespace um\admin\core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Admin_Forms_Settings' ) ) {
	class Admin_Forms_Settings extends Admin_Forms {

		/**
		 * Admin_Forms constructor.
		 * @param bool $form_data
		 */
		function __construct( $form_data = false ) {

			parent::__construct( $form_data );

		}


		/**
		 * Get field value
		 *
		 * @param array $field_data
		 * @return string|array
		 */
		function get_field_value( $field_data ) {
			$default = isset( $field_data['default'] ) ? $field_data['default'] : UM()->options()->get_default( $field_data['id'] );

			if ( $field_data['type'] == 'checkbox' || $field_data['type'] == 'multi_checkbox' ) {
				if ( isset( $field_data['value'] ) ) {
					return $field_data['value'];
				} else {
					$value = UM()->options()->get( $field_data['id'] );
					return '' !== $value ? $value : $default;
				}
			} else {
				if ( isset( $field_data['value'] ) ) {
					return $field_data['value'];
				} else {
					$value = UM()->options()->get( $field_data['id'] );
					return isset( $value ) ? $value : $default;
				}
			}
		}

	}
}