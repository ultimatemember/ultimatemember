<?php
namespace um\ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Fields
 *
 * @package um\ajax
 */
class Fields {

	/**
	 * Fields constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_um_select_options', array( &$this, 'ajax_select_options' ) );
		add_action( 'wp_ajax_nopriv_um_select_options', array( &$this, 'ajax_select_options' ) );
	}

	/**
	 *
	 */
	public function ajax_select_options() {
		if ( ! isset( $_POST['child_name'] ) ) {
			wp_send_json_error( __( 'Invalid user ID', 'ultimate-member' ) );
		}
		$child_name = sanitize_text_field( $_POST['child_name'] );

		check_ajax_referer( 'um_dropdown_parent_nonce' . $child_name, 'nonce' );

		// Callback validation
		if ( empty( $_POST['child_callback'] ) ) {
			wp_send_json_error( __( 'Wrong callback.', 'ultimate-member' ) );
		}
		$ajax_source_func = sanitize_text_field( $_POST['child_callback'] );
		if ( ! function_exists( $ajax_source_func ) ) {
			wp_send_json_error( __( 'Wrong callback.', 'ultimate-member' ) );
		}

		$allowed_callbacks = UM()->options()->get( 'allowed_choice_callbacks' );
		if ( empty( $allowed_callbacks ) ) {
			wp_send_json_error( __( 'This is not possible for security reasons.', 'ultimate-member' ) );
		}

		$allowed_callbacks = array_map( 'rtrim', explode( "\n", wp_unslash( $allowed_callbacks ) ) );
		if ( ! in_array( $ajax_source_func, $allowed_callbacks, true ) ) {
			wp_send_json_error( __( 'This is not possible for security reasons.', 'ultimate-member' ) );
		}

		if ( UM()->fields()->is_source_blacklisted( $ajax_source_func ) ) {
			wp_send_json_error( __( 'This is not possible for security reasons.', 'ultimate-member' ) );
		}

		$arr_options = array();

		if ( ! empty( $_POST['member_directory'] ) ) {
			global $wpdb;
			$directory_id              = UM()->member_directory()->get_directory_by_hash( sanitize_text_field( $_POST['member_directory_hash'] ) );
			$disable_filters_pre_query = (bool) get_post_meta( $directory_id, '_um_disable_filters_pre_query', true );

			if ( true !== $disable_filters_pre_query ) {
				$values_array = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT DISTINCT meta_value
					FROM $wpdb->usermeta
					WHERE meta_key = %s AND
						  meta_value != ''",
						$child_name
					)
				);
			}

			$parent_options = array();
			if ( ! empty( $_POST['parent_option'] ) ) {
				if ( is_array( $_POST['parent_option'] ) ) {
					$parent_options = array_map( 'sanitize_text_field', array_map( 'wp_unslash', $_POST['parent_option'] ) );
				} else {
					$parent_options = sanitize_text_field( wp_unslash( $_POST['parent_option'] ) );
				}
			}

			$arr_options['items'] = $ajax_source_func( $parent_options, sanitize_text_field( $_POST['parent_option_name'] ) );

			if ( true === $disable_filters_pre_query && ! empty( $arr_options['items'] ) ) {
				$values_array = $arr_options['items'];
			}

			if ( ! empty( $values_array ) ) {
				if ( array_keys( $arr_options['items'] ) !== range( 0, count( $arr_options['items'] ) - 1 ) ) {
					// array with dropdown items is associative
					if ( true !== (bool) get_post_meta( $directory_id, '_um_disable_filters_pre_query', true ) ) {
						$arr_options['items'] = array_intersect_key( array_map( 'trim', $arr_options['items'] ), array_flip( $values_array ) );
					}
				} else {
					// array with dropdown items has sequential numeric keys, starting from 0 and there are intersected values with $values_array
					$arr_options['items'] = array_intersect( $arr_options['items'], $values_array );
				}
			} else {
				$arr_options['items'] = array();
			}

			wp_send_json_success( $arr_options );
		} else {
			if ( isset( $_POST['form_id'] ) ) {
				UM()->fields()->set_id = absint( $_POST['form_id'] );
			}
			UM()->fields()->set_mode = 'profile';
			$form_fields             = UM()->fields()->get_fields();

			/**
			 * Filters debug mode marker in the custom callback handler for dropdowns.
			 *
			 * @param {bool} $debug Debug mode marker.
			 *
			 * @return {bool}
			 *
			 * @since 1.3.x
			 * @hook um_ajax_select_options__debug_mode
			 *
			 * @example <caption>Enable debug mode in the custom callback handler for dropdowns.</caption>
			 * add_filter( 'um_ajax_select_options__debug_mode', '__return_true' );
			 */
			$debug = apply_filters( 'um_ajax_select_options__debug_mode', false );
			if ( $debug ) {
				$arr_options['debug'] = array( $_POST, $form_fields );
			}

			if ( array_key_exists( $child_name, $form_fields ) ) {
				$choices_callback = UM()->fields()->get_custom_dropdown_options_source( $child_name, $form_fields[ $child_name ] );
				// If the requested callback function is added in the form or added in the field option, execute it with call_user_func.
				if ( $choices_callback === $ajax_source_func ) {
					$arr_options['field'] = $form_fields[ $child_name ];

					// Adds placeholder id needed.
					if ( ! ( isset( $form_fields[ $child_name ]['allowclear'] ) && 0 === $form_fields[ $child_name ]['allowclear'] ) ) {
						$arr_options['items'] = array( '' => __( 'None', 'ultimate-member' ) );
					}

					$parent_options = isset( $_POST['parent_option'] ) ? $_POST['parent_option'] : array();
					if ( ! is_array( $parent_options ) ) {
						$parent_options = array( $parent_options );
					}
					$parent_options = array_map( 'sanitize_text_field', array_map( 'wp_unslash', $parent_options ) );

					$callback_result = $choices_callback( $parent_options, $form_fields[ $child_name ]['parent_dropdown_relationship'] );
					if ( ! empty( $callback_result ) && isset( $arr_options['items'] ) ) {
						$arr_options['items'] = array_merge( $arr_options['items'], $callback_result );
					} else {
						$arr_options['items'] = $callback_result;
					}
				} else {
					wp_send_json_error( __( 'This is not possible for security reasons.', 'ultimate-member' ) );
				}
			}

			wp_send_json_success( $arr_options );
		}
	}
}
