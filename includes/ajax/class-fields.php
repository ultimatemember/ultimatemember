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

		if ( isset( $_POST['form_id'] ) ) {
			UM()->fields()->set_id = absint( $_POST['form_id'] );
		}
		UM()->fields()->set_mode = 'profile';
		$form_fields             = UM()->fields()->get_fields();

		if ( isset( $_POST['members_directory'] ) && 'yes' === $_POST['members_directory'] ) {
			global $wpdb;
			$values_array = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT meta_value
					FROM $wpdb->usermeta
					WHERE meta_key = %s AND
						  meta_value != ''",
					$child_name
				)
			);

			if ( ! empty( $values_array ) ) {
				if ( ! empty( $_POST['parent_option'] ) ) {
					$parent_dropdown      = isset( $_POST['parent_option_name'] ) ? sanitize_text_field( $_POST['parent_option_name'] ) : '';
					$arr_options['items'] = $ajax_source_func( $parent_dropdown );
				} else {
					$arr_options['items'] = array();
				}

				if ( array_keys( $arr_options['items'] ) !== range( 0, count( $arr_options['items'] ) - 1 ) ) {
					// array with dropdown items is associative
					$arr_options['items'] = array_intersect_key( array_map( 'trim', $arr_options['items'] ), array_flip( $values_array ) );
				} else {
					// array with dropdown items has sequential numeric keys, starting from 0 and there are intersected values with $values_array
					$arr_options['items'] = array_intersect( $arr_options['items'], $values_array );
				}
			} else {
				$arr_options['items'] = array();
			}

			wp_send_json_success( $arr_options );
		}

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
			$choices_callback = ! empty( $form_fields[ $child_name ]['custom_dropdown_options_source'] ) ? $form_fields[ $child_name ]['custom_dropdown_options_source'] : '';
			/** This filter is documented in includes/core/class-fields.php */
			$choices_callback = apply_filters( "um_custom_dropdown_options_source__$child_name", $choices_callback, $form_fields[ $child_name ] );

			// If the requested callback function is added in the form or added in the field option, execute it with call_user_func.
			if ( ! empty( $choices_callback ) && function_exists( $choices_callback ) && ! UM()->fields()->is_source_blacklisted( $choices_callback ) && $choices_callback === $ajax_source_func ) {
				$arr_options['field'] = $form_fields[ $child_name ];
			//	$arr_options['items'] = $ajax_source_func( $form_fields[ $child_name ]['parent_dropdown_relationship'] );

				// Adds placeholder id needed.
				if ( ! ( isset( $form_fields[ $child_name ]['allowclear'] ) && 0 === $form_fields[ $child_name ]['allowclear'] ) ) {
					$arr_options['items'] = array( '' => __( 'None', 'ultimate-member' ) );
				}
				$callback_result = $ajax_source_func( $form_fields[ $child_name ]['parent_dropdown_relationship'] );
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
