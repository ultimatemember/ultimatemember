jQuery( document ).ready( function() {

    jQuery( '#role' ).change( function() {
        if ( jQuery.inArray( jQuery(this).val().substr(3), um_roles ) !== -1 ) {
            jQuery( '#um_role_selector_wrapper' ).hide();
            jQuery( '#um-role' ).val('');
        } else {
            jQuery( '#um_role_selector_wrapper' ).show();
        }
    }).trigger('change');

    jQuery( '#adduser-role' ).change( function() {
        if ( jQuery.inArray( jQuery(this).val().substr(3), um_roles ) !== -1 ) {
            jQuery( '#um_role_existing_selector_wrapper' ).hide();
            jQuery( '#um-role' ).val('');
        } else {
            jQuery( '#um_role_existing_selector_wrapper' ).show();
        }
    }).trigger('change');

});