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


function um_build_conditions(form) {
	if(form){
		var el_wrap = form.find('.um-condition-group-wrapper');
		var el_cur = form.find('.um-admin-cur-condition');
	} else {
		var el_wrap = jQuery('.um-condition-group-wrapper');
		var el_cur = jQuery('.um-admin-cur-condition');
	}
	el_wrap.each( function ( i ) {
		jQuery( this ).data( 'group_id', i );
		jQuery( this ).find('[id^="_conditional_group"]').val( i );
	} );

	el_cur.each( function ( i ) {
		var id = i === 0 ? '' : i;
		jQuery( this ).find('[id^="_conditional_action"]').attr('name', '_conditional_action' + id).attr('id', '_conditional_action' + id);
		jQuery( this ).find('[id^="_conditional_field"]').attr('name', '_conditional_field' + id).attr('id', '_conditional_field' + id);
		jQuery( this ).find('[id^="_conditional_operator"]').attr('name', '_conditional_operator' + id).attr('id', '_conditional_operator' + id);
		jQuery( this ).find('[id^="_conditional_value"]').attr('name', '_conditional_value' + id).attr('id', '_conditional_value' + id);
		jQuery( this ).find('[id^="_conditional_compare"]').attr('name', '_conditional_compare' + id).attr('id', '_conditional_compare' + id);
		jQuery( this ).find('[id^="_conditional_group"]').attr('name', '_conditional_group' + id).attr('id', '_conditional_group' + id);
	} );
}


jQuery( document ).ready( function() {
	if ( um_admin_builder_data.hide_footer ) {
		jQuery('#wpfooter').hide();
	}
});