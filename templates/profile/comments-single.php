<?php
/**
 * Template for the profile single comments
 *
 * This template can be overridden by copying it to your-theme/ultimate-member/profile/comments-single.php
 *
 * Page: "Profile"
 *
 * @version 2.6.1
 *
 * @var object $comment
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$comment_title = apply_filters( 'um_user_profile_comment_title', get_the_title( $comment->comment_post_ID ), $comment );
$link = apply_filters( 'um_user_profile_comment_url', get_permalink( $comment->comment_post_ID ), $comment ); ?>

<div class="um-item">
	<div class="um-item-link">
		<i class="um-icon-chatboxes"></i>
		<a href="<?php echo esc_url( get_comment_link( $comment->comment_ID ) ); ?>">
			<?php echo get_comment_excerpt( $comment->comment_ID ); ?>
		</a>
	</div>
	<div class="um-item-meta">
		<span>
			<?php
			// translators: %1$s is a link; %2$s is a title.
			printf( __( 'On <a href="%1$s">%2$s</a>','ultimate-member' ), $link, $comment_title );
			?>
		</span>
	</div>
</div>
