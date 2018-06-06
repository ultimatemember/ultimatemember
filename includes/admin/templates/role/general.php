<div class="um-admin-metabox">
	<?php $role = $object['data'];

	UM()->admin_forms( array(
		'class'		=> 'um-role-general um-half-column',
		'prefix_id'	=> 'role',
		'fields' => array(
			array(
				'id'		    => '_um_can_edit_profile',
				'type'		    => 'checkbox',
				'label'    		=> __( 'Can edit their profile?', 'ultimate-member' ),
				'tooltip' 	=> __( 'Can this role edit his own profile?', 'ultimate-member' ),
				'value'		    => ! empty( $role['_um_can_edit_profile'] ) ? $role['_um_can_edit_profile'] : 0,
			),
			array(
				'id'		    => '_um_can_delete_profile',
				'type'		    => 'checkbox',
				'label'    		=> __( 'Can delete their account?', 'ultimate-member' ),
				'tooltip' 	=> __( 'Allow this role to delete their account and end their membership on your site', 'ultimate-member' ),
				'value'		    => ! empty( $role['_um_can_delete_profile'] ) ? $role['_um_can_delete_profile'] : 0,
			)
		)
	) )->render_form(); ?>
</div>