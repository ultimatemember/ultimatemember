<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable VariableAnalysis
// There are "undefined" variables here because they're defined in the code that includes this file as a template.
?>

<div class="g-recaptcha" id="um-login-recaptcha" data-type="<?php /** @noinspection PhpUndefinedVariableInspection */ echo esc_attr( $type ); ?>" data-size="<?php /** @noinspection PhpUndefinedVariableInspection */ echo esc_attr( $size ); ?>" data-theme="<?php /** @noinspection PhpUndefinedVariableInspection */ echo esc_attr( $theme ); ?>" data-sitekey="<?php /** @noinspection PhpUndefinedVariableInspection */ echo esc_attr( $sitekey ); ?>"></div>

<script type="text/javascript">
	<?php if ( 'invisible' === $size ) { ?>

		var onSubmit = function( token ) {
			var me = jQuery('#<?php echo esc_js( $mode ); ?>form');
			me.attr('disabled', 'disabled');
			me.submit();
		};

		var onloadCallback = function() {
			grecaptcha.render( 'wp-submit', {
				'sitekey': '<?php echo esc_js( $sitekey ); ?>',
				'callback': onSubmit
			});
		};

		function um_recaptcha_refresh() {
			grecaptcha.reset();
			onloadCallback();
		}

	<?php } else { ?>

		var onloadCallback = function() {
			jQuery('.g-recaptcha').each( function (i) {
				grecaptcha.render( jQuery(this).attr('id'), {
					'sitekey': jQuery(this).data('sitekey'),
					'theme': jQuery(this).data('theme')
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
