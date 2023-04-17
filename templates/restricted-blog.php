<?php
/**
 * Template for the blog restricted message
 *
 * This template can be overridden by copying it to yourtheme/ultimate-member/restricted-blog.php
 *
 * Call: function blog_message()
 *
 * @version 2.6.1
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
			$restriction = get_post_meta( $post->ID, 'um_content_restriction', true );

			if ( ! isset( $restriction['_um_restrict_by_custom_message'] ) || '0' == $restriction['_um_restrict_by_custom_message'] ) {
				$restricted_global_message = UM()->options()->get( 'restricted_access_message' );
				$message = stripslashes( $restricted_global_message );
			} elseif ( '1' == $restriction['_um_restrict_by_custom_message'] ) {
				$message = ! empty( $restriction['_um_restrict_custom_message'] ) ? stripslashes( $restriction['_um_restrict_custom_message'] ) : '';
			}

			// translators: %s: Restricted blog page message.
			printf( __( '%s', 'ultimate-member' ), $message );
			?>
		</main><!-- #main -->
	</div><!-- #primary -->
</div><!-- .wrap -->

<?php
get_footer();
