<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Update Account Email Notification
 *
 * @param $user_id
 * @param $changed
 */
function um_account_updated_notification( $user_id, $changed ) {
	um_fetch_user( $user_id );
	UM()->mail()->send( um_user( 'user_email' ), 'changedaccount_email' );
}
add_action( 'um_after_user_account_updated', 'um_account_updated_notification', 20, 2 );
