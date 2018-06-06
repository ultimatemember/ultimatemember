<?php
namespace um\core;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'um\core\Rewrite' ) ) {


	/**
	 * Class Rewrite
	 * @package um\core
	 */
	class Rewrite {


		/**
		 * Rewrite constructor.
		 */
		function __construct() {
			//add rewrite rules
			add_filter( 'query_vars', array(&$this, 'query_vars'), 10, 1 );
			add_filter( 'rewrite_rules_array', array( &$this, '_add_rewrite_rules' ), 10, 1 );
			add_action( 'init', array( &$this, 'rewrite_rules'), 100000000 );


			add_action( 'template_redirect', array( &$this, 'redirect_author_page'), 9999 );
			add_action( 'template_redirect', array( &$this, 'locate_user_profile'), 9999 );
		}


		/**
		 * Modify global query vars
		 *
		 * @param $public_query_vars
		 *
		 * @return array
		 */
		function query_vars( $public_query_vars ) {
			$public_query_vars[] = 'um_user';
			$public_query_vars[] = 'um_tab';
			$public_query_vars[] = 'profiletab';
			$public_query_vars[] = 'subnav';

			$public_query_vars[] = 'um_page';
			$public_query_vars[] = 'um_action';
			$public_query_vars[] = 'um_resource';
			$public_query_vars[] = 'um_method';
			$public_query_vars[] = 'um_verify';

			return $public_query_vars;
		}


		/**
		 * Add UM rewrite rules
		 *
		 * @param $rules
		 *
		 * @return array
		 */
		function _add_rewrite_rules( $rules ) {
			$newrules = array();

			$newrules['um-api/([^/]+)/([^/]+)/([^/]+)/([^/]+)/?$'] = 'index.php?um_page=api&um_action=$matches[1]&um_resource=$matches[2]&um_method=$matches[3]&um_verify=$matches[4]';

			if ( isset( UM()->config()->permalinks['user'] ) ) {

				$user_page_id = UM()->config()->permalinks['user'];
				$user = get_post( $user_page_id );

				if ( isset( $user->post_name ) ) {

					$user_slug = $user->post_name;

					$add_lang_code = '';
					$language_code = '';

					if ( function_exists('icl_object_id') || function_exists('icl_get_current_language') ) {

						if ( function_exists('icl_get_current_language') ) {
							$language_code = icl_get_current_language();
						} elseif( function_exists('icl_object_id') && defined('ICL_LANGUAGE_CODE') ) {
							$language_code = ICL_LANGUAGE_CODE;
						}

						// User page translated slug
						$lang_post_id = icl_object_id( $user->ID, 'post', FALSE, $language_code );
						$lang_post_obj = get_post( $lang_post_id );
						if( isset( $lang_post_obj->post_name ) ){
							$user_slug = $lang_post_obj->post_name;
						}

						if(  $language_code != icl_get_default_language() ){
							$add_lang_code = $language_code;
						}

					}

					$newrules[ $user_slug.'/([^/]+)/?$' ] = 'index.php?page_id='.$user_page_id.'&um_user=$matches[1]&lang='.$add_lang_code;
				}
			}

			if ( isset( UM()->config()->permalinks['account'] ) ) {

				$account_page_id = UM()->config()->permalinks['account'];
				$account = get_post( $account_page_id );

				if ( isset( $account->post_name ) ) {

					$account_slug = $account->post_name;

					$add_lang_code = '';
					$language_code = '';

					if ( function_exists('icl_object_id') || function_exists('icl_get_current_language') ) {

						if ( function_exists('icl_get_current_language') ){
							$language_code = icl_get_current_language();
						} elseif( function_exists('icl_object_id') && defined('ICL_LANGUAGE_CODE') ) {
							$language_code = ICL_LANGUAGE_CODE;
						}

						// Account page translated slug
						$lang_post_id = icl_object_id( $account->ID, 'post', FALSE, $language_code );
						$lang_post_obj = get_post( $lang_post_id );
						if ( isset( $lang_post_obj->post_name ) ){
							$account_slug = $lang_post_obj->post_name;
						}

						if ( $language_code != icl_get_default_language() ) {
							$add_lang_code = $language_code;
						}

					}

					$newrules[ $account_slug.'/([^/]+)?$' ] = 'index.php?page_id='.$account_page_id.'&um_tab=$matches[1]&lang='.$add_lang_code;

				}

			}

			return $newrules + $rules;
		}


