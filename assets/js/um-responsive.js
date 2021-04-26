jQuery( window ).on( 'load',function() {
	um_responsive();
	um_modal_responsive();
});

jQuery( window ).on( 'resize', function() {
	responsive_Modal();

	jQuery('img.cropper-hidden').cropper('destroy');

	um_responsive();
	um_modal_responsive();
});

/**
 * Run responsive functions if the page loading is blocked by slow resourses
 */
jQuery( document ).on( 'ready', function () {
	setTimeout( function () {
		if ( document.readyState !== "complete" ) {
			um_responsive();
			um_modal_responsive();
		}
	}, 500 );
} );