<?php
namespace um\core;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'um\core\Files' ) ) {


	/**
	 * Class Files
	 * @package um\core
	 */
	class Files {


		/**
		 * @var
		 */
		var $upload_temp;


		/**
		 * @var
		 */
		var $upload_baseurl;


		/**
		 * @var
		 */
		var $upload_basedir;


		/**
		 * Files constructor.
		 */
		function __construct() {

			$this->setup_paths();

			$this->fonticon = array(
				'pdf' 	=> array('icon' 	=> 'um-faicon-file-pdf-o', 'color' => '#D24D4D' ),
				'txt' 	=> array('icon' 	=> 'um-faicon-file-text-o' ),
				'csv' 	=> array('icon' 	=> 'um-faicon-file-text-o' ),
				'doc' 	=> array('icon' 	=> 'um-faicon-file-text-o', 'color' => '#2C95D5' ),
				'docx' 	=> array('icon' 	=> 'um-faicon-file-text-o', 'color' => '#2C95D5' ),
				'odt' 	=> array('icon' 	=> 'um-faicon-file-text-o', 'color' => '#2C95D5' ),
				'ods' 	=> array('icon' 	=> 'um-faicon-file-excel-o', 'color' => '#51BA6A' ),
				'xls' 	=> array('icon' 	=> 'um-faicon-file-excel-o', 'color' => '#51BA6A' ),
				'xlsx' 	=> array('icon' 	=> 'um-faicon-file-excel-o', 'color' => '#51BA6A' ),
				'zip' 	=> array('icon' 	=> 'um-faicon-file-zip-o' ),
				'rar' 	=> array('icon'		=> 'um-faicon-file-zip-o' ),
				'mp3'	=> array('icon'		=> 'um-faicon-file-audio-o' ),
				'jpg' 	=> array('icon' 	=> 'um-faicon-picture-o' ),
				'jpeg' 	=> array('icon' 	=> 'um-faicon-picture-o' ),
				'png' 	=> array('icon' 	=> 'um-icon-image' ),
				'gif' 	=> array('icon' 	=> 'um-icon-images' ),
				'eps' 	=> array('icon' 	=> 'um-icon-images' ),
				'psd' 	=> array('icon' 	=> 'um-icon-images' ),
				'tif' 	=> array('icon' 	=> 'um-icon-image' ),
				'tiff' 	=> array('icon' 	=> 'um-icon-image' ),
			);

			$this->default_file_fonticon = 'um-faicon-file-o';
		}


		/**
		 * Remove file by AJAX
		 */
		function ajax_remove_file() {
			/**
			 * @var $src
			 */
			extract( $_REQUEST );
			$this->delete_file( $src );
		}


		/**
		 * Resize image AJAX handler
		 */
		function ajax_resize_image() {
			$output = 0;

			extract( $_REQUEST );

			if ( !isset($src) || !isset($coord) ) die( __('Invalid parameters') );

			$coord_n = substr_count($coord, ",");
			if ( $coord_n != 3 ) die( __('Invalid coordinates') );

			$um_is_temp_image = um_is_temp_image( $src );
			if ( !$um_is_temp_image ) die( __('Invalid Image file') );

			$crop = explode(',', $coord );
			$crop = array_map('intval', $crop);

			$uri = UM()->files()->resize_image( $um_is_temp_image, $crop );

			// If you're updating a user
			if ( isset( $user_id ) && $user_id > 0 ) {
				$uri = UM()->files()->new_user_upload( $user_id, $um_is_temp_image, $key );
			}

			$output = $uri;

			delete_option( "um_cache_userdata_{$user_id}" );

			if(is_array($output)){ print_r($output); }else{ echo $output; } die;
		}


