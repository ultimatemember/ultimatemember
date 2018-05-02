/**
 *
 */
function remove_Modal() {
	if ( jQuery('.um-popup-overlay').length ) {
		jQuery('.tipsy').remove();
		jQuery('.um-popup').empty().remove();
		jQuery('.um-popup-overlay').empty().remove();
		jQuery("body,html").css({ overflow: 'auto' });
	}
}


/**
 *
 */
function prepare_Modal() {
	if ( jQuery('.um-popup-overlay').length == 0 ) {
		jQuery('body').append('<div class="um-popup-overlay"></div><div class="um-popup"></div>');
		jQuery('.um-popup').addClass('loading');
		jQuery("body,html").css({ overflow: 'hidden' });
	}
}


/**
 *
 * @param contents
 */
function show_Modal( contents ) {
	if ( jQuery('.um-popup-overlay').length ) {
		jQuery('.um-popup').removeClass('loading').html( contents );
		jQuery('.um-tip-n').tipsy({gravity: 'n', opacity: 1, offset: 3 });
		jQuery('.um-tip-w').tipsy({gravity: 'w', opacity: 1, offset: 3 });
		jQuery('.um-tip-e').tipsy({gravity: 'e', opacity: 1, offset: 3 });
		jQuery('.um-tip-s').tipsy({gravity: 's', opacity: 1, offset: 3 });
	}
}


/**
 *
 */
function responsive_Modal() {
	if ( jQuery('.um-popup-overlay').length ) {

		ag_height = jQuery(window).height() - jQuery('.um-popup .um-popup-header').outerHeight() - jQuery('.um-popup .um-popup-footer').outerHeight() - 80;
		if ( ag_height > 350 ) {
			ag_height = 350;
		}

		if ( jQuery('.um-popup-autogrow:visible').length ) {

			jQuery('.um-popup-autogrow:visible').css({'height': ag_height + 'px'});
			jQuery('.um-popup-autogrow:visible').mCustomScrollbar({ theme:"dark-3", mouseWheelPixels:500 }).mCustomScrollbar("scrollTo", "bottom",{ scrollInertia:0} );

		} else if ( jQuery('.um-popup-autogrow2:visible').length ) {

			jQuery('.um-popup-autogrow2:visible').css({'max-height': ag_height + 'px'});
			jQuery('.um-popup-autogrow2:visible').mCustomScrollbar({ theme:"dark-3", mouseWheelPixels:500 });

		}
	}
}