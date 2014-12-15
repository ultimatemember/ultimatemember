<?php

	/***
	***	@if editing another user
	***/
	add_action('um_after_form_fields', 'um_editing_user_id_input');
	function um_editing_user_id_input($args){ 
		global $ultimatemember;
		if ( $ultimatemember->fields->editing == 1 && $ultimatemember->fields->set_mode == 'profile' && $ultimatemember->user->target_id ) { ?>
	
		<input type="hidden" name="user_id" id="user_id" value="<?php echo $ultimatemember->user->target_id; ?>" />

		<?php
		
		}
	}
	
	/***
	***	@meta description
	***/
	add_action('wp_head', 'um_profile_dynamic_meta_desc', 9999999);
	function um_profile_dynamic_meta_desc() {
	
		global $ultimatemember;
		
		if ( um_is_user_page_uri() && um_get_requested_user() ) {
		
			um_fetch_user( um_get_requested_user() );
			
			$content = $ultimatemember->mail->convert_tags( um_get_option('profile_desc') );
			
			um_reset_user();
			
			?>
		
			<meta name="description" content="<?php echo $content; ?>">
		
			<?php
		
		}
	}
	
	/***
	***	@profile header cover
	***/
	add_action('um_profile_header_cover_area', 'um_profile_header_cover_area' );
	function um_profile_header_cover_area( $args ) {
		global $ultimatemember;
		
		if ( $args['cover_enabled'] == 1 ) {
		
		?>
		
			<div class="um-cover">
				<div class="um-cover-e">
					<?php if ( um_user('cover_photo') ) { echo um_user('cover_photo'); } else { ?>
					<a href="#" class="um-cover-add"><span class="um-cover-add-i"><i class="um-icon-plus-add um-tip-n" title="Upload a cover photo"></i></span></a>
					<?php } ?>
				</div>
			</div>
			
		<?php
		
		}
		
	}
	
	/***
	***	@profile header
	***/
	add_action('um_profile_header', 'um_profile_header' );
	function um_profile_header( $args ) {
		global $ultimatemember;
		
		$classes = null;
		
		if ( !$args['cover_enabled'] ) {
			$classes .= ' no-cover';
		}
		
		?>
		
			<div class="um-header<?php echo $classes; ?>">
			
				<?php do_action('um_pre_header_editprofile', $args); ?>
				
				<div class="um-profile-photo">
					<a href="<?php echo um_user_profile_url(); ?>" class="um-profile-photo-img"><?php echo um_user('profile_photo'); ?></a>
				</div>
				
				<div class="um-profile-meta">
				
					<div class="um-main-meta">
						<div class="um-name"><a href="<?php echo um_user_profile_url(); ?>"><?php echo um_user('display_name'); ?></a></div>
						<div class="um-clear"></div>
					</div>
					
					<div class="um-meta">
						<span>Web designer</span>
						<span class="b">&bull;</span>
						<span>28 years</span>
						<span class="b">&bull;</span>
						<span>United Kingdom</span>
					</div>
					
					<?php if ( um_user('description') ) { ?>
					<div class="um-meta-text"><?php echo um_user('description'); ?></div>
					<?php } ?>
					
				</div><div class="um-clear"></div>
				
			</div>
			
		<?php
	}
	
	/***
	***	@adds profile permissions to view/edit
	***/
	add_action('um_pre_profile_shortcode', 'um_pre_profile_shortcode');
	function um_pre_profile_shortcode($args){
		global $ultimatemember;
		extract( $args );

		if ( $mode == 'profile' && $ultimatemember->fields->editing == false ) {
			$ultimatemember->fields->viewing = 1;
			
			if ( um_get_requested_user() ) {
				if ( !um_can_view_profile( um_get_requested_user() ) ) exit( wp_redirect( home_url() ) );
				if ( !um_can_edit_profile( um_get_requested_user() ) ) $ultimatemember->user->cannot_edit = 1;
				um_fetch_user( um_get_requested_user() );
			} else {
				if ( !is_user_logged_in() ) exit( wp_redirect( home_url() ) );
				if ( !um_user('can_edit_profile') ) $ultimatemember->user->cannot_edit = 1;
			}
			
		}

		if ( $mode == 'profile' && $ultimatemember->fields->editing == true ) {
			$ultimatemember->fields->editing = 1;
		
			if ( um_get_requested_user() ) {
				if ( !um_can_edit_profile( um_get_requested_user() ) ) exit( wp_redirect( home_url() ) );
				um_fetch_user( um_get_requested_user() );
			}
			
		}
		
	}
	
	/***
	***	@display the edit profile icon
	***/
	add_action('um_pre_header_editprofile', 'um_add_edit_icon' );
	function um_add_edit_icon( $args ) {
		global $ultimatemember;
		$output = '';
		
		if ( !is_user_logged_in() ) return; // not allowed for guests
		
		if ( isset( $ultimatemember->user->cannot_edit ) && $ultimatemember->user->cannot_edit == 1 ) return;
		
		if ( $ultimatemember->fields->editing == true ) {
			$output .= '<div class="um-profile-edit um-profile-headericon"><a href="#" title="Save Profile" class="um-profile-save um-tip-n"><i class="um-icon-check"></i></a></div>';
		} else {
			$output .= '<div class="um-profile-edit um-profile-headericon"><a href="'.um_edit_my_profile_uri().'" title="Edit Profile" class="um-tip-n"><i class="um-icon-cog-2"></i></a></div>';
		}

		echo $output;
	}
	
	/***
	***	@update user's profile
	***/
	add_action('um_user_edit_profile', 'um_user_edit_profile', 10);
	function um_user_edit_profile($args){
		
		global $ultimatemember;
		
		$to_update = null;
		
		if ( isset( $args['user_id'] ) ) {
			if ( um_can_edit_profile( $args['user_id'] ) ) {
				$ultimatemember->user->set( $args['user_id'] );
			} else {
				wp_die( __('You are not allowed to edit this user.','ultimatemember') );
			}
		}
		
		$userinfo = $ultimatemember->user->profile;
		
		$fields = unserialize( $args['custom_fields'] );
		
		do_action('um_user_before_updating_profile', $userinfo );
		
		foreach( $fields as $key => $array ) {
			if ( isset( $args['submitted'][ $key ] ) ) {
			
				if ( isset( $userinfo[$key]) && $args['submitted'][$key] != $userinfo[$key] ) {
					$to_update[ $key ] = $args['submitted'][ $key ];
				} else if ( $args['submitted'][$key] ) {
					$to_update[ $key ] = $args['submitted'][ $key ];
				}

			}
		}
		
		if ( is_array( $to_update ) ) {
			$ultimatemember->user->update_profile( $to_update );
		}
		
		do_action('um_user_after_updating_profile', $to_update );
		
		exit( wp_redirect( um_edit_my_profile_cancel_uri() ) );
		
	}
	
	/***
	***	@Show Fields
	***/
	add_action('um_main_profile_fields', 'um_add_profile_fields', 100);
	function um_add_profile_fields($args){
		global $ultimatemember;
		
		if ( $ultimatemember->fields->editing == true ) {
		
			echo $ultimatemember->fields->display( 'profile', $args );
			
		} else {
		
			$ultimatemember->fields->viewing = true;
			
			echo $ultimatemember->fields->display_view( 'profile', $args );
			
		}
		
	}
	
	/***
	***	@form processing
	***/
	add_action('um_submit_form_profile', 'um_submit_form_profile', 10);
	function um_submit_form_profile($args){
		global $ultimatemember;
	
		if ( !isset($ultimatemember->form->errors) ) do_action('um_user_edit_profile', $args);

		do_action('um_user_profile_extra_hook', $args );
		
	}
	
	/***
	***	@Show the submit button (highest priority)
	***/
	add_action('um_after_profile_fields', 'um_add_submit_button_to_profile', 1000);
	function um_add_submit_button_to_profile($args){
		global $ultimatemember;
		
		// DO NOT add when reviewing user's details
		if ( $ultimatemember->user->preview == true && is_admin() ) return;
		
		// only when editing
		if ( $ultimatemember->fields->editing == false ) return;
		
		?>
		
		<div class="um-col-alt">
		
			<?php if ( isset($args['secondary_btn']) && $args['secondary_btn'] != 0 ) { ?>
			
			<div class="um-left um-half"><input type="submit" value="<?php echo $args['primary_btn_word']; ?>" class="um-button" /></div>
			<div class="um-right um-half"><a href="<?php echo um_edit_my_profile_cancel_uri(); ?>" class="um-button um-alt"><?php echo $args['secondary_btn_word']; ?></a></div>
			
			<?php } else { ?>
			
			<div class="um-center"><input type="submit" value="<?php echo $args['primary_btn_word']; ?>" class="um-button" /></div>
			
			<?php } ?>
			
			<div class="um-clear"></div>
			
		</div>
	
		<?php
	}