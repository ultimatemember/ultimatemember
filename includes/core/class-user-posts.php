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

			add_action( 'um_ajax_load_posts__um_load_posts', array( &$this, 'load_posts' ) );
			add_action( 'um_ajax_load_posts__um_load_comments', array( &$this, 'load_comments' ) );
		}


		/**
		 * Dynamic load of posts
		 *
		 * @param array $args
		 */
		function load_posts( $args ) {
			$array = explode(',', $args );
			$post_type = $array[0];
			$posts_per_page = $array[1];
			$offset = $array[2];
			$author = $array[3];

			$offset_n = $posts_per_page + $offset;

			UM()->shortcodes()->modified_args = "$post_type,$posts_per_page,$offset_n,$author";

			UM()->shortcodes()->loop = UM()->query()->make("post_type=$post_type&posts_per_page=$posts_per_page&offset=$offset&author=$author");

			UM()->shortcodes()->load_template('profile/posts-single');
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
		 * Add posts
		 */
		function add_posts() {
			UM()->shortcodes()->load_template( 'profile/posts' );
		}


		/**
		 * Add comments
		 */
		function add_comments() {
			UM()->shortcodes()->load_template( 'profile/comments' );
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