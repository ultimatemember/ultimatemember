<?php

class UM_Files {

	function __construct() {

		add_action('init',  array(&$this, 'setup_paths'), 1);
		
		$this->fonticon = array(
			'pdf' => array('icon' => 'um-icon-file-pdf', 'color' => '#D24D4D' ),
			'txt' => array('icon' => 'um-icon-file-text' ),
			'csv' => array('icon' => 'um-icon-file-text' ),
			'doc' => array('icon' => 'um-icon-file-text', 'color' => '#2C95D5' ),
			'docx' => array('icon' => 'um-icon-file-text', 'color' => '#2C95D5' ),
			'odt' => array('icon' => 'um-icon-file-text', 'color' => '#2C95D5' ),
			'ods' => array('icon' => 'um-icon-file-binary', 'color' => '#51BA6A' ),
			'xls' => array('icon' => 'um-icon-file-binary', 'color' => '#51BA6A' ),
			'xlsx' => array('icon' => 'um-icon-file-binary', 'color' => '#51BA6A' ),
			'zip' => array('icon' => 'um-icon-file-zip' ),
			'rar' => array('icon' => 'um-icon-file-zip' ),
		);
		
		$this->default_file_fonticon = 'um-icon-doc';
	
	}
	
	/***
	***	@allowed image types
	***/
	function allowed_image_types() {
	
		$array['png'] = 'PNG';
		$array['jpeg'] = 'JPEG';
		$array['jpg'] = 'JPG';
		$array['gif'] = 'GIF';
		
		$array = apply_filters('um_allowed_image_types', $array);
		return $array;
	}
	
	/***
	***	@allowed file types
	***/
	function allowed_file_types() {
	
		$array['pdf'] = 'PDF';
		$array['txt'] = 'Text';
		$array['csv'] = 'CSV';
		$array['doc'] = 'DOC';
		$array['docx'] = 'DOCX';
		$array['odt'] = 'ODT';
		$array['ods'] = 'ODS';
		$array['xls'] = 'XLS';
		$array['xlsx'] = 'XLSX';
		$array['zip'] = 'ZIP';
		$array['rar'] = 'RAR';
		
		$array = apply_filters('um_allowed_file_types', $array);
		return $array;
	}
	
	/***
	***	@Get extension icon
	***/
	function get_fonticon_by_ext( $extension ) {
		if (isset($this->fonticon[$extension]['icon'])){
			return $this->fonticon[$extension]['icon'];
		} else {
			return $this->default_file_fonticon;
		}
	}
	
	/***
	***	@Get extension icon background
	***/
	function get_fonticon_bg_by_ext( $extension ) {
		if (isset($this->fonticon[$extension]['color'])){
			return $this->fonticon[$extension]['color'];
		} else {
			return '#666';
		}
	}
	
	/***
	***	@Setup upload directory
	***/
	function setup_paths(){
	
		$this->upload_dir = wp_upload_dir();
		
		$this->upload_basedir = $this->upload_dir['basedir'] . '/ultimate-member/';
		
		$this->upload_baseurl = $this->upload_dir['baseurl'] . '/ultimate-member/';

		// create plugin uploads directory
		if (!file_exists( $this->upload_basedir )) {
			@mkdir( $this->upload_basedir, 0777, true);
		}
		
	}

	/***
	***	@Return upload directory
	***/	
	function upload_dir(){
		return $this->upload_basedir;
	}

	/***
	***	@Return upload URL
	***/
	function upload_url(){
		return $this->upload_baseurl;
	}
	
	/***
	***	@Generate unique temp directory
	***/
	function unique_dir(){
		global $ultimatemember;
		$unique_number = $ultimatemember->validation->generate();
		$array['dir'] = $this->upload_basedir . 'temp/'. $unique_number . '/';
		$array['url'] = $this->upload_baseurl . 'temp/'. $unique_number . '/';
		return $array;
	}
	
	/***
	***	@Fix image orientation
	***/
	function fix_image_orientation(&$image, $source){
		$exif = @exif_read_data($source);

		if (isset($exif['Orientation'])) {
			switch ($exif['Orientation']) {
				case 3:
					$image = imagerotate($image, 180, 0);
					break;

				case 6:
					$image = imagerotate($image, -90, 0);
					break;

				case 8:
					$image = imagerotate($image, 90, 0);
					break;
			}
		}
		
		return $image;
	}
	
