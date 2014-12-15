<?php

	/***
	***	@submit account page changes
	***/
	add_action('um_submit_account_details','um_submit_account_details');
	function um_submit_account_details( $args ) {
		global $ultimatemember;

		if ( $_POST['user_password'] && $_POST['confirm_user_password'] ) {
			$changes['user_pass'] = $_POST['user_password'];
		}
		
		foreach( $_POST as $k => $v ) {
			if ( !strstr( $k, 'password' ) && !strstr( $k, 'um_account' ) ) {
				$changes[ $k ] = $v;
			}
		}

		$ultimatemember->user->update_profile( $changes );

		// delete account
		if ( $_POST['single_user_password'] && isset($_POST['um_account_submit']) && $_POST['um_account_submit'] == __('Delete Account') ) {
			if ( current_user_can('delete_users') || um_user('can_delete_profile') ) {
				if ( !um_user('super_admin') ) {
					$ultimatemember->user->delete();
					if ( um_user('after_delete') == 'redirect_home' ) {
						exit( wp_redirect( home_url() ) );
					} else {
						exit( wp_redirect( um_user('delete_redirect_url') ) );
					}
				}
			}
		}
		
		exit( wp_redirect( um_account_page_url() ) );
		
	}
	
	/***
	***	@validate for errors in account page
	***/
	add_action('um_submit_account_errors_hook','um_submit_account_errors_hook');
	function um_submit_account_errors_hook( $args ) {
		global $ultimatemember;
		
		if ( strlen(trim( $_POST['first_name'] ) ) == 0 ) {
			$ultimatemember->form->add_error('first_name', 'You must provide your first name');
		}
		
		if ( strlen(trim( $_POST['last_name'] ) ) == 0 ) {
			$ultimatemember->form->add_error('last_name', 'You must provide your last name');
		}
		
		if ( strlen(trim( $_POST['user_email'] ) ) == 0 ) {
			$ultimatemember->form->add_error('user_email', 'You must provide your e-mail');
		}
		
		if ( !is_email( $_POST['user_email'] ) ) {
			$ultimatemember->form->add_error('user_email', 'Please provide a valid e-mail');
		}
		
		$ultimatemember->account->current_tab = 'general';
		
		if ( $_POST['current_user_password'] != '' ) {
			if ( !wp_check_password( $_POST['current_user_password'], um_user('user_pass'), um_user('ID') ) ) {
				$ultimatemember->form->add_error('current_user_password', 'This is not your password');
				$ultimatemember->account->current_tab = 'password';
			} else { // correct password
				
				if ( $_POST['user_password'] != $_POST['confirm_user_password'] && $_POST['user_password'] ) {
					$ultimatemember->form->add_error('user_password', 'Your new password does not match');
					$ultimatemember->account->current_tab = 'password';
				}
				
				if ( strlen( utf8_decode( $_POST['user_password'] ) ) < 8 ) {
					$ultimatemember->form->add_error('user_password', __('Your password must contain at least 8 characters') );
				}	
			
				if ( strlen( utf8_decode( $_POST['user_password'] ) ) > 30 ) {
					$ultimatemember->form->add_error('user_password', __('Your password must contain less than 30 characters') );
				}
				
				if ( !$ultimatemember->validation->strong_pass( $_POST['user_password'] ) ) {
					$ultimatemember->form->add_error('user_password', 'Your password must contain at least one capital letter and one number');
					$ultimatemember->account->current_tab = 'password';
				}
				
			}
		}
		
		if ( isset($_POST['um_account_submit']) && $_POST['um_account_submit'] == __('Delete Account') ) {
			if ( strlen(trim( $_POST['single_user_password'] ) ) == 0 ) {
					$ultimatemember->form->add_error('single_user_password', 'You must enter your password');
			} else {
				if ( !wp_check_password( $_POST['single_user_password'], um_user('user_pass'), um_user('ID') ) ) {
					$ultimatemember->form->add_error('single_user_password', 'This is not your password');
				}
			}
			$ultimatemember->account->current_tab = 'delete';
		}
		
	}
	
	/***
	***	@hidden inputs for account page
	***/
	add_action('um_account_page_hidden_fields','um_account_page_hidden_fields');
	function um_account_page_hidden_fields( $args ) {
	
		?>
		
		<input type="hidden" name="_um_account" id="_um_account" value="1" />
		
		<?php
		
	}
	
	/***
	***	@display tab "Delete"
	***/
	add_action('um_account_tab__delete', 'um_account_tab__delete');
	function um_account_tab__delete( $info ) {
		global $ultimatemember;
		extract( $info );
		$fields = $ultimatemember->builtin->get_specific_fields('single_user_password'); ?>
		
		<div class="um-account-heading"><i class="<?php echo $icon; ?>"></i><?php echo $title; ?></div>
		
		<?php echo wpautop( um_get_option('delete_account_text') ); ?>
		
		<?php $output = null;
		foreach( $fields as $key => $data ) {
			$output .= $ultimatemember->fields->edit_field( $key, $data );
		}echo $output; ?>
		
		<div class="um-col-alt um-col-alt-b"><div class="um-left"><input type="submit" name="um_account_submit" id="um_account_submit" value="<?php _e('Delete Account'); ?>" class="um-button" /></div><div class="um-clear"></div></div>
		
		<?php
		
	}

	/***
	***	@display tab "Privacy"
	***/
	add_action('um_account_tab__privacy', 'um_account_tab__privacy');
	function um_account_tab__privacy( $info ) {
		global $ultimatemember;
		extract( $info );
		$fields = $ultimatemember->builtin->get_specific_fields('profile_privacy,show_in_members'); ?>
		
		<div class="um-account-heading"><i class="<?php echo $icon; ?>"></i><?php echo $title; ?></div>
		
		<?php $output = null;
		foreach( $fields as $key => $data ) {
			$output .= $ultimatemember->fields->edit_field( $key, $data );
		}echo $output; ?>
		
		<div class="um-col-alt um-col-alt-b"><div class="um-left"><input type="submit" name="um_account_submit" id="um_account_submit" value="Update Privacy" class="um-button" /></div><div class="um-clear"></div></div>
		
		<?php
		
	}

	/***
	***	@display tab "General"
	***/
	add_action('um_account_tab__general', 'um_account_tab__general');
	function um_account_tab__general( $info ) {
		global $ultimatemember;
		extract( $info );
		$fields = $ultimatemember->builtin->get_specific_fields('user_login,first_name,last_name,user_email'); ?>
		
		<div class="um-account-heading"><i class="<?php echo $icon; ?>"></i><?php echo $title; ?></div>
		
		<?php $output = null;
		foreach( $fields as $key => $data ) {
			$output .= $ultimatemember->fields->edit_field( $key, $data );
		}echo $output; ?>
		
		<div class="um-col-alt um-col-alt-b"><div class="um-left"><input type="submit" name="um_account_submit" id="um_account_submit" value="Update Account" class="um-button" /></div><div class="um-clear"></div></div>
		
		<?php
		
	}
	
	/***
	***	@display tab "Password"
	***/
	add_action('um_account_tab__password', 'um_account_tab__password');
	function um_account_tab__password( $info ) {
		global $ultimatemember;
		extract( $info );
		$fields = $ultimatemember->builtin->get_specific_fields('user_password'); ?>
		
		<div class="um-account-heading"><i class="<?php echo $icon; ?>"></i><?php echo $title; ?></div>
		
		<?php $output = null;
		foreach( $fields as $key => $data ) {
			$output .= $ultimatemember->fields->edit_field( $key, $data );
		}echo $output; ?>
		
		<div class="um-col-alt um-col-alt-b"><div class="um-left"><input type="submit" name="um_account_submit" id="um_account_submit" value="Update Password" class="um-button" /></div><div class="um-clear"></div></div>
		
		<?php
	}
	
	/***
	***	@display account page tabs
	***/
	add_action('um_account_user_photo_hook', 'um_account_user_photo_hook');
	function um_account_user_photo_hook( $args ) {
		global $ultimatemember;
		extract( $args );
		
		?>
		
		<div class="um-account-meta">
			
			<div class="um-account-meta-img"><a href="<?php echo um_user_profile_url(); ?>"><?php echo um_user('profile_photo', 80); ?></a></div>
			
			<div class="um-account-name"><a href="<?php echo um_user_profile_url(); ?>"><?php echo um_user('display_name'); ?></a></div>
		
		</div>
	
		<?php
		
	}
	
	/***
	***	@display account page tabs
	***/
	add_action('um_account_display_tabs_hook', 'um_account_display_tabs_hook');
	function um_account_display_tabs_hook( $args ) {
		global $ultimatemember;
		extract( $args );
		
		$tabs[100]['general']['icon'] = 'um-icon-cog-2';
		$tabs[100]['general']['title'] = 'Account';
		
		$tabs[200]['password']['icon'] = 'um-icon-asterisk-1';
		$tabs[200]['password']['title'] = 'Change Password';
		
		$tabs[300]['privacy']['icon'] = 'um-icon-lock-4';
		$tabs[300]['privacy']['title'] = 'Privacy';
		
		$tabs[400]['notifications']['icon'] = 'um-icon-bell-two';
		$tabs[400]['notifications']['title'] = 'Notifications';
		
		$tabs[500]['delete']['icon'] = 'um-icon-trash-bin-3';
		$tabs[500]['delete']['title'] = 'Delete Account';
		
		$ultimatemember->account->tabs = apply_filters('um_account_page_default_tabs_hook', $tabs );
		
		?>

			<ul>
				
				<?php
				
				foreach( $ultimatemember->account->tabs as $k => $arr ) { 
					foreach( $arr as $id => $info ) { extract( $info ); 
						
						$current_tab = $ultimatemember->account->current_tab;
						
						if ( um_get_option('account_tab_'.$id ) == 1 || $id == 'general' ) { ?>
				
				<li>
					<a data-tab="<?php echo $id; ?>" href="<?php echo $ultimatemember->account->tab_link($id); ?>" class="um-account-link <?php if ( $id == $current_tab ) echo 'current'; ?>">
						<span class="um-account-icon"><i class="<?php echo $icon; ?>"></i></span>
						<span class="um-account-title"><?php echo $title; ?></span>
						<span class="um-account-arrow"><i class="um-icon-right-open"></i></span>
					</a>
				</li>
				
				<?php
				
						}
					} 
				}
				
				?>
				
			</ul>
		
		<?php

	}