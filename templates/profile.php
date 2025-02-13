<?php
/**
 * Template for the profile page
 *
 * This template can be overridden by copying it to your-theme/ultimate-member/templates/profile.php
 *
 * Page: "Profile"
 *
 * @version 2.10.0
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
<div class="um <?php echo esc_attr( $this->get_class( $mode ) ); ?> um-<?php echo esc_attr( $form_id ); ?> um-role-<?php echo esc_attr( um_user( 'role' ) ); ?>">
	<div class="um-form" data-mode="<?php echo esc_attr( $mode ); ?>" data-form_id="<?php echo esc_attr( $form_id ); ?>">
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

		if ( um_is_on_edit_profile() ) {
			?>
			<form method="post" action="" data-description_key="<?php echo esc_attr( $description_key ); ?>">
			<?php
		}
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
		 * @return {string} User Profile navigation bar classes.
		 *
		 * @example <caption>Adds `my_custom_class` class to the navigation bar on the User Profile page.</caption>
		 * function my_um_profile_navbar_classes( $classes ) {
		 *     // your code here
		 *     $classes .= ' my_custom_class';
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

		if ( um_is_on_edit_profile() || UM()->user()->preview ) {
			$nav    = 'main';
			$subnav = UM()->profile()->active_subnav();
			$subnav = ! empty( $subnav ) ? $subnav : 'default';
			?>
			<div class="um-profile-body <?php echo esc_attr( $nav . ' ' . $nav . '-' . $subnav ); ?>">
				<?php
				/**
				 * Fires for adding content in User Profile nav menu content tab.
				 * $nav profile menu tab key.
				 *
				 * Internal Ultimate Member callbacks (Priority -> Callback name -> Excerpt):
				 * 10 - `um_profile_content_main()` displays `About` menu tab's content (v2).
				 * 10 - `UM()->frontend()->profile()->about()` displays `About` menu tab's content (v3).
				 * 10 - `um_profile_content_followers()` displays `Followers` menu tab's content.
				 * 10 - `um_profile_content_following()` displays `Following` menu tab's content.
				 * 10 - `um_profile_content_groups_list()` displays `User Groups List` menu tab's content.
				 * 10 - `profile_tab_content()` displays `My Jobs` menu tab's content.
				 * 10 - `profile_tab_dashboard_content()` displays `My Jobs Dashboard` menu tab's content.
				 * 10 - `content_messages()` displays `Messages` menu tab's content.
				 * 10 - `um_profile_content_private_content()` displays `Private Content` menu tab's content.
				 * 10 - `um_profile_content_{$tab['tabid']}` displays custom Profile Tabs content.
				 * 10 - `um_profile_content_reviews()` displays `Reviews` menu tab's content.
				 * 10 - `show_wall()` displays `Activity` menu tab's content.
				 *
				 * @param {array} $args User Profile data.
				 *
				 * @since 1.3.x
				 * @hook  um_profile_content_{$nav}
				 *
				 * @example <caption>Display some content in User Profile `main` nav content tab.</caption>
				 * function my_profile_content_main( $args ) {
				 *     // your code here
				 *     echo $content;
				 * }
				 * add_action( 'um_profile_content_main', 'my_profile_content_main' );
				 */
				do_action( "um_profile_content_$nav", $args );
				/**
				 * Fires for adding content in User Profile nav > subnav menu content tab.
				 * $nav profile menu tab key.
				 * $subnav profile sub-menu tab key.
				 *
				 * Internal Ultimate Member callbacks (Priority -> Callback name -> Excerpt):
				 * 10 - `um_bbpress_user_topics()` displays `Topics` submenu of bbPress tab's content.
				 * 10 - `um_bbpress_user_replies()` displays `Replies` submenu of bbPress tab's content.
				 * 10 - `um_bbpress_user_favorites()` displays `Favorites` submenu of bbPress tab's content.
				 * 10 - `um_bbpress_user_subscriptions()` displays `Subscriptions` submenu of bbPress tab's content.
				 * 10 - `profile_content_forums_topics()` displays `Topics` submenu of ForumWP tab's content.
				 * 10 - `profile_content_forums_replies()` displays `Replies` submenu of ForumWP tab's content.
				 * 10 - `profile_content_subscriptions()` displays `Subscriptions` submenu of ForumWP tab's content.
				 * 10 - `profile_content_bookmarks()` displays `Bookmarks` submenu of ForumWP tab's content.
				 * 10 - `profile_content_likes()` displays `Likes` submenu of ForumWP tab's content.
				 * 10 - `um_profile_content_friends_myfriends()` displays `My Friends` submenu of Friends tab's content.
				 * 10 - `um_profile_content_friends_friendreqs()` displays `Friends Requests` submenu of Friends tab's content.
				 * 10 - `um_profile_content_friends_sentreqs()` displays `Send Friends Requests` submenu of Friends tab's content.
				 * 10 - `um_profile_content_badges_my_badges()` displays `My badges` submenu of myCRED tab's content.
				 * 10 - `um_profile_content_badges_all_badges()` displays `All badges` submenu of myCRED tab's content.
				 * 10 - `get_bookmarks_content()` displays `All bookmarks with folders` submenu of User Bookmarks tab's content.
				 * 10 - `get_bookmarks_content_all()` displays `All bookmarks` submenu of User Bookmarks tab's content.
				 * 10 - `get_bookmarks_content_users()` displays `All bookmarked users` submenu of User Bookmarks tab's content.
				 * 10 - `user_notes_profile_tab()` displays `View Notes` submenu of User Notes tab's content.
				 * 10 - `get_add_note_form()` displays `Add Note` submenu of User Notes tab's content.
				 * 10 - `get_gallery_content()` displays `Albums` submenu of User Photos tab's content.
				 * 10 - `get_gallery_photos_content()` displays `Photos` submenu of User Photos tab's content.
				 * 10 - `um_profile_content_purchases()` displays `Purchases` submenu of Woocommerce tab's content.
				 * 10 - `um_profile_content_product_reviews()` displays `Product Reviews` submenu of Woocommerce tab's content.
				 *
				 * @param {array} $args User Profile data.
				 *
				 * @since 1.3.x
				 * @hook  um_profile_content_{$nav}_{$subnav}
				 *
				 * @example <caption>Display some content in User Profile `main` nav and `step2` subnav content tab.</caption>
				 * function my_profile_content_main_step2( $args ) {
				 *     // your code here
				 *     echo $content;
				 * }
				 * add_action( 'um_profile_content_main_step2', 'my_profile_content_main_step2' );
				 */
				do_action( "um_profile_content_{$nav}_$subnav", $args );
				?>
				<div class="clear"></div>
			</div>

			<?php if ( ! UM()->user()->preview ) { ?>

			</form>

				<?php
			}
		} else {
			$menu_enabled = UM()->options()->get( 'profile_menu' );
			$profile_tabs = UM()->profile()->tabs_active();

			$nav    = UM()->profile()->active_tab();
			$subnav = UM()->profile()->active_subnav();
			$subnav = ! empty( $subnav ) ? $subnav : 'default';

			if ( $menu_enabled || ! empty( $profile_tabs[ $nav ]['hidden'] ) ) {
				?>
				<div class="um-profile-body <?php echo esc_attr( $nav . ' ' . $nav . '-' . $subnav ); ?>">

					<?php
					// Custom hook to display tabbed content
					/** This action is documented in ultimate-member/templates/profile.php */
					do_action( "um_profile_content_$nav", $args );
					/** This action is documented in ultimate-member/templates/profile.php */
					do_action( "um_profile_content_{$nav}_$subnav", $args );
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
</div>
