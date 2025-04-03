<?php if ( ! defined( 'ABSPATH' ) ) exit;


function um_upgrade_users_count213beta3() {
	UM()->admin()->check_ajax_nonce();

	um_maybe_unset_time_limit();

	global $wpdb;

	$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->users}" );

	wp_send_json_success( array( 'count' => $count ) );
}


function um_upgrade_metadata_per_user213beta3() {
	UM()->admin()->check_ajax_nonce();

	um_maybe_unset_time_limit();


	if ( empty( $_POST['page'] ) ) {
		wp_send_json_error( __( 'Wrong data', 'ultimate-member' ) );
	}

	$per_page = 50;

	global $wpdb;

	$min_max = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT MIN(ID) AS MinID, MAX(ID) AS MaxID
			FROM (
				SELECT u.ID
				FROM {$wpdb->users} as u
				ORDER BY u.ID
				LIMIT %d, %d
			) as dt",
			( absint( $_POST['page'] ) - 1 ) * $per_page,
			$per_page
		),
		ARRAY_A
	);

	$metadata = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT u.ID as user_id,
				  um.meta_key as meta_key,
				  um.meta_value as meta_value
			FROM {$wpdb->users} u
			LEFT JOIN {$wpdb->usermeta} um ON ( um.user_id = u.ID AND um.meta_key IN( 'account_status','hide_in_members','synced_gravatar_hashed_id','synced_profile_photo','profile_photo','cover_photo','_um_verified' ) )
			WHERE u.ID >= %d AND
				  u.ID <= %d",
			$min_max['MinID'],
			$min_max['MaxID']
		),
		ARRAY_A
	);

	$users_map = array();
	foreach ( $metadata as $metadatarow ) {
		if ( ! isset( $users_map[ $metadatarow['user_id'] ] ) ) {
			$users_map[ $metadatarow['user_id'] ] = array(
				'account_status'    => 'approved',
				'hide_in_members'   => UM()->member_directory()->get_hide_in_members_default(),
				'profile_photo'     => false,
				'cover_photo'       => false,
				'verified'          => false,
			);
		}

		switch ( $metadatarow['meta_key'] ) {
			case 'account_status':
				$users_map[ $metadatarow['user_id'] ]['account_status'] = $metadatarow['meta_value'];
				break;
			case 'hide_in_members':

				$hide_in_members = UM()->member_directory()->get_hide_in_members_default();
				if ( ! empty( $metadatarow['meta_value'] ) ) {
					if ( $metadatarow['meta_value'] == 'Yes' || $metadatarow['meta_value'] == __( 'Yes', 'ultimate-member' ) ||
					     ( is_array( $metadatarow['meta_value'] ) && array_intersect( array( 'Yes', __( 'Yes', 'ultimate-member' ) ), $metadatarow['meta_value'] ) ) ) {
						$hide_in_members = true;
					} else {
						$hide_in_members = false;
					}
				}

				$users_map[ $metadatarow['user_id'] ]['hide_in_members'] = $hide_in_members;

				break;
			case 'synced_gravatar_hashed_id':
				if ( UM()->options()->get( 'use_gravatars' ) ) {
					if ( empty( $users_map[ $metadatarow['user_id'] ]['profile_photo'] ) ) {
						$users_map[ $metadatarow['user_id'] ]['profile_photo'] = ! empty( $metadatarow['meta_value'] );
					}
				}

				break;
			case 'synced_profile_photo':
			case 'profile_photo':
				if ( empty( $users_map[ $metadatarow['user_id'] ]['profile_photo'] ) ) {
					$users_map[ $metadatarow['user_id'] ]['profile_photo'] = ! empty( $metadatarow['meta_value'] );
				}
				break;
			case 'cover_photo':
				$users_map[ $metadatarow['user_id'] ]['cover_photo'] = ! empty( $metadatarow['meta_value'] );
				break;
			case '_um_verified':
				$users_map[ $metadatarow['user_id'] ]['verified'] = $metadatarow['meta_value'] == 'verified' ? true : false;
				break;
		}
	}

	if ( ! empty( $users_map ) ) {
		foreach ( $users_map as $user_id => $metavalue ) {
			update_user_meta( $user_id, 'um_member_directory_data', $metavalue );
		}
	}

	$from = ( absint( $_POST['page'] ) * $per_page ) - $per_page + 1;
	$to   = absint( $_POST['page'] ) * $per_page;

	// translators: %1$s is a from; %2$s is a to.
	wp_send_json_success( array( 'message' => sprintf( __( 'Metadata from %1$s to %2$s users were upgraded successfully...', 'ultimate-member' ), $from, $to ) ) );
}


function um_upgrade_metatable213beta3() {
	UM()->admin()->check_ajax_nonce();

	um_maybe_unset_time_limit();

	global $wpdb;

	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE {$wpdb->prefix}um_metadata (
umeta_id bigint(20) unsigned NOT NULL auto_increment,
user_id bigint(20) unsigned NOT NULL default '0',
um_key varchar(255) default NULL,
um_value longtext default NULL,
PRIMARY KEY  (umeta_id),
KEY user_id_indx (user_id),
KEY meta_key_indx (um_key),
KEY meta_value_indx (um_value(191))
) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );

	update_option( 'um_last_version_upgrade', '2.1.3-beta3' );
	wp_send_json_success( array( 'message' => __( 'Usermeta table was upgraded successfully', 'ultimate-member' ) ) );
}
