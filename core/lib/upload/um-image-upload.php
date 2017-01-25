<?php

$i = 0;
$dirname = dirname( __FILE__ );
do {
	$dirname = dirname( $dirname );
	$wp_load = "{$dirname}/wp-load.php";
}
while( ++$i < 10 && !file_exists( $wp_load ) );
require_once( $wp_load );
global $ultimatemember;

$ret['error'] = null;
$ret = array();

$id = $_POST['key'];
$timestamp = $_POST['timestamp'];
$nonce = $_POST['_wpnonce'];

$ultimatemember->fields->set_id = $_POST['set_id'];
$ultimatemember->fields->set_mode = $_POST['set_mode'];

$um_image_upload_nonce = apply_filters("um_image_upload_nonce", true );

if(  $um_image_upload_nonce ){
	if ( ! wp_verify_nonce( $nonce, 'um_upload_nonce-'.$timestamp ) && is_user_logged_in() ) {
	    // This nonce is not valid.
	    $ret['error'] = 'Invalid nonce';
	    die( json_encode( $ret ) );
	}
}

if(isset($_FILES[$id]['name'])) {

    if(!is_array($_FILES[$id]['name'])) {
	
		$temp = $_FILES[$id]["tmp_name"];
		$file = $id."-".$_FILES[$id]["name"];
		$file = sanitize_file_name($file);
		 $ext = strtolower( pathinfo($file, PATHINFO_EXTENSION) );

		$error = $ultimatemember->files->check_image_upload( $temp, $id );
		if ( $error ){
			
			$ret['error'] = $error;
		
		} else {
			$file = "stream_photo_".md5($file)."_".uniqid().".".$ext;
			$ret[ ] = $ultimatemember->files->new_image_upload_temp( $temp, $file, um_get_option('image_compression') );
			
		}

    }
	
} else {
	$ret['error'] = __('A theme or plugin compatibility issue','ultimatemember');
}
echo json_encode($ret);
