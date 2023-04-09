<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


function um_upgrade_social_fields260() {
	UM()->admin()->check_ajax_nonce();

	um_maybe_unset_time_limit();

	$fields_for_upgrade = array();

	$forms_query = new \WP_Query;
	$forms       = $forms_query->query( array(
		'post_type'      => 'um_form',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	) );

	foreach ( $forms as $form_id ) {
		$forms_fields = get_post_meta( $form_id, '_um_custom_fields', true );
		if ( ! is_array( $forms_fields ) ) {
			continue;
		}

		$need_update = false;
		foreach ( $forms_fields as $key => &$field ) {
			if ( array_key_exists( 'validate', $field ) && in_array( $field['validate'], array( 'google_url', 'googleplus_url', 'vk_url' ), true ) ) { // Looks like googleplus_url is old legacy value.
				$field['validate'] = 'url';
				$need_update       = true;

				$fields_for_upgrade[] = $key;
			}
		}

		if ( $need_update ) {
			update_post_meta( $form_id, '_um_custom_fields', $forms_fields );
		}
	}

	$need_update   = false;
	$custom_fields = get_option( 'um_fields', array() );
	foreach ( $custom_fields as &$field ) {
		if ( array_key_exists( 'validate', $field ) && in_array( $field['validate'], array( 'google_url', 'googleplus_url', 'vk_url' ), true ) ) { // Looks like googleplus_url is old legacy value.
			$field['validate'] = 'url';
			$need_update       = true;

			$fields_for_upgrade[] = $field['metakey'];
		}
	}
	if ( $need_update ) {
		update_option( 'um_fields', $custom_fields );
	}

	$fields_for_upgrade = array_unique( $fields_for_upgrade );

	// delete temporarily option for fields upgrade
	update_option( 'um_last_version_upgrade', '2.6.0' );

	if ( ! empty( $fields_for_upgrade ) ) {
		wp_send_json_success( array( 'message' => __( 'Social URLs fields have been successfully updated.', 'ultimate-member' ) ) );
	} else {
		wp_send_json_success( array( 'message' => __( 'Database has been updated successfully', 'ultimate-member' ) ) );
	}
}
