<?php
/**
 * Template for the profile single post
 *
 * This template can be overridden by copying it to yourtheme/ultimate-member/profile/posts-single.php
 *
 * Page: "Profile"
 *
 * @version 2.6.1
 *
 * @var object $post
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} ?>

<div class="um-item">
	<div class="um-item-link">
		<i class="um-icon-ios-paper"></i>
		<a href="<?php echo esc_url( get_permalink( $post ) ); ?>"><?php echo get_the_title( $post ); ?></a>
	</div>

	<?php if ( has_post_thumbnail( $post->ID ) ) {
		$image_id = get_post_thumbnail_id( $post->ID );
		$image_url = wp_get_attachment_image_src( $image_id, 'full', true ); ?>

		<div class="um-item-img">
			<a href="<?php echo esc_url( get_permalink( $post ) ); ?>">
				<?php echo get_the_post_thumbnail( $post->ID, 'medium' ); ?>
			</a>
		</div>

	<?php } ?>

	<div class="um-item-meta">
		<span>
			<?php
			// translators: %s: years.
			printf( __( '%s ago', 'ultimate-member' ), human_time_diff( get_the_time( 'U', $post->ID ), current_time( 'timestamp' ) ) );
			?>
		</span>
		<span>
			<?php _e( 'in', 'ultimate-member' ); ?>: <?php the_category( ', ', '', $post->ID ); ?>
		</span>
		<span>
			<?php $num_comments = get_comments_number( $post->ID );

			if ( $num_comments == 0 ) {
				$comments = __( 'no comments', 'ultimate-member' );
			} elseif ( $num_comments > 1 ) {
				// translators: %s: coments number.
				$comments = sprintf( __( '%s comments', 'ultimate-member' ), $num_comments );
			} else {
				$comments = __( '1 comment', 'ultimate-member' );
			} ?>

			<a href="<?php echo esc_url( get_comments_link( $post->ID ) ); ?>"><?php echo $comments; ?></a>
		</span>
	</div>
</div>
