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
?>

<div class="um-field">
	<div class="g-recaptcha" id="um-<?php echo esc_attr( $args['form_id'] ); ?>" <?php echo esc_html( $attrs ); ?>></div>
</div>

<?php if ( UM()->form()->has_error( 'recaptcha' ) ) { ?>
	<div class="um-field-error"><?php esc_html_e( UM()->form()->errors['recaptcha'] ); ?></div>
<?php } ?>

<script type="text/javascript">
	<?php if ( 'invisible' === $options['data-size'] ) { ?>

		var onSubmit = function( token ) {
			var me = jQuery('.um-<?php echo esc_js( $args['form_id'] ); ?> form');
			me.attr('disabled', 'disabled');
			me.submit();
		};

		var onloadCallback = function() {
			grecaptcha.render('um-submit-btn', {
				'sitekey': '<?php echo esc_js( $options['data-sitekey'] ); ?>',
				'callback': onSubmit
			});
		};

		function um_recaptcha_refresh() {
			grecaptcha.reset();
			onloadCallback();
		}

		jQuery(document).ready( function() {
			jQuery('.um-<?php echo esc_js( $args['form_id'] ); ?> #um-submit-btn').addClass('um-has-recaptcha');
		});

	<?php } else { ?>

		var onloadCallback = function () {
			jQuery('.g-recaptcha').each( function (i) {
				grecaptcha.render( jQuery(this).attr('id'), {
					'sitekey': jQuery(this).attr('data-sitekey'),
					'theme': jQuery(this).attr('data-theme')
				});
			});
		};

		function um_recaptcha_refresh() {
			jQuery('.g-recaptcha').html('');
			grecaptcha.reset();
			onloadCallback();
		}

	<?php } ?>
</script>
