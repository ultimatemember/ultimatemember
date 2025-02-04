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

jQuery(document).ready(function() {

	jQuery(document.body).on('click', '.um-user-action', function(e) {
		e.preventDefault();
		if ( jQuery(this).data('confirm-onclick') ) {
			// Using wp.hooks here for workaround and integrate um-dropdown links and js.confirm
			if ( ! confirm( jQuery(this).data('confirm-onclick') ) ) {
				wp.hooks.addFilter( 'um_dropdown_link_result', 'ultimate-member', function( result, attrClass, obj ) {
					if ( ! obj.data('confirm-onclick') ) {
						return result;
					}
					return false;
				});
				return false;
			} else {
				wp.hooks.removeFilter( 'um_dropdown_link_result', 'ultimate-member' );
			}
		} else {
			wp.hooks.removeFilter( 'um_dropdown_link_result', 'ultimate-member' );
		}
	});

	jQuery( document.body ).on( 'click', '.um-user-posts-load-more', function( e ) {
		e.preventDefault();

		let $btn = jQuery(this);
		let $loopWrapper = $btn.siblings('.um-user-profile-posts-loop');
		let page = $btn.data('page')*1 + 1;

		$btn.prop('disabled',true);
		$btn.siblings('.um-user-posts-loader').umShow();
		wp.ajax.send(
			'um_get_user_posts',
			{
				data: {
					author:  $btn.data('author'),
					last_id: $btn.data('last_id'),
					nonce:   $btn.data('nonce')
				},
				success: function( response ) {
					$btn.prop('disabled', false);
					$btn.siblings('.um-user-posts-loader').umHide();
					$loopWrapper.append( response.content );

					let totalPages = $btn.data('pages')*1;
					if ( page === totalPages ) {
						$btn.remove();
					} else {
						$btn.data( 'page', page );
						$btn.data('last_id', response.last_id );
					}
				},
				error: function( data ) {
					$btn.prop('disabled', false);
					$btn.siblings('.um-user-posts-loader').umHide();
					console.log( data );
				}
			}
		);
	});

	jQuery( document.body ).on( 'click', '.um-user-comments-load-more', function( e ) {
		e.preventDefault();

		let $btn = jQuery(this);
		let $loopWrapper = $btn.siblings('.um-user-profile-comments-loop');
		let page = $btn.data('page')*1 + 1;

		$btn.prop('disabled',true);
		$btn.siblings('.um-user-comments-loader').umShow();
		wp.ajax.send(
			'um_get_user_comments',
			{
				data: {
					author:  $btn.data('author'),
					last_id: $btn.data('last_id'),
					nonce:   $btn.data('nonce')
				},
				success: function( response ) {
					$btn.prop('disabled', false);
					$btn.siblings('.um-user-comments-loader').umHide();
					$loopWrapper.append( response.content );

					let totalPages = $btn.data('pages')*1;
					if ( page === totalPages ) {
						$btn.remove();
					} else {
						$btn.data( 'page', page );
						$btn.data('last_id', response.last_id );
					}
				},
				error: function( data ) {
					$btn.prop('disabled', false);
					$btn.siblings('.um-user-comments-loader').umHide();
					console.log( data );
				}
			}
		);
	});

	jQuery( document.body ).on('click', '.um-photo-modal', function(e){
		e.preventDefault();
		let photoSrc = jQuery(this).data('src');
		let content = jQuery(this).html();

		let settings = {
			// These are the defaults.
			classes:  'um-profile-field-photo-modal',
			duration: 400, // ms
			footer:   '',
			header:   '',
			size:     'large', // small, normal, large
			content:  content,
			template: '<div class="um-modal"><span class="um-modal-close um-modal-close-fixed">&times;</span><div class="um-modal-body"></div></div>'
		};

		UM.modal.addModal( settings, null );

		return false;
	});

	jQuery( document.body ).on( 'click', '.um-modal-field-image-decline', function(e){
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
							jQuery('[data-um-modal-opened="1"]').siblings('.um-avatar').find('> img').replaceWith( response.avatar );
							if ( response.all_sizes ) {
								$.each( response.all_sizes, function(i) {
									$('.um-avatar-' + i + '[data-user_id="' + userID + '"]').find('> img').replaceWith( response.all_sizes[i] );
								})
							}

							// Image-type form field
							if ( response.image.file_preview ) {
								jQuery('[data-key="' + fieldID + '"]').find('.um-uploader-file-preview').html( response.image.file_preview );
								jQuery('[data-key="' + fieldID + '"]').find('.um-uploader-dropzone').umHide();
								jQuery('[data-key="' + fieldID + '"]').find('.um-uploader-filelist').umShow();
								jQuery('[data-key="' + fieldID + '"]').find('.um-uploader').addClass('um-upload-completed');
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

	jQuery('.um-profile.um-viewing .um-profile-body .um-row').each(function(){
		var this_row = jQuery(this);
		if ( this_row.find('.um-field').length == 0 ) {
			this_row.prev('.um-row-heading').remove();
			this_row.remove();
		}
	});

	if ( jQuery('.um-profile.um-viewing .um-profile-body').length && jQuery('.um-profile.um-viewing .um-profile-body').find('.um-field').length == 0 ) {
		jQuery('.um-profile.um-viewing .um-profile-body').find('.um-row-heading,.um-row').remove();
		jQuery('.um-profile-note').show();
	}

	jQuery( document.body ).on( 'click', '.um-profile-save', function(e){
		e.preventDefault();
		jQuery(this).parents('.um.um-profile.um-editing').find('form').trigger('submit');
		return false;
	});

	// Bio characters limit
	jQuery( document.body ).on( 'change keyup', '#um-meta-bio', function() {
		if ( typeof jQuery(this).val() !== 'undefined' ) {
			let um_bio_limit = jQuery(this).data( 'character-limit' );
			let bio_html     = jQuery(this).data( 'html' );

			let remaining = um_bio_limit - jQuery(this).val().length;
			if ( parseInt( bio_html ) === 1 ) {
				remaining = um_bio_limit - jQuery(this).val().replace(/(<([^>]+)>)/ig,'').length;
			}

			remaining = remaining < 0 ? 0 : remaining;

			jQuery( 'span.um-meta-bio-character span.um-bio-limit' ).text( remaining );
			let color = remaining < 5 ? 'red' : '';
			jQuery('span.um-meta-bio-character').css( 'color', color );
		}
	});
	jQuery( '#um-meta-bio' ).trigger('change');

	// Biography (description) fields syncing.
	jQuery( '.um-profile form' ).each( function () {
		let descKey = jQuery(this).data('description_key');
		if ( jQuery(this).find( 'textarea[name="' + descKey + '"]' ).length ) {
			jQuery( document.body ).on( 'change input', 'textarea[name="' + descKey + '"]', function ( e ) {
				jQuery(this).parents( 'form' ).find( 'textarea[name="' + descKey + '"]' ).each( function() {
					jQuery(this).val( e.currentTarget.value );
					if ( jQuery('#um-meta-bio')[0] !== e.currentTarget && jQuery('#um-meta-bio')[0] === jQuery(this)[0] ) {
						jQuery(this).trigger('change');
					}
				});
			});
		}
	});

	/**
	 * Fix profile nav links for iPhone
	 * @see https://www.html5rocks.com/en/mobile/touchandmouse/
	 */
	jQuery( '.um-profile-nav a' ).on( 'touchend', function(e) {
		jQuery( e.currentTarget).trigger( "click" );
	});
});
