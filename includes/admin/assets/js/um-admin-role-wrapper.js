jQuery( document ).ready( function() {

    jQuery('.um-nav-mode').each( function() {
        if ( jQuery(this).find('select').val() == 2 ) {
            jQuery(this).parents('.um-nav-edit').find('.um-nav-roles').show();
        } else {
            jQuery(this).parents('.um-nav-edit').find('.um-nav-roles').hide();
        }
    });


    jQuery(document).on('change', '.um-nav-mode select', function(){
        if ( jQuery(this).val() == 2 ) {
            jQuery(this).parents('.um-nav-edit').find('.um-nav-roles').show();
        } else {
            jQuery(this).parents('.um-nav-edit').find('.um-nav-roles').hide();
        }
    });


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