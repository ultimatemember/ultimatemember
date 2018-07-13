<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


/**
 * WP API user search
 *
 * @param $query_args
 * @param $args
 *
 * @return mixed
 */
function um_search_usernames_emails( $query_args, $args ) {
	extract( $args );

	$query = UM()->permalinks()->get_query_array();
	$arr_columns = array();

	foreach ( UM()->members()->core_search_fields as $key ) {
		if ( ! empty( $query[ $key ]  ) ) {
			$arr_columns[] = $key;
			$query_args['search'] = '*' . $query[ $key ] .'*';
		}
	}

	if ( ! empty( $arr_columns ) )
		$query_args['search_columns'] = $arr_columns;

	return $query_args;
}
add_filter( 'um_prepare_user_query_args', 'um_search_usernames_emails', 51, 2 );


/**
 * Remove users we do not need to show in directory
 *
 * @param $query_args
 * @param $args
 *
 * @return mixed
 */
function um_remove_special_users_from_list( $query_args, $args ) {
	extract( $args );

	$query_args['meta_query']['relation'] = 'AND';

	if ( ! UM()->roles()->um_user_can( 'can_edit_everyone' )  ) {

		$query_args['meta_query'][] = array(
			'key' => 'account_status',
			'value' => 'approved',
			'compare' => '='
		);

	}

	if ( ! UM()->roles()->um_user_can( 'can_edit_everyone' ) || UM()->options()->get( 'account_hide_in_directory' ) ) {
		$query_args['meta_query'][] = array(
			"relation"	=> "OR",
			array(
				'key' => 'hide_in_members',
				'value' => '',
				'compare' => 'NOT EXISTS'
			),
			array(
				"relation"	=> "AND",
				array(
					'key' => 'hide_in_members',
					'value' => __('Yes','ultimate-member'),
					'compare' => 'NOT LIKE'
				),
				array(
					'key' => 'hide_in_members',
					'value' => 'Yes',
					'compare' => 'NOT LIKE'
				),
			),
		);
	}

	$roles = um_user( 'can_view_roles' );
	if ( UM()->roles()->um_user_can( 'can_view_all' ) && ! empty( $roles ) ) {

		$roles = maybe_unserialize( $roles );

		if ( ! empty( $roles ) ) {
			if ( ! empty( $query_args['role__in'] ) ) {
				$query_args['role__in'] = array_intersect( $query_args['role__in'], $roles );
			} else {
				$query_args['role__in'] = $roles;
			}
		}

	}

	return $query_args;
}
add_filter( 'um_prepare_user_query_args', 'um_remove_special_users_from_list', 99, 2 );


/**
 * Adds search parameters
 *
 * @param $query_args
 * @param $args
 *
 * @return mixed|void
 */
function um_add_search_to_query( $query_args, $args ){
	extract( $args );

	if ( empty( $_POST['general_search'] ) ) {
		return $query_args;
	}

	$field_query = array(
		array(
			'value'     => trim( $_POST['general_search'] ),
			'compare'   => '=',
		),
		array(
			'value'     => trim( $_POST['general_search'] ),
			'compare'   => 'LIKE',
		),
		array(
			'value'     => trim( serialize( strval( $_POST['general_search'] ) ) ),
			'compare'   => 'LIKE',
		),
		'relation' => 'OR',
	);

	$query_args['meta_query'][] = $field_query;

	/**
	 * UM hook
	 *
	 * @type filter
	 * @title um_query_args_filter
	 * @description Change query for search at Members Directory
	 * @input_vars
	 * [{"var":"$query_args","type":"array","desc":"Query Arguments"}]
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage
	 * <?php add_filter( 'um_query_args_filter', 'function_name', 10, 1 ); ?>
	 * @example
	 * <?php
	 * add_filter( 'um_query_args_filter', 'my_query_args_filter', 10, 1 );
	 * function my_query_args_filter( $query_args ) {
	 *     // your code here
	 *     return $query_args;
	 * }
	 * ?>
	 */
	$query_args = apply_filters( 'um_query_args_filter', $query_args );

	if ( count( $query_args['meta_query'] ) == 1 ) {
		unset( $query_args['meta_query'] );
	}

	return $query_args;
}
add_filter( 'um_prepare_user_query_args', 'um_add_search_to_query', 50, 2 );


/**
 * Adds main parameters
 *
 * @param $query_args
 * @param $args
 *
 * @return mixed
 */
