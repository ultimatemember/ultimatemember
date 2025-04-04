<?php if ( ! defined( 'ABSPATH' ) ) exit;


function um_upgrade_fields2044() {
	UM()->admin()->check_ajax_nonce();

	um_maybe_unset_time_limit();

	include 'metafields.php';

	update_option( 'um_last_version_upgrade', '2.0.44' );

	wp_send_json_success( array( 'message' => __( 'Field was upgraded successfully', 'ultimate-member' ) ) );
}