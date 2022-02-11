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

			do_action( 'um_delete_custom_field', $key, $args );

			update_option( 'um_fields', $fields );

			// delete custom field from the UM Forms meta
			global $wpdb;
			$forms = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'um_form'" );
			foreach ( $forms as $form_id ) {
				$form_fields = get_post_meta( $form_id, '_um_custom_fields', true );
				if ( empty( $form_fields ) ) {
					$form_fields = array();
				}
				unset( $form_fields[ $key ] );
				update_post_meta( $form_id, '_um_custom_fields', $form_fields );
			}

			// delete field from Member Directories meta
			$directories = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'um_directory'" );
			foreach ( $directories as $directory_id ) {
				// Frontend filters
				$directory_search_fields = get_post_meta( $directory_id, '_um_search_fields', true );
				if ( empty( $directory_search_fields ) ) {
					$directory_search_fields = array();
				}
				$directory_search_fields = array_values( array_diff( $directory_search_fields, array( $key ) ) );
				update_post_meta( $directory_id, '_um_search_fields', $directory_search_fields );

				// Admin filtering
				$directory_search_filters = get_post_meta( $directory_id, '_um_search_filters', true );
				if ( empty( $directory_search_filters ) ) {
					$directory_search_filters = array();
				}
				unset( $directory_search_filters[ $key ] );
				update_post_meta( $directory_id, '_um_search_filters', $directory_search_filters );

				// display in tagline
				$directory_reveal_fields = get_post_meta( $directory_id, '_um_reveal_fields', true );
				if ( empty( $directory_reveal_fields ) ) {
					$directory_reveal_fields = array();
				}
				$directory_reveal_fields = array_values( array_diff( $directory_reveal_fields, array( $key ) ) );
				update_post_meta( $directory_id, '_um_reveal_fields', $directory_reveal_fields );

				// extra user information section
				$directory_tagline_fields = get_post_meta( $directory_id, '_um_tagline_fields', true );
				if ( empty( $directory_tagline_fields ) ) {
					$directory_tagline_fields = array();
				}
				$directory_tagline_fields = array_values( array_diff( $directory_tagline_fields, array( $key ) ) );
				update_post_meta( $directory_id, '_um_tagline_fields', $directory_tagline_fields );

				// Custom fields selected in "Choose field(s) to enable in sorting"
				$directory_sorting_fields = get_post_meta( $directory_id, '_um_sorting_fields', true );
				if ( empty( $directory_sorting_fields ) ) {
					$directory_sorting_fields = array();
				}
				foreach ( $directory_sorting_fields as $k => $sorting_data ) {
					if ( is_array( $sorting_data ) && array_key_exists( $key, $sorting_data ) ) {
						unset( $directory_sorting_fields[ $k ] );
					}
				}
				$directory_sorting_fields = array_values( $directory_sorting_fields );
				update_post_meta( $directory_id, '_um_sorting_fields', $directory_sorting_fields );

				// If "Default sort users by" = "Other (Custom Field)" is selected when delete this custom field and set default sorting
				$directory_sortby_custom = get_post_meta( $directory_id, '_um_sortby_custom', true );
				if ( $directory_sortby_custom === $key ) {
					$directory_sortby = get_post_meta( $directory_id, '_um_sortby', true );
					if ( 'other' === $directory_sortby ) {
						update_post_meta( $directory_id, '_um_sortby', 'user_registered_desc' );
					}
					update_post_meta( $directory_id, '_um_sortby_custom', '' );
					update_post_meta( $directory_id, '_um_sortby_custom_label', '' );
				}
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
