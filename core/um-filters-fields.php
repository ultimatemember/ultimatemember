<?php

	/***
	***	@get form fields
	***/
	add_filter('um_get_form_fields', 'um_get_form_fields', 99);
	function um_get_form_fields( $array ) {
		
		global $ultimatemember;
		
		$form_id = (isset ( $ultimatemember->fields->set_id ) ) ? $ultimatemember->fields->set_id : null;
		$mode = (isset( $ultimatemember->fields->set_mode ) ) ? $ultimatemember->fields->set_mode : null;
		
		$array = $ultimatemember->query->get_attr('custom_fields', $form_id );
		
		return $array;
		
	}