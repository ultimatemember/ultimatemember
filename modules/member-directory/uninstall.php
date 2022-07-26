<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$install_class = UM()->call_class( 'umm\member_directory\Install' );

// Remove settings
$options = get_option( 'um_options', array() );
foreach ( $install_class->settings_defaults as $k => $v ) {
	unset( $options[ $k ] );
}
update_option( 'um_options', $options );

// remove predefined page option value
UM()->options()->remove( 'core_members' );

// delete registered widgets from options
delete_option( 'widget_um_search_widget' );

$cpt = UM()->call_class( 'umm\member_directory\includes\common\CPT' );
$cpt->create_post_types();

$um_directories = get_posts(
	array(
		'post_type'   => 'um_directory',
		'numberposts' => -1,
		'fields'      => 'ids',
	)
);

foreach ( $um_directories as $um_directory_post_id ) {
	wp_delete_post( $um_directory_post_id, 1 );
}

global $wpdb;

// Remove usermeta
$wpdb->query(
	"DELETE 
	FROM {$wpdb->usermeta} 
	WHERE meta_key = 'um_member_directory_data' OR meta_key = 'hide_in_members'"
);
