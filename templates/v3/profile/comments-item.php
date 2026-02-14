<?php
/**
 * Template for the profile single post
 *
 * This template can be overridden by copying it to your-theme/ultimate-member/profile/posts-single.php
 *
 * Page: "Profile"
 *
 * @version 2.8.2
 *
 * @var object $comment
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$comment_post_title = apply_filters( 'um_user_profile_comment_title', get_the_title( $comment->comment_post_ID ), $comment );
$comment_post_link  = apply_filters( 'um_user_profile_comment_url', get_permalink( $comment->comment_post_ID ), $comment );

$unix_published_date = get_comment_time( 'Y-m-d H:i:s', true, false, $comment->comment_ID );

$wrapper_classes = array( 'um-list-item' );
?>
<div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>">
	<div class="um-item-data">
		<div>
			<a class="um-link um-header-link um-item-link" href="<?php echo esc_url( get_comment_link( $comment->comment_ID ) ); ?>"><?php echo wp_kses( get_comment_excerpt( $comment->comment_ID ), UM()->get_allowed_html( 'templates' ) ); ?></a>
			<?php
			if ( empty( $comment->comment_approved ) ) {
				echo wp_kses(
					UM()->frontend()::layouts()::badge(
						__( 'Comment is awaiting approval', 'ultimate-member' ),
						array(
							'size'    => 's',
							'color'   => 'warning',
							'classes' => array(
								'um-comment-waiting-approval',
							),
						)
					),
					UM()->get_allowed_html( 'templates' )
				);
			}
			?>
		</div>
		<div class="um-item-meta">
			<?php if ( $unix_published_date ) { ?>
				<span class="um-supporting-text">
					<?php
					// translators: %s: human time diff.
					echo esc_html( sprintf( __( '%s ago', 'ultimate-member' ), human_time_diff( strtotime( $unix_published_date ) ) ) );
					?>
				</span>
			<?php } ?>
			<span class="um-supporting-text">
				<?php
				// translators: %1$s is a link; %2$s is a title.
				printf( __( 'on <a href="%1$s">%2$s</a>','ultimate-member' ), $comment_post_link, $comment_post_title );
				?>
			</span>
		</div>
	</div>
</div>
