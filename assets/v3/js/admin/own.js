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

function um_init_tooltips() {
	if ( jQuery( '.um-tooltip' ).length > 0 ) {
		jQuery( '.um-tooltip' ).tooltip({
			tooltipClass: "um-tooltip",
			content: function () {
				return jQuery( this ).attr( 'title' );
			}
		});
	}
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


jQuery(document).ready(function() {


	um_admin_init_colorpicker();


	/**
		Tooltips
	**/
	um_init_tooltips();

	um_admin_init_tipsy();

	jQuery(document).ajaxStart( function() {
		jQuery('.tipsy').hide();
	});

	jQuery( document.body ).on('click', 'a[data-silent_action^="um_"]', function() {
		if ( typeof jQuery(this).attr('disabled') !== 'undefined' ) {
			return false;
		}

		var in_row = '';
		var in_sub_row = '';
		var in_column = '';
		var in_group = '';

		var demon_settings = jQuery('.um-col-demon-settings');
		if ( demon_settings.data('in_column') ) {
			in_row = demon_settings.data('in_row');
			in_sub_row = demon_settings.data('in_sub_row');
			in_column = demon_settings.data('in_column');
			in_group = demon_settings.data('in_group');
		}

		var act_id = jQuery(this).data('silent_action');
		var arg1 = jQuery(this).data('arg1');
		var arg2 = jQuery(this).data('arg2');

		jQuery('.tipsy').hide();

		um_admin_remove_modal();
		jQuery.ajax({
			url: wp.ajax.settings.url,
			type: 'POST',
			data: {
				action:'um_do_ajax_action',
				act_id : act_id,
				arg1 : arg1,
				arg2 : arg2,
				in_row: in_row,
				in_sub_row: in_sub_row,
				in_column: in_column,
				in_group: in_group,
				nonce: um_admin_scripts.nonce
			},
			success: function( data ) {
				demon_settings.data('in_row', '').data('in_sub_row', '').data('in_column', '').data('in_group', '');
				um_admin_modal_responsive();
				um_admin_update_builder();
			},
			error: function( data ) {

			}
		});

		return false;
	});
});
