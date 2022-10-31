<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $post;

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
	$register_g_recaptcha_status = get_post_meta( $post->ID, '_um_register_g_recaptcha_status', true );
	$register_g_recaptcha_status = '' === $register_g_recaptcha_status ? $recaptcha_enabled : $register_g_recaptcha_status;

	$fields = array(
		array(
			'id'      => '_um_register_g_recaptcha_status',
			'type'    => 'select',
			'label'   => __( 'reCAPTCHA status on this form', 'ultimate-member' ),
			'value'   => $register_g_recaptcha_status,
			'options' => array(
				''  => __( 'Default', 'ultimate-member' ),
				'0' => __( 'No', 'ultimate-member' ),
				'1' => __( 'Yes', 'ultimate-member' ),
			),
		),
	);

	$version = UM()->options()->get( 'g_recaptcha_version' );
	if ( 'v3' === $version ) {
		$register_g_recaptcha_score = get_post_meta( $post->ID, '_um_register_g_recaptcha_score', true );
		$register_g_recaptcha_score = '' === $register_g_recaptcha_score ? UM()->options()->get( 'g_reCAPTCHA_score' ) : $register_g_recaptcha_score;

		$fields[] = array(
			'id'          => '_um_register_g_recaptcha_score',
			'type'        => 'text',
			'label'       => __( 'reCAPTCHA score', 'ultimate-member' ),
			'value'       => $register_g_recaptcha_score,
			'conditional' => array( '_um_register_g_recaptcha_status', '=', '1' ),
		);
	}

	UM()->admin_forms(
		array(
			'class'     => 'um-form-register-recaptcha um-top-label',
			'prefix_id' => 'form',
			'fields'    => $fields,
		)
	)->render_form();
	?>

	<div class="um-admin-clear"></div>
</div>