	/***
	***	@Process an image
	***/
	function create_and_copy_image($source, $destination, $quality = 100) {
		
		$info = getimagesize($source);
		
		if ($info['mime'] == 'image/jpeg'){
		
			$image = imagecreatefromjpeg($source);
	
		} else if ($info['mime'] == 'image/gif'){
		
			$image = imagecreatefromgif($source);

		} else if ($info['mime'] == 'image/png'){
		
			$image = imagecreatefrompng($source);

		}
		
		$this->fix_image_orientation($image, $source);
		
		imagejpeg($image, $destination, $quality);
				
	}
	
	/***
	***	@Process a file
	***/
	function upload_temp_file($source, $destination) {
		
		move_uploaded_file($source, $destination);
				
	}

	/***
	***	@Process a temp upload
	***/
	function new_image_upload_temp($source, $destination, $quality = 100){
	
		$unique_dir = $this->unique_dir();
		
		$this->make_dir( $unique_dir['dir'] );

		$this->create_and_copy_image($source, $unique_dir['dir'] . $destination, $quality);
		
		$url = $unique_dir['url'] . $destination;

		return $url;
		
	}
	
	/***
	***	@Process a temp upload for files
	***/
	function new_file_upload_temp($source, $destination ){
	
		$unique_dir = $this->unique_dir();
		
		$this->make_dir( $unique_dir['dir'] );

		$this->upload_temp_file($source, $unique_dir['dir'] . $destination);
		
		$url = $unique_dir['url'] . $destination;

		return $url;
		
	}
	
	/***
	***	@Make a Folder
	***/
	function make_dir( $dir ){
	
		@mkdir( $dir, 0777, true);
		
	}
	
	/***
	***	@Get extension by mime type
	***/
	function get_extension_by_mime_type($mime){
		$split = explode('/',$mime);
		return $split[1];
	}
	
	/***
	***	@Get file data
	***/
	function get_file_data($file){
	
		$array['size'] = filesize($file);

		return $array;
	}
	
	/***
	***	@Get image data
	***/
	function get_image_data($file){
	
		$array['size'] = filesize($file);
		
		$array['image'] = @getimagesize($file);
		
		if ( $array['image'] > 0 ) {
		
			$array['invalid_image'] = false;
			
			list($width, $height, $type, $attr) = @getimagesize($file);
			
			$array['width'] = $width;
			$array['height'] = $height;
			$array['ratio'] = $width / $height;
			
			$array['extension'] = $this->get_extension_by_mime_type( $array['image']['mime'] );
		
		} else {
		
			$array['invalid_image'] = true;
			
		}
		
		return $array;
	}
	
	/***
	***	@Check image upload and handle errors
	***/
	function check_image_upload($file, $field) {
		global $ultimatemember;
		$error = null;
		
		$fileinfo = $this->get_image_data($file);
		$data = $ultimatemember->fields->get_field($field);
		
		if ( $fileinfo['invalid_image'] == true ) {
			$error = $data['invalid_image'];
		} elseif ( !$this->in_array( $fileinfo['extension'], $data['allowed_types'] ) ) {
			$error = $data['extension_error'];
		} elseif ( isset($data['min_size']) && ( $fileinfo['size'] < $data['min_size'] ) ) {
			$error = $data['min_size_error'];
		}
		
		return $error;
	}
	
	/***
	***	@Check file upload and handle errors
	***/
	function check_file_upload($file, $extension, $field) {
		global $ultimatemember;
		$error = null;

		$fileinfo = $this->get_file_data($file);
		$data = $ultimatemember->fields->get_field($field);
		
		if ( !$this->in_array( $extension, $data['allowed_types'] ) ) {
			$error = $data['extension_error'];
		} elseif ( isset($data['min_size']) && ( $fileinfo['size'] < $data['min_size'] ) ) {
			$error = $data['min_size_error'];
		}
		
		return $error;
	}
	
	/***
	***	@If a value exists in comma seperated list
	***/
	function in_array($value, $array){
		if (in_array($value, explode(',',$array)))
			return true;
		return false;
	}
	
}