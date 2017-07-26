<?php $query_posts = UM()->query()->make('post_type=post&posts_per_page=10&offset=0&author=' . um_user('ID') ); ?>

<?php UM()->shortcodes()->loop = apply_filters('um_profile_query_make_posts', $query_posts ); ?>

<?php if ( UM()->shortcodes()->loop->have_posts()) { ?>
			
	<?php UM()->shortcodes()->load_template('profile/posts-single'); ?>
	
	<div class="um-ajax-items">
	
		<!--Ajax output-->
		
		<?php if ( UM()->shortcodes()->loop->found_posts >= 10 ) { ?>
		
		<div class="um-load-items">
			<a href="#" class="um-ajax-paginate um-button" data-hook="um_load_posts" data-args="post,10,10,<?php echo um_user('ID'); ?>"><?php _e('load more posts','ultimate-member'); ?></a>
		</div>
		
		<?php } ?>
		
	</div>
		
<?php } else { ?>

	<div class="um-profile-note"><span><?php echo ( um_profile_id() == get_current_user_id() ) ? __('You have not created any posts.','ultimate-member') : __('This user has not created any posts.','ultimate-member'); ?></span></div>
	
<?php } wp_reset_postdata(); ?>