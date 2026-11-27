jQuery( document ).ready( function() {

	jQuery( '#role' ).on('change', function( event, initialLoad ) {

		if ( typeof um_roles == 'object' ) {
			um_roles = Object.keys( um_roles ).map(function( key ) { return um_roles[ key ]; });
		}

		if ( jQuery.inArray( jQuery(this).val().substr(3), um_roles ) !== -1 ) {
			jQuery( '#um_role_selector_wrapper' ).hide();

			// The initial call below only syncs visibility. Resetting the value
			// on page load makes WordPress think the profile has unsaved changes,
			// so only reset it after the admin changes the role.
			if ( ! initialLoad ) {
				jQuery( '#um-role' ).val('');
			}

			var hide = wp.hooks.applyFilters( 'um_user_screen_block_hiding', true );
			if ( hide ) {
				jQuery( '#um_user_screen_block' ).hide();
			}
		} else {
			jQuery( '#um_role_selector_wrapper' ).show();
			jQuery( '#um_user_screen_block' ).show();
		}
	}).trigger('change', [ true ]);

	jQuery( '#adduser-role' ).on('change', function( event, initialLoad ) {
		if ( typeof um_roles == 'object' ) {
			um_roles = Object.keys( um_roles ).map(function( key ) { return um_roles[ key ]; });
		}

		if ( jQuery.inArray( jQuery(this).val().substr(3), um_roles ) !== -1 ) {
			jQuery( '#um_role_existing_selector_wrapper' ).hide();

			// The initial call below only syncs visibility. Resetting the value
			// on page load makes WordPress think the form has unsaved changes,
			// so only reset it after the admin changes the role.
			if ( ! initialLoad ) {
				jQuery( '#um-role' ).val('');
			}

			var hide = wp.hooks.applyFilters( 'um_user_screen_block_hiding', true );
			if ( hide ) {
				jQuery( '#um_user_screen_block' ).hide();
			}
		} else {
			jQuery( '#um_role_existing_selector_wrapper' ).show();
			jQuery( '#um_user_screen_block' ).show();
		}
	}).trigger('change', [ true ]);

});