function um_prepare_user_query_args( $query_args, $args ) {
	extract( $args );

	$query_args['fields'] = 'ID';

	$query_args['number'] = 0;

	$query_args['meta_query']['relation'] = 'AND';

	// must have a profile photo
	if ( $has_profile_photo == 1 ) {
		if ( UM()->options()->get( 'use_gravatars' ) ) {
			$query_args['meta_query'][] = array(
				'relation' => 'OR',
				array(
					'key' => 'synced_profile_photo', // addons
					'value' => '',
					'compare' => '!='
				),
				array(
					'key' => 'profile_photo', // from upload form
					'value' => '',
					'compare' => '!='
				),
				array(
					'key' => 'synced_gravatar_hashed_id', //  gravatar
					'value' => '',
					'compare' => '!='
				)

			);
		} else {
			$query_args['meta_query'][] = array(
				'relation' => 'OR',
				array(
					'key' => 'synced_profile_photo', // addons
					'value' => '',
					'compare' => '!='
				),
				array(
					'key' => 'profile_photo', // from upload form
					'value' => '',
					'compare' => '!='
				)
			);
		}
	}

	// must have a cover photo
	if ( $has_cover_photo == 1 ) {
		$query_args['meta_query'][] = array(
			'key' => 'cover_photo',
			'value' => '',
			'compare' => '!='
		);
	}


	// show specific usernames
	if ( isset( $show_these_users ) && $show_these_users && is_array( $show_these_users ) ) {
		foreach ( $show_these_users as $username ) {
			$users_array[] = username_exists( $username );
		}
		$query_args['include'] = $users_array;
	}

	// add roles to appear in directory
	if ( ! empty( $roles ) ) {

		//since WP4.4 use 'role__in' argument
		$query_args['role__in'] = $roles;

		/*$query_args['meta_query'][] = array(
				'key' => 'role',
				'value' => $roles,
				'compare' => 'IN'
			);*/

	}

	// sort members by
	$query_args['order'] = 'ASC';
	$sortby = ! empty( $_POST['sorting'] ) ? $_POST['sorting'] : $sortby;

	if ( isset( $sortby ) ) {


		if ( $sortby == 'other' && $sortby_custom ) {

			$query_args['meta_key'] = $sortby_custom;
			$query_args['orderby'] = 'meta_value, display_name';

		} else if ( in_array( $sortby, array( 'last_name', 'first_name' ) ) ) {

			$query_args['meta_key'] = $sortby;
			$query_args['orderby'] = 'meta_value';

		} else {

			if ( strstr( $sortby, '_desc' ) ) {
				$sortby = str_replace('_desc','',$sortby);
				$order = 'DESC';
			}

			if ( strstr( $sortby, '_asc' ) ) {
				$sortby = str_replace('_asc','',$sortby);
				$order = 'ASC';
			}
				
			$query_args['orderby'] = $sortby;

		}

		if ( isset( $order ) ) {
			$query_args['order'] = $order;
		}

		/**
		 * UM hook
		 *
		 * @type filter
		 * @title um_modify_sortby_parameter
		 * @description Change query sort by attributes for search at Members Directory
		 * @input_vars
		 * [{"var":"$query_args","type":"array","desc":"Query Arguments"},
		 * {"var":"$sortby","type":"string","desc":"Sort by"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage
		 * <?php add_filter( 'um_modify_sortby_parameter', 'function_name', 10, 2 ); ?>
		 * @example
		 * <?php
		 * add_filter( 'um_modify_sortby_parameter', 'my_modify_sortby_parameter', 10, 2 );
		 * function my_modify_sortby_parameter( $query_args, $sortby ) {
		 *     // your code here
		 *     return $query_args;
		 * }
		 * ?>
		 */
		$query_args = apply_filters('um_modify_sortby_parameter', $query_args, $sortby);

	}

	return $query_args;
}
add_filter( 'um_prepare_user_query_args', 'um_prepare_user_query_args', 10, 2 );


/**
 * Sorting by last login date
 *
 * @param $query_args
 * @param $sortby
 *
 * @return mixed
 */
function um_sortby_last_login( $query_args, $sortby ) {
	if ( $sortby == 'last_login' ) {
		$query_args['orderby'] = 'meta_value_num';
		$query_args['order'] = 'desc';
		$query_args['meta_key'] = '_um_last_login';
	}
	return $query_args;
}
add_filter( 'um_modify_sortby_parameter', 'um_sortby_last_login', 100, 2 );


/**
 * Sorting random
 *
 * @param $query
 *
 * @return mixed
 */
