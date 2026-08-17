<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function um_country_name_replacements2122() {
	// Old country names are kept here to update the data saved before the official renames.
	return array(
		'Turkey'                                     => 'Türkiye',
		'Macedonia, the former Yugoslav Republic of' => 'North Macedonia',
	);
}


function um_country_name_replace_value2122( $value, $replacements, &$changed ) {
	if ( is_array( $value ) ) {
		$new_value = array();
		foreach ( $value as $key => $item ) {
			$item = um_country_name_replace_value2122( $item, $replacements, $changed );

			// Select option values can be stored as array keys when the options pair mode is enabled.
			// Rename the key only when the new name is not used yet, so no value is silently replaced.
			if ( is_string( $key ) && isset( $replacements[ $key ] )
				&& ! array_key_exists( $replacements[ $key ], $value )
				&& ! array_key_exists( $replacements[ $key ], $new_value ) ) {
				$changed                            = true;
				$new_value[ $replacements[ $key ] ] = $item;
			} else {
				$new_value[ $key ] = $item;
			}
		}
		return $new_value;
	}

	if ( is_string( $value ) && isset( $replacements[ $value ] ) ) {
		$changed = true;
		return $replacements[ $value ];
	}

	return $value;
}


function um_country_fields_replace_names2122( &$fields, $replacements ) {
	$metakeys = array();

	if ( empty( $fields ) || ! is_array( $fields ) ) {
		return $metakeys;
	}

	foreach ( $fields as $key => &$field ) {
		if ( ! is_array( $field ) || empty( $field['metakey'] ) ) {
			continue;
		}

		// The whole field is walked to also update the values referenced by conditional logic.
		$changed = false;
		$field   = um_country_name_replace_value2122( $field, $replacements, $changed );

		if ( $changed ) {
			$metakeys[] = $field['metakey'];
		}
	}
	unset( $field );

	return $metakeys;
}


function um_upgrade_country_fields2122() {
	UM()->admin()->check_ajax_nonce();

	um_maybe_unset_time_limit();

	$replacements = um_country_name_replacements2122();

	// The predefined country field keeps user metadata even when form options are not persisted.
	$fields_for_upgrade = array( 'country' );

	$forms_query = new WP_Query;
	$forms = $forms_query->query( array(
		'post_type'      => 'um_form',
		'posts_per_page' => -1,
		'post_status'    => 'any',
		'no_found_rows'  => true,
		'fields'         => 'ids',
	) );

	foreach ( $forms as $form_id ) {
		$forms_fields = get_post_meta( $form_id, '_um_custom_fields', true );

		$changed_metakeys = um_country_fields_replace_names2122( $forms_fields, $replacements );
		if ( ! empty( $changed_metakeys ) ) {
			update_post_meta( $form_id, '_um_custom_fields', $forms_fields );
			$fields_for_upgrade = array_merge( $fields_for_upgrade, $changed_metakeys );
		}
	}

	$custom_fields = get_option( 'um_fields', array() );

	$changed_metakeys = um_country_fields_replace_names2122( $custom_fields, $replacements );
	if ( ! empty( $changed_metakeys ) ) {
		update_option( 'um_fields', $custom_fields );
		$fields_for_upgrade = array_merge( $fields_for_upgrade, $changed_metakeys );
	}

	// Update the default filters of member directories limited by the old country names.
	$directories_query = new WP_Query;
	$directories = $directories_query->query( array(
		'post_type'      => 'um_directory',
		'posts_per_page' => -1,
		'post_status'    => 'any',
		'no_found_rows'  => true,
		'fields'         => 'ids',
	) );

	foreach ( $directories as $directory_id ) {
		$search_filters = get_post_meta( $directory_id, '_um_search_filters', true );
		if ( empty( $search_filters ) || ! is_array( $search_filters ) ) {
			continue;
		}

		$changed        = false;
		$search_filters = um_country_name_replace_value2122( $search_filters, $replacements, $changed );
		if ( $changed ) {
			update_post_meta( $directory_id, '_um_search_filters', $search_filters );
		}
	}

	$fields_for_upgrade = array_values( array_unique( $fields_for_upgrade ) );

	update_option( 'um_upgrade_2122_country_fields_for_upgrade', $fields_for_upgrade );

	wp_send_json_success( array( 'message' => __( 'Country fields have been updated successfully', 'ultimate-member' ), 'count' => count( $fields_for_upgrade ) ) );
}


