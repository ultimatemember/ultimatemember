jQuery( document ).ready( function() {

    jQuery( document ).on( 'click', '.reset_email_template', function() {
        var obj = jQuery(this);

        wp.ajax.send( 'um_delete_email_template', {
            data: {
                email_key : obj.parents('.email_template_wrapper').data('key'),
                nonce: um_admin_scripts.nonce
            },
            success: function( data ) {
                obj.parents('.email_template_wrapper').removeClass('in_theme');
            },
            error: function( data ) {
                alert( data );
            }
        });
    });


    /**
     * Licenses
     */
    jQuery( document ).on( 'click', '.um_license_deactivate', function() {
        jQuery(this).siblings('.um-option-field').val('');
        jQuery(this).parents('form.um-settings-form').submit();
    });


    /**
     * Not licenses page
     */
    if ( jQuery( '#licenses_settings' ).length == 0 ) {
        var changed = false;

        jQuery( 'input, textarea, select' ).change( function() {
            changed = true;
        });

        jQuery( '#um-settings-wrap .um-nav-tab-wrapper a, #um-settings-wrap .subsubsub a' ).click( function() {
            if ( changed ) {
                window.onbeforeunload = function() {
                    return um_admin_settings_data.texts.beforeunload;
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