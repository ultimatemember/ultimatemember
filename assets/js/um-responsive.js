jQuery( window ).on( 'load',function() {
	um_responsive();
	um_modal_responsive();
});

// Resize using debounce.
// * https://medium.com/geekculture/debounce-handle-browser-resize-like-a-pro-994cd522e14b
// * https://davidwalsh.name/javascript-debounce-function
jQuery(window).on( 'resize', _.debounce( function() {
	responsive_Modal();

	wp.hooks.doAction( 'um_window_resize' );

	um_responsive();
	um_modal_responsive();
}, 300 ) );

wp.hooks.addAction( 'um_admin_modal_success_result', 'um_frontend_responsive', function( $adminModal ) {
	// Make responsive script only when live preview,
	if ( $adminModal.find('.um-admin-modal-body').find('.um').length ) {
		um_responsive();
	}
});
