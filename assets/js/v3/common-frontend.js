if ( typeof ( window.UM ) !== 'object' ) {
	window.UM = {};
}

if ( typeof ( UM.frontend ) !== 'object' ) {
	UM.frontend = {};
}

UM.frontend = {
	cropper: {
		/**
		 * @type ?Cropper
		 */
		obj: null,
		init: function() {
			let target_img = jQuery('.um-modal .um-modal-crop-wrapper img').first();
			if ( ! target_img.length || '' === target_img.attr('src') ) {
				return;
			}

			if ( UM.frontend.cropper.obj ) {
				// If Cropper object exists then destroy before re-init.
				UM.frontend.cropper.destroy();
			}

			var target_img_parent = jQuery('.um-modal .um-modal-crop-wrapper');

			var crop_data = target_img.parent().data('crop');
			var min_width = target_img.parent().data('min_width');
			var min_height= target_img.parent().data('min_height');
			var ratio     = target_img.parent().data('ratio');

			let singleUploadRatio = jQuery('.um-modal').find('#um_upload_single').data('ratio');
			if ( singleUploadRatio ) {
				let ratioSplit = singleUploadRatio.split(':');
				ratio = ratioSplit[0];
			}

			var max_height = jQuery(window).height() - ( jQuery('.um-modal-buttons-wrapper').height() + 20 ) - 80 - ( jQuery('.um-modal-header:visible').height() );

			const img = new Image;
			img.src = target_img.attr( 'src' );

			new ResizeObserver((e, observer) => {
				//img.remove();
				observer.disconnect();
				target_img.css({'height' : 'auto'});
				target_img_parent.css({'height' : 'auto'});
				target_img_parent.css({ 'height': max_height +'px', 'max-height' : max_height + 'px' });
				target_img.css({ 'height' : 'auto' });
			}).observe(img);

			let opts;
			if ( 'square' === crop_data ) {
				opts = {
					minWidth: min_width,
					minHeight: min_height,
					dragCrop: false,
					aspectRatio: 1.0,
					zoomable: false,
					rotatable: false,
					dashed: false,
					scalable: false
				};
			} else if ( 'cover' === crop_data ) {
				if ( Math.round( min_width / ratio ) > 0 ) {
					min_height = Math.round( min_width / ratio )
				}
				opts = {
					minWidth: min_width,
					minHeight: min_height,
					dragCrop: false,
					aspectRatio: ratio,
					zoomable: false,
					rotatable: false,
					dashed: false,
					scalable: false
				};
			} else if ( 'user' === crop_data ) {
				opts = {
					minWidth: min_width,
					minHeight: min_height,
					dragCrop: true,
					aspectRatio: "auto",
					zoomable: false,
					rotatable: false,
					dashed: false,
					scalable: false
				};
			}

			if ( opts ) {
				UM.frontend.cropper.obj = new Cropper(target_img[0], opts);
			}
		},
		destroy: function() {
			if ( jQuery('.cropper-container').length > 0 && UM.frontend.cropper.obj ) {
				UM.frontend.cropper.obj.destroy(); // destroy Cropper.JS method
				UM.frontend.cropper.obj = null; // flush our own object
			}
		}
	},
	dropdown: {
		init: function() {
			let $dropdown = jQuery('.um-dropdown');
			if ( $dropdown.length ) {
				$dropdown.um_dropdownMenu();
			}
		}
	},
	toggleElements: {
		init: function () {
			jQuery( document.body ).on('click', '[data-um-toggle]', function(e){
				e.preventDefault();

				let $toggleButton = jQuery(this);

				if ( $toggleButton.data('um-toggle-ignore') ) {
					return;
				}
				$toggleButton.data('um-toggle-ignore', true);

				let $toggleBlock = jQuery( $toggleButton.data('um-toggle') );
				$toggleBlock = wp.hooks.applyFilters( 'um_toggle_block', $toggleBlock, $toggleButton );
				$toggleBlock.toggleClass('um-toggle-block-collapsed');
				$toggleButton.toggleClass('um-toggle-button-active');

				let toggleCb = function ( force ) {
					$toggleBlock.find('.um-toggle-block-inner').toggleClass('um-visible');
					if ( ! force ) {
						$toggleButton.data('um-toggle-ignore', false);
					}
				};

				if ( $toggleBlock.hasClass('um-toggle-block-collapsed') ) {
					toggleCb( true );
					setTimeout( function (){
						$toggleButton.data('um-toggle-ignore', false);
					}, 500 );
				} else {
					setTimeout( toggleCb, 500);
				}

				return false;
			});
		}
	},
	progressBar: {
		init: function () {
			jQuery( '.um-progress-bar' ).each( function() {
				jQuery(this).find('.um-progress-bar-inner').css('width', jQuery(this).data('value') + '%');
			});
		},
		set: function( $bar, value ) {
			$bar.data('value', value).attr('title', value + '%');
			$bar.find('.um-progress-bar-inner').css('width', value + '%').attr('title', value + '%');
			$bar.siblings('.um-progress-bar-label').text(value + '%');
		}
	},
	responsive: {
		resolutions: { //important order by ASC
			xs: 320,
			s:  576,
			m:  768,
			l:  992,
			xl: 1024
		},
		getSize: function( number ) {
			let responsive = UM.frontend.responsive;
			for ( let key in responsive.resolutions ) {
				if ( responsive.resolutions.hasOwnProperty( key ) && responsive.resolutions[ key ] === number ) {
					return key;
				}
			}

			return false;
		},
		setClass: function() {
			let responsive = UM.frontend.responsive;
			let $resolutions = Object.values( responsive.resolutions );
			$resolutions.sort( function(a, b){ return b-a; });

			jQuery('.um').each( function() {
				let obj = jQuery(this);

				if ( obj.hasClass('um-not-responsive') ) {
					return;
				}

				let element_width = obj.outerWidth();

				jQuery.each( $resolutions, function( index ) {
					let $class = responsive.getSize( $resolutions[ index ] );
					obj.removeClass('um-ui-' + $class );
				});

				jQuery.each( $resolutions, function( index ) {
					let $class = responsive.getSize( $resolutions[ index ] );

					if ( element_width >= $resolutions[ index ] ) {
						obj.addClass('um-ui-' + $class );
						return false;
					} else if ( $class === 'xs' && element_width <= $resolutions[ index ] ) {
						obj.addClass('um-ui-' + $class );
						return false;
					}
				});
			});
		}
	},
	uploaders: [],
	uploader: {
		init: function () {
			plupload.addFileFilter('um_files_limit', function(filters, file, cb) {
				let self = this;
				let filesCount = wp.hooks.applyFilters( 'um_uploader_files_limit', self.files.length, self );

				if ( filters && filesCount >= filters ) {
					this.trigger('Error', {
						code : -801,
						message : wp.i18n.__( 'Files limit error.', 'ultimate-member' ),
						file : file
					});
					cb(false);
				} else {
					cb(true);
				}

				let button = self.getOption( 'browse_button' )[0];
				let $uploader = button.parentNode;

				if ( filters && filesCount >= filters - 1 ) {
					button.setAttribute('disabled', 'disabled');

					let dropZone = self.getOption( 'drop_element' )[0];
					if ( dropZone ) {
						$uploader = dropZone.parentNode;
						dropZone.classList.add('um-dropzone-disabled');
						let uploadLink = dropZone.querySelector( '.um-upload-link' );
						if ( uploadLink ) {
							uploadLink.classList.add('um-link-disabled');
						}
					}

					wp.hooks.doAction( 'um_uploader_trigger_files_limit_true', $uploader );
				} else {
					button.removeAttribute('disabled');

					if ( self.getOption( 'drop_element' ) ) {
						let dropZone = self.getOption( 'drop_element' )[0];

						$uploader = dropZone.parentNode;
						dropZone.classList.remove('um-dropzone-disabled');
						let uploadLink = dropZone.querySelector( '.um-upload-link' );
						if ( uploadLink ) {
							uploadLink.classList.remove('um-link-disabled');
						}
					}

					wp.hooks.doAction( 'um_uploader_trigger_files_limit_false', $uploader );
				}
			});

			jQuery('.um-uploader-button').each( function() {
				let $button = jQuery(this);

				let $uploader = $button.parents( '.um-uploader' );
				if ( $uploader.data('plupload') ) {
					// It's already initialized then continue.
					return;
				}

				let $dropZone  = $uploader.find( '.um-uploader-dropzone' );
				let $fileList  = $uploader.find( '.um-uploader-filelist' );

				let mimeTypes= $button.data('mime-types');
				let maxSize     = parseInt( $button.data('max-size') );
				let filesLimit  = parseInt( $button.data('max-files') );
				let multiple = $button.data('multiple');
				let handler  = $button.data('handler');
				let nonce    = $button.data('nonce');

				let uploaderFilters = {
					prevent_duplicates: true,
					mime_types: mimeTypes
				}

				if ( 0 !== maxSize ) {
					uploaderFilters.max_file_size = plupload.formatSize( maxSize );
				}

				if ( 0 !== filesLimit ) {
					uploaderFilters.um_files_limit = filesLimit;
				}

				let uploaderData = {
					browse_button: $button.get( 0 ), // you can pass in id...
					url: wp.ajax.settings.url + '?action=um_upload&handler=' + handler + '&nonce=' + nonce,
					chunk_size: '1024kb',
					max_retries: 1,
					multi_selection: multiple,
					filters: uploaderFilters,
					init: {
						Error: function ( up, err ) {
							wp.hooks.doAction( 'um_uploader_error', $uploader, up, err );
							if ( 'undefined' !== typeof err.file ) {
								if ( $fileList.length ) {
									$fileList.umShow();
									let fileRow = $fileList.find('#' + err.file.id);

									if ( ! fileRow.length ) {
										let $cloned = $uploader.find('.um-uploader-file-placeholder').clone().addClass('um-uploader-file um-upload-failed').removeClass('um-uploader-file-placeholder um-display-none').attr('id',err.file.id);
										$fileList.append( $cloned );

										fileRow = $fileList.find('#' + err.file.id);
										fileRow.find('.um-uploader-file-name').text(err.file.name);
										let extension = err.file.name.split('.').pop();
										if ( '' === err.file.type ) {
											extension = 'file';
										}
										fileRow.find('.um-file-extension-text').text(extension);
									} else {
										fileRow.addClass('um-upload-failed');
									}

									fileRow.find('.um-supporting-text').html(err.message);
									fileRow.find('.um-progress-bar-wrapper').remove();

									wp.hooks.doAction( 'um_uploader_after_file_row_error', $uploader, up, err, fileRow );
								}
							}
						},
						FileFiltered: function ( up, file ) {
							let actionInFilter = wp.hooks.applyFilters( 'um_uploader_file_filtered', null, $button, up, file );
							if ( null === actionInFilter ) {
								if ( $fileList.length ) {
									$fileList.umShow();

									// flush files list if there is only 1 file can be uploaded.
									if ( ! up.getOption( 'multi_selection' ) ) {
										$fileList.find( '.um-uploader-file' ).each( function ( u, item ) {
											up.removeFile( item.id );
										} );
									}

									let fileRow = $fileList.find('#' + file.id);

									if ( ! fileRow.length ) {
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

										if ( $fileList.hasClass('um-uploader-filelist-sortable') ) {
											$fileList.sortable();
										}
									}
								}
							}
						},
						FilesAdded: function ( up, files ) {
							wp.hooks.doAction( 'um_uploader_files_added', $uploader, up, files );
							if ( files.length ) {
								up.start();
							}
						},
						FilesRemoved: function ( up, files ) {
							if ( files.length ) {
								jQuery.each( files, function ( i, file ) {
									jQuery( '#' + file.id ).remove();
								});
							}
							wp.hooks.doAction( 'um_uploader_files_removed', $uploader, up, files );
						},
						FileUploaded: function ( up, file, result ) {
							if ( result.status === 200 && result.response ) {
								let response = JSON.parse( result.response );
								if ( ! response ) {
									let actionInFilter = wp.hooks.applyFilters( 'um_uploader_file_upload_failed', null, $button, up, file, response );
									if ( null === actionInFilter ) {
										if ( $fileList.length ) {
											let fileRow = $fileList.find('#' + file.id);
											fileRow.find('.um-supporting-text').text( wp.i18n.__( 'File was not loaded. Wrong file upload server response.', 'ultimate-member' ) );
											fileRow.addClass('um-upload-failed');
											fileRow.find('.um-progress-bar-wrapper').remove();
										}
									}
								} else if ( response.info && response.OK === 0 ) {
									let actionInFilter = wp.hooks.applyFilters( 'um_uploader_file_upload_failed', null, $button, up, file, response );
									if ( null === actionInFilter ) {
										if ( $fileList.length ) {
											let fileRow = $fileList.find('#' + file.id);
											fileRow.find('.um-supporting-text').text( response.info );
											fileRow.addClass('um-upload-failed');
											fileRow.find('.um-progress-bar-wrapper').remove();
										}
									}
								} else if ( response.data ) {
									let actionInFilter = wp.hooks.applyFilters( 'um_uploader_file_uploaded', null, $button, up, file, response );
									if ( null === actionInFilter ) {
										// some default process.
										$uploader.addClass('um-upload-completed');
										if ( $fileList.length ) {
											let fileRow = $fileList.find('#' + file.id);

											fileRow.data('filename', response.data[0].name_saved).data('nonce', response.data[0].delete_nonce);
											fileRow.find('.um-progress-bar-wrapper').remove();
										}
									}
								}

							} else {
								let actionInFilter = wp.hooks.applyFilters( 'um_uploader_file_upload_failed', null, $button, up, file, null );
								if ( null === actionInFilter ) {
									if ( $fileList.length ) {
										let fileRow = $fileList.find('#' + file.id);
										fileRow.find('.um-supporting-text').text( wp.i18n.__( 'File was not loaded, Status Code ', 'ultimate-member' ) + result.status );
										fileRow.addClass('um-upload-failed');
										fileRow.find('.um-progress-bar-wrapper').remove();
									}
								}
							}
						},
						PostInit: function ( up ) {
						},
						UploadProgress: function ( up, file ) {
							wp.hooks.doAction( 'um_uploader_upload_progress', $uploader, up, file );
							let $bar = jQuery( '#' + file.id ).find( '.um-progress-bar' );
							if ( $bar.length ) {
								UM.frontend.progressBar.set( $bar, file.percent );
							}
						},
						UploadComplete: function ( up, files ) {
							wp.hooks.doAction( 'um_uploader_upload_complete', $uploader, up, files );
						}
					}
				};

				if ( $dropZone.length ) {
					uploaderData.drop_element = $dropZone.get( 0 );
				}

				uploaderData = wp.hooks.applyFilters( 'um_uploader_data', uploaderData, handler, $button );
				if ( ! uploaderData.url ) {
					return;
				}

				let uploaderObj = new plupload.Uploader( uploaderData );
				UM.frontend.uploaders[ uploaderObj['id'] ] = uploaderObj;
				$uploader.data('plupload',uploaderObj['id']);
				uploaderObj.init();

				if ( $fileList.length && $fileList.hasClass('um-uploader-filelist-sortable') ) {
					let actionInFilter = wp.hooks.applyFilters( 'um_uploader_init_sortable_filelist', null, $fileList, handler );
					if ( null === actionInFilter ) {
						$fileList.sortable();
					}
				}
			});
		},
		initActions: function () {
			// Dropzone activity handlers.
			jQuery(document.body).on('dragover', '.um-uploader-dropzone', function ( ev ){
				let dropzoneTarget = ev.target;
				if ( ! ev.target.classList.contains('um-uploader-dropzone') ) {
					dropzoneTarget = dropzoneTarget.closest('.um-uploader-dropzone');
				}

				jQuery(dropzoneTarget).attr('drop-active', true);
				ev.preventDefault();
			});

			jQuery(document.body).on('dragleave', '.um-uploader-dropzone', function ( ev ){
				ev.preventDefault();
				let dropzoneTarget = ev.target;
				if ( ! ev.target.classList.contains('um-uploader-dropzone') ) {
					dropzoneTarget = dropzoneTarget.closest('.um-uploader-dropzone');
				}

				jQuery(dropzoneTarget).removeAttr('drop-active');
			});

			jQuery(document.body).on('drop', '.um-uploader-dropzone', function ( ev ){
				ev.preventDefault();

				let dropzoneTarget = ev.target;
				if ( ! ev.target.classList.contains('um-uploader-dropzone') ) {
					dropzoneTarget = dropzoneTarget.closest('.um-uploader-dropzone');
				}
				jQuery(dropzoneTarget).removeAttr('drop-active');
			});

			// Duplicate uploader link.
			jQuery(document.body).on('click', '.um-upload-link:not(.um-link-disabled)', function(e) {
				e.preventDefault();
				jQuery(this).parents('.um-uploader').find('.um-uploader-button').trigger('click');
			});

			// Default remove file from uploader queue button.
			jQuery(document.body).on('click', '.um-uploader-file-remove', function() {
				if ( ! confirm( wp.i18n.__( 'Are you sure that you want to delete this file?', 'ultimate-member' ) ) ) {
					return false;
				}

				let $uploader = jQuery(this).parents('.um-uploader');
				let $fileRow  = jQuery(this).parents('.um-uploader-file');

				let removeRow = function () {
					let fileID = $fileRow.attr('id');
					let uploaderObj = UM.frontend.uploaders[ $uploader.data('plupload') ];

					wp.hooks.doAction( 'um_uploader_file_row_removed', $fileRow, fileID, uploaderObj );

					let filter = uploaderObj.getOption( 'filters' ).um_files_limit;

					uploaderObj.removeFile( fileID );
					$fileRow.remove();

					if ( filter && uploaderObj.files.length < filter ) {
						let button = uploaderObj.getOption( 'browse_button' )[0];
						button.removeAttribute('disabled');

						let dropZone = uploaderObj.getOption( 'drop_element' )[0];
						if ( dropZone ) {
							dropZone.classList.remove('um-dropzone-disabled');
							let uploadLink = dropZone.querySelector( '.um-upload-link' );
							if ( uploadLink ) {
								uploadLink.classList.remove('um-link-disabled');
							}
						}
					}

					wp.hooks.doAction( 'um_uploader_after_file_row_removed', $uploader, fileID, uploaderObj );
				}

				let fileID = $fileRow.attr('id');
				if ( fileID && ! $fileRow.hasClass('um-upload-failed') ) {
					let fileName = $fileRow.data('filename');
					let nonce = $fileRow.data('nonce');

					// then file can be removed from server.
					wp.ajax.send(
						'um_delete_temp_file',
						{
							data: {
								name: fileName,
								nonce: nonce
							},
							success: function () {
								removeRow();
							},
							error: function (data) {
								alert(data);
								console.log(data);
							}
						}
					);
				} else {
					removeRow();
				}
			});

			// Image/File type fields controls.
			jQuery(document.body).on('click', '.um-field-image-change', function() {
				if ( ! confirm( wp.i18n.__( 'Are you sure that you want to change this image?', 'ultimate-member' ) ) ) {
					return false;
				}

				let $wrapper = jQuery(this).parents('.um-field-uploader-wrapper');
				let $uploader = $wrapper.find('.um-uploader');

				$uploader.find('.um-uploader-button').trigger( 'click' );
			});

			jQuery(document.body).on('click', '.um-field-image-remove', function() {
				let confirmText = wp.i18n.__( 'Are you sure that you want to delete this image?', 'ultimate-member' );
				confirmText = wp.hooks.applyFilters( 'um-field-image-remove-confirm-text', confirmText, jQuery(this) );
				if ( confirmText && ! confirm( confirmText ) ) {
					return false;
				}

				let $controls = jQuery(this).parents('.um-field-image-controls');
				let $wrapper = jQuery(this).parents('.um-field-uploader-wrapper');
				let $uploader = $wrapper.find('.um-uploader');
				let $fileRow  = $uploader.find('.um-uploader-file');
				let $dropZone  = $uploader.find( '.um-uploader-dropzone' );
				let $fileList  = $uploader.find( '.um-uploader-filelist' );

				let removeRow = function () {
					let fileID = $fileRow.attr('id');
					if ( ! fileID ) {
						$fileRow.remove();
						$dropZone.umShow();
						$controls.umHide();
						$fileList.html('').umHide();
						$uploader.removeClass('um-upload-completed');
						return;
					}

					let uploaderObj = UM.frontend.uploaders[ $uploader.data('plupload') ];

					wp.hooks.doAction( 'um_uploader_file_row_removed', $fileRow, fileID, uploaderObj );

					let filter = uploaderObj.getOption( 'filters' ).um_files_limit;

					uploaderObj.removeFile( fileID );
					$fileRow.remove();
					$dropZone.umShow();
					$controls.umHide();
					$fileList.html('').umHide();
					$uploader.removeClass('um-upload-completed');

					if ( filter && uploaderObj.files.length < filter ) {
						let button = uploaderObj.getOption( 'browse_button' )[0];
						button.removeAttribute('disabled');

						let dropZone = uploaderObj.getOption( 'drop_element' )[0];
						if ( dropZone ) {
							dropZone.classList.remove('um-dropzone-disabled');
							let uploadLink = dropZone.querySelector( '.um-upload-link' );
							if ( uploadLink ) {
								uploadLink.classList.remove('um-link-disabled');
							}
						}
					}

					wp.hooks.doAction( 'um_uploader_after_file_row_removed', $uploader, fileID, uploaderObj );
				}

				let fileID = $fileRow.attr('id');
				if ( fileID && ! $fileRow.hasClass('um-upload-failed') ) {
					let fileName = $fileRow.data('filename');
					let nonce = $fileRow.data('nonce');

					// then file can be removed from server.
					wp.ajax.send(
						'um_delete_temp_file',
						{
							data: {
								name: fileName,
								nonce: nonce
							},
							success: function () {
								removeRow();
							},
							error: function (data) {
								alert(data);
								console.log(data);
							}
						}
					);
				} else {
					removeRow();
				}
			});

			jQuery(document.body).on('click', '.um-field-file-change', function() {
				if ( ! confirm( wp.i18n.__( 'Are you sure that you want to change this file?', 'ultimate-member' ) ) ) {
					return false;
				}

				let $wrapper = jQuery(this).parents('.um-field-uploader-wrapper');
				let $uploader = $wrapper.find('.um-uploader');

				$uploader.find('.um-uploader-button').trigger( 'click' );
			});

			jQuery(document.body).on('click', '.um-field-file-remove', function() {
				if ( ! confirm( wp.i18n.__( 'Are you sure that you want to delete this file?', 'ultimate-member' ) ) ) {
					return false;
				}

				let $controls = jQuery(this).parents('.um-field-file-controls');
				let $wrapper = jQuery(this).parents('.um-field-uploader-wrapper');
				let $uploader = $wrapper.find('.um-uploader');
				let $fileRow  = $uploader.find('.um-uploader-file');
				let $dropZone  = $uploader.find( '.um-uploader-dropzone' );
				let $fileList  = $uploader.find( '.um-uploader-filelist' );

				let removeRow = function () {
					let fileID = $fileRow.attr('id');
					if ( ! fileID ) {
						$fileRow.remove();
						$dropZone.umShow();
						$controls.umHide();
						$fileList.html('').umHide();
						$uploader.removeClass('um-upload-completed');
						return;
					}

					let uploaderObj = UM.frontend.uploaders[ $uploader.data('plupload') ];

					wp.hooks.doAction( 'um_uploader_file_row_removed', $fileRow, fileID, uploaderObj );

					let filter = uploaderObj.getOption( 'filters' ).um_files_limit;

					uploaderObj.removeFile( fileID );
					$fileRow.remove();
					$dropZone.umShow();
					$controls.umHide();
					$fileList.html('').umHide();
					$uploader.removeClass('um-upload-completed');

					if ( filter && uploaderObj.files.length < filter ) {
						let button = uploaderObj.getOption( 'browse_button' )[0];
						button.removeAttribute('disabled');

						let dropZone = uploaderObj.getOption( 'drop_element' )[0];
						if ( dropZone ) {
							dropZone.classList.remove('um-dropzone-disabled');
							let uploadLink = dropZone.querySelector( '.um-upload-link' );
							if ( uploadLink ) {
								uploadLink.classList.remove('um-link-disabled');
							}
						}
					}

					wp.hooks.doAction( 'um_uploader_after_file_row_removed', $uploader, fileID, uploaderObj );
				}

				let fileID = $fileRow.attr('id');
				if ( fileID && ! $fileRow.hasClass('um-upload-failed') ) {
					let fileName = $fileRow.data('filename');
					let nonce = $fileRow.data('nonce');

					// then file can be removed from server.
					wp.ajax.send(
						'um_delete_temp_file',
						{
							data: {
								name: fileName,
								nonce: nonce
							},
							success: function () {
								removeRow();
							},
							error: function (data) {
								alert(data);
								console.log(data);
							}
						}
					);
				} else {
					removeRow();
				}
			});
		}
	},
	url: {
		parseData: function () {
			let data = {};

			let query = window.location.search.substring(1);
			let attrs = query.split( '&' );
			jQuery.each( attrs, function( i ) {
				let attr = attrs[ i ].split( '=' );
				data[ attr[0] ] = attr[1];
			});
			return data;
		},
		setURLSearchParam: function( key, value ) {
			const url = new URL(window.location.href);
			url.searchParams.set(key, value);
			window.history.pushState({ path: url.href }, '', url.href);
		},
		deleteURLSearchParam: function( key, reload = false ) {
			const url = new URL(window.location.href);
			url.searchParams.delete(key);
			if ( reload ) {
				window.location.assign( url );
			} else {
				window.history.pushState({ path: url.href }, '', url.href);
			}
		}
	},
	image: {
		lazyload: {
			init: function () {
				let $lazyloaded = document.querySelectorAll('.um-image-lazyload-wrapper:not(.um-inited)');
				for (let $item = 0; $item < $lazyloaded.length; $item++) {
					$lazyloaded[ $item ].classList.add('um-inited');
					let $img = $lazyloaded[ $item ].querySelector('.um-image-lazyload');

					if ( $img.complete ) {
						$lazyloaded[ $item ].classList.add('um-loaded');
					} else {
						// @todo think about `error` event when image wasn't loaded.
						$img.addEventListener('load', (e) => {
							$lazyloaded[ $item ].classList.add('um-loaded');
						}, false);
					}
				}
			}
		}
	},
}


