<?php

class UM_Permalinks {

	function __construct() {

		global $wp;

		$this->core = get_option('um_core_pages');

		add_action('init',  array(&$this, 'check_for_querystrings'), 1);

		add_action('init',  array(&$this, 'activate_account_via_email_link'), 1);

		remove_action( 'wp_head', 'rel_canonical' );
		add_action('wp_head',  array(&$this, 'um_rel_canonical_'), 9 );

		$this->current_url = $this->get_current_url();

	}

	/***
	***	@SEO canonical href bugfix
	***/
	function um_rel_canonical_() {
		if ( !is_singular() )
			return;

		global $ultimatemember, $wp_the_query;
		if ( !$id = $wp_the_query->get_queried_object_id() )
			return;

		if( $this->core['user'] == $id ) {
			$link = $this->get_current_url();
			echo "<link rel='canonical' href='$link' />\n";
			return;
		}

		$link = get_permalink( $id );
		if ( $page = get_query_var('cpage') )
			$link = get_comments_pagenum_link( $page );
		echo "<link rel='canonical' href='$link' />\n";

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

			$um_get_option = get_option('um_options');
			$server_name_method = ( $um_get_option['current_url_method'] ) ? $um_get_option['current_url_method'] : 'SERVER_NAME';
			$um_port_forwarding_url = ( isset( $um_get_option['um_port_forwarding_url'] ) ) ? $um_get_option['um_port_forwarding_url']: '';

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

			if ( $um_port_forwarding_url == 1 && isset( $_SERVER["SERVER_PORT"] ) ) {
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

		if ( isset($_REQUEST['act']) && $_REQUEST['act'] == 'activate_via_email' && isset($_REQUEST['hash']) && is_string($_REQUEST['hash']) && strlen($_REQUEST['hash']) == 40 &&
			isset($_REQUEST['user_id']) && is_numeric($_REQUEST['user_id']) ) { // valid token

				$user_id = absint( $_REQUEST['user_id'] );
				delete_option( "um_cache_userdata_{$user_id}" );

				um_fetch_user( $user_id );

				if (  strtolower($_REQUEST['hash']) !== strtolower( um_user('account_secret_hash') )  )
					wp_die( __( 'This activation link is expired or have already been used.','ultimatemember' ) );

				$ultimatemember->user->approve();
				$redirect = ( um_user('url_email_activate') ) ? um_user('url_email_activate') : um_get_core_page('login', 'account_active');
				$login    = (bool) um_user('login_email_activate');

				// log in automatically
				if ( !is_user_logged_in() && $login ) {
					$user = get_userdata($user_id);
					$user_id = $user->ID;

					// update wp user
					wp_set_current_user( $user_id, $user_login );
					wp_set_auth_cookie( $user_id );

					ob_start();
					do_action( 'wp_login', $user_login );
					ob_end_clean();
				}

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
		global $ultimatemember, $wpdb;

		$page_id = $this->core['user'];
		$profile_url = get_permalink( $page_id );


		$profile_url = apply_filters('um_localize_permalink_filter', $this->core, $page_id, $profile_url );


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

		$full_name_permalinks = array( 'name', 'name_dash', 'name_plus' );
		if( in_array( um_get_option( 'permalink_base'),  $full_name_permalinks ) )
		{
			$full_name = um_user( 'full_name' );
			$last_name = um_user( 'last_name' );
			$first_name = um_user( 'first_name' );

			$count  = intval( um_is_meta_value_exists( 'full_name', $full_name ) );


			if( $count > 1 )
			{
				$full_name .= ' ' . um_user( 'ID' );
			}

			switch( um_get_option('permalink_base') )
			{
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

		
					if( $difficulties > 0 ){
						update_user_meta( um_user('ID'), 'um_user_profile_url_slug_name_'.$full_name_slug, $full_name );
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

					if( $difficulties > 0 ){
						update_user_meta( um_user('ID'), 'um_user_profile_url_slug_name_'.$full_name_slug, $full_name );
					}

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

					if( $difficulties > 0 ){
						update_user_meta( um_user('ID'), 'um_user_profile_url_slug_name_'.$full_name_slug, $full_name );
					}

					$user_in_url = $full_name_slug;
					
					break;
			}


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
