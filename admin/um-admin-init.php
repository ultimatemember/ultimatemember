<?php

class UM_Admin_API {

	function __construct() {

		$this->slug = 'ultimatemember';

		$this->about_tabs['about'] = 'About';
		$this->about_tabs['start'] = 'Getting Started';

		add_action('admin_init', array(&$this, 'admin_init'), 0);
		
		add_action('admin_menu', array(&$this, 'admin_menu'), 0 );
		add_action('admin_menu', array(&$this, 'secondary_menu_items'), 1000 );

		if ( !class_exists( 'ReduxFramework' ) && file_exists( um_path . 'admin/core/lib/ReduxFramework/ReduxCore/framework.php' ) ) {
			require_once( um_path . 'admin/core/lib/ReduxFramework/ReduxCore/framework.php' );
		}
		if ( file_exists ( um_path . 'admin/core/um-admin-redux.php' ) ) {
			require_once( um_path . 'admin/core/um-admin-redux.php' );
		}
		
	}
	
	/***
	***	@Creates menu
	***/
	function admin_menu() {
		
		add_menu_page( __('Ultimate Member', $this->slug), __('Ultimate Member', $this->slug), 'manage_options', $this->slug, array(&$this, 'admin_page'), 'dashicons-admin-users', '66.78578');
		
		add_submenu_page( $this->slug, __('Dashboard', $this->slug), __('Dashboard', $this->slug), 'manage_options', $this->slug, array(&$this, 'admin_page') );

		foreach( $this->about_tabs as $k => $tab ) {
			add_submenu_page( '_'. $k . '_um', sprintf(__('%s | Ultimate Member', $this->slug), $tab), sprintf(__('%s | Ultimate Member', $this->slug), $tab), 'manage_options', $this->slug . '-' . $k, array(&$this, 'admin_page') );
		}
		
	}
	
	/***
	***	@After "settings" menu
	***/
	function secondary_menu_items() {

		add_submenu_page( $this->slug, __('Forms', $this->slug), __('Forms', $this->slug), 'manage_options', 'edit.php?post_type=um_form', '', '' );

		add_submenu_page( $this->slug, __('User Roles', $this->slug), __('User Roles', $this->slug), 'manage_options', 'edit.php?post_type=um_role', '', '' );

		if ( um_get_option('members_page' ) || !get_option('um_options') ){
			add_submenu_page( $this->slug, __('Member Directories', $this->slug), __('Member Directories', $this->slug), 'manage_options', 'edit.php?post_type=um_directory', '', '' );
		}
	
	}
	
	/***
	***	@Admin page function
	***/
	function admin_page() {

		$page = $_REQUEST['page'];

		if ( $page == 'ultimatemember' ) {
			include_once um_path . 'admin/templates/dashboard.php';
		}
		
		if ( strstr( $page, 'ultimatemember-' ) ) {

			$template = str_replace('ultimatemember-','',$page);
			$file = um_path . 'admin/templates/'. $template . '.php';

			if ( file_exists( $file ) ){
				include_once um_path . 'admin/templates/'. $template . '.php';
			} else {
				echo '<h4>' .  __('Please create a team.php template in admin templates.','ultimatemember') . '</h4>';
			}

		}

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
		require_once um_path . 'admin/core/um-admin-tracking.php';
		
		require_once um_path . 'admin/core/um-admin-actions-user.php';
		require_once um_path . 'admin/core/um-admin-actions-modal.php';
		require_once um_path . 'admin/core/um-admin-actions-fields.php';
		require_once um_path . 'admin/core/um-admin-actions-ajax.php';
		require_once um_path . 'admin/core/um-admin-actions.php';
		
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
		$this->tracking = new UM_Admin_Tracking();
		
		if ( 	is_admin() && 
				current_user_can('manage_options') && 
				isset($_REQUEST['um_adm_action']) && 
				$_REQUEST['um_adm_action'] != ''
			)
		{
			do_action("um_admin_do_action__", $_REQUEST['um_adm_action'] );
			do_action("um_admin_do_action__{$_REQUEST['um_adm_action']}", $_REQUEST['um_adm_action'] );
		}
		
	}

}

$um_admin = new UM_Admin_API();