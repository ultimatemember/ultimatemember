function um_responsive(){
	
	/* responsive layout */
	jQuery('.um').each(function(){
	
		element_width = jQuery(this).width();
		
		if ( element_width <= 500 ) {
		
			jQuery(this).removeClass('uimob500');
			jQuery(this).removeClass('uimob800');
			jQuery(this).addClass('uimob500');

		} else if ( element_width <= 800 ) {
			
			jQuery(this).removeClass('uimob500');
			jQuery(this).removeClass('uimob800');
			jQuery(this).addClass('uimob800');

		} else if ( element_width > 800 ) {
		
			jQuery(this).removeClass('uimob500');
			jQuery(this).removeClass('uimob800');
			
		}

	});
	
}

/* Run only when window is loaded */
jQuery(window).load(function() {

	um_responsive();

});

/* Run only when window is resized */
jQuery(window).resize(function() {

	um_responsive();

});