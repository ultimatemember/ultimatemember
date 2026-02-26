<?php
/**
 * Template for the taxonomy restricted message
 *
 * This template can be overridden by copying it to your-theme/ultimate-member/restricted-taxonomy.php
 *
 * Call: function taxonomy_message()
 *
 * @version 2.11.3
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<div class="wrap">

	<?php if ( have_posts() ) : ?>
		<header class="page-header">
			<?php
			the_archive_title( '<h1 class="page-title">', '</h1>' );
			the_archive_description( '<div class="taxonomy-description">', '</div>' );
			?>
		</header><!-- .page-header -->
	<?php endif; ?>

	<div id="primary" class="content-area">
		<main id="main" class="site-main" role="main">
			<?php
			$message     = '';
			$restriction = array();
			if ( is_tag() ) {
				$tag_id = get_query_var( 'tag_id' );
				if ( ! empty( $tag_id ) ) {
					$restriction = get_term_meta( $tag_id, 'um_content_restriction', true );
				}
			} elseif ( is_category() ) {
				$um_category = get_category( get_query_var( 'cat' ) );

				if ( ! empty( $um_category->term_id ) ) {
					$restriction = get_term_meta( $um_category->term_id, 'um_content_restriction', true );
				}
			} elseif ( is_tax() ) {
				$tax_name     = get_query_var( 'taxonomy' );
				$term_name    = get_query_var( 'term' );
				$current_term = get_term_by( 'slug', $term_name, $tax_name );
				if ( ! empty( $current_term->term_id ) ) {
					$restriction = get_term_meta( $current_term->term_id, 'um_content_restriction', true );
				}
			}

			if ( ! array_key_exists( '_um_restrict_by_custom_message', $restriction ) || empty( $restriction['_um_restrict_by_custom_message'] ) ) {
				$message = UM()->options()->get( 'restricted_access_message' );
			} elseif ( ! empty( $restriction['_um_restrict_custom_message'] ) ) {
				$message = $restriction['_um_restrict_custom_message'];
			}

			// Restricted taxonomy message from plugin settings or term meta.
			// Output with safe HTML escaping to allow basic formatting.
			echo wp_kses_post( stripslashes( $message ) );
			?>
		</main><!-- #main -->
	</div><!-- #primary -->
	<?php get_sidebar(); ?>
</div><!-- .wrap -->

<?php
get_footer();
