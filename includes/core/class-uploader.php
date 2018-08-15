<?php
namespace um\core;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'um\core\Uploader' ) ) {


	/**
	 * Class Uploader
	 * @package um\core
	 */
	class Uploader {

		/**
		 * @var integer
		 */
		var $user_id;

		/**
		 * @var string
		 */
		var $field_key;

		/**
		 * @var string
		 */
		var $wp_upload_dir;

		/**
		 * @var string
		 */
		var $temp_upload_dir;

		/**
		 * @var string
		 */
		var $core_upload_dir;

		/**
		 * @var string
		 */
		var $upload_baseurl;

		/**
		 * @var string
		 */
		var $upload_basedir;

		/**
		 * @var string
		 */
		var $upload_user_baseurl;

		/**
		 * @var string
		 */
		var $upload_user_basedir;

		/**
		 * @var string
		 */
		var $upload_image_type;

		/**
		 * @var string
		 */
		var $upload_type;



		/**
		 * Uploader constructor.
		 */
		function __construct() {

			$this->core_upload_dir = "/ultimatemember/";
			$this->upload_image_type = 'stream_photo';
			$this->wp_upload_dir = wp_upload_dir(); 
			$this->temp_upload_dir = "temp";

			add_filter("upload_dir", array( $this, "set_upload_directory" ), 10, 1 );
			add_filter("wp_handle_upload_prefilter", array( $this, "validate_upload" ) );
			add_filter("um_upload_image_process__profile_photo", array( $this, "profile_photo" ),  10, 6 );
			add_filter("um_upload_image_process__cover_photo", array( $this, "cover_photo" ), 10, 6 );
			add_filter("um_upload_stream_image_process", array( $this, "stream_photo" ), 10, 6 );
			add_filter("um_custom_image_handle_wall_img_upload", array( $this, "stream_photo_data"), 10, 1 );

			add_action("init", array( $this, "init" ) );

		}

		/**
		 * Init
		 */
		function init(){
			$this->user_id = get_current_user_id();	
		}

		/**
		 * Get core temporary directory path
		 *
		 * @since 2.0.22 
		 * @return string
		 */
		public function get_core_temp_dir(){

			return  $this->get_upload_base_dir(). $this->temp_upload_dir;
		}

		/**
		 * Get core temporary directory URL
		 *
		 * @since 2.0.22 
		 * @return string
		 */
		public function get_core_temp_url(){

			return  $this->get_upload_base_url(). $this->temp_upload_dir;
		}

		/**
		 * Get core upload directory
		 *
		 * @since 2.0.22 
		 * @return string
		 */
		public function get_core_upload_dir(){
			
			return $this->core_upload_dir;

		}

		/**
		 * Get core upload base url
		 *
		 * @since 2.0.22 
		 * @return string
		 */
		public function get_upload_base_url(){

			$wp_baseurl = $this->wp_upload_dir['baseurl'];

			$this->upload_baseurl = $wp_baseurl . $this->core_upload_dir;

			return $this->upload_baseurl;

		}

		/**
		 * Get core upload  base directory
		 *
		 * @since 2.0.22 
		 * @return string
		 */
		public function get_upload_base_dir(){
			
			$wp_basedir = $this->wp_upload_dir['basedir'];

			$this->upload_basedir = $wp_basedir . $this->core_upload_dir;
	
			return $this->upload_basedir;
		}

		/**
		 * Get user upload base directory
		 *
		 * @param integer $user_id
		 * @since 2.0.22 
		 * @return string
		 */
		public function get_upload_user_base_dir( $user_id = null, $create_dir = false){

			if( $user_id ){
				$this->user_id = $user_id;
			}

			$this->upload_user_basedir	= $this->get_upload_base_dir() . $this->user_id;

			if( $create_dir  ){
				wp_mkdir_p( $this->upload_user_basedir );  
			}

			return $this->upload_user_basedir;

		}

