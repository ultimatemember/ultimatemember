<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$role = $object['data'];
?>

<div class="um-admin-metabox">
	<?php
	UM()->admin()->forms(
		array(
			'class'     => 'um-role-delete um-half-column',
			'prefix_id' => 'role',
			'fields'    => array(
				array(
					'id'          => '_um_after_delete',
					'type'        => 'select',
					'label'       => __( 'Action to be taken after account is deleted', 'ultimate-member' ),
					'description' => __( 'Select what happens when a user with this role deletes their own account', 'ultimate-member' ),
					'value'       => ! empty( $role['_um_after_delete'] ) ? $role['_um_after_delete'] : array(),
					'options'     => array(
						'redirect_home' => __( 'Go to Homepage', 'ultimate-member' ),
						'redirect_url'  => __( 'Go to Custom URL', 'ultimate-member' ),
					),
				),
				array(
					'id'          => '_um_delete_redirect_url',
					'type'        => 'text',
					'label'       => __( 'Set Custom Redirect URL', 'ultimate-member' ),
					'description' => __( 'Set a url to redirect this user role to after they delete account', 'ultimate-member' ),
					'value'       => ! empty( $role['_um_delete_redirect_url'] ) ? $role['_um_delete_redirect_url'] : '',
					'conditional' => array( '_um_after_delete', '=', 'redirect_url' ),
				),
			),
		)
	)->render_form();
	?>
</div>
