<?php $ultimatemember->shortcodes->loop = $ultimatemember->query->make('post_type=comment&number=10&offset=0&user_id=' . um_user('ID') ); ?>

<?php if ( $ultimatemember->shortcodes->loop ) { ?>
			
	<?php $ultimatemember->shortcodes->load_template('profile/comments-single'); ?>
	
	<div class="um-ajax-items">
	
		<!--Ajax output-->
		
		<?php if ( count($ultimatemember->shortcodes->loop) >= 10 ) { ?>
		
		<div class="um-load-items">
			<a href="#" class="um-ajax-paginate um-button" data-hook="um_load_comments" data-args="comment,10,10,<?php echo um_user('ID'); ?>"><?php _e('load more comments','ultimatemember'); ?></a>
		</div>
		
		<?php } ?>
		
	</div>
		
<?php } else { ?>

	<?php ( um_is_myprofile() ) ? _e('You have not made any comments.','ultimatemember') : _e('This user has not made any comments.','ultimatemember'); ?>

<?php } ?>