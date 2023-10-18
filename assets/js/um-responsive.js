jQuery( window ).on( 'load',function() {
	um_responsive();
	um_modal_responsive();
});

jQuery(window).on( 'resize', function() {
	responsive_Modal();

	if ( jQuery('.cropper-hidden').length > 0 ) {
		cropper.destroy();
	}

	um_responsive();
	um_modal_responsive();
});
