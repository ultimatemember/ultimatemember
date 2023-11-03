if ( typeof (window.UM) !== 'object' ) {
	window.UM = {};
}

if ( typeof (window.UM.admin) !== 'object' ) {
	window.UM.admin = {};
}

UM.admin = {
	tooltip: {
		all: null,
		init: function() {
			let $tooltip = jQuery( '.um_tooltip' );
			if ( $tooltip.length > 0 ) {
				UM.admin.tooltip.all = $tooltip.tooltip({
					tooltipClass: "um_tooltip",
					content: function () {
						return jQuery( this ).attr( 'title' );
					}
				});
			}
		},
		close: function () {
			if ( null !== UM.admin.tooltip.all && UM.admin.tooltip.all > 0 && 'function' === typeof UM.admin.tooltip.all.tooltip ) {
				UM.admin.tooltip.all.tooltip('close');
			}
		}
	},
	colorPicker: {
		init: function () {
			let $colorPicker = jQuery('.um-admin-colorpicker');
			if ( $colorPicker.length ) {
				$colorPicker.wpColorPicker();
			}
		}
	},
	datetimePicker: {
		init: function () {
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
	}
}

jQuery(document).ready(function() {
	UM.admin.tooltip.init();
	UM.admin.colorPicker.init();
	UM.admin.datetimePicker.init();
});
