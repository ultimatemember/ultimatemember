function um_admin_init_datetimepicker() {
	jQuery('.um-datepicker:not(.picker__input)').each(function(){
		elem = jQuery(this);

		if ( typeof elem.attr('data-disabled_weekdays') != 'undefined' && elem.attr('data-disabled_weekdays') != '' ) {
			var disable = JSON.parse( elem.attr('data-disabled_weekdays') );
		} else {
			var disable = false;
		}

		var years_n = null;
		if ( typeof elem.attr('data-years') != 'undefined' ) {
			years_n = elem.attr('data-years');
		}

		var minRange = elem.attr('data-date_min');
		var maxRange = elem.attr('data-date_max');

		var minSplit = [], maxSplit = [];
		if ( typeof minRange != 'undefined' ) {
			minSplit = minRange.split(",");
		}
		if ( typeof maxRange != 'undefined' ) {
			maxSplit = maxRange.split(",");
		}

		var min = minSplit.length ? new Date(minSplit) : null;
		var max = minSplit.length ? new Date(maxSplit) : null;

		// fix min date for safari
		if ( min && min.toString() == 'Invalid Date' && minSplit.length == 3 ) {
			var minDateString = minSplit[1] + '/' + minSplit[2] + '/' + minSplit[0];
			min = new Date(Date.parse(minDateString));
		}

		// fix max date for safari
		if ( max && max.toString() == 'Invalid Date' && maxSplit.length == 3 ) {
			var maxDateString = maxSplit[1] + '/' + maxSplit[2] + '/' + maxSplit[0];
			max = new Date(Date.parse(maxDateString));
		}

		var data = {
			disable: disable,
			format: elem.attr( 'data-format' ),
			formatSubmit: 'yyyy/mm/dd',
			hiddenName: true,
			onOpen: function() { elem.blur(); },
			onClose: function() { elem.blur(); }
		};

		if ( years_n !== null ) {
			data.selectYears = years_n;
		}

		if ( min !== null ) {
			data.min = min;
		}

		if ( max !== null ) {
			data.max = max;
		}

		elem.pickadate( data );
	});

	jQuery('.um-timepicker:not(.picker__input)').each(function(){
		elem = jQuery(this);

		elem.pickatime({
			format: elem.attr('data-format'),
			interval: parseInt( elem.attr('data-intervals') ),
			formatSubmit: 'HH:i',
			hiddenName: true,
			onOpen: function() { elem.blur(); },
			onClose: function() { elem.blur(); }
		});
	});
}


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
						var style_class = ''; // empty for Ionicons
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
						allowHtml: true,
						templateSelection: iformat,
						templateResult: iformat,
						dropdownCssClass: 'um-select2-icon-dropdown',
						containerCssClass : 'um-select2-icon-container',
					});
				});
			},
			error: function( data ) {

			}
		});
	}
}


jQuery(document).ready(function() {

	um_admin_init_colorpicker();

	um_admin_init_tipsy();

	jQuery(document).ajaxStart( function() {
		jQuery('.tipsy').hide();
	});

	um_admin_init_icon_select();
});
