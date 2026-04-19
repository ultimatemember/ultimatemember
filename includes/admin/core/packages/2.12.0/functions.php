<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function um_upgrade_usermeta_count2120() {
	UM()->admin()->check_ajax_nonce();

	if ( ! is_multisite() ) {
		wp_send_json_success( array( 'count' => 0 ) );
	}

	um_maybe_unset_time_limit();

	global $wpdb;

	$count = $wpdb->get_var(
		"SELECT COUNT(*)
		FROM {$wpdb->usermeta}
		WHERE meta_key = 'um_member_directory_data' OR
			  meta_key = 'profile_photo' OR
			  meta_key = 'cover_photo'"
	);

	wp_send_json_success( array( 'count' => $count ) );
}


function um_upgrade_metadata_per_user2120() {
	UM()->admin()->check_ajax_nonce();

	um_maybe_unset_time_limit();

	if ( empty( $_POST['page'] ) ) {
		wp_send_json_error( __( 'Wrong data', 'ultimate-member' ) );
	}

	if ( ! is_multisite() ) {
		wp_send_json_success( array( 'message' => __( 'Metadata were upgraded successfully...', 'ultimate-member' ) ) );
	}

	global $wpdb;

	$per_page = 100;
	$usermeta = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT *
			FROM {$wpdb->usermeta}
			WHERE meta_key = 'um_member_directory_data' OR
				  meta_key = 'profile_photo' OR
				  meta_key = 'cover_photo'
			LIMIT %d, %d",
			( absint( $_POST['page'] ) - 1 ) * $per_page,
			$per_page
		),
		ARRAY_A
	);

	$blog_ids = get_sites(
		array(
			'number' => -1,
			'fields' => 'ids',
		)
	);

	$paths = array();
	foreach ( $blog_ids as $b_id ) {
		switch_to_blog( $b_id );
		$paths[ $b_id ] = UM()->common()->filesystem()->get_upload_dir( 'ultimatemember' );
	}

	restore_current_blog();

	foreach ( $usermeta as $usermeta_row ) {
		$updated_meta = null;

		if ( empty( $usermeta_row['meta_key'] ) ) {
			continue;
		}

		$user_id = $usermeta_row['user_id'];
		if ( 'um_member_directory_data' === $usermeta_row['meta_key'] ) {
			foreach ( $blog_ids as $b_id ) {
				switch_to_blog( $b_id );

				update_user_option( $user_id, 'um_member_directory_data', maybe_unserialize( $usermeta_row['meta_value'] ) );
			}

			restore_current_blog();
		} elseif ( 'profile_photo' === $usermeta_row['meta_key'] ) {
			foreach ( $blog_ids as $b_id ) {
				if ( file_exists( wp_normalize_path( $paths[ $b_id ] . DIRECTORY_SEPARATOR . $user_id . DIRECTORY_SEPARATOR . $usermeta_row['meta_value'] ) ) ) {
					switch_to_blog( $b_id );
					update_user_option( $user_id, 'profile_photo', $usermeta_row['meta_value'] );
				}
			}

			restore_current_blog();
		} elseif ( 'cover_photo' === $usermeta_row['meta_key'] ) {
			foreach ( $blog_ids as $b_id ) {
				if ( file_exists( wp_normalize_path( $paths[ $b_id ] . DIRECTORY_SEPARATOR . $user_id . DIRECTORY_SEPARATOR . $usermeta_row['meta_value'] ) ) ) {
					switch_to_blog( $b_id );
					update_user_option( $user_id, 'cover_photo', $usermeta_row['meta_value'] );
				}
			}

			restore_current_blog();
		}
	}

	$from = ( absint( $_POST['page'] ) * $per_page ) - $per_page + 1;
	$to   = absint( $_POST['page'] ) * $per_page;

	// translators: %1$s is a from; %2$s is a to.
	wp_send_json_success( array( 'message' => sprintf( __( 'Metadata from %1$s to %2$s were upgraded successfully...', 'ultimate-member' ), $from, $to ) ) );
}

function um_upgrade_update_options2120() {
	UM()->admin()->check_ajax_nonce();

	um_maybe_unset_time_limit();

	// delete temporarily option for fields upgrade
	update_option( 'um_last_version_upgrade', '2.12.0' );

	wp_send_json_success( array( 'message' => __( 'Database has been updated successfully', 'ultimate-member' ) ) );
}
