jQuery( document ).ready( function() {

    jQuery( document ).on( 'click', '#um-search-button', function() {
        jQuery(this).parents('form').submit();
    });

});


