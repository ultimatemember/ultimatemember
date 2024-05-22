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
	}
}

jQuery(document).ready(function() {
	UM.admin.tooltip.init();
	UM.admin.colorPicker.init();
});
