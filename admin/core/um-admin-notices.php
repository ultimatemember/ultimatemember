<?php

class UM_Admin_Notices {

	function __construct() {
		
		add_action('admin_init', array(&$this, 'create_languages_folder') );

		add_action('admin_notices', array(&$this, 'main_notices'), 1);
		
		add_action('admin_notices', array(&$this, 'localize_note'), 2);
		
		add_action('admin_notices', array(&$this, 'show_update_messages'), 10);
		
	}
	
	/***
	***	@to store plugin languages
	***/
	function create_languages_folder() {
		
		global $ultimatemember;
		
		$path = $ultimatemember->files->upload_basedir;
		$path = str_replace('/uploads/ultimatemember','',$path);
		$path = $path . '/languages/plugins/';
		$path = str_replace('//','/',$path);
		
		if ( !file_exists( $path ) ) {
			$old = umask(0);
			@mkdir( $path, 0777, true);
			umask($old);
		}
		
	}
	
	/***
	***	@show main notices
	***/
	function main_notices(){
	
		$hide_register_notice = get_option('um_can_register_notice');
		
		if ( !get_option('users_can_register') && !$hide_register_notice ) {
			
			echo '<div class="updated" style="border-color: #3ba1da;"><p>';
		
			echo sprintf(__( 'Registration is disabled. Please go to the <a href="%s">general settings</a> page in the WordPress admin and select anyone can register. <a href="%s">Hide this notice</a>', 'ultimatemember' ), admin_url('options-general.php'), add_query_arg('um_adm_action', 'um_can_register_notice') );
		
			echo '</p></div>';
		
		}
		
		$hide_exif_notice = get_option('um_hide_exif_notice');
		
		if ( !extension_loaded('exif') && !$hide_exif_notice ) {
			
			echo '<div class="updated" style="border-color: #3ba1da;"><p>';
		
			echo sprintf(__( 'Exif is not enabled on your server. Mobile photo uploads will not be rotated correctly until you enable the exif extension. <a href="%s">Hide this notice</a>', 'ultimatemember' ), add_query_arg('um_adm_action', 'um_hide_exif_notice') );
		
			echo '</p></div>';
		
		}
		
	}
	
	
	/***
	***	@localization notice
	***/
	function localize_note() {
		global $ultimatemember;
		
		$locale = get_option('WPLANG');
		if ( !$locale ) return;
		if ( strstr( $locale, 'en_' ) ) return; // really, english!
		if ( file_exists( WP_LANG_DIR . '/plugins/ultimatemember-' . $locale . '.mo' ) ) return;
		
		if ( isset( $ultimatemember->available_languages[$locale] ) ) {
		
			$download_uri = add_query_arg('um_adm_action', 'um_language_downloader');
				
			echo '<div class="updated" style="border-color: #3ba1da;"><p>';
			
			echo sprintf(__('Your site language is <strong>%1$s</strong>. Good news! Ultimate Member is already available in <strong>%2$s language</strong>. <a href="%3$s">Download the translation</a> files and start using the plugin in your language now.','ultimatemember'), $locale, $ultimatemember->available_languages[$locale], $download_uri );
			
			echo '</p></div>';
		
		} else {
			
			$hide_locale_notice = get_option('um_hide_locale_notice');
			if ( !$hide_locale_notice ) {
				
			echo '<div class="updated" style="border-color: #3ba1da;"><p>';
				
			echo sprintf(__('Ultimate Member has not yet been translated to your langeuage: <strong>%1$s</strong>. If you have translated the plugin you need put these files <code>ultimatemember-%1$s.po and ultimatemember-%1$s.mo</code> in <strong>/wp-content/languages/plugins/</strong> for the plugin to be translated in your language. <a href="%2$s">Hide this notice</a>','ultimatemember'), $locale, add_query_arg('um_adm_action', 'um_hide_locale_notice') );
				
			echo '</p></div>';
			
			}
			
		}
	
	}
	
	/***
	***	@updating users
	***/
	function show_update_messages(){

		if ( !isset($_REQUEST['update']) ) return;

		$update = $_REQUEST['update'];
		switch($update) {
			
			case 'confirm_delete':
			
				$confirm_uri = urldecode($_REQUEST['_refer']);
				$users = implode(', ', $_REQUEST['user']);
				
				$ignore = admin_url('users.php');
				
				$messages[0]['err_content'] = sprintf(__('Are you sure you want to delete the selected user(s)? The following users will be deleted: (%s) <strong>This cannot be undone!</strong>','ultimatemember'), $users);
				$messages[0]['err_content'] .= '&nbsp;&nbsp;<a href="'.$confirm_uri.'" class="button-primary">' . __('Yes! Delete','ultimatemember') . '</a>&nbsp;&nbsp;<a href="'.$ignore.'" class="button">' . __('Cancel','ultimatemember') . '</a>';
				
				break;
				
			case 'language_updated':
				$messages[0]['content'] = __('Your translation files have been updated successfully.','ultimatemember');
				break;
				
			case 'purged_temp':
				$messages[0]['content'] = __('Your temp uploads directory is now clean.','ultimatemember');
				break;
				
			case 'form_duplicated':
				$messages[0]['content'] = __('The form has been duplicated successfully.','ultimatemember');
				break;
		
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