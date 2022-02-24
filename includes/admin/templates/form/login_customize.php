<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $post_id;

$login_forgot_pass_link = ! isset( $post_id ) ? UM()->options()->get( 'login_forgot_pass_link' ) : get_post_meta( $post_id, '_um_login_forgot_pass_link', true );
$login_show_rememberme  = ! isset( $post_id ) ? UM()->options()->get( 'login_show_rememberme' ) : get_post_meta( $post_id, '_um_login_show_rememberme', true );

$login_customize_fields = array(
	array(
		'id'      => '_um_login_template',
		'type'    => 'select',
		'label'   => __( 'Template', 'ultimate-member' ),
		'value'   => UM()->query()->get_meta_value( '_um_login_template', null, UM()->options()->get( 'login_template' ) ),
		'options' => UM()->shortcodes()->get_templates( 'login' ),
	),
	array(
		'id'          => '_um_login_primary_btn_word',
		'type'        => 'text',
		'label'       => __( 'Primary Button Text', 'ultimate-member' ),
		'description' => __( 'Customize the button text', 'ultimate-member' ),
		'value'       => UM()->query()->get_meta_value( '_um_login_primary_btn_word', null, UM()->options()->get( 'login_primary_btn_word' ) ),
	),
	array(
		'id'      => '_um_login_forgot_pass_link',
		'type'    => 'select',
		'label'   => __( 'Show Forgot Password Link?', 'ultimate-member' ),
		'value'   => $login_forgot_pass_link,
		'options' => array(
			0 => __( 'No', 'ultimate-member' ),
			1 => __( 'Yes', 'ultimate-member' ),
		),
	),
	array(
		'id'      => '_um_login_show_rememberme',
		'type'    => 'select',
		'label'   => __( 'Show "Remember Me"?', 'ultimate-member' ),
		'value'   => $login_show_rememberme,
		'options' => array(
			0 => __( 'No', 'ultimate-member' ),
			1 => __( 'Yes', 'ultimate-member' ),
		),
	),
	array(
		'id'          => '_um_login_after_login',
		'type'        => 'select',
		'label'       => __( 'Redirection after Login', 'ultimate-member' ),
		'description' => __( 'Change this If you want to override role redirection settings after login only.', 'ultimate-member' ),
		'value'       => UM()->query()->get_meta_value( '_um_login_after_login', null, 0 ),
		'options'     => array(
			'0'                => __( 'Default', 'ultimate-member' ),
			'redirect_profile' => __( 'Redirect to profile', 'ultimate-member' ),
			'redirect_url'     => __( 'Redirect to URL', 'ultimate-member' ),
			'refresh'          => __( 'Refresh active page', 'ultimate-member' ),
			'redirect_admin'   => __( 'Redirect to WordPress Admin', 'ultimate-member' ),
		),
	),
	array(
		'id'          => '_um_login_redirect_url',
		'type'        => 'text',
		'label'       => __( 'Set Custom Redirect URL', 'ultimate-member' ),
		'value'       => UM()->query()->get_meta_value( '_um_login_redirect_url', null, 'na' ),
		'conditional' => array( '_um_login_after_login', '=', 'redirect_url' ),
	),
);
?>

<div class="um-admin-metabox">
	<?php
	UM()->admin_forms(
		array(
			'class'     => 'um-form-login-customize um-top-label',
			'prefix_id' => 'form',
			'fields'    => $login_customize_fields,
		)
	)->render_form();
	?>
	<div class="um-admin-clear"></div>
</div>
