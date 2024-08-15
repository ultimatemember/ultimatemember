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
		/**
		 * Filters classes of the User Profile navigation bar.
		 *
		 * Internal Ultimate Member callbacks (Priority -> Callback name -> Excerpt):
		 * 10 - `um_followers_profile_navbar_classes()` from Followers extension class.
		 * 10 - `profile_navbar_classes()` from Messaging extension class.
		 *
		 * @param {string} $classes User Profile navigation bar classes.
		 *
		 * @since 2.0
		 * @hook  um_profile_navbar_classes
		 *
		 * @example <caption>Adds `my_custom_class` class to the navigation bar on the User Profile page.</caption>
		 * function my_um_profile_navbar_classes( $classes ) {
		 *     // your code here
		 *     $classes .= 'my_custom_class';
		 *     echo $classes;
		 * }
		 * add_filter( 'um_profile_navbar_classes', 'my_um_profile_navbar_classes' );
		 */
		$classes = apply_filters( 'um_profile_navbar_classes', '' );
		?>
		<div class="um-profile-navbar <?php echo esc_attr( $classes ); ?>">
			<?php
			/**
			 * Fires for displaying User Profile navigation bar.
			 *
			 * Internal Ultimate Member callbacks (Priority -> Callback name -> Excerpt):
			 * 4 - `um_followers_add_profile_bar()` displays Followers button.
			 * 5 - `add_profile_bar()` displays Messaging button.
			 *
			 * @param {array} $args User Profile data.
			 *
			 * @since 1.3.x
			 * @hook  um_profile_navbar
			 *
			 * @example <caption>Display some content before or after User Profile navigation bar.</caption>
			 * function my_um_profile_navbar( $args ) {
			 *     // your code here
			 *     echo $content;
			 * }
			 * add_action( 'um_profile_navbar', 'my_um_profile_navbar' );
			 */
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
