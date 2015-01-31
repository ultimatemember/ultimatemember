<?php $loop = $ultimatemember->query->make('post_type=post&posts_per_page=10&offset=0&author=' . um_user('ID') ); ?>

<?php if ( $loop->have_posts()) { ?>
			
	<?php include_once um_path . 'templates/profile/posts-single.php'; ?>
	
	<div class="um-ajax-items">
	
		<!--Ajax output-->
		
		<?php if ( $loop->found_posts >= 10 ) { ?>
		
		<div class="um-load-items">
			<a href="#" class="um-ajax-paginate um-button" data-hook="um_load_posts" data-args="post,10,10,<?php echo um_user('ID'); ?>"><?php _e('load more posts','um-bbpress'); ?></a>
		</div>
		
		<?php } ?>
		
	</div>
		
<?php } else { ?>

	<?php ( um_is_myprofile() ) ? _e('You have not created any posts.') : _e('This user has not created any posts.'); ?>

<?php } ?>