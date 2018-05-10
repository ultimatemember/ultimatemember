jQuery(document).ready(function() {

	jQuery(document).ajaxStart(function(){
		jQuery('.tipsy').hide();
	});

	jQuery(document).on( 'click', 'a[data-silent_action^="um_"]', function(){

		if ( typeof jQuery(this).attr('disabled') !== 'undefined' ) {
			return false;
		}

		var col_demon = jQuery('.um-col-demon-settings');

		var in_row = '';
		var in_sub_row = '';
		var in_column = '';
		var in_group = '';

		if ( col_demon.data( 'in_column' ) ) {
			in_row = col_demon.data('in_row');
			in_sub_row = col_demon.data('in_sub_row');
			in_column = col_demon.data('in_column');
			in_group = col_demon.data('in_group');
		}

		var act_id = jQuery(this).data('silent_action');
		var arg1 = jQuery(this).data('arg1');
		var arg2 = jQuery(this).data('arg2');

		jQuery('.tipsy').hide();

		um_admin_remove_modal();

		// Send the form via ajax
		wp.ajax.send( 'um_do_ajax_action', {
			data: {
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

				col_demon.data('in_row', '').data('in_sub_row', '').data('in_column', '').data('in_group', '');

				um_admin_modal_responsive();
				um_admin_update_builder();

			},
			error: function(data) {

			}
		});

		return false;
	});

});