<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$install_class = UM()->call_class( 'umm\forumwp\Install' );

// Remove settings
$options = get_option( 'um_options', array() );
foreach ( $install_class->settings_defaults as $k => $v ) {
	unset( $options[ $k ] );
}
update_option( 'um_options', $options );


// Remove postmeta
global $wpdb;
$wpdb->query(
	"DELETE 
	FROM {$wpdb->postmeta} 
	WHERE meta_key = '_um_forumwp_can_topic' OR 
	      meta_key = '_um_forumwp_can_reply' OR
	      meta_key = 'fmwp_um_notifications_need_mention' OR
	      meta_key = 'fmwp_um_notifications_mentioned' OR
	      meta_key = 'fmwp_um_notifications_subscribers_need_notified' OR
	      meta_key = 'fmwp_um_notifications_subscribers_notified'"
);

// Remove notifications if exists
$table_exists = $wpdb->query("SHOW TABLES LIKE '{$wpdb->prefix}um_notifications'" );
if (  ! empty( $table_exists ) ) {
	$wpdb->query(
		"DELETE
		FROM {$wpdb->prefix}um_notifications
		WHERE type = 'fmwp_mention' OR 
		      type = 'fmwp_new_reply' OR 
		      type = 'fmwp_new_topic'"
	);
}

// Remove UM Role metadata
$all_roles = UM()->roles()->get_roles();
$role_keys = array_keys( $all_roles );
foreach ( $role_keys as $role_key ) {
	if ( strpos( $role_key, 'um_' ) === 0 ) {
		$role_key = substr( $role_key, 3 );
	}
	$role_meta = get_option( "um_role_{$role_key}_meta", array() );

	$need_upgrade = false;
	$remove_keys  = array(
		'_um_disable_forumwp_tab',
		'_um_disable_create_forumwp_topics',
		'_um_lock_create_forumwp_topics_notice',
		'_um_disable_create_forumwp_replies',
		'_um_lock_create_forumwp_replies_notice',
	);

	foreach ( $remove_keys as $remove_key ) {
		if ( array_key_exists( $remove_key, $role_meta ) ) {
			$need_upgrade = true;
			unset( $role_meta[ $remove_key ] );
		}
	}

	if ( $need_upgrade ) {
		update_option( "um_role_{$role_key}_meta", $role_meta );
	}
}

// Remove UM Role metadata for all ForumWP roles
$fmwp_roles = array(
	'fmwp_manager',
	'fmwp_moderator',
	'fmwp_participant',
	'fmwp_spectator',
);
foreach ( $fmwp_roles as $fmwp_role ) {
	delete_option( "um_role_{$fmwp_role}_meta" );
}

// Remove Profile Form profile tabs from ForumWP module
$profile_tabs = array(
	'forumwp',
);
$profile_forms = get_posts(
	array(
		'post_type'  => 'um_form',
		'meta_query' => array(
			array(
				'key'   => '_um_mode',
				'value' => 'profile',
			)
		),
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);

if ( ! empty( $profile_forms ) ) {
	foreach ( $profile_forms as $profile_form_id ) {
		foreach ( $profile_tabs as $profile_tab ) {
			delete_post_meta( $profile_form_id, "_um_profile_tab_{$profile_tab}" );
			delete_post_meta( $profile_form_id, "_um_profile_tab_{$profile_tab}_privacy" );
			delete_post_meta( $profile_form_id, "_um_profile_tab_{$profile_tab}_roles" );
		}

		$order = get_post_meta( $profile_form_id, '_um_profile_tabs_order', true );
		if ( ! empty( $order ) ) {
			$order = explode( ',', $order );
			$order = array_map( 'trim', $order );
			foreach ( $profile_tabs as $profile_tab ) {
				unset( $order[ array_search( $profile_tab, $order, true ) ] );
			}
			update_post_meta( $profile_form_id, '_um_profile_tabs_order', $order );
		}

		$tabs_custom_titles = get_post_meta( $profile_form_id, '_um_profile_tabs_custom_titles', true );
		if ( ! empty( $tabs_custom_titles ) && is_array( $tabs_custom_titles ) ) {
			foreach ( $profile_tabs as $profile_tab ) {
				unset( $tabs_custom_titles[ $profile_form_id ] );
			}
			update_post_meta( $profile_form_id, '_um_profile_tabs_custom_titles', $tabs_custom_titles );
		}

		$old_default = get_post_meta( $profile_form_id, '_um_profile_menu_default_tab', true );
		if ( ! empty( $old_default ) && in_array( $old_default, $profile_tabs, true ) ) {
			$new_default = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT meta_key
					FROM {$wpdb->postmeta} 
					WHERE post_id = %d
					      meta_key LIKE '_um_profile_tab_%' AND 
					      meta_key NOT LIKE '%_roles' AND 
					      meta_key NOT LIKE '%_privacy' AND 
					      meta_value = 1
					LIMIT 1",
					$profile_form_id
				)
			);

			// set new default profile tab if we delete default on uninstall module
			if ( ! empty( $new_default ) ) {
				update_post_meta( $profile_form_id, '_um_profile_menu_default_tab', $new_default );
			} else {
				delete_post_meta( $profile_form_id, '_um_profile_menu_default_tab' );
			}
		}
	}
}
