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

		$result = wp_delete_file( $temp_profile_photo['path'] );
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
		check_ajax_referer( 'um_upload_profile_photo_apply', 'nonce' );

		if ( ! array_key_exists( 'user_id', $_REQUEST ) ) {
			wp_send_json_error( __( 'Invalid data', 'ultimate-member' ) );
		}

		// @todo check form ID and form metadata for locked profile photo loader.
//		$disable_photo_uploader = empty( $post_data['use_custom_settings'] ) ? UM()->options()->get( 'disable_profile_photo_upload' ) : $post_data['disable_photo_upload'];
//		if ( $disable_photo_uploader ) {
//			wp_send_json_error( esc_js( __( 'You have no permission to edit this field', 'ultimate-member' ) ) );
//		}

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

		$extension  = pathinfo( $temp_image_path, PATHINFO_EXTENSION );
		$image_dir  = UM()->common()->filesystem()->get_user_uploads_dir( um_user( 'ID' ) ) . DIRECTORY_SEPARATOR;
		$image_path = wp_normalize_path( $image_dir . 'profile_photo.' . $extension );

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

		// Flush user directory from original profile photo thumbnails.
		$files = scandir( $image_dir );
		if ( ! empty( $files ) ) {
			foreach ( $files as $file ) {
				if ( preg_match( '/^profile_photo-(.*?)/', $file ) ) {
					wp_delete_file( wp_normalize_path( $image_dir . $file ) );
				}
			}
		}

		// Creates new file's thumbnails.
		$sizes_array = array();
		$all_sizes   = UM()->config()->get( 'avatar_thumbnail_sizes' );
		foreach ( $all_sizes as $size ) {
			$sizes_array[] = array( 'width' => $size );
		}
		$image->multi_resize( $sizes_array );

		// Remove temp original file used for crop.
		if ( file_exists( $temp_image_path ) ) {
			wp_delete_file( $temp_image_path );
		}

		update_user_meta( $user_id, 'profile_photo', 'profile_photo.' . $extension );
		delete_user_meta( $user_id, 'profile_photo_metadata_temp' );
		delete_user_meta( $user_id, 'synced_profile_photo' );

		// Flush the user's cache.
		UM()->common()->users()->remove_cache( $user_id );

		$avatar_args = array(
			'loading'  => 'lazy',
			'um-cache' => false,
		);

		wp_send_json_success(
			array(
				'avatar'    => get_avatar( $user_id, 128, '', '', $avatar_args ),
				'all_sizes' => array(
					's'  => get_avatar( $user_id, 24, '', '', $avatar_args ),
					'm'  => get_avatar( $user_id, 32, '', '', $avatar_args ),
					'l'  => get_avatar( $user_id, 64, '', '', $avatar_args ),
					'xl' => get_avatar( $user_id, 128, '', '', $avatar_args ),
				),
			)
		);
	}
}
