<?php

	/***
	***	@Support multisite
	***/
	add_filter('um_upload_basedir_filter','um_multisite_urls_support', 99 );
	add_filter('um_upload_baseurl_filter','um_multisite_urls_support', 99 );
	function um_multisite_urls_support( $dir ) {
		
		if ( is_multisite() ) { // Need to the work
		
			if ( get_current_blog_id() == '1' ) return $dir;
			
			$split = explode('sites/',$dir);
			$um_dir = 'ultimatemember/';
			$dir = $split[0] . $um_dir;

		}

		return $dir;
	}