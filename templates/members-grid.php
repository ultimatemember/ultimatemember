<script type="text/template" id="tmpl-um-member-grid">
	<div class="um-members">

		<div class="um-gutter-sizer"></div>

		<# _.each( data.users, function( user, key, list ) { #>
			<div class="um-member um-role-{{{user.role}}} {{{user.account_status}}} <?php if ( $cover_photos ) { echo 'with-cover'; } ?>">
				<span class="um-member-status {{{user.account_status}}}">{{{user.account_status_name}}}</span>

				<?php if ( $cover_photos ) {
					$sizes = um_get_option( 'cover_thumb_sizes' );
					$cover_size = UM()->mobile()->isTablet() ? $sizes[1] : $sizes[0]; ?>

					<div class="um-member-cover" data-ratio="<?php echo um_get_option( 'profile_cover_ratio' ); ?>">
						<div class="um-member-cover-e">
							<a href="{{{user.profile_url}}}" title="{{{user.display_name}}}">
								{{{user.cover_photo}}}
							</a>
						</div>
					</div>

				<?php }

				if ( $profile_photo ) { ?>
					<div class="um-member-photo radius-<?php echo um_get_option( 'profile_photocorner' ); ?>">
						<a href="{{{user.profile_url}}}" title="{{{user.display_name}}}">
							{{{user.avatar}}}
						</a>
					</div>
				<?php } ?>

				<div class="um-member-card <?php if ( ! $profile_photo ) { echo 'no-photo'; } ?>">

					<?php if ( $show_name ) { ?>
						<div class="um-member-name">
							<a href="{{{user.profile_url}}}" title="{{{user.display_name}}}">
								{{{user.display_name_html}}}
							</a>
						</div>
					<?php }

					do_action( 'um_members_just_after_name', um_user('ID'), $args );

					if ( UM()->roles()->um_current_user_can( 'edit', um_user('ID') ) || UM()->roles()->um_user_can( 'can_edit_everyone' ) ) { ?>
						<div class="um-members-edit-btn">
							<a href="{{{user.edit_profile_url}}}" class="um-edit-profile-btn um-button um-alt">
								<?php _e( 'Edit profile','ultimate-member' ) ?>
							</a>
						</div>
					<?php }

					do_action( 'um_members_after_user_name', um_user('ID'), $args );

					if ( $show_tagline && is_array( $tagline_fields ) ) {
						foreach ( $tagline_fields as $key ) {
							if ( $key && um_filtered_value( $key ) ) { ?>

								<div class="um-member-tagline um-member-tagline-<?php echo $key;?>">
									{{{user.<?php echo $key;?>}}}
								</div>

							<?php }
						}
					}

					if ( $show_userinfo ) { ?>

						<div class="um-member-meta-main">

							<?php if ( $userinfo_animate ) { ?>
								<div class="um-member-more"><a href="#"><i class="um-faicon-angle-down"></i></a></div>
							<?php } ?>

							<div class="um-member-meta <?php if ( !$userinfo_animate ) { echo 'no-animate'; } ?>">

								<?php foreach ( $reveal_fields as $key ) {
									if ( $key && um_filtered_value( $key ) ) { ?>

										<div class="um-member-metaline um-member-metaline-<?php echo $key; ?>">
										<span>
											<strong>{{{user.label_<?php echo $key;?>}}}:</strong>
											{{{user.<?php echo $key;?>}}}
										</span>
										</div>

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
			<# }); #>

		<div class="um-clear"></div>
	</div>
</script>