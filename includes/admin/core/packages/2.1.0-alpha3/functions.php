<?php if ( ! defined( 'ABSPATH' ) ) exit;


function um_upgrade_metadata210alpha3() {
	UM()->admin()->check_ajax_nonce();

	um_maybe_unset_time_limit();

	include 'metadata.php';

	update_option( 'um_last_version_upgrade', '2.1.0-alpha3' );

	wp_send_json_success( array( 'message' => __( 'Usermeta was upgraded successfully', 'ultimate-member' ) ) );
}