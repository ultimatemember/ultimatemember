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
?>

<div class="um <?php echo esc_attr( $this->get_class( $mode ) ); ?> um-<?php echo esc_attr( $form_id ); ?> um-role-<?php echo esc_attr( um_user( 'role' ) ); ?> ">
	<?php
	/**
	 * Fires before User Profile header.
	 * It's the first hook at User Profile wrapper.
	 *
	 * Internal Ultimate Member callbacks (Priority -> Callback name -> Excerpt):
	 * 10 - `um_profile_completeness_show_notice()` displays Profile Completeness notices.
	 *
	 * @param {array} $args User Profile data.
	 *
	 * @since 1.3.x
	 * @hook  um_profile_before_header
	 *
	 * @example <caption>Display some content before User Profile.</caption>
	 * function my_um_profile_before_header( $args ) {
	 *     // your code here
	 *     echo $notice;
	 * }
	 * add_action( 'um_profile_before_header', 'my_um_profile_before_header' );
	 */
	do_action( 'um_profile_before_header', $args );
	/**
	 * Fires for displaying User Profile cover area.
	 *
	 * Internal Ultimate Member callbacks (Priority -> Callback name -> Excerpt):
	 * 9 - `um_profile_header_cover_area()` displays User Profile cover photo.
	 *
	 * @param {array} $args User Profile data.
	 *
	 * @since 1.3.x
	 * @hook  um_profile_header_cover_area
	 *
	 * @example <caption>Display some content before or after User Profile cover.</caption>
	 * function my_um_profile_header_cover_area( $args ) {
	 *     // your code here
	 *     echo $content;
	 * }
	 * add_action( 'um_profile_header_cover_area', 'my_um_profile_header_cover_area' );
	 */
	do_action( 'um_profile_header_cover_area', $args );
	/**
	 * Fires for displaying User Profile header.
	 *
	 * Internal Ultimate Member callbacks (Priority -> Callback name -> Excerpt):
	 * 9 - `um_profile_header()` displays User Profile header.
	 *
	 * @param {array} $args User Profile data.
	 *
	 * @since 1.3.x
	 * @hook  um_profile_header
	 *
	 * @example <caption>Display some content before or after User Profile header.</caption>
	 * function my_um_profile_header( $args ) {
	 *     // your code here
	 *     echo $content;
	 * }
	 * add_action( 'um_profile_header', 'my_um_profile_header' );
	 */
	do_action( 'um_profile_header', $args );

	// @todo find the proper place for "um-profile-navbar" block. It's removed for now but there is displayed followers and messages buttons.

	if ( um_is_on_edit_profile() || UM()->user()->preview ) {
		$nav    = 'main';
		$subnav = UM()->profile()->active_subnav();
		$subnav = ! empty( $subnav ) ? $subnav : 'default';
		?>

		<div class="um-profile-body <?php echo esc_attr( $nav . ' ' . $nav . '-' . $subnav ); ?>">
			<?php
			if ( ! UM()->user()->preview ) {
				?>
				<form method="post" action="" class="um-form-new">
				<?php
			}
			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_profile_content_{$nav}
			 * @description Custom hook to display tabbed content
			 * @input_vars
			 * [{"var":"$args","type":"array","desc":"Profile form shortcode arguments"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_profile_content_{$nav}', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_action( 'um_profile_content_{$nav}', 'my_profile_content', 10, 1 );
			 * function my_profile_content( $args ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( "um_profile_content_{$nav}", $args );

			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_profile_content_{$nav}_{$subnav}
			 * @description Custom hook to display tabbed content
			 * @input_vars
			 * [{"var":"$args","type":"array","desc":"Profile form shortcode arguments"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_profile_content_{$nav}_{$subnav}', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_action( 'um_profile_content_{$nav}_{$subnav}', 'my_profile_content', 10, 1 );
			 * function my_profile_content( $args ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( "um_profile_content_{$nav}_{$subnav}", $args );
			if ( ! UM()->user()->preview ) {
				?>
				</form>
				<?php
			}
			?>
		</div>
		<?php
	} else {
		// @todo show profile menu only on the view profile mode. Need to clarify if we need it on the profile edit or preview.
		/**
		 * Fires for displaying User Profile menu.
		 *
		 * Internal Ultimate Member callbacks (Priority -> Callback name -> Excerpt):
		 * 9 - `um_profile_menu()` displays User Profile menu.
		 *
		 * @param {array} $args User Profile data.
		 *
		 * @since 2.0
		 * @hook  um_profile_menu
		 *
		 * @example <caption>Display some content before or after User Profile menu.</caption>
		 * function my_um_profile_menu( $args ) {
		 *     // your code here
		 *     echo $content;
		 * }
		 * add_action( 'um_profile_menu', 'my_um_profile_menu' );
		 */
		do_action( 'um_profile_menu', $args );

		$menu_enabled = UM()->options()->get( 'profile_menu' );
		$tabs         = UM()->profile()->tabs_active();

		$nav    = UM()->profile()->active_tab();
		$subnav = UM()->profile()->active_subnav();
		$subnav = ! empty( $subnav ) ? $subnav : 'default';

		if ( $menu_enabled || ! empty( $tabs[ $nav ]['hidden'] ) ) {
			?>
			<div class="um-profile-body <?php echo esc_attr( $nav . ' ' . $nav . '-' . $subnav ); ?>">

				<?php
				// Custom hook to display tabbed content
				/**
				 * UM hook
				 *
				 * @type action
				 * @title um_profile_content_{$nav}
				 * @description Custom hook to display tabbed content
				 * @input_vars
				 * [{"var":"$args","type":"array","desc":"Profile form shortcode arguments"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_action( 'um_profile_content_{$nav}', 'function_name', 10, 1 );
				 * @example
				 * <?php
				 * add_action( 'um_profile_content_{$nav}', 'my_profile_content', 10, 1 );
				 * function my_profile_content( $args ) {
				 *     // your code here
				 * }
				 * ?>
				 */
				do_action( "um_profile_content_{$nav}", $args );

				/**
				 * UM hook
				 *
				 * @type action
				 * @title um_profile_content_{$nav}_{$subnav}
				 * @description Custom hook to display tabbed content
				 * @input_vars
				 * [{"var":"$args","type":"array","desc":"Profile form shortcode arguments"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_action( 'um_profile_content_{$nav}_{$subnav}', 'function_name', 10, 1 );
				 * @example
				 * <?php
				 * add_action( 'um_profile_content_{$nav}_{$subnav}', 'my_profile_content', 10, 1 );
				 * function my_profile_content( $args ) {
				 *     // your code here
				 * }
				 * ?>
				 */
				do_action( "um_profile_content_{$nav}_{$subnav}", $args );
				?>
				<div class="clear"></div>
			</div>
			<?php
		}
	}

	/**
	 * Fires for adding content below User Profile menu.
	 *
	 * Internal Ultimate Member callbacks (Priority -> Callback name -> Excerpt):
	 * 99 - `profile_footer_login_form()` displays User Login form related to messaging extension.
	 *
	 * @param {array} $args User Profile data.
	 *
	 * @since 2.0
	 * @hook  um_profile_footer
	 *
	 * @example <caption>Display some content in User Profile footer.</caption>
	 * function my_um_profile_footer( $args ) {
	 *     // your code here
	 *     echo $content;
	 * }
	 * add_action( 'um_profile_footer', 'my_um_profile_footer' );
	 */
	do_action( 'um_profile_footer', $args );
	?>
</div>
