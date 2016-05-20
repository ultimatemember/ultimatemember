<?php

	/***
	***	@fallback for ajax urls
	***/
	add_action('wp_head','ultimatemember_ajax_urls');
	add_action('admin_head','ultimatemember_ajax_urls');
	function ultimatemember_ajax_urls() { ?>

		<script type="text/javascript">

		var ultimatemember_image_upload_url = '<?php echo um_url . 'core/lib/upload/um-image-upload.php'; ?>';
		var ultimatemember_file_upload_url = '<?php echo um_url . 'core/lib/upload/um-file-upload.php'; ?>';
		var ultimatemember_ajax_url = '<?php echo admin_url('admin-ajax.php'); ?>';

		</script>

	<?php

	}

	/***
	***	@remove any file silently
	***/
	add_action('wp_ajax_nopriv_ultimatemember_remove_file', 'ultimatemember_remove_file');
	add_action('wp_ajax_ultimatemember_remove_file', 'ultimatemember_remove_file');
	function ultimatemember_remove_file(){
		global $ultimatemember;
		extract($_REQUEST);
		$ultimatemember->files->delete_file( $src );
	}

	/***
	***	@remove profile photo silently
	***/
	add_action('wp_ajax_nopriv_ultimatemember_delete_profile_photo', 'ultimatemember_delete_profile_photo');
	add_action('wp_ajax_ultimatemember_delete_profile_photo', 'ultimatemember_delete_profile_photo');
	function ultimatemember_delete_profile_photo(){
		global $ultimatemember;
		extract($_REQUEST);

		if ( !um_current_user_can('edit', $user_id ) ) die( __('You can not edit this user') );

		$ultimatemember->files->delete_core_user_photo( $user_id, 'profile_photo' );

	}

	/***
	***	@remove cover photo silently
	***/
	add_action('wp_ajax_nopriv_ultimatemember_delete_cover_photo', 'ultimatemember_delete_cover_photo');
	add_action('wp_ajax_ultimatemember_delete_cover_photo', 'ultimatemember_delete_cover_photo');
	function ultimatemember_delete_cover_photo(){
		global $ultimatemember;
		extract($_REQUEST);

		if ( !um_current_user_can('edit', $user_id ) ) die( __('You can not edit this user') );

		$ultimatemember->files->delete_core_user_photo( $user_id, 'cover_photo' );

	}

	/***
	***	@resampling/crop images
	***/
	add_action('wp_ajax_nopriv_ultimatemember_resize_image', 'ultimatemember_resize_image');
	add_action('wp_ajax_ultimatemember_resize_image', 'ultimatemember_resize_image');
	function ultimatemember_resize_image(){
		global $ultimatemember;
		$output = 0;

		extract($_REQUEST);

		if ( !isset($src) || !isset($coord) ) die( __('Invalid parameters') );

		$coord_n = substr_count($coord, ",");
		if ( $coord_n != 3 ) die( __('Invalid coordinates') );

		$um_is_temp_image = um_is_temp_image( $src );
		if ( !$um_is_temp_image ) die( __('Invalid Image file') );

		$crop = explode(',', $coord );
		$crop = array_map('intval', $crop);

		$uri = $ultimatemember->files->resize_image( $um_is_temp_image, $crop );

		// If you're updating a user
		if ( isset( $user_id ) && $user_id > 0 ) {
			$uri = $ultimatemember->files->new_user_upload( $user_id, $um_is_temp_image, $key );
		}

		$output = $uri;

		delete_option( "um_cache_userdata_{$user_id}" );

		if(is_array($output)){ print_r($output); }else{ echo $output; } die;

	}

	/***
	***	@run an ajax action on the fly
	***/
	add_action('wp_ajax_nopriv_ultimatemember_muted_action', 'ultimatemember_muted_action');
	add_action('wp_ajax_ultimatemember_muted_action', 'ultimatemember_muted_action');
	function ultimatemember_muted_action(){
		global $ultimatemember;
		extract($_REQUEST);

		if ( !um_current_user_can('edit', $user_id ) ) die( __('You can not edit this user') );

		switch( $hook ) {
			default:
				do_action("um_run_ajax_function__{$hook}", $_REQUEST);
				break;
		}

	}

	/***
	***	@run an ajax pagination on the fly
	***/
	add_action('wp_ajax_nopriv_ultimatemember_ajax_paginate', 'ultimatemember_ajax_paginate');
	add_action('wp_ajax_ultimatemember_ajax_paginate', 'ultimatemember_ajax_paginate');
	function ultimatemember_ajax_paginate(){
		global $ultimatemember;
		extract($_REQUEST);

		ob_start();

		do_action("um_ajax_load_posts__{$hook}", $args);

		$output = ob_get_contents();
		ob_end_clean();

		die($output);

	}

	/***
	***	@run check if username exists
	***/
	add_action('wp_ajax_nopriv_ultimatemember_check_username_exists', 'ultimatemember_check_username_exists');
	add_action('wp_ajax_ultimatemember_check_username_exists', 'ultimatemember_check_username_exists');
	function ultimatemember_check_username_exists() {
		$username = isset($_REQUEST['username']) ? $_REQUEST['username'] : '';
		$exists   = username_exists( $username );
		$exists   = apply_filters( 'um_validate_username_exists', $exists, $username );

		if( $exists ) {
			echo 1;
		} else {
			echo 0;
		}

		die();
	}
