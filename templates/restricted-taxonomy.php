<?php if ( ! defined( 'ABSPATH' ) ) exit;

get_header(); ?>

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

			<?php if ( is_tag() ) {
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
				$tax_name = get_query_var( 'taxonomy' );
				$term_name = get_query_var( 'term' );
				$term = get_term_by( 'slug', $term_name, $tax_name );
				if ( ! empty( $term->term_id ) ) {
					$restriction = get_term_meta( $term->term_id, 'um_content_restriction', true );
				}
			}

			if ( ! isset( $restriction['_um_restrict_by_custom_message'] ) || '0' == $restriction['_um_restrict_by_custom_message'] ) {
				$restricted_global_message = UM()->options()->get( 'restricted_access_message' );
				$message = stripslashes( $restricted_global_message );
			} elseif ( '1' == $restriction['_um_restrict_by_custom_message'] ) {
				$message = ! empty( $restriction['_um_restrict_custom_message'] ) ? stripslashes( $restriction['_um_restrict_custom_message'] ) : '';
			}

			// translators: %s: Restricted taxonomy message.
			printf( __( '%s', 'ultimate-member' ), $message ); ?>

		</main><!-- #main -->
	</div><!-- #primary -->
	<?php get_sidebar(); ?>
</div><!-- .wrap -->

<?php get_footer();