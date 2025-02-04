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
				source: $button
			};

			UM.profile.avatarModal = UM.modal.addModal( settings, null );

			$uploader.removeClass('um-upload-completed');
			$fileList.umHide();
			$dropZone.umShow();

			let fileRow = $fileList.find('#' + file.id);
			fileRow.data('filename', response.data[0].name_saved).data('nonce', response.data[0].delete_nonce);

			return true;
		} else if ( 'square' === cropSetting ) {
			let settings = {
				// These are the defaults.
				classes:  'um-field-image-modal',
				duration: 400, // ms
				footer:   '',
				header:   wp.i18n.__( 'Crop image', 'ultimate-member' ),
				size:     'normal', // small, normal, large
				content:  response.data[0].modal_content,
				source: $button
			};

			UM.profile.avatarModal = UM.modal.addModal( settings, null );

			$uploader.removeClass('um-upload-completed');
			$fileList.umHide();
			$dropZone.umShow();

			let fileRow = $fileList.find('#' + file.id);
			fileRow.data('filename', response.data[0].name_saved).data('nonce', response.data[0].delete_nonce);

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
	}

	return null;
});
