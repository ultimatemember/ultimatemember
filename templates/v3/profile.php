<?php
/**
 * Template for the profile page
 *
 * This template can be overridden by copying it to your-theme/ultimate-member/templates/profile.php
 *
 * Page: "Profile"
 *
 * @version 3.0.0
 *
 * @var string $mode
 * @var int    $form_id
 * @var array  $args
 * @var array  $wrapper_classes
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>">
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

	$nav = null;

	// @todo find the proper place for "um-profile-navbar" block. It's removed for now but there is displayed followers and messages buttons.
	$content_wrapper_classes = array( 'um-profile-body' );
	if ( um_is_on_edit_profile() || UM()->user()->preview ) {
		ob_start();
		/** This action is documented in ultimate-member/templates/v3/profile.php */
		do_action( 'um_profile_content_main', $args );
		$content = ob_get_clean();

		$content_wrapper_classes[] = 'um-profile-main';
	} else {
		// @todo show profile navbar and menu only on the view profile mode. Need to clarify if we need it on the profile edit or preview.
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

		$tabs = UM()->profile()->tabs_active();
		$nav  = UM()->profile()->active_tab();

		ob_start();

		if ( array_key_exists( $nav, $tabs ) ) {
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
			do_action( "um_profile_content_{$nav}", $args );

			$content_wrapper_classes[] = 'um-profile-' . $nav;

			$subnav = null;
			if ( array_key_exists( 'subnav', $tabs[ $nav ] ) ) {
				$default_subnav = array_key_exists( 'subnav_default', $tabs[ $nav ] ) ? $tabs[ $nav ]['subnav_default'] : array_keys( $tabs[ $nav ]['subnav'] )[0];
				$subnav         = UM()->profile()->active_subnav() ? UM()->profile()->active_subnav() : $default_subnav;
			}
			if ( $subnav && array_key_exists( $subnav, $tabs[ $nav ]['subnav'] ) ) {
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
				do_action( "um_profile_content_{$nav}_{$subnav}", $args );

				$content_wrapper_classes[] = 'um-profile-' . $nav . '-' . $subnav;
			}
		}

		$content = ob_get_clean();
	}
	if ( ! empty( $content ) ) {
		/**
		 * Filters User Profile Body wrapper classes.
		 *
		 * @param {array}  $classes User Profile body classes.
		 * @param {array}  $args    User Profile data.
		 * @param {string} $nav     Profile menu slug.
		 *
		 * @since 3.0
		 * @hook  um_profile_body_wrapper_classes
		 *
		 * @example <caption>Extends profile body classes.</caption>
		 * function my_profile_body_wrapper_classes( $classes, $args, $nav ) {
		 *     // your code here
		 *     $classes[] = 'my-class';
		 *     return $classes;
		 * }
		 * add_action( 'um_profile_body_wrapper_classes', 'my_profile_body_wrapper_classes', 10, 2 );
		 */
		$content_wrapper_classes = apply_filters( 'um_profile_body_wrapper_classes', $content_wrapper_classes, $args, $nav );
		?>
		<div class="<?php echo esc_attr( implode( ' ', $content_wrapper_classes ) ); ?>">
			<?php echo wp_kses( $content, UM()->get_allowed_html( 'templates' ) ); ?>
		</div>
		<?php
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
