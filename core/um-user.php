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
	
	/**
	 * @function set()
	 *
	 * @description This method lets you set a user. For example, to retrieve a profile or anything related to that user.
	 *
	 * @usage <?php $ultimatemember->user->set( $user_id, $clean = false ); ?>
	 *
	 * @param $user_id (numeric) (optional) Which user to retrieve. A numeric user ID
	 * @param $clean (boolean) (optional) Should be true or false. Basically, if you did not provide a user ID It will set the current logged in user as a profile
	 *
	 * @returns This API method does not return anything. It sets user profile and permissions and allow you to retrieve any details for that user.
	 *
	 * @example The following example makes you set a user and retrieve their display name after that using the user API.

		<?php
		
			$ultimatemember->user->set( 12 );
			$display_name = $ultimatemember->user->profile['display_name']; // Should print user display name
			
		?>

	 *
	 *
	 */
	function set( $user_id = null, $clean = false ) {
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
	
	/**
	 * @function auto_login()
	 *
	 * @description This method lets you auto sign-in a user to your site.
	 *
	 * @usage <?php $ultimatemember->user->auto_login( $user_id, $rememberme = false ); ?>
	 *
	 * @param $user_id (numeric) (required) Which user ID to sign in automatically
	 * @param $rememberme (boolean) (optional) Should be true or false. If you want the user sign in session to use cookies, use true
	 *
	 * @returns Sign in the specified user automatically.
	 *
	 * @example The following example lets you sign in a user automatically by their ID.

		<?php $ultimatemember->user->auto_login( 2 ); ?>

	 *
	 *
	 * @example The following example lets you sign in a user automatically by their ID and makes the plugin remember their session.

		<?php $ultimatemember->user->auto_login( 10, true ); ?>

	 *
	 *
	 */
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
	
	/**
	 * @function set_role()
	 *
	 * @description This method assign a role to a user. The user must be already set before processing this API method.
	 *
	 * @usage <?php $ultimatemember->user->set_role( $role ); ?>
	 *
	 * @param $role (string) (required) The user role slug you want to assign to user.
	 *
	 * @returns Changes user role if the given user role was a valid plugin role.
	 *
	 * @example Set a user and give them the role community-member

		<?php
		
		// Sets a user. Can accept numeric user ID
		um_fetch_user( 14 );
		
		// Change user role
		$ultimatemember->user->set_role('community-member');
		
		?>

	 *
	 *
	 */
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
	
	/**
	 * @function approve()
	 *
	 * @description This method approves a user membership and sends them an optional welcome/approval e-mail.
	 *
	 * @usage <?php $ultimatemember->user->approve(); ?>
	 *
	 * @returns Approves a user membership.
	 *
	 * @example Approve a pending user and allow him to sign-in to your site.

		<?php
		
			um_fetch_user( 352 );
			$ultimatemember->user->approve();
			
		?>

	 *
	 *
	 */
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
	
	/**
	 * @function pending()
	 *
	 * @description This method puts a user under manual review by administrator and sends them an optional e-mail.
	 *
	 * @usage <?php $ultimatemember->user->pending(); ?>
	 *
	 * @returns Puts a user under review and sends them an email optionally.
	 *
	 * @example An example of putting a user pending manual review

		<?php
		
			um_fetch_user( 54 );
			$ultimatemember->user->pending();
			
		?>

	 *
	 *
	 */
	function pending(){
		global $ultimatemember;
		$this->set_status('awaiting_admin_review');
		$ultimatemember->mail->send( um_user('user_email'), 'pending_email' );
	}
	
	/**
	 * @function reject()
	 *
	 * @description This method rejects a user membership and sends them an optional e-mail.
	 *
	 * @usage <?php $ultimatemember->user->reject(); ?>
	 *
	 * @returns Rejects a user membership.
	 *
	 * @example Reject a user membership example

		<?php
		
			um_fetch_user( 114 );
			$ultimatemember->user->reject();
			
		?>

	 *
	 *
	 */
	function reject(){
		global $ultimatemember;
		$this->set_status('rejected');
		$ultimatemember->mail->send( um_user('user_email'), 'rejected_email' );
	}
	
	/**
	 * @function deactivate()
	 *
	 * @description This method deactivates a user membership and sends them an optional e-mail.
	 *
	 * @usage <?php $ultimatemember->user->deactivate(); ?>
	 *
	 * @returns Deactivates a user membership.
	 *
	 * @example Deactivate a user membership with the following example

		<?php
		
			um_fetch_user( 32 );
			$ultimatemember->user->deactivate();
			
		?>

	 *
	 *
	 */
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
	
	/**
	 * @function get_role()
	 *
	 * @description This method gets a user role in slug format. e.g. member
	 *
	 * @usage <?php $ultimatemember->user->get_role(); ?>
	 *
	 * @returns The user role's slug.
	 *
	 * @example Do something if the user's role is paid-member

		<?php
		
			um_fetch_user( 12 );
			
			if ( $ultimatemember->user->get_role() == 'paid-member' ) {
				// Show this to paid customers
			} else {
				// You are a free member
			}
			
		?>

	 *
	 *
	 */
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
	
	/**
	 * @function get_role_name()
	 *
	 * @description This method is similar to $ultimatemember->user->get_role() but returns the role name instead of slug.
	 *
	 * @usage <?php $ultimatemember->user->get_role_name(); ?>
	 *
	 * @returns The user role's name.
	 *
	 * @example Do something if the user's role is Paid Customer

		<?php
		
			um_fetch_user( 12 );
			
			if ( $ultimatemember->user->get_role_name() == 'Paid Customer' ) {
				// Show this to paid customers
			} else {
				// You are a free member
			}
			
		?>

	 *
	 *
	 */
	function get_role_name() {
		return $this->profile['role_name'];
	}
	
	/***
	***	@Update one key in user meta
	***/
	function update_usermeta_info( $key ){
		update_user_meta( $this->id, $key, $this->profile[$key] );
	}

	/**
	 * @function delete_meta()
	 *
	 * @description This method can be used to delete user's meta key.
	 *
	 * @usage <?php $ultimatemember->user->delete_meta( $key ); ?>
	 *
	 * @param $key (string) (required) The meta field key to remove from user
	 *
	 * @returns This method will not return anything. The specified meta key will be deleted from database for the specified user.
	 *
	 * @example Delete user's age field

		<?php
		
			um_fetch_user( 15 );
			$ultimatemember->user->delete_meta( 'age' );
			
		?>

	 *
	 *
	 */
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
	
	/**
	 * @function is_private_profile()
	 *
	 * @description This method checks if give user profile is private.
	 *
	 * @usage <?php $ultimatemember->user->is_private_profile( $user_id ); ?>
	 *
	 * @param $user_id (numeric) (required) A user ID must be passed to check if the user profile is private
	 *
	 * @returns Returns true if user profile is private and false if user profile is public.
	 *
	 * @example This example display a specific user's name If his profile is public

		<?php
		
			um_fetch_user( 60 );
			$is_private = $ultimatemember->user->is_private_profile( 60 );
			if ( !$is_private ) {
				echo 'User is public and his name is ' . um_user('display_name');
			}
			
		?>

	 *
	 *
	 */
	function is_private_profile( $user_id ) {
		$privacy = get_user_meta( $user_id, 'profile_privacy', true );
		if ( $privacy == __('Only me','ultimatemember') ) {
			return true;
		}
		return false;
	}
	
	/**
	 * @function is_approved()
	 *
	 * @description This method can be used to determine If a certain user is approved or not.
	 *
	 * @usage <?php $ultimatemember->user->is_approved( $user_id ); ?>
	 *
	 * @param $user_id (numeric) (required) The user ID to check approval status for
	 *
	 * @returns True if user is approved and false if user is not approved.
	 *
	 * @example Do something If a user's membership is approved

		<?php
		
			if ( $ultimatemember->user->is_approved( 55 ) {
				// User account is approved
			} else {
				// User account is not approved
			}
			
		?>

	 *
	 *
	 */
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
	
	/**
	 * @function user_exists_by_id()
	 *
	 * @description This method checks if a user exists or not in your site based on the user ID.
	 *
	 * @usage <?php $ultimatemember->user->user_exists_by_id( $user_id ); ?>
	 *
	 * @param $user_id (numeric) (required) A user ID must be passed to check if the user exists
	 *
	 * @returns Returns true if user exists and false if user does not exist.
	 *
	 * @example Basic Usage

		<?php
		
			$boolean = $ultimatemember->user->user_exists_by_id( 15 );
			if ( $boolean ) {
				// That user exists
			}
			
		?>

	 *
	 *
	 */
	function user_exists_by_id( $user_id ) {
		$aux = get_userdata( $user_id );
		if($aux==false){
			return false;
		} else {
			return $user_id;
		}
	}
	
}