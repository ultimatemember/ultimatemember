<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


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