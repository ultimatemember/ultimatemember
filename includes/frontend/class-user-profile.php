<?php
namespace um\frontend;

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
		}

		/**
		 * Check edit profile action and set edit mode or redirect if there aren't capabilities to edit.
		 * @return void
		 */
		public function handle_edit_screen() {
			if ( ! is_user_logged_in() ) {
				return;
			}

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
	}
}
