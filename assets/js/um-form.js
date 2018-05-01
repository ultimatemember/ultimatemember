jQuery( document ).ready( function() {

    if( jQuery('input[data-key="user_password"],input[data-key="confirm_user_password"]').length == 2 ) {
        jQuery(document).on('keyup', 'input[data-key="user_password"],input[data-key="confirm_user_password"]', function(e) {
            var value = jQuery('input[data-key="user_password"]').val();
            var match = jQuery('input[data-key="confirm_user_password"]').val();
            var field = jQuery('input[data-key="user_password"],input[data-key="confirm_user_password"]');

            if ( ! value && ! match ) {
                field.removeClass('um-validate-matched').removeClass('um-validate-not-matched');
            } else if( value !== match ) {
                field.removeClass('um-validate-matched').addClass('um-validate-not-matched');
            } else {
                field.removeClass('um-validate-not-matched').addClass('um-validate-matched');
            }
        });
    }






});