jQuery(document).ready(function() {

	/* Remove field permanently */
	jQuery(document.body).on('click', '.um-admin-btns a span.remove', function(e){
		e.preventDefault();

		if ( confirm( 'This will permanently delete this custom field from database' ) ) {

			jQuery(this).parents('a').remove();

			arg1 = jQuery(this).parents('a').data('arg1');

			jQuery.ajax({
				url: wp.ajax.settings.url,
				type: 'POST',
				data: {
					action:'um_do_ajax_action',
					act_id : 'um_admin_remove_field_global',
					arg1 : arg1,
					nonce: um_admin_scripts.nonce

				},
				success: function(data){

				},
				error: function(data){

				}
			});
		}

		return false;
	});


	/* Add a Field */
	jQuery(document.body).on('submit', 'form.um_add_field', function(e) {
		e.preventDefault();

		//need fields refactor
		jQuery('.um-admin-cur-condition').each( function() {
			if ( jQuery( this ).find('[id^="_conditional_action"]').val() === '' ||
				jQuery( this ).find('[id^="_conditional_field"]').val() === '' ||
				jQuery( this ).find('[id^="_conditional_operator"]').val() === '' ) {

				if ( jQuery('.um-admin-cur-condition').length !== 1 ) {
					jQuery( this ).find('.um-admin-remove-condition').trigger('click');
				}
			}
		} );

		//need fields refactor
		var form = $(this).closest('form');
		um_build_conditions(form);

		var form = jQuery(this);
		jQuery.ajax({
			url: wp.ajax.settings.url,
			type: 'POST',
			dataType: 'json',
			data: form.serialize(),
			beforeSend: function(){
				form.css({'opacity': 0.5});
				jQuery('.um-admin-error').removeClass('um-admin-error');
				form.find('.um-admin-error-block').hide();
				form.find('.um-admin-success-block').hide();
			},
			complete: function(){
				form.css({'opacity': 1});
			},
			success: function(data){
				if (data.error){

					c = 0;
					jQuery.each(data.error, function(i, v){
						c++;
						if ( c == 1 ) {
							form.find('#'+i).addClass('um-admin-error').focus();
							form.find('.um-admin-error-block').show().html(v);
						}
					});

					um_admin_modal_responsive();

				} else {

					jQuery('.um-col-demon-settings').data('in_row', '');
					jQuery('.um-col-demon-settings').data('in_sub_row', '');
					jQuery('.um-col-demon-settings').data('in_column', '');
					jQuery('.um-col-demon-settings').data('in_group', '');

					um_admin_remove_modal();
					um_admin_update_builder();

				}

			},
			error: function(data){
				console.log(data);
			}
		});

		return false;

	});

});