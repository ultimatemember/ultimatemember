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

$nonce = $_POST['_wpnonce'];
$id = $_POST['key'];
$timestamp = $_POST['timestamp'];

$ultimatemember->fields->set_id = $_POST['set_id'];
$ultimatemember->fields->set_mode = $_POST['set_mode'];

$um_file_upload_nonce = apply_filters("um_file_upload_nonce", true );

if(  $um_file_upload_nonce  ){
	if ( ! wp_verify_nonce( $nonce, 'um_upload_nonce-'.$timestamp  ) && is_user_logged_in()) {
	    // This nonce is not valid.
	    $ret['error'] = 'Invalid nonce';
	    die( json_encode( $ret ) );
	}
}



if(isset($_FILES[$id]['name'])) {

    if(!is_array($_FILES[$id]['name'])) {
	
		$temp = $_FILES[$id]["tmp_name"];
		$file = $_FILES[$id]["name"];
		$file = sanitize_file_name($file);
		$extension = strtolower( pathinfo($file, PATHINFO_EXTENSION) );
		
		$error = $ultimatemember->files->check_file_upload( $temp, $extension, $id );
		if ( $error ){
			$ret['error'] = $error;
		} else {
			$ret[] = $ultimatemember->files->new_file_upload_temp( $temp, $file );
			$ret['icon'] = $ultimatemember->files->get_fonticon_by_ext( $extension );
			$ret['icon_bg'] = $ultimatemember->files->get_fonticon_bg_by_ext( $extension );
			$ret['filename'] = $file;
		}

    }
	
} else {
	$ret['error'] = __('A theme or plugin compatibility issue','ultimatemember');
}
echo json_encode($ret);