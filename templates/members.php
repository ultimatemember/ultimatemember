<div class="um <?php echo $this->get_class( $mode ); ?> um-<?php echo $form_id; ?>" data-error_required="This field is required" data-password_not_match="Passwords do not match" data-password_not_long="Password must be 8 characters at least">

	<div class="um-form">

			<?php $args = apply_filters('um_members_directory_arguments', $args ); ?>
			
			<?php do_action('um_members_directory_search', $args ); ?>
			
			<?php do_action('um_members_directory_head', $args ); ?>
			
			<?php do_action('um_members_directory_display', $args ); ?>
			
			<?php do_action('um_members_directory_footer', $args ); ?>

	</div>
	
</div>