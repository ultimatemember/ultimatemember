<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$wpdb->query(
	"DELETE 
	FROM {$wpdb->postmeta} 
	WHERE meta_key LIKE '_um_register_use_terms_conditions' OR 
		  meta_key LIKE '_um_register_use_terms_conditions_content_id' OR
		  meta_key LIKE '_um_register_use_terms_conditions_toggle_show' OR
		  meta_key LIKE '_um_register_use_terms_conditions_toggle_hide' OR
		  meta_key LIKE '_um_register_use_terms_conditions_agreement' OR
		  meta_key LIKE '_um_register_use_terms_conditions_error_text'"
);
