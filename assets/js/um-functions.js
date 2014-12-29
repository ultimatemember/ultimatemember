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
			showStatusAfterSuccess: true,
			onSubmit:function(files){
			
				trigger.parents('.um-modal-body').find('.um-error-block').remove();
				
			},
			onSuccess:function(files,data,xhr){
			
				trigger.selectedFiles = 0;
				
				data = jQuery.parseJSON(data);
				if (data.error && data.error != '') {

					trigger.parents('.um-modal-body').append('<div class="um-error-block">'+data.error+'</div>');
					trigger.parents('.um-modal-body').find('.upload-statusbar').hide(0);
					um_modal_responsive();
					
				} else {

					jQuery.each( data, function(key, value) {
						
						var img_id = trigger.parents('.um-modal-body').find('.um-single-image-preview img');
						var img_id_h = trigger.parents('.um-modal-body').find('.um-single-image-preview');
						
						img_id.attr("src", value);
						img_id.load(function(){
						
							trigger.parents('.um-modal-body').find('.um-modal-btn.um-finish-upload.disabled').removeClass('disabled');
							trigger.parents('.um-modal-body').find('.ajax-upload-dragdrop,.upload-statusbar').hide(0);
							img_id_h.show(0);
							um_modal_responsive();
							
						});
						
					});

				}
				
			}
		});

}

function initFileUpload_UM( trigger ) {

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
			url: ultimatemember_file_upload_url,
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
			showStatusAfterSuccess: true,
			onSubmit:function(files){
			
				trigger.parents('.um-modal-body').find('.um-error-block').remove();
				
			},
			onSuccess:function(files,data,xhr){
			
				trigger.selectedFiles = 0;
				
				data = jQuery.parseJSON(data);
				if (data.error && data.error != '') {

					trigger.parents('.um-modal-body').append('<div class="um-error-block">'+data.error+'</div>');
					trigger.parents('.um-modal-body').find('.upload-statusbar').hide(0);
					um_modal_responsive();
					
				} else {

					jQuery.each( data, function(key, value) {
						
						trigger.parents('.um-modal-body').find('.um-modal-btn.um-finish-upload.disabled').removeClass('disabled');
						trigger.parents('.um-modal-body').find('.ajax-upload-dragdrop,.upload-statusbar').hide(0);
						trigger.parents('.um-modal-body').find('.um-single-file-preview').show(0);
						
						if (key == 'icon') {
							trigger.parents('.um-modal-body').find('.um-single-fileinfo i').removeClass().addClass(value);
						} else if ( key == 'icon_bg' ) {
							trigger.parents('.um-modal-body').find('.um-single-fileinfo span.icon').css({'background-color' : value } );
						} else if ( key == 'filename' ) {
							trigger.parents('.um-modal-body').find('.um-single-fileinfo span.filename').html(value);
						} else {
							trigger.parents('.um-modal-body').find('.um-single-fileinfo a').attr('href', value);
						}
						
					});
					
					um_modal_responsive();
				
				}
				
			}
		});
		
}