		/**
		 * Allowed image types
		 *
		 * @return array
		 */
		function allowed_image_types() {
			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_allowed_image_types
			 * @description Extend allowed image types
			 * @input_vars
			 * [{"var":"$types","type":"array","desc":"Image ext types"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_allowed_image_types', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_filter( 'um_allowed_image_types', 'my_allowed_image_types', 10, 1 );
			 * function my_allowed_image_types( $types ) {
			 *     // your code here
			 *     return $types;
			 * }
			 * ?>
			 */
			return apply_filters( 'um_allowed_image_types', array(
				'png'   => 'PNG',
				'jpeg'  => 'JPEG',
				'jpg'   => 'JPG',
				'gif'   => 'GIF'
			) );
		}


		/**
		 * Allowed file types
		 *
		 * @return mixed
		 */
		function allowed_file_types() {
			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_allowed_file_types
			 * @description Extend allowed File types
			 * @input_vars
			 * [{"var":"$types","type":"array","desc":"Files ext types"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_allowed_file_types', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_filter( 'um_allowed_file_types', 'my_allowed_file_types', 10, 1 );
			 * function my_allowed_file_types( $types ) {
			 *     // your code here
			 *     return $types;
			 * }
			 * ?>
			 */
			return apply_filters( 'um_allowed_file_types', array(
				'pdf'   => 'PDF',
				'txt'   => 'Text',
				'csv'   => 'CSV',
				'doc'   => 'DOC',
				'docx'  => 'DOCX',
				'odt'   => 'ODT',
				'ods'   => 'ODS',
				'xls'   => 'XLS',
				'xlsx'  => 'XLSX',
				'zip'   => 'ZIP',
				'rar'   => 'RAR',
				'mp3'   => 'MP3',
				'jpg'   => 'JPG',
				'jpeg'  => 'JPEG',
				'png'   => 'PNG',
				'gif'   => 'GIF',
				'eps'   => 'EPS',
				'psd'   => 'PSD',
				'tif'   => 'TIF',
				'tiff'  => 'TIFF',
			) );
		}


		/**
		 * Get extension icon
		 *
		 * @param $extension
		 *
		 * @return string
		 */
		function get_fonticon_by_ext( $extension ) {
			if ( isset( $this->fonticon[$extension]['icon'] ) ) {
				return $this->fonticon[$extension]['icon'];
			} else {
				return $this->default_file_fonticon;
			}
		}


		/**
		 * Get extension icon background
		 *
		 * @param $extension
		 *
		 * @return string
		 */
		function get_fonticon_bg_by_ext( $extension ) {
			if ( isset( $this->fonticon[$extension]['color'] ) ) {
				return $this->fonticon[$extension]['color'];
			} else {
				return '#666';
			}
		}


		/**
		 * Setup upload directory
		 */
		function setup_paths() {

			$this->upload_dir = wp_upload_dir();

			$this->upload_basedir = $this->upload_dir['basedir'] . '/ultimatemember/';
			$this->upload_baseurl = $this->upload_dir['baseurl'] . '/ultimatemember/';

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_upload_basedir_filter
			 * @description Change Uploads Basedir
			 * @input_vars
			 * [{"var":"$basedir","type":"string","desc":"Uploads basedir"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_upload_basedir_filter', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_filter( 'um_upload_basedir_filter', 'my_upload_basedir', 10, 1 );
			 * function my_upload_basedir( $basedir ) {
			 *     // your code here
			 *     return $basedir;
			 * }
			 * ?>
			 */
			$this->upload_basedir = apply_filters( 'um_upload_basedir_filter', $this->upload_basedir );
			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_upload_baseurl_filter
			 * @description Change Uploads Base URL
			 * @input_vars
			 * [{"var":"$baseurl","type":"string","desc":"Uploads base URL"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_upload_baseurl_filter', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_filter( 'um_upload_baseurl_filter', 'my_upload_baseurl', 10, 1 );
			 * function my_upload_baseurl( $baseurl ) {
			 *     // your code here
			 *     return $baseurl;
			 * }
			 * ?>
			 */
			$this->upload_baseurl = apply_filters( 'um_upload_baseurl_filter', $this->upload_baseurl );

			// @note : is_ssl() doesn't work properly for some sites running with load balancers
			// Check the links for more info about this bug
			// https://codex.wordpress.org/Function_Reference/is_ssl
			// http://snippets.webaware.com.au/snippets/wordpress-is_ssl-doesnt-work-behind-some-load-balancers/
			if ( is_ssl() || stripos( get_option( 'siteurl' ), 'https://' ) !== false
			     || ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' ) ) {
				$this->upload_baseurl = str_replace("http://", "https://",  $this->upload_baseurl);
			}

			$this->upload_temp = $this->upload_basedir . 'temp/';
			$this->upload_temp_url = $this->upload_baseurl . 'temp/';

			if ( ! file_exists( $this->upload_basedir ) ) {
				$old = umask(0);
				@mkdir( $this->upload_basedir, 0755, true );
				umask( $old );
			}

			if ( ! file_exists( $this->upload_temp ) ) {
				$old = umask(0);
				@mkdir( $this->upload_temp , 0755, true );
				umask( $old );
			}

		}


		/**
		 * Generate unique temp directory
		 *
		 * @return mixed
		 */
		function unique_dir(){
			$unique_number = UM()->validation()->generate();
			$array['dir'] = $this->upload_temp . $unique_number . '/';
			$array['url'] = $this->upload_temp_url . $unique_number . '/';
			return $array;
		}


		/**
		 * Get path only without file name
		 *
		 * @param $file
		 *
		 * @return string
		 */
		function path_only( $file ) {
			return trailingslashit( dirname( $file ) );
		}


		/**
		 * Fix image orientation
		 *
		 * @param $rotate
		 * @param $source
		 *
		 * @return resource
		 */
		function fix_image_orientation( $rotate, $source ) {
			if ( extension_loaded('exif') ){
				$exif = @exif_read_data( $source );

				if (isset($exif['Orientation'])) {
					switch ($exif['Orientation']) {
						case 3:
							$rotate = imagerotate($rotate, 180, 0);
							break;

						case 6:
							$rotate = imagerotate($rotate, -90, 0);
							break;

						case 8:
							$rotate = imagerotate($rotate, 90, 0);
							break;
					}
				}
			}
			return $rotate;
		}


		/**
		 * Process an image
		 *
		 * @param $source
		 * @param $destination
		 * @param int $quality
		 *
		 * @return array
		 */
		function create_and_copy_image($source, $destination, $quality = 100) {

			$info = @getimagesize($source);

			if ($info['mime'] == 'image/jpeg'){

				$image = imagecreatefromjpeg( $source );

			} else if ($info['mime'] == 'image/gif'){

				$image = imagecreatefromgif( $source );

			} else if ($info['mime'] == 'image/png'){

				$image = imagecreatefrompng( $source );
				imagealphablending( $image, false );
				imagesavealpha( $image, true );

			}

			list($w, $h) = @getimagesize( $source );
			if ( $w > UM()->options()->get('image_max_width') ) {

				$ratio = round( $w / $h, 2 );
				$new_w = UM()->options()->get('image_max_width');
				$new_h = round( $new_w / $ratio, 2 );

				if ( $info['mime'] == 'image/jpeg' ||  $info['mime'] == 'image/gif' ){

					$image_p = imagecreatetruecolor( $new_w, $new_h );
					imagecopyresampled( $image_p, $image, 0, 0, 0, 0, $new_w, $new_h, $w, $h );
					$image_p = $this->fix_image_orientation( $image_p, $source );

				}else if( $info['mime'] == 'image/png' ){

					$srcImage = $image;
					$targetImage = imagecreatetruecolor( $new_w, $new_h );
					imagealphablending( $targetImage, false );
					imagesavealpha( $targetImage, true );
					imagecopyresampled( $targetImage, $srcImage,   0, 0, 0, 0, $new_w, $new_h, $w, $h );

				}

				if ( $info['mime'] == 'image/jpeg' ){
					$has_copied = imagejpeg( $image_p, $destination, $quality );
				}else if ( $info['mime'] == 'image/gif' ){
					$has_copied = imagegif( $image_p, $destination );
				}else if ( $info['mime'] == 'image/png' ){
					$has_copied = imagepng( $targetImage, $destination, 0 ,PNG_ALL_FILTERS);
				}

				$info['um_has_max_width'] = 'custom';
				$info['um_has_copied'] = $has_copied ? 'yes':'no';

			} else {

				$image = $this->fix_image_orientation( $image, $source );

				if ( $info['mime'] == 'image/jpeg' ){
					$has_copied = imagejpeg( $image, $destination, $quality );
				}else if ( $info['mime'] == 'image/gif' ){
					$has_copied = imagegif( $image, $destination );
				}else if ( $info['mime'] == 'image/png' ){
					$has_copied = imagepng( $image , $destination , 0 ,PNG_ALL_FILTERS);
				}

				$info['um_has_max_width'] = 'default';
				$info['um_has_copied'] = $has_copied ? 'yes':'no';
			}

			return $info;
		}


		/**
		 * Process a file
		 *
		 * @param $source
		 * @param $destination
		 */
		function upload_temp_file( $source, $destination ) {
			move_uploaded_file( $source, $destination );
		}


		/**
		 * Process a temp upload
		 *
		 * @param $source
		 * @param $destination
		 * @param int $quality
		 *
		 * @return string
		 */
		function new_image_upload_temp( $source, $destination, $quality = 100 ){

			$unique_dir = $this->unique_dir();

			$this->make_dir( $unique_dir['dir'] );

			$info = $this->create_and_copy_image( $source, $unique_dir['dir'] . $destination, $quality );

			$url = $unique_dir['url'] . $destination ;

			return $url;

		}


		/**
		 * Process a temp upload for files
		 *
		 * @param $source
		 * @param $destination
		 *
		 * @return string
		 */
		function new_file_upload_temp( $source, $destination ){

			$unique_dir = $this->unique_dir();

			$this->make_dir( $unique_dir['dir'] );

			$this->upload_temp_file( $source, $unique_dir['dir'] . $destination );

			$url = $unique_dir['url'] . $destination;

			return $url;

		}


		/**
		 * Make a Folder
		 *
		 * @param $dir
		 */
		function make_dir( $dir ) {
			$old = umask(0);
			@mkdir( $dir, 0755, true);
			umask( $old );
		}


		/**
		 * Get extension by mime type
		 *
		 * @param $mime
		 *
		 * @return mixed
		 */
		function get_extension_by_mime_type( $mime ) {
			$split = explode('/', $mime );
			return $split[1];
		}


		/**
		 * Get file data
		 *
		 * @param $file
		 *
		 * @return mixed
		 */
		function get_file_data( $file ) {
			$array['size'] = filesize( $file );
			return $array;
		}


		/**
		 * Get image data
		 *
		 * @param $file
		 *
		 * @return mixed
		 */
		function get_image_data( $file ) {

			$array['size'] = filesize( $file );

			$array['image'] = @getimagesize( $file );

			if ( $array['image'] > 0 ) {

				$array['invalid_image'] = false;

				list($width, $height, $type, $attr) = @getimagesize( $file );

				$array['width'] = $width;
				$array['height'] = $height;
				$array['ratio'] = $width / $height;

				$array['extension'] = $this->get_extension_by_mime_type( $array['image']['mime'] );

			} else {

				$array['invalid_image'] = true;

			}

			return $array;
		}


		/**
		 * Check image upload and handle errors
		 *
		 * @param $file
		 * @param $field
		 *
		 * @return null|string|void
		 */
		function check_image_upload( $file, $field ) {
			$error = null;

			$fileinfo = $this->get_image_data( $file );
			$data = UM()->fields()->get_field( $field );

			if ( $data == null ) {
				/**
				 * UM hook
				 *
				 * @type filter
				 * @title um_custom_image_handle_{$field}
				 * @description Custom image handle
				 * @input_vars
				 * [{"var":"$data","type":"array","desc":"Image Data"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_filter( 'um_custom_image_handle_{$field}', 'function_name', 10, 1 );
				 * @example
				 * <?php
				 * add_filter( 'um_custom_image_handle_{$field}', 'my_custom_image_handle', 10, 1 );
				 * function my_custom_image_handle( $data ) {
				 *     // your code here
				 *     return $data;
				 * }
				 * ?>
				 */
				$data = apply_filters( "um_custom_image_handle_{$field}", array() );
				if ( ! $data ) {
					$error = __( 'This media type is not recognized.', 'ultimate-member' );
				}
			}

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_image_handle_global__option
			 * @description Custom image global handle
			 * @input_vars
			 * [{"var":"$data","type":"array","desc":"Image Data"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_image_handle_global__option', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_filter( 'um_image_handle_global__option', 'my_image_handle_global', 10, 1 );
			 * function my_image_handle_global( $data ) {
			 *     // your code here
			 *     return $data;
			 * }
			 * ?>
			 */
			$data = apply_filters("um_image_handle_global__option", $data );
			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_image_handle_{$field}__option
			 * @description Custom image handle for each $field
			 * @input_vars
			 * [{"var":"$data","type":"array","desc":"Image Data"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_image_handle_{$field}__option', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_filter( 'um_image_handle_{$field}__option', 'my_image_handle', 10, 1 );
			 * function my_image_handle( $data ) {
			 *     // your code here
			 *     return $data;
			 * }
			 * ?>
			 */
			$data = apply_filters( "um_image_handle_{$field}__option", $data );

			if ( $fileinfo['invalid_image'] == true ) {
				$error = sprintf(__('Your image is invalid or too large!','ultimate-member') );
			} elseif ( isset( $data['allowed_types'] ) && !$this->in_array( $fileinfo['extension'], $data['allowed_types'] ) ) {
				$error = ( isset( $data['extension_error'] ) && !empty( $data['extension_error'] ) ) ? $data['extension_error'] : 'not allowed';
			} elseif ( isset($data['min_size']) && ( $fileinfo['size'] < $data['min_size'] ) ) {
				$error = $data['min_size_error'];
			} elseif ( isset($data['min_width']) && ( $fileinfo['width'] < $data['min_width'] ) ) {
				$error = sprintf(__('Your photo is too small. It must be at least %spx wide.','ultimate-member'), $data['min_width']);
			} elseif ( isset($data['min_height']) && ( $fileinfo['height'] < $data['min_height'] ) ) {
				$error = sprintf(__('Your photo is too small. It must be at least %spx wide.','ultimate-member'), $data['min_height']);
			}

			return $error;
		}


		/**
		 * Check file upload and handle errors
		 *
		 * @param $file
		 * @param $extension
		 * @param $field
		 *
		 * @return null|string
		 */
		function check_file_upload( $file, $extension, $field ) {
			$error = null;

			$fileinfo = $this->get_file_data( $file );
			$data = UM()->fields()->get_field( $field );

			if ( !$this->in_array( $extension, $data['allowed_types'] ) ) {
				$error = ( isset( $data['extension_error'] ) && !empty( $data['extension_error'] ) ) ? $data['extension_error'] : 'not allowed';
			} elseif ( isset($data['min_size']) && ( $fileinfo['size'] < $data['min_size'] ) ) {
				$error = $data['min_size_error'];
			}

			return $error;
		}


		/**
		 * If a value exists in comma seperated list
		 *
		 * @param $value
		 * @param $array
		 *
		 * @return bool
		 */
		function in_array( $value, $array ){

			if ( in_array( $value, explode(',', $array ) ) ){
				return true;
			}

			return false;
		}


		/**
		 * This function will delete file upload from server
		 *
		 * @param $src
		 */
		function delete_file( $src ) {

			if ( strstr( $src, '?' ) ) {
				$splitted = explode( '?', $src );
				$src = $splitted[0];
			}

			$is_temp = um_is_temp_upload( $src );
			if ( $is_temp ) {
				unlink( $is_temp );
				rmdir( dirname( $is_temp ) );
			} else {
				wp_die( __('Ultimate Member: Not a valid temp file','ultimate-member') );
			}
		}


		/**
		 * Delete a main user photo
		 *
		 * @param $user_id
		 * @param $type
		 */
		function delete_core_user_photo( $user_id, $type ) {

			delete_user_meta( $user_id, $type );

			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_after_remove_{$type}
			 * @description Make some actions after remove file
			 * @input_vars
			 * [{"var":"$user_id","type":"int","desc":"User ID"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_after_remove_{$type}', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_action( 'um_after_remove_{$type}', 'my_after_remove_file', 10, 1 );
			 * function my_after_remove_file( $user_id ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( "um_after_remove_{$type}", $user_id );

			$dir = $this->upload_basedir . $user_id . '/';
			$prefix = $type;
			chdir($dir);
			$matches = glob($prefix.'*',GLOB_MARK);

			if( is_array($matches) && !empty($matches)) {
				foreach($matches as $match) {
					if( is_file($dir.$match) ) unlink($dir.$match);
				}
			}

			if ( count(glob("$dir/*")) === 0) {
				rmdir( $dir );
			}

		}


		/**
		 * Resize a local image
		 *
		 * @param $file
		 * @param $crop
		 *
		 * @return string
		 */
		function resize_image( $file, $crop ) {

			$targ_x1 = $crop[0];
			$targ_y1 = $crop[1];
			$targ_x2 = $crop[2];
			$targ_y2 = $crop[3];

			$info = @getimagesize( $file );

			if ( $info['mime'] == 'image/gif' ) {

				$img_r = imagecreatefromgif( $file );
				$dst_r = imagecreatetruecolor( $targ_x2, $targ_y2 );
				imagecopy( $dst_r, $img_r, 0, 0, $targ_x1, $targ_y1, $targ_x2, $targ_y2 );
				imagegif( $dst_r, $this->path_only( $file ) . basename( $file ) );

			} elseif ( $info['mime'] == 'image/png' ) {

				$img_r = imagecreatefrompng( $file );
				$dst_r = imagecreatetruecolor( $targ_x2, $targ_y2 );
				imagealphablending( $dst_r, false);
				imagesavealpha( $dst_r, true);
				imagecopy( $dst_r, $img_r, 0, 0, $targ_x1, $targ_y1, $targ_x2, $targ_y2 );
				imagepng( $dst_r, $this->path_only( $file ) . basename( $file ) );

			} else {

				$img_r = imagecreatefromjpeg( $file );
				$dst_r = imagecreatetruecolor( $targ_x2, $targ_y2 );
				imagecopy( $dst_r, $img_r, 0, 0, $targ_x1, $targ_y1, $targ_x2, $targ_y2 );
				imagejpeg( $dst_r, $this->path_only( $file ) . basename( $file ), 100 );

			}

			$split = explode('/ultimatemember/temp/', $file );
			return $this->upload_temp_url . $split[1];
		}


		/**
		 * Make a user folder for uploads
		 *
		 * @param $user_id
		 */
		function new_user( $user_id ) {
			if ( !file_exists( $this->upload_basedir . $user_id . '/' ) ) {
				$old = umask(0);
				@mkdir( $this->upload_basedir . $user_id . '/' , 0755, true);
				umask($old);
			}
		}


		/**
		 * New user upload
		 *
		 * @param $user_id
		 * @param $source
		 * @param $key
		 *
		 * @return string
		 */
		function new_user_upload( $user_id, $source, $key ) {

			if( ! is_numeric( $user_id ) ){
				wp_die( __("Invalid user ID: ".json_encode( $user_id )." ",'ultimate-member') );
			}

			$user_id = trim( $user_id );

			// if he does not have uploads dir yet
			$this->new_user( $user_id );

			if ( is_user_logged_in() && ( get_current_user_id() != $user_id ) && ! UM()->roles()->um_user_can( 'can_edit_everyone' ) ) {
				wp_die( __( 'Unauthorized to do this attempt.', 'ultimate-member' ) );
			}

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_allow_frontend_image_uploads
			 * @description Allow Fronend Image uploads
			 * @input_vars
			 * [{"var":"$allow","type":"bool","desc":"Allow"},
			 * {"var":"$user_id","type":"int","desc":"User ID"},
			 * {"var":"$key","type":"string","desc":"Field Key"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_allow_frontend_image_uploads', 'function_name', 10, 3 );
			 * @example
			 * <?php
			 * add_filter( 'um_allow_frontend_image_uploads', 'my_allow_frontend_image_uploads', 10, 3 );
			 * function my_allow_frontend_image_uploads( $data ) {
			 *     // your code here
			 *     return $data;
			 * }
			 * ?>
			 */
			$allow_frontend_image_uploads = apply_filters( 'um_allow_frontend_image_uploads', false, $user_id, $key );

			if ( $allow_frontend_image_uploads == false && ! is_user_logged_in() && ( $key == 'profile_photo' || $key == 'cover_photo' ) ) {
				wp_die( __('Unauthorized to do this attempt.','ultimate-member') );
			}

			$ext = '.' . pathinfo($source, PATHINFO_EXTENSION);

			// copy & overwrite file

			if( in_array( $key , array('profile_photo','cover_photo') ) ){
				$filename = $key . $ext;
				$name = $key;
			}else{
				$filename = basename( $source );
			}



			if ( file_exists( $this->upload_basedir . $user_id . '/' . $filename ) ) {
				unlink( $this->upload_basedir . $user_id . '/' . $filename );
			}
			copy( $source, $this->upload_basedir . $user_id . '/' . $filename );

			$info = @getimagesize( $source );

			// thumbs
			if ( $key == 'profile_photo' ) {

				list($w, $h) = @getimagesize( $source );


				$sizes = UM()->options()->get( 'photo_thumb_sizes' );
				foreach( $sizes as $size ) {

					$ratio = round( $w / $h, 2 );
					$height = round( $size / $ratio, 2 );

					if ( file_exists(  $this->upload_basedir . $user_id . '/' . $name . '-' . $size . $ext ) ) {
						unlink( $this->upload_basedir . $user_id . '/' . $name . '-' . $size . $ext );
					}

					if ( $size < $w ) {

						if ( $info['mime'] == 'image/jpeg' ){
							$thumb_s = imagecreatefromjpeg( $source );
							$thumb = imagecreatetruecolor( $size, $size );
							imagecopyresampled( $thumb, $thumb_s, 0, 0, 0, 0, $size, $size, $w, $h );
							imagejpeg( $thumb, $this->upload_basedir . $user_id . '/' . $name . '-' . $size . $ext, 100);
							imagejpeg( $thumb, $this->upload_basedir . $user_id . '/' . $name . $ext, 100);
						}else if ( $info['mime'] == 'image/png' ){
							$thumb_s  = imagecreatefrompng( $source );
							$thumb = imagecreatetruecolor( $size, $size );
							imagealphablending( $thumb, false);
							imagesavealpha( $thumb, true);
							imagecopyresampled( $thumb, $thumb_s, 0, 0, 0, 0, $size, $size, $w, $h );
							imagepng( $thumb, $this->upload_basedir . $user_id . '/' . $name . '-' . $size . $ext );
						}else if ( $info['mime'] == 'image/gif' ){
							$thumb_s = imagecreatefromgif( $source );
							$thumb = imagecreatetruecolor( $size, $size );
							imagecopyresampled( $thumb, $thumb_s, 0, 0, 0, 0, $size, $size, $w, $h );
							imagegif( $thumb, $this->upload_basedir . $user_id . '/' . $name . '-' . $size . $ext);
							imagegif( $thumb, $this->upload_basedir . $user_id . '/' . $name . $ext);
						}
					}

				}

				// removes a synced profile photo
				delete_user_meta( $user_id, 'synced_profile_photo' );

			} else if ( $key == 'cover_photo' ) {

				list($w, $h) = @getimagesize( $source );

				$sizes = UM()->options()->get( 'cover_thumb_sizes' );
				foreach ( $sizes as $size ) {

					$ratio = round( $w / $h, 2 );
					$height = round( $size / $ratio, 2 );

					if ( file_exists(  $this->upload_basedir . $user_id . '/' . $name . '-' . $size . $ext ) ) {
						unlink( $this->upload_basedir . $user_id . '/' . $name . '-' . $size . $ext );
					}

					if ( $size < $w ) {

						if ( $info['mime'] == 'image/jpeg' ){
							$thumb = imagecreatetruecolor( $size, $height );
							$thumb_s = imagecreatefromjpeg( $source );
							imagecopyresampled( $thumb, $thumb_s, 0, 0, 0, 0, $size, $height, $w, $h );
							imagejpeg( $thumb, $this->upload_basedir . $user_id . '/' . $name . '-' . $size . $ext, 100);
						}else if ( $info['mime'] == 'image/png' ){
							$thumb_s  = imagecreatefrompng( $source );
							$thumb = imagecreatetruecolor( $size, $height );
							imagealphablending( $thumb, false);
							imagesavealpha( $thumb, true);
							imagecopyresampled( $thumb, $thumb_s, 0, 0, 0, 0, $size, $height, $w, $h );
							imagepng( $thumb, $this->upload_basedir . $user_id . '/' . $name . '-' . $size . $ext );
						}else if ( $info['mime'] == 'image/gif' ){
							$thumb = imagecreatetruecolor( $size, $height );
							$thumb_s = imagecreatefromgif( $source );
							imagecopyresampled( $thumb, $thumb_s, 0, 0, 0, 0, $size, $height, $w, $h );
							imagegif( $thumb, $this->upload_basedir . $user_id . '/' . $name . '-' . $size . $ext);
						}
					}

				}

			}

			// clean up temp
			$dir = dirname( $source );
			unlink( $source );
			rmdir( $dir );

			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_before_upload_db_meta
			 * @description Update user's meta before upload
			 * @input_vars
			 * [{"var":"$user_id","type":"int","desc":"User ID"},
			 * {"var":"$key","type":"string","desc":"Meta key"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_before_upload_db_meta', 'function_name', 10, 2 );
			 * @example
			 * <?php
			 * add_action( 'um_before_upload_db_meta', 'my_before_upload_db_meta', 10, 2 );
			 * function my_before_upload_db_meta( $user_id, $key ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_before_upload_db_meta', $user_id, $key );
			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_before_upload_db_meta_{$key}
			 * @description Update user's meta before upload
			 * @input_vars
			 * [{"var":"$user_id","type":"int","desc":"User ID"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_before_upload_db_meta_{$key}', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_action( 'um_before_upload_db_meta_{$key}', 'my_before_upload_db_meta', 10, 1 );
			 * function my_before_upload_db_meta( $user_id ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( "um_before_upload_db_meta_{$key}", $user_id );

			update_user_meta( $user_id, $key, $filename );

			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_after_upload_db_meta
			 * @description Update user's meta before upload
			 * @input_vars
			 * [{"var":"$user_id","type":"int","desc":"User ID"},
			 * {"var":"$key","type":"string","desc":"Meta key"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_after_upload_db_meta', 'function_name', 10, 2 );
			 * @example
			 * <?php
			 * add_action( 'um_after_upload_db_meta', 'my_after_upload_db_meta', 10, 2 );
			 * function my_after_upload_db_meta( $user_id, $key ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_after_upload_db_meta', $user_id, $key );
			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_after_upload_db_meta_{$key}
			 * @description Update user's meta after upload
			 * @input_vars
			 * [{"var":"$user_id","type":"int","desc":"User ID"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_after_upload_db_meta_{$key}', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_action( 'um_after_upload_db_meta_{$key}', 'my_after_upload_db_meta', 10, 1 );
			 * function my_after_upload_db_meta( $user_id ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( "um_after_upload_db_meta_{$key}", $user_id );

			// the url of upload
			return $this->upload_baseurl . $user_id . '/' . $filename;

		}


		/**
		 * Remove a directory
		 *
		 * @param $dir
		 */
		function remove_dir( $dir ) {
			if ( file_exists( $dir ) ) {
				foreach(glob($dir . '/*') as $file) {
					if(is_dir($file)) $this->remove_dir($file); else unlink($file);
				} rmdir($dir);
			}
		}


		/**
		 * Format Bytes
		 *
		 * @param $size
		 * @param int $precision
		 *
		 * @return string
		 */
		function format_bytes( $size , $precision = 1 ) {

			$base = log($size, 1024);
			$suffixes = array('', 'kb', 'MB', 'GB', 'TB');
			$computed_size = round(pow(1024, $base - floor($base)), $precision);
			$unit = $suffixes[ floor($base) ];

			return   $computed_size.' '.$unit;

		}


		/**
		 * Image upload by AJAX
		 */
		function ajax_image_upload() {
			$ret['error'] = null;
			$ret = array();

			$id = $_POST['key'];
			$timestamp = $_POST['timestamp'];
			$nonce = $_POST['_wpnonce'];

			UM()->fields()->set_id = $_POST['set_id'];
			UM()->fields()->set_mode = $_POST['set_mode'];

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

					$error = UM()->files()->check_image_upload( $temp, $id );
					if ( $error ){

						$ret['error'] = $error;

					} else {
						$file = "stream_photo_".md5($file)."_".uniqid().".".$ext;
						$ret[ ] = UM()->files()->new_image_upload_temp( $temp, $file, UM()->options()->get('image_compression') );

					}

				}

			} else {
				$ret['error'] = __('A theme or plugin compatibility issue','ultimate-member');
			}
			echo json_encode($ret);
			exit;
		}


		/**
		 *
		 */
		function ajax_file_upload(){
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

			UM()->fields()->set_id = $_POST['set_id'];
			UM()->fields()->set_mode = $_POST['set_mode'];

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
				if ( ! wp_verify_nonce( $nonce, 'um_upload_nonce-'.$timestamp  ) && is_user_logged_in()) {
					// This nonce is not valid.
					$ret['error'] = 'Invalid nonce';
					die( json_encode( $ret ) );
				}
			}

			if(isset($_FILES[$id]['name'])) {

				if(!is_array($_FILES[$id]['name'])) {

					$temp = $_FILES[$id]["tmp_name"];
					/**
					 * UM hook
					 *
					 * @type filter
					 * @title um_upload_file_name
					 * @description Change File Upload nonce
					 * @input_vars
					 * [{"var":"$filename","type":"string","desc":"Filename"},
					 * {"var":"$id","type":"int","desc":"ID"},
					 * {"var":"$name","type":"string","desc":"Name"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage
					 * <?php add_filter( 'um_upload_file_name', 'function_name', 10, 3 ); ?>
					 * @example
					 * <?php
					 * add_filter( 'um_upload_file_name', 'my_upload_file_name', 10, 3 );
					 * function my_upload_file_name( $filename, $id, $name ) {
					 *     // your code here
					 *     return $filename;
					 * }
					 * ?>
					 */
					$file = apply_filters( 'um_upload_file_name', $id . "-" . $_FILES[$id]["name"], $id, $_FILES[$id]["name"] );
					$file = sanitize_file_name($file);
					$extension = strtolower( pathinfo($file, PATHINFO_EXTENSION) );

					$error = UM()->files()->check_file_upload( $temp, $extension, $id );
					if ( $error ){
						$ret['error'] = $error;
					} else {
						$ret[] = UM()->files()->new_file_upload_temp( $temp, $file );
						$ret['icon'] = UM()->files()->get_fonticon_by_ext( $extension );
						$ret['icon_bg'] = UM()->files()->get_fonticon_bg_by_ext( $extension );
						$ret['filename'] = $file;
					}

				}

			} else {
				$ret['error'] = __('A theme or plugin compatibility issue','ultimate-member');
			}
			echo json_encode($ret);
			exit;
		}
	}
}