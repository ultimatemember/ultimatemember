<?php if ( ! defined( 'ABSPATH' ) ) exit;

UM()->shortcodes()->loop = UM()->query()->make('post_type=comment&number=10&offset=0&user_id=' . um_user('ID') );

if ( UM()->shortcodes()->loop ) {

	UM()->shortcodes()->load_template('profile/comments-single'); ?>
	
	<div class="um-ajax-items">
	
		<!--Ajax output-->
		
		<?php if ( count( UM()->shortcodes()->loop ) >= 10 ) { ?>
		
			<div class="um-load-items">
				<a href="javascript:void(0);" class="um-ajax-paginate um-button" data-hook="um_load_comments"
				   data-args="comment,10,10,<?php echo esc_attr( um_user( 'ID' ) ); ?>">
					<?php _e( 'load more comments', 'ultimate-member' ); ?>
				</a>
			</div>
		
		<?php } ?>
		
	</div>
		
<?php } else { ?>

	<div class="um-profile-note">
		<span>
			<?php if ( um_profile_id() == get_current_user_id() ) {
				_e( 'You have not made any comments.', 'ultimate-member' );
			} else {
				_e( 'This user has not made any comments.', 'ultimate-member' );
			} ?>
		</span>
	</div>
	
<?php }