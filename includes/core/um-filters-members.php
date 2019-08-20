<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


/**
 * WP API user search
 *
 * @param $query_args
 * @param $args
 *
 * @return mixed
 */
//function um_search_usernames_emails( $query_args, $args ) {
//	extract( $args );
//
//	$query = UM()->permalinks()->get_query_array();
//	$arr_columns = array();
//
//	foreach ( UM()->members()->core_search_fields as $key ) {
//		if ( ! empty( $query[ $key ]  ) ) {
//			$arr_columns[] = $key;
//			$query_args['search'] = '*' . $query[ $key ] .'*';
//		}
//	}
//
//	if ( ! empty( $arr_columns ) )
//		$query_args['search_columns'] = $arr_columns;
//
//	return $query_args;
//}
//add_filter( 'um_prepare_user_query_args', 'um_search_usernames_emails', 51, 2 );


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

	if ( UM()->options()->get( 'account_hide_in_directory' ) ) {
		if ( ! UM()->roles()->um_user_can( 'can_access_private_profile' ) ) {
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
	}

	$roles = um_user( 'can_view_roles' );
	if ( UM()->roles()->um_user_can( 'can_view_all' ) && ! empty( $roles ) ) {

		$roles = maybe_unserialize( $roles );

		if ( ! empty( $roles ) ) {
			if ( ! empty( $query_args['role__in'] ) ) {
				$roles_intersect = array_intersect( $query_args['role__in'], $roles );
				if( ! empty( $roles_intersect ) ){
					$query_args['role__in'] = $roles_intersect;
				}
			} else {
				$query_args['role__in'] = $roles;
			}
		}		

	}

	return $query_args;
}
//add_filter( 'um_prepare_user_query_args', 'um_remove_special_users_from_list', 99, 2 );


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
			'key'       => 'cover_photo',
			'value'     => '',
			'compare'   => '!='
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
	}

	// sort members by
	$query_args['order'] = 'ASC';

	if ( isset( $sortby ) ) {

		if ( $sortby == 'other' && $sortby_custom ) {

			$query_args['meta_key'] = $sortby_custom;
			$query_args['orderby'] = 'meta_value, display_name';

		} elseif ( 'display_name' == $sortby ) {

			$display_name = UM()->options()->get( 'display_name' );
			if ( $display_name == 'username' ) {
				$query_args['orderby'] = 'user_login';
				$order = 'ASC';
			} else {
				$query_args['meta_query'][] = array(
					'relation' => 'OR',
					'full_name' => array(
						'key'       => 'full_name',
						'compare'   => 'EXISTS'
					),
					array(
						'key'       => 'full_name',
						'compare'   => 'NOT EXISTS'
					)
				);

				$query_args['orderby'] = 'full_name, display_name';
				$order = 'ASC';
			}

		} elseif ( in_array( $sortby, array( 'last_name', 'first_name' ) ) ) {

			$query_args['meta_key'] = $sortby;
			$query_args['orderby'] = 'meta_value';

		} else {

			if ( strstr( $sortby, '_desc' ) ) {
				$sortby = str_replace( '_desc', '', $sortby );
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
		$query_args = apply_filters( 'um_modify_sortby_parameter', $query_args, $sortby );

	}

	return $query_args;
}
//add_filter( 'um_prepare_user_query_args', 'um_prepare_user_query_args', 10, 2 );