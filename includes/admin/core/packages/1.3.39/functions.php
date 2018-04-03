<?php
function um_upgrade_usermetaquery1339() {
	include 'usermeta_query.php';

	update_option( 'um_last_version_upgrade', '1.3.39' );

	wp_send_json_success( array( 'message' => 'Usermeta was upgraded successfully' ) );
}