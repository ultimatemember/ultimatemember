<?php

	/**
	 * Fallback for ajax urls
	 * @uses action hooks: wp_head, admin_head
	 */
	add_action('wp_head','ultimatemember_ajax_urls');
	add_action('admin_head','ultimatemember_ajax_urls');
	function ultimatemember_ajax_urls() { 

		$enable_ajax_urls = apply_filters("um_enable_ajax_urls", true );
		if( $enable_ajax_urls ){
	?>
 
		<script type="text/javascript">

		var ultimatemember_image_upload_url = '<?php echo um_url . 'core/lib/upload/um-image-upload.php'; ?>';
		var ultimatemember_file_upload_url = '<?php echo um_url . 'core/lib/upload/um-file-upload.php'; ?>';
		var ultimatemember_ajax_url = '<?php echo admin_url('admin-ajax.php'); ?>';

		</script>

	<?php
		}
	}

	/**
	 * Remove any files silently
	 * @uses action hooks: wp_ajax_nopriv_ultimatemember_remove_file, wp_ajax_ultimatemember_remove_file
	 * 
	 */
	add_action('wp_ajax_nopriv_ultimatemember_remove_file', 'ultimatemember_remove_file');
	add_action('wp_ajax_ultimatemember_remove_file', 'ultimatemember_remove_file');
	function ultimatemember_remove_file(){
		global $ultimatemember;
		extract($_REQUEST);
		$ultimatemember->files->delete_file( $src );
	}

	/**
	 * Removes profile photo silently
	 * @uses action hooks: wp_ajax_nopriv_ultimatemember_delete_profile_photo, wp_ajax_ultimatemember_delete_profile_photo
	 */
	add_action('wp_ajax_nopriv_ultimatemember_delete_profile_photo', 'ultimatemember_delete_profile_photo');
	add_action('wp_ajax_ultimatemember_delete_profile_photo', 'ultimatemember_delete_profile_photo');
	function ultimatemember_delete_profile_photo(){
		global $ultimatemember;
		extract($_REQUEST);

		if ( !um_current_user_can('edit', $user_id ) ) die( __('You can not edit this user') );

		$ultimatemember->files->delete_core_user_photo( $user_id, 'profile_photo' );

	}

	/**
	 * Remove cover photo silently
	 * @uses action hooks: wp_ajax_nopriv_ultimatemember_delete_cover_photo, wp_ajax_ultimatemember_delete_cover_photo
	 */
	add_action('wp_ajax_nopriv_ultimatemember_delete_cover_photo', 'ultimatemember_delete_cover_photo');
	add_action('wp_ajax_ultimatemember_delete_cover_photo', 'ultimatemember_delete_cover_photo');
	function ultimatemember_delete_cover_photo(){
		global $ultimatemember;
		extract($_REQUEST);

		if ( !um_current_user_can('edit', $user_id ) ) die( __('You can not edit this user') );

		$ultimatemember->files->delete_core_user_photo( $user_id, 'cover_photo' );

	}

	/**
	 * Resampling/crop images
	 * @uses action hooks: wp_ajax_nopriv_ultimatemember_resize_image, wp_ajax_ultimatemember_resize_image
	 */
	add_action('wp_ajax_nopriv_ultimatemember_resize_image', 'ultimatemember_resize_image');
	add_action('wp_ajax_ultimatemember_resize_image', 'ultimatemember_resize_image');
	function ultimatemember_resize_image(){
		global $ultimatemember;

		/**
		 * @var $key
		 * @var $src
		 * @var $coord
		 * @var $user_id
		 */
		extract( $_REQUEST );

		if ( ! isset( $src ) || ! isset( $coord ) ) {
			wp_send_json_error( esc_js( __( 'Invalid parameters', 'ultimate-member' ) ) );
		}

		$coord_n = substr_count( $coord, "," );
		if ( $coord_n != 3 ) {
			wp_send_json_error( esc_js( __( 'Invalid coordinates', 'ultimate-member' ) ) );
		}


		$image_path = um_is_file_owner( $src, $user_id, true );
		if ( ! $image_path ) {
			wp_send_json_error( esc_js( __( 'Invalid file ownership', 'ultimate-member' ) ) );
		}

		$output = $ultimatemember->uploader()->resize_image( $image_path, $src, $key, $user_id, $coord );

		delete_option( "um_cache_userdata_{$user_id}" );

		wp_send_json_success( $output );
	}



	/**
	 * Image upload by AJAX
	 */
	add_action('wp_ajax_nopriv_ultimatemember_image_upload', 'ajax_image_upload');
	add_action('wp_ajax_ultimatemember_image_upload', 'ajax_image_upload');
	function ajax_image_upload() {
		global $ultimatemember;

		$ret['error'] = null;
		$ret = array();

		$id = $_POST['key'];
		$timestamp = $_POST['timestamp'];
		$nonce = $_POST['_wpnonce'];
		$user_id = $_POST['user_id'];

		$ultimatemember->fields->set_id = $_POST['set_id'];
		$ultimatemember->fields->set_mode = $_POST['set_mode'];


		/**
		 * UM hook
		 *
		 * @type filter
		 * @title um_image_upload_nonce
		 * @description Change Image Upload nonce
		 * @input_vars
		 * [{"var":"$nonce","type":"bool","desc":"Nonce"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage
		 * <?php add_filter( 'um_image_upload_nonce', 'function_name', 10, 1 ); ?>
		 * @example
		 * <?php
		 * add_filter( 'um_image_upload_nonce', 'my_image_upload_nonce', 10, 1 );
		 * function my_image_upload_nonce( $nonce ) {
		 *     // your code here
		 *     return $nonce;
		 * }
		 * ?>
		 */
		$um_image_upload_nonce = apply_filters("um_image_upload_nonce", true );

		if(  $um_image_upload_nonce ){
			if ( ! wp_verify_nonce( $nonce, "um_upload_nonce-{$timestamp}" ) && is_user_logged_in() ) {
				// This nonce is not valid.
				$ret['error'] = 'Invalid nonce';
				wp_send_json_error( $ret );
			}
		}

		if( isset( $_FILES[ $id ]['name'] ) ) {

			if( ! is_array( $_FILES[ $id ]['name'] ) ) {

				$uploaded = $ultimatemember->uploader()->upload_image( $_FILES[ $id ], $user_id, $id );
				if ( isset( $uploaded['error'] ) ){

					$ret['error'] = $uploaded['error'];

				}else{
					$ts = current_time( 'timestamp' );
					$ret[ ] = $uploaded['handle_upload'];
				}

			}

		} else {
			$ret['error'] = __('A theme or plugin compatibility issue','ultimate-member');
		}

		wp_send_json_success( $ret );
	}



	add_action('wp_ajax_nopriv_ultimatemember_file_upload', 'ajax_image_upload');
	add_action('wp_ajax_ultimatemember_file_upload', 'ajax_image_upload');
	/**
	 *
	 */
	function ajax_file_upload(){
		global $ultimatemember;

		$ret['error'] = null;
		$ret = array();

		/* commented for enable download files on registration form
		 * if ( ! is_user_logged_in() ) {
			$ret['error'] = 'Invalid user';
			die( json_encode( $ret ) );
		}*/

		$nonce = $_POST['_wpnonce'];
		$id = $_POST['key'];
		$timestamp = $_POST['timestamp'];

		$ultimatemember->fields->set_id = $_POST['set_id'];
		$ultimatemember->fields->set_mode = $_POST['set_mode'];

		/**
		 * UM hook
		 *
		 * @type filter
		 * @title um_file_upload_nonce
		 * @description Change File Upload nonce
		 * @input_vars
		 * [{"var":"$nonce","type":"bool","desc":"Nonce"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage
		 * <?php add_filter( 'um_file_upload_nonce', 'function_name', 10, 1 ); ?>
		 * @example
		 * <?php
		 * add_filter( 'um_file_upload_nonce', 'my_file_upload_nonce', 10, 1 );
		 * function my_file_upload_nonce( $nonce ) {
		 *     // your code here
		 *     return $nonce;
		 * }
		 * ?>
		 */
		$um_file_upload_nonce = apply_filters("um_file_upload_nonce", true );

		if ( $um_file_upload_nonce  ) {
			if ( ! wp_verify_nonce( $nonce, 'um_upload_nonce-'.$timestamp  ) && is_user_logged_in() ) {
				// This nonce is not valid.
				$ret['error'] = 'Invalid nonce';
				wp_send_json_error( $ret );
			}
		}


		if( isset( $_FILES[ $id ]['name'] ) ) {

			if( ! is_array( $_FILES[ $id ]['name'] ) ) {
				$user_id = $_POST['user_id'];
				$uploaded = $ultimatemember->uploader()->upload_file( $_FILES[ $id ], $user_id, $id );
				if ( isset( $uploaded['error'] ) ){

					$ret['error'] = $uploaded['error'];

				}else{

					$uploaded_file = $uploaded['handle_upload'];
					$ret['url'] = $uploaded_file['file_info']['name'];
					$ret['icon'] = $ultimatemember->files->get_fonticon_by_ext( $uploaded_file['file_info']['ext'] );
					$ret['icon_bg'] = $ultimatemember->files->get_fonticon_bg_by_ext( $uploaded_file['file_info']['ext'] );
					$ret['filename'] = $uploaded_file['file_info']['basename'];
					$ret['original_name'] = $uploaded_file['file_info']['original_name'];


				}

			}

		} else {
			$ret['error'] = __('A theme or plugin compatibility issue','ultimate-member');
		}

		wp_send_json_success( $ret );
	}



