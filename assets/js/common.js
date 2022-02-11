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
