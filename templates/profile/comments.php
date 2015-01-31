<?php $loop = $ultimatemember->query->make('post_type=comment&number=10&offset=0&author_email=' . um_user('user_email') ); ?>

<?php if ( $loop ) { ?>
			
	<?php include_once um_path . 'templates/profile/comments-single.php'; ?>
	
	<div class="um-ajax-items">
	
		<!--Ajax output-->
		
		<?php if ( count($loop) >= 10 ) { ?>
		
		<div class="um-load-items">
			<a href="#" class="um-ajax-paginate um-button" data-hook="um_load_comments" data-args="comment,10,10,<?php echo um_user('user_email'); ?>"><?php _e('load more comments','um-bbpress'); ?></a>
		</div>
		
		<?php } ?>
		
	</div>
		
<?php } else { ?>

	<?php ( um_is_myprofile() ) ? _e('You have not made any comments.') : _e('This user has not made any comments.'); ?>

<?php } ?>