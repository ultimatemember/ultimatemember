<?php namespace um\common;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'um\common\Field' ) ) {


	/**
	 * Class Field
	 *
	 * @package um\common
	 */
	class Field {


		/**
		 * Field constructor.
		 *
		 * @since 3.0
		 */
		function __construct() {
		}


		/**
		 * Delete custom field from DB
		 *
		 * @todo don't use UM()->builtin()->saved_fields please use $fields = get_option( 'um_fields', array() ); instead for getting globally saved UM custom fields and remove selected field from there
		 *
		 * @param string $key
		 *
		 * @return bool
		 */
		function delete_permanently( $key ) {
			$fields = UM()->builtin()->saved_fields;

			if ( ! array_key_exists( $key, $fields ) ) {
				return false;
			}

			$args = $fields[ $key ];

			unset( $fields[ $key ] );

			/**
			 * Fires before an Ultimate Member custom field is permanently removed.
			 *
			 * @since 1.x
			 * @hook  um_delete_custom_field
			 *
			 * @param {string} $key  Field meta_key.
			 * @param {array}  $args Field data.
			 */
			do_action( 'um_delete_custom_field', $key, $args );

			update_option( 'um_fields', $fields );

			// delete custom field from the UM Forms meta
			global $wpdb;
			$forms = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'um_form'" );
			foreach ( $forms as $form_id ) {
				$form_fields = get_post_meta( $form_id, '_um_custom_fields', true );
				if ( empty( $form_fields ) || ! is_array( $form_fields ) ) {
					$form_fields = array();
				}
				unset( $form_fields[ $key ] );
				update_post_meta( $form_id, '_um_custom_fields', $form_fields );
			}

			return true;
		}


		/**
		 * Duplicates a field by meta key
		 *
		 * @param string $metakey
		 * @param int $form_id
		 */
		function duplicate( $metakey, $form_id ) {
			$fields     = UM()->query()->get_attr( 'custom_fields', $form_id );
			$all_fields = UM()->builtin()->saved_fields;

			$inc = count( $fields ) + 1;

			$duplicate = $fields[ $metakey ];

			$new_metakey  = $metakey . '_' . $inc;
			$new_title    = $fields[ $metakey ]['title'] . " #" . $inc;
			$new_position = $inc;

			$duplicate['title']    = $new_title;
			$duplicate['metakey']  = $new_metakey;
			$duplicate['position'] = $new_position;

			$fields[ $new_metakey ]     = $duplicate;
			$all_fields[ $new_metakey ] = $duplicate;

			// not global attributes
			unset( $all_fields[ $new_metakey ]['in_row'] );
			unset( $all_fields[ $new_metakey ]['in_sub_row'] );
			unset( $all_fields[ $new_metakey ]['in_column'] );
			unset( $all_fields[ $new_metakey ]['in_group'] );
			unset( $all_fields[ $new_metakey ]['position'] );

			do_action( 'um_add_new_field', $new_metakey, $duplicate );

			UM()->query()->update_attr( 'custom_fields', $form_id, $fields );
			update_option( 'um_fields', $all_fields );
		}


		/**
		 * Delete a field from a form
		 *
		 * @param string $metakey
		 * @param int $form_id
		 *
		 * @return bool
		 */
		function delete_from_form( $metakey, $form_id ) {
			$fields = UM()->query()->get_attr( 'custom_fields', $form_id );

			if ( ! array_key_exists( $metakey, $fields ) ) {
				return false;
			}

			// remove the $metakey field if it's used as a conditional field everywhere
			$condition_fields = get_option( 'um_fields', array() );
			if ( ! is_array( $condition_fields ) ) {
				$condition_fields = array();
			}

			foreach ( $condition_fields as $key => $value ) {
				$deleted_field = array_search( $metakey, $value );

				if ( $key != $metakey && false !== $deleted_field ) {
					$deleted_field_id = str_replace( 'conditional_field', '', $deleted_field );

					if ( '' === $deleted_field_id ) {
						$arr_id = 0;
					} else {
						$arr_id = $deleted_field_id;
					}

					unset( $condition_fields[ $key ][ 'conditional_action' . $deleted_field_id ] );
					unset( $condition_fields[ $key ][ $deleted_field ] );
					unset( $condition_fields[ $key ][ 'conditional_operator' . $deleted_field_id ] );
					unset( $condition_fields[ $key ][ 'conditional_value' . $deleted_field_id ] );
					unset( $condition_fields[ $key ]['conditions'][ $arr_id ] );

					unset( $fields[ $key ][ 'conditional_action' . $deleted_field_id ] );
					unset( $fields[ $key ][ $deleted_field ] );
					unset( $fields[ $key ][ 'conditional_operator' . $deleted_field_id ] );
					unset( $fields[ $key ][ 'conditional_value' . $deleted_field_id ] );
					unset( $fields[ $key ]['conditions'][ $arr_id ] );
				}
			}

			update_option( 'um_fields', $condition_fields );
			unset( $fields[ $metakey ] );
			UM()->query()->update_attr( 'custom_fields', $form_id, $fields );

			return true;
		}



	}
}
