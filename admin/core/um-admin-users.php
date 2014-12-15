<?php

class UM_Admin_Users {

	function __construct() {

		$this->custom_role = 'um_role';

		add_filter('manage_users_columns', array(&$this, 'manage_users_columns') );
		
		add_action('manage_users_custom_column', array(&$this, 'manage_users_custom_column'), 10, 3);
		
		add_action('restrict_manage_users', array(&$this, 'restrict_manage_users') );
		
		add_action('admin_init',  array(&$this, 'um_bulk_users_edit'), 9);
		
		add_action('admin_init',  array(&$this, 'um_single_user_edit'), 9);
		
		add_filter('views_users', array(&$this, 'views_users') );
		
		add_filter('pre_user_query', array(&$this, 'um_role_filter') );
		
		add_filter('user_row_actions', array(&$this, 'user_row_actions'), 10, 2);
				
	}
	
	/***
	***	@Process an action to user via users table
	***/
	function um_single_user_edit(){
		global $ultimatemember;
		
		if ( !isset($_REQUEST['um_single_user']) ) return;
		if ( !isset($_REQUEST['um_single_user_action']) ) return;
		if ( !current_user_can('edit_users') ) wp_die( __( 'You do not have enough permissions to do that.','ultimatemember') );
		$user_id = $_REQUEST['um_single_user'];
		$action = $_REQUEST['um_single_user_action'];
		
		$ultimatemember->user->set( $user_id );
		
		do_action("um_admin_user_action_hook", $action);
		
		do_action("um_admin_user_action_{$action}_hook");

		wp_redirect( add_query_arg( 'update', 'user_updated', admin_url('users.php') ) );
		exit;

	}
	
	/***
	***	@Custom row actions for users page
	***/
	function user_row_actions($actions, $user_object) {

		um_fetch_user( $user_object->ID );

		$actions = array();

		$actions['backend_profile'] = "<a class='' href='" . admin_url('user-edit.php?user_id='. $user_object->ID ) . "'>" . __( 'Edit','ultimatemember' ) . "</a>";
		$actions['frontend_profile'] = "<a class='' href='" . um_user_profile_url() . "'>" . __( 'Edit in frontend','ultimatemember') . "</a>";

		return $actions;

	}

	/***
	***	@Custom role filter
	***/
	function um_role_filter( $query ){
		global $pagenow;

		if ( is_admin() && $pagenow=='users.php' && isset($_GET[ $this->custom_role ]) && $_GET[ $this->custom_role ] != '') {
			
			$query->role = urldecode($_GET[ $this->custom_role ]);

			global $wpdb;

			$query->query_where = 
			str_replace('WHERE 1=1', 
					"WHERE 1=1 AND {$wpdb->users}.ID IN (
						 SELECT {$wpdb->usermeta}.user_id FROM $wpdb->usermeta 
							WHERE {$wpdb->usermeta}.meta_key = 'role' 
							AND {$wpdb->usermeta}.meta_value = '{$query->role}')", 
					$query->query_where
			);

		}

