jQuery(document).ready(function() {

	jQuery(document).on('click', '.um-popup-overlay', function(){

		remove_Modal();
	});

	jQuery(document).on('click', '.um-modal-overlay, a[data-action="um_remove_modal"]', function(){
		um_remove_modal();
	});

	jQuery(document).on('click', 'a[data-modal^="um_"], span[data-modal^="um_"], .um-modal:not(:has(.um-form)) a', function(e){
		e.preventDefault();
		return false;
	});

	// jQuery(document).on('click', '.um-modal .um-single-file-preview a.cancel', function(e){
	// 	e.preventDefault();
	//
	// 	var parent = jQuery(this).parents('.um-modal-body');
	// 	var src = jQuery(this).parents('.um-modal-body').find('.um-single-fileinfo a').attr('href');
	// 	var mode = parent.find('.um-single-file-upload').data('set_mode');
	//
	// 	jQuery.ajax({
	// 		url: wp.ajax.settings.url,
	// 		type: 'post',
	// 		data: {
	// 			action: 'um_remove_file',
	// 			src: src,
	// 			mode: mode,
	// 			nonce: um_scripts.nonce
	// 		},
	// 		success: function() {
	// 			parent.find('.um-single-file-preview').hide();
	// 			parent.find('.ajax-upload-dragdrop').show();
	// 			parent.find('.um-modal-btn.um-finish-upload').addClass('disabled');
	// 			um_modal_responsive();
	// 		}
	// 	});
	//
	// 	return false;
	// });

	// jQuery(document).on('click', '.um-modal .um-single-image-preview a.cancel', function(e){
	// 	e.preventDefault();
	//
	// 	var parent = jQuery(this).parents('.um-modal-body');
	// 	var src = jQuery(this).parents('.um-modal-body').find('.um-single-image-preview img').attr('src');
	// 	var mode = parent.find('.um-single-image-upload').data('set_mode');
	//
	// 	jQuery.ajax({
	// 		url: wp.ajax.settings.url,
	// 		type: 'post',
	// 		data: {
	// 			action: 'um_remove_file',
	// 			src: src,
	// 			mode: mode,
	// 			nonce: um_scripts.nonce
	// 		},
	// 		success: function() {
	// 			wp.hooks.doAction( 'um_after_removing_preview' );
	//
	// 			parent.find('.um-single-image-preview img').attr( 'src', '' );
	// 			parent.find('.um-single-image-preview').hide();
	// 			parent.find('.ajax-upload-dragdrop').show();
	// 			parent.find('.um-modal-btn.um-finish-upload').addClass( 'disabled' );
	//
	// 			um_modal_responsive();
	// 		}
	// 	});
	//
	// 	return false;
	// });

	jQuery(document).on('click', '.um-finish-upload.file:not(.disabled)', function(){

		var key = jQuery(this).attr('data-key');

		var preview = jQuery(this).parents('.um-modal-body').find('.um-single-file-preview').html();

		um_remove_modal();

		jQuery('.um-single-file-preview[data-key='+key+']').fadeIn().html( preview );

		var file = jQuery('.um-field[data-key='+key+']').find('.um-single-fileinfo a').data('file');

		jQuery('.um-single-file-preview[data-key='+key+']').parents('.um-field').find('.um-btn-auto-width').html( jQuery(this).attr('data-change') );

		jQuery('.um-single-file-preview[data-key='+key+']').parents('.um-field').find('input[type="hidden"]').val( file );

	});

	jQuery(document).on('click', '.um-finish-upload.image:not(.disabled)', function(){

		var elem = jQuery(this);
		var key = jQuery(this).attr('data-key');
		var img_c = jQuery(this).parents('.um-modal-body').find('.um-single-image-preview');
		var src = img_c.find('img').attr('src');

		var file = img_c.find('img').data('file');
		var user_id = 0;
		if ( jQuery(this).parents('#um_upload_single').data('user_id')  ) {
			user_id = jQuery(this).parents('#um_upload_single').data('user_id');
		}

		var d;
		var form_id = 0;
		var mode = '';
		if ( jQuery('div.um-field-image[data-key="' + key + '"]').length === 1 ) {
			var $formWrapper = jQuery('div.um-field-image[data-key="' + key + '"]').closest('.um-form');
			form_id = $formWrapper.find('input[name="form_id"]').val();
			mode = $formWrapper.attr('data-mode');
		}

		if ( jQuery('.cropper-hidden').length > 0 && UM.frontend.cropper.obj ) {
			var data = UM.frontend.cropper.obj.getData();
			var coord = Math.round(data.x) + ',' + Math.round(data.y) + ',' + Math.round(data.width) + ',' + Math.round(data.height);

			jQuery(this).html( jQuery(this).attr('data-processing') ).addClass('disabled');

			jQuery.ajax({
				url: wp.ajax.settings.url,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'um_resize_image',
					src : src,
					coord : coord,
					user_id : user_id,
					key: key,
					set_id: form_id,
					set_mode: mode,
					nonce: um_scripts.nonce
				},
				success: function( response ) {

					if ( response.success ) {

						d = new Date();

						if ( key === 'profile_photo' ) {
							jQuery('.um-profile-photo-img img').attr('src', response.data.image.source_url + "?"+d.getTime());
						} else if ( key === 'cover_photo' ) {
							jQuery('.um-cover-e').empty().html('<img src="' + response.data.image.source_url + "?"+d.getTime() + '" alt="" />');
							if ( jQuery('.um').hasClass('um-editing') ) {
								jQuery('.um-cover-overlay').show();
							}
						}

						jQuery('.um-single-image-preview[data-key='+key+']').fadeIn().find('img').attr('src', response.data.image.source_url + "?"+d.getTime());

						um_remove_modal();

						jQuery('img.cropper-invisible').remove();

						jQuery('.um-single-image-preview[data-key='+key+']').parents('.um-field').find('.um-btn-auto-width').html( elem.attr('data-change') );

						jQuery('.um-single-image-preview[data-key='+key+']').parents('.um-field').find('input[type="hidden"]').val( response.data.image.filename );
					}

				}
			});

		} else {
			d = new Date();

			jQuery('.um-single-image-preview[data-key='+key+']').fadeIn().find('img').attr('src', src + "?"+d.getTime());

			um_remove_modal();

			jQuery('.um-single-image-preview[data-key='+key+']').parents('.um-field').find('.um-btn-auto-width').html( elem.attr('data-change') );

			jQuery('.um-single-image-preview[data-key='+key+']').parents('.um-field').find('input[type=hidden]').val( file );
		}
	});

	jQuery(document.body).on('click', 'a[data-modal^="um_"], span[data-modal^="um_"]', function(e){

		var modal_id = jQuery(this).attr('data-modal');

		var size = 'normal';

		if ( jQuery(this).data('modal-size')  ) {
			size = jQuery(this).data('modal-size');
		}

		if ( jQuery(this).data('modal-copy') ) {

			jQuery('#' + modal_id).html( jQuery(this).parents('.um-field').find('.um-modal-hidden-content').html() );

			if ( jQuery(this).parents('.um-profile-photo').attr('data-user_id') ) {
				jQuery('#' + modal_id).attr('data-user_id', jQuery(this).parents('.um-profile-photo').attr('data-user_id') );
			}

			if ( jQuery(this).parents('.um-cover').attr('data-ratio') ) {
				jQuery('#' + modal_id).attr('data-ratio',  jQuery(this).parents('.um-cover').attr('data-ratio')  );
			}

			if ( jQuery(this).parents('.um-cover').attr('data-user_id') ) {
				jQuery('#' + modal_id).attr('data-user_id',  jQuery(this).parents('.um-cover').attr('data-user_id')  );
			}

			if ( jQuery('input[type="hidden"][name="user_id"]').length > 0 ) {
				jQuery('#' + modal_id).attr( 'data-user_id',  jQuery('input[type="hidden"][name="user_id"]').val() );
			}

		}

		um_new_modal( modal_id, size );
	});

});
