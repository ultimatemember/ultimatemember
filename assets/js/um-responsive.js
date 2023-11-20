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

wp.hooks.addAction( 'um_admin_modal_success_result', 'um_frontend_responsive', function( $adminModal ) {
	// Make responsive script only when live preview,
	if ( $adminModal.find('.um-admin-modal-body').find('.um').length ) {
		um_responsive();
	}
});
