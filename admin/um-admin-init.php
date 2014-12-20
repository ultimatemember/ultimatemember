<?php

class UM_Admin_API {

	function __construct() {

		$this->slug = 'ultimatemember';

		add_action('admin_init',  array(&$this, 'admin_init'), 0);

		if ( !class_exists( 'ReduxFramework' ) && file_exists( um_path . 'admin/core/lib/ReduxFramework/ReduxCore/framework.php' ) ) {
			require_once( um_path . 'admin/core/lib/ReduxFramework/ReduxCore/framework.php' );
		}
		if ( file_exists ( um_path . 'admin/core/um-admin-redux.php' ) ) {
			require_once( um_path . 'admin/core/um-admin-redux.php' );
		}
		
	
		$this->about_tabs['about'] = 'About';
		$this->about_tabs['start'] = 'Getting Started';
		
	}
	
	/***
	***	@Init
	***/
	function admin_init(){
	
		global $ultimatemember;
		
		require_once um_path . 'admin/core/um-admin-columns.php';
		require_once um_path . 'admin/core/um-admin-notices.php';
		require_once um_path . 'admin/core/um-admin-enqueue.php';
		require_once um_path . 'admin/core/um-admin-metabox.php';
		require_once um_path . 'admin/core/um-admin-access.php';
		require_once um_path . 'admin/core/um-admin-functions.php';
		require_once um_path . 'admin/core/um-admin-users.php';
		require_once um_path . 'admin/core/um-admin-roles.php';
		require_once um_path . 'admin/core/um-admin-builder.php';
		require_once um_path . 'admin/core/um-admin-dragdrop.php';
		
		require_once um_path . 'admin/core/um-admin-actions-user.php';
		require_once um_path . 'admin/core/um-admin-actions-modal.php';
		require_once um_path . 'admin/core/um-admin-actions-fields.php';
		require_once um_path . 'admin/core/um-admin-actions-ajax.php';
		
		require_once um_path . 'admin/core/um-admin-filters-fields.php';
		
		/* initialize UM administration */
		$this->columns = new UM_Admin_Columns();
		$this->styles = new UM_Admin_Enqueue();
		$this->functions = new UM_Admin_Functions();
		$this->metabox = new UM_Admin_Metabox();
		$this->notices = new UM_Admin_Notices();
		$this->users = new UM_Admin_Users();
		$this->roles = new UM_Admin_Roles();
		$this->access = new UM_Admin_Access();
		$this->builder = new UM_Admin_Builder();
		$this->dragdrop = new UM_Admin_DragDrop();
		
		if ( 	is_admin() && 
				current_user_can('manage_options') && 
				isset($_REQUEST['um_action']) && 
				$_REQUEST['um_action'] == 'uninstall_ultimatemember' 
			) 
		{
			$ultimatemember->uninstall->remove_um();
		}
		
	}

}

$um_admin = new UM_Admin_API();