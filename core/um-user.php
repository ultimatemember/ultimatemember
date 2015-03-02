<?php

class UM_User {

	function __construct() {
	
		$this->id = 0;
		$this->usermeta = null;
		$this->data = null;
		
		$this->banned_keys = array(
			'metabox','postbox','meta-box',
			'dismissed_wp_pointers', 'session_tokens',
			'screen_layout', 'wp_user-', 'dismissed',
			'cap_key', 'wp_capabilities',
			'managenav', 'nav_menu','user_activation_key',
			'level_', 'wp_user_level'
		);
		
		add_action('init',  array(&$this, 'set'), 1);
	
		$this->preview = false;
		
		// a list of keys that should never be in wp_usermeta
		$this->update_user_keys = array(
			'user_email',
			'user_pass',
			'user_password',
			'display_name',
		);
		
		$this->target_id = null;

	}
	
	/***
	***	@Converts object to array
	***/
	function toArray($obj)
	{
		if (is_object($obj)) $obj = (array)$obj;
		if (is_array($obj)) {
			$new = array();
			foreach ($obj as $key => $val) {
				$new[$key] = $this->toArray($val);
			}
		} else {
			$new = $obj;
		}

		return $new;
	}
	
	/***
	***	@Set user
	***/
	function set( $user_id = null, $clean=false ) {
		global $ultimatemember;
		
		if ( isset( $this->profile ) ) {
			unset( $this->profile );
		}
		
		if ($user_id) {
		
			$this->id = $user_id;
			$this->usermeta = get_user_meta($user_id);
			$this->data = get_userdata($this->id);
			
		} elseif (is_user_logged_in() && $clean == false ){
		
			$this->id = get_current_user_id();
			$this->usermeta = get_user_meta($this->id);
			$this->data = get_userdata($this->id);
			
		} else {
		
			$this->id = 0;
			$this->usermeta = null;
			$this->data = null;
		
		}
		
		// we have a user, populate a profile
		if ( $this->id && $this->toArray($this->data) ) {

			// add user data
			$this->data = $this->toArray($this->data);
			
			foreach( $this->data as $k=>$v ) {
				if ($k == 'roles') {
					$this->profile['wp_roles'] = implode(',',$v);
				} else if (is_array($v)){
					foreach($v as $k2 => $v2){
						$this->profile[$k2] = $v2;
					}
				} else {
					$this->profile[$k] = $v;
				}
			}
			
			// add account status
			if ( !isset( $this->usermeta['account_status'][0] ) )  {
				$this->usermeta['account_status'][0] = 'approved';
			}

			if ( $this->usermeta['account_status'][0] == 'approved' ) {
				$this->usermeta['account_status_name'][0] = 'Approved';
			}

			if ( $this->usermeta['account_status'][0] == 'awaiting_email_confirmation' ) {
				$this->usermeta['account_status_name'][0] = 'Awaiting E-mail Confirmation';
			}
				
			if ( $this->usermeta['account_status'][0] == 'awaiting_admin_review' ) {
				$this->usermeta['account_status_name'][0] = 'Pending Review';
			}
			
			if ( $this->usermeta['account_status'][0] == 'rejected' ) {
				$this->usermeta['account_status_name'][0] = 'Membership Rejected';
			}
			
			if ( $this->usermeta['account_status'][0] == 'inactive' ) {
				$this->usermeta['account_status_name'][0] = 'Membership Inactive';
			}
			
			// add user meta
			foreach($this->usermeta as $k=>$v){
				$this->profile[$k] = $v[0];
			}

			// add user stuff
			$this->profile['post_count'] = $ultimatemember->query->count_posts($this->id);
			$this->profile['comment_count'] = $ultimatemember->query->count_comments($this->id);
			
			// add permissions
			$user_role = $this->get_role();
			$this->role_meta = $ultimatemember->query->role_data( $user_role );
			$this->role_meta = apply_filters('um_user_permissions_filter', $this->role_meta, $this->id);

			$this->profile = array_merge( $this->profile, (array)$this->role_meta);
			
			$this->profile['super_admin'] = ( is_super_admin( $this->id ) ) ? 1 : 0;
				
			// clean profile
			$this->clean();

		}
		
	}
	
	/***
	***	@reset user data
	***/
	function reset( $clean = false ){
		$this->set(0, $clean);
	}
	
	/***
	***	@Clean user profile
	***/
	function clean(){
		foreach($this->profile as $key => $value){
			foreach($this->banned_keys as $ban){
				if (strstr($key, $ban) || is_numeric($key) )
					unset($this->profile[$key]);
			}
		}
	}
	
