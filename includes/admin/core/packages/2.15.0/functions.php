<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function um_upgrade_flush_rewrite_rules2150() {
	check_ajax_referer( 'um_run_package_2.15.0' );

	um_maybe_unset_time_limit();

	UM()->rewrite()->reset_rules();

	update_option( 'um_last_version_upgrade', '2.15.0' );

	wp_send_json_success( array( 'message' => __( 'Rewrite rules were flushed. Database has been updated successfully', 'ultimate-member' ) ) );
}
