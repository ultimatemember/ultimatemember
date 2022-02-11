<?php
/**
 * Template for the UM Online Users.
 * Used for "Ultimate Member - Online Users" widget.
 *
 * Caller: method Online_Shortcode->ultimatemember_online()
 * Shortcode: [ultimatemember_online]
 *
 * This template can be overridden by copying it to yourtheme/ultimate-member/um-online/online.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} ?>

<div class="um-online" data-max="<?php echo $max; ?>">
	<?php
	$online = array_keys( $online );
	foreach ( $online as $user_id ) {
		$user_meta  = get_userdata( $user_id );
		$user_roles = $user_meta->roles;
		if ( 'all' !== $roles && count( array_intersect( $user_roles, explode( ',', $roles ) ) ) <= 0 ) {
			continue;
		}

		$name = $user_meta->display_name;
		if ( empty( $name ) ) {
			continue;
		} ?>

		<div class="um-online-user">
			<div class="um-online-pic">
				<a href="<?php echo esc_url( um_user_profile_url( $user_id ) ); ?>" class="um-tip-n" title="<?php echo esc_attr( $name ); ?>">
					<?php echo get_avatar( $user_id, 40 ); ?>
				</a>
			</div>
		</div>

	<?php } ?>

	<div class="um-clear"></div>
</div>