	/***
	***	@Automatic login by user id
	***/
	function auto_login( $user_id, $rememberme = 0 ) {
		wp_set_current_user($user_id);
		wp_set_auth_cookie($user_id, $rememberme );
	}
	
	/***
	***	@Set user's registration details
	***/
	function set_registration_details( $submitted ) {
		update_user_meta( $this->id, 'submitted', $submitted );
	}
	
	/***
	***	@A plain version of password
	***/
	function set_plain_password( $plain ) {
		update_user_meta( $this->id, '_um_cool_but_hard_to_guess_plain_pw', $plain );
	}
	
	/***
	***	@Set user's role
	***/
	function set_role( $role ){
	
		do_action('um_before_user_role_is_changed');
		
		$this->profile['role'] = $role;
		$this->update_usermeta_info('role');
		
		do_action('um_after_user_role_is_changed');
		
	}
	
	/***
	***	@Set user's account status
	***/
	function set_status( $status ){
	
		$this->profile['account_status'] = $status;
		
		$this->update_usermeta_info('account_status');
		
		do_action('um_after_user_status_is_changed', $status);
		
	}
	
	/***
	***	@Set user's hash for password reset
	***/
	function password_reset_hash(){
		global $ultimatemember;

		$this->profile['reset_pass_hash'] = $ultimatemember->validation->generate();
		$this->update_usermeta_info('reset_pass_hash');
		
	}
	
	/***
	***	@Set user's hash
	***/
	function assign_secretkey(){
		global $ultimatemember;
		
		do_action('um_before_user_hash_is_changed');

		$this->profile['account_secret_hash'] = $ultimatemember->validation->generate();
		$this->update_usermeta_info('account_secret_hash');

		do_action('um_after_user_hash_is_changed');
		
	}
	
	/***
	***	@password reset email
	***/
	function password_reset(){
		global $ultimatemember;
		$this->password_reset_hash();
		$ultimatemember->mail->send( um_user('user_email'), 'resetpw_email' );
	}
	
	/***
	***	@approves a user
	***/
	function approve(){
		global $ultimatemember;
		
		if ( um_user('account_status') == 'awaiting_admin_review' ) {
			$email_tpl = 'approved_email';
		} else {
			$email_tpl = 'welcome_email';
		}
		
		$this->set_status('approved');
		$ultimatemember->mail->send( um_user('user_email'), $email_tpl );
		
		$this->delete_meta('account_secret_hash');
		$this->delete_meta('_um_cool_but_hard_to_guess_plain_pw');
		
		do_action('um_after_user_is_approved', um_user('ID') );
		
	}
	
	/***
	***	@pending email
	***/
	function email_pending(){
		global $ultimatemember;
		$this->assign_secretkey();
		$this->set_status('awaiting_email_confirmation');
		$ultimatemember->mail->send( um_user('user_email'), 'checkmail_email' );
	}
	
	/***
	***	@pending review
	***/
	function pending(){
		global $ultimatemember;
		$this->set_status('awaiting_admin_review');
		$ultimatemember->mail->send( um_user('user_email'), 'pending_email' );
	}
	
	/***
	***	@reject membership
	***/
	function reject(){
		global $ultimatemember;
		$this->set_status('rejected');
		$ultimatemember->mail->send( um_user('user_email'), 'rejected_email' );
	}
	
	/***
	***	@deactivate membership
	***/
	function deactivate(){
		global $ultimatemember;
		$this->set_status('inactive');
		$ultimatemember->mail->send( um_user('user_email'), 'inactive_email' );
	}
	
	/***
	***	@delete user
	***/
	function delete( $send_mail = true ) {
		global $ultimatemember;
		
		if ( $send_mail ) {
			$ultimatemember->mail->send( um_user('user_email'), 'deletion_email' );
		}
		
		$ultimatemember->files->remove_dir( um_user_uploads_dir() );

		if ( is_multisite() ) {
			
			if ( !function_exists('wpmu_delete_user') ) {
				require_once( ABSPATH . 'wp-admin/includes/ms.php' );
			}
			
			wpmu_delete_user( $this->id );
			
		} else {
			
			if ( !function_exists('wp_delete_user') ) {
				require_once( ABSPATH . 'wp-admin/includes/user.php' );
			}
			
			wp_delete_user( $this->id, 1 );
			
		}

	}
	
	/***
	***	@Get user's role in UM
	***/
	function get_role() {
		if (isset($this->profile['role']) && !empty( $this->profile['role'] ) ) {
			return $this->profile['role'];
		} else {
			if ( $this->profile['wp_roles'] == 'administrator' ) {
				return 'admin';
			} else {
				return 'member';
			}
		}
	}
	
