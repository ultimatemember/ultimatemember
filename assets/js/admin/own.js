function um_admin_init_tipsy() {
    if ( typeof( jQuery.fn.tipsy ) === 'function' ) {
		jQuery('.um-admin-tipsy-n').tipsy({gravity: 'n', opacity: 1, live: 'a.live' });
		jQuery('.um-admin-tipsy-w').tipsy({gravity: 'w', opacity: 1, live: 'a.live' });
		jQuery('.um-admin-tipsy-e').tipsy({gravity: 'e', opacity: 1, live: 'a.live' });
		jQuery('.um-admin-tipsy-s').tipsy({gravity: 's', opacity: 1, live: 'a.live' });
	}
}


function um_admin_init_colorpicker() {
	/**
	 WP Color Picker
	 **/
	if ( jQuery('.um-admin-colorpicker').length ) {
		jQuery('.um-admin-colorpicker').wpColorPicker();
	}
}


function um_admin_init_icon_select() {
	if ( jQuery('.um-icon-select-field').length ) {

		function iformat( icon ) {
			var originalOption = icon.element;
			if ( 'undefined' !== typeof originalOption ) {
				return jQuery('<span><i class="' + jQuery( originalOption ).val() + '"></i> ' + icon.text + '</span>');
			} else {
				return jQuery('<span><i class="' + icon.id + '"></i> ' + icon.text + '</span>');
			}
		}

		var select2_atts = {
			ajax: {
				url: wp.ajax.settings.url,
					dataType: 'json',
					delay: 250, // delay in ms while typing when to perform a AJAX search
					data: function( params ) {
					return {
						search: params.term, // search query
						action: 'um_get_icons', // AJAX action for admin-ajax.php
						page: params.page || 1, // infinite scroll pagination
						nonce: um_admin_scripts.nonce
					};
				},
				processResults: function( response, params ) {
					params.page = params.page || 1;
					var options = [];

					if ( response.data.icons ) {
						// data is the array of arrays, and each of them contains ID and the Label of the option
						jQuery.each( response.data.icons, function( index, text ) {
							options.push( { id: index, text: text.label } );
						});
					}

					return {
						results: options,
						pagination: {
							more: ( params.page * 50 ) < response.data.total_count
						}
					};
				},
				cache: true
			},
			minimumInputLength: 0, // the minimum of symbols to input before perform a search
			allowClear: true,
			width: "100%",
			allowHtml: true,
			templateSelection: iformat,
			templateResult: iformat,
			dropdownCssClass: 'um-select2-icon-dropdown',
			containerCssClass : 'um-select2-icon-container'
		};

		if ( jQuery('.um-icon-select-field').parents('.um-admin-tri').length ) {
			select2_atts.dropdownParent = jQuery('.um-icon-select-field').parents('.um-admin-tri');
		}

		jQuery('.um-icon-select-field').select2( select2_atts );
	}
}


jQuery(document).ready(function() {

	um_admin_init_colorpicker();

	um_admin_init_tipsy();

	jQuery(document).ajaxStart( function() {
		jQuery('.tipsy').hide();
	});

	um_admin_init_icon_select();

	if ( jQuery('.wp-submenu a[href="https://ultimatemember.com/pricing"]').length ) {
		jQuery('.wp-submenu a[href="https://ultimatemember.com/pricing"]').attr('target', '_blank');
	}
});
