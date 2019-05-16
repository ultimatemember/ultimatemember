<?php
/**
 * Uninstall UM
 *
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

if ( ! defined( 'um_path' ) )
	define( 'um_path', plugin_dir_path( __FILE__ ) );

if ( ! defined( 'um_url' ) )
	define( 'um_url', plugin_dir_url( __FILE__ ) );

if ( ! defined( 'um_plugin' ) )
	define( 'um_plugin', plugin_basename( __FILE__ ) );

//for delete Email options only for Core email notifications
remove_all_filters( 'um_email_notifications' );
//for delete only Core Theme Link pages
remove_all_filters( 'um_core_pages' );

require_once plugin_dir_path( __FILE__ ) . 'includes/class-functions.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-init.php';

$delete_options = UM()->options()->get( 'uninstall_on_delete' );
if ( ! empty( $delete_options ) ) {

	//remove uploads
	$upl_folder = UM()->files()->upload_basedir;
	UM()->files()->remove_dir( $upl_folder );
	
	//remove core pages
	foreach ( UM()->config()->core_pages as $page_key => $page_value ) {
		$page_id = UM()->options()->get( UM()->options()->get_core_page_id( $page_key ) );
		if ( ! empty( $page_id ) )
			wp_delete_post( $page_id, true );
	}

	//remove core settings
	$settings_defaults = UM()->config()->settings_defaults;
	foreach ( $settings_defaults as $k => $v ) {
		UM()->options()->remove( $k );
	}

	//delete UM Custom Post Types posts
	$um_posts = get_posts( array(
		'post_type'     => array(
			'um_form',
			'um_directory',
			'um_role'
		),
		'numberposts'   => -1
	) );

	foreach ( $um_posts as $um_post ){
		delete_option( 'um_existing_rows_' . $um_post->ID );
		delete_option( 'um_form_rowdata_' . $um_post->ID );
		wp_delete_post( $um_post->ID, 1 );
	}

	//remove user role meta
	$role_keys = get_option( 'um_roles' );
	if ( $role_keys ) {
		foreach ( $role_keys as $role_key ) {
			delete_option( 'um_role_' . $role_key . '_meta' );
		}
	}

	delete_option( 'um_first_activation_date' );
	delete_option( 'um_role_subscriber_meta' );
	delete_option( 'um_role_author_meta' );
	delete_option( 'um_role_contributor_meta' );
	delete_option( 'um_role_editor_meta' );
	delete_option( 'um_role_administrator_meta' );
	delete_option( 'um_roles' );
	delete_option( 'um_role_um_user_meta' );
	delete_option( 'um_fields' );
	delete_option( 'um_roles' );
	delete_option( 'um_options' );
	delete_option( 'um_version' );
	delete_option( 'um_is_installed' );
	delete_option( 'um_core_forms' );
	delete_option( 'um_core_directories' );
	delete_option( 'um_last_version_upgrade' );
	delete_option( 'um_first_setup_roles' );
	delete_option( 'um_hashed_passwords_fix' );
	delete_option( 'um_cached_users_queue' );
	delete_option( 'um_options-transients' );
	delete_option( 'um_cached_role_admin' );
	delete_option( 'um_cached_role_member' );
	delete_option( 'um_cache_fonticons' );
	delete_option( 'widget_um_search_widget' );
	delete_option( '__ultimatemember_sitekey' );

	//remove all users cache
	UM()->user()->remove_cache_all_users();

	global $wpdb;


	$wpdb->query(
		"DELETE 
        FROM {$wpdb->usermeta} 
        WHERE meta_key LIKE '_um%' OR 
              meta_key LIKE 'um%' OR 
              meta_key LIKE 'reviews%' OR 
              meta_key = 'submitted' OR 
              meta_key = 'account_status' OR 
              meta_key = 'password_rst_attempts' OR 
              meta_key = 'profile_photo' OR 
              meta_key = '_enable_new_follow' OR 
              meta_key = '_enable_new_friend' OR 
              meta_key = '_mylists' OR 
              meta_key = '_enable_new_pm' OR 
              meta_key = '_hidden_conversations' OR 
              meta_key = '_pm_blocked' OR 
              meta_key = '_notifications_prefs' OR 
              meta_key = '_profile_progress' OR 
              meta_key = '_completed' OR 
              meta_key = '_cannot_add_review' OR 
              meta_key = 'synced_profile_photo' OR 
              meta_key = 'full_name'"
	);
}