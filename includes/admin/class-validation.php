<?php
namespace um\admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\admin\Validation' ) ) {

	/**
	 * Class Validation
	 *
	 * @package um\admin
	 */
	final class Validation extends \um\common\Validation {

		public function validate_min_max( $value, $submission, $submission_key, $field_submission, $compare_with ) {
			if ( '' === $value ) {
				return false;
			}

			if ( ! array_key_exists( $compare_with, $field_submission ) || '' === $field_submission[ $compare_with ] ) {
				return false;
			}

			if ( $value > $field_submission[ $compare_with ] ) {
				if ( array_key_exists( 'type', $field_submission ) ) {
					$field_settings_tabs = UM()->admin()->field_group()->get_field_settings( $field_submission['type'] );
					$field_settings      = call_user_func_array( 'array_merge', array_values( $field_settings_tabs ) );

					$min_field_label = $field_settings[ $submission_key ]['label'];
					$max_field_label = $field_settings[ $compare_with ]['label'];

					// translators: %1$s is a Min field label, %2$s is a Max field label
					return sprintf( __( '%1$s should be lower %2$s.', 'ultimate-member' ), $min_field_label, $max_field_label );
				}
				return __( 'Invalid value.', 'ultimate-member' );
			}

			return false;
		}

		public function validate_max_min( $value, $submission, $submission_key, $field_submission, $compare_with ) {
			if ( '' === $value ) {
				return false;
			}

			if ( ! array_key_exists( $compare_with, $field_submission ) || '' === $field_submission[ $compare_with ] ) {
				return false;
			}

			if ( $value < $field_submission[ $compare_with ] ) {
				if ( array_key_exists( 'type', $field_submission ) ) {
					$field_settings_tabs = UM()->admin()->field_group()->get_field_settings( $field_submission['type'] );
					$field_settings      = call_user_func_array( 'array_merge', array_values( $field_settings_tabs ) );

					$max_field_label = $field_settings[ $submission_key ]['label'];
					$min_field_label = $field_settings[ $compare_with ]['label'];

					// translators: %1$s is a Max field label, %2$s is a Min field label
					return sprintf( __( '%1$s should be higher %2$s.', 'ultimate-member' ), $max_field_label, $min_field_label );
				}
				return __( 'Invalid value.', 'ultimate-member' );
			}

			return false;
		}

		public function validate_min_max_date( $value, $submission, $submission_key, $field_submission, $compare_with ) {
			if ( '' === $value ) {
				return false;
			}

			if ( ! array_key_exists( $compare_with, $field_submission ) || '' === $field_submission[ $compare_with ] ) {
				return false;
			}

			if ( strtotime( $value ) > strtotime( $field_submission[ $compare_with ] ) ) {
				if ( array_key_exists( 'type', $field_submission ) ) {
					$field_settings_tabs = UM()->admin()->field_group()->get_field_settings( $field_submission['type'] );
					$field_settings      = call_user_func_array( 'array_merge', array_values( $field_settings_tabs ) );

					$min_field_label = $field_settings[ $submission_key ]['label'];
					$max_field_label = $field_settings[ $compare_with ]['label'];

					// translators: %1$s is a Min field label, %2$s is a Max field label
					return sprintf( __( '%1$s should be lower %2$s.', 'ultimate-member' ), $min_field_label, $max_field_label );
				}
				return __( 'Invalid value.', 'ultimate-member' );
			}

			return false;
		}

		public function validate_max_min_date( $value, $submission, $submission_key, $field_submission, $compare_with ) {
			if ( '' === $value ) {
				return false;
			}

			if ( ! array_key_exists( $compare_with, $field_submission ) || '' === $field_submission[ $compare_with ] ) {
				return false;
			}

			if ( strtotime( $value ) < strtotime( $field_submission[ $compare_with ] ) ) {
				if ( array_key_exists( 'type', $field_submission ) ) {
					$field_settings_tabs = UM()->admin()->field_group()->get_field_settings( $field_submission['type'] );
					$field_settings      = call_user_func_array( 'array_merge', array_values( $field_settings_tabs ) );

					$max_field_label = $field_settings[ $submission_key ]['label'];
					$min_field_label = $field_settings[ $compare_with ]['label'];

					// translators: %1$s is a Max field label, %2$s is a Min field label
					return sprintf( __( '%1$s should be higher %2$s.', 'ultimate-member' ), $max_field_label, $min_field_label );
				}
				return __( 'Invalid value.', 'ultimate-member' );
			}

			return false;
		}

		/**
		 * @param string $key
		 *
		 * @return false|string|null
		 */
		public function validate_user_metakey( $key ) {
			if ( empty( $key ) ) {
				return false;
			}

			$banned_keys = $this->get_banned_keys();
			if ( isset( $banned_keys[ $key ] ) ) {
				return __( 'Your meta key is a WordPress native reserved key and cannot be used.', 'ultimate-member' );
			}

			if ( ! $this->metakey_is_valid( $key ) ) {
				return __( 'Your meta key contains illegal characters. Please correct it.', 'ultimate-member' );
			}

			return false;
		}

		/**
		 * @param string $value
		 * @param array  $submission
		 * @param string $submission_key
		 *
		 * @return false|string
		 */
		public function unique_in_field_group_err( $value, $submission, $submission_key ) {
			if ( empty( $value ) ) {
				return false;
			}

			$matches = array();
			if ( ! empty( $submission ) ) {
				foreach ( $submission as $submission_row ) {
					if ( array_key_exists( $submission_key, $submission_row ) ) {
						if ( $submission_row[ $submission_key ] === $value ) {
							if ( ! isset( $matches[ $submission_row['parent_id'] ] ) ) {
								$matches[ $submission_row['parent_id'] ] = 0;
							}
							if ( 0 === $matches[ $submission_row['parent_id'] ] ) {
								$matches[ $submission_row['parent_id'] ]++;
							} else {
								return __( 'Meta key already exists in your group fields list.', 'ultimate-member' );
							}
						}
					}
				}
			}

			return false;
		}

		/**
		 * Checks for a unique field globally error
		 *
		 * @param string $key
		 *
		 * @return bool|string
		 */
		public function unique_field_globally_err( $key ) {
			if ( isset( $this->predefined_fields[ $key ] ) ) {
				return __( 'Your meta key is a predefined reserved key and cannot be used.', 'ultimate-member' );
			}

			if ( isset( $this->saved_fields[ $key ] ) ) {
				return __( 'Your meta key already exists in your fields list.', 'ultimate-member' );
			}

			$banned_keys = $this->get_banned_keys();
			if ( isset( $banned_keys[ $key ] ) ) {
				return __( 'Your meta key is a WordPress native reserved key and cannot be used.', 'ultimate-member' );
			}

			if ( ! $this->metakey_is_valid( $key ) ) {
				return __( 'Your meta key contains illegal characters. Please correct it.', 'ultimate-member' );
			}

			return false;
		}

		/**
		 * Checks for a unique field metakey inside fields group error
		 *
		 * @param string $key
		 *
		 * @return bool|string
		 */
		public function unique_field_err( $key ) {
			if ( isset( $this->predefined_fields[ $key ] ) ) {
				return __( 'Your meta key is a predefined reserved key and cannot be used.', 'ultimate-member' );
			}

			if ( isset( $this->saved_fields[ $key ] ) ) {
				return __( 'Your meta key already exists in your fields list.', 'ultimate-member' );
			}

			$banned_keys = $this->get_banned_keys();
			if ( isset( $banned_keys[ $key ] ) ) {
				return __( 'Your meta key is a WordPress native reserved key and cannot be used.', 'ultimate-member' );
			}

			if ( ! $this->metakey_is_valid( $key ) ) {
				return __( 'Your meta key contains illegal characters. Please correct it.', 'ultimate-member' );
			}

			return false;
		}
	}
}
