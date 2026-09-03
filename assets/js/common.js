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
	datetimePicker: {
		init: function () {
			jQuery('.um-datepicker:not(.um-inited)').each(function(){
				let input = this;
				jQuery(input).addClass('um-inited');
				input.addEventListener('change', function () {
					if ( ! this.value ) {
						return;
					}

					this.setCustomValidity('');

					// Range validation for typed values (ISO dates compare safely as strings).
					if ( this.min && this.value < this.min ) {
						/* translators: %s: Date range min. */
						this.setCustomValidity( wp.i18n.sprintf( wp.i18n.__( 'Please pick a date on or after %s.', 'ultimate-member' ), this.min ) );
						this.value = '';
						this.reportValidity();
						return;
					}
					if ( this.max && this.value > this.max ) {
						/* translators: %s: Date range max. */
						this.setCustomValidity( wp.i18n.sprintf( wp.i18n.__( 'Please pick a date on or before %s.', 'ultimate-member' ), this.max ) );
						this.value = '';
						this.reportValidity();
						return;
					}

					let disabled = JSON.parse(this.dataset.disabled_weekdays || '[]'); // options used 1=Sun..7=Sat
					let day = new Date(this.value + 'T00:00:00').getDay(); // this function returns 0=Sun..6=Sat
					if (disabled.includes( day + 1 )) {
						this.setCustomValidity(wp.i18n.__( 'This day is not selectable.', 'ultimate-member' ) );
						this.value = '';
						this.reportValidity();
					} else {
						this.setCustomValidity('');
					}
				});
			});

			jQuery('.um-timepicker:not(.um-inited)').each(function(){
				let input = this;
				jQuery(input).addClass('um-inited');
				input.addEventListener('change', function () {
					if ( ! this.value ) {
						return;
					}
					let stepSec = parseInt(this.step, 10) || 60;
					let [h, m] = this.value.split(':').map(Number);
					let totalSec = (h * 3600) + (m * 60);
					if (totalSec % stepSec !== 0) {
						this.setCustomValidity( wp.i18n.__( 'Please pick a time on the allowed interval.', 'ultimate-member' ) );
						this.reportValidity();
					} else {
						this.setCustomValidity('');
					}
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
		vanillaSerialize: function ( formID ) {
			let form = document.querySelector('#' + formID);
			let data = new FormData( form );

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
		}
	}
}

jQuery(document).on( 'ajaxStart', function() {
	UM.common.tipsy.hide();
});

jQuery(document).on( 'ajaxSuccess', function() {
	UM.common.tipsy.init();
});

jQuery(document).ready(function() {
	UM.common.tipsy.init();
	UM.common.datetimePicker.init();
});
