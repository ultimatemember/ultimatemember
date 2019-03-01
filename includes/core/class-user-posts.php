<?php
namespace um\core;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'um\core\User_posts' ) ) {


	/**
	 * Class User_posts
	 * @package um\core
	 */
	class User_posts {

		/**
		 * User_posts constructor.
		 */
		function __construct() {
			add_action( 'um_profile_content_posts', array( &$this, 'add_posts' ) );
			add_action( 'um_profile_content_comments', array( &$this, 'add_comments' ) );

			add_action( 'um_ajax_load_posts__um_load_comments', array( &$this, 'load_comments' ), 10, 1 );
		}


		/**
		 * Add posts
		 */
		function add_posts() {

			$args = array(
				'post_type'         => 'post',
				'posts_per_page'    => 10,
				'offset'            => 0,
				'author'            => um_get_requested_user(),
				'post_status'       => array( 'publish' )
			);

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_profile_query_make_posts
			 * @description Some changes of WP_Query Posts Tab
			 * @input_vars
			 * [{"var":"$query_posts","type":"WP_Query","desc":"UM Posts Tab query"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_profile_query_make_posts', 'function_name', 10, 1 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_profile_query_make_posts', 'my_profile_query_make_posts', 10, 1 );
			 * function my_profile_query_make_posts( $query_posts ) {
			 *     // your code here
			 *     return $query_posts;
			 * }
			 * ?>
			 */
			$args = apply_filters( 'um_profile_query_make_posts', $args );
			$posts = get_posts( $args );

			$count_posts = (int) count_user_posts( um_get_requested_user(), 'post', true );

			UM()->shortcodes()->set_args = array( 'posts' => $posts, 'count_posts' => $count_posts );
			UM()->shortcodes()->load_template( 'profile/posts' );
		}


		/**
		 * Add comments
		 */
		function add_comments() {
			UM()->shortcodes()->load_template( 'profile/comments' );
		}


		/**
		 * Dynamic load of posts
		 *
		 */
		function load_posts() {
			UM()->check_ajax_nonce();

			$author = ! empty( $_POST['author'] ) ? $_POST['author'] : get_current_user_id();
			$page = ! empty( $_POST['page'] ) ? $_POST['page'] : 0;

			$args = array(
				'post_type'         => 'post',
				'posts_per_page'    => 10,
				'offset'            => ( $page - 1 ) * 10,
				'author'            => $author,
				'post_status'       => array( 'publish' )
			);

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_profile_query_make_posts
			 * @description Some changes of WP_Query Posts Tab
			 * @input_vars
			 * [{"var":"$query_posts","type":"WP_Query","desc":"UM Posts Tab query"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_profile_query_make_posts', 'function_name', 10, 1 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_profile_query_make_posts', 'my_profile_query_make_posts', 10, 1 );
			 * function my_profile_query_make_posts( $query_posts ) {
			 *     // your code here
			 *     return $query_posts;
			 * }
			 * ?>
			 */
			$args = apply_filters( 'um_profile_query_make_posts', $args );
			$posts = get_posts( $args );

			UM()->shortcodes()->set_args = array( 'posts' => $posts );
			UM()->shortcodes()->load_template( 'profile/posts' );
			wp_die();
		}


		/**
		 * Dynamic load of comments
		 *
		 * @param $args
		 */
		function load_comments( $args ) {
			$array = explode(',', $args );
			$post_type = $array[0];
			$posts_per_page = $array[1];
			$offset = $array[2];
			$author = $array[3];

			$offset_n = $posts_per_page + $offset;

			UM()->shortcodes()->modified_args = "$post_type,$posts_per_page,$offset_n,$author";

			UM()->shortcodes()->loop = UM()->query()->make("post_type=$post_type&number=$posts_per_page&offset=$offset&user_id=$author");

			UM()->shortcodes()->load_template('profile/comments-single');
		}


		/**
		 * Count posts by type
		 *
		 * @param string $user_id
		 * @param string $post_type
		 *
		 * @return int|string
		 */
		function count_user_posts_by_type( $user_id= '', $post_type = 'post' ) {
			global $wpdb;
			if ( !$user_id )
				$user_id = um_user( 'ID' );

			if ( !$user_id ) return 0;

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
		function count_user_comments( $user_id = null ) {
			global $wpdb;
			if ( !$user_id )
				$user_id = um_user('ID');

			if ( !$user_id ) return 0;

			$count = $wpdb->get_var("SELECT COUNT(comment_ID) FROM " . $wpdb->comments. " WHERE user_id = " . $user_id . " AND comment_approved = '1'");

			return $this->pretty_number_formatting( $count );
		}


		/**
		 * @param int $count
		 *
		 * @return string
		 */
		function pretty_number_formatting( $count ) {
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

	}
}