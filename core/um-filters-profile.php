<?php

	/***
	***	@Main tabs
	***/
	add_filter('um_profile_tabs', 'um_profile_tabs', 1 );
	function um_profile_tabs( $tabs ) {
		
		$tabs['main'] = array(
			'name' => __('About','ultimatemember'),
			'icon' => 'um-faicon-user'
		);
		
		return $tabs;
	}
	
	/***
	***	@dynamic profile page title
	***/
	add_filter('wp_title', 'um_dynamic_user_profile_pagetitle', 100000, 2 );
	function um_dynamic_user_profile_pagetitle( $title, $sep = '' ) {
		global $paged, $page, $ultimatemember;

		$profile_title = um_get_option('profile_title');

		if ( um_is_core_page('user') && um_get_requested_user() ) {
			
			um_fetch_user( um_get_requested_user() );
			
			$profile_title = um_convert_tags( $profile_title );
			
			$title = $profile_title;
			
			um_reset_user();
		
		}
		
		return $title;
	}
	
	/***
	***	@try and modify the page title in page
	***/
	add_filter('the_title', 'um_dynamic_user_profile_title', 100000, 2 );
	function um_dynamic_user_profile_title( $title, $id = '' ) {
		global $ultimatemember;
		
		if( is_admin() )
			return $title;

		if (  $id == $ultimatemember->permalinks->core['user'] ) {
			if ( um_is_core_page('user') && um_get_requested_user() ) {
				$title = um_get_display_name( um_get_requested_user() );
			} else if ( um_is_core_page('user') && is_user_logged_in() ) {
				$title = um_get_display_name( get_current_user_id() );
			}
		}
		
		return (strlen($title)!==strlen(utf8_decode($title))) ? $title : utf8_encode($title);
	}