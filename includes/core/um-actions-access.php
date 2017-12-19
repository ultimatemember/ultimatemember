<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Profile Access
 *
 * @param int $user_id
 */
function um_access_profile( $user_id ) {

	if ( ! um_is_myprofile() && um_is_core_page( 'user' ) && ! current_user_can( 'edit_users' ) ) {

		um_fetch_user( $user_id );

		if ( ! in_array( um_user( 'account_status' ), array( 'approved' ) ) ) {
			um_redirect_home();
		}

		um_reset_user();

	}
}
add_action( 'um_access_profile', 'um_access_profile' );