		/**
		 * Get user upload base url
		 * 
		 * @param integer $user_id
		 * @since 2.0.22 
		 * @return string
		 */
		public function get_upload_user_base_url( $user_id = null ){

			if( $user_id ){
				$this->user_id = $user_id;
			}

			$this->upload_user_baseurl	= $this->get_upload_base_url() . $this->user_id;

			return $this->upload_user_baseurl;

		}
		
		/**
		 * Validate file size
		 * @param  array $file 
		 * @return array
		 */
		public function validate_upload( $file ) {

			$error = false;
			if ( 'image' == $this->upload_type ) {
				$error = $this->validate_image_data( $file['tmp_name'], $this->field_key );
			} elseif( 'file' == $this->upload_type ) {
				$error = $this->validate_file_data( $file['tmp_name'], $this->field_key );
			}

			if ( $error ) {
				$file['error'] = $error;
			}

			return $file;
		}

		/**
		 * Set upload directory
		 *
		 * @param array $args
		 *
		 * @return array
		 */
		public function set_upload_directory( $args ){

			$this->upload_baseurl = $args['baseurl'] . $this->core_upload_dir;
			$this->upload_basedir = $args['basedir'] . $this->core_upload_dir;

			if( 'image' == $this->upload_type && 'wall_img_upload' != $this->field_key && is_user_logged_in() ){
				$this->upload_user_baseurl	= $this->upload_baseurl . $this->user_id;
				$this->upload_user_basedir	= $this->upload_basedir . $this->user_id;
			}else{
				$this->upload_user_baseurl	= $this->upload_baseurl . $this->temp_upload_dir;
				$this->upload_user_basedir	= $this->upload_basedir . $this->temp_upload_dir;
			}

			$args['path'] = $this->upload_user_basedir;
			$args['url'] = $this->upload_user_baseurl;

			return $args;
		}

		/**
		 * Upload Image files
		 *
		 * @param $uploadedfile
		 * @param int|null $user_id
		 * @param string $field_key
		 * @param string $upload_type
		 *
		 * @since  2.0.22
		 *
		 * @return array
		 */
		public function upload_image( $uploadedfile, $user_id = null, $field_key = '', $upload_type = 'stream_photo' ) {


			$response = array();

			if ( ! function_exists( 'wp_handle_upload' ) ) {
			    require_once( ABSPATH . 'wp-admin/includes/file.php' );
			}

			if( empty( $field_key ) ){
				$field_key = "custom_field";
			}

			$this->field_key = $field_key;

			$this->upload_type = 'image';

			$this->upload_image_type = $upload_type;

			if( $user_id && is_user_logged_in() ){
				$this->user_id = $user_id;
			}
			
			if( in_array( $field_key, array( 'profile_photo','cover_photo' ) ) ){
				$this->upload_image_type = $field_key;
			} 

			$field_data = UM()->fields()->get_field( $field_key );
			
			if( isset( $field_data['allowed_types'] ) && ! empty( $field_data['allowed_types'] ) ){
				$field_allowed_file_types = explode(",", $field_data['allowed_types'] );
			}else{
				$field_allowed_file_types = apply_filters("um_uploader_image_default_filetypes", array('JPG','JPEG','PNG','GIF') );
			}

			$allowed_image_mimes = array();
			
			foreach( $field_allowed_file_types as $a ){
				$atype = wp_check_filetype( "test.{$a}" );
				$allowed_image_mimes[ $atype['ext'] ] = $atype['type']; 
			}

  			$image_compression = UM()->options()->get('image_compression');

			$upload_overrides = array(
			    'test_form' => false,
			    'mimes' => apply_filters( "um_uploader_allowed_image_mimes", $allowed_image_mimes ),
			    'unique_filename_callback' => array( $this, "unique_filename"),
			);

			$upload_overrides = apply_filters( "um_image_upload_handler_overrides__{$field_key}", $upload_overrides );

			$movefile = wp_handle_upload( $uploadedfile, $upload_overrides );

			if ( isset( $movefile['error'] ) ) {
			    /*
			     * Error generated by _wp_handle_upload()
			     * @see _wp_handle_upload() in wp-admin/includes/file.php
			     */
			    $response['error'] = $movefile['error'];
			}else{

				$movefile['url'] = set_url_scheme( $movefile['url'] );

				$movefile['file_info']['basename'] = wp_basename( $movefile['file'] );
				
				$file_type = wp_check_filetype( $movefile['file_info']['basename'] );
				
				$movefile['file_info']['name'] = $movefile['url'];
				$movefile['file_info']['original_name'] = $uploadedfile['name'];
				$movefile['file_info']['ext'] = $file_type['ext'];
				$movefile['file_info']['type'] = $file_type['type'];
				$movefile['file_info']['size'] = filesize( $movefile['file'] );
				$movefile['file_info']['size_format'] = size_format( $movefile['file_info']['size'] );
				$movefile['file'] = $movefile['file_info']['basename'];


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
				 * function my_before_upload_db_meta( $user_id, $field_key ) {
				 *     // your code here
				 * }
				 * ?>
				 */
				do_action( 'um_before_upload_db_meta', $this->user_id, $field_key );
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
				do_action( "um_before_upload_db_meta_{$field_key}", $this->user_id );

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
				 * function my_after_upload_db_meta( $user_id, $field_key ) {
				 *     // your code here
				 * }
				 * ?>
				 */
				do_action( 'um_after_upload_db_meta', $this->user_id, $field_key );
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
				do_action( "um_after_upload_db_meta_{$field_key}", $this->user_id );

				update_user_meta( $this->user_id, $field_key, wp_basename( $movefile['url'] ) );

				$filename = wp_basename( $movefile['url'] );
				
				set_transient( "um_{$filename}", $movefile['file_info'], 2 * HOUR_IN_SECONDS );
				

			}

			$response['handle_upload'] = $movefile;

			return $response;
		}


