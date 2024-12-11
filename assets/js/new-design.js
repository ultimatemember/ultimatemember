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
};

jQuery(document).ready(function($) {
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

	$( document.body ).on( 'click', '.um-modal-field-image-decline', function(e){
		e.preventDefault();

		// let $button = $(this);
		// let $loader = $button.siblings('.um-ajax-spinner-svg');
		// let $buttons = $button.parents('.um-modal-buttons-wrapper').find('.um-button');
		// let userID = $button.data('user_id');
		// let nonce = $button.data('nonce');
		//
		// $buttons.prop('disabled',true);
		// $loader.show();
		//
		// wp.ajax.send(
		// 	'um_decline_profile_photo_change',
		// 	{
		// 		data: {
		// 			user_id: userID,
		// 			nonce: nonce
		// 		},
		// 		success: function () {
		// 			$loader.hide();
		// 			if ( UM.frontend.cropper.obj ) {
		// 				// If Cropper object exists then destroy before re-init.
		// 				UM.frontend.cropper.destroy();
		// 			}
		// 			UM.modal.close();
		// 		},
		// 		error: function (data) {
		// 			$buttons.prop('disabled',false);
		// 			$loader.hide();
		//
		// 			console.log(data);
		// 		}
		// 	}
		// );
	});

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
	let $image = $modal.find('.um-modal-crop-wrapper');
	if ( $image.length ) {
		UM.frontend.cropper.init();
	}
}, 10, 1 );

wp.hooks.addAction( 'um-modal-shown', 'ultimate-member', function( $modal ) {
	let $image = $modal.find('.um-modal-crop-wrapper');
	if ( $image.length ) {
		UM.frontend.cropper.init();
	}
}, 10, 1 );

wp.hooks.addAction( 'um-modal-before-close', 'ultimate-member', function( $modal ) {
	if ( $modal.find( '.um-modal-avatar-decline:not(:disabled)' ).length ) {
		$modal.find( '.um-modal-avatar-decline' ).trigger('click');
	}

	if ( $modal.find( '.um-modal-field-image-decline:not(:disabled)' ).length ) {
		$modal.find( '.um-modal-field-image-decline' ).trigger('click');
	}

	if ( UM.frontend.cropper.obj ) {
		// If Cropper object exists then destroy before re-init.
		UM.frontend.cropper.destroy();
	}
});