function um_modify_sortby_randomly( $query ) {
	if( um_is_session_started() === false ){
		@session_start();
	}

	// Reset seed on load of initial
	if( ! isset( $_REQUEST['members_page'] ) || $_REQUEST['members_page'] == 0 ||  $_REQUEST['members_page'] == 1 ) {
		if( isset( $_SESSION['seed'] ) ) {
			unset( $_SESSION['seed'] );
		}
	}

	// Get seed from session variable if it exists
	$seed = false;
	if( isset( $_SESSION['seed'] ) ) {
		$seed = $_SESSION['seed'];
	}
        
	// Set new seed if none exists
	if ( ! $seed ) {
		$seed = rand();
		$_SESSION['seed'] = $seed;
	}

	if($query->query_vars["orderby"] == 'random') {
		$query->query_orderby = 'ORDER by RAND('. $seed.')';
	}

	return $query;
}
add_filter( 'pre_user_query','um_modify_sortby_randomly' );


/**
 * Retrieves search filter options from a callback
 *
 * @param  $atts array
 * @return array
 */
function um_search_select_fields( $atts ) {

	if( isset( $atts['custom_dropdown_options_source'] ) && ! empty( $atts['custom_dropdown_options_source'] ) ){
		$atts['custom'] = true;
		$atts['options'] = UM()->fields()->get_options_from_callback( $atts, $atts['type'] );
	}

	if( isset( $atts['label'] ) ){
		$atts['label'] = strip_tags( $atts['label'] );
	}

	return $atts;
}
add_filter( 'um_search_select_fields', 'um_search_select_fields' );


/**
 * @param $field_query
 * @param $query
 * @return mixed|void
 */
function um_add_slider_filter_to_query( $field_query, $query ) {

	$from_date = date( 'Y-m-d', mktime( 0,0,0, date('m'), date('d'), date('Y', time() - $query['birth_date'][0]*YEAR_IN_SECONDS ) ) );
	$to_date = date( 'Y-m-d', mktime( 0,0,0, date('m'), date('d'), date('Y', time() - $query['birth_date'][1]*YEAR_IN_SECONDS ) ) );

	$field_query = array(
		array(
			'key'       => 'birth_date',
			'value'     => array( $to_date, $from_date ),
			'compare'   => 'BETWEEN',
			'type'      => 'DATE',
		),
		'relation' => 'OR'
	);

	return $field_query;
}
add_filter( 'um_query_args_birth_date__filter', 'um_add_slider_filter_to_query', 98, 2 );



/**
 * @param $query_args
 * @param $args
 * @return mixed|void
 */
function um_add_filter_to_query( $query_args, $args ) {
	extract( $args );

	$query = $_POST;

	unset( $query['sorting'] );
	unset( $query['page'] );
	unset( $query['args'] );

	if ( $query && is_array( $query ) ) {
		foreach ( $query as $field => $value ) {

			if ( in_array( $field, array( 'members_page', 'general_search' ) ) ) continue;

			if ( $value && $field != 'um_search' && $field != 'page_id' ) {

				if ( strstr( $field, 'role_' ) )
					$field = 'role';

				if ( ! in_array( $field, UM()->members()->core_search_fields ) ) {

					if ( 'role' == $field ) {
						$query_args['role__in'] = trim( $value );
					} else {

						if ( is_array( $value ) ) {
							$field_query = array( 'relation' => 'OR' );

							foreach ( $value as $single_val ) {
								$serialize_value = serialize( strval( $single_val ) );

								$field_query = array_merge( $field_query, array(
									array(
										'key' => $field,
										'value' => trim( $single_val ),
										'compare' => '=',
									),
									array(
										'key' => $field,
										'value' => trim( $single_val ),
										'compare' => 'LIKE',
									),
									array(
										'key' => $field,
										'value' => trim( $serialize_value ),
										'compare' => 'LIKE',
									),
								) );
							}
						} else {
							$serialize_value = serialize( strval( $value ) );

							$field_query = array(
								array(
									'key' => $field,
									'value' => trim( $value ),
									'compare' => '=',
								),
								array(
									'key' => $field,
									'value' => trim( $value ),
									'compare' => 'LIKE',
								),
								array(
									'key' => $field,
									'value' => trim( $serialize_value ),
									'compare' => 'LIKE',
								),
								'relation' => 'OR',
							);
						}

						$field_query = apply_filters( "um_query_args_{$field}__filter", $field_query );
						$query_args['meta_query'][] = $field_query;
					}

				}

			}

		}
	}


	// allow filtering
	$query_args = apply_filters( 'um_query_args_filter', $query_args );

	if ( count( $query_args['meta_query'] ) == 1 )
		unset( $query_args['meta_query'] );

	return $query_args;

}
add_filter( 'um_prepare_user_query_args', 'um_add_filter_to_query', 50, 2 );
