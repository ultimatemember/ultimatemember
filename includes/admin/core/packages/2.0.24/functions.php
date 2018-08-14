<?php
function um_upgrade_privacy2024() {
	um_maybe_unset_time_limit();

	global $wpdb;

	$wpdb->query( $wpdb->prepare(
		"UPDATE {$wpdb->usermeta} 
		SET meta_value = CASE 
			WHEN meta_value = %s THEN 'Everyone' 
			WHEN meta_value = %s THEN 'Only me' 
			WHEN meta_value = %s THEN 'followed' 
			WHEN meta_value = %s THEN 'follower' 
			WHEN meta_value = %s THEN 'friends' 
		END
		WHERE meta_key = 'profile_privacy'",
		__( 'Everyone', 'ultimate-member' ),
		__( 'Only me', 'ultimate-member' ),
		__( 'Only people I follow can view my profile', 'um-followers' ),
		__( 'Followers', 'um-followers' ),
		__( 'Friends only', 'um-friends' )
	) );

	UM()->user()->remove_cache_all_users();

	update_option( 'um_last_version_upgrade', '2.0.24' );

	wp_send_json_success( array( 'message' => __( 'Privacy Settings was upgraded successfully', 'ultimate-member' ) ) );
}