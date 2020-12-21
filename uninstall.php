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
			'um_role',
			'um_private_content',
			'um_mailchimp',
			'um_profile_tabs',
			'um_social_login',
			'um_review',
			'um_frontend_posting',
			'um_notice'
		),
		'post_status'   => array( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash' ),
		'numberposts'   => -1
	) );

	foreach ( $um_posts as $um_post ){
		delete_option( 'um_existing_rows_' . $um_post->ID );
		delete_option( 'um_form_rowdata_' . $um_post->ID );
		wp_delete_post( $um_post->ID, 1 );
	}

	//remove user role meta
	$role_keys = get_option( 'um_roles', array() );
	if ( $role_keys ) {
		foreach ( $role_keys as $role_key ) {
			delete_option( 'um_role_' . $role_key . '_meta' );
		}
	}

	delete_option( '__ultimatemember_sitekey' );
	delete_option( 'um_flush_rewrite_rules' );

	//remove all users cache
	UM()->user()->remove_cache_all_users();

	global $wpdb;

	//remove extensions core pages
	$ext_pages = $wpdb->get_results( "
		SELECT post_id
		FROM {$wpdb->postmeta}
		WHERE meta_key = '_um_core'
	", ARRAY_A );

	foreach ( $ext_pages as $page_id ) {
		wp_delete_post( $page_id['post_id'], 1 );
	}

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
              meta_key = 'full_name' OR
              meta_key = '_reviews' OR 
              meta_key = '_reviews_compound' OR 
              meta_key = '_reviews_total' OR 
              meta_key = '_reviews_avg'"
	);

	$wpdb->query(
		"DELETE 
        FROM {$wpdb->postmeta} 
        WHERE meta_key LIKE '_um%' OR 
              meta_key LIKE 'um%'"
	);

	//remove all tables from extensions
	$all_tables = "SHOW TABLES LIKE '{$wpdb->prefix}um\_%'";
	$results = $wpdb->get_results( $all_tables );
	if( $results ){
		foreach( $results as $index => $value ) {
			foreach( $value as $table_name ) {
				$um_groups_members = $wpdb->prefix.'um_groups_members';
				if( $table_name == $um_groups_members ){
					$wpdb->query( "
						DELETE posts, term_rel, pmeta, terms, tax, commetns
				        FROM {$wpdb->posts} posts
				        LEFT JOIN {$wpdb->term_relationships} term_rel
				        ON (posts.ID = term_rel.object_id)
				        LEFT JOIN {$wpdb->postmeta} pmeta
				        ON (posts.ID = pmeta.post_id)
				        LEFT JOIN {$wpdb->terms} terms
				        ON (term_rel.term_taxonomy_id = terms.term_id)
				        LEFT JOIN {$wpdb->term_taxonomy} tax
				        ON (term_rel.term_taxonomy_id = tax.term_taxonomy_id)
				        LEFT JOIN {$wpdb->comments} commetns
				        ON (commetns.comment_post_ID = posts.ID)
				        WHERE posts.post_type = 'um_groups' OR posts.post_type = 'um_groups_discussion'"
					);
				}
				$wpdb->query( "DROP TABLE IF EXISTS $table_name" );
			}
		}
	}

	//remove options from extensions
	//user photos
	$um_user_photos = get_posts( array(
		'post_type'     => array(
			'um_user_photos'
		),
		'post_status'   => array( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash' ),
		'numberposts'   => -1
	) );
	if( $um_user_photos ) {
		foreach ( $um_user_photos as $um_user_photo ) {
			$attachments = get_attached_media( 'image', $um_user_photo->ID );
			foreach ( $attachments as $attachment ) {
				wp_delete_attachment( $attachment->ID, 1 );
			}
			wp_delete_post( $um_user_photo->ID, 1 );
		}
	}

	//user notes
	$um_notes = get_posts( array(
		'post_type'     => array(
			'um_notes'
		),
		'post_status'   => array( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash' ),
		'numberposts'   => -1
	) );
	if( $um_notes ){
		foreach ( $um_notes as $um_note ){
			$attachments = get_attached_media( 'image', $um_note->ID );
			foreach ( $attachments as $attachment ){
				wp_delete_attachment( $attachment->ID, 1 );
			}
			wp_delete_post( $um_note->ID, 1 );
		}
	}

	//user tags
	$wpdb->query( "
		DELETE tax, terms
    	FROM {$wpdb->term_taxonomy} tax
        LEFT JOIN {$wpdb->terms} terms
        ON (tax.term_taxonomy_id = terms.term_id)
    	WHERE tax.taxonomy = 'um_user_tag'"
	);

	//mailchimp
	$mailchimp_log = UM()->files()->upload_basedir . 'mailchimp.log';
	if ( file_exists( $mailchimp_log ) ) {
		unlink( $mailchimp_log );
	}

	$um_options = $wpdb->get_results(
		"SELECT option_name
		FROM {$wpdb->options}
		WHERE option_name LIKE '_um%' OR
              option_name LIKE 'um_%' OR
              option_name LIKE 'widget_um%' OR
              option_name LIKE 'ultimatemember_%'" );

	foreach( $um_options as $um_option ) {
		delete_option( $um_option->option_name );
	}

	//social activity
	$um_activities = get_posts( array(
		'post_type'     => array(
			'um_activity'
		),
		'post_status'   => array( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash' ),
		'numberposts'   => -1
	) );
	foreach ( $um_activities as $um_activity ) {
		$image = get_post_meta( $um_activity->ID, '_photo', true );
		if( $image ){
			$user_id = get_post_meta( $um_activity->ID, '_user_id', true );
			$upload_dir = wp_upload_dir();
			$image_path = $upload_dir['basedir'] . '/ultimatemember/' . $user_id . '/' . $image;
			if ( file_exists( $image_path ) ) {
				unlink( $image_path );
			}
		}
		wp_delete_post( $um_activity->ID, 1 );
	}
}