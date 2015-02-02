	<?php while ($loop->have_posts()) { $loop->the_post(); $post_id = get_the_ID(); ?>

		<div class="um-item">
			<div class="um-item-link"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></div>
			<div class="um-item-meta">
				<span><?php echo sprintf(__('%s ago','ultimatemember'), human_time_diff( get_the_time('U'), current_time('timestamp') ) ); ?></span>
				<span>in: <?php the_category( ', ' ); ?></span>
				<span><?php comments_number( __('no comments','ultimatemember'), __('1 comment','ultimatemember'), __('% comments','ultimatemember') ); ?></span>
			</div>
		</div>
		
	<?php } ?>
	
	<?php if ( isset($modified_args) && $loop->have_posts() && $loop->found_posts >= 10 ) { ?>
	
		<div class="um-load-items">
			<a href="#" class="um-ajax-paginate um-button" data-hook="um_load_posts" data-args="<?php echo $modified_args; ?>"><?php _e('load more posts','ultimatemember'); ?></a>
		</div>
		
	<?php } ?>