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
});
