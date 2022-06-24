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
		function __construct() {
			add_action( 'wp_ajax_um_admin_review_registration', array( &$this, 'admin_review_registration' ) );
			add_action( 'wp_ajax_um_get_users', array( &$this, 'get_users' ) );
		}


		/**
		 *
		 */
		function admin_review_registration() {
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
		function get_users() {
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

			wp_send_json_success(
				array(
					'users'       => $users,
					'total_count' => $total_count,
				)
			);
		}
	}
}
