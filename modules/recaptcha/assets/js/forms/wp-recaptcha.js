/**
 * reCAPTCHA v3
 * @see https://developers.google.com/recaptcha/docs/v3
 * @since version 2.1.2 [2019-09-20]
 */
function um_recaptcha_validate_form( e ) {
	e.preventDefault();

	var $form = jQuery( e.target );

	grecaptcha.execute( umRecaptchaData.site_key, {
		action: 'login'
	}).then( function( token ) {

		if ( $form.find('[name="g-recaptcha-response"]').length ) {
			$form.find('[name="g-recaptcha-response"]').val( token );
		} else {
			$form.append('<input type="hidden" name="g-recaptcha-response" value="' + token + '">');
		}

		$form.off( 'submit', um_recaptcha_validate_form ).trigger( 'submit' );
	});
}

grecaptcha.ready( function() {
	jQuery('.g-recaptcha').closest('form').on( 'submit', um_recaptcha_validate_form );
});
