<?php

	/***
	***	@profile name update
	***/
	add_action('um_update_profile_full_name', 'um_update_profile_full_name' );
	function um_update_profile_full_name( $changes ) {
		global $ultimatemember;

		if ( isset( $changes['first_name'] ) && isset( $changes['last_name'] ) ) {
			
			$full_name = $ultimatemember->user->profile['display_name'];
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
		
		// Sync display name changes
		$option = um_get_option('display_name');
		$user_id = $ultimatemember->user->id;
		switch ( $option ) {
			default:
				break;
			case 'full_name':
				$update_name = get_user_meta( $user_id, 'first_name', true ) . ' ' . get_user_meta( $user_id, 'last_name', true );
				break;
			case 'sur_name':
				$fname = get_user_meta( $user_id, 'first_name', true );
				$lname = get_user_meta( $user_id, 'last_name', true );
				$update_name = $lname . ' ' . $fname;
				break;
			case 'initial_name':
				$fname = get_user_meta( $user_id, 'first_name', true );
				$lname = get_user_meta( $user_id, 'last_name', true );
				$update_name = $fname . ' ' . $lname[0];
				break;
			case 'initial_name_f':
				$fname = get_user_meta( $user_id, 'first_name', true );
				$lname = get_user_meta( $user_id, 'last_name', true );
				$update_name = $fname[0] . ' ' . $lname;
				break;
			case 'nickname':
				$update_name = get_user_meta( $user_id, 'nickname', true );
				break;
		}

		if ( isset( $update_name ) ) {
			wp_update_user( array( 'ID' => $user_id, 'display_name' => $update_name ) );
		}
	}