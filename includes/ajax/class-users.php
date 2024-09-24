<?php
namespace um\ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Users
 *
 * @package um\ajax
 */
class Users {

	public function __construct() {
		add_action( 'wp_ajax_um_get_users', array( $this, 'get_users' ) );
	}

	public function get_users() {
		UM()->admin()->check_ajax_nonce();

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
}
