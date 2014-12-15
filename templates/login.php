<div class="um <?php echo $this->get_class( $mode ); ?> um-<?php echo $form_id; ?>" data-error_required="This field is required" data-password_not_match="Passwords do not match" data-password_not_long="Password must be 8 characters at least">

	<div class="um-form">
	
		<form method="post" action="" autocomplete="off">
	
		<?php

			do_action("um_before_form", $args);
			
			do_action("um_before_{$template}_fields", $args);
			
			do_action("um_main_{$template}_fields", $args);
			
			do_action("um_after_form_fields", $args);
			
			do_action("um_after_{$template}_fields", $args);
			
			do_action("um_after_form", $args);
			
		?>
		
		</form>
	
	</div>
	
</div>