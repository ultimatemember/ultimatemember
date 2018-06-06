<?php
namespace um\core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'um\core\Query' ) ) {


	/**
	 * Class Query
	 * @package um\core
	 */
	class Query {


		/**
		 * @var array
		 */
		public $wp_pages = array();


		/**
		 * @var array
		 */
		public $roles = array();


		/**
		 * Query constructor.
		 */
		function __construct() {


		}


		/**
		 * Ajax pagination for posts
		 */
		function ajax_paginate() {
			/**
			 * @var $hook
			 * @var $args
			 */
			extract( $_REQUEST );

			ob_start();

			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_ajax_load_posts__{$hook}
			 * @description Action on posts loading by AJAX
			 * @input_vars
			 * [{"var":"$args","type":"array","desc":"Query arguments"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_ajax_load_posts__{$hook}', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_action( 'um_ajax_load_posts__{$hook}', 'my_ajax_load_posts', 10, 1 );
			 * function my_ajax_load_posts( $args ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( "um_ajax_load_posts__{$hook}", $args );

			$output = ob_get_contents();
			ob_end_clean();

			die( $output );
		}


		/**
		 * Get wp pages
		 *
		 * @return array|string
		 */
		function wp_pages() {
			global $wpdb;

			if( isset( $this->wp_pages ) && ! empty( $this->wp_pages ) ){
				return $this->wp_pages;
			}

			$count_pages = wp_count_posts('page');

			if ( $count_pages->publish > 300 ){
				return 'reached_maximum_limit';
			}


			$pages = $wpdb->get_results('SELECT * FROM '.$wpdb->posts.' WHERE post_type = "page" AND post_status = "publish" ', OBJECT);

			$array = array();
			if( $wpdb->num_rows > 0 ){
				foreach ($pages as $page_data) {
					$array[ $page_data->ID ] = $page_data->post_title;
				}
			}

			$this->wp_pages = $array;

			return $array;
		}


		/**
		 * Get all forms
		 *
		 * @return mixed
		 */
		function forms() {
			$results = array();

			$args = array(
				'post_type' => 'um_form',
				'posts_per_page' => 200,
				'post_status' => array('publish')
			);
			$query = new \WP_Query( $args );

			foreach ( $query->posts as $post ) {
				setup_postdata( $post );
				$results[ $post->ID ] = $post->post_title;
			}
			return $results;
		}


		/**
		 * Do custom queries
		 *
		 * @param $args
		 *
		 * @return array|bool|int|\WP_Query
		 */
		function make( $args ) {

			$defaults = array(
				'post_type' => 'post',
				'post_status' => array('publish')
			);
			$args = wp_parse_args( $args, $defaults );

			if ( isset( $args['post__in'] ) && empty( $args['post__in'] ) )
				return false;

			extract( $args );

			if ( $post_type == 'comment' ) { // comments

				unset( $args['post_type'] );

				/**
				 * UM hook
				 *
				 * @type filter
				 * @title um_excluded_comment_types
				 * @description Extend excluded comment types
				 * @input_vars
				 * [{"var":"$types","type":"array","desc":"Comment Types"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage
				 * <?php add_filter( 'um_excluded_comment_types', 'function_name', 10, 1 ); ?>
				 * @example
				 * <?php
				 * add_filter( 'um_excluded_comment_types', 'my_excluded_comment_types', 10, 1 );
				 * function my_profile_active_tab( $types ) {
				 *     // your code here
				 *     return $types;
				 * }
				 * ?>
				 */
				$args['type__not_in'] = apply_filters( 'um_excluded_comment_types', array('') );

				$comments = get_comments($args);
				return $comments;

			} else {
				$custom_posts = new \WP_Query();
				$args['post_status'] = is_array( $args['post_status'] ) ? $args['post_status'] : explode( ',', $args['post_status'] );

				$custom_posts->query( $args );

				return $custom_posts;
			}
		}


		/**
		 * Get last users
		 *
		 * @param int $number
		 *
		 * @return array
		 */
		function get_recent_users($number = 5){
			$args = array( 'fields' => 'ID', 'number' => $number, 'orderby' => 'user_registered', 'order' => 'desc' );

			$users = new \WP_User_Query( $args );
			return $users->results;
		}


		/**
		 * Count users by status
		 *
		 * @param $status
		 *
		 * @return int
		 */
		function count_users_by_status( $status ) {
			$args = array( 'fields' => 'ID', 'number' => 0 );
			if ( $status == 'unassigned' ) {
				$args['meta_query'][] = array(array('key' => 'account_status','compare' => 'NOT EXISTS'));
				$users = new \WP_User_Query( $args );
				foreach ( $users->results as $user ) {
					update_user_meta( $user, 'account_status', 'approved' );
				}
			} else {
				$args['meta_query'][] = array(array('key' => 'account_status','value' => $status,'compare' => '='));
			}
			$users = new \WP_User_Query( $args );
			return count( $users->results );
		}


		/**
		 * Get users by status
		 *
		 * @param $status
		 * @param int $number
		 *
		 * @return array
		 */
		function get_users_by_status($status, $number = 5){
			$args = array( 'fields' => 'ID', 'number' => $number, 'orderby' => 'user_registered', 'order' => 'desc' );

			$args['meta_query'][] = array(
				array(
					'key'     => 'account_status',
					'value'   => $status,
					'compare' => '='
				)
			);

			$users = new \WP_User_Query( $args );
			return $users->results;
		}


		/**
		 * Count all users
		 *
		 * @return mixed
		 */
		function count_users() {
			$result = count_users();
			return $result['total_users'];
		}


		/**
		 * Using wpdb instead of update_post_meta
		 *
		 * @param $key
		 * @param $post_id
		 * @param $new_value
		 */
		function update_attr( $key, $post_id, $new_value ){
			update_post_meta( $post_id, '_um_' . $key, $new_value );
		}


		/**
		 * Get data
		 *
		 * @param $key
		 * @param $post_id
		 *
		 * @return mixed
		 */
		function get_attr( $key, $post_id ) {
			$meta = get_post_meta( $post_id, '_um_' . $key, true );
			return $meta;
		}


		/**
		 * Delete data
		 *
		 * @param $key
		 * @param $post_id
		 *
		 * @return bool
		 */
		function delete_attr( $key, $post_id ) {
			$meta = delete_post_meta( $post_id, '_um_' . $key );
			return $meta;
		}


		/**
		 * Checks if post has a specific meta key
		 *
		 * @param $key
		 * @param null $value
		 * @param null $post_id
		 *
		 * @return bool
		 */
		function has_post_meta( $key, $value = null, $post_id = null ) {
			if ( ! $post_id ) {
				global $post;
				$post_id = $post->ID;
			}
			if ( $value ) {
				if ( get_post_meta( $post_id, $key, true ) == $value ) {
					return true;
				}
			} else {
				if ( get_post_meta( $post_id, $key, true ) ) {
					return true;
				}
			}
			return false;
		}


		/**
		 * Get posts with specific meta key/value
		 *
		 * @param $post_type
		 * @param $key
		 * @param $value
		 *
		 * @return bool
		 */
		function find_post_id( $post_type, $key, $value ) {
			$posts = get_posts( array( 'post_type' => $post_type, 'meta_key' => $key, 'meta_value' => $value ) );
			if ( isset( $posts[0] ) && ! empty( $posts ) )
				return $posts[0]->ID;
			return false;
		}


		/**
		 * Get post data
		 *
		 * @param $post_id
		 *
		 * @return mixed
		 */
		function post_data( $post_id ) {
			$array['form_id'] = $post_id;
			$mode = $this->get_attr('mode', $post_id);
			$meta = get_post_custom( $post_id );
			foreach ($meta as $k => $v){
				if ( strstr($k, '_um_'.$mode.'_' ) ) {
					$k = str_replace('_um_'.$mode.'_', '', $k);
					$array[$k] = $v[0];
				} elseif ($k == '_um_mode'){
					$k = str_replace('_um_', '', $k);
					$array[$k] = $v[0];
				} elseif ( strstr($k, '_um_') ) {
					$k = str_replace('_um_', '', $k);
					$array[$k] = $v[0];
				}

			}

			foreach( $array as $k => $v ) {
				if ( strstr( $k, 'login_') || strstr( $k, 'register_' ) || strstr( $k, 'profile_' ) ){
					if ( $mode != 'directory' ) {
						unset($array[$k]);
					}
				}
			}
			return $array;
		}


		/**
		 * Capture selected value
		 *
		 * @param $key
		 * @param null $array_key
		 * @param null $fallback
		 * @return int|mixed|null|string
		 */
		function get_meta_value( $key, $array_key = null, $fallback = null ) {
			$post_id = get_the_ID();
			$try = get_post_meta( $post_id, $key, true );

			//old version if ( ! empty( $try ) )
			if ( false !== $try )
				if ( is_array( $try ) && in_array( $array_key, $try ) ) {
					return $array_key;
				} else if ( is_array( $try ) ) {
					return '';
				} else {
					return $try;
				}

			if ( $fallback == 'na' ) {
				$fallback = 0;
				$none = '';
			} else {
				$none = 0;
			}
			return ! empty( $fallback ) ? $fallback : $none;
		}


		/**
		 * Checks if its a core page of UM
		 *
		 * @param $post_id
		 *
		 * @return bool|mixed
		 */
		function is_core( $post_id ){
			$is_core = get_post_meta($post_id, '_um_core', true);
			if ( $is_core != '' ) {
				return $is_core;
			} else {
				return false;
			}
		}

	}
}
