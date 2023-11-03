jQuery( window ).on( 'load',function() {
	um_responsive();
	um_modal_responsive();
});

jQuery(window).on( 'resize', function() {
	responsive_Modal();

	wp.hooks.doAction( 'um_window_resize' );

	um_responsive();
	um_modal_responsive();
});
