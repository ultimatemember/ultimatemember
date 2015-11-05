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

$id = $_POST['key'];
$ultimatemember->fields->set_id = $_POST['set_id'];
$ultimatemember->fields->set_mode = $_POST['set_mode'];

$ret['error'] = null;
$ret = array();

if(isset($_FILES[$id]['name'])) {

    if(!is_array($_FILES[$id]['name'])) {
	
		$temp = $_FILES[$id]["tmp_name"];
		$file = $_FILES[$id]["name"];
		$file = sanitize_file_name($file);
		$extension = pathinfo($file, PATHINFO_EXTENSION);
		
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