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
 * @var object $post
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$has_thumbnail       = has_post_thumbnail( $post->ID );
$unix_published_date = get_post_datetime( $post, 'date', 'gmt' );
$categories_list     = get_the_category_list( ', ', '', $post->ID );

$wrapper_classes = array( 'um-list-item' );

$item_title = get_the_title( $post );
if ( empty( $item_title ) ) {
	$item_title = __( '(No title)', 'ultimate-member' );
}
?>

<div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>">
	<?php
	if ( $has_thumbnail ) {
		$image_id  = get_post_thumbnail_id( $post->ID );
		$image_url = wp_get_attachment_image_src( $image_id, 'full', true );
		?>
		<div class="um-item-img">
			<a href="<?php echo esc_url( get_permalink( $post ) ); ?>">
				<?php echo get_the_post_thumbnail( $post->ID, 'thumbnail' ); ?>
			</a>
		</div>
		<?php
	} else {
		?>
		<div class="um-item-img um-empty-image">
			<?php esc_html_e( 'No image', 'ultimate-member' ); ?>
		</div>
		<?php
	}
	?>

	<div class="um-item-data">
		<a class="um-link um-header-link um-item-link" href="<?php echo esc_url( get_permalink( $post ) ); ?>"><?php echo wp_kses( $item_title, UM()->get_allowed_html( 'templates' ) ); ?></a>
		<div class="um-item-meta">
			<?php if ( false !== $unix_published_date ) { ?>
				<span class="um-supporting-text">
					<?php
					// translators: %s: human time diff.
					echo esc_html( sprintf( __( '%s ago', 'ultimate-member' ), human_time_diff( $unix_published_date->getTimestamp() ) ) );
					?>
				</span>
			<?php } ?>
			<?php if ( ! empty( $categories_list ) ) { ?>
				<span class="um-supporting-text">
					<?php
					// translators: %s: categories list.
					echo wp_kses( sprintf( __( 'in: %s', 'ultimate-member' ), $categories_list ), UM()->get_allowed_html( 'templates' ) );
					?>
				</span>
			<?php } ?>
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
				<a class="um-link um-link-secondary um-link-underline" href="<?php echo esc_url( get_comments_link( $post->ID ) ); ?>"><?php echo esc_html( $comments_html ); ?></a>
			</span>
		</div>
	</div>
</div>
