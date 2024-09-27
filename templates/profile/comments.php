<?php
/**
 * Template for the profile comments
 *
 * This template can be overridden by copying it to your-theme/ultimate-member/profile/comments.php
 *
 * Page: "Profile"
 * Call: function add_comments(), function load_comments()
 *
 * @version 2.6.1
 *
 * @var int    $count_comments
 * @var object $comments
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
	//Only for AJAX loading posts
	if ( ! empty( $comments ) ) {
		foreach ( $comments as $comment ) {
			UM()->get_template( 'profile/comments-single.php', '', array( 'comment' => $comment ), true );
		}
	}
} else {
	if ( ! empty( $comments ) ) { ?>
		<div class="um-ajax-items">

			<?php foreach ( $comments as $comment ) {
				UM()->get_template( 'profile/comments-single.php', '', array( 'comment' => $comment ), true );
			}

			if ( $count_comments > 10 ) { ?>
				<div class="um-load-items">
					<a href="javascript:void(0);" class="um-ajax-paginate um-button" data-hook="um_load_comments"
					   data-user_id="<?php echo esc_attr( um_get_requested_user() ); ?>" data-page="1"
					   data-pages="<?php echo esc_attr( ceil( $count_comments / 10 ) ); ?>">
						<?php _e( 'load more comments', 'ultimate-member' ); ?>
					</a>
				</div>
			<?php } ?>

		</div>

	<?php } else { ?>

		<div class="um-profile-note">
			<span>
				<?php if ( um_profile_id() == get_current_user_id() ) {
					_e( 'You have not made any comments.', 'ultimate-member' );
				} else {
					_e( 'This user has not made any comments.', 'ultimate-member' );
				} ?>
			</span>
		</div>

	<?php }
}
