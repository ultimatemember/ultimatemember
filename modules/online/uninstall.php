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

global $wpdb;

// Remove usermeta
$wpdb->query(
	"DELETE 
	FROM {$wpdb->usermeta} 
	WHERE meta_key = '_hide_online_status'"
);

// Remove postmeta
$wpdb->query(
	"DELETE 
	FROM {$wpdb->postmeta} 
	WHERE meta_key = '_um_online_hide_stats'"
);

// remove online status fields from form fields
$forms = get_posts(
	array(
		'post_type'   => 'um_form',
		'numberposts' => -1,
		'fields'      => 'ids',
	)
);

$delete_fields = array(
	'online_status',
);

if ( ! empty( $forms ) ) {
	foreach ( $forms as $form_id ) {
		foreach ( $delete_fields as $delete_field ) {
			UM()->common()->field()->delete_from_form( $delete_field, $form_id );
		}
	}
}

// delete field from Member Directories meta
$directories = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'um_directory'" );
if ( ! empty( $directories ) ) {
	foreach ( $directories as $directory_id ) {
		// Frontend filters
		$directory_search_fields = get_post_meta( $directory_id, '_um_search_fields', true );
		if ( empty( $directory_search_fields ) ) {
			$directory_search_fields = array();
		}
		$directory_search_fields = array_values( array_diff( $directory_search_fields, $delete_fields ) );
		update_post_meta( $directory_id, '_um_search_fields', $directory_search_fields );

		// Admin filtering
		$directory_search_filters = get_post_meta( $directory_id, '_um_search_filters', true );
		if ( empty( $directory_search_filters ) ) {
			$directory_search_filters = array();
		}
		foreach ( $delete_fields as $delete_field ) {
			unset( $directory_search_filters[ $delete_field ] );
		}
		update_post_meta( $directory_id, '_um_search_filters', $directory_search_filters );

		// display in tagline
		$directory_reveal_fields = get_post_meta( $directory_id, '_um_reveal_fields', true );
		if ( empty( $directory_reveal_fields ) ) {
			$directory_reveal_fields = array();
		}
		$directory_reveal_fields = array_values( array_diff( $directory_reveal_fields, $delete_fields ) );
		update_post_meta( $directory_id, '_um_reveal_fields', $directory_reveal_fields );

		// extra user information section
		$directory_tagline_fields = get_post_meta( $directory_id, '_um_tagline_fields', true );
		if ( empty( $directory_tagline_fields ) ) {
			$directory_tagline_fields = array();
		}
		$directory_tagline_fields = array_values( array_diff( $directory_tagline_fields,$delete_fields ) );
		update_post_meta( $directory_id, '_um_tagline_fields', $directory_tagline_fields );

		// Custom fields selected in "Choose field(s) to enable in sorting"
		$directory_sorting_fields = get_post_meta( $directory_id, '_um_sorting_fields', true );
		if ( empty( $directory_sorting_fields ) ) {
			$directory_sorting_fields = array();
		}
		foreach ( $directory_sorting_fields as $k => $sorting_data ) {
			foreach ( $delete_fields as $delete_field ) {
				if ( is_array( $sorting_data ) && array_key_exists( $delete_field, $sorting_data ) ) {
					unset( $directory_sorting_fields[ $k ] );
				}
			}
		}
		$directory_sorting_fields = array_values( $directory_sorting_fields );
		update_post_meta( $directory_id, '_um_sorting_fields', $directory_sorting_fields );

		// If "Default sort users by" = "Other (Custom Field)" is selected when delete this custom field and set default sorting
		$directory_sortby_custom = get_post_meta( $directory_id, '_um_sortby_custom', true );
		foreach ( $delete_fields as $delete_field ) {
			if ( $directory_sortby_custom === $delete_field ) {
				$directory_sortby = get_post_meta( $directory_id, '_um_sortby', true );
				if ( 'other' === $directory_sortby ) {
					update_post_meta( $directory_id, '_um_sortby', 'user_registered_desc' );
				}
				update_post_meta( $directory_id, '_um_sortby_custom', '' );
				update_post_meta( $directory_id, '_um_sortby_custom_label', '' );
			}
		}
	}
}

// Remove custom templates.
$templates_directories = array(
	trailingslashit( get_stylesheet_directory() ) . 'ultimate-member/online/',
	trailingslashit( get_template_directory() ) . 'ultimate-member/online/',
);
foreach ( $templates_directories as $templates_dir ) {
	if ( is_dir( $templates_dir ) ) {
		UM()->files()->remove_dir( $templates_dir );
	}
}
