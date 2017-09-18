<div class="um <?php echo $this->get_class( $mode ); ?> um-<?php echo esc_attr( $form_id ); ?>">

    <?php $args['view_type'] = ! empty( $_GET['view_type'] ) ? $_GET['view_type'] : $args['view_type'];
    $view_type = ( ! empty( $args['view_type'] ) && 'list' == $args['view_type'] ) ? 'list' : 'grid'; ?>

	<div class="um-form">

        <div class="um-member-directory-header">
            <div class="um-clear"></div>
            <div class="um-member-directory-view-type">
                <a href="#" class="um-member-directory-view-type-a">
                    <i class="<?php if ( 'list' == $view_type ) { ?>um-faicon-th-list<?php } else { ?>um-faicon-th-large<?php } ?>"></i>
                </a>
                <?php
                $items = array(
                    'logout' => '<a href="'. um_get_core_page('members') .'" class="real_url">'.__('Grid','ultimate-member').'</a>',
                    'editprofile' => '<a href="'.add_query_arg( array('view_type' => 'list'), um_get_core_page('members') ).'" class="real_url">'.__('List','ultimate-member').'</a>',
                    'cancel' => '<a href="#" class="um-dropdown-hide">'.__('Cancel','ultimate-member').'</a>',
                );

                UM()->menu()->new_ui( 'bc', 'div.um-member-directory-view-type', 'click', $items ); ?>
            </div>
            <div class="um-clear"></div>
        </div>

        <?php

		do_action('um_members_directory_search', $args );
		do_action('um_members_directory_head', $args );
		do_action('um_members_directory_display', $args );
		do_action('um_members_directory_footer', $args );

		?>
	</div>
	
</div>