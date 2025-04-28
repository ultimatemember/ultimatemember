<?php
namespace um\legacy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\legacy\User_Posts' ) ) {

	/**
	 * Class User_posts
	 * @package um\legacy
	 */
	class User_Posts {

		/**
		 * User_posts constructor.
		 */
		public function __construct() {
			add_action( 'um_profile_content_posts', array( &$this, 'add_posts' ) );
			add_action( 'um_profile_content_comments', array( &$this, 'add_comments' ) );
			add_action( 'wp_ajax_um_ajax_paginate_posts', array( &$this, 'load_posts' ) );
			add_action( 'wp_ajax_nopriv_um_ajax_paginate_posts', array( &$this, 'load_posts' ) );
			add_action( 'wp_ajax_um_ajax_paginate_comments', array( &$this, 'load_comments' ) );
			add_action( 'wp_ajax_nopriv_um_ajax_paginate_comments', array( &$this, 'load_comments' ) );
			add_filter( 'um_pretty_number_formatting', array( &$this, 'pretty_number_formatting_default' ) );
		}

		/**
		 * Add posts
		 */
		public function add_posts() {
			$args = array(
				'post_type'        => 'post',
				'posts_per_page'   => 10,
				'offset'           => 0,
				'author'           => um_get_requested_user(),
				'post_status'      => array( 'publish' ),
				'um_main_query'    => true,
				'suppress_filters' => false,
			);

			/** This filter is documented in includes/frontend/class-profile.php */
			$args  = apply_filters( 'um_profile_query_make_posts', $args );
			$posts = get_posts( $args );

			$args['posts_per_page'] = -1;
			$args['fields']         = 'ids';
			unset( $args['offset'] );
			$count_posts = get_posts( $args );
			if ( ! empty( $count_posts ) && ! is_wp_error( $count_posts ) ) {
				$count_posts = count( $count_posts );
			}

			UM()->get_template(
				'profile/posts.php',
				'',
				array(
					'posts'       => $posts,
					'count_posts' => $count_posts,
				),
				true
			);
		}

		/**
		 * Add comments
		 */
		public function add_comments() {
			$comments = get_comments(
				array(
					'number'       => 10,
					'offset'       => 0,
					'user_id'      => um_user( 'ID' ),
					'post_status'  => array( 'publish' ),
					'type__not_in' => apply_filters( 'um_excluded_comment_types', array( '' ) ),
				)
			);

			$comments_count = get_comments(
				array(
					'user_id'      => um_user( 'ID' ),
					'post_status'  => array( 'publish' ),
					'type__not_in' => apply_filters( 'um_excluded_comment_types', array( '' ) ),
					'count'        => 1,
				)
			);

			UM()->get_template(
				'profile/comments.php',
				'',
				array(
					'comments'       => $comments,
					'count_comments' => $comments_count,
				),
				true
			);
		}

		/**
		 * Dynamic load of posts
		 *
		 */
		public function load_posts() {
			UM()->check_ajax_nonce();

			$author = ! empty( $_POST['author'] ) ? absint( $_POST['author'] ) : get_current_user_id();
			$page   = ! empty( $_POST['page'] ) ? absint( $_POST['page'] ) : 0;

			$args = array(
				'post_type'        => 'post',
				'posts_per_page'   => 10,
				'offset'           => ( $page - 1 ) * 10,
				'author'           => $author,
				'post_status'      => array( 'publish' ),
				'um_main_query'    => true,
				'suppress_filters' => false,
			);

			/** This filter is documented in includes/frontend/class-profile.php */
			$args  = apply_filters( 'um_profile_query_make_posts', $args );
			$posts = get_posts( $args );

			UM()->get_template( 'profile/posts.php', '', array( 'posts' => $posts ), true );
			wp_die();
		}

		/**
		 * Dynamic load of comments
		 */
		public function load_comments() {
			UM()->check_ajax_nonce();

			$user_id = ! empty( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : get_current_user_id();
			$page    = ! empty( $_POST['page'] ) ? absint( $_POST['page'] ) : 0;

			$comments = get_comments(
				array(
					'number'       => 10,
					'offset'       => ( $page - 1 ) * 10,
					'user_id'      => $user_id,
					'post_status'  => array( 'publish' ),
					'type__not_in' => apply_filters( 'um_excluded_comment_types', array( '' ) ),
				)
			);

			UM()->get_template( 'profile/comments.php', '', array( 'comments' => $comments ), true );
			wp_die();
		}

		/**
		 * Count posts by type
		 *
		 * @param string $user_id
		 * @param string $post_type
		 *
		 * @return int|string
		 */
		public function count_user_posts_by_type( $user_id = '', $post_type = 'post' ) {
			global $wpdb;
			if ( ! $user_id ) {
				$user_id = um_user( 'ID' );
			}

			if ( ! $user_id ) {
				return 0;
			}

			$where = get_posts_by_author_sql( $post_type, true, $user_id );
			$count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts $where" );

			return $this->pretty_number_formatting( $count );
		}

		/**
		 * Count comments
		 *
		 * @param int|null $user_id
		 *
		 * @return int|string
		 */
		public function count_user_comments( $user_id = null ) {
			global $wpdb;
			if ( ! $user_id ) {
				$user_id = um_user( 'ID' );
			}

			if ( ! $user_id ) {
				return 0;
			}

			$count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(comment_ID)
					FROM {$wpdb->comments}
					WHERE user_id = %d AND
						  comment_approved = '1'",
					$user_id
				)
			);

			return $this->pretty_number_formatting( $count );
		}

		/**
		 * @param int $count
		 *
		 * @return string
		 */
		private function pretty_number_formatting( $count ) {
			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_pretty_number_formatting
			 * @description Change User Posts count value
			 * @input_vars
			 * [{"var":"$count","type":"int","desc":"Posts Count"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_pretty_number_formatting', 'function_name', 10, 1 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_pretty_number_formatting', 'my_pretty_number_formatting', 10, 1 );
			 * function my_pretty_number_formatting( $count ) {
			 *     // your code here
			 *     return $count;
			 * }
			 * ?>
			 */
			return apply_filters( 'um_pretty_number_formatting', $count );
		}

		/**
		 * Formats numbers nicely
		 *
		 * @param $count
		 *
		 * @return string
		 */
		public function pretty_number_formatting_default( $count ) {
			$count = (int) $count;
			return number_format( $count );
		}
	}
}
