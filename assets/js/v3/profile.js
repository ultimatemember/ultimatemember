wp.hooks.addFilter( 'um_uploader_data', 'ultimate-member', function( uploaderData, handler, $button ) {
	if ( 'field-image' !== handler && 'field-file' !== handler ) {
		return uploaderData;
	}

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

	return uploaderData;
});

wp.hooks.addFilter( 'um_uploader_file_uploaded', 'ultimate-member', function( preventDefault, $button, up, file, response ) {
	let handler = $button.data( 'handler' );
	if ( 'field-image' !== handler && 'field-file' !== handler ) {
		return preventDefault;
	}

	let $uploader = $button.parents( '.um-uploader' );
	let $fileList = $uploader.find( '.um-uploader-filelist' );

	if ( $fileList.length ) {
		let fileRow = $fileList.find( '#' + file.id );
		fileRow.find( '.um-uploaded-value' ).val( response.data[0].name_saved );
		fileRow.find( '.um-uploaded-value-hash' ).val( response.data[0].hash );
	}

	return null;
});

jQuery(document).ready(function() {

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

	jQuery( document.body ).on( 'click', '.um-user-action.um_delete', function(e) {
		e.preventDefault();
		// Using wp.hooks here for workaround and integrate um-dropdown links and js.confirm
		if ( ! confirm( wp.i18n.__( 'Are you sure that you want to delete this user?', 'ultimate-member' ) ) ) {
			wp.hooks.addFilter( 'um_dropdown_link_result', 'ultimate-member', function( result, attrClass ) {
				if ( 'um-user-action um_delete um-destructive' !== attrClass ) {
					return result;
				}
				return false;
			});
			return false;
		} else {
			wp.hooks.removeFilter( 'um_dropdown_link_result', 'ultimate-member' );
		}
	});

	jQuery( document.body ).on( 'click', '.um-user-action.um_switch_user', function(e) {
		e.preventDefault();

		if ( ! confirm( wp.i18n.__( 'Are you sure that you want to switch to this user? It breaks your current session.', 'ultimate-member' ) ) ) {
			wp.hooks.addFilter( 'um_dropdown_link_result', 'ultimate-member', function( result, attrClass ) {
				if ( 'um-user-action um_switch_user' !== attrClass ) {
					return result;
				}
				return false;
			});
			return false;
		} else {
			wp.hooks.removeFilter( 'um_dropdown_link_result', 'ultimate-member' );
		}
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
