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

	/**
	 clone a field dropdown
	 **/
	jQuery(document).on( 'click', '#um_add_review_love', function(e){
		jQuery(this).parents('#um_start_review_notice').hide();
		jQuery('.um_hidden_notice[data-key="love"]').show();
	});

	/**
	 clone a field dropdown
	 **/
	jQuery(document).on( 'click', '#um_add_review_good', function(e){
		jQuery(this).parents('#um_start_review_notice').hide();
		jQuery('.um_hidden_notice[data-key="good"]').show();
	});

	/**
	 clone a field dropdown
	 **/
	jQuery(document).on( 'click', '#um_add_review_bad', function(e){
		jQuery(this).parents('#um_start_review_notice').hide();
		jQuery('.um_hidden_notice[data-key="bad"]').show();
	});


	/**
		clone a field dropdown
	**/
	jQuery(document).on('click', '.um-admin-clone', function(e){
		e.preventDefault();
		var container = jQuery(this).parents('.um-admin-field');
		var parent = jQuery(this).parents('p').find('.um-admin-field:last-child');
		container.find('select').select2('destroy');
		var cloned = container.clone();
		cloned.find('.um-admin-clone').replaceWith('<a href="#" class="um-admin-clone-remove button um-admin-tipsy-n" title="Remove Field"><i class="um-icon-close" style="margin-right:0!important"></i></a>');
		cloned.insertAfter( parent );
		cloned.find('select').val('');
		jQuery('.um-admin-field select').select2({
			allowClear: true,
			minimumResultsForSearch: 10
		});
		return false;
	});
	
	/**
		remove a field dropdown
	**/
	jQuery(document).on('click', '.um-admin-clone-remove', function(e){
		e.preventDefault();
		var container = jQuery(this).parents('.um-admin-field');
		jQuery('.tipsy').remove();
		container.remove();
		jQuery('.um-admin-field select').select2({
			allowClear: true,
			minimumResultsForSearch: 10
		});
		return false;
	});
	
	/**
		Ajax link
	**/
	
	jQuery('.um-admin-ajaxlink').click(function(e){
		e.preventDefault();
		return false;
	});
	
	/**
		On/Off Buttons
	**/
	
	jQuery(document).on('click', '.um-admin-yesno span.btn', function(){
		if (!jQuery(this).parents('p').hasClass('disabled-on-off')){
		if ( jQuery(this).parent().find('input[type=hidden]').val() == 0 ){
			update_val = 1;
			jQuery(this).animate({'left': '48px'}, 200);
			jQuery(this).parent().find('input[type=hidden]').val( update_val ).trigger('change');
		} else {
			update_val = 0;
			jQuery(this).animate({'left': '0'}, 200);
			jQuery(this).parent().find('input[type=hidden]').val( update_val ).trigger('change');
		}
		}
	});
	
	/**
		WP Color Picker
	**/
	
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