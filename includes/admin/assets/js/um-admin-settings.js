jQuery( document ).ready( function() {

    /**
     * Licenses
     */
    jQuery( 'body' ).on( 'click', '.um_license_deactivate', function() {
        jQuery(this).siblings('.um-option-field').val('');
        jQuery(this).parents('form.um-settings-form').submit();
    });


});