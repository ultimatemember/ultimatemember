/**
 *
 */
function um_responsive() {

	jQuery('.um').each( function() {

		element_width = jQuery(this).width();

		jQuery(this).removeClass('uimob340');
		jQuery(this).removeClass('uimob500');
		jQuery(this).removeClass('uimob800');
		jQuery(this).removeClass('uimob960');

		if ( element_width <= 340 ) {

			jQuery(this).addClass('uimob340');

		} else if ( element_width <= 500 ) {

			jQuery(this).addClass('uimob500');

		} else if ( element_width <= 800 ) {

			jQuery(this).addClass('uimob800');

		} else if ( element_width <= 960 ) {

			jQuery(this).addClass('uimob960');

		}

		if ( jQuery('.um-account-nav').length > 0 && jQuery('.um-account-side').is(':visible') && jQuery('.um-account-tab:visible').length == 0 ) {
			jQuery('.um-account-side li a.current').trigger('click');
		}

		jQuery(this).css( 'opacity', 1 );
	});

	jQuery('.um-cover, .um-member-cover, .um-cover-e').each( function(){

		var elem = jQuery(this);
		var ratio = elem.data('ratio');
		var width = elem.width();
		var ratios = ratio.split(':');

		calcHeight = Math.round( width / ratios[0] ) + 'px';
		elem.height( calcHeight );
		elem.find('.um-cover-add').height( calcHeight );

	});

	jQuery( document ).trigger( 'um_responsive_event' );
}

jQuery( window ).load( function() {
	um_responsive();
	//um_modal_responsive();
});

jQuery( window ).resize( function() {
	responsive_Modal();

	jQuery( 'img.cropper-hidden' ).cropper( 'destroy' );

	um_responsive();
	//um_modal_responsive();
});