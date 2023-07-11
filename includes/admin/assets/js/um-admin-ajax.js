function um_builder_delete_field_ajax( callback ) {
	if ( UM.admin.builder.fieldsToDelete.length > 0 ) {
		let fieldDelete = UM.admin.builder.fieldsToDelete.shift();
		let arg1 = jQuery( fieldDelete ).find('[data-silent_action="um_admin_remove_field"]').data('arg1');
		let arg2 = jQuery( fieldDelete ).find('[data-silent_action="um_admin_remove_field"]').data('arg2');

		jQuery.ajax({
			url: wp.ajax.settings.url,
			type: 'POST',
			data: {
				action:'um_do_ajax_action',
				act_id : 'um_admin_remove_field',
				arg1 : arg1,
				arg2 : arg2,
				nonce: um_admin_scripts.nonce
			},
			success: function( data ) {
				um_builder_delete_field_ajax( callback );
			},
			error: function( data ) {
				callback();
			}
		});
	} else {
		callback();
	}
}

jQuery(document).ready(function() {

	jQuery(document).ajaxStart( function() {
		jQuery('.tipsy').hide();
	});

	jQuery( document.body ).on('click', 'a[data-silent_action^="um_"]', function() {
		if ( typeof jQuery(this).attr('disabled') !== 'undefined' ) {
			return false;
		}

		var act_id = jQuery(this).data('silent_action');
		var arg1   = jQuery(this).data('arg1');
		var arg2   = jQuery(this).data('arg2');

		var in_row = '';
		var in_sub_row = '';
		var in_column = '';
		var in_group = '';

		var demon_settings = jQuery('.um-col-demon-settings');
		if ( demon_settings.data('in_column') ) {
			in_row = demon_settings.data('in_row');
			in_sub_row = demon_settings.data('in_sub_row');
			in_column = demon_settings.data('in_column');
			in_group = demon_settings.data('in_group');
		}

		jQuery('.tipsy').hide();

		um_admin_remove_modal();
		jQuery.ajax({
			url: wp.ajax.settings.url,
			type: 'POST',
			data: {
				action:'um_do_ajax_action',
				act_id : act_id,
				arg1 : arg1,
				arg2 : arg2,
				in_row: in_row,
				in_sub_row: in_sub_row,
				in_column: in_column,
				in_group: in_group,
				nonce: um_admin_scripts.nonce
			},
			success: function( data ) {
				demon_settings.data('in_row', '').data('in_sub_row', '').data('in_column', '').data('in_group', '');
				um_admin_modal_responsive();
				um_admin_update_builder();
			},
			error: function( data ) {

			}
		});

		return false;
	});
});
