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

		$dropdown_items = array(
			'<a href="#" class="um-manual-trigger" data-parent=".um-profile-photo" data-child=".um-btn-auto-width">' . esc_html__( 'Set photo', 'ultimate-member' ) . '</a>',
		);

		wp_send_json_success(
			array(
				'avatar'         => get_avatar( $user_id, 128, '', '', array( 'loading' => 'lazy' ) ),
				'dropdown_items' => $dropdown_items,
			)
		);
	}
}
