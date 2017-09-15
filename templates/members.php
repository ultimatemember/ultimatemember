<div class="um <?php echo $this->get_class( $mode ); ?> um-<?php echo esc_attr( $form_id ); ?>">

	<div class="um-form">

        <div class="um-member-directory-header">
            <div class="um-member-directory-view-type">
                <a href="#" class="um-profile-edit-a"><i class="um-faicon-cog"></i></a>
            </div>
        </div>

        <?php

		do_action('um_members_directory_search', $args );
		do_action('um_members_directory_head', $args );
		do_action('um_members_directory_display', $args );
		do_action('um_members_directory_footer', $args );

		?>
	</div>
	
</div>