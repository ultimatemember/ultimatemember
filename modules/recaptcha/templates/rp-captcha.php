<?php
/**
 * Template for the UM Google reCAPTCHA
 *
 * Called from the um_recaptcha_add_captcha() function
 *
 * This template can be overridden by copying it to yourtheme/ultimate-member/um-recaptcha/captcha.php
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable VariableAnalysis
// There are "undefined" variables here because they're defined in the code that includes this file as a template.
?>

<div class="g-recaptcha" id="um-<?php echo esc_attr( $args['form_id'] ); ?>" data-type="<?php /** @noinspection PhpUndefinedVariableInspection */ echo esc_attr( $type ); ?>" data-size="<?php /** @noinspection PhpUndefinedVariableInspection */ echo esc_attr( $size ); ?>" data-theme="<?php /** @noinspection PhpUndefinedVariableInspection */ echo esc_attr( $theme ); ?>" data-sitekey="<?php /** @noinspection PhpUndefinedVariableInspection */ echo esc_attr( $sitekey ); ?>"></div>
