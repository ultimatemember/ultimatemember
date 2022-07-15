jQuery( document ).ready( function() {
	var row = jQuery('select#role').closest('tr');
	var clone = row.clone();
	row.html(jQuery('.um-roles-container tr').html());
	jQuery('.um-roles-container').remove();

	jQuery('input[name="um_user_roles_general"]').change(function() {
		var checkedValue = jQuery('input:checkbox:checked').map(function() { return this.value; }).get();
		jQuery('#um_user_roles_general').val(checkedValue);
	});

/*  The roles are now checkboxes and there is no longer a separate UM section in the user-edit page.
	jQuery( '#role' ).on('change', function() {

		if ( typeof um_roles == 'object' ) {
			um_roles = Object.keys( um_roles ).map(function( key ) { return um_roles[ key ]; });
		}

		if ( jQuery.inArray( jQuery(this).val().substr(3), um_roles ) !== -1 ) {
			jQuery( '#um_role_selector_wrapper' ).hide();
			jQuery( '#um-role' ).val('');

			var hide = wp.hooks.applyFilters( 'um_user_screen_block_hiding', true );
			if ( hide ) {
				jQuery( '#um_user_screen_block' ).hide();
			}
		} else {
			jQuery( '#um_role_selector_wrapper' ).show();
			jQuery( '#um_user_screen_block' ).show();
		}
	}).trigger('change');

	jQuery( '#adduser-role' ).on('change', function() {
		if ( typeof um_roles == 'object' ) {
			um_roles = Object.keys( um_roles ).map(function( key ) { return um_roles[ key ]; });
		}

		if ( jQuery.inArray( jQuery(this).val().substr(3), um_roles ) !== -1 ) {
			jQuery( '#um_role_existing_selector_wrapper' ).hide();
			jQuery( '#um-role' ).val('');

			var hide = wp.hooks.applyFilters( 'um_user_screen_block_hiding', true );
			if ( hide ) {
				jQuery( '#um_user_screen_block' ).hide();
			}
		} else {
			jQuery( '#um_role_existing_selector_wrapper' ).show();
			jQuery( '#um_user_screen_block' ).show();
		}
	}).trigger('change');
*/
});
