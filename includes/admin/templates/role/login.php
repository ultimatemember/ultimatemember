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
			'class'     => 'um-role-login um-half-column',
			'prefix_id' => 'role',
			'fields'    => array(
				array(
					'id'          => '_um_after_login',
					'type'        => 'select',
					'label'       => __( 'Action to be taken after login', 'ultimate-member' ),
					'description' => __( 'Select what happens when a user with this role logins to your site. Role setting has a highest priority for redirect.', 'ultimate-member' ),
					'value'       => ! empty( $role['_um_after_login'] ) ? $role['_um_after_login'] : 'redirect_profile', // set 'redirect_profile' as default redirect
					'options'     => UM()->config()->get( 'login_redirect_options' ),
				),
				array(
					'id'          => '_um_login_redirect_url',
					'type'        => 'text',
					'label'       => __( 'Set Custom Redirect URL', 'ultimate-member' ),
					'description' => __( 'Set a url to redirect this user role to after they login with their account.', 'ultimate-member' ),
					'value'       => ! empty( $role['_um_login_redirect_url'] ) ? $role['_um_login_redirect_url'] : '',
					'conditional' => array( '_um_after_login', '=', 'redirect_url' ),
				),
			),
		)
	)->render_form();
	?>
	<div class="clear"></div>
</div>