		/**
		 * Upload Files
		 *
		 * @param $uploadedfile
		 * @param int|null $user_id
		 * @param string $field_key
		 *
		 * @since  2.0.22
		 *
		 * @return array
		 */
		public function upload_file( $uploadedfile, $user_id = null, $field_key = '' ){
			$response = array();

			if ( ! function_exists( 'wp_handle_upload' ) ) {
			    require_once( ABSPATH . 'wp-admin/includes/file.php' );
			}

			$this->field_key = $field_key;

			if ( $user_id && is_user_logged_in() ) {
				$this->user_id = $user_id;
			}

			$this->upload_type = 'file';

			$field_data = UM()->fields()->get_field( $field_key );
			
			$field_allowed_file_types = explode(",", $field_data['allowed_types'] );

			$allowed_file_mimes = array();
			
			foreach ( $field_allowed_file_types as $a ) {
				$atype = wp_check_filetype( "test.{$a}" );
				$allowed_file_mimes[ $atype['ext'] ] = $atype['type']; 
			}

			$upload_overrides = array(
			    'test_form' => false,
			    'mimes' => apply_filters( "um_uploader_allowed_file_mimes", $allowed_file_mimes ),
			    'unique_filename_callback' => array( $this, "unique_filename"),
			);

			$upload_overrides = apply_filters( "um_file_upload_handler_overrides__{$field_key}", $upload_overrides );

			$movefile = wp_handle_upload( $uploadedfile, $upload_overrides );

			if ( isset( $movefile['error'] ) ) {
			    /*
			     * Error generated by _wp_handle_upload()
			     * @see _wp_handle_upload() in wp-admin/includes/file.php
			     */
			    $response['error'] = $movefile['error'];
			} else {

				$file_type = wp_check_filetype( $movefile['file'] );

				$movefile['url'] = set_url_scheme( $movefile['url'] );

				$movefile['file_info']['name'] = $movefile['url'];
				$movefile['file_info']['original_name'] = $uploadedfile['name'];
				$movefile['file_info']['basename'] = wp_basename( $movefile['file'] );
				$movefile['file_info']['ext'] = $file_type['ext'];
				$movefile['file_info']['type'] = $file_type['type'];
				$movefile['file_info']['size'] = filesize( $movefile['file'] );
				$movefile['file_info']['size_format'] = size_format( $movefile['file_info']['size'] );
				

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
				 * function my_before_upload_db_meta( $user_id, $field_key ) {
				 *     // your code here
				 * }
				 * ?>
				 */
				do_action( 'um_before_upload_db_meta', $this->user_id, $field_key );
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
				do_action( "um_before_upload_db_meta_{$field_key}", $this->user_id );

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
				 * function my_after_upload_db_meta( $user_id, $field_key ) {
				 *     // your code here
				 * }
				 * ?>
				 */
				do_action( 'um_after_upload_db_meta', $this->user_id, $field_key );
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
				do_action( "um_after_upload_db_meta_{$field_key}", $this->user_id );

				update_user_meta( $this->user_id, $field_key, wp_basename( $movefile['url'] ) );

				$filename = wp_basename( $movefile['url'] );

				set_transient( "um_{$filename}", $movefile['file_info'], 2 * HOUR_IN_SECONDS );
				

			}

			$response['handle_upload'] = $movefile;

			return $response;
		}


