<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


/**
 * Main admin user actions
 *
 * @param array $actions
 * @param int $user_id
 *
 * @return null
 */
function um_admin_user_actions_hook( $actions, $user_id ) {
	um_fetch_user( $user_id );

	//if ( UM()->roles()->um_current_user_can( 'edit', $user_id ) ) {
	if ( current_user_can( 'manage_options' ) ) {

		if ( um_user( 'account_status' ) == 'awaiting_admin_review' ) {
			$actions['um_approve_membership'] = array( 'label' => __( 'Approve Membership', 'ultimate-member' ) );
			$actions['um_reject_membership'] = array( 'label' => __( 'Reject Membership', 'ultimate-member' ) );
		}

		if ( um_user( 'account_status' ) == 'rejected' ) {
			$actions['um_approve_membership'] = array( 'label' => __( 'Approve Membership', 'ultimate-member' ) );
		}

		if ( um_user( 'account_status' ) == 'approved' ) {
			$actions['um_put_as_pending'] = array( 'label' => __( 'Put as Pending Review', 'ultimate-member' ) );
		}

		if ( um_user( 'account_status' ) == 'awaiting_email_confirmation' ) {
			$actions['um_resend_activation'] = array( 'label' => __( 'Resend Activation E-mail', 'ultimate-member' ) );
		}

		if ( um_user( 'account_status' ) != 'inactive' ) {
			$actions['um_deactivate'] = array( 'label' => __( 'Deactivate this account', 'ultimate-member' ) );
		}

		if ( um_user( 'account_status' ) == 'inactive' ) {
			$actions['um_reenable'] = array( 'label' => __( 'Reactivate this account', 'ultimate-member' ) );
		}

	}

	if ( UM()->roles()->um_current_user_can( 'delete', $user_id ) ) {
		$actions['um_delete'] = array( 'label' => __( 'Delete this user', 'ultimate-member' ) );
	}

	if ( current_user_can( 'delete_users' ) ) {
		$actions['um_switch_user'] = array( 'label' => __( 'Login as this user', 'ultimate-member' ) );
	}

	return $actions;
}
add_filter( 'um_admin_user_actions_hook', 'um_admin_user_actions_hook', 10, 2 );


/**
 * Filter user basename
 * @param  string $value
 * @return string
 * @hook_filter: um_clean_user_basename_filter
 */
