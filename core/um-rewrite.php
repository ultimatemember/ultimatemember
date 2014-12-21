<?php

class UM_Rewrite {

	function __construct() {
		
		add_filter('query_vars', array(&$this, 'query_vars'), 10, 1 );
		
		add_action('init', array(&$this, 'rewrite_rules') );
		
		add_action('template_redirect', array(&$this, 'locate_user_profile'), 9999 );
		
	}
	
	/***
	***	@modify global query vars
	***/
	function query_vars($public_query_vars) {
		$public_query_vars[] = 'um_user';
		$public_query_vars[] = 'um_tab';
		$public_query_vars[] = 'members_page';
		return $public_query_vars;
	}
	
	/***
	***	@setup rewrite rules
	***/
	function rewrite_rules(){
	
		global $ultimatemember;
		
		if ( isset( $ultimatemember->permalinks->core['user'] ) ) {
		
			$user_page_id = $ultimatemember->permalinks->core['user'];
			
			$account_page_id = $ultimatemember->permalinks->core['account'];
			
			add_rewrite_rule(
				'^user/([^/]*)$',
				'index.php?page_id='.$user_page_id.'&um_user=$matches[1]',
				'top'
			);
			
			add_rewrite_rule(
				'^account/([^/]*)$',
				'index.php?page_id='.$account_page_id.'&um_tab=$matches[1]',
				'top'
			);
			
			flush_rewrite_rules();
			
		}
		
	}
	
	/***
	***	@locate/display a profile
	***/
	function locate_user_profile() {
		global $post, $ultimatemember;
		
		if ( um_queried_user() && um_is_core_page('user') ) {
		
			if ( um_get_option('permalink_base') == 'user_login' ) {
				$user_id = username_exists( um_queried_user() );
				if ( $user_id ) {
					um_set_requested_user( $user_id );
				} else {
					exit( wp_redirect( um_get_core_page('user') ) );
				}
			}
			
			if ( um_get_option('permalink_base') == 'user_id' ) {
				$user_id = $ultimatemember->user->user_exists_by_id( um_queried_user() );
				if ( $user_id ) {
					um_set_requested_user( $user_id );
				} else {
					exit( wp_redirect( um_get_core_page('user') ) );
				}
			}
			
			if ( um_get_option('permalink_base') == 'name' ) {
				$user_id = $ultimatemember->user->user_exists_by_name( um_queried_user() );
				if ( $user_id ) {
					um_set_requested_user( $user_id );
				} else {
					exit( wp_redirect( um_get_core_page('user') ) );
				}
			}
			
		}

	}

}