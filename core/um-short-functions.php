<?php

	/***
	***	@Get core page url
	***/
	function um_get_core_page( $slug, $updated = false) {
		global $ultimatemember;
		if ( $ultimatemember->permalinks->core[ $slug ] )
			$url = get_permalink( $ultimatemember->permalinks->core[ $slug ] );
			if ( $updated ) {
				$url = add_query_arg( 'updated', $updated, $url );
			}
			return $url;
		return '';
	}
	
	/***
	***	@boolean check if we are on a core page or not
	***/
	function um_is_core_page( $page ) {
		global $post, $ultimatemember;
		if ( isset($post->ID) && $post->ID == $ultimatemember->permalinks->core[ $page ] )
			return true;
		return false;
	}
	
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
	***	@check if a legitimate password reset request is in action
	***/
	function um_requesting_password_reset() {
		global $post, $ultimatemember;
		if (  um_is_core_page('password-reset') && isset( $_POST['_um_password_reset'] ) == 1 )
			return true;
		return false;
	}
	
	/***
	***	@check if a legitimate password change request is in action
	***/
	function um_requesting_password_change() {
		global $post, $ultimatemember;
		if (  um_is_core_page('password-reset') && isset( $_POST['_um_password_change'] ) == 1 )
			return true;
		return false;
	}
	
	/***
	***	@boolean for account page editing
	***/
	function um_submitting_account_page() {
		if ( um_is_core_page('account') && isset($_POST['_um_account']) == 1 && is_user_logged_in() )
			return true;
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
		if ( um_is_core_page('user') && get_current_user_id() == um_get_requested_user() ) return true;
		return false;
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
		if ( isset( $ultimatemember->user->target_id ) && !empty( $ultimatemember->user->target_id ) )
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

		if ( isset( $data['public'] ) ) {
		
			if ( !is_user_logged_in() && $data['public'] != '1' ) return false;
			
			if ( is_user_logged_in() ) {
			
				if ( !um_is_user_himself() && $data['public'] == '-1' && !um_user_can('can_edit_everyone') )
					return false;
					
				if ( $data['public'] == '-2' && $data['roles'] )
					if ( !in_array( $ultimatemember->query->get_role_by_userid( get_current_user_id() ), $data['roles'] ) )
						return false;
			}
		
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
			if ( is_user_logged_in() && isset( $data['editable'] ) && $data['editable'] == 0 ) {
				
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
		global $ultimatemember;
		$um_options = $ultimatemember->options;
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

			case 'password_reset_link':
				return $ultimatemember->password->reset_url();
				break;
				
			case 'account_activation_link':
				return $ultimatemember->permalinks->activate_url();
				break;

			case 'profile_photo':
				
				$default_avatar_uri = um_get_option('default_avatar');
				$default_avatar_uri = $default_avatar_uri['url'];
					
				if ( !$default_avatar_uri ) {
					$default_avatar_uri = um_url . 'assets/img/default_avatar.png';
				}

				$default_avatar_uri = um_url . 'assets/img/Dollarphotoclub_57189843.jpg';

				return '<img src="' . $default_avatar_uri . '" class="gravatar avatar avatar-'.$attrs.' um-avatar" width="'.$attrs.'" height="'.$attrs.'" alt="" />';
					
				break;

			case 'cover_photo':
				if ( um_profile('cover_photo') ) {
					return '<a href="#"><img src="'.um_profile('cover_photo').'" alt="" /></a>';
				}
				return '<a href="#"><img src="'.um_url . 'assets/img/best-hd-wallpapers-2.jpg" alt="" /></a>';
				break;
				
		}
	}