<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$unique_hash = substr( md5( $args['form_id'] ), 10, 5 );
?>

<script type="text/template" id="tmpl-um-member-grid-<?php echo esc_attr( $unique_hash ) ?>">
	<# if ( data.length > 0 ) { #>
		<# _.each( data, function( user, key, list ) { #>

			<div id="um-member-{{{user.card_anchor}}}-<?php echo esc_attr( $unique_hash ) ?>" class="um-member um-role-{{{user.role}}} {{{user.account_status}}} <?php if ( $cover_photos ) { echo 'with-cover'; } ?>">
				<div class="um-member-card-left-actions">
					<# if ( Object.keys( user.dropdown_actions ).length > 0 ) { #>
						<div class="um-member-cog">
							<a href="javascript:void(0);" class="um-button um-button-small um-member-actions-a">
								<i class="fas fa-cog"></i>
							</a>
							<?php UM()->frontend()->helpers()->dropdown_menu_js( '.um-member-cog', 'click', 'user', '', '.um-member', 190, 'bottom-right' ); ?>
						</div>
					<# } #>
				</div>
				<div class="um-member-card-right-actions">
					<!-- Maybe bookmarks are here -->
				</div>

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
					<div class="um-member-photo">
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
					<?php } ?>

					<span class="um-tag um-member-status {{{user.account_status}}}">
						{{{user.account_status_name}}}
					</span>

					<?php do_action( 'um_members_just_after_name_tmpl', $args ); ?>

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

					if ( $show_social ) { ?>
						<# if ( typeof user.social_urls !== 'undefined' ) { #>
							<div class="um-member-connect">
								{{{user.social_urls}}}
							</div>
						<# } #>
					<?php } ?>

					<?php if ( $show_userinfo ) { ?>
						<a href="javascript:void(0);" class="um-link um-member-more-details-link" data-user_id="{{{user.id}}}"><?php esc_html_e( 'More details', 'ultimate-member' ); ?></a>
					<?php } ?>
				</div>
				<div class="um-member-action-buttons">
					<?php $extra_buttons = apply_filters( 'um_members_grid_actions_buttons', '', $args ); ?>

					<?php if ( '' !== $extra_buttons ) { ?>
						<div class="um-member-action-extra-buttons">
							<?php echo $extra_buttons; ?>
						</div>
					<?php } ?>

					<a href="{{{user.profile_url}}}" title="<?php printf( esc_attr__( 'View %s\'s Profile','ultimate-member' ), '{{{user.display_name}}}' ); ?>" class="um-view-profile-btn um-button um-button-primary">
						<?php esc_html_e( 'View profile','ultimate-member' ); ?>
					</a>
				</div>
			</div>

		<# }); #>
	<# } else { #>

		<div class="um-members-none">
			<p><?php echo $no_users; ?></p>
		</div>

	<# } #>

</script>
