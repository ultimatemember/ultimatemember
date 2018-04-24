<div class="um-members">
			
	<div class="um-gutter-sizer"></div>
	
	<?php $i = 0; foreach( um_members('users_per_page') as $member) { $i++; um_fetch_user( $member ); ?>
			
	<div class="um-member um-role-<?php echo um_user( 'role' ); ?> <?php echo um_user('account_status'); ?> <?php if ($cover_photos) { echo 'with-cover'; } ?>">
				
		<span class="um-member-status <?php echo um_user('account_status'); ?>"><?php echo um_user('account_status_name'); ?></span>
					
		<?php
		if ($cover_photos) {
			$sizes = UM()->options()->get('cover_thumb_sizes');
			if ( UM()->mobile()->isTablet() ) {
				$cover_size = $sizes[1];
			} else {
				$cover_size = $sizes[0];
			}
		?>

		<div class="um-member-cover" data-ratio="<?php echo UM()->options()->get('profile_cover_ratio'); ?>">
			<div class="um-member-cover-e"><a href="<?php echo um_user_profile_url(); ?>" title="<?php echo esc_attr(um_user('display_name')); ?>"><?php echo um_user('cover_photo', $cover_size); ?></a></div>
		</div>

		<?php } ?>
		
		<?php if ($profile_photo) {
			$default_size = str_replace( 'px', '', UM()->options()->get('profile_photosize') );
			$corner = UM()->options()->get('profile_photocorner');
		?>
		<div class="um-member-photo radius-<?php echo $corner; ?>"><a href="<?php echo um_user_profile_url(); ?>" title="<?php echo esc_attr(um_user('display_name')); ?>"><?php echo get_avatar( um_user('ID'), $default_size ); ?></a></div>
		<?php } ?>
					
					<div class="um-member-card <?php if (!$profile_photo) { echo 'no-photo'; } ?>">
						
						<?php if ( $show_name ) { ?>
						<div class="um-member-name"><a href="<?php echo um_user_profile_url(); ?>" title="<?php echo esc_attr(um_user('display_name')); ?>"><?php echo um_user('display_name', 'html'); ?></a></div>
						<?php } ?>
						
						<?php
						/**
						 * UM hook
						 *
						 * @type action
						 * @title um_members_just_after_name
						 * @description Show content just after user name
						 * @input_vars
						 * [{"var":"$user_id","type":"int","desc":"User ID"},
						 * {"var":"$args","type":"array","desc":"Member directory shortcode arguments"}]
						 * @change_log
						 * ["Since: 2.0"]
						 * @usage add_action( 'um_members_just_after_name', 'function_name', 10, 2 );
						 * @example
						 * <?php
						 * add_action( 'um_members_just_after_name', 'my_members_just_after_name', 10, 2 );
						 * function my_members_just_after_name( $user_id, $args ) {
						 *     // your code here
						 * }
						 * ?>
						 */
						do_action( 'um_members_just_after_name', um_user('ID'), $args ); ?>

						<?php if ( UM()->roles()->um_current_user_can( 'edit', um_user('ID') ) || UM()->roles()->um_user_can( 'can_edit_everyone' ) ) { ?>
							<div class="um-members-edit-btn">
								<a href="<?php echo um_edit_profile_url() ?>" class="um-edit-profile-btn um-button um-alt"><?php _e( 'Edit profile','ultimate-member' ) ?></a>
							</div>
						<?php }

						/**
						 * UM hook
						 *
						 * @type action
						 * @title um_members_after_user_name
						 * @description Show content just after user name
						 * @input_vars
						 * [{"var":"$user_id","type":"int","desc":"User ID"},
						 * {"var":"$args","type":"array","desc":"Member directory shortcode arguments"}]
						 * @change_log
						 * ["Since: 2.0"]
						 * @usage add_action( 'um_members_after_user_name', 'function_name', 10, 2 );
						 * @example
						 * <?php
						 * add_action( 'um_members_after_user_name', 'my_members_after_user_name', 10, 2 );
						 * function my_members_after_user_name( $user_id, $args ) {
						 *     // your code here
						 * }
						 * ?>
						 */
						do_action( 'um_members_after_user_name', um_user('ID'), $args ); ?>
						
						<?php
						if ( $show_tagline && is_array( $tagline_fields ) ) {
							
							um_fetch_user( $member );

							foreach( $tagline_fields as $key ) {
								if ( $key /*&& um_filtered_value( $key )*/ ) {
									$value = um_filtered_value( $key );
									if ( ! $value )
										continue;
						?>
						
						<div class="um-member-tagline um-member-tagline-<?php echo $key;?>"><?php _e( $value, 'ultimate-member'); ?></div>
						
						<?php
								} // end if
							} // end foreach
						} // end if $show_tagline

						if ( ! empty( $show_userinfo ) ) { ?>

							<div class="um-member-meta-main">
						
							<?php if ( $userinfo_animate ) { ?>
								<div class="um-member-more"><a href="#"><i class="um-faicon-angle-down"></i></a></div>
							<?php } ?>
							
							<div class="um-member-meta <?php if ( ! $userinfo_animate ) { echo 'no-animate'; } ?>">
							
								<?php um_fetch_user( $member );
								foreach ( $reveal_fields as $key ) {
									if ( $key ) {
										$value = um_filtered_value( $key );
										if ( ! $value )
											continue; ?>
								
										<div class="um-member-metaline um-member-metaline-<?php echo $key; ?>"><span><strong><?php echo UM()->fields()->get_label( $key ); ?>:</strong> <?php _e( $value, 'ultimate-member'); ?></span></div>

									<?php }
								} 

								if ( $show_social ) { ?>
									<div class="um-member-connect">
										<?php UM()->fields()->show_social_urls(); ?>
									</div>
								<?php } ?>
								
							</div>

							<div class="um-member-less"><a href="#"><i class="um-faicon-angle-up"></i></a></div>
						
						</div>
						
						<?php } ?>
						
					</div>
					
	</div>
				
	<?php 
	um_reset_user_clean();
	} // end foreach

	um_reset_user();
	?>

	<div class="um-clear"></div>

</div>
