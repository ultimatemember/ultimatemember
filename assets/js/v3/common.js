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
	choices: {
		optionsCache: {},
		init: function () {
			const self = this;
			self.choicesInstances = {};

			jQuery('.js-choice').each( function() {
				if ( 'active' === jQuery(this).attr('data-choice') ){
					return;
				}

				let element = jQuery(this)[0];
				let choices = null;
				let attrs = {};
				// @todo https://github.com/Choices-js/Choices/issues/747 maybe add native "clear all" button in the future
				if ( jQuery(this).attr( 'multiple' ) ) {
					// @todo https://github.com/Choices-js/Choices/issues/1066 , but it works properly on backend validation
					let minSelections = jQuery(this).data( 'min_selections' );

					let maxSelections = jQuery(this).data( 'max_selections' );
					attrs = {removeItemButton: true};

					if ( maxSelections ) {
						attrs.maxItemCount = maxSelections;
					}

				} else if ( jQuery(this).hasClass( 'um-no-search' ) ) {
					attrs = { searchEnabled: false };
				}

				choices = new Choices(element, attrs);
				self.choicesInstances[ element.id ] = choices;

				// Workaround for form reset https://github.com/Choices-js/Choices/issues/1053#issuecomment-1810488521
				const form = jQuery(this).closest('form')[0];
				if ( ! form ) {
					return;
				}

				form.addEventListener( 'reset', () => {
					choices.destroy();
					choices.init();
				});
			});
		},
		initChild: function() {
			let $childDropdown = jQuery('select[data-um-parent]');
			if ( $childDropdown.length ) {
				/**
				 * Find all select fields with parent select fields
				 */
				$childDropdown.each( function() {
					let me = jQuery(this);
					let parentOption = me.data('um-parent');
					let childCallback = me.data('um-ajax-source');
					let nonce = me.data('nonce');

					jQuery(document.body).on('change','select[name="' + parentOption + '"], select[name="' + parentOption + '[]"]',function() {
						let parent  = jQuery(this);
						let form_id = parent.closest( 'form' ).find( 'input[type="hidden"][name="form_id"]' ).val();
						let arr_key = parent.val();
						arr_key = wp.hooks.applyFilters( 'um_common_child_dropdown_loop_parent_value', arr_key, me, parent );

						if ( typeof arr_key !== 'undefined' && arr_key !== '' && typeof UM.common.choices.optionsCache[ arr_key ] !== 'object' ) {
							if ( typeof( me.um_wait ) === 'undefined' || me.um_wait === false ) {
								me.um_wait = true;
							} else {
								return;
							}

							let optionsRequestData = {
								parent_option_name: parentOption,
								parent_option: arr_key,
								child_callback: childCallback,
								child_name: me.attr('name'),
								form_id: form_id,
								nonce: nonce
							};
							optionsRequestData = wp.hooks.applyFilters( 'um_common_child_dropdown_child_options_request', optionsRequestData, me, parent );

							wp.ajax.send(
								'um_select_options',
								{
									data: optionsRequestData,
									success: function( data ) {
										UM.common.choices.optionsCache[ arr_key ] = data;
										UM.common.choices.populateChildOptions( me.attr('id'), data );

										if ( typeof data.debug !== 'undefined' ) {
											console.log( data );
										}

										me.um_wait = false;
									},
									error: function( e ) {
										console.log( e );
										me.um_wait = false;
									}
								}
							);
						} else {
							setTimeout( UM.common.choices.populateChildOptions, 10, me.attr('id'), UM.common.choices.optionsCache[ arr_key ] );
						}
					});

					wp.hooks.doAction( 'um_after_init_child_loop', parentOption, me );
				});
			}
		},
		updateOptions: function (selector, newOptions, reset = false) {
			const element = jQuery('#' + selector);
			if (element && this.choicesInstances[ selector ]) {
				const choices = this.choicesInstances[ selector ];
				if ( true === reset ) {
					choices.removeActiveItems();
				} else {
					choices.clearStore();
					choices.clearChoices();
					choices.removeActiveItems();
					if ( newOptions.length === 0 ) {
						if ( ! element.parents( '.um-directory' ).length ) {
							// Fallback when empty items, but not on the member directory (just on the UM forms).
							choices.setChoices([{id: '', label: wp.i18n.__( 'None', 'ultimate-member' ), placeholder: true, selected: true}], 'id', 'label', true);
						}
						choices.disable();
					} else {
						choices.setChoices(newOptions, 'id', 'label', true);
						choices.enable();
					}
				}
			}
		},
		/**
		 * Populates child options and cache AJAX response
		 *
		 * @param selector
		 * @param data
		 */
		populateChildOptions: function ( selector, data ) {
			let me = jQuery( '#' + selector );
			me.find('option[value!=""]').remove();

			if ( ! me.hasClass('um-child-option-disabled') ) {
				me.prop('disabled', false);
			}

			let arr_items = [];
			// let arr_items = [],
			// 	search_get = '';
			// if ( me.attr('data-um-original-value') ) {
			// 	search_get = me.attr('data-um-original-value');
			// }
			if ( typeof data !== 'undefined' && data.items ) {
				jQuery.each(data.items, function (k, v) {
					// arr_items.push({id: k, label: v, selected: false});
					if ( '' !== k ) {
						arr_items.push({id: k, label: v, selected: false});
					} else {
						// placeholder
						arr_items.push({id: '', label: v, placeholder: true, selected: true});
					}

					// if ( 0 !== parseInt( k ) ) {
					// arr_items.push({id: k, text: v, selected: (v === search_get)});
					// }
				});
			}

			UM.common.choices.updateOptions(selector, arr_items);

			let actionInFilter = wp.hooks.applyFilters( 'um_populate_child_options', null, me, selector, data, arr_items );
			if ( null === actionInFilter ) {
				if ( typeof data !== 'undefined' && typeof data.field !== 'undefined' ) {
					// @todo We need to close a lack of logic when child default value isn't situated in the diapason for default parent value.
					if ( typeof data.field.default !== 'undefined' ) {
						me.val( data.field.default ).trigger('change');
					}

					// @todo maybe not editable field cannot be changed from callback. And probably we need to close a lack of logic when parent is editable, but child isn't.
					if ( data.field.editable == 0 ) {
						me.addClass('um-child-option-disabled');
						me.attr('disabled','disabled');
					}
				}
			}
		}
	}
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
	UM.common.choices.init();
	UM.common.choices.initChild();
});