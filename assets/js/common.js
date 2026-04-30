// Custom jQuery functions.
jQuery.fn.extend({
	umShow: function() {
		return this.each(function() {
			jQuery(this).removeClass( 'um-display-none' );
		});
	},
	umHide: function() {
		return this.each(function() {
			jQuery(this).addClass( 'um-display-none' );
		});
	},
	umToggle: function() {
		return this.each(function() {
			jQuery(this).toggleClass( 'um-display-none' );
		});
	}
});

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
				jQuery('.um-page .tipsy').remove();
			}
		}
	},
	rating: {
		init: function () {
			if ( 'function' === typeof( jQuery.fn.um_raty ) ) {
				if ( jQuery('.um-rating').length ) {
					jQuery('.um-rating').um_raty({
						half:       false,
						starType:   'i',
						number:     function() {
							return jQuery(this).attr('data-number');
						},
						score:      function() {
							return jQuery(this).attr('data-score');
						},
						scoreName:  function() {
							return jQuery(this).attr('data-key');
						},
						hints:      false,
						click:      function( score, evt ) {
							um_live_field = this.id;
							um_live_value = score;
							// @todo make condition logic here
							// um_apply_conditions( jQuery(this), false );
						}
					});
				}
				if ( jQuery('.um-rating-readonly').length ) {
					jQuery('.um-rating-readonly').um_raty({
						half:       false,
						starType:   'i',
						number:     function() {
							return jQuery(this).attr('data-number');
						},
						score:      function() {
							return jQuery(this).attr('data-score');
						},
						scoreName:  function() {
							return jQuery(this).attr('data-key');
						},
						hints:      false,
						readOnly:   true
					});
				}
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
					onOpen: function() {
						elem.blur();
						if ( elem.parents('body').hasClass('wp-admin') ) {
							elem.siblings('.picker').find('.picker__button--close').addClass('button')
						}
					},
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
	},
	select: {
		isSelected: function( selected, current ){
			if ( selected === current ) {
				return ' selected="selected"';
			}
			return "";
		}
	},
	form: {
		vanillaSerialize: function ( form ) {
			let formObj;
			if (typeof form === "string") {
				formObj = document.querySelector('#' + form);
			} else {
				formObj = form[0];
			}
			let data = new FormData( formObj );

			let obj = {};
			for (let [key, value] of data) {
				if (obj[key] !== undefined) {
					if (!Array.isArray(obj[key])) {
						obj[key] = [obj[key]];
					}
					obj[key].push(value);
				} else {
					obj[key] = value;
				}
			}

			return obj;
		},
		sanitizeValue: function ( value, el ) {
			let element = document.createElement( 'div' );
			element.innerText = value;
			let sanitized_value = element.innerHTML;
			if ( el ) {
				jQuery( el ).val( sanitized_value );
			}
			return sanitized_value;
		},
		unsanitizeValue: function( input ) {
			let e = document.createElement( 'textarea' );
			e.innerHTML = input;
			// handle case of empty input
			return e.childNodes.length === 0 ? "" : e.childNodes[0].nodeValue;
		},
		messageTimeout: function( wrapper, message, timeout = 1000, callback = null ) {
			wrapper.html( message ).umShow();

			if ( callback ) {
				callback( wrapper );
			}

			setTimeout(() => {
				wrapper.html( '' ).umHide().removeClass( ['um-error-text','um-success-text'] );
			}, timeout );
		}
	}
}

jQuery(document).on( 'ajaxStart', function() {
	UM.common.tipsy.hide();
});

jQuery(document).on( 'ajaxSuccess', function() {
	UM.common.tipsy.init();
	UM.common.rating.init();
});

jQuery(document).ready(function() {
	UM.common.tipsy.init();
	UM.common.rating.init();
	UM.common.datetimePicker.init();
});
