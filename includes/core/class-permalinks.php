<?php
namespace um\core;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Permalinks' ) ) {
    class Permalinks {
        var $core;
        var $current_url;

        function __construct() {

            add_action( 'init',  array( &$this, 'set_current_url' ), 0 );

            add_action( 'init',  array( &$this, 'check_for_querystrings' ), 1 );

            add_action( 'init',  array( &$this, 'activate_account_via_email_link' ), 1 );

            add_action( 'um_user_after_updating_profile', array( &$this, 'profile_url' ), 1 );

            remove_action( 'wp_head', 'rel_canonical' );

            add_action( 'wp_head',  array( &$this, 'um_rel_canonical_' ), 9 );

            add_filter( 'um_user_pre_updating_profile_array', array( &$this, 'um_user_updating_profile_need_change_permalink') );
        }


	    /**
	     * Set current URL variable
	     */
        function set_current_url() {
	        $this->current_url = $this->get_current_url();
        }


        /***
         ***	@SEO canonical href bugfix
         ***/
        function um_rel_canonical_() {
            global $wp_the_query;

            if ( !is_singular() )
                return;

            $enable_canonical = apply_filters("um_allow_canonical__filter", true );

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
	        $server_name_method = UM()->options()->get( 'current_url_method' );
	        $server_name_method = ! empty( $server_name_method ) ? $server_name_method : 'SERVER_NAME';

	        $um_port_forwarding_url = UM()->options()->get( 'um_port_forwarding_url' );
	        $um_port_forwarding_url = ! empty( $um_port_forwarding_url ) ? $um_port_forwarding_url : '';

            if ( is_multisite() ) {

                $page_url 	= '';
                $blog_id 	= get_current_blog_id();
                $siteurl 	= get_site_url( $blog_id );

                /*if ( is_front_page() ) {
                    $page_url = $siteurl;

                    if( isset( $_SERVER['QUERY_STRING'] ) && trim( $_SERVER['QUERY_STRING'] ) ) {
                        $page_url .= '?' . $_SERVER['QUERY_STRING'];
                    }
                } else {*/

                    $network_permalink_structure = UM()->options()->get( 'network_permalink_structure' );

                    if(  $network_permalink_structure == "sub-directory" ){

                        $page_url = 'http';

                        if ( isset( $_SERVER["HTTPS"] ) && $_SERVER["HTTPS"] == "on" ) {
                            $page_url .= "s";
                        }

                        $page_url .= "://";

                        $page_url .= $_SERVER[ $server_name_method ];
                    }else{
                        $page_url .= $siteurl;
                    }

                    if ( $um_port_forwarding_url == 1 && isset( $_SERVER["SERVER_PORT"] ) ) {
                        $page_url .= ":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];

                    } else {
                        $page_url .= $_SERVER["REQUEST_URI"];
                    }

                //}


            }else{
                if ( !isset( $_SERVER['SERVER_NAME'] ) )
                    return '';

                /*if ( is_front_page() ) {
                    $page_url = home_url();

                    if( isset( $_SERVER['QUERY_STRING'] ) && trim( $_SERVER['QUERY_STRING'] ) ) {
                        $page_url .= '?' . $_SERVER['QUERY_STRING'];
                    }
                } else {*/
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

                //}


            }

            if ( $no_query_params == true ) {
                $page_url = strtok( $page_url, '?' );
            }

            return apply_filters( 'um_get_current_page_url', $page_url );
        }

        /***
         ***	@activates an account via email
         ***/
        function activate_account_via_email_link(){
            if ( isset($_REQUEST['act']) && $_REQUEST['act'] == 'activate_via_email' && isset($_REQUEST['hash']) && is_string($_REQUEST['hash']) && strlen($_REQUEST['hash']) == 40 &&
                isset($_REQUEST['user_id']) && is_numeric($_REQUEST['user_id']) ) { // valid token

                $user_id = absint( $_REQUEST['user_id'] );
                delete_option( "um_cache_userdata_{$user_id}" );

                um_fetch_user( $user_id );

                if (  strtolower($_REQUEST['hash']) !== strtolower( um_user('account_secret_hash') )  )
                    wp_die( __( 'This activation link is expired or have already been used.','ultimate-member' ) );

                UM()->user()->approve();
                $redirect = ( um_user('url_email_activate') ) ? um_user('url_email_activate') : um_get_core_page('login', 'account_active');
                $login    = (bool) um_user('login_email_activate');

                // log in automatically
                if ( !is_user_logged_in() && $login ) {
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

                do_action('um_after_email_confirmation', $user_id );

                exit( wp_redirect( $redirect ) );

            }

        }

        /***
         ***	@makes an activate link for any user
         ***/
        function activate_url(){
            if ( !um_user('account_secret_hash') ) return false;
            $url =  apply_filters( 'um_activate_url', home_url() );
            $url =  add_query_arg( 'act', 'activate_via_email', $url );
            $url =  add_query_arg( 'hash', um_user('account_secret_hash'), $url );
            $url =  add_query_arg( 'user_id', um_user('ID'), $url );

            return $url;
        }

        /***
         ***	@checks for UM query strings
         ***/
        function check_for_querystrings(){
            if ( isset($_REQUEST['message']) )
                UM()->shortcodes()->message_mode = true;
        }

        /***
         ***	@add a query param to url
         ***/
        function add_query( $key, $value ) {
            $this->current_url =  add_query_arg( $key, $value, $this->get_current_url() );
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
        function profile_url( $update_slug = false ) {
            // Permalink base
            $permalink_base = UM()->options()->get( 'permalink_base' );


            // Get user slug
            $profile_slug = get_user_meta( um_user('ID'), "um_user_profile_url_slug_{$permalink_base}", true );
            $generate_slug = UM()->options()->get( 'um_generate_slug_in_directory' );

            // Return existing profile slug
            if( $generate_slug && $update_slug == false && $profile_slug  ){
                return $this->profile_permalink( $profile_slug );
            }

            // Reset cache
            if( $update_slug == true ){

                $user_id = um_user('ID');

                delete_option( "um_cache_userdata_{$user_id}" );

                um_fetch_user( $user_id );

            }

            // Username
            if ( $permalink_base == 'user_login' ) {
                $user_in_url = um_user('user_login');

                if ( is_email( $user_in_url ) ) {
                    $user_email = $user_in_url;
                    $user_in_url = str_replace('@','',$user_in_url);

                    if( ( $pos = strrpos( $user_in_url , '.' ) ) !== false ) {
                        $search_length  = strlen( '.' );
                        $user_in_url    = substr_replace( $user_in_url , '-' , $pos , $search_length );
                    }
                    update_user_meta( um_user('ID') , "um_email_as_username_{$user_in_url}" , $user_email );

                } else {

                    $user_in_url = sanitize_title( $user_in_url );

                }

            }

            // User ID
            if ( $permalink_base == 'user_id' ) {
                $user_in_url = um_user('ID');
            }

            // Fisrt and Last name
            $full_name_permalinks = array( 'name', 'name_dash', 'name_plus' );

	        if ( in_array( $permalink_base, $full_name_permalinks ) ) {
		        $separated = array('name' => '.','name_dash' =>'-','name_plus'=>'+');
		        $separate = $separated[$permalink_base];
		        $first_name       = um_user( 'first_name' );
		        $last_name        = um_user( 'last_name' );
		        $full_name  = sprintf('%s %s',$first_name,$last_name);
		        $full_name        = preg_replace( '/\s+/', ' ', $full_name ); // Remove double spaces
		        $profile_slug = UM()->permalinks()->profile_slug( $full_name, $first_name, $last_name );

		        if ( isset($update_slug['need_change_permalink']) && $update_slug['need_change_permalink'] == true ) {

			        $append    = 0;
			        $username  = $full_name;
			        $_username = $full_name;

			        while ( 1 ) {
				        $username = $_username . ( empty( $append ) ? '' : " $append" );
				        if ( ! $this->exist_url_slug_permalink_base( $permalink_base, $profile_slug . ( empty( $append ) ? '' : "{$separate}{$append}" ) ) ) {
					        break;
				        }
				        $append ++;

			        }
		        }

		        $user_in_url = $this->profile_slug( $username, $first_name, $last_name );
	        }

            update_user_meta( um_user('ID'), "um_user_profile_url_slug_{$permalink_base}", $user_in_url  );
            $profile_url = $this->profile_permalink( $user_in_url );

            return $profile_url;
        }


	    function exist_url_slug_permalink_base( $permalink_base, $slug ) {
		    global $wpdb;

		    if ( $user_id = $wpdb->get_var( "SELECT `user_id`  FROM `{$wpdb->usermeta}` WHERE `meta_key` = 'um_user_profile_url_slug_{$permalink_base}' AND `meta_value` = '{$slug}'" ) ) {
			    return $user_id;
		    }

		    return 0;
	    }

        /**
         * Get Profile Permalink
         * @param  string $slug
         * @return string $profile_url
         */
        function profile_permalink( $slug ) {

            $page_id = UM()->config()->permalinks['user'];
            $profile_url = get_permalink( $page_id );

            $profile_url = apply_filters('um_localize_permalink_filter', UM()->config()->permalinks, $page_id, $profile_url );

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

            switch( $permalink_base )
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

	    /**
	     * Adds the "need_change_permalink" parameter to recreate the user's permanent link
	     * @param array $to_update
	     * @return array $to_update
	     */
        function um_user_updating_profile_need_change_permalink( $to_update ) {

	        if ( um_user( 'first_name' ) != $to_update['first_name'] ||
	             um_user( 'last_name' ) != $to_update['last_name'] ) {
		        $to_update['need_change_permalink'] = true;
	        }
	        return $to_update;
        }
    }
}