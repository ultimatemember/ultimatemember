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
	iconSelector: {
		init: function () {
			let $iconSelector = jQuery('.um-icon-select-field');
			if ( $iconSelector.length ) {

				function iformat( icon ) {
					let originalOption = icon.element;
					if ( 'undefined' !== typeof originalOption ) {
						return jQuery('<span><i class="' + jQuery( originalOption ).val() + '"></i> ' + icon.text + '</span>');
					} else {
						return jQuery('<span><i class="' + icon.id + '"></i> ' + icon.text + '</span>');
					}
				}

				let select2_atts = {
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

				if ( $iconSelector.parents('.um-icon-select-field-wrapper').length ) {
					select2_atts.dropdownParent = $iconSelector.parents('.um-icon-select-field-wrapper');
				}

				$iconSelector.select2( select2_atts ).on( 'change', function () {
					// handle outdated icons and remove them after select new one.
					let oldWrapper = $iconSelector.siblings('.um_admin_fonticon_wrapper');
					if ( oldWrapper.length > 0 ) {
						oldWrapper.find('.um_old_icon_field_value').val( $iconSelector.val() );
						oldWrapper.hide();
					}
				});
			}
		}
	}
}

jQuery(document).ready(function() {
	UM.admin.tooltip.init();
	UM.admin.colorPicker.init();
	UM.admin.iconSelector.init();
});
