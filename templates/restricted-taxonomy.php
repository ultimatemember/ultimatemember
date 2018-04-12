<?php

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

				<?php echo 'asdasdasda'; ?>

			</main><!-- #main -->
		</div><!-- #primary -->
		<?php get_sidebar(); ?>
	</div><!-- .wrap -->

<?php get_footer();