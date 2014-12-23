function initCrop_UM() {

	jQuery('.um-single-image-preview.crop img').imgAreaSelect({
		handles: true,
	});

}

function initImageUpload_UM( trigger ) {

		if (trigger.data('upload_help_text')){
			upload_help_text = '<span class="help">' + trigger.data('upload_help_text') + '</span>';
		} else {
			upload_help_text = '';
		}
		
		if ( trigger.data('icon') ) {
			icon = '<span class="icon"><i class="'+ trigger.data('icon') + '"></i></span>';
		} else {
			icon = '';
		}

		if ( trigger.data('upload_text') ) {
			upload_text = '<span class="str">' + trigger.data('upload_text') + '</span>';
		} else {
			upload_text = '';
		}
		
		trigger.uploadFile({
			url: ultimatemember_image_upload_url,
			method: "POST",
			multiple: false,
			formData: {key: trigger.data('key'), set_id: trigger.data('set_id'), set_mode: trigger.data('set_mode') },
			fileName: trigger.data('key'),
			allowedTypes: trigger.data('allowed_types'),
			maxFileSize: trigger.data('max_size'),
			dragDropStr: icon + upload_text + upload_help_text,
			sizeErrorStr: trigger.data('max_size_error'),
			extErrorStr: trigger.data('extension_error'),
			maxFileCountErrorStr: trigger.data('max_files_error'),
			maxFileCount: 1,
			showDelete: false,
			showAbort: false,
			showDone: false,
			showFileCounter: false,
			showStatusAfterSuccess: false,
			onSubmit:function(files){
			
				trigger.parents('.um-modal-body').find('.um-error-block').remove();
				
			},
			onSuccess:function(files,data,xhr){
			
				trigger.selectedFiles = 0;
				
				data = jQuery.parseJSON(data);
				if (data.error && data.error != '') {

					trigger.parents('.um-modal-body').append('<div class="um-error-block">'+data.error+'</div>');
					
				} else {

					jQuery.each( data, function(key, value) {
						trigger.parents('.um-modal-body').find('.um-single-image-preview img').attr('src', value);
					});
					
					trigger.parents('.um-modal-body').find('.ajax-upload-dragdrop').fadeOut( function () {
						trigger.parents('.um-modal-body').find('.um-single-image-preview').show();
						trigger.parents('.um-modal-body').find('.um-modal-btn.um-finish-upload.disabled').removeClass('disabled');
						um_modal_responsive();
					} );
					
					initCrop_UM();
					
				}
				
			}
		});

}