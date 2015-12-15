<?php

class UM_Permalinks {

	function __construct() {

		global $wp;

		$this->core = get_option('um_core_pages');

		add_action('init',  array(&$this, 'check_for_querystrings'), 1);

		add_action('init',  array(&$this, 'activate_account_via_email_link'), 1);

		$this->current_url = $this->get_current_url();

	}

	/***
	***	@Get query as array
	***/
	function get_query_array() {
		$parts = parse_url( $this->get_current_url() );
		if ( isset( $parts['query'] ) ) {
			parse_str($parts['query'], $query);
			return $query;
		}
	}

	/***
	***	@Get current URL anywhere
	***/
	function get_current_url( $no_query_params = false ) {
		global $post;

		$server_name_method = ( um_get_option('current_url_method') ) ? um_get_option('current_url_method') : 'SERVER_NAME';

		if ( !isset( $_SERVER['SERVER_NAME'] ) )
			return '';

		if ( is_front_page() ) {
			$page_url = home_url();

			if( isset( $_SERVER['QUERY_STRING'] ) && trim( $_SERVER['QUERY_STRING'] ) ) {
				$page_url .= '?' . $_SERVER['QUERY_STRING'];
			}
		} else {
			$page_url = 'http';

			if ( isset( $_SERVER["HTTPS"] ) && $_SERVER["HTTPS"] == "on" ) {
				$page_url .= "s";
			}
			$page_url .= "://";

			if ( isset( $_SERVER["SERVER_PORT"] ) && $_SERVER["SERVER_PORT"] != "80" && $_SERVER["SERVER_PORT"] != "443" ) {
				$page_url .= $_SERVER[ $server_name_method ].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
			} else {
				$page_url .= $_SERVER[ $server_name_method ].$_SERVER["REQUEST_URI"];
			}
		}

		if ( $no_query_params == true ) {
			$page_url = strtok($page_url, '?');
		}

		return apply_filters( 'um_get_current_page_url', $page_url );
	}

	/***
	***	@activates an account via email
	***/
	function activate_account_via_email_link(){
		global $ultimatemember;

		if ( isset($_REQUEST['act']) && $_REQUEST['act'] == 'activate_via_email' && isset($_REQUEST['hash']) && strlen($_REQUEST['hash']) == 40 &&
			isset($_REQUEST['user_id']) && is_numeric($_REQUEST['user_id']) ) { // valid token

				$user_id = absint( $_REQUEST['user_id'] );
				delete_option( "um_cache_userdata_{$user_id}" );

				um_fetch_user( $user_id );

				if ( um_user('account_status') != 'awaiting_email_confirmation' ) wp_die('The activation link you used is invalid or has expired.');

				if ( $_REQUEST['hash'] != um_user('account_secret_hash') ) wp_die('The secret key provided does not match this one for the user.');

				$ultimatemember->user->approve();
				$redirect = ( um_user('url_email_activate') ) ? um_user('url_email_activate') : um_get_core_page('login', 'account_active');

				um_reset_user();

				do_action('um_after_email_confirmation', $user_id );

				exit( wp_redirect( $redirect ) );

		}

	}

	/***
	***	@makes an activate link for any user
	***/
	function activate_url(){
		global $ultimatemember;

		if ( !um_user('account_secret_hash') ) return false;

		$url =  add_query_arg( 'act', 'activate_via_email', home_url() );
		$url =  add_query_arg( 'hash', um_user('account_secret_hash'), $url );
		$url =  add_query_arg( 'user_id', um_user('ID'), $url );

		return $url;
	}

	/***
	***	@checks for UM query strings
	***/
	function check_for_querystrings(){
		global $ultimatemember;
		if ( isset($_REQUEST['message']) )
			$ultimatemember->shortcodes->message_mode = true;
	}

	/***
	***	@add a query param to url
	***/
	function add_query( $key, $value ) {
		$this->current_url =  add_query_arg( $key, $value, $this->current_url );
		return $this->current_url;
	}
	/***
	***	@remove a query param from url
	***/
	function remove_query( $key, $value ) {
		$this->current_url = remove_query_arg( $key, $this->current_url );
		return $this->current_url;
	}

	/***
	***	@get profile url for set user
	***/
	function profile_url() {
		global $ultimatemember;

		$page_id = $this->core['user'];
		$profile_url = get_permalink( $page_id );

		if ( function_exists('icl_get_current_language') && icl_get_current_language() != icl_get_default_language() ) {
			if ( get_the_ID() > 0 && get_post_meta( get_the_ID(), '_um_wpml_user', true ) == 1 ) {
				$profile_url = get_permalink( get_the_ID() );
			}
		}

		if ( um_get_option('permalink_base') == 'user_login' ) {
			$user_in_url = um_user('user_login');

			if ( is_email($user_in_url) ) {
				$user_in_url = str_replace('@','',$user_in_url);
				if( ( $pos = strrpos( $user_in_url , '.' ) ) !== false ) {
					$search_length  = strlen( '.' );
					$user_in_url    = substr_replace( $user_in_url , '-' , $pos , $search_length );
				}
			} else {

				$user_in_url = sanitize_title( $user_in_url );

			}

		}

		if ( um_get_option('permalink_base') == 'user_id' ) {
			$user_in_url = um_user('ID');
		}

		if ( um_get_option('permalink_base') == 'name' ) {
			$user_in_url = rawurlencode( strtolower( um_user('full_name') ) );
		}

		if ( get_option('permalink_structure') ) {

			$profile_url = trailingslashit( untrailingslashit( $profile_url ) );
			$profile_url = $profile_url . $user_in_url . '/';

		} else {

			$profile_url =  add_query_arg( 'um_user', $user_in_url, $profile_url );

		}

		return $profile_url;
	}

	/***
	***	@get action url for admin use
	***/
	function admin_act_url( $action, $subaction ) {
		$url = $this->get_current_url();
		$url =  add_query_arg( 'um_adm_action', $action, $url );
		$url =  add_query_arg( 'sub', $subaction, $url );
		$url =  add_query_arg( 'user_id', um_user('ID'), $url );
		return $url;
	}

}
