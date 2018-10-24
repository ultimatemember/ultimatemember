<?php if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
	//Only for AJAX loading posts
	if ( ! empty( $posts ) ) {
		foreach ( $posts as $post ) {
			UM()->shortcodes()->set_args = array( 'post' => $post );
			UM()->shortcodes()->load_template( 'profile/posts-single' );
		}
	}
} else {
	if ( ! empty( $posts ) ) { ?>
		<div class="um-ajax-items">

			<?php foreach ( $posts as $post ) {
				UM()->shortcodes()->set_args = array( 'post' => $post );
				UM()->shortcodes()->load_template( 'profile/posts-single' );
			}

			if ( $count_posts > 10 ) { ?>
				<div class="um-load-items">
					<a href="javascript:void(0);" class="um-ajax-paginate um-button" data-hook="um_load_posts" data-author="<?php echo um_get_requested_user(); ?>" data-page="1" data-pages="<?php echo ceil( $count_posts / 10 ) ?>">
						<?php _e( 'load more posts', 'ultimate-member' ); ?>
					</a>
				</div>
			<?php } ?>

		</div>

	<?php } else { ?>

		<div class="um-profile-note">
			<span>
				<?php echo ( um_profile_id() == get_current_user_id() ) ? __( 'You have not created any posts.', 'ultimate-member' ) : __( 'This user has not created any posts.', 'ultimate-member' ); ?>
			</span>
		</div>

	<?php }
}