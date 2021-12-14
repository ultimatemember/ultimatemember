<?php if ( ! defined( 'ABSPATH' ) ) exit;

$unique_hash = substr( md5( $args['form_id'] ), 10, 5 ); ?>

<script type="text/template" id="tmpl-um-member-grid-<?php echo esc_attr( $unique_hash ) ?>">
	<div class="um-members um-members-grid">
		<div class="um-gutter-sizer"></div>

		<# if ( data.length > 0 ) { #>
			<# _.each( data, function( user, key, list ) { #>

				<div id="um-member-{{{user.card_anchor}}}-<?php echo esc_attr( $unique_hash ) ?>" class="um-member um-role-{{{user.role}}} {{{user.account_status}}} <?php if ( $cover_photos ) { echo 'with-cover'; } ?>">

					<span class="um-member-status {{{user.account_status}}}">
						{{{user.account_status_name}}}
					</span>

					<?php if ( $cover_photos ) { ?>
						<div class="um-member-cover" data-ratio="<?php echo esc_attr( UM()->options()->get( 'profile_cover_ratio' ) ); ?>">
							<div class="um-member-cover-e">
								<a href="{{{user.profile_url}}}" title="<# if ( user.display_name ) { #>{{{user.display_name}}}<# } #>">
									{{{user.cover_photo}}}
								</a>
							</div>
						</div>
					<?php }

					if ( $profile_photo ) { ?>
						<div class="um-member-photo radius-<?php echo esc_attr( UM()->options()->get( 'profile_photocorner' ) ); ?>">
							<a href="{{{user.profile_url}}}" title="<# if ( user.display_name ) { #>{{{user.display_name}}}<# } #>">
								{{{user.avatar}}}
								<?php do_action( 'um_members_in_profile_photo_tmpl', $args ); ?>
							</a>
						</div>
					<?php } ?>


					<div class="um-member-card <?php if ( ! $profile_photo ) { echo 'no-photo'; } ?>">
						<?php if ( $show_name ) { ?>
							<# if ( user.display_name_html ) { #>
								<div class="um-member-name">
									<a href="{{{user.profile_url}}}" title="{{{user.display_name}}}">
										{{{user.display_name_html}}}
									</a>
								</div>
							<# } #>
						<?php }

						// please use for buttons priority > 100
						do_action( 'um_members_just_after_name_tmpl', $args ); ?>
						{{{user.hook_just_after_name}}}


						<# if ( user.can_edit ) { #>
							<div class="um-members-edit-btn">
								<a href="{{{user.edit_profile_url}}}" class="um-edit-profile-btn um-button um-alt">
									<?php _e( 'Edit profile','ultimate-member' ) ?>
								</a>
							</div>
						<# } #>


						<?php do_action( 'um_members_after_user_name_tmpl', $args ); ?>
						{{{user.hook_after_user_name}}}


						<?php if ( $show_tagline && ! empty( $tagline_fields ) && is_array( $tagline_fields ) ) {
							foreach ( $tagline_fields as $key ) {
								if ( empty( $key ) ) {
									continue;
								} ?>

								<# if ( typeof user['<?php echo $key; ?>'] !== 'undefined' ) { #>
									<div class="um-member-tagline um-member-tagline-<?php echo esc_attr( $key ); ?>"
									     data-key="<?php echo esc_attr( $key ); ?>">
										{{{user['<?php echo $key; ?>']}}}
									</div>
								<# } #>

							<?php }
						}

						if ( $show_userinfo ) { ?>

							<# var $show_block = false; #>

							<?php foreach ( $reveal_fields as $k => $key ) {
								if ( empty( $key ) ) {
									unset( $reveal_fields[ $k ] );
								} ?>
								<# if ( typeof user['<?php echo $key; ?>'] !== 'undefined' ) {
									$show_block = true;
								} #>
							<?php }

							if ( $show_social ) { ?>
								<# if ( ! $show_block ) { #>
									<# $show_block = user.social_urls #>
								<# } #>
							<?php } ?>

							<# if ( $show_block ) { #>
								<div class="um-member-meta-main">

									<?php if ( $userinfo_animate ) { ?>
										<div class="um-member-more">
											<a href="javascript:void(0);"><i class="um-faicon-angle-down"></i></a>
										</div>
									<?php } ?>

									<div class="um-member-meta <?php if ( ! $userinfo_animate ) { echo 'no-animate'; } ?>">

										<?php foreach ( $reveal_fields as $key ) { ?>

											<# if ( typeof user['<?php echo $key; ?>'] !== 'undefined' ) { #>
												<div class="um-member-metaline um-member-metaline-<?php echo $key; ?>">
													<strong>{{{user['label_<?php echo $key;?>']}}}:</strong> {{{user['<?php echo $key;?>']}}}
												</div>
											<# } #>

										<?php }

										if ( $show_social ) { ?>
											<div class="um-member-connect">
												{{{user.social_urls}}}
											</div>
										<?php } ?>
									</div>

									<?php if ( $userinfo_animate ) { ?>
										<div class="um-member-less">
											<a href="javascript:void(0);"><i class="um-faicon-angle-up"></i></a>
										</div>
									<?php } ?>
								</div>
							<# } #>
						<?php } ?>

					</div>
				</div>

			<# }); #>
		<# } else { #>

			<div class="um-members-none">
				<p><?php echo $no_users; ?></p>
			</div>

		<# } #>

		<div class="um-clear"></div>
	</div>
</script>