jQuery(document).ready( function($) {
	jQuery( document.body ).on('click', '.um-toggle-password', function (){
		let parent = jQuery(this).closest('.um-field-area-password');
		let passwordField = parent.find('input');
		let type = passwordField.attr('type');
		if ( 'text' === type ) {
			passwordField.attr('type', 'password');
			jQuery(this).toggleClass('um-icon-eye um-icon-eye-disabled');
		} else {
			passwordField.attr('type', 'text');
			jQuery(this).toggleClass('um-icon-eye um-icon-eye-disabled');
		}
	});

	jQuery(document.body).on( 'keydown', '#single_user_password-export-request', function(e) {
		if ( jQuery(this).hasClass('um-error') ) {
			let hintMessage = jQuery(this).parents('form').find('#um_account_request_download_data').data('hint');

			let fieldWrapper = jQuery(this).parents('.um-field');
			fieldWrapper.find('.um-field-hint').removeClass( 'um-field-error' ).removeAttr('id').html( hintMessage );
			jQuery(this).removeClass( 'um-error' ).removeAttr('aria-errormessage');
		}
	});

	jQuery(document.body).on( 'keydown', '#single_user_password-erase-request', function(e) {
		if ( jQuery(this).hasClass('um-error') ) {
			let hintMessage = jQuery(this).parents('form').find('#um_account_request_erase_data').data('hint');

			let fieldWrapper = jQuery(this).parents('.um-field');
			fieldWrapper.find('.um-field-hint').removeClass( 'um-field-error' ).removeAttr('id').html( hintMessage );
			jQuery(this).removeClass( 'um-error' ).removeAttr('aria-errormessage');
		}
	});

	jQuery(document.body).on( 'click', '#um_account_request_download_data', function(e) {
		e.preventDefault();

		let requestData = {
			nonce: jQuery(this).data('nonce')
		};

		let loader = jQuery(this).siblings('.um-ajax-spinner-svg');
		loader.show();

		let passwordField = jQuery(this).parents('form').find('#single_user_password-export-request');
		let errorID = 'um-error-for-single_user_password-export-request';
		let fieldWrapper = passwordField.parents('.um-field');

		if ( passwordField.length ) {
			let password = passwordField.val();
			if ( '' === password ) {
				fieldWrapper.find('.um-field-hint').data('hint',fieldWrapper.find('.um-field-hint').html()).addClass( 'um-field-error' ).attr('id', errorID).html( jQuery(this).data('error') );
				passwordField.addClass( 'um-error' ).attr('aria-errormessage', errorID);
				loader.hide();
				return;
			}
			requestData.password = passwordField.val();
		}

		wp.ajax.send(
			'um_personal_data_export',
			{
				data: requestData,
				success: function(data) {
					passwordField.parents('.um-form-new').find('.um-form-submit').remove();
					passwordField.parents('.um-form-col').html( '<p class="um-supporting-text">' + data + '</p>' );
					loader.hide();
				},
				error: function (data) {
					if ( data['single_user_password-export-request'] ) {
						let errorID = 'um-error-for-single_user_password-export-request';

						let fieldWrapper = passwordField.parents('.um-field');
						fieldWrapper.find('.um-field-hint').data('hint',fieldWrapper.find('.um-field-hint').html()).addClass( 'um-field-error' ).attr('id', errorID).html( data['single_user_password-export-request'] );
						passwordField.addClass( 'um-error' ).attr('aria-errormessage', errorID);
					}
					loader.hide();
				}
			}
		);
	});

	jQuery(document.body).on( 'click', '#um_account_request_erase_data', function(e) {
		e.preventDefault();

		let requestData = {
			nonce: jQuery(this).data('nonce')
		};

		let loader = jQuery(this).siblings('.um-ajax-spinner-svg');
		loader.show();

		let passwordField = jQuery(this).parents('form').find('#single_user_password-erase-request');
		let errorID = 'um-error-for-single_user_password-erase-request';
		let fieldWrapper = passwordField.parents('.um-field');

		if ( passwordField.length ) {
			let password = passwordField.val();
			if ( '' === password ) {
				fieldWrapper.find('.um-field-hint').data('hint',fieldWrapper.find('.um-field-hint').html()).addClass( 'um-field-error' ).attr('id', errorID).html( jQuery(this).data('error') );
				passwordField.addClass( 'um-error' ).attr('aria-errormessage', errorID);
				loader.hide();
				return;
			}
			requestData.password = passwordField.val();
		}

		wp.ajax.send(
			'um_personal_data_erase',
			{
				data: requestData,
				success: function(data) {
					passwordField.parents('.um-form-new').find('.um-form-submit').remove();
					passwordField.parents('.um-form-col').html( '<p class="um-supporting-text">' + data + '</p>' );
					loader.hide();
				},
				error: function (data) {
					if ( data['single_user_password-erase-request'] ) {
						let errorID = 'um-error-for-single_user_password-erase-request';

						let fieldWrapper = passwordField.parents('.um-field');
						fieldWrapper.find('.um-field-hint').data('hint',fieldWrapper.find('.um-field-hint').html()).addClass( 'um-field-error' ).attr('id', errorID).html( data['single_user_password-erase-request'] );
						passwordField.addClass( 'um-error' ).attr('aria-errormessage', errorID);
					}
					loader.hide();
				}
			}
		);
	});

	// test case
	$("#um-indeterminate").prop("indeterminate", true);
});

// Avatar uploader handlers.
wp.hooks.addFilter( 'um_uploader_data', 'ultimate-member', function( uploaderData, handler, $button ) {
	if ( 'upload-avatar' !== handler ) {
		return uploaderData;
	}

	let userID = $button.data('user_id');
	uploaderData.url += '&user_id=' + userID;

	return uploaderData;
});

wp.hooks.addAction( 'um_uploader_error', 'ultimate-member', function( $uploader, up, err ) {
	let $button = $uploader.find('.um-uploader-button');
	if ( 'upload-avatar' === $button.data('handler') && 'undefined' !== typeof err ) {
		$button.parents('.um-profile-photo-uploader').removeClass('um-processing');
		alert( err.message );
	}
});

wp.hooks.addAction( 'um_uploader_files_added', 'ultimate-member', function( $uploader, up, files ) {
	let $button = $uploader.find('.um-uploader-button');
	if ( 'upload-avatar' === $button.data('handler') && files.length ) {
		$button.parents('.um-profile-photo-uploader').addClass('um-processing');
	}
});

wp.hooks.addAction( 'um_uploader_upload_complete', 'ultimate-member', function( $uploader, up, files ) {
	let $button = $uploader.find('.um-uploader-button');
	if ( 'upload-avatar' === $button.data('handler') && files.length ) {
		jQuery.each( files, function(i) {
			if ( files[i] ) {
				up.removeFile( files[i].id );
			}
		})
		$button.parents('.um-profile-photo-uploader').removeClass('um-processing');
	}
});

