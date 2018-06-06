<?php
function um_upgrade_styles2010() {
	um_maybe_unset_time_limit();

	include 'styles.php';
	wp_send_json_success( array( 'message' => __( 'Styles was upgraded successfully', 'ultimate-member' ) ) );
}


function um_upgrade_cache2010() {
	um_maybe_unset_time_limit();

	UM()->user()->remove_cache_all_users();

	update_option( 'um_last_version_upgrade', '2.0.10' );

	wp_send_json_success( array( 'message' => __( 'Users cache was cleared successfully', 'ultimate-member' ) ) );
}