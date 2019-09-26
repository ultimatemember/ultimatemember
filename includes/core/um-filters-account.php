<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


/**
 * Disables first and last name fields in account page
 * @param  array $fields
 * @return array
 * @uses  um_get_field__first_name, um_get_field__last_name
 */
function um_account_disable_name_fields( $fields ){
	if ( ! UM()->options()->get( 'account_name_disable' ) ) {
		return $fields;
	}

	if ( um_is_core_page( 'account' ) ) {
		$fields['disabled'] = 'disabled="disabled"';
	}

	return $fields;
}
add_filter( 'um_get_field__first_name', 'um_account_disable_name_fields', 10 ,1 );
add_filter( 'um_get_field__last_name', 'um_account_disable_name_fields', 10 ,1 );


/**
 * Sanitize inputs on Account update
 *
 * @param $data
 *
 * @return mixed
 */
function um_account_sanitize_data( $data ) {
	foreach ( $data as $key => $value ) {
		if ( is_array( $value ) ) {
			$data[ $key ] = array_filter( $value, function( $var ) {
				$var = trim( esc_html( strip_tags( $var ) ) );
				return $var;
			});
		} else {
			$data[ $key ] = trim( esc_html( strip_tags( $value ) ) );
		}
	}

	return $data;
}
add_filter( 'um_account_pre_updating_profile_array', 'um_account_sanitize_data', 10, 1 );