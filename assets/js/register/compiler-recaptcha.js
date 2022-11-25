// reCAPTCHA.js - loaded when recaptcha module is active

// common.js
if ( typeof ( window.UM ) !== 'object' ) {
	window.UM = {};
}

if ( typeof ( UM.common ) !== 'object' ) {
	UM.common = {};
}

UM.common = {
	responsive: {
		resolutions: { //important order by ASC
			xs: 320,
			s:  576,
			m:  768,
			l:  992,
			xl: 1024
		},
		getSize: function( number ) {
			let responsive = UM.common.responsive;
			for ( let key in responsive.resolutions ) {
				if ( responsive.resolutions.hasOwnProperty( key ) && responsive.resolutions[ key ] === number ) {
					return key;
				}
			}

			return false;
		},
		setClass: function() {
			let responsive = UM.common.responsive;
			let $resolutions = Object.values( responsive.resolutions );
			$resolutions.sort( function(a, b){ return b-a; });

			jQuery('.um').each( function() {
				let obj = jQuery(this);
				let element_width = obj.outerWidth();

				jQuery.each( $resolutions, function( index ) {
					let $class = responsive.getSize( $resolutions[ index ] );
					obj.removeClass('um-ui-' + $class );
				});

				jQuery.each( $resolutions, function( index ) {
					let $class = responsive.getSize( $resolutions[ index ] );

					if ( element_width >= $resolutions[ index ] ) {
						obj.addClass('um-ui-' + $class );
						return false;
					} else if ( $class === 'xs' && element_width <= $resolutions[ index ] ) {
						obj.addClass('um-ui-' + $class );
						return false;
					}
				});
			});
		}
	},
};


jQuery.ajaxSetup({
	beforeSend: function( jqXHR, settings ) {
		if ( settings.processData ) {
			if ( settings.data !== '' ) {
				settings.data += '&um_current_locale=' + um_common_variables.locale;
			} else {
				settings.data = 'um_current_locale=' + um_common_variables.locale;
			}
		} else {
			settings.data = jQuery.extend(
				settings.data,
				{
					um_current_locale: um_common_variables.locale
				}
			);
		}

		return true;
	}
});

jQuery( document ).ready( function($) {
	$( window ).on( 'resize', function() {
		UM.common.responsive.setClass();
	});
});

jQuery( window ).on( 'load', function() {
	UM.common.responsive.setClass();
});


// forms.js
if ( typeof ( window.UM ) !== 'object' ) {
	window.UM = {};
}

if ( typeof ( UM.forms ) !== 'object' ) {
	UM.forms = {};
}

UM.forms = {
	honeypot: function () {
		// flush fields using honeypot security
		jQuery('input[name="' + umRegister.honeypot + '"]').val('');
	}
};

jQuery( window ).on( 'load', function() {
	UM.forms.honeypot();
});


// modules/recaptcha/assets/js/password-reset.js
/**
 * reCAPTCHA v3
 * @see https://developers.google.com/recaptcha/docs/v3
 * @since version 2.1.2 [2019-09-20]
 */
if ( typeof ( window.UM ) !== 'object' ) {
	window.UM = {};
}

if ( typeof ( UM.forms ) !== 'object' ) {
	UM.forms = {};
}

UM.forms.recaptcha = {
	validateForm: function(e) {
		e.preventDefault();

		let $form  = jQuery( e.target );
		let action = $form.find('.g-recaptcha').data('mode') || 'homepage';

		grecaptcha.execute( umRegister.recaptcha.site_key, {
			action: action
		}).then( function( token ) {

			if ( $form.find('[name="g-recaptcha-response"]').length ) {
				$form.find('[name="g-recaptcha-response"]').val( token );
			} else {
				$form.append( '<input type="hidden" name="g-recaptcha-response" value="' + token + '">' );
			}

			$form.off( 'submit', UM.forms.recaptcha.validateForm ).trigger('submit');
		});
	}/*,
	refresh: function() {

	}*/
};

if ( typeof( umRegister.recaptcha ) !== 'undefined' && 'v3' === umRegister.recaptcha.version ) {
	grecaptcha.ready( function() {
		jQuery('.g-recaptcha').closest('form').on( 'submit', UM.forms.recaptcha.validateForm );
	});
} else if ( typeof( umRegister.recaptcha ) !== 'undefined' && 'v2' === umRegister.recaptcha.version ) {
	if ( 'invisible' === umRegister.recaptcha.size ) {
		var umRegisterRecaptchaCallback = function() {
			grecaptcha.render( 'um-new-password', {
				'sitekey': umRegister.recaptcha.site_key,
				'callback': function( token ) {
					jQuery('#um-lostpassword').attr('disabled', 'disabled').submit();
				}
			});
		};

		// UM.forms.recaptcha.refresh = function() {
		// 	grecaptcha.reset();
		// 	onloadCallback();
		// };

		jQuery(document).ready( function() {
			jQuery('#um-new-password').addClass('um-has-recaptcha');
		});
	} else {
		var umRegisterRecaptchaCallback = function() {
			jQuery('.g-recaptcha').each( function(i) {
				grecaptcha.render( jQuery(this).attr('id'), {
					'sitekey': jQuery(this).attr('data-sitekey'),
					'theme': jQuery(this).attr('data-theme')
				});
			});
		};

		// UM.forms.recaptcha.refresh = function() {
		// 	jQuery('.g-recaptcha').html('');
		// 	grecaptcha.reset();
		// 	onloadCallback();
		// };
	}
}
