<?php
namespace um\ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'um\ajax\User' ) ) {


	/**
	 * Class User
	 *
	 * @package um\ajax
	 */
	class User {

		/**
		 * User constructor.
		 */
		public function __construct() {
			add_action( 'wp_ajax_um_admin_review_registration', array( &$this, 'admin_review_registration' ) );
			add_action( 'wp_ajax_um_get_users', array( &$this, 'get_users' ) );

			// @todo make the avatar field for ability to set user photo while registration process
			//add_action( 'wp_ajax_nopriv_um_upload_profile_photo', array( &$this, 'upload_profile_photo' ) );
			//add_action( 'wp_ajax_nopriv_um_resize_avatar', array( &$this, 'resize_avatar' ) );

			add_action( 'wp_ajax_um_upload_profile_photo', array( &$this, 'upload_profile_photo' ) );
			add_action( 'wp_ajax_um_resize_avatar', array( &$this, 'resize_avatar' ) );
			add_action( 'wp_ajax_um_reset_avatar', array( &$this, 'reset_avatar' ) );
		}

		/**
		 *
		 */
		public function admin_review_registration() {
			UM()->ajax()->check_nonce( 'um-admin-nonce' );

			if ( empty( $_REQUEST['user_id'] ) ) {
				wp_send_json_error( __( 'Invalid user ID.', 'ultimate-member' ) );
			}

			$user_id = absint( $_REQUEST['user_id'] );

			if ( ! um_can_view_profile( $user_id ) ) {
				wp_send_json_success( '' );
			}

			um_fetch_user( $user_id );

			UM()->user()->preview = true;

			$output = um_user_submitted_registration_formatted( true );

			um_reset_user();

			wp_send_json_success( $output );
		}

		/**
		 *
		 */
		public function get_users() {
			UM()->ajax()->check_nonce( 'um-admin-nonce' );

			$search_request = ! empty( $_REQUEST['search'] ) ? sanitize_text_field( $_REQUEST['search'] ) : '';
			$page           = ! empty( $_REQUEST['page'] ) ? absint( $_REQUEST['page'] ) : 1;
			$per_page       = 20;

			$args = array(
				'fields' => array( 'ID', 'user_login' ),
				'paged'  => $page,
				'number' => $per_page,
			);

			if ( ! empty( $search_request ) ) {
				$args['search'] = '*' . $search_request . '*';
			}

			$args = apply_filters( 'um_get_users_list_ajax_args', $args );

			$users_query = new \WP_User_Query( $args );
			$users       = $users_query->get_results();
			$total_count = $users_query->get_total();

			if ( ! empty( $_REQUEST['avatar'] ) ) {
				foreach ( $users as $key => $user ) {
					$url                = get_avatar_url( $user->ID );
					$users[ $key ]->img = $url;
				}
			}

			wp_send_json_success(
				array(
					'users'       => $users,
					'total_count' => $total_count,
				)
			);
		}

