<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


/**
 * Account secure fields
 * @param  array $fields
 * @param  string $tab_key
 * @return array
 * @uses  um_account_secure_fields
 */
function um_account_secure_fields( $fields, $tab_key ) {
	/**
	 * UM hook
	 *
	 * @type filter
	 * @title um_account_secure_fields__enabled
	 * @description Active account secure fields
	 * @input_vars
	 * [{"var":"$enabled","type":"string","desc":"Enable secure account fields"}]
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage
	 * <?php add_filter( 'um_account_secure_fields__enabled', 'function_name', 10, 1 ); ?>
	 * @example
	 * <?php
	 * add_filter( 'um_account_secure_fields__enabled', 'my_account_secure_fields', 10, 1 );
	 * function my_account_secure_fields( $enabled ) {
	 *     // your code here
	 *     return $enabled;
	 * }
	 * ?>
	 */
	$secure = apply_filters( 'um_account_secure_fields__enabled', true );

	if( ! $secure ) return $fields;

	if( isset( UM()->account()->register_fields ) && ! isset( UM()->account()->register_fields[ $tab_key ] ) ){
		UM()->account()->register_fields[ $tab_key ] = $fields;
	}

	return $fields;
}
add_filter( 'um_account_secure_fields', 'um_account_secure_fields', 10, 2 );


/**
 * Disables first and last name fields in account page
 * @param  array $fields
 * @return array
 * @uses  um_get_field__first_name, um_get_field__last_name
 */
function um_account_disable_name_fields( $fields ){
	if( ! UM()->options()->get( "account_name_disable" ) ) return $fields;

	if ( um_is_core_page("account") ) {
		$fields['disabled'] = 'disabled="disabled"';
	}

	return $fields;
}
add_filter( "um_get_field__first_name","um_account_disable_name_fields", 10 ,1 );
add_filter( "um_get_field__last_name","um_account_disable_name_fields", 10 ,1 );