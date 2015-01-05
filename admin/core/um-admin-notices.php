<?php

class UM_Admin_Notices {

	function __construct() {

		add_action('admin_notices', array(&$this, 'main_notices'), 1);
		
		add_action('admin_notices', array(&$this, 'show_update_messages'), 10);
		
	}
	
	/***
	***	@show main notices
	***/
	function main_notices(){
	
		$hide_notice = get_option('um_can_register_notice');
		
		if ( !get_option('users_can_register') && !$hide_notice ) {
			
			echo '<div class="updated" style="border-color: #3ba1da;"><p>';
		
			echo sprintf(__( 'Registration is disabled. Please go to the <a href="%s">general settings</a> page in the WordPress admin and select anyone can register. <a href="%s">Hide this notice</a>', 'ultimatemember' ), admin_url('options-general.php'), add_query_arg('um_adm_action', 'um_can_register_notice') );
		
			echo '</p></div>';
		
		}
	}
	
	/***
	***	@updating users
	***/
	function show_update_messages(){

		if ( !isset($_REQUEST['update']) ) return;

		$update = $_REQUEST['update'];
		switch($update) {
		
			case 'user_updated':
				$messages[0]['content'] = __('User has been updated.','ultimatemember');
				break;
				
			case 'users_updated':
				$messages[0]['content'] = __('Users have been updated.','ultimatemember');
				break;
				
			case 'err_users_updated':
				$messages[0]['err_content'] = __('Super administrators cannot be modified.','ultimatemember');
				$messages[1]['content'] = __('Other users have been updated.','ultimatemember');
				
		}
		
		if ( !empty( $messages ) ) {
			foreach( $messages as $message ) {
				if ( isset($message['err_content'])) {
					echo '<div class="error"><p>' . $message['err_content'] . '</p></div>';
				} else {
					echo '<div class="updated" style="border-color: #3ba1da;"><p>' . $message['content'] . '</p></div>';
				}
			}
		}
		
	}

}