		/**
		 * Callback function for uploading avatar
		 */
		public function upload_profile_photo() {
			$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_key( $_REQUEST['nonce'] ) : '';
			if ( ! wp_verify_nonce( $nonce, 'um-frontend-nonce' ) ) {
				wp_send_json(
					array(
						'OK'   => 0,
						'info' => __( 'Wrong nonce.', 'ultimate-member' ),
					)
				);
			}

			$user_id = ! empty( $_REQUEST['user_id'] ) ? absint( $_REQUEST['user_id'] ) : '';
			if ( ! empty( $user_id ) ) {
				update_user_meta( $user_id, 'um_temp_profile_photo', '' );
			}

			$files = array();

			$chunk  = ! empty( $_REQUEST['chunk'] ) ? absint( $_REQUEST['chunk'] ) : 0;
			$chunks = ! empty( $_REQUEST['chunks'] ) ? absint( $_REQUEST['chunks'] ) : 0;

			// Get a file name
			if ( isset( $_REQUEST['name'] ) ) {
				$filename = sanitize_file_name( $_REQUEST['name'] );
			} elseif ( ! empty( $_FILES ) ) {
				$filename = sanitize_file_name( $_FILES['file']['name'] );
			} else {
				$filename = uniqid( 'file_' );
			}

			/**
			 * Filters the MIME-types of the images that can be uploaded as Profile Photo (avatar).
			 *
			 * @since 3.0.0
			 * @hook um_profile_photo_mime_types
			 *
			 * @param {array} $mime_types MIME types.
			 *
			 * @return {array} MIME types.
			 */
			$mimes = apply_filters(
				'um_profile_photo_mime_types',
				array(
					'jpg|jpeg|jpe' => 'image/jpeg',
					'gif'          => 'image/gif',
					'png'          => 'image/png',
					'bmp'          => 'image/bmp',
					'tiff|tif'     => 'image/tiff',
					'ico'          => 'image/x-icon',
					'webp'         => 'image/webp',
					'heic'         => 'image/heic',
				)
			);

			$image_type = wp_check_filetype( $filename, $mimes );
			if ( ! $image_type['ext'] ) {
				wp_send_json(
					array(
						'OK'   => 0,
						'info' => __( 'Wrong filetype.', 'ultimate-member' ),
					)
				);
			}

			UM()->common()->filesystem()->clear_temp_dir();

			if ( empty( $_FILES ) || $_FILES['file']['error'] ) {
				wp_send_json(
					array(
						'OK'   => 0,
						'info' => __( 'Failed to move uploaded file.', 'ultimate-member' ),
					)
				);
			}

			// Uploader for the chunks
			if ( $chunks ) {
				if ( isset( $_COOKIE['um-avatar-upload'] ) && $chunks > 1 ) {
					$unique_name = sanitize_file_name( $_COOKIE['um-avatar-upload'] );
					$filepath    = UM()->common()->filesystem()->temp_upload_dir . DIRECTORY_SEPARATOR . $unique_name;

					$image_type = wp_check_filetype( $unique_name, $mimes );
					if ( ! $image_type['ext'] ) {
						wp_send_json(
							array(
								'OK'   => 0,
								'info' => __( 'Wrong filetype.', 'ultimate-member' ),
							)
						);
					}
				} else {
					$unique_name = wp_unique_filename( UM()->common()->filesystem()->temp_upload_dir, $filename, array( &$this, 'unique_filename' ) );
					$filepath    = UM()->common()->filesystem()->temp_upload_dir . DIRECTORY_SEPARATOR . $unique_name;
					if ( $chunks > 1 ) {
						UM()->setcookie( 'um-avatar-upload', $unique_name );
					}
				}

				// phpcs:disable WordPress.WP.AlternativeFunctions -- for directly fopen, fwrite, fread, fclose functions using
				// phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged -- for silenced fopen, fwrite, fread, fclose functions running

				// Open temp file
				$out = @fopen( "{$filepath}.part", 0 === $chunk ? 'wb' : 'ab' );

				if ( $out ) {

					// Read binary input stream and append it to temp file
					$in = @fopen( $_FILES['file']['tmp_name'], 'rb' );

					if ( $in ) {
						// phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition -- reading buffer here
						while ( $buff = fread( $in, 4096 ) ) {
							fwrite( $out, $buff );
						}
					} else {
						wp_send_json(
							array(
								'OK'   => 0,
								'info' => __( 'Failed to open input stream.', 'ultimate-member' ),
							)
						);
					}

					fclose( $in );
					fclose( $out );
					unlink( $_FILES['file']['tmp_name'] );

				} else {

					wp_send_json(
						array(
							'OK'   => 0,
							'info' => __( 'Failed to open output stream.', 'ultimate-member' ),
						)
					);

				}

				// phpcs:enable WordPress.WP.AlternativeFunctions
				// phpcs:enable WordPress.PHP.NoSilencedErrors.Discouraged

				// Check if file has been uploaded
				if ( $chunk === $chunks - 1 ) {
					// Strip the temp .part suffix off
					rename( "{$filepath}.part", $filepath ); // Strip the temp .part suffix off

					$fileinfo                = $_FILES['file'];
					$fileinfo['file']        = $filepath;
					$fileinfo['name_loaded'] = $filename;
					$fileinfo['name_saved']  = wp_basename( $fileinfo['file'] );
					$fileinfo['hash']        = md5( $fileinfo['name_saved'] . '_um_uploader_security_salt' );
					$fileinfo['path']        = wp_normalize_path( UM()->common()->filesystem()->temp_upload_dir . DIRECTORY_SEPARATOR . $fileinfo['name_saved'] );
					$fileinfo['url']         = UM()->common()->filesystem()->temp_upload_url . '/' . $fileinfo['name_saved'];
					$fileinfo['size']        = filesize( $fileinfo['file'] );
					$fileinfo['size_format'] = size_format( $fileinfo['size'] );
					$fileinfo['time']        = gmdate( 'Y-m-d H:i:s', filemtime( $fileinfo['file'] ) );

					$files[] = $fileinfo;

					UM()->setcookie( 'um-avatar-upload', false );

					update_user_meta( $user_id, 'um_temp_profile_photo', $fileinfo['name_saved'] );
				} else {
					wp_send_json(
						array(
							'OK'   => 1,
							'info' => __( 'Upload successful.', 'ultimate-member' ),
						)
					);
				}
			}

			wp_send_json_success( $files );
		}

		/**
		 * Generate unique filename
		 *
		 * @param string $dir
		 * @param string $name
		 * @param string $ext
		 *
		 * @return string
		 *
		 * @since 1.0
		 */
		public function unique_filename( /** @noinspection PhpUnusedParameterInspection */$dir, $name, $ext ) {
			$hashed = hash( 'ripemd160', time() . wp_rand( 10, 1000 ) );
			$name   = "profile_photo_{$hashed}{$ext}";

			return $name;
		}

