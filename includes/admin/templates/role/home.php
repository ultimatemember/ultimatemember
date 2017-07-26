<div class="um-admin-metabox">

	<?php $role = $object['data'];

    UM()->admin_forms( array(
        'class'		=> 'um-role-home um-half-column',
        'prefix_id'	=> 'role',
        'fields' => array(
            array(
                'id'		    => '_um_default_homepage',
                'type'		    => 'checkbox',
                'name'		    => '_um_default_homepage',
                'label'    		=> __( 'Can view default homepage?', 'ultimate-member' ),
                'tooltip' 	=> __( 'Allow this user role to view your site\'s homepage', 'ultimate-member' ),
                'value'		    => ! empty( $role['_um_default_homepage'] ) ? $role['_um_default_homepage'] : 0,
            ),
            array(
                'id'		=> '_um_redirect_homepage',
                'type'		=> 'text',
                'name'		=> '_um_redirect_homepage',
                'label'    		=> __( 'Custom Homepage Redirect', 'ultimate-member' ),
                'tooltip' 	=> __( 'Set a url to redirect this user role to if they try to view your site\'s homepage', 'ultimate-member' ),
                'value'		=> ! empty( $role['_um_redirect_homepage'] ) ? $role['_um_redirect_homepage'] : '',
                'conditional'	=> array( '_um_default_homepage', '=', '0' )
            )
        )
    ) )->render_form(); ?>
	
</div>