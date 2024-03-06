<?php
namespace um\ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
		if ( defined( 'UM_DEV_MODE' ) && UM_DEV_MODE && UM()->options()->get( 'enable_no_conflict_avatar' ) ) {
			add_action( 'wp_ajax_um_delete_profile_photo', array( $this, 'delete_avatar' ) );
			add_action( 'wp_ajax_um_upload_profile_photo', array( $this, 'upload_profile_photo' ) );
			add_action( 'wp_ajax_um_decline_profile_photo_change', array( $this, 'decline_profile_photo_change' ) );
			add_action( 'wp_ajax_um_apply_profile_photo_change', array( $this, 'apply_profile_photo_change' ) );
		}
	}

	/**
	 * Delete profile avatar AJAX handler.
	 */
	public function delete_avatar() {
		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'um_remove_profile_photo' ) ) {
			wp_send_json_error( __( 'Invalid nonce', 'ultimate-member' ) );
		}

		if ( ! array_key_exists( 'user_id', $_REQUEST ) ) {
			wp_send_json_error( __( 'Invalid data', 'ultimate-member' ) );
		}

		$user_id = absint( $_REQUEST['user_id'] );

		if ( ! UM()->roles()->um_current_user_can( 'edit', $user_id ) ) {
			wp_send_json_error( __( 'You can not edit this user.', 'ultimate-member' ) );
		}

		$result = UM()->common()->users()->delete_photo( $user_id, 'profile_photo' );
		if ( false === $result ) {
			wp_send_json_error( __( 'Cannot delete user photo.', 'ultimate-member' ) );
		}

