<?php
namespace umm\member_directory\includes\ajax;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Fields
 * @package umm\member_directory\includes\ajax
 */
class Fields {


	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		add_action( 'wp_ajax_um_select_options', array( $this, 'ajax_select_options' ), 9 );
		add_action( 'wp_ajax_nopriv_um_select_options', array( $this, 'ajax_select_options' ), 9 );
	}


	/**
	 *
	 */
	public function ajax_select_options() {
		UM()->ajax()->check_nonce( 'um-frontend-nonce' );

		$arr_options           = array();
		$arr_options['status'] = 'success';
		$arr_options['post']   = $_POST;

		if ( isset( $_POST['form_id'] ) ) {
			UM()->fields()->set_id = absint( $_POST['form_id'] );
		}
		UM()->fields()->set_mode  = 'profile';
		$form_fields              = UM()->fields()->get_fields();
		$arr_options['fields']    = $form_fields;

		if ( isset( $arr_options['post']['members_directory'] ) && 'yes' === $arr_options['post']['members_directory'] ) {
			$ajax_source_func = $_POST['child_callback'];
			if ( function_exists( $ajax_source_func ) ) {
				global $wpdb;

				$values_array = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT DISTINCT meta_value 
							FROM $wpdb->usermeta 
							WHERE meta_key = %s AND 
								  meta_value != ''",
						$arr_options['post']['child_name']
					)
				);

				if ( ! empty( $values_array ) ) {
					$parent_dropdown = isset( $arr_options['field']['parent_dropdown_relationship'] ) ? $arr_options['field']['parent_dropdown_relationship'] : '';
					$arr_options['items'] = call_user_func( $ajax_source_func, $parent_dropdown );

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

				wp_send_json( $arr_options );
			}
		}
	}
}
