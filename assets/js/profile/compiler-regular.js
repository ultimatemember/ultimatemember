// Standard.js - which is loaded when there isn't recaptcha

// common.js
if ( typeof ( window.UM ) !== 'object' ) {
	window.UM = {};
}

if ( typeof ( UM.common ) !== 'object' ) {
	UM.common = {};
}

UM.common = {
	responsive: {
		resolutions: { //important order by ASC
			xs: 320,
			s:  576,
			m:  768,
			l:  992,
			xl: 1024
		},
		getSize: function( number ) {
			let responsive = UM.common.responsive;
			for ( let key in responsive.resolutions ) {
				if ( responsive.resolutions.hasOwnProperty( key ) && responsive.resolutions[ key ] === number ) {
					return key;
				}
			}

			return false;
		},
		setClass: function() {
			let responsive = UM.common.responsive;
			let $resolutions = Object.values( responsive.resolutions );
			$resolutions.sort( function(a, b){ return b-a; });

			jQuery('.um').each( function() {
				let obj = jQuery(this);
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
};

jQuery.ajaxSetup({
	beforeSend: function( jqXHR, settings ) {
		if ( settings.processData ) {
			if ( settings.data !== '' ) {
				settings.data += '&um_current_locale=' + um_common_variables.locale;
			} else {
				settings.data = 'um_current_locale=' + um_common_variables.locale;
			}
		} else {
			settings.data = jQuery.extend(
				settings.data,
				{
					um_current_locale: um_common_variables.locale
				}
			);
		}

		return true;
	}
});

jQuery( document ).ready( function($) {
	$( window ).on( 'resize', function() {
		UM.common.responsive.setClass();
	});
});

jQuery( window ).on( 'load', function() {
	UM.common.responsive.setClass();
});


// forms.js
if ( typeof ( window.UM ) !== 'object' ) {
	window.UM = {};
}

if ( typeof ( UM.forms ) !== 'object' ) {
	UM.forms = {};
}

UM.forms = {
	honeypot: function () {
		// flush fields using honeypot security
		jQuery('input[name="' + umProfile.honeypot + '"]').val('');
	}
};

jQuery( window ).on( 'load', function() {
	UM.forms.honeypot();
});


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
	avatarUploaderInit: function () {
		var um_media_uploader;
		var um_media_uploaders = {};

		jQuery('.um-change-profile-photo').each( function() {
			var $button = jQuery(this);
			var $user_id = jQuery(this).data('user_id');
			var $filelist = $button.parents('.jb-uploader-dropzone');
			var $button_wrapper = $button.parents('.jb-select-media-button-wrapper');
			var $errorlist = $filelist.siblings( '.jb-uploader-errorlist' );
			var extensions = 'jpg,jpeg,gif,png,bmp,ico,tiff';

			var uploader_args = {
				browse_button: $button.get( 0 ), // you can pass in id...
				url: wp.ajax.settings.url + '?action=um_upload_profile_photo&user_id=' + $user_id + '&nonce=' + um_scripts.nonce,
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
						$errorlist.html( '<p>' + wp.i18n.__( 'Error!', 'ultimate-member' ) + ' ' + err.message + '</p>' );
					},
					FileFiltered: function ( up, file ) {

						$errorlist.empty();

						if ( ! up.getOption( 'multi_selection' ) ) {
							$filelist.find( '.jb-uploader-file' ).each( function ( u, item ) {
								up.removeFile( item.id );
							} );
						}
					},
					FilesAdded: function ( up, files ) {
						jQuery( '.um-profile-photo' ).find('.um-profile-photo-overlay').show();
						up.start();
					},
					FilesRemoved: function ( up, files ) {
						$.each( files, function ( i, file ) {
							jQuery( '#' + file.id ).remove();
						} );

						if ( ! $filelist.find( '.jb-uploader-file' ).length ) {
							$errorlist.empty();
						}
					},
					FileUploaded: function ( up, file, result ) {
						if ( result.status === 200 && result.response ) {

							var response = JSON.parse( result.response );

							if ( ! response ) {
								$errorlist.append( '<p>' + wp.i18n.__( 'Error! Wrong file upload server response.', 'ultimate-member' ) + '</p>' );
							} else if ( response.info && response.OK === 0 ) {
								console.error( response.info );
							} else if ( response.data ) {

								// $button.parents('.jb-uploader').addClass( 'jb-uploaded' );
								// $button.parents('.jb-uploader').siblings('.jb-uploaded-wrapper').addClass( 'jb-uploaded' ).removeClass('jb-waiting-change');
								// $button.parents('.jb-uploader').siblings('.jb-uploaded-wrapper').find('img').attr( 'src', response.data[0].url );
								// wp.hooks.doAction( 'jb_job_uploader_after_success_upload', $button, response );
								//
								// $button.parents('.jb-uploader').siblings('.jb-media-value').val( response.data[0].name_saved );
								// $button.parents('.jb-uploader').siblings('.jb-media-value-hash').val( response.data[0].hash );

								var settings = {
									// These are the defaults.
									classes:  'um-profile-photo-modal',
									duration: 400, // ms
									footer:   '',
									header:   wp.i18n.__( 'Change your profile photo', 'ultimate-member' ),
									size:     'normal', // small, normal, large
									content:  '<div class="um-profile-photo-crop-wrapper"><img src="' + response.data[0].url + '" class="um-profile-photo-crop" /></div><div class="um-modal-buttons-wrapper"><a href="javascript:void(0);" class="um-button um-button-primary um-apply-avatar-crop" data-user_id="' + $user_id + '">' + wp.i18n.__( 'Apply', 'ultimate-member' ) + '</a><a class="um-button um-modal-avatar-decline" href="javascript:void(0);">' + wp.i18n.__( 'Cancel', 'ultimate-member' ) + '</a></div>',
								};

								settings.relatedButton = jQuery('.um-profile-photo').umModal( settings );

								UM.profile.avatarModal = UM.modal.addModal( settings, null );
							}

						} else {
							// translators: %s is the error status code.
							console.error( wp.i18n.__( 'File was not loaded, Status Code %s', 'ultimate-member' ), [ result.status ] );
						}
					},
					PostInit: function ( up ) {
						$filelist.find( '.jb-uploader-file' ).remove();
					},
					UploadProgress: function ( up, file ) {
						jQuery( '#' + file.id ).find( 'b' ).html( '<span>' + file.percent + '%</span>' );
					},
					UploadComplete: function ( up, files ) {
					}
				}
			};
			uploader_args = wp.hooks.applyFilters( 'jb_job_uploader_filters_attrs', uploader_args, $button );

			um_media_uploader = new plupload.Uploader( uploader_args );
			um_media_uploaders[ um_media_uploader['id'] ] = um_media_uploader;
			um_media_uploader.init();

			jQuery(this).parents('.jb-form-field-content').attr('data-uploader', um_media_uploader['id']);
		});
	},
};

jQuery( document ).ready( function($) {
	jQuery( document.body ).on( 'click', '.um-apply-avatar-crop', function(e){
		e.preventDefault();

		var elem = jQuery(this);
		var key = jQuery(this).attr('data-key');
		var img_c = jQuery(this).parents('.um-modal-body').find('.um-single-image-preview');
		//var src = jQuery( '.um-profile-photo-crop' ).attr('src');
		var coord = UM.profile.avatarCropper.cropper('getData');
		var file = img_c.find('img').data('file');

		var user_id = elem.data('user_id');

		var d;
		var form_id = 0;
		var mode = '';
		if ( jQuery('div.um-field-image[data-key="' + key + '"]').length === 1 ) {
			var $formWrapper = jQuery('div.um-field-image[data-key="' + key + '"]').closest('.um-form');
			form_id = $formWrapper.find('input[name="form_id"]').val();
			mode = $formWrapper.attr('data-mode');
		}

		if ( coord ) {

			jQuery(this).html( jQuery(this).attr('data-processing') ).addClass('disabled');

			wp.ajax.send( 'um_resize_avatar', {
				data: {
					coord : coord,
					user_id : user_id,
					nonce: um_scripts.nonce
				},
				success: function( response ) {
					d = new Date();
					jQuery('.um-profile-photo img').attr('src', response.avatar + "?"+d.getTime() );

					jQuery( '.um-profile-photo' ).find('.um-profile-photo-overlay').hide();
					jQuery( '.um-profile-photo' ).find('.um-new-dropdown').find( 'li' ).hide();
					jQuery.each( response.actions, function(i) {
						jQuery( '.um-profile-photo' ).find('.um-new-dropdown').find( '.' + response.actions[i] ).parents('li').show();
					});

					if ( null !== UM.profile.avatarCropper ) {
						UM.profile.avatarCropper.cropper( 'destroy' );
					}
					UM.modal.destroy( UM.profile.avatarModal );
					//
					// if ( key === 'profile_photo' ) {
					// 	jQuery('.um-profile-photo-img img').attr('src', response.data.image.source_url + "?"+d.getTime());
					// } else if ( key === 'cover_photo' ) {
					// 	jQuery('.um-cover-e').empty().html('<img src="' + response.data.image.source_url + "?"+d.getTime() + '" alt="" />');
					// 	if ( jQuery('.um').hasClass('um-editing') ) {
					// 		jQuery('.um-cover-overlay').show();
					// 	}
					// }
					//
					// jQuery('.um-single-image-preview[data-key='+key+']').fadeIn().find('img').attr('src', response.data.image.source_url + "?"+d.getTime());
					//
					// um_remove_modal();
					//
					// jQuery('img.cropper-invisible').remove();
					//
					// jQuery('.um-single-image-preview[data-key='+key+']').parents('.um-field').find('.um-btn-auto-width').html( elem.attr('data-change') );
					//
					// jQuery('.um-single-image-preview[data-key='+key+']').parents('.um-field').find('input[type="hidden"]').val( response.data.image.filename );
				},
				error: function (data) {
					console.log(data);
				}
			});

		}/* else {

			d = new Date();

			jQuery('.um-single-image-preview[data-key='+key+']').fadeIn().find('img').attr('src', src + "?"+d.getTime());

			um_remove_modal();

			jQuery('.um-single-image-preview[data-key='+key+']').parents('.um-field').find('.um-btn-auto-width').html( elem.attr('data-change') );

			jQuery('.um-single-image-preview[data-key='+key+']').parents('.um-field').find('input[type=hidden]').val( file );


		}*/
	});

	jQuery( document.body ).on( 'click', '.um-modal-avatar-decline', function(e){
		e.preventDefault();
		if ( null !== UM.profile.avatarCropper ) {
			UM.profile.avatarCropper.cropper( 'destroy' );
		}
		UM.modal.destroy( UM.profile.avatarModal );
		jQuery( '.um-profile-photo' ).find('.um-profile-photo-overlay').hide();
	});

	jQuery( document.body ).on( 'click', '.um-reset-profile-photo', function(e){
		e.preventDefault();
		var user_id = jQuery(this).data('user_id');
		jQuery( '.um-profile-photo' ).find('.um-profile-photo-overlay').show();
		wp.ajax.send( 'um_reset_avatar', {
			data: {
				user_id : user_id,
				nonce: um_scripts.nonce
			},
			success: function( response ) {
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
		});
	});
});

jQuery(window).on( 'resize', function() {
	if ( null !== UM.profile.avatarCropper ) {
		UM.profile.avatarCropper.cropper( 'destroy' );
	}
	var $image = jQuery('.um-profile-photo-crop');

	if ( $image.length ) {
		UM.profile.avatarCropper = $image.cropper({
			aspectRatio: 1 / 1,
			crop: function(event) {
				// console.log(event.detail.x);
				// console.log(event.detail.y);
				// console.log(event.detail.width);
				// console.log(event.detail.height);
				// console.log(event.detail.rotate);
				// console.log(event.detail.scaleX);
				// console.log(event.detail.scaleY);
			}
		});
	}
});

wp.hooks.addAction( 'um_dropdown_render', 'ultimate-member', function ( dropdown, trigger, element, obj ) {
	// console.log( dropdown );
	// console.log( trigger );
	// console.log( element );
	// console.log( obj );
	// console.log( obj.hasClass('um-profile-photo') );

	if ( obj.hasClass('um-profile-photo') ) {
		UM.profile.avatarUploaderInit();
	}
}, 10, 4 );

wp.hooks.addAction( 'um-modal-shown', 'ultimate-member', function( $modal ) {
	var $image = $modal.find('.um-profile-photo-crop');

	if ( $image.length ) {
		UM.profile.avatarCropper = $image.cropper({
			aspectRatio: 1 / 1,
			crop: function(event) {
				// console.log(event.detail.x);
				// console.log(event.detail.y);
				// console.log(event.detail.width);
				// console.log(event.detail.height);
				// console.log(event.detail.rotate);
				// console.log(event.detail.scaleX);
				// console.log(event.detail.scaleY);
			}
		});
	}
}, 10, 1 );
