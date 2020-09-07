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
		 * @var integer
		 */
		var $replace_upload_dir = false;


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
		var $core_upload_url;


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
			$this->core_upload_dir = DIRECTORY_SEPARATOR . 'ultimatemember' . DIRECTORY_SEPARATOR;
			$this->core_upload_url = '/ultimatemember/';
			$this->upload_image_type = 'stream_photo';
			$this->wp_upload_dir = wp_upload_dir();
			$this->temp_upload_dir = 'temp';

			add_filter( 'upload_dir', array( $this, 'set_upload_directory' ), 10, 1 );
			add_filter( 'wp_handle_upload_prefilter', array( $this, 'validate_upload' ) );

			add_filter( 'um_upload_image_result', array( $this, 'rotate_uploaded_image' ), 10, 1 );
			add_filter( 'um_upload_image_process__profile_photo', array( $this, 'profile_photo' ), 10, 7 );
			add_filter( 'um_upload_image_process__cover_photo', array( $this, 'cover_photo' ), 10, 7 );
			add_action( 'um_upload_stream_image_process', array( $this, 'stream_photo' ), 10, 7 );

			add_action( 'init', array( $this, 'init' ) );

			//remove user old files
			add_action( 'um_after_move_temporary_files', array( $this, 'remove_unused_uploads' ), 10, 3 );
		}


		/**
		 * Init
		 */
		function init() {
			$this->user_id = get_current_user_id();
		}


		/**
		 * Get core temporary directory path
		 *
		 * @since 2.0.22
		 * @return string
		 */
		public function get_core_temp_dir() {
			return $this->get_upload_base_dir(). $this->temp_upload_dir;
		}


		/**
		 * Get core temporary directory URL
		 *
		 * @since 2.0.22
		 * @return string
		 */
		public function get_core_temp_url() {
			return $this->get_upload_base_url(). $this->temp_upload_dir;
		}


		/**
		 * Get core upload directory
		 *
		 * @since 2.0.22
		 * @return string
		 */
		public function get_core_upload_dir() {
			return $this->core_upload_dir;
		}


		/**
		 * Get core upload base url
		 *
		 * @since 2.0.22
		 * @return string
		 */
		public function get_upload_base_url() {
			$wp_baseurl = $this->wp_upload_dir['baseurl'];

			$this->upload_baseurl = set_url_scheme( $wp_baseurl . $this->core_upload_url );

			return $this->upload_baseurl;
		}


		/**
		 * Get core upload  base directory
		 *
		 * @since 2.0.22
		 * @return string
		 */
		public function get_upload_base_dir() {
			$wp_basedir = $this->wp_upload_dir['basedir'];

			$this->upload_basedir = $wp_basedir . $this->core_upload_dir;

			return $this->upload_basedir;
		}


		/**
		 * Get user upload base directory
		 *
		 * @param integer $user_id
		 * @param bool $create_dir
		 *
		 * @since 2.0.22
		 *
		 * @return string
		 */
		public function get_upload_user_base_dir( $user_id = null, $create_dir = false ) {
			if ( $user_id ) {
				$this->user_id = $user_id;
			}

			$this->upload_user_basedir = $this->get_upload_base_dir() . $this->user_id;

			if ( $create_dir ) {
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
		public function get_upload_user_base_url( $user_id = null ) {
			if ( $user_id ) {
				$this->user_id = $user_id;
			}

			$this->upload_user_baseurl = $this->get_upload_base_url() . $this->user_id;

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
		public function set_upload_directory( $args ) {
			$this->upload_baseurl = $args['baseurl'] . $this->core_upload_url;
			$this->upload_basedir = $args['basedir'] . $this->core_upload_dir;

			if ( 'image' == $this->upload_type && is_user_logged_in() ) {
				if ( 'stream_photo' == $this->upload_image_type ) {
					$this->upload_user_baseurl = $this->upload_baseurl . $this->temp_upload_dir;
					$this->upload_user_basedir = $this->upload_basedir . $this->temp_upload_dir;
				} else {
					$this->upload_user_baseurl = $this->upload_baseurl . $this->user_id;
					$this->upload_user_basedir = $this->upload_basedir . $this->user_id;
				}
			} else {
				$this->upload_user_baseurl = $this->upload_baseurl . $this->temp_upload_dir;
				$this->upload_user_basedir = $this->upload_basedir . $this->temp_upload_dir;
			}

			list( $this->upload_user_baseurl, $this->upload_user_basedir ) = apply_filters( 'um_change_upload_user_path', array( $this->upload_user_baseurl, $this->upload_user_basedir ), $this->field_key, $this->upload_type );

			if ( $this->replace_upload_dir ) {
				$args['path'] = $this->upload_user_basedir;
				$args['url'] = $this->upload_user_baseurl;
			}

			return $args;
		}


		/**
		 *  Upload Image files
		 *
		 * @param array $uploadedfile
		 * @param int|null $user_id
		 * @param string $field_key
		 * @param string $upload_type
		 *
		 * @since  2.0.22
		 *
		 * @return array
		 * @throws \Exception
		 */
		public function upload_image( $uploadedfile, $user_id = null, $field_key = '', $upload_type = 'stream_photo' ) {
			$response = array();

			if ( ! function_exists( 'wp_handle_upload' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/file.php' );
			}

			if ( empty( $field_key ) ) {
				$field_key = 'custom_field';
			}

			$this->field_key = $field_key;

			$this->upload_type = 'image';

			$this->upload_image_type = $upload_type;

			if ( $user_id && is_user_logged_in() ) {
				$this->user_id = $user_id;
			}

			if ( in_array( $field_key, array( 'profile_photo', 'cover_photo' ) ) ) {
				$this->upload_image_type = $field_key;
			}

			$field_data = UM()->fields()->get_field( $field_key );

			if ( ! empty( $field_data['allowed_types'] ) ) {
				$field_allowed_file_types = explode( ',', $field_data['allowed_types'] );
			} else {
				$field_allowed_file_types = apply_filters( 'um_uploader_image_default_filetypes', array( 'JPG', 'JPEG', 'PNG', 'GIF' ) );
			}

			$allowed_image_mimes = array();

			foreach ( $field_allowed_file_types as $a ) {
				$atype = wp_check_filetype( "test.{$a}" );
				$allowed_image_mimes[ $atype['ext'] ] = $atype['type'];
			}

			$upload_overrides = array(
				'test_form'                 => false,
				'mimes'                     => apply_filters( 'um_uploader_allowed_image_mimes', $allowed_image_mimes ),
				'unique_filename_callback'  => array( $this, 'unique_filename' ),
			);

			$upload_overrides = apply_filters( "um_image_upload_handler_overrides__{$field_key}", $upload_overrides );

			$movefile = wp_handle_upload( $uploadedfile, $upload_overrides );

			if ( isset( $movefile['error'] ) ) {
				/*
			     * Error generated by _wp_handle_upload()
			     * @see _wp_handle_upload() in wp-admin/includes/file.php
			     */
				$response['error'] = $movefile['error'];
			} else {

				/**
				 * UM hook
				 *
				 * @type        filter
				 * @title       um_upload_image_result
				 * @description Filter uploaded image data
				 * @input_vars  [
				 * 	{"var":"$movefile", "type":"array", "desc":"Uploaded file info"},
				 * 	{"var":"$user_id", "type":"int", "desc":"User ID"},
				 * 	{"var":"$field_data", "type":"array", "desc":"Field data"}
				 * ]
				 * @change_log
				 * ["Since: 2.1.6"]
				 * @example
				  <?php
				  add_filter( 'um_upload_image_result', 'custom_um_upload_image_result', 10, 3 );
				  function custom_um_upload_image_result( $movefile, $user_id, $field_data ) {
						// your code here
						return $movefile;
				  }
				  ?>
				 */
				$movefile = apply_filters( 'um_upload_image_result', $movefile, $user_id, $field_data );

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

				$filename = wp_basename( $movefile['url'] );

				$transient = set_transient( "um_{$filename}", $movefile['file_info'], 2 * HOUR_IN_SECONDS );
				if ( empty( $transient ) ) {
					update_user_meta( $this->user_id, "{$field_key}_metadata_temp", $movefile['file_info'] );
				}
			}

			$response['handle_upload'] = $movefile;

			// Remove old files from 'temp' directory
			UM()->files()->remove_old_files( UM()->files()->upload_temp );

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
		public function upload_file( $uploadedfile, $user_id = null, $field_key = '' ) {
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
				'test_form'                 => false,
				'mimes'                     => apply_filters( 'um_uploader_allowed_file_mimes', $allowed_file_mimes ),
				'unique_filename_callback'  => array( $this, 'unique_filename' ),
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

				//update_user_meta( $this->user_id, $field_key, wp_basename( $movefile['url'] ) );

				$filename = wp_basename( $movefile['url'] );

				$transient = set_transient( "um_{$filename}", $movefile['file_info'], 2 * HOUR_IN_SECONDS );
				if ( empty( $transient ) ) {
					update_user_meta( $this->user_id, "{$field_key}_metadata_temp", $movefile['file_info'] );
				}
			}

			$response['handle_upload'] = $movefile;

			// Remove old files from 'temp' directory
			UM()->files()->remove_old_files( UM()->files()->upload_temp );

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
		 *
		 * @param  string $dir
		 * @param  string $filename
		 * @param  string $ext
		 * @return string $filename
		 *
		 * @since  2.0.22
		 */
		public function unique_filename( $dir, $filename, $ext ) {

			if ( empty( $ext ) ) {
				$image_type = wp_check_filetype( $filename );
				$ext = strtolower( trim( $image_type['ext'], ' \/.' ) );
			} else {
				$ext = strtolower( trim( $ext, ' \/.' ) );
			}

			if ( 'image' == $this->upload_type ) {

				switch ( $this->upload_image_type ) {

					case 'stream_photo':
						$hashed = hash('ripemd160', time() . mt_rand( 10, 1000 ) );
						$filename = "stream_photo_{$hashed}.{$ext}";
						break;

					case 'profile_photo':
					case 'cover_photo':
						$filename = "{$this->upload_image_type}_temp.{$ext}";
						break;

				}

			} elseif ( 'file' == $this->upload_type ) {
				$hashed = hash('ripemd160', time() . mt_rand( 10, 1000 ) );
				$filename = "file_{$hashed}.{$ext}";
			}

			$this->delete_existing_file( $filename, $ext, $dir );

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
		public function delete_existing_file( $filename, $ext = '', $dir = '' ) {
			if ( file_exists( $this->upload_user_basedir . DIRECTORY_SEPARATOR . $filename  ) && ! empty( $filename ) ) {
				unlink( $this->upload_user_basedir . DIRECTORY_SEPARATOR . $filename );
			}
		}


		/**
		 * Profile photo image process
		 *
		 * @param  array $response
		 * @param  string $image_path
		 * @param  string $src
		 * @param  string $key
		 * @param  integer $user_id
		 * @param  string $coord
		 * @param  array $crop
		 *
		 * @since 2.0.22
		 *
		 * @return array
		 */
		public function profile_photo( $response, $image_path, $src, $key, $user_id, $coord, $crop ) {
			$sizes = UM()->options()->get( 'photo_thumb_sizes' );

			$quality = UM()->options()->get( 'image_compression' );

			$image = wp_get_image_editor( $image_path ); // Return an implementation that extends WP_Image_Editor

			$temp_image_path = $image_path;
			//refresh image_path to make temporary image permanently after upload
			$image_path = pathinfo( $image_path, PATHINFO_DIRNAME ) . DIRECTORY_SEPARATOR . $key . '.' . pathinfo( $image_path, PATHINFO_EXTENSION );

			if ( ! is_wp_error( $image ) ) {
				$src_x = $crop[0];
				$src_y = $crop[1];
				$src_w = $crop[2];
				$src_h = $crop[3];

				$image->crop( $src_x, $src_y, $src_w, $src_h );

				$max_w = UM()->options()->get( 'image_max_width' );
				if ( $src_w > $max_w ) {
					$image->resize( $max_w, $src_h );
				}

				$image->save( $image_path );

				$image->set_quality( $quality );

				$sizes_array = array();

				foreach ( $sizes as $size ) {
					$sizes_array[] = array( 'width' => $size );
				}

				$image->multi_resize( $sizes_array );

				delete_user_meta( $user_id, 'synced_profile_photo' );

				unlink( $temp_image_path );

				$src = str_replace( '/' . $key . '_temp.', '/' . $key . '.',  $src );

				$response['image']['source_url'] = $src;
				$response['image']['source_path'] = $image_path;
				$response['image']['filename'] = wp_basename( $image_path );

				update_user_meta( $this->user_id, $key, wp_basename( wp_basename( $image_path ) ) );
				delete_user_meta( $this->user_id, "{$key}_metadata_temp" );
			} else {
				wp_send_json_error( esc_js( __( "Unable to crop image file: {$src}", 'ultimate-member' ) ) );
			}

			return $response;
		}


		/**
		 * Cover photo image process
		 *
		 * @param  string $src
		 * @param  integer $user_id
		 * @param  string $coord
		 * @param  array $crop
		 * @param  array $response
		 *
		 * @since 2.0.22
		 *
		 * @return array
		 */
		public function cover_photo( $response, $image_path, $src, $key, $user_id, $coord, $crop ) {

			$sizes = UM()->options()->get( 'cover_thumb_sizes' );

			$quality = UM()->options()->get( 'image_compression' );

			$image = wp_get_image_editor( $image_path ); // Return an implementation that extends WP_Image_Editor

			$temp_image_path = $image_path;

			//refresh image_path to make temporary image permanently after upload
			$image_path = pathinfo( $image_path, PATHINFO_DIRNAME ) . DIRECTORY_SEPARATOR . $key . '.' . pathinfo( $image_path, PATHINFO_EXTENSION );

			if ( ! is_wp_error( $image ) ) {

				$src_x = $crop[0];
				$src_y = $crop[1];
				$src_w = $crop[2];
				$src_h = $crop[3];

				$image->crop( $src_x, $src_y, $src_w, $src_h );

				$max_w = UM()->options()->get( 'image_max_width' );
				if ( $src_w > $max_w ) {
					$image->resize( $max_w, $src_h );
				}

				$image->save( $image_path );

				$image->set_quality( $quality );

				$sizes_array = array();

				foreach ( $sizes as $size ) {
					$sizes_array[] = array( 'width' => $size );
				}

				$resize = $image->multi_resize( $sizes_array );

				// change filenames of resized images
				foreach ( $resize as $row ) {
					$new_filename = str_replace( "x{$row['height']}" , '', $row['file'] );
					$old_filename = $row['file'];

					rename( dirname( $image_path ) . DIRECTORY_SEPARATOR . $old_filename, dirname( $image_path ) . DIRECTORY_SEPARATOR . $new_filename );
				}

				unlink( $temp_image_path );

				$src = str_replace( '/' . $key . '_temp.', '/' . $key . '.',  $src );

				$response['image']['source_url'] = $src;
				$response['image']['source_path'] = $image_path;
				$response['image']['filename'] = wp_basename( $image_path );

				update_user_meta( $this->user_id, $key, wp_basename( wp_basename( $image_path ) ) );
				delete_user_meta( $this->user_id, "{$key}_metadata_temp" );
			} else {
				wp_send_json_error( esc_js( __( "Unable to crop image file: {$src}", 'ultimate-member' ) ) );
			}

			return $response;
		}


		/**
		 * Stream photo image process
		 *
		 * @param  array $response
		 * @param  string $image_path
		 * @param  string $src
		 * @param  integer $user_id
		 * @param  string $coord
		 * @param  array $crop
		 *
		 * @since 2.0.22
		 *
		 * @return array
		 */
		public function stream_photo( $response, $image_path, $src, $key, $user_id, $coord, $crop ) {

			$image = wp_get_image_editor( $image_path ); // Return an implementation that extends WP_Image_Editor

			$quality = UM()->options()->get( 'image_compression' );

			if ( ! is_wp_error( $image ) ) {
				if ( ! empty( $crop ) ) {

					if ( ! is_array( $crop ) ) {
						$crop = explode( ",", $crop );
					}

					$src_x = $crop[0];
					$src_y = $crop[1];
					$src_w = $crop[2];
					$src_h = $crop[3];

					$image->crop( $src_x, $src_y, $src_w, $src_h );

					$max_w = UM()->options()->get( 'image_max_width' );
					if ( $src_w > $max_w ) {
						$image->resize( $max_w, $src_h );
					}
				}

				$image->save( $image_path );

				$image->set_quality( $quality );

			} else {
				wp_send_json_error( esc_js( __( "Unable to crop stream image file: {$image_path}", 'ultimate-member' ) ) );
			}

			return $response;
		}


		/**
		 * Resize Image
		 *
		 * @param  string $image_path
		 * @param  string $src
		 * @param  string $key
		 * @param  integer $user_id
		 * @param  string $coord
		 *
		 * @since 2.0.22
		 *
		 * @return array
		 */
		public function resize_image( $image_path, $src, $key, $user_id, $coord ) {
			$crop = explode( ',', $coord );
			$crop = array_map( 'intval', $crop );

			$response = array(
				'image' => array(
					'source_url'    => $src,
					'source_path'   => $image_path,
					'filename'      => wp_basename( $image_path ),
				),
			);

			$response = apply_filters( "um_upload_image_process__{$key}", $response, $image_path, $src, $key, $user_id, $coord, $crop );

			if ( ! in_array( $key, array( 'profile_photo', 'cover_photo' ) ) ) {
				$response = apply_filters( 'um_upload_stream_image_process', $response, $image_path, $src, $key, $user_id, $coord, $crop );
			}

			return $response;
		}


		/**
		 * Fix image orientation
		 *
		 * @since 2.1.6
		 *
		 * @param  array $movefile
		 * @return array
		 */
		public function rotate_uploaded_image( $movefile ) {
			$image_fix_orientation = UM()->options()->get( 'image_orientation_by_exif' );
			if ( $image_fix_orientation && $movefile['type'] == 'image/jpeg' ) {
				$image = imagecreatefromjpeg( $movefile['file'] );
				if ( $image ) {
					$image = UM()->files()->fix_image_orientation( $image, $movefile['file'] );
					$quality = UM()->options()->get( 'image_compression' );
					imagejpeg( $image, $movefile['file'], $quality );
				}
			}
			return $movefile;
		}


		/**
		 * Move temporary files
		 *
		 * run when uploaded files are from custom fields
		 * move them to the users' folder after form submitted
		 *
		 * @param $user_id
		 * @param $files
		 * @param bool $move_only
		 */
		function move_temporary_files( $user_id, $files, $move_only = false ) {
			$new_files = array();
			$old_files = array();

			$user_basedir = UM()->uploader()->get_upload_user_base_dir( $user_id, true );

			foreach ( $files as $key => $filename ) {

				if ( empty( $filename ) || 'empty_file' == $filename ) {
					//clear empty filename values
					$old_filename = get_user_meta( $user_id, $key, true );
					if ( ! empty( $old_filename ) ) {
						$file = $user_basedir . DIRECTORY_SEPARATOR . $old_filename;

						$valid = true;
						//validate traversal file
						if ( validate_file( $file ) === 1 ) {
							$valid = false;
						}

						if ( $valid ) {
							if ( file_exists( $file ) && um_is_file_owner( $file, $user_id ) ) {
								unlink( $file );
							}
						}
					}

					delete_user_meta( $user_id, $key );
					delete_user_meta( $user_id, "{$key}_metadata" );
					delete_transient( "um_{$filename}" );

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

					$submitted = get_user_meta( $user_id, 'submitted', true );
					$submitted = ! empty( $submitted ) ? $submitted : array();

					$submitted[ $key ] = $new_filename;
					update_user_meta( $user_id, 'submitted', $submitted );

					if ( $move_only ) {

						$file = $user_basedir . DIRECTORY_SEPARATOR . $filename;
						if ( rename( $temp_file_path, $file ) ) {
							$new_files[ $key ] = $filename;
						}

					} else {

						$file = $user_basedir . DIRECTORY_SEPARATOR . $new_filename;

						if ( rename( $temp_file_path, $file ) ) {
							$new_files[ $key ] = $new_filename;
							$old_files[ $key ] = get_user_meta( $user_id, $key, true );

							update_user_meta( $user_id, $key, $new_filename );

							$file_info = get_transient( "um_{$filename}" );
							if ( ! $file_info ) {
								$file_info = get_user_meta( $user_id, "{$key}_metadata_temp", true );
								delete_user_meta( $user_id, "{$key}_metadata_temp" );
							}

							if ( $file_info ) {
								update_user_meta( $user_id, "{$key}_metadata", $file_info );
								delete_transient( "um_{$filename}" );
							}
						}
					}
				}

			}

			/**
			 * @hooked UM()->uploader()->remove_unused_uploads() - 10
			 */
			do_action( 'um_after_move_temporary_files', $user_id, $new_files, $old_files );
		}


		/**
		 * Clean user temp uploads
		 *
		 * @param int $user_id
		 * @param array $new_files
		 * @param array $old_files
		 */
		function remove_unused_uploads( $user_id, $new_files, $old_files = array() ) {

			if ( ! file_exists( $this->get_upload_user_base_dir( $user_id ) ) ) {
				return;
			}

			UM()->user()->remove_cache( $user_id );
			UM()->user()->set( $user_id );
			$user_meta_keys = UM()->user()->profile;

			$_array = $new_files;
			if ( ! empty( UM()->builtin()->custom_fields ) ) {
				foreach ( UM()->builtin()->custom_fields as $_field ) {
					if ( in_array( $_field['type'], array( 'file', 'image' ) ) && isset( $user_meta_keys[$_field['metakey']] ) && empty( $_array[$_field['metakey']] ) ) {
						$_array[$_field['metakey']] = $user_meta_keys[$_field['metakey']];
					}
				}
			}

			$files = glob( UM()->uploader()->get_upload_base_dir() . $user_id . DIRECTORY_SEPARATOR . '*', GLOB_BRACE );
			if ( ! empty( $files ) ) {
				foreach ( $files as $file ) {
					$str = basename( $file );

					if ( strstr( $str, 'profile_photo' ) || strstr( $str, 'cover_photo' ) || preg_grep( '/' . $str . '/', $_array ) ) {
						continue;
					}

					// Don't delete photo that belongs to the Social Activity post or Groups post
					if ( strstr( $str, 'stream_photo' ) ) {
						global $wpdb;
						$is_post_image = $wpdb->get_var( "
							SELECT COUNT(*) FROM {$wpdb->postmeta}
							WHERE `meta_key`='_photo' AND `meta_value`='{$str}';" );
						if ( $is_post_image ) {
							continue;
						}
					}

					$can_unlink = apply_filters( 'um_can_remove_uploaded_file', true, $user_id, $str );
					if ( $can_unlink ) {
						unlink( $file );
					}
				}
			}
		}
	}

}
