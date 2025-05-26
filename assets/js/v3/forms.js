wp.hooks.addFilter( 'um_uploader_data', 'ultimate-member', function( uploaderData, handler, $button ) {
	if ( 'field-image' !== handler && 'field-file' !== handler ) {
		return uploaderData;
	}

	uploaderData.filters.prevent_duplicates = false;

	let $userField = $button.parents('form').find('input[name="user_id"]');
	if ( $userField.length ) {
		let userID = $userField.val();
		if ( userID ) {
			uploaderData.url += '&user_id=' + userID;
		}
	}

	let $formField = $button.parents('form').find('input[name="form_id"]');
	if ( $formField.length ) {
		let formID = $formField.val();
		if ( formID ) {
			uploaderData.url += '&form_id=' + formID;
		}
	}

	let field = $button.parents('.um-field').data('key');
	if ( field ) {
		uploaderData.url += '&field_id=' + field;
	}

	if ( 'field-image' === handler ) {
		uploaderData.url += '&crop=' + $button.parents('.um-uploader').data('crop');
	}

	return uploaderData;
});

wp.hooks.addFilter( 'um_uploader_file_filtered', 'ultimate-member', function( preventDefault, $button, up, file ) {
	let handler = $button.data( 'handler' );
	if ( 'field-image' !== handler ) {
		return preventDefault;
	}

	let $uploader = $button.parents( '.um-uploader' );
	// let $wrapper  = $uploader.parents( '.um-field-uploader-wrapper' );
	let $fileList = $uploader.find( '.um-uploader-filelist' );
	let $dropZone = $uploader.find( '.um-uploader-dropzone' );

	if ( $fileList.length ) {
		$uploader.removeClass('um-upload-completed');
		$fileList.umShow();
		$dropZone.umHide();

		// flush files list if there is only 1 file can be uploaded.
		if ( ! up.getOption( 'multi_selection' ) ) {
			$fileList.find( '.um-uploader-file' ).each( function ( u, item ) {
				up.removeFile( item.id );
			} );
		}

		let fileRow = $fileList.find('#' + file.id);

		if ( ! fileRow.length ) {
			$fileList.html('');

			let $cloned = $uploader.find('.um-uploader-file-placeholder').clone().addClass('um-uploader-file').removeClass('um-uploader-file-placeholder um-display-none').attr('id',file.id);

			let objSelectors = [
				'.um-uploaded-value',
				'.um-uploaded-value-hash',
				'.um-uploaded-value-temp-hash',
			];

			for ( let i = 0; i < objSelectors.length; i++ ) {
				let name = $cloned.find(objSelectors[i]).attr('name');
				name = name.replace( '\{\{\{file_id\}\}\}', file.id );
				$cloned.find(objSelectors[i]).prop('disabled',false).attr('name', name );
			}

			$fileList.append( $cloned );

			fileRow = $fileList.find('#' + file.id);
			let extension = file.name.split('.').pop();
			if ( '' === file.type ) {
				extension = 'file';
			}
			fileRow.find('.um-file-extension-text').text(extension);
			fileRow.find('.um-supporting-text').text(plupload.formatSize(file.size));
		}
	}

	return true;
} );

wp.hooks.addFilter( 'um_uploader_file_uploaded', 'ultimate-member', function( preventDefault, $button, up, file, response ) {
	let handler = $button.data( 'handler' );
	if ( 'field-image' !== handler ) {
		return preventDefault;
	}

	let $uploader = $button.parents( '.um-uploader' );
	let $fileList = $uploader.find( '.um-uploader-filelist' );
	let $wrapper  = $uploader.parents( '.um-field-uploader-wrapper' );
	let $dropZone = $uploader.find( '.um-uploader-dropzone' );

	if ( $fileList.length ) {
		let cropSetting = $uploader.data('crop');
		if ( 'user' === cropSetting ) {
			let settings = {
				// These are the defaults.
				classes:  'um-field-image-modal',
				duration: 400, // ms
				footer:   '',
				header:   wp.i18n.__( 'Crop image', 'ultimate-member' ),
				size:     'normal', // small, normal, large
				content:  response.data[0].modal_content,
				source: $button,
				attributes: {
					'data-file-id': file.id
				}
			};

			UM.profile.avatarModal = UM.modal.addModal( settings, null );

			$uploader.removeClass('um-upload-completed');
			$fileList.umHide();
			$dropZone.umShow();

			let fileRow = $fileList.find('#' + file.id);
			fileRow.data('filename', response.data[0].name_saved).data('temp_hash', response.data[0].temp_hash).data('nonce', response.data[0].delete_nonce);

			return true;
		} else if ( 'square' === cropSetting || 'cover' === cropSetting ) {
			let settings = {
				// These are the defaults.
				classes:  'um-field-image-modal',
				duration: 400, // ms
				footer:   '',
				header:   wp.i18n.__( 'Crop image', 'ultimate-member' ),
				size:     'normal', // small, normal, large
				content:  response.data[0].modal_content,
				source: $button,
				attributes: {
					'data-file-id': file.id
				}
			};

			UM.profile.avatarModal = UM.modal.addModal( settings, null );

			$uploader.removeClass('um-upload-completed');
			$fileList.umHide();
			$dropZone.umShow();

			let fileRow = $fileList.find('#' + file.id);
			fileRow.data('filename', response.data[0].name_saved).data('temp_hash', response.data[0].temp_hash).data('nonce', response.data[0].delete_nonce);

			return true;
		} else {
			$wrapper.find( '.um-field-image-controls' ).umShow();
			$uploader.addClass('um-upload-completed');

			$uploader.find('.um-uploader-file .um-uploader-file-preview').html( response.data[0].lazy_image );
			$uploader.find('.um-uploader-file').find('.um-uploader-file-data').umHide();

			UM.frontend.image.lazyload.init();

			let fileRow = $fileList.find( '#' + file.id );
			fileRow.find( '.um-uploaded-value' ).val( response.data[0].name_saved );
			fileRow.find( '.um-uploaded-value-hash' ).val( response.data[0].hash );
			fileRow.find( '.um-uploaded-value-temp-hash' ).val( response.data[0].temp_hash );

			$uploader.find('.um-uploaded-value-hidden').prop('disabled', true);
			$uploader.find('.um-uploaded-value-hash-hidden').prop('disabled', true);
			$uploader.find('.um-uploaded-value-temp-hash-hidden').prop('disabled', true);

			fileRow.data('filename', response.data[0].name_saved).data('temp_hash', response.data[0].temp_hash).data('nonce', response.data[0].delete_nonce);
		}
	}

	return null;
});

