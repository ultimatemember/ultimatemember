<?php
namespace um\frontend;

use WP_Comment;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\frontend\User_Profile' ) ) {

	/**
	 * Class User_Profile
	 *
	 * @package um\frontend
	 */
	class User_Profile {

		/**
		 * User_Profile constructor.
		 */
		public function __construct() {
			add_action( 'template_redirect', array( $this, 'handle_edit_screen' ), 10000 );
			add_filter( 'get_edit_user_link', array( $this, 'change_edit_user_link' ), 10, 2 );
			add_filter( 'get_comment_author_url', array( $this, 'change_comment_author_url' ), 10, 3 );
		}

		/**
		 * Check edit profile action and set edit mode or redirect if there aren't capabilities to edit.
		 * @return void
		 */
		public function handle_edit_screen() {
			if ( ! is_user_logged_in() ) {
				return;
			}

			// phpcs:disable WordPress.Security.NonceVerification
			if ( ! isset( $_REQUEST['um_action'] ) ) {
				return;
			}

			$action = sanitize_key( $_REQUEST['um_action'] );

			if ( 'edit' !== $action ) {
				return;
			}

			$uid = 0;
			if ( isset( $_REQUEST['uid'] ) ) {
				$uid = absint( $_REQUEST['uid'] );
			}
			// phpcs:enable WordPress.Security.NonceVerification

			if ( ! empty( $uid ) && ! UM()->common()->users()::user_exists( $uid ) ) {
				return;
			}

			if ( ! empty( $uid ) && is_super_admin( $uid ) ) {
				wp_die( esc_html__( 'Super administrators can not be modified.', 'ultimate-member' ) );
			}

			UM()->fields()->editing = true;

			if ( ! um_is_myprofile() && ! UM()->roles()->um_current_user_can( 'edit', um_profile_id() ) ) {
				um_safe_redirect( UM()->permalinks()->get_current_url( true ) );
				exit;
			}

			if ( um_is_myprofile() && ! um_can_edit_my_profile() ) {
				um_safe_redirect( um_edit_my_profile_cancel_uri() );
				exit;
			}
		}

		/**
		 * Returns the user profile edit link using Ultimate Member plugin.
		 *
		 * @param string $link    The default edit user link.
		 * @param int    $user_id The ID of the user.
		 *
		 * @return string The customized user profile edit link.
		 */
		public function change_edit_user_link( $link, $user_id ) {
			return um_edit_profile_url( $user_id );
		}

		/**
		 * Retrieves the user profile URL for the given comment author.
		 *
		 * @param string          $comment_author_url The URL of the comment author.
		 * @param int             $comment_id         The ID of the comment.
		 * @param WP_Comment|null $comment            The comment object.
		 *
		 * @return string The user profile URL for the comment author.
		 */
		public function change_comment_author_url( $comment_author_url, $comment_id, $comment ) {
			if ( ! is_null( $comment ) && ! empty( $comment->user_id ) ) {
				return um_user_profile_url( $comment->user_id );
			}

			return $comment_author_url;
		}
	}
}
