<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$wpdb->query(
	"DELETE 
	FROM {$wpdb->postmeta} 
	WHERE meta_key = '_um_register_use_terms_conditions' OR 
		  meta_key = '_um_register_use_terms_conditions_content_id' OR
		  meta_key = '_um_register_use_terms_conditions_toggle_show' OR
		  meta_key = '_um_register_use_terms_conditions_toggle_hide' OR
		  meta_key = '_um_register_use_terms_conditions_agreement' OR
		  meta_key = '_um_register_use_terms_conditions_error_text'"
);

$usermeta = $wpdb->get_results(
	"SELECT *
	FROM {$wpdb->usermeta} 
	WHERE meta_key = 'submitted' AND 
		  meta_value LIKE '%" . serialize( 'use_terms_conditions_agreement' ) . "%'",
	ARRAY_A
);
if ( ! empty( $usermeta ) ) {
	foreach ( $usermeta as $row ) {
		$value = maybe_unserialize( $row['meta_value'] );
		if ( is_array( $value ) && array_key_exists( 'use_terms_conditions_agreement', $value ) ) {
			unset( $value['use_terms_conditions_agreement'] );
			update_user_meta( $row['user_id'], $row['meta_key'], $value );
		}
	}
}