		/**
		 * Callback function for crop|resize avatar and finally place it as profile photo
		 */
		public function resize_avatar() {
			UM()->ajax()->check_nonce( 'um-frontend-nonce' );

			if ( empty( $_REQUEST['user_id'] ) || ! UM()->common()->user()->exists_by_id( absint( $_REQUEST['user_id'] ) ) ) {
				wp_send_json_error( __( 'Invalid user', 'ultimate-member' ) );
			}
			$user_id = absint( $_REQUEST['user_id'] );
			if ( ! UM()->roles()->um_current_user_can( 'edit', $user_id ) ) {
				wp_send_json_error( __( 'You have no permission to edit this user', 'ultimate-member' ) );
			}

			if ( empty( $_REQUEST['coord'] ) ) {
				wp_send_json_error( __( 'Invalid image parameters', 'ultimate-member' ) );
			}

			$coord   = $_REQUEST['coord'];
			$coord_map = array(
				'x',
				'y',
				'width',
				'height',
			);
			foreach ( $coord_map as $coord_key ) {
				if ( ! array_key_exists( $coord_key, $coord ) ) {
					wp_send_json_error( __( 'Invalid coordinates', 'ultimate-member' ) );
				}
			}
			$crop = array_map( 'intval', $coord );

			$temp_filename = get_user_meta( $user_id, 'um_temp_profile_photo', true );
			if ( empty( $temp_filename ) ) {
				wp_send_json_error( __( 'Invalid temp image', 'ultimate-member' ) );
			}
			$image_path = wp_normalize_path( UM()->common()->filesystem()->temp_upload_dir . DIRECTORY_SEPARATOR . $temp_filename );
			if ( ! file_exists( $image_path ) ) {
				wp_send_json_error( __( 'Temp image doesn\'t exist', 'ultimate-member' ) );
			}

			$sizes   = um_get_all_avatar_sizes();
			$quality = UM()->options()->get( 'image_compression' );

			$image = wp_get_image_editor( $image_path ); // Return an implementation that extends WP_Image_Editor
			if ( is_wp_error( $image ) ) {
				wp_send_json_error( __( 'Unable to crop image file', 'ultimate-member' ) );
			}

			$temp_image_path = $image_path;

			$image_path_crop = wp_normalize_path( UM()->uploader()->get_upload_base_dir() . $user_id . '/profile_photo.jpg' );

			$image->crop( $crop['x'], $crop['y'], $crop['width'], $crop['height'] );
			$max_w = UM()->options()->get( 'image_max_width' );
			if ( $crop['width'] > $max_w ) {
				$image->resize( $max_w, null );
			}

			$files_for_copy   = array();
			$files_for_copy[] = $image_path_crop;

			// save to the user folder with the proper profile_photo.{ext} name
			$image->save( $image_path_crop );

			$image->set_quality( $quality );
			$sizes_array = array();
			foreach ( $sizes as $size ) {
				$sizes_array[] = array( 'width' => $size );
			}

			$image->multi_resize( $sizes_array );

			update_user_meta( $user_id, 'um_temp_profile_photo', '' );
			unlink( $temp_image_path );
			delete_option( "um_cache_userdata_{$user_id}" );

			wp_send_json_success(
				array(
					'avatar' => UM()->common()->user()->get_avatar_url( $user_id ),
					'actions' => array(
						'um-change-profile-photo',
						'um-reset-profile-photo',
					),
				)
			);
		}

		/**
		 * Callback function for crop|resize avatar and finally place it as profile photo
		 */
		public function reset_avatar() {
			UM()->ajax()->check_nonce( 'um-frontend-nonce' );

			if ( empty( $_REQUEST['user_id'] ) || ! UM()->common()->user()->exists_by_id( absint( $_REQUEST['user_id'] ) ) ) {
				wp_send_json_error( __( 'Invalid user', 'ultimate-member' ) );
			}
			$user_id = absint( $_REQUEST['user_id'] );
			if ( ! UM()->roles()->um_current_user_can( 'edit', $user_id ) ) {
				wp_send_json_error( __( 'You have no permission to edit this user', 'ultimate-member' ) );
			}

			$avatar_path = wp_normalize_path( UM()->uploader()->get_upload_base_dir() . $user_id . '/profile_photo.jpg' );
			if ( ! file_exists( $avatar_path ) ) {
				wp_send_json_error( __( 'Invalid avatar path', 'ultimate-member' ) );
			}

			$result     = false;
			$can_unlink = apply_filters( 'um_can_remove_uploaded_file', true, $user_id, 'profile_photo.jpg' );
			if ( $can_unlink ) {
				$result = unlink( $avatar_path );
			}

			// remove all sizes
			$files = glob( UM()->uploader()->get_upload_base_dir() . $user_id . DIRECTORY_SEPARATOR . '*' );
			if ( ! empty( $files ) ) {
				foreach ( $files as $file ) {
					$str = basename( $file );

					if ( strstr( $str, 'profile_photo' ) ) {
						$can_unlink = apply_filters( 'um_can_remove_uploaded_file', true, $user_id, $str );
						if ( $can_unlink ) {
							unlink( $file );
						}
					}
				}
			}

			if ( false === $result ) {
				wp_send_json_error( __( 'Cannot remove avatar', 'ultimate-member' ) );
			}

			wp_send_json_success(
				array(
					'avatar'  => UM()->common()->user()->get_avatar( $user_id, 'xl' ),
					'actions' => array(
						'um-change-profile-photo',
//						'um-reset-profile-photo',
					),
				)
			);
		}
	}
}
