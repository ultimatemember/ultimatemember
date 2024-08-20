<?php
/**
 * Template for the profile page
 *
 * This template can be overridden by copying it to yourtheme/ultimate-member/templates/profile.php
 *
 * Page: "Profile"
 *
 * @version 2.6.9
 *
 * @var string $mode
 * @var int    $form_id
 * @var array  $args
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$description_key = UM()->profile()->get_show_bio_key( $args );
?>

<div class="um <?php echo esc_attr( $this->get_class( $mode ) ); ?> um-<?php echo esc_attr( $form_id ); ?> um-role-<?php echo esc_attr( um_user( 'role' ) ); ?> ">
	<div class="um-form" data-mode="<?php echo esc_attr( $mode ); ?>">
		<?php
		/** This action is documented in ultimate-member/templates/v3/profile.php */
		do_action( 'um_profile_before_header', $args );

		if ( um_is_on_edit_profile() ) {
			?>
			<form method="post" action="" data-description_key="<?php echo esc_attr( $description_key ); ?>">
			<?php
		}
		/** This action is documented in ultimate-member/templates/v3/profile.php */
		do_action( 'um_profile_header_cover_area', $args );
		/** This action is documented in ultimate-member/templates/v3/profile.php */
		do_action( 'um_profile_header', $args );
		/** This filter is documented in ultimate-member/includes/frontend/class-profile.php */
		$classes = apply_filters( 'um_profile_navbar_classes', '' );
		?>
		<div class="um-profile-navbar <?php echo esc_attr( $classes ); ?>">
			<?php
			/** This action is documented in ultimate-member/templates/v3/profile.php */
			do_action( 'um_profile_navbar', $args );
			?>
			<div class="um-clear"></div>
		</div>
		<?php
		/** This action is documented in ultimate-member/templates/v3/profile.php */
		do_action( 'um_profile_menu', $args );

		if ( um_is_on_edit_profile() || UM()->user()->preview ) {

			$nav = 'main';
			$subnav = UM()->profile()->active_subnav();
			$subnav = ! empty( $subnav ) ? $subnav : 'default'; ?>

			<div class="um-profile-body <?php echo esc_attr( $nav . ' ' . $nav . '-' . $subnav ); ?>">
				<?php
				/** This action is documented in ultimate-member/templates/v3/profile.php */
				do_action("um_profile_content_{$nav}", $args);
				/** This action is documented in ultimate-member/templates/v3/profile.php */
				do_action( "um_profile_content_{$nav}_{$subnav}", $args );
				?>
				<div class="clear"></div>
			</div>

			<?php if ( ! UM()->user()->preview ) { ?>

			</form>

			<?php }
		} else {
			$menu_enabled = UM()->options()->get( 'profile_menu' );
			$tabs = UM()->profile()->tabs_active();

			$nav = UM()->profile()->active_tab();
			$subnav = UM()->profile()->active_subnav();
			$subnav = ! empty( $subnav ) ? $subnav : 'default';

			if ( $menu_enabled || ! empty( $tabs[ $nav ]['hidden'] ) ) {
				?>
				<div class="um-profile-body <?php echo esc_attr( $nav . ' ' . $nav . '-' . $subnav ); ?>">
					<?php
					/** This action is documented in ultimate-member/templates/v3/profile.php */
					do_action( "um_profile_content_{$nav}", $args );
					/** This action is documented in ultimate-member/templates/v3/profile.php */
					do_action( "um_profile_content_{$nav}_{$subnav}", $args );
					?>
					<div class="clear"></div>
				</div>
				<?php
			}
		}
		/** This action is documented in ultimate-member/templates/v3/profile.php */
		do_action( 'um_profile_footer', $args );
		?>
	</div>
</div>