wp.hooks.addAction( 'um_remove_modal', 'um_common_frontend', function() {
	UM.frontend.cropper.destroy();
});

wp.hooks.addAction( 'um_after_removing_preview', 'um_common_frontend', function() {
	UM.frontend.cropper.destroy();
});

wp.hooks.addAction( 'um_window_resize', 'um_common_frontend', function() {
	UM.frontend.cropper.destroy();
});

wp.hooks.addAction( 'um_member_directory_loaded', 'um_common_frontend', function() {
	UM.frontend.dropdown.init();
});

wp.hooks.addAction( 'um_member_directory_build_template', 'um_common_frontend', function() {
	UM.frontend.dropdown.init();
});

jQuery(document).ready(function($) {
	UM.frontend.dropdown.init();
	UM.frontend.toggleElements.init();
	UM.frontend.progressBar.init();
	UM.frontend.uploader.init();
	UM.frontend.uploader.initActions();

	UM.frontend.image.lazyload.init();

	$( window ).on( 'resize', function() {
		UM.frontend.responsive.setClass();
	});

	$(document.body).on('click', '.um-alert-dismiss', function (e) {
		e.preventDefault();
		$(this).parents('.um-alert').umHide();
	});
});

jQuery( window ).on( 'load', function() {
	UM.frontend.responsive.setClass();
});