function um_clean_user_basename_filter( $value, $raw ){
	$permalink_base = UM()->options()->get( 'permalink_base' );

	$user_query = new WP_User_Query(
		array(
			'meta_query'    => array(
				'relation'  => 'AND',
				array(
					'key'     => 'um_user_profile_url_slug_'.$permalink_base,
					'value'   => $raw,
					'compare' => '='
				)
			),
			'fields' => array('ID')
		)

	);

	if( $user_query->total_users > 0 ){

		$result = current( $user_query->get_results() );
		$slugname =  '';

		if( isset( $result->ID ) ){
			$slugname =  get_user_meta( $result->ID, 'um_user_profile_url_slug_'.$permalink_base, true );
			$value = $slugname;
		}
	}

	/**
	 * UM hook
	 *
	 * @type filter
	 * @title um_permalink_base_before_filter
	 * @description Base permalink before
	 * @input_vars
	 * [{"var":"$permalink","type":"string","desc":"User Permalink"}]
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage
	 * <?php add_filter( 'um_permalink_base_before_filter', 'function_name', 10, 1 ); ?>
	 * @example
	 * <?php
	 * add_filter( 'um_permalink_base_before_filter', 'my_permalink_base_before', 10, 1 );
	 * function my_permalink_base_before( $permalink ) {
	 *     // your code here
	 *     return $permalink;
	 * }
	 * ?>
	 */
	$value = apply_filters( "um_permalink_base_before_filter", $value );
	$raw_value = $value;

	switch( $permalink_base ) {
		case 'name':


			if( ! empty( $value ) && strrpos( $value ,"_") > -1 ){
				$value = str_replace( '_', '. ', $value );
			}

			if( ! empty( $value ) && strrpos( $value ,"_") > -1 ){
				$value = str_replace( '_', '-', $value );
			}

			if( ! empty( $value ) && strrpos( $value ,".") > -1 && strrpos( $raw_value ,"_" ) <= -1 ){
				$value = str_replace( '.', ' ', $value );
			}

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_permalink_base_after_filter_name
			 * @description Base permalink after if permalink is username
			 * @input_vars
			 * [{"var":"$permalink","type":"string","desc":"User Permalink"},
			 * {"var":"$raw_permalink","type":"string","desc":"RAW User Permalink"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_permalink_base_after_filter_name', 'function_name', 10, 2 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_permalink_base_after_filter_name', 'my_permalink_base_after_filter_name', 10, 2 );
			 * function my_permalink_base_after_filter_name( $permalink, $raw_permalink ) {
			 *     // your code here
			 *     return $permalink;
			 * }
			 * ?>
			 */
			$value = apply_filters("um_permalink_base_after_filter_name", $value, $raw_value );

			break;

		case 'name_dash':

			if( ! empty( $value ) && strrpos( $value ,"-") > -1 ){
				$value = str_replace( '-', ' ', $value );
			}

			if( ! empty( $value ) && strrpos( $value ,"_") > -1 ){
				$value = str_replace( '_', '-', $value );
			}

			// Checks if last name has a dash
			if( ! empty( $value ) && strrpos( $value ,"_") > -1 ){
				$value = str_replace( '_', '-', $value );
			}

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_permalink_base_after_filter_name_dash
			 * @description Base permalink after if permalink is first name - last name
			 * @input_vars
			 * [{"var":"$permalink","type":"string","desc":"User Permalink"},
			 * {"var":"$raw_permalink","type":"string","desc":"RAW User Permalink"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_permalink_base_after_filter_name_dash', 'function_name', 10, 2 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_permalink_base_after_filter_name_dash', 'my_permalink_base_after_filter_name_dash', 10, 2 );
			 * function my_permalink_base_after_filter_name_dash( $permalink, $raw_permalink ) {
			 *     // your code here
			 *     return $permalink;
			 * }
			 * ?>
			 */
			$value = apply_filters("um_permalink_base_after_filter_name_dash", $value, $raw_value );

			break;


		case 'name_plus':

			if( ! empty( $value ) && strrpos( $value ,"+") > -1 ){
				$value = str_replace( '+', ' ', $value );
			}

			if( ! empty( $value ) && strrpos( $value ,"_") > -1 ){
				$value = str_replace( '_', '+', $value );
			}

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_permalink_base_after_filter_name_plus
			 * @description Base permalink after if permalink is first name + last name
			 * @input_vars
			 * [{"var":"$permalink","type":"string","desc":"User Permalink"},
			 * {"var":"$raw_permalink","type":"string","desc":"RAW User Permalink"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_permalink_base_after_filter_name_plus', 'function_name', 10, 2 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_permalink_base_after_filter_name_plus', 'my_permalink_base_after_filter_name_plus', 10, 2 );
			 * function my_permalink_base_after_filter_name_plus( $permalink, $raw_permalink ) {
			 *     // your code here
			 *     return $permalink;
			 * }
			 * ?>
			 */
			$value = apply_filters("um_permalink_base_after_filter_name_plus", $value, $raw_value );

			break;

		default:

			// Checks if last name has a dash
			if( ! empty( $value ) && strrpos( $value ,"_") > -1 && substr( $value , "_") == 1 ){
				$value = str_replace( '_', '-', $value );
			}

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_permalink_base_after_filter
			 * @description Base permalink after for default permalink
			 * @input_vars
			 * [{"var":"$permalink","type":"string","desc":"User Permalink"},
			 * {"var":"$raw_permalink","type":"string","desc":"RAW User Permalink"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_permalink_base_after_filter', 'function_name', 10, 2 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_permalink_base_after_filter', 'my_permalink_base_after', 10, 2 );
			 * function my_permalink_base_after( $permalink, $raw_permalink ) {
			 *     // your code here
			 *     return $permalink;
			 * }
			 * ?>
			 */
			$value = apply_filters("um_permalink_base_after_filter", $value, $raw_value );

			break;
	}

	return $value;

}
add_filter( 'um_clean_user_basename_filter', 'um_clean_user_basename_filter', 2, 10 );


/**
 * Filter before update profile to force utf8 strings
 *
 * @param  array $changes
 * @param int $user_id
 *
 * @return array
 */
function um_before_update_profile( $changes, $user_id ) {
	if ( ! UM()->options()->get( 'um_force_utf8_strings' ) ) {
		return $changes;
	}

	foreach ( $changes as $key => $value ) {
		$changes[ $key ] = um_force_utf8_string( $value );
	}

	return $changes;
}
add_filter( 'um_before_update_profile','um_before_update_profile', 10, 2 );