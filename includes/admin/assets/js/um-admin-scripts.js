/**
 * Init Tooltips
 */
function um_init_tooltips() {
	var tooltip_obj = jQuery( '.um_tooltip' );

	if ( tooltip_obj.length > 0 ) {
		tooltip_obj.tooltip({
			tooltipClass: "um_tooltip",
			content: function () {
				return jQuery( this ).attr( 'title' );
			}
		});
	}
}


jQuery(document).ready(function() {

	//WP Color Picker
	jQuery('.um-admin-colorpicker').wpColorPicker();


	//Init Tooltips
	um_init_tooltips();


	//Init Tipsy
	if ( typeof tipsy !== 'undefined' ) {
		jQuery('.um-admin-tipsy-n').tipsy({gravity: 'n', opacity: 1, live: 'a.live' });
		jQuery('.um-admin-tipsy-w').tipsy({gravity: 'w', opacity: 1, live: 'a.live' });
		jQuery('.um-admin-tipsy-e').tipsy({gravity: 'e', opacity: 1, live: 'a.live' });
		jQuery('.um-admin-tipsy-s').tipsy({gravity: 's', opacity: 1, live: 'a.live' });
	}


    jQuery(document).on( 'click', '.um-admin-notice.is-dismissible .notice-dismiss', function(e) {
        var notice_key = jQuery(this).parents('.um-admin-notice').data('key');

        wp.ajax.send( 'um_dimiss_notice', {
            data: {
                key: notice_key,
                nonce: um_admin_scripts.nonce
            },
            success: function( data ) {
                return true;
            },
            error: function( data ) {
                return false;
            }
        });
    });

});