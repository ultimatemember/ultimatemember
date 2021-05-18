/**
 * This function updates the builder area with fields
 *
 * @returns {boolean}
 */
function um_admin_update_builder() {
	var form_id = jQuery('.um-admin-builder').data('form_id');

	jQuery('.tipsy').hide();

	jQuery.ajax({
		url: wp.ajax.settings.url,
		type: 'POST',
		data: {
			action:'um_update_builder',
			form_id: form_id,
			nonce: um_admin_scripts.nonce
		},
		success: function( data ) {
			jQuery('.um-admin-drag-ajax').html( data );
			jQuery('.tipsy').hide();

			/* trigger columns at start */
			allow_update_via_col_click = false;
			jQuery('.um-admin-drag-ctrls.columns a.active').each( function() {
				jQuery(this).trigger('click');
			}).promise().done( function(){
				allow_update_via_col_click = true;
			});

			UM_Rows_Refresh();
		},
		error: function( data ) {

		}
	});

	return false;
}

jQuery( document ).ready( function() {
	if ( um_admin_builder_data.hide_footer ) {
		jQuery('#wpfooter').hide();
	}


	jQuery(document.body).on('click', '.um-modal-tab a', function() {
		if ( jQuery(this).parents('li').hasClass('active') ) {
			return;
		}

		jQuery(this).parents('.um-modal-tabs').find('.um-modal-tab').removeClass('active');
        jQuery(this).parents('li').addClass('active');

        var key = jQuery(this).data('key');
        var tabs_wrapper = jQuery('.um-modal-tabs-content-wrapper');

        tabs_wrapper.find('.um-modal-tab-content').removeClass('active');
        tabs_wrapper.find('.um-modal-tab-' + key ).addClass('active');
	});
});