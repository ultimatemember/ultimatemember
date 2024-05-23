<?php
/**
 * Template for the members directory grid
 *
 * This template can be overridden by copying it to yourtheme/ultimate-member/members-grid.php
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
}

if ( ! $directory_data['profile_photo'] ) {
	$card_classes[] = 'um-member-no-photo';
}

ob_start();

if ( $directory_data['cover_photos'] && $member['cover_photo'] ) {
	if ( $directory_data['profile_photo'] ) {
		?>
		<div class="um-member-photos">
		<?php
	}
	?>
	<div class="um-member-cover">
		<?php echo wp_kses( $member['cover_photo'], UM()->get_allowed_html( 'templates' ) ); ?>
	</div>
	<?php
	if ( $directory_data['profile_photo'] ) {
		echo wp_kses( UM()->frontend()::layouts()::single_avatar( $member['id'], array( 'size' => 'xl' ) ), UM()->get_allowed_html( 'templates' ) );
		do_action( 'um_members_in_profile_photo_tmpl', $t_args );
		?>
		</div>
		<?php
	}
}

echo wp_kses(
	UM()->frontend()::layouts()::dropdown_menu(
		'um-member-actions-toggle',
		$member['dropdown_actions'],
		array( 'width' => 210 )
	),
	UM()->get_allowed_html( 'templates' )
);

?>
<div class="um-member-box-main">
	<?php
	if ( ! ( $directory_data['cover_photos'] && $member['cover_photo'] ) && $directory_data['profile_photo'] ) {
		echo wp_kses( UM()->frontend()::layouts()::single_avatar( $member['id'], array( 'size' => 'xl' ) ), UM()->get_allowed_html( 'templates' ) );
		do_action( 'um_members_in_profile_photo_tmpl', $t_args );
	}

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
		);
		if ( 'awaiting_admin_review' === $member['account_status'] ) {
			$status_badge = array(
				'color' => 'error',
			);
		}
		echo wp_kses( UM()->frontend()::layouts()::badge( $member['account_status_name'], $status_badge ), UM()->get_allowed_html( 'templates' ) );
	}

	// {{{user.hook_just_after_name}}}
	do_action( 'um_members_just_after_name', $member['id'], $directory_data );
	// {{{user.hook_after_user_name}}}
	do_action( 'um_members_after_user_name', $member['id'], $directory_data );

	if ( $directory_data['show_tagline'] && ! empty( $directory_data['tagline_fields'] ) && is_array( $directory_data['tagline_fields'] ) ) {
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
			<div class="um-member-meta <?php if ( ! $directory_data['userinfo_animate'] ) { echo 'um-member-meta-no-animate'; } ?>">
				<?php
				if ( $directory_data['userinfo_animate'] ) {
					?>
					<div class="um-member-meta-inner">
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
				<a class="um-link um-meta-toggle" data-toggle-text="<?php esc_attr_e( 'Hide details', 'ultimate-member' ); ?>" href="#"><?php esc_html_e( 'More details', 'ultimate-member' ); ?></a>
				<?php
			}
		}
	}

	echo wp_kses(
		UM()->frontend()::layouts()::button(
			__( 'View Profile', 'ultimate-member' ),
			array(
				'size'   => 'm',
				'design' => 'primary',
				'type'   => 'link',
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
