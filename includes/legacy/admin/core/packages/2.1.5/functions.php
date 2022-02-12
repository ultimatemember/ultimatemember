<?php if ( ! defined( 'ABSPATH' ) ) exit;


function um_upgrade_balance_field215() {
	UM()->admin()->check_ajax_nonce();

	um_maybe_unset_time_limit();

	global $wpdb;

	$point_type = defined( 'MYCRED_DEFAULT_TYPE_KEY' ) ? MYCRED_DEFAULT_TYPE_KEY : 'mycred_default';

	if ( function_exists( 'mycred' ) ) {
		UM()->options()->update( 'mycred_point_types', array( $point_type ) );
	}

	// update default sorting
	$wpdb->query(
		"UPDATE {$wpdb->postmeta}
		SET meta_value = IF( meta_value = 'most_mycred_points', 'most_mycred_default', IF( meta_value = 'least_mycred_points', 'least_mycred_default', meta_value ) )
		WHERE meta_key = '_um_sortby'"
	);

	// Update role_select and role_radio filters to role
	$postmeta = $wpdb->get_results( "SELECT * FROM {$wpdb->postmeta} WHERE meta_key='_um_sorting_fields'", ARRAY_A );
	if ( ! empty( $postmeta ) ) {
		foreach ( $postmeta as $row ) {
			$meta_value = maybe_unserialize( $row['meta_value'] );

			if ( is_array( $meta_value ) ) {
				$update = false;

				if ( false !== ( $index = array_search( 'most_mycred_points', $meta_value ) ) ) {
					$meta_value[ $index ] = 'most_mycred_default';
					$update = true;
				}

				if ( false !== ( $index = array_search( 'least_mycred_points', $meta_value ) ) ) {
					$meta_value[ $index ] = 'least_mycred_default';
					$update = true;
				}

				if ( $update ) {
					update_post_meta( $row['post_id'], '_um_sorting_fields', $meta_value );
				}
			}
		}
	}


	$custom_fields = get_option( 'um_fields', array() );

	$forms_query = new WP_Query;
	$forms = $forms_query->query( array(
		'post_type'         => 'um_form',
		'posts_per_page'    => -1,
		'fields'            => 'ids'
	) );

	$field_for_upgrade = array();

	foreach ( $forms as $form_id ) {
		$forms_fields = get_post_meta( $form_id, '_um_custom_fields', true );

		foreach ( $forms_fields as $key => &$field ) {

			if ( isset( $field['metakey'] ) && $point_type == $field['metakey'] ) {
				if ( empty( $field_for_upgrade ) ) {
					$field_for_upgrade = array(
						'type'          => 'mycred_balance',
						'title'         => $field['title'],
						'metakey'       => $field['metakey'],
						'label'         => $field['label'],
						'public'        => $field['public'],
						'visibility'    => $field['visibility'],
					);
				}

				$field['type'] = 'mycred_balance';
			}

		}

		update_post_meta( $form_id, '_um_custom_fields', $forms_fields );
	}

	if ( ! empty( $field_for_upgrade ) ) {
		$custom_fields[ $point_type ] = $field_for_upgrade;
		update_option( 'um_fields', $custom_fields );
	}

	update_option( 'um_last_version_upgrade', '2.1.5' );

	if ( ! empty( $field_for_upgrade ) ) {
		wp_send_json_success( array( 'message' => __( 'Balance fields were updated successfully', 'um-mycred' ) ) );
	} else {
		wp_send_json_success( array( 'message' => __( 'Updated successfully', 'um-mycred' ) ) );
	}
}