<div class="um-admin-metabox">

	<?php $role = $object['data'];

	UM()->admin_forms( array(
		'class'		=> 'um-role-login um-half-column',
		'prefix_id'	=> 'role',
		'fields' => array(
			array(
				'id'		    => '_um_after_login',
				'type'		    => 'select',
				'label'    		=> __( 'Action to be taken after login', 'ultimate-member' ),
				'tooltip' 	    => __( 'Select what happens when a user with this role logins to your site', 'ultimate-member' ),
				'value' 		=> ! empty( $role['_um_after_login'] ) ? $role['_um_after_login'] : array(),
				'options'		=> array(
					'redirect_profile'	=> __( 'Redirect to profile', 'ultimate-member' ),
					'redirect_url'		=> __( 'Redirect to URL', 'ultimate-member' ),
					'refresh'			=> __( 'Refresh active page', 'ultimate-member' ),
					'redirect_admin'	=> __( 'Redirect to WordPress Admin', 'ultimate-member' )
				)
			),
			array(
				'id'		    => '_um_login_redirect_url',
				'type'		    => 'text',
				'label'    		=> __( 'Set Custom Redirect URL', 'ultimate-member' ),
				'tooltip' 	    => __( 'Set a url to redirect this user role to after they login with their account', 'ultimate-member' ),
				'value' 		=> ! empty( $role['_um_login_redirect_url'] ) ? $role['_um_login_redirect_url'] : '',
				'conditional'	=> array( '_um_after_login', '=', 'redirect_url' )
			)
		)
	) )->render_form(); ?>

</div>