function um_upgrade_usermeta_count2122() {
	UM()->admin()->check_ajax_nonce();

	um_maybe_unset_time_limit();

	$fields_for_upgrade = get_option( 'um_upgrade_2122_country_fields_for_upgrade', array() );
	if ( ! is_array( $fields_for_upgrade ) ) {
		$fields_for_upgrade = array();
	}

	$meta_keys     = array_merge( $fields_for_upgrade, array( 'submitted' ) );
	$placeholders  = implode( ',', array_fill( 0, count( $meta_keys ), '%s' ) );

	global $wpdb;

	$count = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*)
			FROM {$wpdb->usermeta}
			WHERE meta_key IN ( $placeholders )",
			$meta_keys
		)
	);

	wp_send_json_success( array( 'count' => $count ) );
}


function um_upgrade_usermeta_part2122() {
	UM()->admin()->check_ajax_nonce();

	um_maybe_unset_time_limit();

	if ( empty( $_POST['page'] ) ) {
		wp_send_json_error( __( 'Wrong data', 'ultimate-member' ) );
	}

	$fields_for_upgrade = get_option( 'um_upgrade_2122_country_fields_for_upgrade', array() );
	if ( empty( $fields_for_upgrade ) || ! is_array( $fields_for_upgrade ) ) {
		wp_send_json_success( array( 'message' => __( 'Database has been updated successfully', 'ultimate-member' ) ) );
	}

	$replacements = um_country_name_replacements2122();

	$per_page = 100;

	$meta_keys    = array_merge( $fields_for_upgrade, array( 'submitted' ) );
	$placeholders = implode( ',', array_fill( 0, count( $meta_keys ), '%s' ) );

	global $wpdb;

	$usermetas = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT umeta_id,
				  user_id,
				  meta_key,
				  meta_value
			FROM {$wpdb->usermeta}
			WHERE meta_key IN ( $placeholders )
			ORDER BY umeta_id
			LIMIT %d, %d",
			array_merge( $meta_keys, array(
				( absint( $_POST['page'] ) - 1 ) * $per_page,
				$per_page,
			) )
		),
		ARRAY_A
	);

	if ( ! empty( $usermetas ) ) {
		foreach ( $usermetas as $usermeta ) {
			$updated_meta = null;

			if ( 'submitted' === $usermeta['meta_key'] ) {
				$unserialized_meta = maybe_unserialize( $usermeta['meta_value'] );
				if ( is_array( $unserialized_meta ) ) {
					$meta_changed = false;
					foreach ( $fields_for_upgrade as $field_metakey ) {
						if ( ! isset( $unserialized_meta[ $field_metakey ] ) ) {
							continue;
						}
						$unserialized_meta[ $field_metakey ] = um_country_name_replace_value2122( $unserialized_meta[ $field_metakey ], $replacements, $meta_changed );
					}
					if ( $meta_changed ) {
						$updated_meta = $unserialized_meta;
					}
				}
			} else {
				$unserialized_meta = maybe_unserialize( $usermeta['meta_value'] );
				$meta_changed      = false;
				$new_value         = um_country_name_replace_value2122( $unserialized_meta, $replacements, $meta_changed );
				if ( $meta_changed ) {
					$updated_meta = $new_value;
				}
			}

			if ( isset( $updated_meta ) ) {
				update_metadata_by_mid( 'user', absint( $usermeta['umeta_id'] ), $updated_meta, $usermeta['meta_key'] );
				delete_option( "um_cache_userdata_{$usermeta['user_id']}" );
			}
		}
	}

	$from = ( absint( $_POST['page'] ) * $per_page ) - $per_page + 1;
	$to   = absint( $_POST['page'] ) * $per_page;

	// translators: %1$s is a from; %2$s is a to.
	wp_send_json_success( array( 'message' => sprintf( __( 'Metadata from %1$s to %2$s were upgraded successfully...', 'ultimate-member' ), $from, $to ) ) );
}


function um_upgrade_update_options2122() {
	UM()->admin()->check_ajax_nonce();

	um_maybe_unset_time_limit();

	// delete temporarily option for fields upgrade
	delete_option( 'um_upgrade_2122_country_fields_for_upgrade' );

	update_option( 'um_last_version_upgrade', '2.12.2' );

	wp_send_json_success( array( 'message' => __( 'Database has been updated successfully', 'ultimate-member' ) ) );
}
