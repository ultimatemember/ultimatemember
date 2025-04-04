<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function um_upgrade_usermeta_count280() {
	UM()->admin()->check_ajax_nonce();

	um_maybe_unset_time_limit();

	global $wpdb;

	$count = $wpdb->get_var(
		"SELECT COUNT(*)
		FROM {$wpdb->usermeta}
		WHERE meta_key = 'use_gdpr_agreement' OR
			  meta_key = 'um_user_blocked__timestamp' OR
			  meta_key = '_um_last_login' OR
			  meta_key = 'submitted'"
	);

	wp_send_json_success( array( 'count' => $count ) );
}


function um_upgrade_metadata_per_user280() {
	UM()->admin()->check_ajax_nonce();

	um_maybe_unset_time_limit();

	if ( empty( $_POST['page'] ) ) {
		wp_send_json_error( __( 'Wrong data', 'ultimate-member' ) );
	}

	global $wpdb;

	$per_page = 100;
	$usermeta = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT *
			FROM {$wpdb->usermeta}
			WHERE meta_key = 'use_gdpr_agreement' OR
				  meta_key = 'um_user_blocked__timestamp' OR
				  meta_key = '_um_last_login' OR
				  meta_key = 'submitted'
			LIMIT %d, %d",
			( absint( $_POST['page'] ) - 1 ) * $per_page,
			$per_page
		),
		ARRAY_A
	);

	// Make non-GMT timestamp as GMT base on current wp_timezone().
	$offset = (int) ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
	foreach ( $usermeta as $usermeta_row ) {
		$updated_meta = null;

		if ( empty( $usermeta_row['meta_key'] ) ) {
			continue;
		}

		$user_id = $usermeta_row['user_id'];
		if ( 'use_gdpr_agreement' === $usermeta_row['meta_key'] ) {
			if ( is_numeric( $usermeta_row['meta_value'] ) ) {
				update_user_meta( $user_id, 'um_use_gdpr_agreement_backup', $usermeta_row['meta_value'] );
				$updated_meta = gmdate( 'Y-m-d H:i:s', $usermeta_row['meta_value'] );
			}
		} elseif ( '_um_last_login' === $usermeta_row['meta_key'] ) {
			if ( is_numeric( $usermeta_row['meta_value'] ) ) {
				update_user_meta( $user_id, '_um_last_login_backup', $usermeta_row['meta_value'] );
				if ( ! empty( $offset ) ) {
					$updated_meta = (int) $usermeta_row['meta_value'] - $offset;
				} else {
					$updated_meta = (int) $usermeta_row['meta_value'];
				}

				$updated_meta = gmdate( 'Y-m-d H:i:s', $updated_meta );
			}
		} elseif ( 'submitted' === $usermeta_row['meta_key'] ) {
			$unserialized_meta = maybe_unserialize( $usermeta_row['meta_value'] );
			if ( isset( $unserialized_meta['use_gdpr_agreement'] ) && is_numeric( $unserialized_meta['use_gdpr_agreement'] ) ) {
				update_user_meta( $user_id, 'submitted_backup', $unserialized_meta );
				$unserialized_meta['use_gdpr_agreement'] = gmdate( 'Y-m-d H:i:s', $unserialized_meta['use_gdpr_agreement'] );

				$updated_meta = maybe_serialize( $unserialized_meta );
			}
		} elseif ( 'um_user_blocked__timestamp' === $usermeta_row['meta_key'] ) {
			if ( ! empty( $offset ) ) {
				$last_scanned_time = $usermeta_row['meta_value'];
				if ( ! empty( $last_scanned_time ) ) {
					update_user_meta( $user_id, 'um_user_blocked__timestamp_backup', $last_scanned_time );
					$last_scanned_time = strtotime( $last_scanned_time ) - $offset;
					$updated_meta      = gmdate( 'Y-m-d H:i:s', $last_scanned_time );
				}
			}
		}

		if ( isset( $updated_meta ) ) {
			update_user_meta( $user_id, $usermeta_row['meta_key'], $updated_meta );
		}
	}

	$from = ( absint( $_POST['page'] ) * $per_page ) - $per_page + 1;
	$to   = absint( $_POST['page'] ) * $per_page;

	// translators: %1$s is a from; %2$s is a to.
	wp_send_json_success( array( 'message' => sprintf( __( 'Metadata from %1$s to %2$s were upgraded successfully...', 'ultimate-member' ), $from, $to ) ) );
}

function um_upgrade_update_options280() {
	UM()->admin()->check_ajax_nonce();

	um_maybe_unset_time_limit();

	$offset = (int) ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );

	if ( ! empty( $offset ) ) {
		$last_scanned_time = get_option( 'um_secure_last_time_scanned' );
		if ( ! empty( $last_scanned_time ) ) {
			update_option( 'um_secure_last_time_scanned_backup', $last_scanned_time );
			$last_scanned_time = strtotime( $last_scanned_time ) - $offset;
			$last_scanned_time = gmdate( 'Y-m-d H:i:s', $last_scanned_time );

			update_option( 'um_secure_last_time_scanned', $last_scanned_time );
		}
	}

	// delete temporarily option for fields upgrade
	update_option( 'um_last_version_upgrade', '2.8.0' );

	wp_send_json_success( array( 'message' => __( 'Database has been updated successfully', 'ultimate-member' ) ) );
}
