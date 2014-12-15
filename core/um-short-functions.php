<?php
	
	/***
	***	@Check value of queried search in text input
	***/
	function um_queried_search_value( $filter ) {
		global $ultimatemember;
		if ( isset($_REQUEST['um_search']) ) {
			$query = $ultimatemember->permalinks->get_query_array();
			if ( $query[$filter] != '' )
				echo $query[$filter];
		}
		echo '';
	}
	
	/***
	***	@Check whether item in dropdown is selected in query-url
	***/
	function um_select_if_in_query_params( $filter, $val ) {
		global $ultimatemember;
		if ( isset($_REQUEST['um_search']) ) {
			$query = $ultimatemember->permalinks->get_query_array();
			if ( in_array( $val, $query ) )
				echo 'selected="selected"';
		}
		echo '';
	}
	
	/***
	***	@get styling defaults
	***/
	function um_styling_defaults( $mode ) {
		global $ultimatemember;
		$arr = $ultimatemember->setup->core_form_meta_all;
		foreach( $arr as $k => $v ) {
			$s = str_replace($mode . '_', '', $k );
			if ( strstr($k, '_um_'.$mode.'_') && !in_array($s, $ultimatemember->setup->core_global_meta_all ) ) {
				$a = str_replace('_um_'.$mode.'_','',$k);
				$b = str_replace('_um_','',$k);
				$new_arr[$a] = um_get_option( $b );
			} else if ( in_array( $k, $ultimatemember->setup->core_global_meta_all ) ) {
				$a = str_replace('_um_','',$k);
				$new_arr[$a] = um_get_option( $a );
			}
		}

		return $new_arr;
	}
	
	/***
	***	@get meta option default
	***/
	function um_get_metadefault( $id ) {
		global $ultimatemember;
		if ( isset( $ultimatemember->setup->core_form_meta_all[ '_um_' . $id ] ) )
			return $ultimatemember->setup->core_form_meta_all[ '_um_' . $id ];
		return '';
	}
	
	/***
	***	@boolean for account page editing
	***/
	function um_submitting_account_page() {
		if ( um_is_account_page() && isset($_POST['_um_account']) == 1 && is_user_logged_in() )
			return true;
		return false;
	}
	
	/***
	***	@if we're on account page
	***/
	function um_is_account_page() {
		global $post, $ultimatemember;
		if ( isset($post->ID) && $post->ID == $ultimatemember->permalinks->core['account'] )
			return true;
		return false;
	}
	
	/***
	***	@account page URI
	***/
	function um_account_page_url(){
		global $ultimatemember;
		return get_permalink( $ultimatemember->permalinks->core['account'] );
	}
	
	/***
	***	@if we're on logout page
	***/
	function um_is_logout_page() {
		global $post, $ultimatemember;
		if ( isset($post->ID) && $post->ID == $ultimatemember->permalinks->core['logout'] )
			return true;
		return false;
	}
	
	/***
	***	@show logout page url
	***/
	function um_logout_page( $redirect_to = false ) {
		global $ultimatemember;
		if ( isset( $ultimatemember->permalinks->core['logout'] ) && is_user_logged_in() )
			$link = get_permalink( $ultimatemember->permalinks->core['logout'] );
			if ( $redirect_to ) {
				$link = add_query_arg( 'redirect_to', $redirect_to, $link );
			}
			return $link;
		return false;
	}
	
	/***
	***	@get a user's display name
	***/
	function um_get_display_name( $user_id ) {
		global $ultimatemember;
		
		$ultimatemember->user->reset( true );
		$ultimatemember->user->set( $user_id );
		$cached = um_user('display_name');
		$ultimatemember->user->reset();
		return $cached;
		
	}
	
	/***
	***	@get members to show in directory
	***/
	function um_members( $argument ) {
		global $ultimatemember;
		return $ultimatemember->members->results[ $argument ];
	}
	
	/***
	***	@reset user clean
	***/
	function um_reset_user_clean() {
		global $ultimatemember;
		$ultimatemember->user->reset( true );
	}
	
	/***
	***	@reset user
	***/
	function um_reset_user() {
		global $ultimatemember;
		$ultimatemember->user->reset();
	}
	
	/***
	***	@boolean if viewing his own profile
	***/
	function um_is_my_profile() {
		if ( !is_user_logged_in() ) return false;
		if ( um_is_user_page_uri() && get_current_user_id() == um_get_requested_user() ) return true;
		return false;
	}
	
	/***
	***	@The UM's profile page URI
	***/
	function um_user_page_uri(){
		global $ultimatemember;
		return get_permalink( $ultimatemember->permalinks->core['user'] );
	}
	
	/***
	***	@checks whether we're on UM profile page
	***/
	function um_is_user_page_uri() {
		global $post, $ultimatemember;
		if ( isset($post->ID) && $post->ID == $ultimatemember->permalinks->core['user'] )
			return true;
		return false;
	}
	
	/***
	***	@user's profile ID
	***/
	function um_user_page_id() {
		global $post, $ultimatemember;
		if ( isset( $ultimatemember->permalinks->core['user'] ) ) {
			return $ultimatemember->permalinks->core['user'];
		}
		return '';
	}
	
	/***
	***	@gets the queried user
	***/
	function um_queried_user() {
		return get_query_var('um_user');
	}
	
	/***
	***	@Sets the requested user
	***/
	function um_set_requested_user( $user_id ) {
		global $ultimatemember;
		$ultimatemember->user->target_id = $user_id;
	}
	
	/***
	***	@Gets the requested user
	***/
	function um_get_requested_user() {
		global $ultimatemember;
		if ( $ultimatemember->user->target_id )
			return $ultimatemember->user->target_id;
		return false;
	}
	
	/***
	***	@Returns profile edit link
	***/
	function um_edit_my_profile_uri() {
		global $ultimatemember;
		$url = $ultimatemember->permalinks->add_query( 'um_action', 'edit' );
		return $url;
	}
	
	/***
	***	@remove edit profile args from url
	***/
	function um_edit_my_profile_cancel_uri() {
		global $ultimatemember;
		$url = $ultimatemember->permalinks->remove_query( 'um_action', 'edit' );
		return $url;
	}
	
	/***
	***	@can view field
	***/
	function um_can_view_field( $data ) {
		global $ultimatemember;

		if ( !is_user_logged_in() && $data['public'] != '1' ) return false;
		
		if ( is_user_logged_in() && isset( $data['public'] ) ) {
		
			if ( !um_is_user_himself() && $data['public'] == '-1' && !um_user_can('can_edit_everyone') )
				return false;
				
			if ( $data['public'] == '-2' && $data['roles'] )
				if ( !in_array( $ultimatemember->query->get_role_by_userid( get_current_user_id() ), $data['roles'] ) )
					return false;
		}
		
		return true;
	}
	
	/***
	***	@checks if user can view profile
	***/
	function um_can_view_profile( $user_id ){
		global $ultimatemember;
		
		if ( !is_user_logged_in() ) {
			if ( $ultimatemember->user->is_private_profile( $user_id ) ) {
				return false;
			} else {
				return true;
			}
		}
		
		if ( !um_user('can_access_private_profile') && $ultimatemember->user->is_private_profile( $user_id ) ) return false;
		
		if ( !um_user('can_view_all') && $user_id != get_current_user_id() ) return false;
		
		if ( um_user('can_view_roles') && $user_id != get_current_user_id() ) {
			if ( !in_array( $ultimatemember->query->get_role_by_userid( $user_id ), um_user('can_view_roles') ) ) {
				return false;
			}
		}
		
		return true;
		
	}
	
	/***
	***	@Tests for current user
	***/
	function um_user_can( $permission ) {
		global $ultimatemember;
		$user_id = get_current_user_id();
		$role = get_user_meta( $user_id, 'role', true );
		$permissions = $ultimatemember->query->role_data( $role );
		if ( $permissions[ $permission ] == 1 )
			return true;
		return false;
	}
	
	/***
	***	@boolean check for not same user
	***/
	function um_is_user_himself() {
		if ( um_get_requested_user() && um_get_requested_user() != get_current_user_id() )
			return false;
		return true;
	}
	
	/***
	***	@can edit field
	***/
	function um_can_edit_field( $data ) {
		global $ultimatemember;
		if ( $ultimatemember->fields->editing == true && $ultimatemember->fields->set_mode == 'profile' ) {
			if ( is_user_logged_in() && $data['editable'] == 0 ) {
				
				if ( um_is_user_himself() && !um_user('can_edit_everyone') )
					return false;
				
				if ( !um_is_user_himself() && !um_user_can('can_edit_everyone') )
					return false;
					
			}
		}
		return true;
	}
	
	/***
	***	@checks if user can edit profile
	***/
	function um_can_edit_profile( $user_id ){
		global $ultimatemember;
		
		if ( !is_user_logged_in() ) return false;
		if ( um_user('can_edit_everyone') ) return true;
		if ( get_current_user_id() == $user_id && um_user('can_edit_profile') ) return true;
		if ( get_current_user_id() == $user_id && !um_user('can_edit_profile') ) return false;
		if ( !um_user('can_edit_everyone') ) return false;
		
		return true;
		
	}
	
	/***
	***	@checks if user can edit his profile
	***/
	function um_can_edit_my_profile(){
		global $ultimatemember;
		
		if ( !is_user_logged_in() ) return false;
		if ( !um_user('can_edit_profile') ) return false;
		
		return true;
		
	}
	
	/***
	***	@short for admin e-mail
	***/
	function um_admin_email(){
		return um_get_option('admin_email');
	}
	
	/***
	***	@Gets an option from DB
	***/
	function um_get_option($option_id) {
		global $um_options;
		if ( isset($um_options[$option_id]) && !empty( $um_options[$option_id] ) )	{
			return $um_options[$option_id];
		}
		
		switch($option_id){
		
			case 'site_name':
				return get_bloginfo('name');
				break;
				
			case 'admin_email':
				return get_bloginfo('admin_email');
				break;
				
		}
		
	}
	
	/***
	***	@Display a link to profile page
	***/
	function um_user_profile_url() {
		global $ultimatemember;
		return $ultimatemember->permalinks->profile_url();
	}
	
	/***
	***	@User has avatar
	***/
	function um_has_gravatar($email) {
		$hash = md5($email);
		$uri = 'http://www.gravatar.com/avatar/' . $hash . '?d=404';
		$headers = @get_headers($uri);
		if (!preg_match("|200|", $headers[0])) {
			$has_valid_avatar = FALSE;
		} else {
			$has_valid_avatar = TRUE;
		}
		return $has_valid_avatar;
	}

	/***
	***	@Get all UM roles in array
	***/
	function um_get_roles() {
		global $ultimatemember;
		return $ultimatemember->query->get_roles();
	}
	
	/***
	***	@Sets up a user profile by ID
	***/
	function um_fetch_user( $user_id ) {
		global $ultimatemember;
		$ultimatemember->user->set( $user_id );
	}
	
	/***
	***	@Load profile key
	***/
	function um_profile( $key ){
		global $ultimatemember;
		if (isset( $ultimatemember->user->profile[$key] ) && !empty( $ultimatemember->user->profile[$key] ) ){
			return $ultimatemember->user->profile[$key];
		} else {
			return false;
		}
	}
	
	/***
	***	@get user data
	***/
	function um_user( $data, $attrs = null ) {
		global $ultimatemember;
		
		switch($data){
		
			default:
			
				$value = um_profile($data);
				
				if ( $ultimatemember->validation->is_serialized( $value ) ) {
					return unserialize( $value );
				} else {
					return $value;
				}
				
				break;
				
			case 'full_name':
				if ( !um_profile( $data ) ) {
				
					if ( um_user('first_name') && um_user('last_name') ) {
						$full_name = um_user('first_name') . '.' . um_user('last_name');
					} else {
						$full_name = um_user('display_name');
					}
					
					$full_name = $ultimatemember->validation->safe_name_in_url( $full_name );
					update_user_meta( um_user('ID'), 'full_name', $full_name );
					
					return $full_name;
					
				} else {
				
					return um_profile( $data );
					
				}
				break;
				
			case 'display_name':
			
				$op = um_get_option('display_name');
				
				if ( $op == 'full_name' ) {
					if ( um_user('first_name') && um_user('last_name') ) {
						return um_user('first_name') . ' ' . um_user('last_name');
					} else {
						return um_profile( $data );
					}
				}
				
				if ( $op == 'sur_name' ) {
					if ( um_user('first_name') && um_user('last_name') ) {
						return um_user('last_name') . ', ' . um_user('first_name');
					} else {
						return um_profile( $data );
					}
				}
				
				if ( $op == 'first_name' ) {
					if ( um_user('first_name') ) {
						return um_user('first_name');
					} else {
						return um_profile( $data );
					}
				}
				
				if ( $op == 'username' ) {
					return um_user('user_login');
				}
				
				if ( $op == 'initial_name' ) {
					if ( um_user('first_name') && um_user('last_name') ) {
						$initial = um_user('last_name');
						return um_user('first_name') . ' ' . $initial[0];
					} else {
						return um_profile( $data );
					}
				}
				
				if ( $op == 'initial_name_f' ) {
					if ( um_user('first_name') && um_user('last_name') ) {
						$initial = um_user('first_name');
						return $initial[0] . ' ' . um_user('last_name');
					} else {
						return um_profile( $data );
					}
				}
				
				if ( $op == 'public_name' ) {
					return um_profile( $data );
				}
				
				if ( $op == 'field' && um_get_option('display_name_field') != '' ) {
					$fields = array_filter(preg_split('/[,\s]+/', um_get_option('display_name_field') )); 
					$output = '';
					foreach( $fields as $field ) {
						$output .= um_profile( $field ) . ' ';
					}
					return $output;
				}
				
				return um_profile( $data );
				
				break;
				
			case 'role_select':
			case 'role_radio':
				return um_user('role_name');
				break;
				
			case 'submitted':
				$array = um_profile($data);
				if ( empty( $array ) ) return '';
				$array = unserialize( $array );
				return $array;	
				break;

			case 'account_activation_link':
				return $ultimatemember->permalinks->activate_url();
				break;

			case 'profile_photo':
				if ( um_has_gravatar( um_profile('user_email') ) ) {
				
					return get_avatar( um_profile('ID'), $attrs);
					
				} else {
					
					$default_avatar_uri = um_get_option('default_avatar');
					$default_avatar_uri = $default_avatar_uri['url'];
					
					if ( !$default_avatar_uri ) {
						$default_avatar_uri = um_url . 'assets/img/default_avatar.png';
					}
					
					return '<img src="' . $default_avatar_uri . '" class="avatar avatar-'.$attrs.' um-avatar" width="'.$attrs.'" height="'.$attrs.'" alt="" />';
					
				}
				break;

			case 'cover_photo':
				if ( um_profile('cover_photo') ) {
					return '<a href="#"><img src="'.um_profile('cover_photo').'" alt="" /></a>';
				}
				break;
				
		}
	}