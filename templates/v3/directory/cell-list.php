<?php
/**
 * Template for the members directory list
 *
 * This template can be overridden by copying it to your-theme/ultimate-member/members-list.php
 *
 * Page: "Members"
 *
 * @version 2.6.1
 *
 * @var array  $member
 * @var array  $t_args
 * @var string $unique_hash
 * @var array  $directory_data
 * @var string $form_id
 * @var bool   $cover_photos
 * @var bool   $profile_photo
 * @var bool   $show_name
 * @var bool   $show_tagline
 * @var bool   $show_userinfo
 * @var bool   $userinfo_animate
 * @var bool   $show_social
 * @var array  $reveal_fields
 * @var string $no_users
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$card_id      = 'um-member-' . $member['card_anchor'] . '-' . $unique_hash;
$card_classes = array(
	'um-member',
	'um-role-' . $member['role'],
	'um-member-status-' . $member['account_status'],
);

if ( $directory_data['cover_photos'] && $member['cover_photo'] ) {
	$card_classes[] = 'um-member-with-cover';
} else {
	$card_classes[] = 'um-member-no-cover';
}

if ( ! $directory_data['profile_photo'] ) {
	$card_classes[] = 'um-member-no-photo';
}

if ( empty( $member['dropdown_actions'] ) ) {
	$card_classes[] = 'um-member-no-actions';
}

ob_start();
?>
<div class="um-member-skeleton">
	<div class="um-member-skeleton-inner">
		<?php if ( $directory_data['profile_photo'] ) { ?>
			<div class="um-skeleton-box-avatar"></div>
		<?php } ?>
		<div class="um-skeleton-box-group">
			<div class="um-skeleton-box-header"></div>
			<div class="um-skeleton-box"></div>
			<div class="um-skeleton-box"></div>
			<div class="um-skeleton-box"></div>
			<div class="um-skeleton-box"></div>
			<div class="um-skeleton-box"></div>
			<div class="um-skeleton-box"></div>
			<div class="um-skeleton-box"></div>
			<div class="um-skeleton-box"></div>
		</div>
	</div>
</div>
<div class="um-member-box-main">
	<?php
	echo wp_kses(
		UM()->frontend()::layouts()::dropdown_menu(
			'um-member-actions-toggle',
			$member['dropdown_actions'],
			array(
				'width' => 210,
			)
		),
		UM()->get_allowed_html( 'templates' )
	);

	if ( $directory_data['profile_photo'] ) {
		echo wp_kses( UM()->frontend()::layouts()::single_avatar( $member['id'], array( 'size' => 'l' ) ), UM()->get_allowed_html( 'templates' ) );
		do_action( 'um_members_list_in_profile_photo_tmpl', $t_args );
	}
	?>

	<div class="um-member-list-data">
		<div class="um-member-nameline">
			<?php
			if ( $directory_data['show_name'] && $member['display_name_html'] ) {
				?>
				<span class="um-member-name" title="<?php if ( $member['display_name'] ) { echo esc_attr( $member['display_name'] ); } ?>">
					<?php echo wp_kses( $member['display_name_html'], UM()->get_allowed_html( 'templates' ) ); ?>
				</span>
				<?php
			}

			if ( 'approved' !== $member['account_status'] ) {
				$status_badge = array(
					'class' => array( 'um-member-status' ),
					'size'  => 's',
				);
				if ( 'awaiting_admin_review' === $member['account_status'] ) {
					$status_badge['color'] = 'error';
				}
				echo wp_kses( UM()->frontend()::layouts()::badge( $member['account_status_name'], $status_badge ), UM()->get_allowed_html( 'templates' ) );
			} ?>
		</div>
		<?php
		// {{{user.hook_just_after_name}}}
		do_action( 'um_members_just_after_name', $member['id'], $directory_data );
		// {{{user.hook_after_user_name}}}
		do_action( 'um_members_after_user_name', $member['id'], $directory_data );

		if ( ( $directory_data['show_tagline'] && ! empty( $directory_data['tagline_fields'] ) && is_array( $directory_data['tagline_fields'] ) ) || 'approved' !== $member['account_status'] ) {
			?>
			<div class="um-member-taglines">
				<?php
				foreach ( $directory_data['tagline_fields'] as $key ) {
					if ( empty( $key ) ) {
						continue;
					}

					if ( empty( $member[ $key ] ) ) {
						continue;
					}
					?>
					<div class="um-member-tagline um-member-tagline-<?php echo esc_attr( $key ); ?>" data-key="<?php echo esc_attr( $key ); ?>">
						<?php echo wp_kses( $member[ $key ], UM()->get_allowed_html( 'templates' ) ); ?>
					</div>
					<?php
				}
				?>
			</div>
			<?php
		}

		if ( $directory_data['show_userinfo'] ) {
			$show_block = false;
			if ( $directory_data['show_social'] && ! empty( $member['social_urls'] ) ) {
				$show_block = true;
			}

			foreach ( $directory_data['reveal_fields'] as $k => $key ) {
				if ( empty( $key ) ) {
					continue;
				}

				if ( empty( $member[ $key ] ) ) {
					continue;
				}

				$show_block = true;
				break;
			}

			if ( $show_block ) {
				?>
				<div class="um-member-meta<?php if ( ! $directory_data['userinfo_animate'] ) { echo ' um-member-meta-no-animate'; } else { echo ' um-toggle-block um-toggle-block-collapsed'; } ?>">
					<?php
					if ( $directory_data['userinfo_animate'] ) {
					?>
					<div class="um-member-meta-inner um-toggle-block-inner">
						<?php
						}
						foreach ( $directory_data['reveal_fields'] as $key ) {
							if ( empty( $member[ $key ] ) ) {
								continue;
							}
							?>
							<div class="um-member-metaline um-member-metaline-<?php echo esc_attr( $key ); ?>">
								<strong><?php echo esc_html( $member[ 'label_' . $key ] ); ?>:</strong> <?php echo wp_kses( $member[ $key ], UM()->get_allowed_html( 'templates' ) ); ?>
							</div>
							<?php
						}

						if ( $directory_data['show_social'] && ! empty( $member['social_urls'] ) ) {
							?>
							<div class="um-member-connect">
								<?php echo wp_kses( $member['social_urls'], UM()->get_allowed_html( 'templates' ) ); ?>
							</div>
						<?php }
						if ( $directory_data['userinfo_animate'] ) {
						?>
					</div>
				<?php
				} ?>
				</div>
				<?php
				if ( $directory_data['userinfo_animate'] ) {
					?>
					<a class="um-link um-meta-toggle" data-um-toggle=".um-member-meta" data-toggle-text="<?php esc_attr_e( 'Hide details', 'ultimate-member' ); ?>" href="#"><?php esc_html_e( 'More details', 'ultimate-member' ); ?></a>
					<?php
				}
			}
		}
		?>
	</div>

	<?php
	echo wp_kses(
		UM()->frontend()::layouts()::link(
			__( 'View Profile', 'ultimate-member' ),
			array(
				'size'   => 'm',
				'design' => 'primary',
				'type'   => 'button',
				'url'    => $member['profile_url'],
			)
		),
		UM()->get_allowed_html( 'templates' )
	);
	?>
</div>

<?php
$content = ob_get_clean();

echo wp_kses(
	UM()->frontend()::layouts()::box(
		$content,
		array(
			'id'      => $card_id,
			'classes' => $card_classes,
		)
	),
	UM()->get_allowed_html( 'templates' )
);
