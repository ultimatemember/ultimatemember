<?php
namespace um\admin\core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

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
		function __construct( $form_data = false ) {

			parent::__construct( $form_data );

		}


		/**
		 * Get field value
		 *
		 * @param array $field_data
		 * @param string $i
		 * @return string|array
		 */
		function get_field_value( $field_data, $i = '' ) {
			$default = isset( $field_data['default' . $i] ) ? $field_data['default' . $i] : UM()->options()->get_default( $field_data['id' . $i] );

			if ( $field_data['type'] == 'checkbox' || $field_data['type'] == 'multi_checkbox' ) {
				if ( isset( $field_data['value' . $i] ) ) {
					return $field_data['value' . $i];
				} else {
					$value = UM()->options()->get( $field_data['id' . $i] );
					$value = is_string( $value ) ? stripslashes( $value ) : $value;
					return '' !== $value ? $value : $default;
				}
			} else {
				if ( isset( $field_data['value' . $i] ) ) {
					return $field_data['value'. $i];
				} else {
					$value = UM()->options()->get( $field_data['id' . $i] );
					$value = is_string( $value ) ? stripslashes( $value ) : $value;
					return isset( $value ) ? $value : $default;
				}
			}
		}

	}
}