jQuery( document ).ready( function() {
	/**
	 * Licenses
	 */
	jQuery( document.body ).on( 'click', '.um_license_deactivate', function() {
		jQuery(this).siblings('.um-option-field').val('');
		jQuery(this).parents('form.um-settings-form').trigger('submit');
	});


	/**
	 * Not licenses page
	 */
	if ( jQuery( '#licenses_settings' ).length === 0 ) {
		var changed = false;

		jQuery( 'input, textarea, select' ).on('change', function() {
			changed = true;
		});

		jQuery( '#um-settings-wrap .um-nav-tab-wrapper a, #um-settings-wrap .subsubsub a' ).on( 'click', function() {
			if ( changed ) {
				window.onbeforeunload = function() {
					return wp.i18n.__( 'Are sure, maybe some settings not saved', 'ultimate-member' );
				};
			} else {
				window.onbeforeunload = '';
			}
		});

		jQuery( '.submit input' ).on( 'click', function() {
			window.onbeforeunload = '';
		});
	}
});