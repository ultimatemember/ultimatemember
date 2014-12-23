function um_responsive(){

	jQuery('.um').each(function(){
	
		element_width = jQuery(this).width();

		if ( element_width <= 340 ) {
		
			jQuery(this).removeClass('uimob340');
			jQuery(this).removeClass('uimob500');
			jQuery(this).removeClass('uimob800');
			jQuery(this).removeClass('uimob960');
			
			jQuery(this).addClass('uimob340');

		} else if ( element_width <= 500 ) {
			
			jQuery(this).removeClass('uimob340');
			jQuery(this).removeClass('uimob500');
			jQuery(this).removeClass('uimob800');
			jQuery(this).removeClass('uimob960');
			
			jQuery(this).addClass('uimob500');

		} else if ( element_width <= 800 ) {
			
			jQuery(this).removeClass('uimob340');
			jQuery(this).removeClass('uimob500');
			jQuery(this).removeClass('uimob800');
			jQuery(this).removeClass('uimob960');
			
			jQuery(this).addClass('uimob800');

		} else if ( element_width <= 960 ) {
			
			jQuery(this).removeClass('uimob340');
			jQuery(this).removeClass('uimob500');
			jQuery(this).removeClass('uimob800');
			jQuery(this).removeClass('uimob960');
			
			jQuery(this).addClass('uimob960');

		} else if ( element_width > 960 ) {
		
			jQuery(this).removeClass('uimob340');
			jQuery(this).removeClass('uimob500');
			jQuery(this).removeClass('uimob800');
			jQuery(this).removeClass('uimob960');

		}

		jQuery('.um-members').each(function(){
			UM_Member_Grid( jQuery(this) );
		});
		
		if (  jQuery('.um-account-nav').length > 0 && jQuery('.um-account-side').is(':visible') && jQuery('.um-account-tab:visible').length == 0 ) {
			jQuery('.um-account-side li a.current').trigger('click');
		}
		
		jQuery(this).css('opacity',1);
	
	});
	
}

/* Run only when window is loaded */
jQuery(window).load(function() {

	um_responsive();
	um_modal_responsive();

});

/* Run only when window is resized */
jQuery(window).resize(function() {

	um_responsive();
	um_modal_responsive();

});