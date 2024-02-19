// profile.js
if ( typeof ( window.UM ) !== 'object' ) {
	window.UM = {};
}

if ( typeof ( UM.profile ) !== 'object' ) {
	UM.profile = {};
}

UM.profile = {
	avatarModal: null,
	avatarCropper: null,
	avatarUploaderObj: null,
	avatarUploaderObjs: [],
	avatarUploaderInit: function () {
		jQuery('.um-profile-photo-uploader-overflow').each( function() {
			let $button = jQuery(this);
			let userID = $button.data('user_id');
			let nonce = $button.data('nonce');
			let nonceApply = $button.data('apply_nonce');
			let nonceDecline = $button.data('decline_nonce');
			let extensions = 'jpg,jpeg,gif,png,bmp,ico,tiff';


			// var $filelist = $button.parents('.jb-uploader-dropzone');
			// var $errorlist = $filelist.siblings( '.jb-uploader-errorlist' );


			let uploader_args = {
				browse_button: $button.get( 0 ), // you can pass in id...
				url: wp.ajax.settings.url + '?action=um_upload_profile_photo&user_id=' + userID + '&nonce=' + nonce,
				chunk_size: '1024kb',
				max_retries: 1,
				multipart: true,
				multi_selection: false,
				filters: {
					max_file_size: '10mb',
					mime_types: [
						{ title: wp.i18n.__( 'Image files', 'ultimate-member' ), extensions: extensions },
					],
					prevent_duplicates: true,
					max_file_count: 1
				},
				init: {
					Error: function ( up, err ) {
						// $errorlist.html( '<p>' + wp.i18n.__( 'Error!', 'ultimate-member' ) + ' ' + err.message + '</p>' );
					},
					FileFiltered: function ( up, file ) {

						// $errorlist.empty();
						//
						// if ( ! up.getOption( 'multi_selection' ) ) {
						// 	$filelist.find( '.jb-uploader-file' ).each( function ( u, item ) {
						// 		up.removeFile( item.id );
						// 	} );
						// }
					},
					FilesAdded: function ( up, files ) {
						$button.parents('.um-profile-photo-uploader').addClass('um-processing');
						up.start();
					},
					FilesRemoved: function ( up, files ) {
						// $.each( files, function ( i, file ) {
						// 	jQuery( '#' + file.id ).remove();
						// } );
						//
						// if ( ! $filelist.find( '.jb-uploader-file' ).length ) {
						// 	$errorlist.empty();
						// }
					},
					FileUploaded: function ( up, file, result ) {
						if ( result.status === 200 && result.response ) {

							let response = JSON.parse( result.response );

							if ( ! response ) {
								// $errorlist.append( '<p>' + wp.i18n.__( 'Error! Wrong file upload server response.', 'ultimate-member' ) + '</p>' );
							} else if ( response.info && response.OK === 0 ) {
								console.error( response.info );
							} else if ( response.data ) {
								let settings = {
									// These are the defaults.
									classes:  'um-profile-photo-modal',
									duration: 400, // ms
									footer:   '',
									header:   wp.i18n.__( 'Change your profile photo', 'ultimate-member' ),
									size:     'normal', // small, normal, large
									content:  '<div class="um-profile-photo-crop-wrapper" data-crop="square" data-ratio="1" data-min_width="256" data-min_height="256"><img src="' + response.data[0].url + '" class="um-profile-photo-crop fusion-lazyload-ignore" alt="" /></div><div class="um-modal-buttons-wrapper"><button type="button" class="um-button um-button-primary um-button-size-m um-apply-avatar-crop" data-user_id="' + userID + '" data-nonce="' + nonceApply + '">' + wp.i18n.__( 'Apply', 'ultimate-member' ) + '</button><button type="button" class="um-button um-button-size-m um-modal-avatar-decline" data-user_id="' + userID + '" data-nonce="' + nonceDecline + '">' + wp.i18n.__( 'Cancel', 'ultimate-member' ) + '</button><span class="um-ajax-spinner-svg um-ajax-spinner-s"><svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 48 48" fill="none">' +
										'<path d="M45 24C45 26.7578 44.4568 29.4885 43.4015 32.0364C42.3461 34.5842 40.7993 36.8992 38.8492 38.8492C36.8992 40.7993 34.5842 42.3461 32.0364 43.4015C29.4885 44.4568 26.7578 45 24 45C21.2422 45 18.5115 44.4568 15.9636 43.4015C13.4158 42.3461 11.1008 40.7993 9.15075 38.8492C7.20072 36.8992 5.65388 34.5842 4.59853 32.0363C3.54318 29.4885 3 26.7578 3 24C3 21.2422 3.54318 18.5115 4.59853 15.9636C5.65388 13.4158 7.20073 11.1008 9.15076 9.15075C11.1008 7.20072 13.4158 5.65387 15.9637 4.59853C18.5115 3.54318 21.2423 3 24 3C26.7578 3 29.4885 3.54318 32.0364 4.59853C34.5842 5.65388 36.8992 7.20073 38.8493 9.15077C40.7993 11.1008 42.3461 13.4158 43.4015 15.9637C44.4568 18.5115 45 21.2423 45 24L45 24Z" stroke="var(--um-gray-100,#f2f4f7)" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/>' +
										'<path d="M24 3C26.7578 3 29.4885 3.54318 32.0364 4.59853C34.5842 5.65388 36.8992 7.20073 38.8492 9.15076C40.7993 11.1008 42.3461 13.4158 43.4015 15.9637C44.4568 18.5115 45 21.2422 45 24" stroke="var(--um-primary-600-bg,#7f56d9)" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/>' +
										'</svg></span></div>',
									source: $button
								};

								UM.profile.avatarModal = UM.modal.addModal( settings, null );
							}

						} else {
							// translators: %s is the error status code.
							console.error( wp.i18n.__( 'File was not loaded, Status Code %s', 'ultimate-member' ), [ result.status ] );
						}
					},
					PostInit: function ( up ) {
						// $filelist.find( '.jb-uploader-file' ).remove();
					},
					UploadProgress: function ( up, file ) {
						// jQuery( '#' + file.id ).find( 'b' ).html( '<span>' + file.percent + '%</span>' );
					},
					UploadComplete: function ( up, files ) {
						jQuery.each( files, function(i) {
							up.removeFile( files[i].id );
						})
						$button.parents('.um-profile-photo-uploader').removeClass('um-processing');
					}
				}
			};
			uploader_args = wp.hooks.applyFilters( 'um_avatar_uploader_filters_attrs', uploader_args, $button );

			UM.profile.avatarUploaderObj = new plupload.Uploader( uploader_args );
			UM.profile.avatarUploaderObjs[ UM.profile.avatarUploaderObj['id'] ] = UM.profile.avatarUploaderObj;
			UM.profile.avatarUploaderObj.init();
		});
	},
};