	/***
	***	@Get user's role name in UM
	***/
	function get_role_name() {
		return $this->profile['role_name'];
	}
	
	/***
	***	@Update one key in user meta
	***/
	function update_usermeta_info( $key ){
		update_user_meta( $this->id, $key, $this->profile[$key] );
	}

	/***
	***	@Delete any meta key
	***/
	function delete_meta( $key ){
		delete_user_meta( $this->id, $key );
	}
	
	/***
	***	@Get all bulk actions
	***/
	function get_bulk_admin_actions() {
		$output = '';
		$actions = array();
		$actions = apply_filters('um_admin_bulk_user_actions_hook', $actions );
		foreach($actions as $id => $arr ) {
			if ( isset($arr['disabled'])){
				$arr['disabled'] = 'disabled';
			} else {
				$arr['disabled'] = '';
			}

			$output .= '<option value="' . $id . '" '. $arr['disabled'] . '>' . $arr['label'] . '</option>';
		}
		return $output;
	}
	
	/***
	***	@Get admin actions for individual user
	***/
	function get_admin_actions() {
		$items = '';
		$actions = array();
		$actions = apply_filters('um_admin_user_actions_hook', $actions );
		if ( !isset( $actions ) || empty( $actions ) ) return false;
		foreach($actions as $id => $arr ) {
			$url = add_query_arg('um_action', $id );
			$url = add_query_arg('uid', um_profile_id(), $url );
			$items[] = '<a href="' . $url .'" class="real_url">' . $arr['label'] . '</a>';
		}
		return $items;
	}
	
	/***
	***	@If it is a private profile
	***/
	function is_private_profile( $user_id ) {
		$privacy = get_user_meta( $user_id, 'profile_privacy', true );
		if ( $privacy == __('Only me','ultimatemember') ) {
			return true;
		}
		return false;
	}
	
	/***
	***	@If it is un-approved profile
	***/
	function is_approved( $user_id ) {
		$status = get_user_meta( $user_id, 'account_status', true );
		if ( $status == 'approved' || $status == '' ) {
			return true;
		}
		return false;
	}
	
	/***
	***	@update files
	***/
	function update_files( $changes ) {
		
		global $ultimatemember;
		
		foreach( $changes as $key => $uri ) {
			$src = um_is_temp_upload( $uri );
			$ultimatemember->files->new_user_upload( $this->id, $src, $key );
		}
		
	}
	
	/***
	***	@update profile
	***/
	function update_profile( $changes ) {
	
		global $ultimatemember;
		
		$args['ID'] = $this->id;

		// save or update profile meta
		foreach( $changes as $key => $value ) {
		
			if ( !in_array( $key, $this->update_user_keys ) ) {
				
				update_user_meta( $this->id, $key, $value );
			
			} else {
				
				$args[$key] = esc_attr( $changes[$key] );

			}
			
		}
		
		// hook for name changes
		do_action('um_update_profile_full_name', $changes );
		
		// update user
		if ( count( $args ) > 1 ) {
			wp_update_user( $args );
		}
	
	}
	
	/***
	***	@user exists by meta key and value
	***/
	function user_has_metadata( $key, $value ) {
		
		global $ultimatemember;
		$value = $ultimatemember->validation->safe_name_in_url( $value );
		
		$ids = get_users(array( 'fields' => 'ID', 'meta_key' => $key,'meta_value' => $value,'meta_compare' => '=') );
		if ( !isset( $ids ) || empty( $ids ) ) return false;
		foreach( $ids as $k => $id ) {
			if ( $id == um_user('ID') ){
				unset( $ids[$k] );
			} else {
				$duplicates[] = $id;
			}
		}
		if ( isset( $duplicates ) && !empty( $duplicates ) )
			return count( $duplicates );
		return false;
	}
	
	/***
	***	@user exists by name
	***/
	function user_exists_by_name( $value ) {
	
		global $ultimatemember;
		$value = $ultimatemember->validation->safe_name_in_url( $value );
		
		$ids = get_users(array( 'fields' => 'ID', 'meta_key' => 'full_name','meta_value' => $value ,'meta_compare' => '=') );
		if ( isset( $ids[0] ) ) 
			return $ids[0];
		return false;
	}
	
	/***
	***	@user exists by id
	***/
	function user_exists_by_id( $user_id ) {
		$aux = get_userdata( $user_id );
		if($aux==false){
			return false;
		} else {
			return $user_id;
		}
	}
	
}