<?php
namespace um\admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\admin\Forms_Settings' ) ) {

	/**
	 * Class Forms_Settings
	 *
	 * @package um\admin
	 */
	class Forms_Settings extends Forms {

		/**
		 * Forms_Settings constructor.
		 *
		 * @param bool|array $form_data
		 */
		public function __construct( $form_data = false ) {
			parent::__construct( $form_data );
		}

		/**
		 * Get field value
		 *
		 * @param array  $field_data
		 * @param string $i
		 *
		 * @return string|array
		 */
		public function get_field_value( $field_data, $i = '' ) {
			$default = isset( $field_data[ 'default' . $i ] ) ? $field_data[ 'default' . $i ] : UM()->options()->get_default( $field_data[ 'id' . $i ] );

			if ( 'default_avatar' === $field_data['id'] ) {
				$value = UM()->options()->get( $field_data['id'] );
				if ( array_key_exists( 'url', $value ) && empty( $value['url'] ) ) {
					$value = $default;
				}
				return $value;
			} elseif ( 'checkbox' === $field_data['type'] || 'multi_checkbox' === $field_data['type'] ) {
				if ( isset( $field_data[ 'value' . $i ] ) ) {
					return $field_data[ 'value' . $i ];
				} else {
					$value = UM()->options()->get( $field_data[ 'id' . $i ] );
					$value = is_string( $value ) ? stripslashes( $value ) : $value;
					return '' !== $value ? $value : $default;
				}
			} else {
				if ( isset( $field_data[ 'value' . $i ] ) ) {
					return $field_data[ 'value'. $i ];
				} else {
					$value = UM()->options()->get( $field_data[ 'id' . $i ] );
					$value = is_string( $value ) ? stripslashes( $value ) : $value;
					return isset( $value ) ? $value : $default;
				}
			}
		}
	}
}
