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
