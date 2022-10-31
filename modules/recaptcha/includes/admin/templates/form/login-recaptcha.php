<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$allowed_html = array(
	'strong' => array(
		'style' => true,
	),
);

global $post;
?>

<div class="um-admin-metabox">
	<?php $recaptcha_enabled = UM()->options()->get( 'g_recaptcha_status' ); ?>

	<?php if ( $recaptcha_enabled ) {
		$default_info = wp_kses( __( 'Google reCAPTCHA seems to be <strong style="color:#7ACF58;">enabled</strong> by default.', 'ultimate-member' ), $allowed_html );
	} else {
		$default_info = wp_kses( __( 'Google reCAPTCHA seems to be <strong style="color:#C74A4A;">disabled</strong> by default.', 'ultimate-member' ), $allowed_html );
	}

	$login_g_recaptcha_status = get_post_meta( $post->ID, '_um_login_g_recaptcha_status', true );

	$fields = array(
		array(
			'id'      => '_um_login_g_recaptcha_status',
			'type'    => 'select',
			'label'   => __( 'reCAPTCHA status on this form', 'ultimate-member' ),
			'value'   => $login_g_recaptcha_status,
			'options' => array(
				''  => __( 'Default', 'ultimate-member' ),
				'0' => __( 'No', 'ultimate-member' ),
				'1' => __( 'Yes', 'ultimate-member' ),
			),
		),
		array(
			'id'          => 'login_show_recaptcha',
			'type'        => 'info_text',
			'value'       => $default_info,
			'conditional' => array( '_um_login_g_recaptcha_status', '=', '' ),
		),
	);

	$version = UM()->options()->get( 'g_recaptcha_version' );
	if ( 'v3' === $version ) {
		$login_g_recaptcha_score = get_post_meta( $post->ID, '_um_login_g_recaptcha_score', true );
		$login_g_recaptcha_score = '' === $login_g_recaptcha_score ? UM()->options()->get( 'g_reCAPTCHA_score' ) : $login_g_recaptcha_score;

		$fields[] = array(
			'id'          => '_um_login_g_recaptcha_score',
			'type'        => 'text',
			'label'       => __( 'reCAPTCHA score', 'ultimate-member' ),
			'value'       => $login_g_recaptcha_score,
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
