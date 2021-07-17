jQuery(document).ready(function() {

	/* Remove field permanently */
	jQuery(document.body).on('click', '.um-admin-btns a span.remove', function(e){
		e.preventDefault();

		if ( confirm( wp.i18n.__( 'This will permanently delete this custom field from a database and from all forms on your site. Are you sure?', 'ultimate-member' ) ) ) {

			jQuery(this).parents('a').remove();

			var arg1 = jQuery(this).parents('a').data('arg1');

			jQuery.ajax({
				url: wp.ajax.settings.url,
				type: 'POST',
				data: {
					action:'um_do_ajax_action',
					act_id : 'um_admin_remove_field_global',
					arg1 : arg1,
					nonce: um_admin_scripts.nonce

				},
				success: function(data) {
					jQuery('#um-admin-form-builder .' + arg1).remove();
				},
				error: function(data) {

				}
			});
		}

		return false;
	});


	/* Add a Field */
	jQuery(document.body).on('submit', 'form.um_add_field', function(e){
		e.preventDefault();

		var form = jQuery(this);

		var conditions = form.find( '.um-admin-cur-condition' );
		jQuery( conditions ).each( function (i) {
			var $c = jQuery( this );
			if ( $c.find( '[id^="_conditional_action"]' ).val() === '' ||
					$c.find( '[id^="_conditional_field"]' ).val() === '' ||
					$c.find( '[id^="_conditional_operator"]' ).val() === '' ) {
				conditions.eq(i).remove();
			}
		} );

		conditions = form.find( '.um-admin-cur-condition' );
		jQuery( conditions ).each( function (i) {
			var $c = jQuery( this );
			var id = i === 0 ? '' : i;

			$c.find( '[id^="_conditional_action"]' ).attr( 'name', '_conditional_action' + id );
			$c.find( '[id^="_conditional_action"]' ).attr( 'id', '_conditional_action' + id );
			$c.find( '[id^="_conditional_field"]' ).attr( 'name', '_conditional_field' + id );
			$c.find( '[id^="_conditional_field"]' ).attr( 'id', '_conditional_field' + id );
			$c.find( '[id^="_conditional_operator"]' ).attr( 'name', '_conditional_operator' + id );
			$c.find( '[id^="_conditional_operator"]' ).attr( 'id', '_conditional_operator' + id );
			$c.find( '[id^="_conditional_value"]' ).attr( 'name', '_conditional_value' + id );
			$c.find( '[id^="_conditional_value"]' ).attr( 'id', '_conditional_value' + id );

		} );

		jQuery.ajax({
			url: wp.ajax.settings.url,
			type: 'POST',
			dataType: 'json',
			data: form.serialize(),
			beforeSend: function(){
				jQuery('.um-admin-error').removeClass('um-admin-error');
				form.find('.um-admin-error-block').hide();
				form.find('.um-admin-success-block').hide();
				UM.modal.loading(true);
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
						form.find('#'+i).addClass('um-admin-error').trigger('focus');
						form.find('.um-admin-error-block').show().html(v);
						}
					});

					UM.modal.loading(true).responsive();

				} else {

					jQuery('.um-col-demon-settings').data('in_row', '');
					jQuery('.um-col-demon-settings').data('in_sub_row', '');
					jQuery('.um-col-demon-settings').data('in_column', '');
					jQuery('.um-col-demon-settings').data('in_group', '');

					UM.modal.loading(true).close();

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