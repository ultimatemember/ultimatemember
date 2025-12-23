wp.hooks.addFilter( 'um_toggle_block', 'um_gdpr', function( $toggleBlock, $toggleButton ) {
	if ( $toggleButton.hasClass( 'um-hide-gdpr' ) ) {
		// Change toggle block form-wide.
		$toggleBlock = $toggleButton.parents('form').find( $toggleButton.data('um-toggle') );
		// Change toggle text.
		let textAfter  = $toggleButton.data( 'toggle-text' );
		let textBefore = $toggleButton.text();
		$toggleButton.parents('form').find('.um-hide-gdpr').data( 'toggle-text',textBefore ).text( textAfter );
	}

	return $toggleBlock;
});
