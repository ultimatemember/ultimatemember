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
		
		if ( isset( $changes['hide_in_members'] ) && $changes['hide_in_members'] == 'No' ){
			delete_user_meta( um_user('ID'), 'hide_in_members' );
			unset( $changes['hide_in_members'] );
		}

		$ultimatemember->user->update_profile( $changes );

		// delete account
		if ( $_POST['single_user_password'] && isset($_POST['um_account_submit']) && $_POST['um_account_submit'] == __('Delete Account','ultimatemember') ) {
			if ( current_user_can('delete_users') || um_user('can_delete_profile') ) {
				if ( !um_user('super_admin') ) {
					$ultimatemember->user->delete();
					if ( um_user('after_delete') == 'redirect_home' ) {
						um_redirect_home();
					} else {
						exit( wp_redirect( um_user('delete_redirect_url') ) );
					}
				}
			}
		}
		
		exit( wp_redirect( um_get_core_page('account') ) );
		
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
					$ultimatemember->form->add_error('user_password', __('Your password must contain at least one lowercase letter, one capital letter and one number','ultimatemember') );
					$ultimatemember->account->current_tab = 'password';
				}
				
			}
		}
		
		if ( isset($_POST['um_account_submit']) && $_POST['um_account_submit'] == __('Delete Account','ultimatemember') ) {
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
		
		$output = $ultimatemember->account->get_tab_output('delete');
		
		if ( $output ) { ?>
		
		<div class="um-account-heading uimob300-hide uimob500-hide"><i class="<?php echo $icon; ?>"></i><?php echo $title; ?></div>
		
		<?php echo wpautop( um_get_option('delete_account_text') ); ?>
		
		<?php echo $output; ?>
		
		<div class="um-col-alt um-col-alt-b"><div class="um-left"><input type="submit" name="um_account_submit" id="um_account_submit" value="<?php _e('Delete Account','ultimatemember'); ?>" class="um-button" /></div><div class="um-clear"></div></div>
		
		<?php
		
		}
	}

	/***
	***	@display tab "Privacy"
	***/
	add_action('um_account_tab__privacy', 'um_account_tab__privacy');
	function um_account_tab__privacy( $info ) {
		global $ultimatemember;
		extract( $info );
		
		$output = $ultimatemember->account->get_tab_output('privacy');

		if ( $output ) { ?>
		
		<div class="um-account-heading uimob340-hide uimob500-hide"><i class="<?php echo $icon; ?>"></i><?php echo $title; ?></div>
		
		<?php echo $output; ?>
		
		<div class="um-col-alt um-col-alt-b"><div class="um-left"><input type="submit" name="um_account_submit" id="um_account_submit" value="<?php _e('Update Privacy','ultimatemember'); ?>" class="um-button" /></div><div class="um-clear"></div></div>
		
		<?php
		
		}
	}

	/***
	***	@display tab "General"
	***/
	add_action('um_account_tab__general', 'um_account_tab__general');
	function um_account_tab__general( $info ) {
		global $ultimatemember;
		extract( $info );
		
		$output = $ultimatemember->account->get_tab_output('general');
		
		if ( $output ) { ?>
		
		<div class="um-account-heading uimob340-hide uimob500-hide"><i class="<?php echo $icon; ?>"></i><?php echo $title; ?></div>
		
		<?php echo $output; ?>
		
		<div class="um-col-alt um-col-alt-b"><div class="um-left"><input type="submit" name="um_account_submit" id="um_account_submit" value="<?php _e('Update Account','ultimatemember'); ?>" class="um-button" /></div><div class="um-clear"></div></div>
		
		<?php
		
		}
	}
	
	/***
	***	@display tab "Password"
	***/
	add_action('um_account_tab__password', 'um_account_tab__password');
	function um_account_tab__password( $info ) {
		global $ultimatemember;
		extract( $info );
		extract( $info );
		
		$output = $ultimatemember->account->get_tab_output('password');
		
		if ( $output ) { ?>
		
		<div class="um-account-heading uimob340-hide uimob500-hide"><i class="<?php echo $icon; ?>"></i><?php echo $title; ?></div>
		
		<?php echo $output; ?>
		
		<div class="um-col-alt um-col-alt-b"><div class="um-left"><input type="submit" name="um_account_submit" id="um_account_submit" value="<?php _e('Update Password','ultimatemember'); ?>" class="um-button" /></div><div class="um-clear"></div></div>
		
		<?php
		
		}
	}
	
	/***
	***	@display account photo and username
	***/
	add_action('um_account_user_photo_hook__mobile', 'um_account_user_photo_hook__mobile');
	function um_account_user_photo_hook__mobile( $args ) {
		global $ultimatemember;
		extract( $args );
		
		?>
		
		<div class="um-account-meta radius-<?php echo um_get_option('profile_photocorner'); ?> uimob340-show uimob500-show">
			
			<div class="um-account-meta-img"><a href="<?php echo um_user_profile_url(); ?>"><?php echo get_avatar( um_user('ID'), 120); ?></a></div>
			
			<div class="um-account-name"><a href="<?php echo um_user_profile_url(); ?>"><?php echo um_user('display_name'); ?></a></div>
		
		</div>
	
		<?php
		
	}
	
	/***
	***	@display account photo and username
	***/
	add_action('um_account_user_photo_hook', 'um_account_user_photo_hook');
	function um_account_user_photo_hook( $args ) {
		global $ultimatemember;
		extract( $args );
		
		?>
		
		<div class="um-account-meta radius-<?php echo um_get_option('profile_photocorner'); ?>">
			
			<div class="um-account-meta-img uimob800-hide"><a href="<?php echo um_user_profile_url(); ?>"><?php echo get_avatar( um_user('ID'), 120); ?></a></div>
			
			<?php if ( $ultimatemember->mobile->isMobile() ) { ?>
			
			<div class="um-account-meta-img-b uimob800-show" title="<?php echo um_user('display_name'); ?>"><a href="<?php echo um_user_profile_url(); ?>"><?php echo get_avatar( um_user('ID'), 120); ?></a></div>
			
			<?php } else { ?>
			
			<div class="um-account-meta-img-b uimob800-show um-tip-w" title="<?php echo um_user('display_name'); ?>"><a href="<?php echo um_user_profile_url(); ?>"><?php echo get_avatar( um_user('ID'), 120); ?></a></div>
			
			<?php } ?>
			
			<div class="um-account-name uimob800-hide"><a href="<?php echo um_user_profile_url(); ?>"><?php echo um_user('display_name'); ?></a></div>
		
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
		
		$tabs[100]['general']['icon'] = 'um-faicon-user';
		$tabs[100]['general']['title'] = __('Account','ultimatemember');
		
		$tabs[200]['password']['icon'] = 'um-faicon-asterisk';
		$tabs[200]['password']['title'] = __('Change Password','ultimatemember');
		
		$tabs[300]['privacy']['icon'] = 'um-faicon-lock';
		$tabs[300]['privacy']['title'] = __('Privacy','ultimatemember');
		
		$tabs[400]['notifications']['icon'] = 'um-icon-ios-bell';
		$tabs[400]['notifications']['title'] = __('Notifications','ultimatemember');
		
		$tabs[500]['delete']['icon'] = 'um-faicon-trash-o';
		$tabs[500]['delete']['title'] = __('Delete Account','ultimatemember');
		
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
						
						<?php if ( $ultimatemember->mobile->isMobile() ) { ?>
						<span class="um-account-icontip uimob800-show" title="<?php echo $title; ?>"><i class="<?php echo $icon; ?>"></i></span>
						<?php } else { ?>
						<span class="um-account-icontip uimob800-show um-tip-w" title="<?php echo $title; ?>"><i class="<?php echo $icon; ?>"></i></span>
						<?php } ?>
						
						<span class="um-account-icon uimob800-hide"><i class="<?php echo $icon; ?>"></i></span>
						<span class="um-account-title uimob800-hide"><?php echo $title; ?></span>
						<span class="um-account-arrow uimob800-hide"><i class="um-faicon-angle-right"></i></span>
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