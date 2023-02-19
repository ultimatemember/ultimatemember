<?php if ( ! defined( 'ABSPATH' ) ) exit;


function um_upgrade_choice_callbacks240() {
	UM()->admin()->check_ajax_nonce();

	um_maybe_unset_time_limit();

	$functions = array();
	// hardcoded for UM:Woocommerce function
	if ( function_exists( 'um_woo_directory_get_states' ) ) {
		$functions[] = 'um_woo_directory_get_states';
	}

	$custom_fields = get_option( 'um_fields', array() );
	foreach ( $custom_fields as $custom_field ) {
		if ( array_key_exists( 'custom_dropdown_options_source', $custom_field ) && function_exists( $custom_field['custom_dropdown_options_source'] ) ) {
			$functions[] = $custom_field['custom_dropdown_options_source'];
		}
	}

	$forms_query = new WP_Query;
	$forms = $forms_query->query( array(
		'post_type'      => 'um_form',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	) );

	foreach ( $forms as $form_id ) {
		$forms_fields = get_post_meta( $form_id, '_um_custom_fields', true );
		if ( ! is_array( $forms_fields ) ) {
			continue;
		}

		foreach ( $forms_fields as $key => $field ) {
			if ( array_key_exists( 'custom_dropdown_options_source', $field ) && function_exists( $field['custom_dropdown_options_source'] ) ) {
				$functions[] = $field['custom_dropdown_options_source'];
			}
		}
	}

	$functions = array_unique( $functions );
	$functions = implode( "\r\n", $functions );
	UM()->options()->update( 'allowed_choice_callbacks', $functions );

	// delete temporarily option for fields upgrade
	update_option( 'um_last_version_upgrade', '2.4.0' );

	wp_send_json_success( array( 'message' => __( 'Custom callback functions whitelisted for 2.4.0 version.', 'ultimate-member' ) ) );
}
