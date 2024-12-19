wp.hooks.addFilter( 'um_toggle_block', 'um_gdpr', function( $toggleBlock, $toggleButton ) {
	if ( $toggleButton.hasClass( 'um-hide-gdpr' ) ) {
		// Change toggle text.
		let textAfter  = $toggleButton.data( 'toggle-text' );
		let textBefore = $toggleButton.text();
		jQuery('.um-hide-gdpr').data( 'toggle-text',textBefore );
		jQuery('.um-hide-gdpr').text( textAfter );
		jQuery('.um-hide-gdpr-second-button').umToggle();
	}

	return $toggleBlock;
});
