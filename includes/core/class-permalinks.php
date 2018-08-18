<?php
namespace um\core;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'um\core\Permalinks' ) ) {


	/**
	 * Class Permalinks
	 * @package um\core
	 */
	class Permalinks {


		/**
		 * @var
		 */
		var $core;


		/**
		 * @var
		 */
		var $current_url;


		/**
		 * Permalinks constructor.
		 */
		function __construct() {

			add_action( 'init',  array( &$this, 'set_current_url' ), 0 );

			add_action( 'init',  array( &$this, 'check_for_querystrings' ), 1 );

			add_action( 'init',  array( &$this, 'activate_account_via_email_link' ), 1 );

			//add_action( 'um_user_after_updating_profile', array( &$this, 'profile_url' ), 1 );

			remove_action( 'wp_head', 'rel_canonical' );

			add_action( 'wp_head',  array( &$this, 'um_rel_canonical_' ), 9 );
		}


		/**
		 * Set current URL variable
		 */
		function set_current_url() {
			$this->current_url = $this->get_current_url();
		}


		/**
		 * SEO canonical href bugfix
		 */
		function um_rel_canonical_() {
			global $wp_the_query;

			if ( ! is_singular() )
				return;

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_allow_canonical__filter
			 * @description Allow canonical
			 * @input_vars
			 * [{"var":"$allow_canonical","type":"bool","desc":"Allow?"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_allow_canonical__filter', 'function_name', 10, 1 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_allow_canonical__filter', 'my_allow_canonical', 10, 1 );
			 * function my_allow_canonical( $allow_canonical ) {
			 *     // your code here
			 *     return $allow_canonical;
			 * }
			 * ?>
			 */
			$enable_canonical = apply_filters( "um_allow_canonical__filter", true );

			if( ! $enable_canonical )
				return;

			if ( !$id = $wp_the_query->get_queried_object_id() )
				return;

			if( UM()->config()->permalinks['user'] == $id ) {
				$link = $this->get_current_url();
				echo "<link rel='canonical' href='$link' />\n";
				return;
			}

			$link = get_permalink( $id );
			if ( $page = get_query_var('cpage') ){
				$link = get_comments_pagenum_link( $page );
				echo "<link rel='canonical' href='$link' />\n";
			}

		}


		/**
		 * Get query as array
		 *
		 * @return array
		 */
		function get_query_array() {
			$parts = parse_url( $this->get_current_url() );
			if ( isset( $parts['query'] ) ) {
				parse_str( $parts['query'], $query );
				return $query;
			}

			return array();
		}


		/**
		 * Get current URL anywhere
		 *
		 * @param bool $no_query_params
		 *
		 * @return mixed|void
		 */
		function get_current_url( $no_query_params = false ) {
			//use WP native function for fill $_SERVER variables by correct values
			wp_fix_server_vars();

			$page_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

			if ( $no_query_params == true ) {
				$page_url = strtok( $page_url, '?' );
			}

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_get_current_page_url
			 * @description Change current page URL
			 * @input_vars
			 * [{"var":"$page_url","type":"string","desc":"Page URL"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_get_current_page_url', 'function_name', 10, 1 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_get_current_page_url', 'my_get_current_page_url', 10, 1 );
			 * function my_get_current_page_url( $page_url ) {
			 *     // your code here
			 *     return $page_url;
			 * }
			 * ?>
			 */
			return apply_filters( 'um_get_current_page_url', $page_url );
		}


		/**
		 * Activates an account via email
		 */
		function activate_account_via_email_link() {
			if ( isset($_REQUEST['act']) && $_REQUEST['act'] == 'activate_via_email' && isset($_REQUEST['hash']) && is_string($_REQUEST['hash']) && strlen($_REQUEST['hash']) == 40 &&
			     isset($_REQUEST['user_id']) && is_numeric($_REQUEST['user_id']) ) { // valid token

				$user_id = absint( $_REQUEST['user_id'] );
				delete_option( "um_cache_userdata_{$user_id}" );

				um_fetch_user( $user_id );

				if (  strtolower($_REQUEST['hash']) !== strtolower( um_user('account_secret_hash') )  )
					wp_die( __( 'This activation link is expired or has already been used.','ultimate-member' ) );

				UM()->user()->approve();
				$redirect = ( um_user('url_email_activate') ) ? um_user('url_email_activate') : um_get_core_page('login', 'account_active');
				$login    = (bool) um_user('login_email_activate');

				// log in automatically
				if ( ! is_user_logged_in() && $login ) {
					$user = get_userdata($user_id);
					$user_id = $user->ID;

					// update wp user
					wp_set_current_user( $user_id, $user->user_login );
					wp_set_auth_cookie( $user_id );

					ob_start();
					do_action( 'wp_login', $user->user_login, $user );
					ob_end_clean();
				}

				um_reset_user();
				/**
				 * UM hook
				 *
				 * @type action
				 * @title um_after_email_confirmation
				 * @description Action on user activation
				 * @input_vars
				 * [{"var":"$user_id","type":"int","desc":"User ID"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_action( 'um_after_email_confirmation', 'function_name', 10, 1 );
				 * @example
				 * <?php
				 * add_action( 'um_after_email_confirmation', 'my_after_email_confirmation', 10, 1 );
				 * function my_after_email_confirmation( $user_id ) {
				 *     // your code here
				 * }
				 * ?>
				 */
				do_action( 'um_after_email_confirmation', $user_id );

				exit( wp_redirect( $redirect ) );

			}

		}


		/**
		 * Makes an activate link for any user
		 *
		 * @return bool|mixed|string|void
		 */
		function activate_url() {
			if ( !um_user('account_secret_hash') ) {
				return false;
			}

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_activate_url
			 * @description Change activate user URL
			 * @input_vars
			 * [{"var":"$url","type":"string","desc":"Activate URL"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_activate_url', 'function_name', 10, 1 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_activate_url', 'my_activate_url', 10, 1 );
			 * function my_activate_url( $url ) {
			 *     // your code here
			 *     return $url;
			 * }
			 * ?>
			 */
			$url =  apply_filters( 'um_activate_url', home_url() );
			$url =  add_query_arg( 'act', 'activate_via_email', $url );
			$url =  add_query_arg( 'hash', um_user('account_secret_hash'), $url );
			$url =  add_query_arg( 'user_id', um_user('ID'), $url );

			return $url;
		}


		/**
		 * Checks for UM query strings
		 */
		function check_for_querystrings() {
			if ( isset( $_REQUEST['message'] ) ) {
				UM()->shortcodes()->message_mode = true;
			}
		}


		/**
		 * Add a query param to url
		 *
		 * @param $key
		 * @param $value
		 *
		 * @return string
		 */
		function add_query( $key, $value ) {
			$this->current_url =  add_query_arg( $key, $value, $this->get_current_url() );
			return $this->current_url;
		}


		/**
		 * Remove a query param from url
		 *
		 * @param $key
		 * @param $value
		 *
		 * @return string
		 */
		function remove_query( $key, $value ) {
			$this->current_url = remove_query_arg( $key, $this->current_url );
			return $this->current_url;
		}


		/**
		* @param $slug
		*
		* @return int|null|string
		*/
		function slug_exists_user_id( $slug ) {
			global $wpdb;

			$permalink_base = UM()->options()->get( 'permalink_base' );

			$user_id = $wpdb->get_var(
				"SELECT user_id 
				FROM {$wpdb->usermeta} 
				WHERE meta_key = 'um_user_profile_url_slug_{$permalink_base}' AND 
					  meta_value = '{$slug}'
				ORDER BY umeta_id ASC
				LIMIT 1"
			);

			if ( ! empty( $user_id ) ) {
				return $user_id;
			}

			return false;
		}


		/**
		 * Get Profile Permalink
		 *
		 * @param  string $slug
		 * @return string $profile_url
		 */
		function profile_permalink( $slug ) {

			$page_id = UM()->config()->permalinks['user'];
			$profile_url = get_permalink( $page_id );

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_localize_permalink_filter
			 * @description Change user profile URL
			 * @input_vars
			 * [{"var":"$profile_url","type":"string","desc":"Profile URL"},
			 * {"var":"$page_id","type":"int","desc":"Profile Page ID"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_localize_permalink_filter', 'function_name', 10, 2 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_localize_permalink_filter', 'my_localize_permalink', 10, 2 );
			 * function my_localize_permalink( $profile_url, $page_id ) {
			 *     // your code here
			 *     return $profile_url;
			 * }
			 * ?>
			 */
			$profile_url = apply_filters( 'um_localize_permalink_filter', $profile_url, $page_id );

			if ( get_option('permalink_structure') ) {

				$profile_url = trailingslashit( untrailingslashit( $profile_url ) );
				$profile_url = $profile_url . strtolower( $slug ). '/';

			} else {

				$profile_url =  add_query_arg( 'um_user', $slug, $profile_url );

			}

			return ! empty( $profile_url ) ? strtolower( $profile_url ) : '';
		}


		/**
		 * Generate profile slug
		 *
		 * @param string $full_name
		 * @param string $first_name
		 * @param string $last_name
		 * @return string
		 */
		function profile_slug( $full_name, $first_name, $last_name ){

			$permalink_base = UM()->options()->get( 'permalink_base' );

			$user_in_url = '';

			$full_name = str_replace("'", "", $full_name );
			$full_name = str_replace("&", "", $full_name );
			$full_name = str_replace("/", "", $full_name );

			switch( $permalink_base ) {
				case 'name': // dotted

					$full_name_slug = $full_name;
					$difficulties = 0;


					if( strpos( $full_name, '.' ) > -1 ){
						$full_name = str_replace(".", "_", $full_name );
						$difficulties++;
					}

					$full_name = strtolower( str_replace( " ", ".", $full_name ) );

					if( strpos( $full_name, '_.' ) > -1 ){
						$full_name  = str_replace('_.', '_', $full_name );
						$difficulties++;
					}

					$full_name_slug = str_replace( '-' ,  '.', $full_name_slug );
					$full_name_slug = str_replace( ' ' ,  '.', $full_name_slug );
					$full_name_slug = str_replace( '..' , '.', $full_name_slug );

					if( strpos( $full_name, '.' ) > -1 ){
						$full_name  = str_replace('.', ' ', $full_name );
						$difficulties++;
					}

					$user_in_url = rawurlencode( $full_name_slug );

					break;

				case 'name_dash': // dashed

					$difficulties = 0;

					$full_name_slug = strtolower( $full_name );

					// if last name has dashed replace with underscore
					if( strpos( $last_name, '-') > -1 && strpos( $full_name, '-' ) > -1 ){
						$difficulties++;
						$full_name  = str_replace('-', '_', $full_name  );
					}
					// if first name has dashed replace with underscore
					if( strpos( $first_name, '-') > -1 && strpos( $full_name, '-' ) > -1 ){
						$difficulties++;
						$full_name  = str_replace('-', '_', $full_name  );
					}
					// if name has space, replace with dash
					$full_name_slug = str_replace( ' ' ,  '-', $full_name_slug );

					// if name has period
					if( strpos( $last_name, '.') > -1 && strpos( $full_name, '.' ) > -1 ){
						$difficulties++;
					}

					$full_name_slug = str_replace( '.' ,  '-', $full_name_slug );
					$full_name_slug = str_replace( '--' , '-', $full_name_slug );

					$user_in_url = rawurlencode(  $full_name_slug );

					break;

				case 'name_plus': // plus

					$difficulties = 0;

					$full_name_slug = strtolower( $full_name );

					// if last name has dashed replace with underscore
					if( strpos( $last_name, '+') > -1 && strpos( $full_name, '+' ) > -1 ){
						$difficulties++;
						$full_name  = str_replace('-', '_', $full_name  );
					}
					// if first name has dashed replace with underscore
					if( strpos( $first_name, '+') > -1 && strpos( $full_name, '+' ) > -1 ){
						$difficulties++;
						$full_name  = str_replace('-', '_', $full_name  );
					}
					if( strpos( $last_name, '-') > -1 || strpos( $first_name, '-') > -1 || strpos( $full_name, '-') > -1 ){
						$difficulties++;
					}
					// if name has space, replace with dash
					$full_name_slug = str_replace( ' ' ,  '+', $full_name_slug );
					$full_name_slug = str_replace( '-' ,  '+', $full_name_slug );

					// if name has period
					if( strpos( $last_name, '.') > -1 && strpos( $full_name, '.' ) > -1 ){
						$difficulties++;
					}

					$full_name_slug = str_replace( '.' ,  '+', $full_name_slug );
					$full_name_slug = str_replace( '++' , '+', $full_name_slug );

					$user_in_url = $full_name_slug;

					break;
			}

			return $user_in_url ;
		}


		/**
		 * Get action url for admin use
		 *
		 * @param $action
		 * @param $subaction
		 *
		 * @return mixed|string|void
		 */
		function admin_act_url( $action, $subaction ) {
			$url = $this->get_current_url();
			$url =  add_query_arg( 'um_adm_action', $action, $url );
			$url =  add_query_arg( 'sub', $subaction, $url );
			$url =  add_query_arg( 'user_id', um_user('ID'), $url );
			return $url;
		}
	}
}
