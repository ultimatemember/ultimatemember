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


if ( jQuery('.um-icon-select-field').length ) {

	function iformat( icon ) {
		var originalOption = icon.element;
		return jQuery('<span><i class="' + jQuery( originalOption ).data( 'icon' ) + '"></i> ' + icon.text + '</span>');
	}

	wp.ajax.send( 'um_get_icons', {
		data: {
			nonce: um_admin_scripts.nonce
		},
		success: function( data ) {
			var options = '<option value="">' + wp.i18n.__( 'None', 'ultimate-member' ) + '</option>';
			jQuery.each( data, function( i ) {
				jQuery.each( data[ i ].styles, function( is ) {
					var style_class;
					if ( data[ i ].styles[ is ] === 'solid' ) {
						style_class = 'fas fa-';
					} else if ( data[ i ].styles[ is ] === 'regular' ) {
						style_class = 'far fa-';
					} else if ( data[ i ].styles[ is ] === 'brands' ) {
						style_class = 'fab fa-';
					}
					options += '<option data-icon="' +  style_class + i + '" value="' + style_class + i + '">' + data[ i ].label + '</option>';
				});
			});

			jQuery('.um-icon-select-field').each( function() {
				var selected = jQuery(this).data('value');
				jQuery(this).html( options ).val( selected );

				jQuery('.um-icon-select-field').select2({
					width: "100%",
					theme: "classic",
					allowHtml: true,
					templateSelection: iformat,
					templateResult: iformat,
					dropdownCssClass: 'um',
				});
			});
		},
		error: function( data ) {

		}
	});
}
