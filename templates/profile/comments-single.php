	<?php foreach( $loop as $comment ) { ?>

		<div class="um-item">
			<div class="um-item-link"><a href="<?php echo get_comment_link( $comment->comment_ID ); ?>"><?php echo get_comment_excerpt( $comment->comment_ID ); ?></a></div>
			<div class="um-item-meta">
				<span><?php printf(__('On <a href="%1$s">%2$s</a>','ultimatemember'), get_permalink($comment->comment_post_ID), get_the_title($comment->comment_post_ID) ); ?></span>
			</div>
		</div>
		
	<?php } ?>
	
	<?php if ( isset($modified_args) && count($loop) >= 10 ) { ?>
	
		<div class="um-load-items">
			<a href="#" class="um-ajax-paginate um-button" data-hook="um_load_comments" data-args="<?php echo $modified_args; ?>"><?php _e('load more comments','ultimatemember'); ?></a>
		</div>
		
	<?php } ?>