<?php
/**
 * Template for the UM Google reCAPTCHA
 *
 * Called from the um_recaptcha_add_captcha() function
 *
 * This template can be overridden by copying it to yourtheme/ultimate-member/um-recaptcha/captcha_v3.php
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mode = empty( $args['mode'] ) ? 'homepage' : $args['mode'];
?>

<div class="g-recaptcha" id="um-<?php echo esc_attr( $args['form_id'] ); ?>" data-mode="<?php echo esc_attr( $mode ); ?>"></div>

<?php if ( UM()->form()->has_error( 'recaptcha' ) ) { ?>
	<div class="um-field-error"><?php esc_html_e( UM()->form()->errors['recaptcha'] ); ?></div>
<?php }