		/**
		 * Check image upload and handle errors
		 *
		 * @param $file
		 * @param $field
		 *
		 * @return null|string
		 */
		public function validate_image_data( $file, $field_key ) {
			$error = null;

			if ( ! function_exists( 'wp_get_image_editor' ) ) {
			    require_once( ABSPATH . 'wp-admin/includes/media.php' );
			}

			$image = wp_get_image_editor( $file );
			if ( is_wp_error( $image ) ) {
				$error = sprintf( __( 'Your image is invalid!', 'ultimate-member' ) );
				return $error;
			}

			$image_sizes = $image->get_size();
			$image_info['width'] = $image_sizes['width'];
			$image_info['height'] = $image_sizes['height'];
			$image_info['ratio'] = $image_sizes['width'] / $image_sizes['height'];

			$image_info['quality'] = $image->get_quality();
			
			$image_type = wp_check_filetype( $file );
			$image_info['extension'] = $image_type['ext'];
			$image_info['mime']= $image_type['type'];
			$image_info['size'] = filesize( $file );
			

			$data = UM()->fields()->get_field( $field_key );

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
				$data = apply_filters( "um_custom_image_handle_{$field_key}", array() );
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
			$data = apply_filters( "um_image_handle_{$field_key}__option", $data );

			if ( isset( $image_info['invalid_image'] ) && $image_info['invalid_image'] == true ) {
				$error = sprintf(__('Your image is invalid or too large!','ultimate-member') );
			} elseif ( isset($data['min_size']) && ( $image_info['size'] < $data['min_size'] ) ) {
				$error = $data['min_size_error'];
			} elseif ( isset($data['max_file_size']) && ( $image_info['size'] > $data['max_file_size'] ) ) {
				$error = $data['max_file_size_error'];
			} elseif ( isset($data['min_width']) && ( $image_info['width'] < $data['min_width'] ) ) {
				$error = sprintf(__('Your photo is too small. It must be at least %spx wide.','ultimate-member'), $data['min_width']);
			} elseif ( isset($data['min_height']) && ( $image_info['height'] < $data['min_height'] ) ) {
				$error = sprintf(__('Your photo is too small. It must be at least %spx wide.','ultimate-member'), $data['min_height']);
			}


			return $error;
		}


