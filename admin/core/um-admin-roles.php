<?php

class UM_Admin_Roles {

	function __construct() {
		
		add_filter('manage_edit-um_role_columns', array(&$this, 'manage_edit_um_role_columns') );
		add_action('manage_um_role_posts_custom_column', array(&$this, 'manage_um_role_posts_custom_column'), 10, 3);

	}

	/***
	***	@Custom columns for Role
	***/
	function manage_edit_um_role_columns($columns) {
	
		$admin = new UM_Admin_Metabox();
		
		$new_columns['cb'] = '<input type="checkbox" />';
		$new_columns['title'] = __('Role Title','ultimatemember');
		$new_columns['count'] = __('No. of Members','ultimatemember') . $admin->_tooltip( __('The total number of members who have this role on your site','ultimatemember') );
		$new_columns['core'] = __('Core / Built-in','ultimatemember') . $admin->_tooltip( __('A core role is installed by default and may not be removed','ultimatemember') );
		$new_columns['has_wpadmin_perm'] = __('WP-Admin Access','ultimatemember') . $admin->_tooltip( __('Let you know If users of this role can view the WordPress backend or not','ultimatemember') );
		
		return $new_columns;
		
	}

	/***
	***	@Display cusom columns for Role
	***/
	function manage_um_role_posts_custom_column($column_name, $id) {
		global $wpdb, $ultimatemember;
		
		switch ($column_name) {
			
			case 'has_wpadmin_perm':
				if ( $ultimatemember->query->is_core( $id ) ) {
					$role = $ultimatemember->query->is_core( $id );
				} else {
					$post = get_post($id);
					$role = $post->post_name;
				}
				$data = $ultimatemember->query->role_data($role);
				if ( $data['can_access_wpadmin'] ){
					echo '<span class="um-adm-ico um-admin-tipsy-n" title="'.__('This role can access the WordPress backend','ultimatemember').'"><i class="um-faicon-check"></i></span>';
				} else {
					echo __('No','ultimatemember');
				}
				break;
				
			case 'count':
				if ( $ultimatemember->query->is_core( $id ) ) {
					$role = $ultimatemember->query->is_core( $id );
				} else {
					$post = get_post($id);
					$role = $post->post_name;
				}
				echo $ultimatemember->query->count_users_by_role( $role );
				break;
			
			case 'core':
				if ( $ultimatemember->query->is_core( $id ) ) {
					echo '<span class="um-adm-ico um-admin-tipsy-n" title="'.__('Core','ultimatemember').'"><i class="um-faicon-check"></i></span>';
				} else {
					echo '&mdash;';
				}
				break;
				
		}
		
	}
	
}