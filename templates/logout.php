<div class="um <?php echo $this->get_class( $mode, $args ); ?> um-<?php echo $form_id; ?>" data-error_required="This field is required" data-password_not_match="Passwords do not match" data-password_not_long="Password must be 8 characters at least">

	<div class="um-form">
	
		<div class="um-misc-with-img">
			
			<div class="um-misc-img">
				<a href="<?php echo um_get_core_page('user'); ?>"><?php echo um_user('profile_photo', 80); ?></a>
			</div>
			
			<div><strong><?php echo um_user('display_name'); ?></strong></div>
			
			<?php do_action('um_logout_after_user_welcome', $args ); ?>
			
		</div>
		
		<ul class="um-misc-ul">
			
			<?php do_action('um_logout_user_links', $args ); ?>
		
		</ul>
	
	</div>
	
</div>