<?php
namespace umm\member_directory\includes\frontend;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Account
 *
 * @package umm\member_directory\includes\frontend
 */
class Account {


	/**
	 * Account constructor.
	 */
	function __construct() {
		add_filter( 'um_account_pre_updating_profile_array', array( 'on_update_account' ), 10, 2 );
	}


	function on_update_account( $changes, $user_id ) {
		if ( isset( $changes['hide_in_members'] ) ) {
			if ( UM()->module( 'member-directory' )->get_hide_in_members_default() ) {
				if ( __( 'Yes', 'ultimate-member' ) === $changes['hide_in_members'] || 'Yes' === $changes['hide_in_members'] || array_intersect( array( 'Yes', __( 'Yes', 'ultimate-member' ) ), $changes['hide_in_members'] ) ) {
					delete_user_meta( $user_id, 'hide_in_members' );
					unset( $changes['hide_in_members'] );
				}
			} else {
				if ( __( 'No', 'ultimate-member' ) === $changes['hide_in_members'] || 'No' === $changes['hide_in_members'] || array_intersect( array( 'No', __( 'No', 'ultimate-member' ) ), $changes['hide_in_members'] ) ) {
					delete_user_meta( $user_id, 'hide_in_members' );
					unset( $changes['hide_in_members'] );
				}
			}
		}

		return $changes;
	}

}
