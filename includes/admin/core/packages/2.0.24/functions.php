<?php
function um_upgrade_tempfolder2024() {
	UM()->admin()->check_ajax_nonce();

	um_maybe_unset_time_limit();

	if ( UM()->is_new_ui() ) {
		$temp_folder = UM()->common()->filesystem()->get_tempdir();
	} else {
		$temp_folder = UM()->files()->upload_temp;
	}

	UM()->common()->filesystem()::remove_dir( $temp_folder );

	update_option( 'um_last_version_upgrade', '2.0.24' );

	wp_send_json_success( array( 'message' => __( 'Temporary dir was purged successfully', 'ultimate-member' ) ) );
}
