<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$install_class = UM()->call_class( 'umm\recaptcha\Install' );

// Remove settings
$options = get_option( 'um_options', array() );
foreach ( $install_class->settings_defaults as $k => $v ) {
	unset( $options[ $k ] );
}
update_option( 'um_options', $options );

global $wpdb;
$wpdb->query(
	"DELETE 
	FROM {$wpdb->postmeta} 
	WHERE meta_key LIKE '_um_login_g_recaptcha_status' OR 
		  meta_key LIKE '_um_login_g_recaptcha_score' OR
		  meta_key LIKE '_um_register_g_recaptcha_status' OR
		  meta_key LIKE '_um_register_g_recaptcha_score'"
);

$hidden_notices = get_option( 'um_hidden_admin_notices', array() );
if ( ! is_array( $hidden_notices ) ) {
	$hidden_notices = array();
}
unset( $hidden_notices[ array_search( 'um_recaptcha_notice', $hidden_notices, true ) ] );
update_option( 'um_hidden_admin_notices', $hidden_notices );

// Remove custom templates.
$templates_directories = array(
	trailingslashit( get_stylesheet_directory() ) . 'ultimate-member/recaptcha/',
	trailingslashit( get_template_directory() ) . 'ultimate-member/recaptcha/',
);
foreach ( $templates_directories as $templates_dir ) {
	if ( is_dir( $templates_dir ) ) {
		UM()->files()->remove_dir( $templates_dir );
	}
}
