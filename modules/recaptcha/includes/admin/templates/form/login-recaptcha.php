<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$allowed_html = array(
	'strong' => array(
		'style' => true,
	),
);
?>

<div class="um-admin-metabox">
	<?php $recaptcha_enabled = UM()->options()->get( 'g_recaptcha_status' ); ?>

	<?php if ( $recaptcha_enabled ) { ?>
		<p><?php echo wp_kses( __( 'Google reCAPTCHA seems to be <strong style="color:#7ACF58;">enabled</strong> by default.', 'ultimate-member' ), $allowed_html ); ?></p>
	<?php } else { ?>
		<p><?php echo wp_kses( __( 'Google reCAPTCHA seems to be <strong style="color:#C74A4A;">disabled</strong> by default.', 'ultimate-member' ), $allowed_html ); ?></p>
	<?php } ?>

	<?php
	$fields = array(
		array(
			'id'      => '_um_login_g_recaptcha_status',
			'type'    => 'select',
			'label'   => __( 'reCAPTCHA status on this form', 'ultimate-member' ),
			'value'   => UM()->query()->get_meta_value( '_um_login_g_recaptcha_status', null, $recaptcha_enabled ),
			'options' => array(
				0 => __( 'No', 'ultimate-member' ),
				1 => __( 'Yes', 'ultimate-member' ),
			),
		),
	);

	$version = UM()->options()->get( 'g_recaptcha_version' );
	if ( 'v3' === $version ) {
		$fields[] = array(
			'id'          => '_um_login_g_recaptcha_score',
			'type'        => 'text',
			'label'       => __( 'reCAPTCHA score', 'ultimate-member' ),
			'value'       => UM()->query()->get_meta_value( '_um_login_g_recaptcha_score', null, UM()->options()->get( 'g_reCAPTCHA_score' ) ),
			'conditional' => array( '_um_login_g_recaptcha_status', '=', '1' ),
		);
	}

	UM()->admin_forms(
		array(
			'class'     => 'um-form-login-recaptcha um-top-label',
			'prefix_id' => 'form',
			'fields'    => $fields,
		)
	)->render_form();
	?>

	<div class="um-admin-clear"></div>
</div>
