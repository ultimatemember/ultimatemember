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
});
