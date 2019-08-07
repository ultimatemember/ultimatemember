<?php if ( ! defined( 'ABSPATH' ) ) exit;

foreach ( UM()->shortcodes()->loop as $comment ) {

	$post_type = get_post_type( $comment->comment_post_ID );
	if ( $post_type == 'um_groups_discussion' ) {
		$comment_id = $comment->comment_post_ID;
		$group_id = get_post_meta( $comment_id, '_group_id', true );
		$comment_title = get_the_title( $group_id );
		$link = site_url() . '/groups/' . $comment_title . '/?tab=discussion#commentid-' . $comment_id;
	} else {
		$comment_title = get_the_title( $comment->comment_post_ID );
		$link = get_permalink( $comment->comment_post_ID );
	} ?>

	<div class="um-item">
		<div class="um-item-link">
			<i class="um-icon-chatboxes"></i>
			<a href="<?php echo esc_url( get_comment_link( $comment->comment_ID ) ); ?>">
				<?php echo get_comment_excerpt( $comment->comment_ID ); ?>
			</a>
		</div>
		<div class="um-item-meta">
			<span><?php printf( __( 'On <a href="%1$s">%2$s</a>','ultimate-member' ), $link, $comment_title ); ?></span>
		</div>
	</div>

<?php }

if ( isset( UM()->shortcodes()->modified_args ) && count( UM()->shortcodes()->loop ) >= 10 ) { ?>

	<div class="um-load-items">
		<a href="javascript:void(0);" class="um-ajax-paginate um-button" data-hook="um_load_comments"
		   data-args="<?php echo esc_attr( UM()->shortcodes()->modified_args ); ?>">
			<?php _e( 'load more comments', 'ultimate-member' ); ?>
		</a>
	</div>

<?php }