wp.hooks.addFilter( 'um_uploader_file_filtered', 'ultimate-member', function( preventDefault, $button, up, file ) {
	let handler = $button.data( 'handler' );
	if ( 'field-file' !== handler ) {
		return preventDefault;
	}

	let $uploader = $button.parents( '.um-uploader' );
	let $fileList = $uploader.find( '.um-uploader-filelist' );
	let $dropZone = $uploader.find( '.um-uploader-dropzone' );

	if ( $fileList.length ) {
		$uploader.removeClass('um-upload-completed');
		$fileList.umShow();
		$dropZone.umHide();

		// flush files list if there is only 1 file can be uploaded.
		if ( ! up.getOption( 'multi_selection' ) ) {
			$fileList.find( '.um-uploader-file' ).each( function ( u, item ) {
				up.removeFile( item.id );
			} );
		}

		let fileRow = $fileList.find('#' + file.id);

		if ( ! fileRow.length ) {
			$fileList.html('');

			let $cloned = $uploader.find('.um-uploader-file-placeholder').clone().addClass('um-uploader-file').removeClass('um-uploader-file-placeholder um-display-none').attr('id',file.id);

			let objSelectors = [
				'.um-uploaded-value',
				'.um-uploaded-value-hash',
				'.um-uploaded-value-temp-hash',
			];

			for ( let i = 0; i < objSelectors.length; i++ ) {
				let name = $cloned.find(objSelectors[i]).attr('name');
				name = name.replace( '\{\{\{file_id\}\}\}', file.id );
				$cloned.find(objSelectors[i]).prop('disabled',false).attr('name', name );
			}

			$fileList.append( $cloned );

			fileRow = $fileList.find('#' + file.id);
			fileRow.find('.um-uploader-file-name').text(file.name);
			let extension = file.name.split('.').pop();
			if ( '' === file.type ) {
				extension = 'file';
			}
			fileRow.find('.um-file-extension-text').text(extension);
			fileRow.find('.um-supporting-text').text(plupload.formatSize(file.size));
		}
	}

	return true;
} );

wp.hooks.addFilter( 'um_uploader_file_uploaded', 'ultimate-member', function( preventDefault, $button, up, file, response ) {
	let handler = $button.data( 'handler' );
	if ( 'field-file' !== handler ) {
		return preventDefault;
	}

	let $uploader = $button.parents( '.um-uploader' );
	let $fileList = $uploader.find( '.um-uploader-filelist' );
	let $wrapper  = $uploader.parents( '.um-field-uploader-wrapper' );

	$wrapper.find( '.um-field-file-controls' ).umShow();

	$uploader.addClass('um-upload-completed');

	if ( $fileList.length ) {
		let fileRow = $fileList.find( '#' + file.id );
		fileRow.find( '.um-uploaded-value' ).val( response.data[0].name_saved );
		fileRow.find( '.um-uploaded-value-hash' ).val( response.data[0].hash );
		fileRow.find( '.um-uploaded-value-temp-hash' ).val( response.data[0].temp_hash );

		$uploader.find('.um-uploaded-value-hidden').prop('disabled', true);
		$uploader.find('.um-uploaded-value-hash-hidden').prop('disabled', true);
		$uploader.find('.um-uploaded-value-temp-hash-hidden').prop('disabled', true);
	}

	return null;
});

