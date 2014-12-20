<?php

class UM_Admin_Notices {

	function __construct() {

		add_action('admin_notices', array(&$this, 'admin_notices'));
	}
	
	/***
	***	@For core admin notices
	***/
	function admin_notices(){
	
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
					echo '<div id="message" class="error"><p>' . $message['err_content'] . '</p></div>';
				} else {
					echo '<div id="message" class="updated"><p>' . $message['content'] . '</p></div>';
				}
			}
		}
		
	}

}