<?php

class UM_API {

	function __construct() {

		require_once um_path . 'core/um-short-functions.php';
		
		if (is_admin()){
			require_once um_path . 'admin/um-admin-init.php';
		}

		add_action('init',  array(&$this, 'init'), 0);
		
		$this->honeypot = 'request';
		
	}
	
	/***
	***	@Init
	***/
	function init(){

		ob_start();
		
		require_once um_path . 'core/um-rewrite.php';
		require_once um_path . 'core/um-setup.php';
		require_once um_path . 'core/um-uninstall.php';
		require_once um_path . 'core/um-fonticons.php';
		require_once um_path . 'core/um-login.php';
		require_once um_path . 'core/um-register.php';
		require_once um_path . 'core/um-enqueue.php';
		require_once um_path . 'core/um-shortcodes.php';
		require_once um_path . 'core/um-account.php';
		require_once um_path . 'core/um-password.php';
		require_once um_path . 'core/um-fields.php';
		require_once um_path . 'core/um-form.php';
		require_once um_path . 'core/um-user.php';
		require_once um_path . 'core/um-query.php';
		require_once um_path . 'core/um-datetime.php';
		require_once um_path . 'core/um-chart.php';
		require_once um_path . 'core/um-builtin.php';
		require_once um_path . 'core/um-files.php';
		require_once um_path . 'core/um-taxonomies.php';
		require_once um_path . 'core/um-validation.php';
		require_once um_path . 'core/um-navmenu.php';
		require_once um_path . 'core/um-access.php';
		require_once um_path . 'core/um-permalinks.php';
		require_once um_path . 'core/um-mail.php';
		require_once um_path . 'core/um-members.php';
		require_once um_path . 'core/um-logout.php';
		require_once um_path . 'core/um-modal.php';
		
		require_once um_path . 'core/um-actions-form.php';
		require_once um_path . 'core/um-actions-wpadmin.php';
		require_once um_path . 'core/um-actions-core.php';
		require_once um_path . 'core/um-actions-ajax.php';
		require_once um_path . 'core/um-actions-login.php';
		require_once um_path . 'core/um-actions-register.php';
		require_once um_path . 'core/um-actions-profile.php';
		require_once um_path . 'core/um-actions-account.php';
		require_once um_path . 'core/um-actions-password.php';
		require_once um_path . 'core/um-actions-members.php';
		require_once um_path . 'core/um-actions-global.php';
		require_once um_path . 'core/um-actions-tracking.php';
		require_once um_path . 'core/um-actions-user.php';
		require_once um_path . 'core/um-actions-save-profile.php';
		require_once um_path . 'core/um-actions-modal.php';
		require_once um_path . 'core/um-actions-notices.php';
		
		require_once um_path . 'core/um-filters-login.php';
		require_once um_path . 'core/um-filters-register.php';
		require_once um_path . 'core/um-filters-fields.php';
		require_once um_path . 'core/um-filters-files.php';
		require_once um_path . 'core/um-filters-navmenu.php';
		require_once um_path . 'core/um-filters-avatars.php';
		require_once um_path . 'core/um-filters-arguments.php';
		require_once um_path . 'core/um-filters-user.php';
		require_once um_path . 'core/um-filters-members.php';
		require_once um_path . 'core/um-filters-profile.php';
		require_once um_path . 'core/um-filters-account.php';
		
		/* initialize UM */
		$this->rewrite = new UM_Rewrite();
		$this->setup = new UM_Setup();
		$this->uninstall = new UM_Uninstall();
		$this->icons = new UM_FontIcons();
		$this->styles = new UM_Enqueue();
		$this->shortcodes = new UM_Shortcodes();
		$this->account = new UM_Account();
		$this->password = new UM_Password();
		$this->login = new UM_Login();
		$this->register = new UM_Register();
		$this->fields = new UM_Fields();
		$this->user = new UM_User();
		$this->datetime = new UM_DateTime();
		$this->chart = new UM_Chart();
		$this->builtin = new UM_Builtin();
		$this->form = new UM_Form();
		$this->files = new UM_Files();
		$this->taxonomies = new UM_Taxonomies();
		$this->validation = new UM_Validation();
		$this->query = new UM_Query();
		$this->access = new UM_Access();
		$this->permalinks = new UM_Permalinks();
		$this->mail = new UM_Mail();
		$this->members = new UM_Members();
		$this->logout = new UM_Logout();
		$this->modal = new UM_Modal();

		$this->options = get_option('um_options');
		
	}
	
}

$ultimatemember = new UM_API();