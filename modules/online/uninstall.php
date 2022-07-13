<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$install_class = UM()->call_class( 'umm\online\Install' );

// Remove settings
$options = get_option( 'um_options', array() );
foreach ( $install_class->settings_defaults as $k => $v ) {
	unset( $options[ $k ] );
}
update_option( 'um_options', $options );

// options used in Online module directly
delete_option( 'um_online_users_last_updated' );
delete_option( 'um_online_users' );

// delete registered widgets from options
delete_option( 'widget_um_online_users' );
