<?php

	/***
	***	@profile name update
	***/
	add_action('um_update_profile_full_name', 'um_update_profile_full_name' );
	function um_update_profile_full_name( $changes ) {
		global $ultimatemember;

		if ( isset( $changes['first_name'] ) && isset( $changes['last_name'] ) ) {
			
			if ( $changes['first_name'] && $changes['last_name'] && um_get_option('display_name') != 'public_name' ) {
				
				wp_update_user( array( 'ID' => $ultimatemember->user->id, 'display_name' => $changes['first_name'] . ' ' . $changes['last_name'] ) );

				$full_name = $changes['first_name'] . '.' . $changes['last_name'];
			
			} else {
				
				$full_name = $ultimatemember->user->profile['display_name'];
			
			}
			
			$full_name = $ultimatemember->validation->safe_name_in_url( $full_name );

			/* duplicate or not */
			if ( $ultimatemember->user->user_has_metadata( 'full_name', $full_name ) ) {
				
				$duplicates = $ultimatemember->user->user_has_metadata( 'full_name', $full_name );
				if ( !get_option("um_duplicate_name_{$full_name}") ) {
				
					update_option("um_duplicate_name_{$full_name}", $duplicates );
					$full_name = $full_name . '.' . $duplicates;
				
				} else {
					
					if ( um_user('_duplicate_id') ) {
						$duplicates = um_user('_duplicate_id');
					} else {
						$duplicates = get_option("um_duplicate_name_{$full_name}") + 1;
						update_option("um_duplicate_name_{$full_name}", $duplicates );
						update_user_meta( $ultimatemember->user->id, '_duplicate_id', $duplicates );
					}
					
					$full_name = $full_name . '.' . $duplicates;
				
				}

			} else {
			
				if ( um_user('_duplicate_id') && $full_name != str_replace( '.' . um_user('_duplicate_id'), '' , um_user('full_name') ) ) {
					$duplicates = um_user('_duplicate_id');
					$full_name = str_replace( '.' . um_user('_duplicate_id'), '', $full_name);
				}
			}

				update_user_meta( $ultimatemember->user->id, 'full_name', $full_name );
			
			
		}
		
		if( um_get_option('display_name') === 'public_name' ){
			update_user_meta( $ultimatemember->user->id, 'display_name', $changes['display_name'] );
		}

	}