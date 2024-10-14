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
	if ( ! um_is_myprofile() && um_is_core_page( 'user' ) && ! current_user_can( 'edit_users' ) ) {
		$account_status = UM()->common()->users()->get_status( $user_id );
		if ( 'approved' !== $account_status ) {
			um_redirect_home();
		}
	}
}
add_action( 'um_access_profile', 'um_access_profile' );
