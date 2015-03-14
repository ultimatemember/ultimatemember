<?php

require_once("../../../../../../wp-load.php");
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
		$file = str_replace(array('#','(',')','+','&','?','%','{','}','[',']','=',',',';','>','<','~',':','$',' '),'',$file);
		$file = strtolower($file);
		
		$error = $ultimatemember->files->check_image_upload( $temp, $id );
		if ( $error ){
			
			$ret['error'] = $error;
		
		} else {
			
			$ret[] = $ultimatemember->files->new_image_upload_temp( $temp, $file, um_get_option('image_compression') );
			
		}

    }
	
} else {
	$ret['error'] = __('Theme Compatibility Issue.','ultimatemember');
}
echo json_encode($ret);