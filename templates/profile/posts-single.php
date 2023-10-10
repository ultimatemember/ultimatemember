<?php
/**
 * Template for the profile single post
 *
 * This template can be overridden by copying it to yourtheme/ultimate-member/profile/posts-single.php
 *
 * Page: "Profile"
 *
 * @version 2.7.0
 *
 * @var object $post
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} ?>

<div class="um-item">
	<div class="um-item-link">
		<i class="um-icon-ios-paper"></i>
		<a href="<?php echo esc_url( get_permalink( $post ) ); ?>"><?php echo wp_kses( get_the_title( $post ), UM()->get_allowed_html( 'templates' ) ); ?></a>
	</div>

	<?php
	if ( has_post_thumbnail( $post->ID ) ) {
		$image_id  = get_post_thumbnail_id( $post->ID );
		$image_url = wp_get_attachment_image_src( $image_id, 'full', true );
		?>
		<div class="um-item-img">
			<a href="<?php echo esc_url( get_permalink( $post ) ); ?>">
				<?php echo get_the_post_thumbnail( $post->ID, 'medium' ); ?>
			</a>
		</div>
		<?php
	}

	$unix_published_date = get_post_datetime( $post, 'date', 'gmt' );
	?>

	<div class="um-item-meta">
		<span>
			<?php
			// translators: %s: human time diff.
			echo esc_html( sprintf( __( '%s ago', 'ultimate-member' ), human_time_diff( $unix_published_date->getTimestamp() ) ) );
			?>
		</span>
		<span>
			<?php
			// translators: %s: categories list.
			echo wp_kses( sprintf( __( 'in: %s', 'ultimate-member' ), get_the_category_list( ', ', '', $post->ID ) ), UM()->get_allowed_html( 'templates' ) );
			?>
		</span>
		<span>
			<?php
			$num_comments = absint( get_comments_number( $post ) );
			if ( 0 === $num_comments ) {
				$comments_html = __( 'no comments', 'ultimate-member' );
			} else {
				// translators: %s: comments number.
				$comments_html = sprintf( _n( '%s comment', '%s comments', $num_comments, 'ultimate-member' ), $num_comments );
			}
			?>
			<a href="<?php echo esc_url( get_comments_link( $post->ID ) ); ?>"><?php echo esc_html( $comments_html ); ?></a>
		</span>
	</div>
</div>
