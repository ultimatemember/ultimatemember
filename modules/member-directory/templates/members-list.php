<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$unique_hash = substr( md5( $args['form_id'] ), 10, 5 ); ?>

<script type="text/template" id="tmpl-um-member-list-<?php echo esc_attr( $unique_hash ) ?>">
	<# if ( data.length > 0 ) { #>
		<# _.each( data, function( user, key, list ) { #>

			<div id="um-member-{{{user.card_anchor}}}-<?php echo esc_attr( $unique_hash ) ?>" class="um-member um-role-{{{user.role}}} {{{user.account_status}}} <?php if ( $cover_photos ) { echo 'with-cover'; } ?>">
				<div class="um-member-card-container">
					<?php if ( $profile_photo ) { ?>
						<div class="um-member-photo">
							<a href="{{{user.profile_url}}}" title="<# if ( user.display_name ) { #>{{{user.display_name}}}<# } #>">
								{{{user.avatar}}}
								<?php do_action( 'um_members_list_in_profile_photo_tmpl', $args ); ?>
							</a>
						</div>
					<?php } ?>

					<div class="um-member-card <?php echo ! $profile_photo ? 'no-photo' : '' ?>">
						<div class="um-member-card-content">
							<div class="um-member-card-header">
								<?php if ( $show_name ) { ?>
									<# if ( user.display_name_html ) { #>
										<div class="um-member-name">
											<a href="{{{user.profile_url}}}" title="<# if ( user.display_name ) { #>{{{user.display_name}}}<# } #>">
												{{{user.display_name_html}}}
											</a>
										</div>
									<# } #>
								<?php } ?>

								<?php do_action( 'um_members_list_after_user_name_tmpl', $args ); ?>
							</div>

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
							} ?>
						</div>

						<div class="um-member-card-actions">

							<# if ( Object.keys( user.dropdown_actions ).length > 0 ) { #>
								<div class="um-member-cog">
									<a href="javascript:void(0);" class="um-member-actions-a">
										<i class="fas fa-cog"></i>
									</a>
									<?php UM()->frontend()->helpers()->dropdown_menu_js( '.um-member-cog', 'click', 'user' ); ?>
								</div>
							<# } #>

						</div>

					</div>
				</div>
				<div class="um-member-card-footer <?php echo ! $profile_photo ? 'no-photo' : '' ?> <?php if ( $show_userinfo ) { ?><# if ( ! $show_block ) { #>no-reveal<# } #><?php } ?>">
					<div class="um-member-card-footer-buttons">
						<?php do_action( 'um_members_list_just_after_actions_tmpl', $args ); ?>
						<a href="{{{user.profile_url}}}" title="<?php printf( esc_attr__( 'View %s\'s Profile','ultimate-member' ), '{{{user.display_name}}}' ); ?>" class="um-view-profile-btn um-button um-button-primary">
							<?php esc_html_e( 'View profile','ultimate-member' ); ?>
						</a>
					</div>
				</div>
			</div>

		<# }); #>
	<# } else { #>

		<div class="um-members-none">
			<p><?php echo $no_users; ?></p>
		</div>

	<# } #>
</script>
