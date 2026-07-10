<?php
namespace um\ajax;

use WP_Comment_Query;
use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Profile
 *
 * @package um\ajax
 */
class Profile {

	/**
	 * Profile constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_um_get_user_posts', array( $this, 'load_posts' ) );
		add_action( 'wp_ajax_nopriv_um_get_user_posts', array( $this, 'load_posts' ) );

		add_action( 'wp_ajax_um_get_user_comments', array( $this, 'load_comments' ) );
		add_action( 'wp_ajax_nopriv_um_get_user_comments', array( $this, 'load_comments' ) );
	}

	/**
	 * Dynamic load of posts
	 *
	 */
	public function load_posts() {
		// phpcs:disable WordPress.Security.NonceVerification
		if ( ! isset( $_POST['author'] ) ) {
			wp_send_json_error( __( 'Invalid user ID', 'ultimate-member' ) );
		}
		$author = absint( $_POST['author'] );
		// phpcs:enable WordPress.Security.NonceVerification

		if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'um_user_profile_posts' . $author ) ) {
			wp_send_json_error( __( 'Wrong nonce', 'ultimate-member' ) );
		}

		if ( ! UM()->common()->users()->can_view_user( $author ) ) {
			wp_send_json_error( __( 'You cannot view this user.', 'ultimate-member' ) );
		}

		if ( empty( $_POST['last_id'] ) ) {
			wp_send_json_error( __( 'Invalid last post ID', 'ultimate-member' ) );
		}

		$query_args = array(
			'post_type'        => 'post',
			'posts_per_page'   => UM()->frontend()->profile()::$posts_per_page,
			'offset'           => 0,
			'author'           => $author,
			'post_status'      => array( 'publish' ),
			'um_main_query'    => true,
			'suppress_filters' => false,
		);
		/** This filter is documented in includes/frontend/class-profile.php */
		$query_args = apply_filters( 'um_profile_query_make_posts', $query_args );

		$last_id = absint( $_POST['last_id'] );

		$filter_handler = static function ( $where ) use ( $last_id ) {
			global $wpdb;
			return $where . $wpdb->prepare( " AND {$wpdb->posts}.ID < %d", $last_id );
		};

		add_filter( 'posts_where', $filter_handler );
		$posts_query = new WP_Query( $query_args );
		remove_filter( 'posts_where', $filter_handler );

		$last_id = 0;
		if ( $posts_query->posts ) {
			$last_post = end( $posts_query->posts );
			$last_id   = absint( $last_post->ID );
		}

		$content = '';
		if ( ! empty( $posts_query->posts ) ) {
			foreach ( $posts_query->posts as $post ) {
				$content .= UM()->get_template( 'v3/profile/posts-item.php', '', array( 'post' => $post ) );
			}
		}

		$content = wp_kses( $content, UM()->get_allowed_html( 'templates' ) );
		wp_send_json_success(
			array(
				'content' => UM()->ajax()->esc_html_spaces( $content ),
				'last_id' => $last_id,
			)
		);
	}

	/**
	 * Dynamic load of comments
	 */
	public function load_comments() {
		// phpcs:disable WordPress.Security.NonceVerification
		if ( ! isset( $_POST['author'] ) ) {
			wp_send_json_error( __( 'Invalid user ID', 'ultimate-member' ) );
		}
		$author = absint( $_POST['author'] );
		// phpcs:enable WordPress.Security.NonceVerification

		if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'um_user_profile_comments' . $author ) ) {
			wp_send_json_error( __( 'Wrong nonce', 'ultimate-member' ) );
		}

		if ( ! UM()->common()->users()->can_view_user( $author ) ) {
			wp_send_json_error( __( 'You cannot view this user.', 'ultimate-member' ) );
		}

		if ( empty( $_POST['last_id'] ) ) {
			wp_send_json_error( __( 'Invalid last post ID', 'ultimate-member' ) );
		}

		$last_id = absint( $_POST['last_id'] );

		$filter_handler = static function ( $clauses ) use ( $last_id ) {
			global $wpdb;
			$clauses['where'] .= $wpdb->prepare( " AND {$wpdb->comments}.comment_ID < %d", $last_id );
			return $clauses;
		};

		$query_args = array(
			'number'      => UM()->frontend()->profile()::$comments_per_page,
			'offset'      => 0,
			'user_id'     => $author,
			'post_status' => array( 'publish' ),
			'post_type'   => array( 'post' ),
		);

		if ( get_current_user_id() !== $author && ! current_user_can( 'edit_posts' ) ) {
			$query_args['status'] = 'approve';
		}

		add_filter( 'comments_clauses', $filter_handler );
		$comments_query = new WP_Comment_Query( $query_args );
		remove_filter( 'comments_clauses', $filter_handler );

		$last_id = 0;
		if ( $comments_query->comments ) {
			$last_comment = end( $comments_query->comments );
			$last_id      = absint( $last_comment->comment_ID );
		}

		$content = '';
		if ( ! empty( $comments_query->comments ) ) {
			foreach ( $comments_query->comments as $comment ) {
				$content .= UM()->get_template( 'v3/profile/comments-item.php', '', array( 'comment' => $comment ) );
			}
		}

		$content = wp_kses( $content, UM()->get_allowed_html( 'templates' ) );
		wp_send_json_success(
			array(
				'content' => UM()->ajax()->esc_html_spaces( $content ),
				'last_id' => $last_id,
			)
		);
	}
}
