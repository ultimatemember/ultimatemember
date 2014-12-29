<?php

	/***
	***	@some required changes before value is shown
	***/
	add_filter('um_profile_field_filter_hook__', 'um_profile_field_filter_hook__', 99, 2);
	function um_profile_field_filter_hook__( $value, $data ) {
	
		if ( isset( $data['validate'] ) && $data['validate'] != '' && strstr( $data['validate'], 'url' ) ) {
			$alt = ( isset( $data['url_text'] ) ) ? $data['url_text'] : $value;
			$url_rel = ( isset( $data['url_rel'] ) ) ? 'rel="nofollow"' : '';
				
			if( !strstr( $value, 'http' )
				&& !strstr( $value, '://' )
				&& !strstr( $value, 'www.' ) 
				&& !strstr( $value, '.com' ) 
				&& !strstr( $value, '.net' )
				&& !strstr( $value, '.org' )
			) {
					
				if ( $data['validate'] == 'facebook_url' ) $value = 'http://facebook.com/' . $value;
				if ( $data['validate'] == 'twitter_url' ) $value = 'http://twitter.com/' . $value;
				if ( $data['validate'] == 'linkedin_url' ) $value = 'http://linkedin.com/' . $value;
				if ( $data['validate'] == 'skype' ) $value = 'http://skype.com/' . $value;
				if ( $data['validate'] == 'googleplus_url' ) $value = 'http://plus.google.com/' . $value;
				if ( $data['validate'] == 'instagram_url' ) $value = 'http://instagram.com/' . $value;
					
			}
			
			if ( strpos($value, 'http://') !== 0 ) {
				$value = 'http://' . $value;
			}
			
			$value = '<a href="'. $value .'" target="'.$data['url_target'].'" ' . $url_rel . '>'.$alt.'</a>';
		}
			
		if ( !is_array( $value ) ) {
		
			if ( is_email( $value ) )
				$value = '<a href="mailto:'. $value.'">'.$value.'</a>';

		} else {
		
			$value = implode(', ', $value);
	
		}

		return $value;
	}
	
	/***
	***	@get form fields
	***/
	add_filter('um_get_form_fields', 'um_get_form_fields', 99);
	function um_get_form_fields( $array ) {
		
		global $ultimatemember;
		
		$form_id = (isset ( $ultimatemember->fields->set_id ) ) ? $ultimatemember->fields->set_id : null;
		$mode = (isset( $ultimatemember->fields->set_mode ) ) ? $ultimatemember->fields->set_mode : null;
		
		if ( $form_id && $mode ) {
		$array = $ultimatemember->query->get_attr('custom_fields', $form_id );
		} else {
			$array = '';
		}
		
		return $array;
		
	}