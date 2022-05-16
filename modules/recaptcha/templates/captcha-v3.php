<?php
/**
 * Template for the UM Google reCAPTCHA
 *
 * Called from the um_recaptcha_add_captcha() function
 *
 * This template can be overridden by copying it to yourtheme/ultimate-member/um-recaptcha/captcha-v3.php
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$recaptcha_mode = empty( $args['mode'] ) ? 'homepage' : $args['mode'];
?>

<div class="g-recaptcha" id="um-<?php echo esc_attr( $args['form_id'] ); ?>" data-mode="<?php echo esc_attr( $recaptcha_mode ); ?>"></div>

<?php
if ( UM()->form()->has_error( 'recaptcha' ) ) {
	?>

	<div class="um-field-error"><?php echo esc_html( UM()->form()->errors['recaptcha'] ); ?></div>

	<?php
}