wp.hooks.addFilter( 'um_uploader_file_uploaded', 'ultimate-member', function( preventDefault, $button, up, file, response ) {
	let handler  = $button.data('handler');
	if ( 'upload-avatar' !== handler ) {
		return preventDefault;
	}

	let settings = {
		// These are the defaults.
		classes:  'um-profile-photo-modal',
		duration: 400, // ms
		footer:   '',
		header:   wp.i18n.__( 'Change your profile photo', 'ultimate-member' ),
		size:     'normal', // small, normal, large
		content:  response.data[0].modal_content,
		source: $button
	};

	UM.profile.avatarModal = UM.modal.addModal( settings, null );

	return true;
});

wp.hooks.addFilter( 'um_uploader_file_upload_failed', 'ultimate-member', function( preventDefault, $button, up, file, response ) {
	let handler = $button.data('handler');
	if ( 'upload-avatar' !== handler ) {
		return preventDefault;
	}

	if ( null === response ) {
		alert( wp.i18n.__( 'File was not loaded. Internal server error.', 'ultimate-member' ) );
	} else if ( ! response ) {
		alert( wp.i18n.__( 'File was not loaded. Wrong file upload server response.', 'ultimate-member' ) );
	} else if ( response.info && response.OK === 0 ) {
		alert( response.info );
	}
	return true;
});


// // Pass reference
// const choices = new Choices('[data-trigger]');
// const choices = new Choices('.js-choice');
//
// // Pass jQuery element
// const choices = new Choices($('.js-choice')[0]);
//
// // Passing options (with default options)
// const choices = new Choices(element, {
// 	silent: false,
// 	items: [],
// 	choices: [],
// 	renderChoiceLimit: -1,
// 	maxItemCount: -1,
// 	addItems: true,
// 	addItemFilter: null,
// 	removeItems: true,
// 	removeItemButton: false,
// 	editItems: false,
// 	allowHTML: true,
// 	duplicateItemsAllowed: true,
// 	delimiter: ',',
// 	paste: true,
// 	searchEnabled: true,
// 	searchChoices: true,
// 	searchFloor: 1,
// 	searchResultLimit: 4,
// 	searchFields: ['label', 'value'],
// 	position: 'auto',
// 	resetScrollPosition: true,
// 	shouldSort: true,
// 	shouldSortItems: false,
// 	sorter: () => {...},
// 	placeholder: true,
// 	placeholderValue: null,
// 	searchPlaceholderValue: null,
// 	prependValue: null,
// 	appendValue: null,
// 	renderSelectedChoices: 'auto',
// 	loadingText: 'Loading...',
// 	noResultsText: 'No results found',
// 	noChoicesText: 'No choices to choose from',
// 	itemSelectText: 'Press to select',
// 	uniqueItemText: 'Only unique values can be added',
// 	customAddItemText: 'Only values matching specific conditions can be added',
// 	addItemText: (value) => {
// 		return `Press Enter to add <b>"${value}"</b>`;
// 	},
// 	maxItemText: (maxItemCount) => {
// 		return `Only ${maxItemCount} values can be added`;
// 	},
// 	valueComparer: (value1, value2) => {
// 		return value1 === value2;
// 	},
// 	classNames: {
// 		containerOuter: 'choices',
// 		containerInner: 'choices__inner',
// 		input: 'choices__input',
// 		inputCloned: 'choices__input--cloned',
// 		list: 'choices__list',
// 		listItems: 'choices__list--multiple',
// 		listSingle: 'choices__list--single',
// 		listDropdown: 'choices__list--dropdown',
// 		item: 'choices__item',
// 		itemSelectable: 'choices__item--selectable',
// 		itemDisabled: 'choices__item--disabled',
// 		itemChoice: 'choices__item--choice',
// 		placeholder: 'choices__placeholder',
// 		group: 'choices__group',
// 		groupHeading: 'choices__heading',
// 		button: 'choices__button',
// 		activeState: 'is-active',
// 		focusState: 'is-focused',
// 		openState: 'is-open',
// 		disabledState: 'is-disabled',
// 		highlightedState: 'is-highlighted',
// 		selectedState: 'is-selected',
// 		flippedState: 'is-flipped',
// 		loadingState: 'is-loading',
// 		noResults: 'has-no-results',
// 		noChoices: 'has-no-choices'
// 	},
// 	// Choices uses the great Fuse library for searching. You
// 	// can find more options here: https://fusejs.io/api/options.html
// 	fuseOptions: {
// 		includeScore: true
// 	},
// 	labelId: '',
// 	callbackOnInit: null,
// 	callbackOnCreateTemplates: null
// });




// const inputs = document.querySelector("#um-indeterminate");
// console.log( inputs );
// for (let i = 0; i < inputs.length; i++) {
// 	console.log( inputs[i] );
// 	inputs[i].indeterminate = true;
// }
