if ( typeof ( window.UM ) !== 'object' ) {
	window.UM = {};
}

if ( typeof ( UM.common ) !== 'object' ) {
	UM.common = {};
}

UM.common = {
	tipsy: {
		init: function () {
			if ( 'function' === typeof( jQuery.fn.tipsy ) ) {
				jQuery('.um-tip-n').tipsy({gravity: 'n', opacity: 1, live: 'a.live', offset: 3 });
				jQuery('.um-tip-w').tipsy({gravity: 'w', opacity: 1, live: 'a.live', offset: 3 });
				jQuery('.um-tip-e').tipsy({gravity: 'e', opacity: 1, live: 'a.live', offset: 3 });
				jQuery('.um-tip-s').tipsy({gravity: 's', opacity: 1, live: 'a.live', offset: 3 });
			}
		},
		hide: function () {
			if ( 'function' === typeof( jQuery.fn.tipsy ) ) {
				jQuery('.um-tip-n').tipsy('hide');
				jQuery('.um-tip-w').tipsy('hide');
				jQuery('.um-tip-e').tipsy('hide');
				jQuery('.um-tip-s').tipsy('hide');
				jQuery('.um .tipsy').remove();
			}
		}
	}
}

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

jQuery(document).on( 'ajaxStart', function() {
	UM.common.tipsy.hide();
});

jQuery(document).on( 'ajaxSuccess', function() {
	UM.common.tipsy.init();
});

jQuery(document).ready(function() {
	UM.common.tipsy.init();
});