		/**
		 * Check file upload and handle errors
		 *
		 * @param $file
		 * @param $field
		 *
		 * @return null|string
		 */
		public function validate_file_data( $file, $field_key ) {
			$error = null;

			if ( ! function_exists( 'wp_get_image_editor' ) ) {
			    require_once( ABSPATH . 'wp-admin/includes/media.php' );
			}

			$file_type = wp_check_filetype( $file );
			$file_info = array();
			$file_info['extension'] = $file_type['ext'];
			$file_info['mime']= $file_type['type'];
			$file_info['size'] = filesize( $file );
			
			$data = UM()->fields()->get_field( $field_key );

			if ( $data == null ) {
				/**
				 * UM hook
				 *
				 * @type filter
				 * @title um_custom_file_handle_{$field}
				 * @description Custom file handle
				 * @input_vars
				 * [{"var":"$data","type":"array","desc":"Image Data"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_filter( 'um_custom_file_handle_{$field}', 'function_name', 10, 1 );
				 * @example
				 * <?php
				 * add_filter( 'um_custom_file_handle_{$field}', 'my_custom_file_handle', 10, 1 );
				 * function my_custom_file_handle( $data ) {
				 *     // your code here
				 *     return $data;
				 * }
				 * ?>
				 */
				$data = apply_filters( "um_custom_file_handle_{$field_key}", array() );
				if ( ! $data ) {
					$error = __( 'This file type is not recognized.', 'ultimate-member' );
				}
			}

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_file_handle_global__option
			 * @description Custom file global handle
			 * @input_vars
			 * [{"var":"$data","type":"array","desc":"Image Data"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_file_handle_global__option', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_filter( 'um_file_handle_global__option', 'my_file_handle_global', 10, 1 );
			 * function my_file_handle_global( $data ) {
			 *     // your code here
			 *     return $data;
			 * }
			 * ?>
			 */
			$data = apply_filters("um_file_handle_global__option", $data );
			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_file_handle_{$field}__option
			 * @description Custom file handle for each $field
			 * @input_vars
			 * [{"var":"$data","type":"array","desc":"Image Data"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_file_handle_{$field}__option', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_filter( 'um_file_handle_{$field}__option', 'my_file_handle', 10, 1 );
			 * function my_file_handle( $data ) {
			 *     // your code here
			 *     return $data;
			 * }
			 * ?>
			 */
			$data = apply_filters( "um_file_handle_{$field_key}__option", $data );

			if ( isset( $data['max_file_size'] ) && ( $file_info['size'] > $data['max_file_size'] ) ) {
				$error = $data['max_file_size_error'];
			}

			return $error;
		}



		/**
		 * Make unique filename
		 * @param  string $filename 
		 * @param  string $ext      
		 * @param  string $dir      
		 * @return string $filename
		 *
		 * @since  2.0.22 
		 */
		public function unique_filename( $filename, $ext, $dir ){

			$image_type = wp_check_filetype( $ext );
				
			$ext = $image_type['ext'];

			if( 'image' == $this->upload_type ){

				switch( $this->upload_image_type ){

					case 'stream_photo':
						$hashed = hash('ripemd160', time(). mt_rand(10,1000) );
						$filename = "stream_photo_{$hashed}.{$ext}";
					break;

					case 'profile_photo':
						$filename = "profile_photo.{$ext}";
					break;

					case 'cover_photo':
						$filename = "cover_photo.{$ext}";
					break;

				}

			}else if( 'file' == $this->upload_type ){
					$hashed = hash('ripemd160', time(). mt_rand(10,1000) );
					$filename = "file_{$hashed}.{$ext}";
					
			}

			$this->delete_existing_file( $filename, $ext, $dir  );

			return $filename;
		}

		/**
		 * Delete file
		 * @param  string $filename 
		 * @param  string $ext      
		 * @param  string $dir      
		 *   
		 * @since 2.0.22
		 */
		public function delete_existing_file( $filename, $ext = '', $dir = ''  ){
			
			if( file_exists( $this->upload_user_basedir . DIRECTORY_SEPARATOR . $filename  ) && ! empty( $filename ) ){
				unlink( $this->upload_user_basedir . DIRECTORY_SEPARATOR . $filename  );
			}

		}

		/**
		 * Profile photo image process
		 * @param  string $src     
		 * @param  integer $user_id 
		 * @param  string $coord   
		 * @param  array $crop  
		 *   
		 * @since 2.0.22
		 */
		public function profile_photo( $image_path, $src, $key, $user_id, $coord, $crop ){

			$sizes = UM()->options()->get( 'photo_thumb_sizes' );
		
			$quality = UM()->options()->get( 'image_compression' );

			$image = wp_get_image_editor( $image_path ); // Return an implementation that extends WP_Image_Editor

			if ( ! is_wp_error( $image ) ) {
					
				$src_x = $crop[0];
				$src_y = $crop[1];
				$src_w = $crop[2];
				$src_h = $crop[3];

				$image->crop( $src_x, $src_y, $src_w, $src_h );

				$image->save( $image_path );

				$image->set_quality( $quality );

				$sizes_array = array();

				foreach( $sizes as $size ){
					$sizes_array[ ] = array ('width' => $size );
				}

				$image->multi_resize( $sizes_array );

				delete_user_meta( $user_id, 'synced_profile_photo' );

			}else{

				wp_send_json_error( esc_js( __( "Unable to crop image file: {$src}", 'ultimate-member' ) ) );		
	
			}	
	
		}


		/**
		 * Cover photo image process
		 * @param  string $src     
		 * @param  integer $user_id 
		 * @param  string $coord   
		 * @param  array $crop   
		 *  
		 * @since 2.0.22
		 */
		public function cover_photo( $image_path, $src, $key, $user_id, $coord, $crop ){

			$sizes = UM()->options()->get( 'cover_thumb_sizes' );
			
			$quality = UM()->options()->get( 'image_compression' );

			$image = wp_get_image_editor( $image_path ); // Return an implementation that extends WP_Image_Editor

			if ( ! is_wp_error( $image ) ) {
					
				$src_x = $crop[0];
				$src_y = $crop[1];
				$src_w = $crop[2];
				$src_h = $crop[3];

				$image->crop( $src_x, $src_y, $src_w, $src_h );

				$image->save( $image_path );
				
				$image->set_quality( $quality );

				$sizes_array = array();

				foreach( $sizes as $size ){
					$sizes_array[ ] = array ('width' => $size );
				}

				$image->multi_resize( $sizes_array );

			}else{

				wp_send_json_error( esc_js( __( "Unable to crop image file: {$src}", 'ultimate-member' ) ) );		
	
			}

		}

		/**
		 * Stream photo image process
		 * @param  string $src     
		 * @param  integer $user_id 
		 * @param  string $coord   
		 * @param  array $crop   
		 *  
		 * @since 2.0.22
		 */
		public function stream_photo( $image_path, $src, $key, $user_id, $coord, $crop ){
			
			$image = wp_get_image_editor( $image_path ); // Return an implementation that extends WP_Image_Editor

			$quality = UM()->options()->get( 'image_compression' );

			if ( ! is_wp_error( $image ) ) {
				
				if( ! empty( $crop ) ){	

					if( ! is_array( $crop ) ){
						$crop = explode(",", $crop );
					}

					$src_x = $crop[0];
					$src_y = $crop[1];
					$src_w = $crop[2];
					$src_h = $crop[3];

					$image->crop( $src_x, $src_y, $src_w, $src_h );
				}

				$image->save( $image_path );
				
				$image->set_quality( $quality );

			}else{

				wp_send_json_error( esc_js( __( "Unable to crop stream image file: {$image_path}", 'ultimate-member' ) ) );		
	
			}

		}

		/**
		 * Set stream photo default settings
		 * @param  array $args 
		 * @return array    
		 *  
		 * @since 2.0.22
		 */
		public function stream_photo_data( $args ){

			$args['max_file_size'] = apply_filters("um_upload_images_stream_maximum_file_size", 9999999 );
			$args['max_file_size_error'] = sprintf(__("Maximum file size allowed: ".size_format( $args['max_file_size'] ),'ultimate-member') );
			
			return $args;
		}

		/**
		 * Resize Image
		 * @param  string $image_path
		 * @param  string $src     
		 * @param  string $key     
		 * @param  integer $user_id 
		 * @param  string $coord   
		 * @return string $src          
		 *  
		 * @since 2.0.22
		 */
		public function resize_image( $image_path, $src, $key, $user_id, $coord ){

			$crop = explode( ',', $coord );
			$crop = array_map( 'intval', $crop );

			do_action("um_upload_image_process__{$key}", $image_path, $src, $key, $user_id, $coord, $crop );

			if( ! in_array( $key, array('profile_photo','cover_photo') ) ){
				do_action("um_upload_stream_image_process", $image_path, $src, $key, $user_id, $coord, $crop );
			}

			$ret = array();
			$ret['image']['source_url'] = $src;
			$ret['image']['source_path'] = $image_path;
			$ret['image']['filename'] = wp_basename( $image_path );

			return $ret;

		}


		/**
		 * Move temporary files
		 *
		 * @param $user_id
		 * @param $files
		 * @param bool $move_only
		 */
		function move_temporary_files( $user_id, $files, $move_only = false ) {
			$new_files = array();

			$user_basedir = UM()->uploader()->get_upload_user_base_dir( $user_id, true );

			foreach ( $files as $key => $filename ) {

				if ( empty( $filename ) || 'empty_file' == $filename ) {
					//clear empty filename values
					$old_filename = get_user_meta( $user_id, $key, true );
					if ( ! empty( $old_filename ) ) {
						$file = $user_basedir . DIRECTORY_SEPARATOR . $old_filename;
						if ( file_exists( $file ) ) {
							unlink( $file );
						}
					}

					delete_user_meta( $user_id, $key );
					delete_user_meta( $user_id, "{$key}_metadata" );
					delete_transient("um_{$filename}");

					continue;
				}

				//move temporary file from temp directory to the correct user directory
				$temp_file_path = UM()->uploader()->get_core_temp_dir() . DIRECTORY_SEPARATOR . $filename;
				if ( file_exists( $temp_file_path ) ) {
					$extra_hash = hash( 'crc32b', current_time('timestamp') );

					if ( strpos( $filename , 'stream_photo_' ) !== false ) {
						$new_filename = str_replace("stream_photo_","stream_photo_{$extra_hash}_", $filename );
					} else {
						$new_filename = str_replace("file_","file_{$extra_hash}_", $filename );
					}

					if ( $move_only ) {

						$file = $user_basedir. DIRECTORY_SEPARATOR . $filename;
						$new_files[ $key ] = $filename;
						rename( $temp_file_path, $file );

					} else {

						$file = $user_basedir. DIRECTORY_SEPARATOR . $new_filename;

						$new_files[ $key ] = $new_filename;

						if ( rename( $temp_file_path, $file ) ) {
							$file_info = get_transient("um_{$filename}");
							update_user_meta( $user_id, $key, $new_filename );
							update_user_meta( $user_id, "{$key}_metadata", $file_info );
							delete_transient("um_{$filename}");
						}
					}
				}

			}

			//remove user old files
			$this->remove_unused_uploads( $user_id, $new_files );
		}


		/**
		 * Clean user temp uploads
		 *
		 * @param int $user_id
		 * @param array $new_files
		 */
		function remove_unused_uploads( $user_id, $new_files ) {
			um_fetch_user( $user_id );
			$user_meta_keys = UM()->user()->profile;

			$_array = array();
			foreach ( UM()->builtin()->custom_fields as $_field ) {
				if ( $_field['type'] == 'file' && ! empty( $user_meta_keys[ $_field['metakey'] ] ) ) {
					$_array[ $_field['metakey'] ] = $user_meta_keys[ $_field['metakey'] ];
				}
			}
			$_array = array_merge( $_array, $new_files );

			$files = glob( um_user_uploads_dir() . '*', GLOB_BRACE );
			$error = array();
			if ( file_exists( um_user_uploads_dir() ) && $files && isset( $_array ) && is_array( $_array ) ) {
				foreach ( $files as $file ) {
					$str = basename( $file );

					if ( ! strstr( $str, 'profile_photo' ) && ! strstr( $str, 'cover_photo' ) &&
					     ! strstr( $str, 'stream_photo' ) && ! preg_grep( '/' . $str . '/', $_array ) ) {
						$error[] = $str;
						unlink( $file );
					}
				}
			}
		}
	}

}