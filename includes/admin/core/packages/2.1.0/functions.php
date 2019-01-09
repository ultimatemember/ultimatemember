<?php
function um_upgrade_conditionallogic210() {
	UM()->admin()->check_ajax_nonce();

	um_maybe_unset_time_limit();

	update_option( 'um_conditional_logic_upgrade', time() );

	update_option( 'um_last_version_upgrade', '2.1.0' );

	wp_send_json_success( array( 'message' => __( 'Conditional Logic upgraded', 'ultimate-member' ) ) );
}