jQuery(document).ready(function() {
	// Click on uploaded image for preview in modal.
	jQuery( document.body ).on( 'click', '.um-photo-modal', function(e) {
		e.preventDefault();
		let photoSrc = jQuery(this).data('src');
		let lazyImg = jQuery(this).find('.um-image-lazyload');
		let content = '<img class="um-photo-modal-img" src="' + photoSrc + '" alt="' + lazyImg.attr('alt') + '"/>';

		let settings = {
			// These are the defaults.
			classes: 'um-profile-field-photo-modal',
			duration: 400, // ms
			footer: '',
			header: '',
			size: 'large', // small, normal, large
			content: content,
			template: '<div class="um-modal"><span class="um-modal-close um-modal-close-fixed">&times;</span><div class="um-modal-body"></div></div>'
		};

		UM.modal.addModal(settings, null);

		return false;
	});

	jQuery(document.body).on('click', '.um-modal-field-image-decline', function (e) {
		e.preventDefault();

		if ( ! confirm( wp.i18n.__( 'Are you sure that you want to cancel crop of this image and remove it?', 'ultimate-member' ) ) ) {
			return false;
		}

		let $button = jQuery(this);
		let $loader = $button.siblings('.um-ajax-spinner-svg');
		let $buttons = $button.parents('.um-modal-buttons-wrapper').find('.um-button');

		let fieldID = $button.data('form_field');

		$buttons.prop('disabled',true);
		$loader.show();
		if ( UM.frontend.cropper.obj ) {
			// If Cropper object exists then destroy before re-init.
			UM.frontend.cropper.destroy();
		}
		UM.modal.close();

		let changeTextCb = function( confirmText, obj ) {
			if ( obj.hasClass('um-field-image-remove') ) {
				confirmText = '';
			}
			return confirmText;
		}
		// Avoid double confirmation message
		wp.hooks.addFilter( 'um-field-image-remove-confirm-text', 'ultimate-member', changeTextCb );
		jQuery('#' + fieldID).find( '.um-field-image-remove' ).trigger('click');
		wp.hooks.removeFilter('um-field-image-remove-confirm-text', 'ultimate-member');
	});

	jQuery( document.body ).on( 'click', '.um-apply-field-image-crop', function(e){
		e.preventDefault();

		let $button = jQuery(this);

		if ( $button.parents('.um-modal-body').find('.cropper-hidden').length > 0 && UM.frontend.cropper.obj ) {
			let $loader = $button.siblings('.um-ajax-spinner-svg');
			let $buttons = $button.parents('.um-modal-buttons-wrapper').find('.um-button');
			let userID = $button.data('user_id');
			let formID = $button.data('form_id');
			let fieldID = $button.data('field_id');
			let nonce = $button.data('nonce');
			let tempHash = $button.data('temp_hash');

			let $modal = $button.parents('.um-modal');
			let fileID = $modal.data('file-id');

			let src = $button.parents('.um-modal-body').find('.cropper-hidden').attr('src');

			let cropperData = UM.frontend.cropper.obj.getData();
			let coord = Math.round(cropperData.x) + ',' + Math.round(cropperData.y) + ',' + Math.round(cropperData.width) + ',' + Math.round(cropperData.height);

			if ( coord ) {
				$buttons.prop('disabled',true);
				$loader.show();

				wp.ajax.send(
					'um_crop_image',
					{
						data: {
							src : src,
							temp_hash : tempHash,
							coord : coord,
							user_id : userID,
							field_id: fieldID,
							form_id: formID,
							nonce: nonce
						},
						success: function( response ) {
							// Image-type form field
							if ( response.file_preview ) {
								let $wrapper = jQuery('[data-key="' + fieldID + '"]');
								$wrapper.find('.um-uploader-file-preview').html( response.file_preview );
								$wrapper.find('.um-uploader-dropzone').umHide();
								$wrapper.find('.um-uploader-filelist').umShow();
								$wrapper.find('.um-uploader').addClass('um-upload-completed');
								$wrapper.find( '.um-field-image-controls' ).umShow();

								let $fileRow = $wrapper.find( '#' + fileID );
								$fileRow.find( '.um-uploaded-value' ).val( response.filename );
								$fileRow.find( '.um-uploaded-value-hash' ).val( response.hash );
								$fileRow.find( '.um-uploaded-value-temp-hash' ).val( tempHash );

								$wrapper.find('.um-uploaded-value-hidden').prop('disabled', true);
								$wrapper.find('.um-uploaded-value-hash-hidden').prop('disabled', true);
								$wrapper.find('.um-uploaded-value-temp-hash-hidden').prop('disabled', true);

								UM.frontend.image.lazyload.init();
							}

							$loader.hide();
							if ( UM.frontend.cropper.obj ) {
								// If Cropper object exists then destroy before re-init.
								UM.frontend.cropper.destroy();
							}
							UM.modal.close();
						},
						error: function (data) {
							console.log(data);
						}
					}
				);
			}
		}
	});
});
