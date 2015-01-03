<?php

	/***
	***	@Adjust available tabs
	***/
	add_filter('um_account_page_default_tabs_hook', 'um_account_page_default_tabs_hook' );
	function um_account_page_default_tabs_hook( $tabs ) {
		global $ultimatemember;
		
		foreach ($tabs as $k => $arr ) {
			foreach( $arr as $id => $info ) {
				
				$output = $ultimatemember->account->get_tab_output( $id );
				if ( !$output ) {
					unset( $tabs[$k][$id] );
				}
				
				if ( $id == 'delete' ) {
					if ( !um_user('can_delete_profile') && !um_user('can_delete_everyone') ) {
						unset( $tabs[$k][$id] );
					}
				}
				
			}
		}
		
		return $tabs;
	
	}