//		$dropdown_items = array(
//			'<a href="#" class="um-manual-trigger" data-parent=".um-profile-photo" data-child=".um-btn-auto-width">' . esc_html__( 'Set photo', 'ultimate-member' ) . '</a>',
//		);


		// Flush the user's cache.
		UM()->common()->users()->remove_cache( $user_id );

		wp_send_json_success(
			array(
				'avatar'         => get_avatar( $user_id, 128, '', '', array( 'loading' => 'lazy' ) ),
//				'dropdown_items' => $dropdown_items,
			)
		);
	}

	/**
	 * Delete temp profile avatar AJAX handler.
	 */
	public function decline_profile_photo_change() {
		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'um_upload_profile_photo_decline' ) ) {
			wp_send_json_error( __( 'Invalid nonce', 'ultimate-member' ) );
		}

		if ( ! array_key_exists( 'user_id', $_REQUEST ) ) {
			wp_send_json_error( __( 'Invalid data', 'ultimate-member' ) );
		}

		$user_id = absint( $_REQUEST['user_id'] );

		if ( ! UM()->roles()->um_current_user_can( 'edit', $user_id ) ) {
			wp_send_json_error( __( 'You can not edit this user.', 'ultimate-member' ) );
		}

		$temp_profile_photo = get_user_meta( $user_id, 'um_temp_profile_photo', true );

		if ( empty( $temp_profile_photo['path'] ) || ! file_exists( $temp_profile_photo['path'] ) ) {
			wp_send_json_error( __( 'Cannot find uploaded file.', 'ultimate-member' ) );
		}

		$result = unlink( $temp_profile_photo['path'] );
		if ( false === $result ) {
			delete_user_meta( $user_id, 'um_temp_profile_photo' );
			wp_send_json_error( __( 'Cannot delete uploaded file.', 'ultimate-member' ) );
		}

		wp_send_json_success();
	}

	/**
	 * Delete temp profile avatar AJAX handler.
	 */
	public function apply_profile_photo_change() {
		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'um_upload_profile_photo_apply' ) ) {
			wp_send_json_error( __( 'Invalid nonce', 'ultimate-member' ) );
		}

		if ( ! array_key_exists( 'user_id', $_REQUEST ) ) {
			wp_send_json_error( __( 'Invalid data', 'ultimate-member' ) );
		}

		$user_id = absint( $_REQUEST['user_id'] );

		if ( ! UM()->roles()->um_current_user_can( 'edit', $user_id ) ) {
			wp_send_json_error( __( 'You can not edit this user.', 'ultimate-member' ) );
		}

		$temp_profile_photo = get_user_meta( $user_id, 'um_temp_profile_photo', true );

		if ( empty( $temp_profile_photo['path'] ) || ! file_exists( $temp_profile_photo['path'] ) ) {
			wp_send_json_error( __( 'Cannot find uploaded file.', 'ultimate-member' ) );
		}

		$image_path = $temp_profile_photo['path'];
		$image      = wp_get_image_editor( $image_path ); // Return an implementation that extends WP_Image_Editor
		if ( is_wp_error( $image ) ) {
			wp_send_json_error( __( 'Unable to crop image file.', 'ultimate-member' ) );
		}

		$coord_n = substr_count( $_REQUEST['coord'], ',' );
		if ( 3 !== $coord_n ) {
			wp_send_json_error( esc_js( __( 'Invalid coordinates', 'ultimate-member' ) ) );
		}
		$coord = sanitize_text_field( $_REQUEST['coord'] );

		$crop = explode( ',', $coord );
		$crop = array_map( 'intval', $crop );

		$quality = UM()->options()->get( 'image_compression' );

		$temp_image_path = $image_path;
		// Refresh image_path to make temporary image permanently after upload

		$extension = pathinfo( $temp_image_path, PATHINFO_EXTENSION );
		if ( is_multisite() ) {
			// Multisite fix for old customers
			$multisite_fix_dir = UM()->uploader()->get_upload_base_dir();
			$multisite_fix_dir = str_replace( DIRECTORY_SEPARATOR . 'sites' . DIRECTORY_SEPARATOR . get_current_blog_id() . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $multisite_fix_dir );

			$image_path = $multisite_fix_dir . um_user( 'ID' ) . '/profile_photo.' . $extension;
		} else {
			$image_path = UM()->uploader()->get_upload_base_dir() . um_user( 'ID' ) . '/profile_photo.' . $extension;
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

		$image->save( $image_path );

		$image->set_quality( $quality );

		$sizes_array = array();

		$all_sizes = UM()->config()->get( 'avatar_thumbnail_sizes' );
		foreach ( $all_sizes as $size ) {
			$sizes_array[] = array( 'width' => $size );
		}

		$image->multi_resize( $sizes_array );

		if ( file_exists( $temp_image_path ) ) {
			unlink( $temp_image_path );
		}

		update_user_meta( $user_id, 'profile_photo', 'profile_photo.' . $extension );
		delete_user_meta( $user_id, 'profile_photo_metadata_temp' );
		delete_user_meta( $user_id, 'synced_profile_photo' );

		// Flush the user's cache.
		UM()->common()->users()->remove_cache( $user_id );

		wp_send_json_success(
			array(
				'avatar'    => get_avatar( $user_id, 128, '', '', array( 'loading' => 'lazy', 'um-cache' => false ) ),
				'all_sizes' => array(
					's'  => get_avatar( $user_id, 24, '', '', array( 'loading' => 'lazy', 'um-cache' => false ) ),
					'm'  => get_avatar( $user_id, 32, '', '', array( 'loading' => 'lazy', 'um-cache' => false ) ),
					'l'  => get_avatar( $user_id, 64, '', '', array( 'loading' => 'lazy', 'um-cache' => false ) ),
					'xl' => get_avatar( $user_id, 128, '', '', array( 'loading' => 'lazy', 'um-cache' => false ) ),
				),
			)
		);
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
	 * @since 2.8.4
	 */
	public function unique_filename( $dir, $name, $ext ) {
		$hashed = hash( 'ripemd160', time() . wp_rand( 10, 1000 ) . $name );
		return "profile_photo_{$hashed}{$ext}";
	}

	/**
	 * Image upload by AJAX
	 *
	 * @throws \Exception
	 */
	public function upload_profile_photo() {
		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'um_upload_profile_photo' ) ) {
			wp_send_json(
				array(
					'OK'   => 0,
					'info' => __( 'Invalid nonce.', 'ultimate-member' ),
				)
			);
		}

		if ( ! array_key_exists( 'user_id', $_REQUEST ) ) {
			wp_send_json(
				array(
					'OK'   => 0,
					'info' => __( 'Invalid data.', 'ultimate-member' ),
				)
			);
		}

		$user_id = absint( $_REQUEST['user_id'] );

		if ( ! UM()->roles()->um_current_user_can( 'edit', $user_id ) ) {
			wp_send_json(
				array(
					'OK'   => 0,
					'info' => __( 'You can not edit this user.', 'ultimate-member' ),
				)
			);
		}

		$files  = array();
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
		 * Filters the MIME-types of the images that can be uploaded as Company Logo.
		 *
		 * @since 2.0
		 * @hook um_image_upload_allowed_mimes
		 *
		 * @param {array} $mime_types MIME types.
		 *
		 * @return {array} MIME types.
		 */
		$mimes = apply_filters(
			'um_image_upload_allowed_mimes',
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

			if ( isset( $_COOKIE['um-profile-photo-upload'] ) && $chunks > 1 ) {
				$unique_name = sanitize_file_name( $_COOKIE['um-profile-photo-upload'] );
				$filepath    = UM()->common()->filesystem()->temp_upload_dir . DIRECTORY_SEPARATOR . $unique_name;

				$image_type = wp_check_filetype( $unique_name, $mimes );
				if ( ! $image_type['ext'] ) {
					wp_send_json(
						array(
							'OK'   => 0,
							'info' => __( 'Wrong filetype.', 'jobboardwp' ),
						)
					);
				}
			} else {
				$unique_name = wp_unique_filename( UM()->common()->filesystem()->temp_upload_dir, $filename, array( &$this, 'unique_filename' ) );
				$filepath    = UM()->common()->filesystem()->temp_upload_dir . DIRECTORY_SEPARATOR . $unique_name;
				if ( $chunks > 1 ) {
					UM()->setcookie( 'um-profile-photo-upload', $unique_name );
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
							'info' => __( 'Failed to open input stream.', 'jobboardwp' ),
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
						'info' => __( 'Failed to open output stream.', 'jobboardwp' ),
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
				$fileinfo['path']        = UM()->common()->filesystem()->temp_upload_dir . DIRECTORY_SEPARATOR . $fileinfo['name_saved'];
				$fileinfo['url']         = UM()->common()->filesystem()->temp_upload_url . '/' . $fileinfo['name_saved'];
				$fileinfo['size']        = filesize( $fileinfo['file'] );
				$fileinfo['size_format'] = size_format( $fileinfo['size'] );
				$fileinfo['time']        = gmdate( 'Y-m-d H:i:s', filemtime( $fileinfo['file'] ) );

				$files[] = $fileinfo;

				update_user_meta( $user_id, 'um_temp_profile_photo', $fileinfo );
				UM()->setcookie( 'um-profile-photo-upload', false );

			} else {
				wp_send_json(
					array(
						'OK'   => 1,
						'info' => __( 'Upload successful.', 'jobboardwp' ),
					)
				);
			}
		}

		wp_send_json_success( $files );

		$ret['error'] = null;
		$ret = array();

		$id = sanitize_text_field( $_POST['key'] );
		$timestamp = absint( $_POST['timestamp'] );
		$nonce = sanitize_text_field( $_POST['_wpnonce'] );
		$user_id = empty( $_POST['user_id'] ) ? get_current_user_id() : absint( $_POST['user_id'] );

		UM()->fields()->set_id = absint( $_POST['set_id'] );
		UM()->fields()->set_mode = sanitize_key( $_POST['set_mode'] );

		if ( UM()->fields()->set_mode != 'register' && ! UM()->roles()->um_current_user_can( 'edit', $user_id ) ) {
			$ret['error'] = __( 'You have no permission to edit this user', 'ultimate-member' );
			wp_send_json_error( $ret );
		}

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
		$um_image_upload_nonce = apply_filters( 'um_image_upload_nonce', true );

		if ( $um_image_upload_nonce ) {
			if ( ! wp_verify_nonce( $nonce, "um_upload_nonce-{$timestamp}" ) && is_user_logged_in() ) {
				// This nonce is not valid.
				$ret['error'] = __( 'Invalid nonce', 'ultimate-member' );
				wp_send_json_error( $ret );
			}
		}

		if ( isset( $_FILES[ $id ]['name'] ) ) {

			if ( ! is_array( $_FILES[ $id ]['name'] ) ) {

				UM()->uploader()->replace_upload_dir = true;
				$uploaded = UM()->uploader()->upload_image( $_FILES[ $id ], $user_id, $id );
				UM()->uploader()->replace_upload_dir = false;
				if ( isset( $uploaded['error'] ) ) {
					$ret['error'] = $uploaded['error'];
				} else {
					$ret[] = $uploaded['handle_upload'];
				}

			}

		} else {
			$ret['error'] = __( 'A theme or plugin compatibility issue', 'ultimate-member' );
		}
		wp_send_json_success( $ret );
	}
}
