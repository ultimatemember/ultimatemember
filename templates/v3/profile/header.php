<?php
/**
 * Template for the header of profile page
 *
 * This template can be overridden by copying it to yourtheme/ultimate-member/templates/v3/profile/header.php
 *
 * Page: "Profile"
 *
 * @version 2.9.0
 *
 * @var int    $current_user_id
 * @var int    $user_profile_id
 * @var string $display_name
 * @var bool   $show_display_name
 * @var string $account_status
 * @var string $social_links
 * @var bool   $show_bio
 * @var string $user_bio Already escaped based on the setting user's bio.
 * @var array  $wrapper_classes
 * @var array  $profile_args
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$actions = '';
if ( is_user_logged_in() && UM()->roles()->um_current_user_can( 'edit', $user_profile_id ) ) {
	if ( true === UM()->fields()->editing ) {
		$submit   = UM()->frontend()::layouts()::button(
			__( 'Save', 'ultimate-member' ),
			array(
				'design'  => 'primary',
				'type'    => 'submit',
				'size'    => 's',
				'classes' => array(
					'um-profile-save',
				),
			)
		);
		$cancel   = UM()->frontend()::layouts()::link(
			__( 'Cancel', 'ultimate-member' ),
			array(
				'type'    => 'button',
				'size'    => 's',
				'url'     => um_user_profile_url( $user_profile_id ),
				'classes' => array(
					'um-profile-form-cancel',
				),
			)
		);
		$actions .= $cancel . $submit;
	} else {
		$actions .= UM()->frontend()::layouts()::link(
			__( 'Edit Profile', 'ultimate-member' ),
			array(
				'design'  => 'primary',
				'type'    => 'button',
				'size'    => 's',
				'url'     => um_edit_profile_url( $user_profile_id ),
				'classes' => array(
					'um-profile-edit-link',
				),
			)
		);
	}
}

if ( true !== UM()->fields()->editing ) {
	$dropdown_actions = UM()->user()->get_dropdown_items( $user_profile_id );
	if ( ! empty( $dropdown_actions ) ) {
		$actions .= UM()->frontend()::layouts()::dropdown_menu(
			'um-profile-actions-toggle',
			$dropdown_actions,
			array(
				'type'         => 'button',
				'button_label' => __( 'More actions', 'ultimate-member' ),
				'width'        => 210,
			)
		);
	}
}
// Reason for deprecate these hooks is unified dropdown with the actions. please use new one:
// apply_filters( 'um_user_dropdown_items', $items, $user_id, $context );
//@todo apply_filters( 'um_profile_edit_menu_items', $items, um_profile_id() ); hook is deprecated for new UI.
//@todo apply_filters( 'um_myprofile_edit_menu_items', $items ); hook is deprecated for new UI.

// Reason for deprecate these hooks is not editable profile photo in the profile header.
// Maybe we will use them on the avatar edit field.
//@todo apply_filters( 'um_user_photo_menu_view', $items ); hook is deprecated for new UI. and maybe will be added soon.
//@todo apply_filters( 'um_user_photo_menu_edit', $items ); hook is deprecated for new UI. and maybe will be added soon.
?>
<div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>">
	<?php
	/**
	 * Fires for displaying content in header wrapper on User Profile.
	 *
	 * Internal Ultimate Member callbacks (Priority -> Callback name -> Excerpt):
	 * 10 - `um_add_edit_icon()` displays User Profile edit button.
	 *
	 * @param {array} $args    User Profile data.
	 * @param {int}   $user_id User Profile ID. Since 2.9.0.
	 *
	 * @since 1.3.x
	 * @since 2.9.0 added $user_id attribute
	 * @hook  um_pre_header_editprofile
	 *
	 * @example <caption>Display some content in User Profile header wrapper.</caption>
	 * function my_um_pre_header_editprofile( $args, $user_id ) {
	 *     // your code here
	 *     echo $content;
	 * }
	 * add_action( 'um_pre_header_editprofile', 'my_um_pre_header_editprofile', 10, 2 );
	 */
	do_action( 'um_pre_header_editprofile', $profile_args, $user_profile_id );
	?>
	<div class="um-profile-header-core">
		<?php
		// Don't remove um-profile-header-core wrapper if you have 3rd-party integrations via `pre` and `after` header hooks.
		echo wp_kses(
			UM()->frontend()::layouts()::single_avatar(
				$user_profile_id,
				array(
					'size'        => 'xl',
					'type'        => 'round',
					'ignore_caps' => true, // ignore caps because we display in profile and caps checked on lower level
				)
			),
			UM()->get_allowed_html( 'templates' )
		);
		?>
		<div class="um-profile-header-content">
			<?php
			/**
			 * Fires for displaying content in header content wrapper on User Profile.
			 *
			 * Internal Ultimate Member callbacks (Priority -> Callback name -> Excerpt):
			 * 10 - `um_friends_add_button()` displays Add Friend button.
			 *
			 * @param {array} $args    User Profile data.
			 * @param {int}   $user_id User Profile ID. Since 2.9.0.
			 *
			 * @since 2.0
			 * @since 2.9.0 added $user_id attribute
			 * @hook  um_before_profile_main_meta
			 *
			 * @example <caption>Display some content in User Profile header content wrapper.</caption>
			 * function my_um_before_profile_main_meta( $args, $user_id ) {
			 *     // your code here
			 *     echo $content;
			 * }
			 * add_action( 'um_before_profile_main_meta', 'my_um_before_profile_main_meta', 10, 2 );
			 */
			do_action( 'um_before_profile_main_meta', $profile_args, $user_profile_id );

			if ( $profile_args['show_name'] || ! empty( $actions ) ) {
				$row_classes = array( 'um-profile-header-main-row' );
				if ( empty( $profile_args['show_name'] ) ) {
					$row_classes[] = 'um-profile-header-no-name';
				}
				if ( empty( $actions ) ) {
					$row_classes[] = 'um-profile-header-no-actions';
				}
				?>
				<div class="<?php echo esc_attr( implode( ' ', $row_classes ) ); ?>">
					<?php if ( $profile_args['show_name'] ) { ?>
						<div class="um-profile-display-name-wrapper">
							<h2 class="um-profile-display-name">
								<?php
								// Construction `um_user( 'display_name', 'html' )` is used for displaying verified marker just after display name.
								echo wp_kses( um_user( 'display_name', 'html' ), UM()->get_allowed_html( 'templates' ) );
								?>
							</h2>
							<?php
							/**
							 * Fires just after user display name in header on User Profile.
							 *
							 * Internal Ultimate Member callbacks (Priority -> Callback name -> Excerpt):
							 * 10  - `um_online_show_user_status()` displays Online status.
							 * 20  - `um_mc_after_profile_name_inline()` displays MailChimp marker.
							 * 200 - `um_friends_add_state()` displays Friendship state.
							 * 200 - `um_followers_add_state()` displays Followers state.
							 *
							 * @param {array} $args    User Profile data.
							 * @param {int}   $user_id User Profile ID. Since 2.9.0.
							 *
							 * @since 1.3.x
							 * @since 2.9.0 added $user_id attribute
							 * @hook  um_after_profile_name_inline
							 *
							 * @example <caption>Display some content after display name in User Profile header.</caption>
							 * function my_um_after_profile_name_inline( $args, $user_id ) {
							 *     // your code here
							 *     echo $content;
							 * }
							 * add_action( 'um_after_profile_name_inline', 'my_um_after_profile_name_inline', 10, 2 );
							 */
							do_action( 'um_after_profile_name_inline', $profile_args, $user_profile_id );
							?>
						</div>
					<?php } ?>
					<?php if ( ! empty( $actions ) ) { ?>
						<div class="um-profile-header-actions">
							<?php echo wp_kses( $actions, UM()->get_allowed_html( 'templates' ) ); ?>
						</div>
					<?php } ?>
				</div>
				<?php
			}
			?>
			<div class="um-profile-header-supporting-rows">
				<?php
				if ( 'approved' !== $account_status ) {
					$status_badge = array(
						'class' => array( 'um-member-status' ),
						'color' => 'error',
					);
					if ( 'awaiting_admin_review' === $account_status ) {
						$status_badge['color'] = 'warning';
					}
					// translators: %s: profile status.
					$badge_text = sprintf( __( 'This user account status is %s', 'ultimate-member' ), um_user( 'account_status_name' ) );
					?>
					<div class="um-profile-header-account-status-row">
						<?php echo wp_kses( UM()->frontend()::layouts()::badge( $badge_text, $status_badge ), UM()->get_allowed_html( 'templates' ) ); ?>
					</div>
					<?php
				}

				if ( ! empty( $social_links ) ) {
					?>
					<div class="um-profile-header-social-row">
						<?php echo wp_kses( $social_links, UM()->get_allowed_html( 'templates' ) ); ?>
					</div>
					<?php
				}

				/**
				 * Fires for displaying content in supporting header row on User Profile.
				 *
				 * Internal Ultimate Member callbacks (Priority -> Callback name -> Excerpt):
				 * 10 - `add_um_user_bookmarks_button_profile_nocover()` displays User Bookmarks button.
				 * 50 - `um_social_links_icons()` displays social URLs.
				 * 60 - `um_friends_add_button_nocover()` displays Friends buttons.
				 * 70 - `um_mycred_show_user_badges_profile_header()` displays myCRED badges.
				 *
				 * @param {array} $args    User Profile data. Since 2.9.0.
				 * @param {int}   $user_id User Profile ID. Since 2.9.0.
				 *
				 * @since 1.3.x
				 * @since 2.9.0 added $profile_args, $user_id attributes
				 * @hook  um_after_profile_header_name
				 *
				 * @example <caption>Display some content in supporting header row on User Profile.</caption>
				 * function my_um_after_profile_header_name( $args, $user_id ) {
				 *     // your code here
				 *     echo $content;
				 * }
				 * add_action( 'um_after_profile_header_name', 'my_um_after_profile_header_name', 10, 2 );
				 */
				do_action( 'um_after_profile_header_name', $profile_args, $user_profile_id );

				if ( ! empty( $profile_args['metafields'] ) ) {
					?>
					<div class="um-profile-header-supporting-row">
						<?php echo wp_kses( UM()->profile()->show_meta( $profile_args['metafields'], $profile_args ), UM()->get_allowed_html( 'templates' ) ); ?>
					</div>
					<?php
				}

				if ( $show_bio ) {
					?>
					<div class="um-profile-header-supporting-row">
						<?php
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped  -- early escaped variable because need different escape based on options.
						echo $user_bio;
						?>
					</div>
					<?php
				}
				?>
			</div>
			<?php
			/**
			 * Fires for displaying content at the end of supporting row in header wrapper on User Profile.
			 *
			 * @param {array} $args    User Profile data.
			 * @param {int}   $user_id User Profile ID.
			 *
			 * @since 1.3.x
			 * @since 2.9.0 Changed the arguments position. $user_id was the 1st, and it's the 2nd now.
			 * @hook  um_after_header_meta
			 *
			 * @example <caption>Display some content at the end of supporting row in User Profile header wrapper after standard content.</caption>
			 * function my_um_after_header_meta( $args, $user_id ) {
			 *     // your code here
			 *     echo $content;
			 * }
			 * add_action( 'um_after_header_meta', 'my_um_after_header_meta', 10, 2 );
			 */
			do_action( 'um_after_header_meta', $profile_args, $user_profile_id );
			?>
		</div>
	</div>
	<?php
	/**
	 * Fires for displaying content at the end of header wrapper on User Profile.
	 *
	 * @param {array} $args    User Profile data.
	 * @param {int}   $user_id User Profile ID.
	 *
	 * @since 1.3.x
	 * @hook  um_after_header_info
	 *
	 * @example <caption>Display some content in User Profile header wrapper after standard content.</caption>
	 * function my_um_after_header_info( $args, $user_id ) {
	 *     // your code here
	 *     echo $content;
	 * }
	 * add_action( 'um_after_header_info', 'my_um_after_header_info', 10, 2 );
	 */
	do_action( 'um_after_header_info', $profile_args, $user_profile_id );
	?>
</div>
