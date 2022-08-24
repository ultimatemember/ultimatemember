if ( typeof ( window.UM ) !== 'object' ) {
	window.UM = {};
}

if ( typeof ( UM.common ) !== 'object' ) {
	UM.common = {};
}

UM.common = {
	responsive: {
		resolutions: { //important order by ASC
			xs: 320,
			s:  576,
			m:  768,
			l:  992,
			xl: 1024
		},
		getSize: function( number ) {
			let responsive = UM.common.responsive;
			for ( let key in responsive.resolutions ) {
				if ( responsive.resolutions.hasOwnProperty( key ) && responsive.resolutions[ key ] === number ) {
					return key;
				}
			}

			return false;
		},
		setClass: function() {
			let responsive = UM.common.responsive;
			let $resolutions = Object.values( responsive.resolutions );
			$resolutions.sort( function(a, b){ return b-a; });

			jQuery('.um').each( function() {
				let obj = jQuery(this);
				let element_width = obj.outerWidth();

				jQuery.each( $resolutions, function( index ) {
					let $class = responsive.getSize( $resolutions[ index ] );
					obj.removeClass('um-ui-' + $class );
				});

				jQuery.each( $resolutions, function( index ) {
					let $class = responsive.getSize( $resolutions[ index ] );

					if ( element_width >= $resolutions[ index ] ) {
						obj.addClass('um-ui-' + $class );
						return false;
					} else if ( $class === 'xs' && element_width <= $resolutions[ index ] ) {
						obj.addClass('um-ui-' + $class );
						return false;
					}
				});
			});
		}
	},
};


jQuery.ajaxSetup({
	beforeSend: function( jqXHR, settings ) {
		if ( settings.processData ) {
			if ( settings.data !== '' ) {
				settings.data += '&um_current_locale=' + um_common_variables.locale;
			} else {
				settings.data = 'um_current_locale=' + um_common_variables.locale;
			}
		} else {
			settings.data = jQuery.extend(
				settings.data,
				{
					um_current_locale: um_common_variables.locale
				}
			);
		}

		return true;
	}
});

jQuery( document ).ready( function($) {
	$( window ).on( 'resize', function() {
		UM.common.responsive.setClass();
	});
});

jQuery( window ).on( 'load', function() {
	UM.common.responsive.setClass();
});
