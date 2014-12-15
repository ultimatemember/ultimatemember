<?php

	/***
	***	@Adjust available tabs
	***/
	add_filter('um_account_page_default_tabs_hook', 'um_account_page_default_tabs_hook' );
	function um_account_page_default_tabs_hook( $tabs ) {
		global $ultimatemember;
		
		foreach ($tabs as $k => $arr ) {
			foreach( $arr as $id => $info ) {
				if ( $id == 'delete' ) {
					if ( !um_user('can_delete_profile') && !um_user('can_delete_everyone') ) {
						unset( $tabs[$k][$id] );
					}
				}
				
				if ( $id == 'privacy' ) {
					if ( !um_user('can_make_private_profile') ) {
						unset( $tabs[$k][$id] );
					}
				}
				
			}
		}
		
		return $tabs;
	
	}