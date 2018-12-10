jQuery( document ).ready( function() {
	/**
	 * Licenses
	 */
	jQuery( document.body ).on( 'click', '.um_license_deactivate', function() {
		jQuery(this).siblings('.um-option-field').val('');
		jQuery(this).parents('form.um-settings-form').submit();
	});


	/**
	 * Not licenses page
	 */
	if ( jQuery( '#licenses_settings' ).length === 0 ) {
		var changed = false;

		jQuery( 'input, textarea, select' ).change( function() {
			changed = true;
		});

		jQuery( '#um-settings-wrap .um-nav-tab-wrapper a, #um-settings-wrap .subsubsub a' ).click( function() {
			if ( changed ) {
				window.onbeforeunload = function() {
					return php_data.onbeforeunload_text;
				};
			} else {
				window.onbeforeunload = '';
			}
		});

		jQuery( '.submit input' ).click( function() {
			window.onbeforeunload = '';
		});
	}
});