<?php

	/***
	***	@add an impression to form
	***/
	add_action('um_before_form_is_loaded', 'um_add_page_view');
	function um_add_page_view($args){
		global $ultimatemember;
		extract($args);
		if ( isset( $form_id ) )
			$ultimatemember->form->add_pageview( $form_id );
	}
	
	/***
	***	@track a successful event
	***/
	add_action('track_approved_user_login', 'um_add_conversion');
	add_action('track_approved_user_registration', 'um_add_conversion');
	function um_add_conversion($args){
		global $ultimatemember;
		extract($args);
		$ultimatemember->form->add_conversion( $form_id );
	}