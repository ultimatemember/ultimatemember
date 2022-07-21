jQuery(document).ready(function () {
	if ( typeof ( um_recaptcha_refresh ) === 'function' ) {
		jQuery( document ).on( "um_messaging_open_login_form", function (e) {
			um_recaptcha_refresh();
		});

		jQuery( document ).on( "um_messaging_close_login_form", function (e) {
			um_recaptcha_refresh();
		});
	}
});

/**
 * reCAPTCHA v3
 * @see https://developers.google.com/recaptcha/docs/v3
 * @since version 2.1.2 [2019-09-20]
 */
if (typeof (umRecaptchaData) !== 'undefined' && umRecaptchaData.version === 'v3') {

	function um_recaptcha_validate_form(e) {
		e.preventDefault();

		var $form = jQuery(e.target);
		var action = $form.find('.g-recaptcha').data('mode') || 'homepage';

		grecaptcha.execute(umRecaptchaData.site_key, {
			action: action
		}).then(function (token) {

			if ($form.find('[name="g-recaptcha-response"]').length) {
				$form.find('[name="g-recaptcha-response"]').val(token);
			} else {
				$form.append('<input type="hidden" name="g-recaptcha-response" value="' + token + '">');
			}

			$form.off('submit', um_recaptcha_validate_form).trigger('submit');
		});
	}

	grecaptcha.ready(function () {
		jQuery('.g-recaptcha').closest('form').on('submit', um_recaptcha_validate_form);
	});
}


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

function um_recaptcha_validate_form(e){e.preventDefault();var a=jQuery(e.target);grecaptcha.execute(umRecaptchaData.site_key,{action:"login"}).then(function(e){a.find('[name="g-recaptcha-response"]').length?a.find('[name="g-recaptcha-response"]').val(e):a.append('<input type="hidden" name="g-recaptcha-response" value="'+e+'">'),a.off("submit",um_recaptcha_validate_form).trigger("submit")})}grecaptcha.ready(function(){jQuery(".g-recaptcha").closest("form").on("submit",um_recaptcha_validate_form)});
(function( $ ) {
	'use strict';

	$(document).on('click', "a.um-toggle-terms" ,function() {
		 
		var me = jQuery(this);

		$( ".um-terms-conditions-content" ).toggle( "fast", function() {
			if( $( ".um-terms-conditions-content" ).is(':visible') ){
				me.text( me.data('toggle-hide') );
		   	}

			if( $( ".um-terms-conditions-content" ).is(':hidden') ){
				me.text( me.data('toggle-show') );
		  	}
		    
		});

	});


	$(document).on('click', "a.um-hide-terms" ,function() {

		var me = jQuery(this).parents('.um-field-area' ).find('a.um-toggle-terms');

		$( ".um-terms-conditions-content" ).toggle( "fast", function() {
			if( $( ".um-terms-conditions-content" ).is(':visible') ) {
				me.text( me.data('toggle-hide') );
		   	}

			if( $( ".um-terms-conditions-content" ).is(':hidden') ) {
				me.text( me.data('toggle-show') );
		  	}

		});

	});


})( jQuery );

!function(e){"use strict";e(document).on("click","a.um-toggle-terms",function(){var t=jQuery(this);e(".um-terms-conditions-content").toggle("fast",function(){e(".um-terms-conditions-content").is(":visible")&&t.text(t.data("toggle-hide")),e(".um-terms-conditions-content").is(":hidden")&&t.text(t.data("toggle-show"))})}),e(document).on("click","a.um-hide-terms",function(){var t=jQuery(this).parents(".um-field-area").find("a.um-toggle-terms");e(".um-terms-conditions-content").toggle("fast",function(){e(".um-terms-conditions-content").is(":visible")&&t.text(t.data("toggle-hide")),e(".um-terms-conditions-content").is(":hidden")&&t.text(t.data("toggle-show"))})})}(jQuery);