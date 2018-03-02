<?php $query_posts = UM()->query()->make('post_type=post&posts_per_page=10&offset=0&author=' . um_get_requested_user() );

/**
 * UM hook
 *
 * @type filter
 * @title um_profile_query_make_posts
 * @description Some changes of WP_Query Posts Tab
 * @input_vars
 * [{"var":"$query_posts","type":"WP_Query","desc":"UM Posts Tab query"}]
 * @change_log
 * ["Since: 2.0"]
 * @usage
 * <?php add_filter( 'um_profile_query_make_posts', 'function_name', 10, 1 ); ?>
 * @example
 * <?php
 * add_filter( 'um_profile_query_make_posts', 'my_profile_query_make_posts', 10, 1 );
 * function my_profile_query_make_posts( $query_posts ) {
 *     // your code here
 *     return $query_posts;
 * }
 * ?>
 */
UM()->shortcodes()->loop = apply_filters( 'um_profile_query_make_posts', $query_posts );

if ( UM()->shortcodes()->loop->have_posts() ) {

	UM()->shortcodes()->load_template( 'profile/posts-single' ); ?>
	
	<div class="um-ajax-items">
		<!--Ajax output-->
		<?php if ( UM()->shortcodes()->loop->found_posts >= 10 ) { ?>
		
			<div class="um-load-items">
				<a href="#" class="um-ajax-paginate um-button" data-hook="um_load_posts" data-args="post,10,10,<?php echo um_get_requested_user(); ?>"><?php _e('load more posts','ultimate-member'); ?></a>
			</div>

		<?php } ?>
	</div>
		
<?php } else { ?>

	<div class="um-profile-note"><span><?php echo ( um_profile_id() == get_current_user_id() ) ? __('You have not created any posts.','ultimate-member') : __('This user has not created any posts.','ultimate-member'); ?></span></div>
	
<?php } wp_reset_postdata(); ?>