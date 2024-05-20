<?php
/**
 * Template for the members directory list
 *
 * This template can be overridden by copying it to yourtheme/ultimate-member/members-list.php
 *
 * Page: "Members"
 *
 * @version 2.6.1
 *
 * @var string $unique_hash
 * @var array  $args
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

if ( ! empty( $members ) ) {
	foreach ( $members as $member ) {
		$t_args['member']      = $member;
		$t_args['unique_hash'] = $unique_hash;
		UM()->get_template( 'v3/directory/cell-list.php', '', $t_args, true );
	}
} else {
	?>
	<div class="um-members-none">
		<p><?php echo esc_html( $no_users ); ?></p>
	</div>
	<?php
}
