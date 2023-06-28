<?php
namespace um\core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
		public function __construct() {
		}

		/**
		 * Ajax pagination for posts
		 */
		public function ajax_paginate() {
			UM()->check_ajax_nonce();

			// phpcs:disable WordPress.Security.NonceVerification
			if ( ! isset( $_REQUEST['hook'] ) ) {
				wp_send_json_error( __( 'Invalid hook.', 'ultimate-member' ) );
			}
			$hook = sanitize_key( $_REQUEST['hook'] );

			$args = ! empty( $_REQUEST['args'] ) ? $_REQUEST['args'] : array();
			// phpcs:enable WordPress.Security.NonceVerification

			ob_start();

			/**
			 * Fires on posts loading by AJAX in User Profile tabs.
			 *
			 * @since 1.3.x
			 * @hook  um_ajax_load_posts__{$hook}
			 *
			 * @param {array} $args Request.
			 *
			 * @example <caption>Make any custom action on when posts loading by AJAX in User Profile.</caption>
			 * function my_ajax_load_posts( $args ) {
			 *     // your code here
			 * }
			 * add_action( 'um_ajax_load_posts__{$hook}', 'my_ajax_load_posts', 10, 1 );
			 */
			do_action( "um_ajax_load_posts__{$hook}", $args );

			$output = ob_get_clean();
			// @todo: investigate using WP_KSES
			die( $output );
		}

		/**
		 * Get wp pages
		 *
		 * @return array|string
		 */
		public function wp_pages() {
			global $wpdb;

			if( isset( $this->wp_pages ) && ! empty( $this->wp_pages ) ){
				return $this->wp_pages;
			}

			$count_pages = wp_count_posts('page');

			if ( $count_pages->publish > 300 ){
				return 'reached_maximum_limit';
			}

			$pages = $wpdb->get_results(
				"SELECT *
				FROM {$wpdb->posts}
				WHERE post_type = 'page' AND
				      post_status = 'publish'",
				OBJECT
			);

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
		public function forms() {
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
		 * @param array $args
		 *
		 * @return array|bool|int|\WP_Query
		 */
		public function make( $args ) {
			$defaults = array(
				'post_type'   => 'post',
				'post_status' => array( 'publish' ),
			);
			$args     = wp_parse_args( $args, $defaults );

			if ( isset( $args['post__in'] ) && empty( $args['post__in'] ) ) {
				return false;
			}

			if ( 'comment' === $args['post_type'] ) {
				// Comments query.
				unset( $args['post_type'] );
				/**
				 * Filters excluded comment types.
				 *
				 * @since 1.3.x
				 * @hook  um_excluded_comment_types
				 *
				 * @param {array} $types Comment Types.
				 *
				 * @return {array} Comment Types.
				 *
				 * @example <caption>Extend excluded comment types.</caption>
				 * function my_excluded_comment_types( $types ) {
				 *     // your code here
				 *     return $types;
				 * }
				 * add_filter( 'um_excluded_comment_types', 'my_excluded_comment_types' );
				 */
				$args['type__not_in'] = apply_filters( 'um_excluded_comment_types', array( '' ) );

				return get_comments( $args );
			}

			$custom_posts        = new \WP_Query();
			$args['post_status'] = is_array( $args['post_status'] ) ? $args['post_status'] : explode( ',', $args['post_status'] );

			$custom_posts->query( $args );

			return $custom_posts;
		}


		/**
		 * Get last users
		 *
		 * @param int $number
		 *
		 * @return array
		 */
		function get_recent_users( $number = 5 ) {
			$args = array( 'fields' => 'ID', 'number' => $number, 'orderby' => 'user_registered', 'order' => 'desc' );

			$users = new \WP_User_Query( $args );
			return $users->results;
		}


		/**
		 * Count users by status
		 *
		 * @since 2.4.2 $status = 'unassigned' is unused. Please use `UM()->setup()->set_default_user_status()` instead. Will be deprecated since 3.0
		 *
		 * @param $status
		 *
		 * @return int
		 */
		function count_users_by_status( $status ) {
			if ( 'unassigned' === $status ) {
				_deprecated_argument(
					__FUNCTION__,
					'2.4.2',
					__( 'The "unassigned" $status has been removed. Use `UM()->setup()->set_default_user_status()` for setting up default user account status.', 'ultimate-member' )
				);

				UM()->setup()->set_default_user_status();
				return 0;
			}

			$users_count = get_transient( "um_count_users_{$status}" );
			if ( false === $users_count ) {
				$args = array(
					'fields'               => 'ids',
					'number'               => 1,
					'meta_query'           => array(
						array(
							'key'     => 'account_status',
							'value'   => $status,
							'compare' => '=',
						),
					),
					'um_custom_user_query' => true,
				);

				$users = new \WP_User_Query( $args );
				if ( empty( $users ) || is_wp_error( $users ) ) {
					$users_count = 0;
				} else {
					$users_count = $users->get_total();
				}

				set_transient( "um_count_users_{$status}", $users_count );
			}

			return $users_count;
		}


		/**
		 * Get pending users (in queue)
		 *
		 * @return int
		 */
		function get_pending_users_count() {
			$users_count = get_transient( 'um_count_users_pending_dot' );
			if ( false === $users_count ) {
				$args = array(
					'fields'               => 'ids',
					'number'               => 1,
					'meta_query'           => array(
						'relation' => 'OR',
						array(
							'key'     => 'account_status',
							'value'   => 'awaiting_email_confirmation',
							'compare' => '=',
						),
						array(
							'key'     => 'account_status',
							'value'   => 'awaiting_admin_review',
							'compare' => '=',
						),
					),
					'um_custom_user_query' => true,
				);

				/**
				 * UM hook
				 *
				 * @type filter
				 * @title um_admin_pending_queue_filter
				 * @description Change user query arguments when get pending users
				 * @input_vars
				 * [{"var":"$args","type":"array","desc":"WP_Users query arguments"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage
				 * <?php add_filter( 'um_admin_pending_queue_filter', 'function_name', 10, 1 ); ?>
				 * @example
				 * <?php
				 * add_filter( 'um_admin_pending_queue_filter', 'my_admin_pending_queue', 10, 1 );
				 * function my_admin_pending_queue( $args ) {
				 *     // your code here
				 *     return $args;
				 * }
				 * ?>
				 */
				$args = apply_filters( 'um_admin_pending_queue_filter', $args );

				$users = new \WP_User_Query( $args );
				if ( empty( $users ) || is_wp_error( $users ) ) {
					$users_count = 0;
				} else {
					$users_count = $users->get_total();
				}

				set_transient( 'um_count_users_pending_dot', $users_count );
			}

			return $users_count;
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
		function update_attr( $key, $post_id, $new_value ) {
			/**
			 * Post meta values are passed through the stripslashes() function upon being stored.
			 * Function wp_slash() is added to compensate for the call to stripslashes().
			 * @see https://developer.wordpress.org/reference/functions/update_post_meta/
			 */
			if ( is_array( $new_value ) ) {
				foreach ( $new_value as $k => $val ) {
					if ( is_array( $val ) && array_key_exists( 'custom_dropdown_options_source', $val ) ) {
						$new_value[ $k ]['custom_dropdown_options_source'] = wp_slash( $val['custom_dropdown_options_source'] );
					}
				}
			}

			update_post_meta( $post_id, '_um_' . $key, $new_value );
		}

		/**
		 * Get postmeta related to Ultimate Member.
		 *
		 * @param string $key
		 * @param int    $post_id
		 *
		 * @return mixed
		 */
		public function get_attr( $key, $post_id ) {
			return get_post_meta( $post_id, '_um_' . $key, true );
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
		 * @param string $key
		 * @param string|null $array_key
		 * @param bool $fallback
		 * @return int|mixed|null|string
		 */
		function get_meta_value( $key, $array_key = null, $fallback = false ) {
			$post_id = get_the_ID();
			$try = get_post_meta( $post_id, $key, true );

			//old-old version if ( ! empty( $try ) )
			//old version if ( $try !== false )
			if ( $try != '' ) {
				if ( is_array( $try ) && in_array( $array_key, $try ) ) {
					return $array_key;
				} else if ( is_array( $try ) ) {
					return '';
				} else {
					return $try;
				}
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


		/**
		 * Get users by status
		 *
		 * @param $status
		 * @param int $number
		 *
		 * @deprecated 2.4.2
		 *
		 * @return array
		 */
		function get_users_by_status( $status, $number = 5 ) {
			_deprecated_function( __METHOD__, '2.4.2' );

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
	}
}
