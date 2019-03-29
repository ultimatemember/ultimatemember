<?php
function um_upgrade_fields2043() {
	UM()->admin()->check_ajax_nonce();

	um_maybe_unset_time_limit();

	include 'metafields.php';
	wp_send_json_success( array( 'message' => __( 'Field was upgraded successfully', 'ultimate-member' ) ) );
}