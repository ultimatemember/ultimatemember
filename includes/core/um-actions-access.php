<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Profile Access
 *
 * @param int $user_id
 */
function um_access_profile( $user_id ) {
	if ( ! um_is_core_page( 'user' ) ) {
		return;
	}

	$account_status = UM()->common()->users()->get_status( $user_id );
	if ( 'approved' === $account_status ) {
		return;
	}

	if ( ! UM()->common()->users()->can_current_user_edit_user( $user_id ) ) {
		um_redirect_home();
	}
}
add_action( 'um_access_profile', 'um_access_profile' );
