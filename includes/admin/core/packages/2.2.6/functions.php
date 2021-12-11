<?php if ( ! defined( 'ABSPATH' ) ) exit;


function um_upgrade_skype_id226() {
	UM()->admin()->check_ajax_nonce();

	um_maybe_unset_time_limit();

	$forms_query = new WP_Query;
	$forms = $forms_query->query( array(
		'post_type'         => 'um_form',
		'posts_per_page'    => -1,
		'fields'            => 'ids'
	) );

	$fields_for_upgrade = array();

	foreach ( $forms as $form_id ) {
		$forms_fields = get_post_meta( $form_id, '_um_custom_fields', true );

		$changed = false;
		foreach ( $forms_fields as $key => &$field ) {
			if ( isset( $field['validate'] ) && 'skype' === $field['validate'] ) {
				if ( isset( $field['type'] ) && 'url' === $field['type'] ) {
					$field['type'] = 'text';
					$changed       = true;

					$fields_for_upgrade[] = $field['metakey'];
				}
			}
		}

		if ( $changed ) {
			update_post_meta( $form_id, '_um_custom_fields', $forms_fields );
		}
	}

	$changed       = false;
	$custom_fields = get_option( 'um_fields', array() );
	foreach ( $custom_fields as &$custom_field ) {
		if ( isset( $custom_field['validate'] ) && 'skype' === $custom_field['validate'] ) {
			if ( isset( $custom_field['type'] ) && 'url' === $custom_field['type'] ) {
				$custom_field['type'] = 'text';
				$changed              = true;

				$fields_for_upgrade[] = $custom_field['metakey'];
			}
		}
	}
	if ( $changed ) {
		update_option( 'um_fields', $custom_fields );
	}

	$fields_for_upgrade = array_unique( $fields_for_upgrade );

	// avoid 'https://', 'http://' at the start of the Skype field is there is nickname but not https://join.skype.com/
	// change only links with nickname skip https://join.skype.com/
	if ( ! empty( $fields_for_upgrade ) ) {
		global $wpdb;
		$usermetas = $wpdb->get_results( "SELECT user_id, meta_key, meta_value FROM {$wpdb->usermeta} WHERE meta_key IN( '" . implode( "','", $fields_for_upgrade ) . "' )", ARRAY_A );
		if ( ! empty( $usermetas ) ) {
			foreach ( $usermetas as $usermeta ) {
				if ( false !== strstr( $usermeta['meta_value'], 'https://' ) || false !== strstr( $usermeta['meta_value'], 'http://' ) ) {
					if ( false === strstr( $usermeta['meta_value'], 'join.skype.com/' ) ) {
						$usermeta['meta_value'] = str_replace( array( 'https://', 'http://' ), '', $usermeta['meta_value'] );
						update_user_meta( $usermeta['user_id'], $usermeta['meta_key'], $usermeta['meta_value'] );

						delete_option( "um_cache_userdata_{$usermeta['user_id']}" );
					}
				}
			}
		}
	}

	update_option( 'um_last_version_upgrade', '2.2.6' );

	if ( ! empty( $fields_for_upgrade ) ) {
		wp_send_json_success( array( 'message' => __( 'SkypeID fields have been updated successfully', 'ultimate-member' ) ) );
	} else {
		wp_send_json_success( array( 'message' => __( 'Database has been updated successfully', 'ultimate-member' ) ) );
	}
}
