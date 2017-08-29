jQuery( document ).ready( function() {
    /**
     * Email templates
     */
    /*jQuery( 'body' ).on( 'click', '.copy_email_template', function() {
        var obj = jQuery(this);

        jQuery.ajax({
            url: php_data.copy_email_template,
            type: 'POST',
            data: { email_key : obj.parents('.email_template_wrapper').data('key') },
            success: function(data){
                obj.parents('.email_template_wrapper').addClass('in_theme');
            },
            error: function(data){
                alert('Something went wrong');
            }
        });
    });*/

    jQuery( 'body' ).on( 'click', '.reset_email_template', function() {
        var obj = jQuery(this);

        jQuery.ajax({
            url: php_data.delete_email_template,
            type: 'POST',
            data: { email_key : obj.parents('.email_template_wrapper').data('key') },
            success: function(data){
                obj.parents('.email_template_wrapper').removeClass('in_theme');
            },
            error: function(data){
                alert('Something went wrong');
            }
        });
    });




    /**
     * Licenses
     */
    jQuery( 'body' ).on( 'click', '.um_license_deactivate', function() {
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