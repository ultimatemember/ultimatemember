<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


	/**
	 * Account secure fields
	 * @param  array $fields 
	 * @param  string $tab_key 
	 * @return array       
	 * @uses  um_account_secure_fields
	 */
	add_filter('um_account_secure_fields','um_account_secure_fields', 10, 2);
	function um_account_secure_fields( $fields, $tab_key ){
		$secure = apply_filters('um_account_secure_fields__enabled', true );

		if( ! $secure ) return $fields;

		
		if( isset( UM()->account()->register_fields ) && ! isset( UM()->account()->register_fields[ $tab_key ] ) ){
			UM()->account()->register_fields[ $tab_key ] = $fields;
		}

		

		return $fields;
	}

	/**
	 * Disables first and last name fields in account page
	 * @param  array $fields 
	 * @return array     
	 * @uses  um_get_field__first_name, um_get_field__last_name  
	 */
	add_filter("um_get_field__first_name","um_account_disable_name_fields", 10 ,1 );
	add_filter("um_get_field__last_name","um_account_disable_name_fields", 10 ,1 );
	function um_account_disable_name_fields( $fields ){
		if( ! UM()->options()->get( "account_name_disable" ) ) return $fields;

		if( um_is_core_page("account") ){
			$fields['disabled'] = 'disabled="disabled"';
		}

		return $fields;
	}