<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Before notifications account tab content
 *
 * @param array $args
 *
 * @throws Exception
 */
function um_before_account_notifications( $args = array() ) {
	$output = UM()->account()->get_tab_fields( 'notifications', $args );
	if ( substr_count( $output, '_enable_new_' ) ) { ?>

		<p><?php _e( 'Select what email notifications you want to receive', 'ultimate-member' ); ?></p>

	<?php }
}
//add_action( 'um_before_account_notifications', 'um_before_account_notifications' );


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


/**
 * Disable WP native email notification when change email on user account
 *
 * @param $user_id
 * @param $changed
 */
function um_disable_native_email_notificatiion( $changed, $user_id ) {
	add_filter( 'send_email_change_email', '__return_false' );
}
add_action( 'um_account_pre_update_profile', 'um_disable_native_email_notificatiion', 10, 2 );