		return $query;
		
	}
	
	/***
	***	@Change the roles with UM roles
	***/
	function views_users( $views ) {
		global $ultimatemember, $query;

		remove_filter('pre_user_query', array(&$this, 'um_role_filter') );
		
		$views = array();

		if ( !isset($_REQUEST[ $this->custom_role ]) ) {
		$views['all'] = '<a href="'.admin_url('users.php').'" class="current">All <span class="count">('.$ultimatemember->query->count_users().')</span></a>';
		} else {
		$views['all'] = '<a href="'.admin_url('users.php').'">All <span class="count">('.$ultimatemember->query->count_users().')</span></a>';
		}
		
		$roles = $ultimatemember->query->get_roles();
		
		foreach($roles as $role => $role_name){
			if ( isset($_REQUEST[ $this->custom_role ]) && $_REQUEST[ $this->custom_role ] == $role) {
			$views[ $role ] = '<a href="'.admin_url('users.php').'?'.$this->custom_role.'='.$role.'" class="current">'.$role_name.' <span class="count">('. $ultimatemember->query->count_users_by_role($role) .')</span></a>';
			} else {
			$views[ $role ] = '<a href="'.admin_url('users.php').'?'.$this->custom_role.'='.$role.'">'.$role_name.' <span class="count">('. $ultimatemember->query->count_users_by_role($role) . ')</span></a>';
			}
		}
		
		return $views;
	}
	
	/***
	***	@Bulk user editing actions
	***/
	function um_bulk_users_edit(){
		global $ultimatemember;
		
		$admin_err = 0;
		
		if (isset($_REQUEST) && !empty ($_REQUEST) ){
		
			// bulk change role
			if (isset($_REQUEST['users']) && is_array($_REQUEST['users']) && isset($_REQUEST['um_changeit']) && $_REQUEST['um_changeit'] != '' && isset($_REQUEST['um_change_role']) && !empty($_REQUEST['um_change_role']) ){
			
					if ( ! current_user_can( 'edit_users' ) )
						wp_die( __( 'You do not have enough permissions to do that.','ultimatemember') );
					
					check_admin_referer('bulk-users');
					
					$users = $_REQUEST['users'];
					$new_role = $_REQUEST['um_change_role'];
					
					foreach($users as $user_id){
						$ultimatemember->user->set( $user_id );
						if ( !um_user('super_admin') ) {
							$ultimatemember->user->set_role( $new_role );
						} else {
							$admin_err = 1;
						}
					}
					
					if ( $admin_err == 0 ){
						wp_redirect( admin_url('users.php?update=promote') );
						exit;
					} else {
						wp_redirect( admin_url('users.php?update=err_admin_role') );
						exit;
					}
					
			} else if ( isset($_REQUEST['um_changeit']) && $_REQUEST['um_changeit'] != '' ) {
			
				wp_redirect( admin_url('users.php') );
				exit;
				
			}
			
			// bulk edit users
			if (isset($_REQUEST['users']) && is_array($_REQUEST['users']) && isset($_REQUEST['um_bulkedit']) && $_REQUEST['um_bulkedit'] != '' && isset($_REQUEST['um_bulk_action']) && !empty($_REQUEST['um_bulk_action']) ){
			
					if ( ! current_user_can( 'edit_users' ) )
						wp_die( __( 'You do not have enough permissions to do that.','ultimatemember') );
					
					check_admin_referer('bulk-users');
					
					$users = $_REQUEST['users'];
					$bulk_action = $_REQUEST['um_bulk_action'];
					
					foreach($users as $user_id){
						$ultimatemember->user->set( $user_id );
						if ( !um_user('super_admin') ) {
						
							do_action("um_admin_user_action_hook", $bulk_action);
							
							do_action("um_admin_user_action_{$bulk_action}_hook");
							
						} else {
							$admin_err = 1;
						}
					}
					
					if ( $admin_err == 0 ){
						wp_redirect( admin_url('users.php?update=users_updated') );
						exit;
					} else {
						wp_redirect( admin_url('users.php?update=err_users_updated') );
						exit;
					}
			
			} else if ( isset($_REQUEST['um_bulkedit']) && $_REQUEST['um_bulkedit'] != '' ) {
			
				wp_redirect( admin_url('users.php') );
				exit;
				
			}
			
		}
	}
	
	/***
	***	@Add UM roles to users admin
	***/
	function restrict_manage_users() {
		global $ultimatemember;
		?>
			
			<div class="actions">
			
				<label class="screen-reader-text" for="um_bulk_action"><?php _e('Bulk Actions','ultimatemember'); ?></label>
				<select name="um_bulk_action" id="um_bulk_action" class="umaf-selectjs" style="width: 200px">
					<option value="0"><?php _e('Bulk Actions','ultimatemember'); ?></option>
					<?php echo $ultimatemember->user->get_bulk_admin_actions(); ?>
				</select>
				
				<input name="um_bulkedit" id="um_bulkedit" class="button" value="<?php _e('Apply'); ?>" type="submit" />
		
			</div>
			
			<div class="actions">
			
				<label class="screen-reader-text" for="um_change_role"><?php _e('Community role&hellip;','ultimatemember'); ?></label>
				<select name="um_change_role" id="um_change_role" class="umaf-selectjs" style="width: 160px">
					<?php foreach($ultimatemember->query->get_roles( $add_default = 'Community role&hellip;' ) as $key => $value) { ?>
					<option value="<?php echo $key; ?>"><?php echo $value; ?></option>
					<?php } ?>
				</select>
				
				<input name="um_changeit" id="um_changeit" class="button" value="<?php _e('Change','ultimatemember'); ?>" type="submit" />
		
			</div>
			
		<?php
		
	}
	
	/***
	***	@add user columns
	***/
	function manage_users_columns($columns) {
	
		$admin = new UM_Admin_Metabox();
		
		unset($columns['posts']);
		unset($columns['email']);
		
		$columns['um_role'] = __('Community Role','ultimatemember') . $admin->_tooltip( __('This is the membership role set by Ultimate Member plugin','ultimatemember') );
		
		$columns['role'] = __('WordPress Role','ultimatemember') . $admin->_tooltip( __('This is the membership role set by WordPress','ultimatemember') );
		
		$columns['um_status'] = __('Status','ultimatemember') . $admin->_tooltip( __('This is current user status in your membership site','ultimatemember') );
		
		$columns['um_info'] = __('Details','ultimatemember') . $admin->_tooltip( __('Review the submitted registration details of this member','ultimatemember') );
		
		$columns['um_actions'] = __('Actions','ultimatemember');
		
		return $columns;
	}
	
	/***
	***	@show user columns
	***/
	function manage_users_custom_column($value, $column_name, $user_id) {
	
		global $ultimatemember;
		
		if ( 'um_info' == $column_name ) {
			
			um_fetch_user( $user_id );
			
			if ( um_user('submitted') ) {
			
				return '<span class="um-adm-ico pointer um-admin-tipsy-n" data-modal="UM_preview_registration" data-modal-size="smaller" data-dynamic-content="um_admin_review_registration" data-arg1="'.$user_id.'" data-arg2="edit_registration" title="Review/update registration info"><i class="um-icon-info"></i></span>';
				
			}
			
		}
		
		if ( 'um_actions' == $column_name ) {
			
			um_fetch_user( $user_id );
			
			$actions = $ultimatemember->user->get_admin_actions( $user_id );
			
			if ( !empty( $actions ) ) {
			
				$edit_url = admin_url('users.php');
				$edit_url = add_query_arg('um_single_user', $user_id, $edit_url);
				
				$output = '<select class="umaf-selectjs um_single_user_action" style="width: 200px">
							<option value="">'.__('Take action...','ultimatemember').'</option>'.$actions.'</select>';
				$output .= '<a href="'.$edit_url.'" class="button">'. __('Apply','ultimatemember') .'</a>';
				return $output;
				
			} else {
			
				return '<span class="um-adm-ico um-admin-tipsy-n" title="'.__('This user is an administrator. To modify this user, change their role first.','ultimatemember').'"><i class="um-icon-lock-3"></i></span>';
			
			}
			
		}
		
		if ( 'um_status' == $column_name ) {
		
			um_fetch_user( $user_id );
			if ( um_user('account_status') == 'approved' ) {
				$output = '<span class="um-admin-tag small ok">'.um_user('account_status_name').'</span>';
			} else {
				$output = '<span class="um-admin-tag small pending">'.um_user('account_status_name').'</span>';
			}
			return $output;
			
		}

		if ( $this->custom_role == $column_name ) {
		
			um_fetch_user( $user_id );
			return um_user('role_name');
			
		}
			
		return $value;
	}

}