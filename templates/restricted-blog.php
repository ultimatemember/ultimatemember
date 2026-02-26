<?php
/**
 * Template for the blog restricted message
 *
 * This template can be overridden by copying it to your-theme/ultimate-member/restricted-blog.php
 *
 * Call: function blog_message()
 *
 * @version 2.11.3
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $post, $wp_query;
$wp_query->queried_object = UM()->access()->maybe_replace_title( $post );

get_header();
?>

<?php if ( is_home() && ! is_front_page() && ! empty( single_post_title( '', false ) ) ) : ?>
	<header class="page-header alignwide">
		<h1 class="page-title"><?php single_post_title(); ?></h1>
	</header><!-- .page-header -->
<?php endif; ?>

<div class="wrap">
	<div id="primary" class="content-area">
		<main id="main" class="site-main" role="main">
			<?php
			$message     = '';
			$restriction = get_post_meta( $post->ID, 'um_content_restriction', true );

			if ( ! array_key_exists( '_um_restrict_by_custom_message', $restriction ) || empty( $restriction['_um_restrict_by_custom_message'] ) ) {
				$message = UM()->options()->get( 'restricted_access_message' );
			} elseif ( ! empty( $restriction['_um_restrict_custom_message'] ) ) {
				$message = $restriction['_um_restrict_custom_message'];
			}

			// Restricted access message from plugin settings or post meta.
			// Output with safe HTML escaping to allow basic formatting.
			echo wp_kses_post( stripslashes( $message ) );
			?>
		</main><!-- #main -->
	</div><!-- #primary -->
</div><!-- .wrap -->

<?php
get_footer();