		/**
		 * Setup rewrite rules
		 */
		function rewrite_rules() {

			if ( isset( UM()->config()->permalinks['user'] ) && isset( UM()->config()->permalinks['account'] ) ) {

				/**
				 * UM hook
				 *
				 * @type filter
				 * @title um_rewrite_flush_rewrite_rules
				 * @description Enable flushing rewrite rules
				 * @input_vars
				 * [{"var":"$stop_flush","type":"bool","desc":"Stop flushing rewrite rules"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage
				 * <?php add_filter( 'um_rewrite_flush_rewrite_rules', 'function_name', 10, 1 ); ?>
				 * @example
				 * <?php
				 * add_filter( 'um_rewrite_flush_rewrite_rules', 'my_rewrite_flush_rewrite_rules', 10, 1 );
				 * function my_rewrite_flush_rewrite_rules( $stop_flush ) {
				 *     // your code here
				 *     return $stop_flush;
				 * }
				 * ?>
				 */
				if ( ! apply_filters( 'um_rewrite_flush_rewrite_rules', UM()->options()->get( 'um_flush_stop' ) ) ) {
					flush_rewrite_rules( true );
				}

			}

		}


		/**
		 * Author page to user profile redirect
		 */
		function redirect_author_page() {
			if ( UM()->options()->get( 'author_redirect' ) && is_author() ) {
				$id = get_query_var( 'author' );
				um_fetch_user( $id );
				exit( wp_redirect( um_user_profile_url() ) );
			}
		}


		/**
		 * Locate/display a profile
		 */
		function locate_user_profile() {
			global $post;

			if ( um_queried_user() && um_is_core_page('user') ) {

				if ( UM()->options()->get( 'permalink_base' ) == 'user_login' ) {

					$user_id = username_exists( um_queried_user() );

					// Try nice name
					if ( !$user_id ) {
						$slug = um_queried_user();
						$slug = str_replace('.','-',$slug);
						$the_user = get_user_by( 'slug', $slug );
						if ( isset( $the_user->ID ) ){
							$user_id = $the_user->ID;
						}

						if ( ! $user_id )
							$user_id = UM()->user()->user_exists_by_email_as_username( um_queried_user() );

						if ( ! $user_id )
							$user_id = UM()->user()->user_exists_by_email_as_username( $slug );

					}

				}

				if ( UM()->options()->get( 'permalink_base' ) == 'user_id' ) {
					$user_id = UM()->user()->user_exists_by_id( um_queried_user() );

				}

				if ( in_array( UM()->options()->get( 'permalink_base' ), array('name','name_dash','name_dot','name_plus') ) ) {
					$user_id = UM()->user()->user_exists_by_name( um_queried_user() );

				}

				/** USER EXISTS SET USER AND CONTINUE **/

				if ( $user_id ) {

					um_set_requested_user( $user_id );

					/**
					 * UM hook
					 *
					 * @type action
					 * @title um_access_profile
					 * @description Action on user access profile
					 * @input_vars
					 * [{"var":"$user_id","type":"int","desc":"User ID"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage add_action( 'um_access_profile', 'function_name', 10, 1 );
					 * @example
					 * <?php
					 * add_action( 'um_access_profile', 'my_access_profile', 10, 1 );
					 * function my_access_profile( $user_id ) {
					 *     // your code here
					 * }
					 * ?>
					 */
					do_action( 'um_access_profile', $user_id );

				} else {

					exit( wp_redirect( um_get_core_page( 'user' ) ) );

				}

			} else if ( um_is_core_page( 'user' ) ) {

				if ( is_user_logged_in() ) { // just redirect to their profile

					$query = UM()->permalinks()->get_query_array();

					$url = um_user_profile_url( um_user( 'ID' ) );

					if ( $query ) {
						foreach ( $query as $key => $val ) {
							$url = add_query_arg( $key, $val, $url );
						}
					}

					exit( wp_redirect( $url ) );
				} else {

					/**
					 * UM hook
					 *
					 * @type filter
					 * @title um_locate_user_profile_not_loggedin__redirect
					 * @description Change redirect URL from user profile for not logged in user
					 * @input_vars
					 * [{"var":"$url","type":"string","desc":"Redirect URL"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage
					 * <?php add_filter( 'um_locate_user_profile_not_loggedin__redirect', 'function_name', 10, 1 ); ?>
					 * @example
					 * <?php
					 * add_filter( 'um_locate_user_profile_not_loggedin__redirect', 'my_user_profile_not_loggedin__redirect', 10, 1 );
					 * function my_user_profile_not_loggedin__redirect( $url ) {
					 *     // your code here
					 *     return $url;
					 * }
					 * ?>
					 */
					$redirect_to = apply_filters( 'um_locate_user_profile_not_loggedin__redirect', home_url() );
					if ( ! empty( $redirect_to ) ){
						exit( wp_redirect( $redirect_to ) );
					}

				}

			}

		}

	}
}