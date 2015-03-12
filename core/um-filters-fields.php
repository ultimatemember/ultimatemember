<?php

	/***
	***	@field is required?
	***/
	add_filter('um_edit_label_all_fields', 'um_edit_label_all_fields', 10, 2);
	function um_edit_label_all_fields( $label, $data ) {
		
		$asterisk = um_get_option('form_asterisk');
		if ( $asterisk && isset( $data['required'] ) && $data['required'] == 1 )
			$label = $label . '<span class="um-req" title="'.__('Required','ultimatemember').'">*</span>';
		
		return $label;
	}
	
	/***
	***	@change birth date label in view
	***/
	add_filter('um_view_label_birth_date', 'um_view_label_birth_date');
	function um_view_label_birth_date( $label ) {
		$label = __('Age','ultimatemember');
		return $label;
	}
	
	/***
	***	@user's registration date
	***/
	add_filter('um_profile_field_filter_hook__user_registered', 'um_profile_field_filter_hook__user_registered', 99, 2);
	function um_profile_field_filter_hook__user_registered( $value, $data ) {
		$value = strtotime($value);
		$value = sprintf(__('Joined %s','ultimatemember'), date('d M Y', $value) );
		return $value;
	}
	
	/***
	***	@urls in description
	***/
	add_filter('um_profile_field_filter_hook__description', 'um_profile_field_filter_hook__description', 99, 2);
	add_filter('um_profile_field_filter_hook__textarea', 'um_profile_field_filter_hook__description', 99, 2);
	function um_profile_field_filter_hook__description( $value, $data ) {
		global $ultimatemember;
		
		if ( isset( $data ) && isset( $data['html'] ) && $data['html'] == 1 )
			return $value;
		
		$value = preg_replace('$(https?://[a-z0-9_./?=&#-]+)(?![^<>]*>)$i', ' <a href="$1" target="_blank">$1</a> ', $value." ");
		$value = preg_replace('$(www\.[a-z0-9_./?=&#-]+)(?![^<>]*>)$i', '<a target="_blank" href="http://$1">$1</a> ', $value." ");

		$value = wpautop($value);
		return $value;
	}
	
	/***
	***	@time
	***/
	add_filter('um_profile_field_filter_hook__time', 'um_profile_field_filter_hook__time', 99, 2);
	function um_profile_field_filter_hook__time( $value, $data ) {
		global $ultimatemember;
		$value = $ultimatemember->datetime->format( $value, $data['format'] );

		$value = str_replace('am', 'a.m.', $value );
		$value = str_replace('pm', 'p.m.', $value );
		return $value;
	}
	
	/***
	***	@date
	***/
	add_filter('um_profile_field_filter_hook__date', 'um_profile_field_filter_hook__date', 99, 2);
	function um_profile_field_filter_hook__date( $value, $data ) {
		global $ultimatemember;

		if ( $data['pretty_format'] == 1 ) {
			$value = $ultimatemember->datetime->get_age( $value );
		} else {
			$value = $ultimatemember->datetime->format( $value, $data['format'] );
		}
		
		return $value;
	}
	
	/***
	***	@file
	***/
	add_filter('um_profile_field_filter_hook__file', 'um_profile_field_filter_hook__file', 99, 2);
	function um_profile_field_filter_hook__file( $value, $data ) {
		global $ultimatemember;
		
		$uri = um_user_uploads_uri() . $value;
		$extension = pathinfo( $uri, PATHINFO_EXTENSION);

		if ( !file_exists( um_user_uploads_dir() . $value ) ) {
			$value = __('This file has been removed.');
		} else {
			$value = '<div class="um-single-file-preview show">
										<div class="um-single-fileinfo">
											<a href="' . $uri  . '" target="_blank">
												<span class="icon" style="background:'. $ultimatemember->files->get_fonticon_bg_by_ext( $extension ) . '"><i class="'. $ultimatemember->files->get_fonticon_by_ext( $extension ) .'"></i></span>
												<span class="filename">' . $value . '</span>
											</a>
										</div>
							</div>';
		}
		
		return $value;
	}
	
	/***
	***	@image
	***/
	add_filter('um_profile_field_filter_hook__image', 'um_profile_field_filter_hook__image', 99, 2);
	function um_profile_field_filter_hook__image( $value, $data ) {
	
		$uri = um_user_uploads_uri() . $value;
		$title = ( isset( $data['title'] ) ) ? $data['title'] : __('Untitled photo');
		
		if ( file_exists( um_user_uploads_dir() . $value ) ) {
			$value = '<div class="um-photo"><a href="#" class="um-photo-modal" data-src="'.$uri.'"><img src="'. $uri .'" alt="'.$title.'" title="'.$title.'" class="" /></a></div>';
		} else {
			$value = '';
		}
		
		return $value;
	}
	
	/***
	***	@global
	***/
	add_filter('um_profile_field_filter_hook__', 'um_profile_field_filter_hook__', 99, 2);
	function um_profile_field_filter_hook__( $value, $data ) {
		
		if ( !$value ) return '';

		if ( ( isset( $data['validate'] ) && $data['validate'] != '' && strstr( $data['validate'], 'url' ) ) || ( isset( $data['type'] ) && $data['type'] == 'url' ) ) {
			$alt = ( isset( $data['url_text'] ) && !empty( $data['url_text'] ) ) ? $data['url_text'] : $value;
			$url_rel = ( isset( $data['url_rel'] ) ) ? 'rel="nofollow"' : '';
			if( !strstr( $value, 'http' )
				&& !strstr( $value, '://' )
				&& !strstr( $value, 'www.' ) 
				&& !strstr( $value, '.com' ) 
				&& !strstr( $value, '.net' )
				&& !strstr( $value, '.org' )
			) {
				if ( $data['validate'] == 'soundcloud_url' ) $value = 'https://soundcloud.com/' . $value;
				if ( $data['validate'] == 'youtube_url' ) $value = 'https://youtube.com/user/' . $value;
				if ( $data['validate'] == 'facebook_url' ) $value = 'https://facebook.com/' . $value;
				if ( $data['validate'] == 'twitter_url' ) $value = 'https://twitter.com/' . $value;
				if ( $data['validate'] == 'linkedin_url' ) $value = 'https://linkedin.com/' . $value;
				if ( $data['validate'] == 'skype' ) $value = 'https://skype.com/' . $value;
				if ( $data['validate'] == 'googleplus_url' ) $value = 'https://plus.google.com/' . $value;
				if ( $data['validate'] == 'instagram_url' ) $value = 'https://instagram.com/' . $value;	
			}
			if ( strpos($value, 'http://') !== 0 ) {
				$value = 'http://' . $value;
			}
			$value = '<a href="'. $value .'" title="'.$alt.'" target="'.$data['url_target'].'" ' . $url_rel . '>'.$alt.'</a>';
		}
			
		if ( !is_array( $value ) ) {
			if ( is_email( $value ) )
				$value = '<a href="mailto:'. $value.'" title="'.$value.'">'.$value.'</a>';
		} else {
			$value = implode(', ', $value);
		}
		
		$value = str_replace('https://https://','https://',$value);
		$value = str_replace('http://https://','https://',$value);
		
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