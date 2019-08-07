<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>


<div class="um-admin-metabox">

	<?php $role = $object['data'];

	UM()->admin_forms( array(
		'class'		=> 'um-role-logout um-half-column',
		'prefix_id'	=> 'role',
		'fields' => array(
			array(
				'id'		    => '_um_after_logout',
				'type'		    => 'select',
				'label'    		=> __( 'Action to be taken after logout', 'ultimate-member' ),
				'tooltip' 	=> __( 'Select what happens when a user with this role logouts of your site', 'ultimate-member' ),
				'value' 		=> ! empty( $role['_um_after_logout'] ) ? $role['_um_after_logout'] : array(),
				'options'		=> array(
					'redirect_home' => __( 'Go to Homepage', 'ultimate-member' ),
					'redirect_url'	=> __( 'Go to Custom URL', 'ultimate-member' ),
				)
			),
			array(
				'id'		=> '_um_logout_redirect_url',
				'type'		=> 'text',
				'label'    		=> __( 'Set Custom Redirect URL', 'ultimate-member' ),
				'tooltip' 	=> __( 'Set a url to redirect this user role to after they logout from site', 'ultimate-member' ),
				'value' 		=> ! empty( $role['_um_logout_redirect_url'] ) ? $role['_um_logout_redirect_url'] : '',
				'conditional'	=> array( '_um_after_logout', '=', 'redirect_url' )
			)
		)
	) )->render_form(); ?>

</div>