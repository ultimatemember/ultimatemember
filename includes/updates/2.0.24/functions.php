<?php
function um_upgrade_tempfolder2024() {
	UM()->admin()->check_ajax_nonce();

	um_maybe_unset_time_limit();

	UM()->files()->remove_dir( UM()->files()->upload_temp );

	update_option( 'um_last_version_upgrade', '2.0.24' );

	wp_send_json_success( array( 'message' => __( 'Temporary dir was purged successfully', 'ultimate-member' ) ) );
}