jQuery(document).ready(function($) {
	UM.profile.avatarUploaderInit();

	/*$(document.body).on('click', '.um-reset-profile-photo', function(e) {
		e.preventDefault;
		let userID = $(this).data('user_id');
		let nonce = $(this).data('nonce');

		jQuery( '.um-profile-photo' ).find('.um-profile-photo-overlay').show();

		wp.ajax.send(
			'um_delete_profile_photo',
			{
				data: {
					user_id: userID,
					nonce: nonce
				},
				success: function ( response ) {
					jQuery( '.um-profile-photo' ).find('.avatar').replaceWith( response.avatar );

					jQuery( '.um-profile-photo' ).find('.um-new-dropdown').find( 'li' ).hide();
					jQuery.each( response.actions, function(i) {
						jQuery( '.um-profile-photo' ).find('.um-new-dropdown').find( '.' + response.actions[i] ).parents('li').show();
					});

					jQuery( '.um-profile-photo' ).find('.um-profile-photo-overlay').hide();
				},
				error: function (data) {
					console.log(data);
				}
			}
		);
	});*/

	$( document.body ).on( 'click', '.um-modal-avatar-decline', function(e){
		e.preventDefault();

		let $button = $(this);
		let $loader = $button.siblings('.um-ajax-spinner-svg');
		let $buttons = $button.parents('.um-modal-buttons-wrapper').find('.um-button');
		let userID = $button.data('user_id');
		let nonce = $button.data('nonce');

		$buttons.prop('disabled',true);
		$loader.show();

		wp.ajax.send(
			'um_decline_profile_photo_change',
			{
				data: {
					user_id: userID,
					nonce: nonce
				},
				success: function () {
					$loader.hide();
					if ( UM.frontend.cropper.obj ) {
						// If Cropper object exists then destroy before re-init.
						UM.frontend.cropper.destroy();
					}
					UM.modal.close();
				},
				error: function (data) {
					$buttons.prop('disabled',false);
					$loader.hide();

					console.log(data);
				}
			}
		);
	});

	$( document.body ).on( 'click', '.um-apply-avatar-crop', function(e){
		e.preventDefault();

		let $button = $(this);
		if ( $button.parents('.um-modal-body').find('.cropper-hidden').length > 0 && UM.frontend.cropper.obj ) {
			let $loader = $button.siblings('.um-ajax-spinner-svg');
			let $buttons = $button.parents('.um-modal-buttons-wrapper').find('.um-button');
			let userID = $button.data('user_id');
			let nonce = $button.data('nonce');

			let cropperData = UM.frontend.cropper.obj.getData();
			let coord = Math.round(cropperData.x) + ',' + Math.round(cropperData.y) + ',' + Math.round(cropperData.width) + ',' + Math.round(cropperData.height);

			if ( coord ) {
				$buttons.prop('disabled',true);
				$loader.show();

				wp.ajax.send(
					'um_apply_profile_photo_change',
					{
					data: {
						coord : coord,
						user_id : userID,
						nonce: nonce
					},
					success: function( response ) {
						$('[data-um-modal-opened="1"]').siblings('.um-avatar').find('> img').replaceWith( response.avatar );
						if ( response.all_sizes ) {
							$.each( response.all_sizes, function(i) {
								$('.um-avatar-' + i + '[data-user_id="' + userID + '"]').find('> img').replaceWith( response.all_sizes[i] );
							})
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

wp.hooks.addAction( 'um-modal-shown', 'ultimate-member', function( $modal ) {
	let $image = $modal.find('.um-profile-photo-crop-wrapper');
	if ( $image.length ) {
		UM.frontend.cropper.init();
	}
}, 10, 1 );

wp.hooks.addAction( 'um-modal-before-close', 'ultimate-member', function( $modal ) {
	if ( $modal.find( '.um-modal-avatar-decline:not(:disabled)' ).length ) {
		$modal.find( '.um-modal-avatar-decline' ).trigger('click');
	}
	if ( UM.frontend.cropper.obj ) {
		// If Cropper object exists then destroy before re-init.
		UM.frontend.cropper.destroy();
	}
});