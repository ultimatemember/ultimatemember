jQuery( document ).ready( function() {


	/**
	 * Licenses
	 */
	jQuery( document.body ).on( 'click', '.um_license_deactivate', function() {
		jQuery(this).siblings('.um-option-field').val('');
		if ( jQuery(this).siblings('#submit').length ) {
			// clear = true for passing the empty field value to the license form submission
			jQuery(this).siblings('#submit').trigger('click',[ true ]);
		} else {
			jQuery(this).parents('form.um-settings-form').trigger('submit');
		}
	});


	jQuery( document.body ).on( 'click', '.um-settings-form #submit', function( e, clear ) {
		if ( ! clear && '' === jQuery(this).siblings('.um-option-field').val() ) {
			return false;
		}
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
