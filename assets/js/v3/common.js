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
	},
	slider: {
		controlFromSlider: function(fromSlider, toSlider/*, fromInput*/) {
			const [from, to] = UM.common.slider.getParsed( fromSlider, toSlider );
			UM.common.slider.fillSlider(fromSlider, toSlider, um_common_variables.colors.gray200, um_common_variables.colors.primary600bg, toSlider);
			if (from > to) {
				fromSlider.value = to;
				// fromInput.value = to;
			} else {
				// fromInput.value = from;
			}
		},
		controlToSlider: function(fromSlider, toSlider/*, toInput*/) {
			const [from, to] = UM.common.slider.getParsed(fromSlider, toSlider);
			UM.common.slider.fillSlider(fromSlider, toSlider, um_common_variables.colors.gray200, um_common_variables.colors.primary600bg, toSlider);
			UM.common.slider.setToggleAccessible(toSlider);

			if (from <= to) {
				toSlider.value = to;
				// toInput.value = to;
			} else {
				// toInput.value = from;
				toSlider.value = from;
			}
		},
		replacePlaceholder: function( currentFrom, from, to ) {
			let placeholder = currentFrom.closest( '.um-range-container' ).querySelector('.um-range-placeholder');
			if ( placeholder ) {
				if ( placeholder.dataset.placeholderS && placeholder.dataset.placeholderP && placeholder.dataset.label ) {
					if ( from === to ) {
						placeholder.innerHTML = placeholder.dataset.placeholderS.replace( '\{\{\{value\}\}\}', from )
						.replace( '\{\{\{label\}\}\}', placeholder.dataset.label );
					} else {
						let placeholderFrom = from;
						let placeholderTo = to;

						if ( placeholderFrom > placeholderTo ) {
							placeholderFrom = placeholderTo;
						} else if ( placeholderTo < placeholderFrom ) {
							placeholderTo = placeholderFrom;
						}

						if ( placeholderTo === placeholderFrom ) {
							placeholder.innerHTML = placeholder.dataset.placeholderS.replace( '\{\{\{value\}\}\}', placeholderFrom )
							.replace( '\{\{\{label\}\}\}', placeholder.dataset.label );
						} else {
							placeholder.innerHTML = placeholder.dataset.placeholderP.replace( '\{\{\{value_from\}\}\}', placeholderFrom )
							.replace( '\{\{\{value_to\}\}\}', placeholderTo )
							.replace( '\{\{\{label\}\}\}', placeholder.dataset.label );
						}
					}
				}
			}
		},
		getParsed: function(currentFrom, currentTo) {
			const from = parseInt(currentFrom.value, 10);
			const to = parseInt(currentTo.value, 10);

			UM.common.slider.replacePlaceholder( currentFrom, from, to );

			return [from, to];
		},
		fillSlider: function(from, to, sliderColor, rangeColor, controlSlider) {
			const rangeDistance = to.max-to.min;
			const fromPosition = from.value - to.min;
			const toPosition = to.value - to.min;

			// Fix for blinking slider progress between switching z-index.
			const thumbWidth = 23 / ( from.offsetWidth / 100 );
			if ( 0 === fromPosition ) {
				controlSlider.style.background = `linear-gradient(
				  to right,
				  transparent 0%,
				  transparent ${thumbWidth}%,
				  ${sliderColor} ${(fromPosition)/(rangeDistance)*100}%,
				  ${rangeColor} ${((fromPosition)/(rangeDistance))*100}%,
				  ${rangeColor} ${(toPosition)/(rangeDistance)*100}%,
				  ${sliderColor} ${(toPosition)/(rangeDistance)*100}%,
				  ${sliderColor} 100%)`;
			} else {
				controlSlider.style.background = `linear-gradient(
				  to right,
				  ${sliderColor} 0%,
				  ${sliderColor} ${(fromPosition)/(rangeDistance)*100}%,
				  ${rangeColor} ${((fromPosition)/(rangeDistance))*100}%,
				  ${rangeColor} ${(toPosition)/(rangeDistance)*100}%,
				  ${sliderColor} ${(toPosition)/(rangeDistance)*100}%,
				  ${sliderColor} 100%)`;
			}
		},
		setToggleAccessible: function (currentTarget) {
			if ( Number(currentTarget.value) <= currentTarget.min ) {
				currentTarget.style.zIndex = 2;
			} else {
				currentTarget.style.zIndex = 0;
			}
		},

		init: function () {
			jQuery('.um-range-container').each( function() {
				const fromSlider = jQuery(this).find('.um-from-slider')[0];
				const toSlider = jQuery(this).find('.um-to-slider')[0];
				const controlSlider = jQuery(this).find('.um-sliders-control')[0];
				if ( fromSlider && toSlider ) {
					UM.common.slider.fillSlider(fromSlider, toSlider, um_common_variables.colors.gray200, um_common_variables.colors.primary600bg, toSlider);
					UM.common.slider.setToggleAccessible(toSlider);

					fromSlider.oninput = () => UM.common.slider.controlFromSlider(fromSlider, toSlider/*, fromInput*/);
					toSlider.oninput = () => UM.common.slider.controlToSlider(fromSlider, toSlider/*, toInput*/);
				}

				if ( controlSlider ) {
					controlSlider.addEventListener('mouseover', function() {
						UM.common.slider.fillSlider(fromSlider, toSlider, um_common_variables.colors.gray300, um_common_variables.colors.primary700bg, toSlider);
					});

					controlSlider.addEventListener('mouseout', function() {
						UM.common.slider.fillSlider(fromSlider, toSlider, um_common_variables.colors.gray200, um_common_variables.colors.primary600bg, toSlider);
					});

					const sliderForm = controlSlider.closest('form');
					if ( sliderForm ) {
						sliderForm.addEventListener('reset', function() {
							setTimeout(function() {

								// executes after the form has been reset. Reset need 1 second time.
								UM.common.slider.fillSlider(fromSlider, toSlider, um_common_variables.colors.gray200, um_common_variables.colors.primary600bg, toSlider);
								UM.common.slider.replacePlaceholder( fromSlider, fromSlider.value, toSlider.value );
							}, 1);
						});
					}
				}
			});
		}
	},
}

jQuery(document).on( 'ajaxStart', function() {
	UM.common.tipsy.hide();
});

jQuery(document).on( 'ajaxSuccess', function() {
	UM.common.tipsy.init();
	UM.common.rating.init();
	UM.common.slider.init();
});

jQuery(document).ready(function() {
	UM.common.tipsy.init();
	UM.common.rating.init();
	UM.common.slider.init();
});