/**
	 * Run an ajax action on the fly
	 * @uses action hooks: wp_ajax_nopriv_ultimatemember_muted_action, wp_ajax_ultimatemember_muted_action
	 */
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

	/**
	 * Run an ajax pagination on the fly
	 * @uses action hooks: wp_ajax_nopriv_ultimatemember_ajax_paginate, wp_ajax_ultimatemember_ajax_paginate
	 */
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

	/**
	 * Run check if username exists
	 * @uses action hooks: wp_ajax_nopriv_ultimatemember_check_username_exists, wp_ajax_ultimatemember_check_username_exists
	 * @return boolean
	 */
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

	/**
	 * Run an ajax to retrieve select options from a callback function
	 * @uses action hooks: wp_ajax_nopriv_ultimatemember_ajax_select_options, wp_ajax_ultimatemember_ajax_select_options
	 * @return json
	 */
	add_action('wp_ajax_nopriv_ultimatemember_ajax_select_options', 'ultimatemember_ajax_select_options');
	add_action('wp_ajax_ultimatemember_ajax_select_options', 'ultimatemember_ajax_select_options');
	function ultimatemember_ajax_select_options() {

		global $ultimatemember;
		
		$arr_options = array();
		$arr_options['status'] = 'success';
		$arr_options['post'] = $_POST;

		$ultimatemember->fields->set_id = intval( $_POST['form_id'] );
		$ultimatemember->fields->set_mode  = 'profile';
		$form_fields = $ultimatemember->fields->get_fields();
		$arr_options['fields'] = $form_fields;

		$debug = apply_filters('um_ajax_select_options__debug_mode', false );
		if( $debug ){
			$arr_options['debug'] = array(
				$_POST,
				$form_fields,
			);
		}

		if( isset( $_POST['child_callback'] ) && ! empty( $_POST['child_callback'] ) && isset( $form_fields[ $_POST['child_name'] ] )  ){
			
			$ajax_source_func = $_POST['child_callback'];
			
			// If the requested callback function is added in the form or added in the field option, execute it with call_user_func.
			if( isset( $form_fields[ $_POST['child_name'] ]['custom_dropdown_options_source'] ) && 
				! empty( $form_fields[ $_POST['child_name'] ]['custom_dropdown_options_source'] ) &&
				$form_fields[ $_POST['child_name'] ]['custom_dropdown_options_source'] == $ajax_source_func ){

				$arr_options['field'] = $form_fields[ $_POST['child_name'] ];
				if( function_exists( $ajax_source_func ) ){
					$arr_options['items'] = call_user_func( $ajax_source_func );
				}

			}else{
				$arr_options['status'] = 'error';
				$arr_options['message'] = __( 'This is not possible for security reasons.','ultimate-member');
			}

		}

		wp_send_json( $arr_options );
	}
