<?php
namespace um\core;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'User_posts' ) ) {
    class User_posts {

        function __construct() {

            //add_filter('um_profile_tabs', array(&$this, 'add_tab'), 100);

            add_action('um_profile_content_posts', array(&$this, 'add_posts') );
            add_action('um_profile_content_comments', array(&$this, 'add_comments') );

            add_action('um_ajax_load_posts__um_load_posts', array(&$this, 'load_posts') );
            add_action('um_ajax_load_posts__um_load_comments', array(&$this, 'load_comments') );

        }

        /***
         ***	@dynamic load of posts
         ***/
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

        /***
         ***	@dynamic load of comments
         ***/
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


        /***
         ***	@add posts
         ***/
        function add_posts() {
            UM()->shortcodes()->load_template('profile/posts');
        }

        /***
         ***	@add comments
         ***/
        function add_comments() {
            UM()->shortcodes()->load_template('profile/comments');
        }

        /***
         ***	@count posts
         ***/
        function count_user_posts_by_type( $user_id= '', $post_type = 'post' ) {
            global $wpdb;
            if ( !$user_id )
                $user_id = um_user('ID');

            if ( !$user_id ) return 0;

            $where = get_posts_by_author_sql( $post_type, true, $user_id );
            $count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts $where" );

            return apply_filters('um_pretty_number_formatting', $count);
        }

        /***
         ***	@count comments
         ***/
        function count_user_comments( $user_id = null ) {
            global $wpdb;
            if ( !$user_id )
                $user_id = um_user('ID');

            if ( !$user_id ) return 0;

            $count = $wpdb->get_var("SELECT COUNT(comment_ID) FROM " . $wpdb->comments. " WHERE user_id = " . $user_id . " AND comment_approved = '1'");

            return apply_filters('um_pretty_number_formatting', $count);
        }

    }
}