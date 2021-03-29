/*
 Plugin Name: Ultimate Member
 Description: Frontend scripts
 Version:     2.1.16
 Author:      Ultimate Member
 Author URI:  http://ultimatemember.com/
 */

if ( typeof (window.UM) !== 'object' ) {
	window.UM = {};
}

UM.dropdown = {
	/**
	 * Hide the menu
	 * @param   {object}    menu
	 * @returns {undefined}
	 */
	hide: function (menu) {

		var $menu = jQuery(menu);
		$menu.parents('div').find('a').removeClass('active');
		$menu.hide();

	},
	/**
	 * Hide all menus
	 * @returns {undefined}
	 */
	hideAll: function () {

		var $menu = jQuery('.um-dropdown');
		$menu.parents('div').find('a').removeClass('active');
		$menu.hide();

	},
	/**
	 * Update the menu position
	 * @param   {object}    menu
	 * @returns {undefined}
	 */
	setPosition: function (menu) {

		var $menu = jQuery(menu),
				menu_width = 200;

		var direction = jQuery('html').attr('dir'),
				element = $menu.attr('data-element'),
				position = $menu.attr('data-position'),
				trigger = $menu.attr('data-trigger');

		var $element = element && jQuery(element).length ? jQuery(element) : ($menu.siblings('a').length ? $menu.siblings('a').first() : $menu.parent());
		$element.addClass('um-trigger-menu-on-' + trigger);

		var gap_right = 0,
				left_p = ($element.outerWidth() - menu_width) / 2,
				top_p = $element.outerHeight(),
				coord = $element.offset();

		// profile photo
		if ( $element.is('.um-profile-photo') ) {
			var $imgBox = $element.find('.um-profile-photo-img');
			if ( $element.closest('div.uimob500').length ) {
				top_p = $element.outerHeight() - $imgBox.outerHeight() / 4;
			} else {
				left_p = ($imgBox.outerWidth() - menu_width) / 2;
				top_p = $imgBox.outerHeight() / 4;
			}
		}

		// cover photo
		if ( $element.is('.um-cover') ) {
			var $imgBox = $element.find('.um-cover-e');
			if ( $element.closest('div.uimob500').length ) {
				left_p = ($imgBox.outerWidth() - menu_width) / 2;
				top_p = $imgBox.outerHeight() / 2 + 24;
			} else {
				left_p = ($imgBox.outerWidth() - menu_width) / 2;
				top_p = $imgBox.outerHeight() / 2 + 46;
			}
		}

		// position
		if ( position === 'lc' && direction === 'rtl' ) {
			position = 'rc';
		}
		if( $element.outerWidth() < menu_width ){
			if ( direction === 'rtl' && coord.left < menu_width*0.5 ){
				position = 'rc';
			} else if ( direction !== 'rtl' && (window.innerWidth - coord.left - $element.outerWidth()) < menu_width*0.5 ){
				position = 'lc';
			}
		}

		switch ( position ) {
			case 'lc':

				gap_right = $element.width() + 17;
				$menu.css({
					'top': 0,
					'width': menu_width,
					'left': 'auto',
					'right': gap_right + 'px',
					'text-align': 'center'
				});

				$menu.find('.um-dropdown-arr').css({
					'top': '4px',
					'left': 'auto',
					'right': '-17px'
				}).find('i').removeClass().addClass('um-icon-arrow-right-b');
				break;

			case 'rc':

				gap_right = $element.width() + 25;
				$menu.css({
					'top': 0,
					'width': menu_width,
					'left': gap_right + 'px',
					'right': 'auto',
					'text-align': 'center'
				});

				$menu.find('.um-dropdown-arr').css({
					'top': '4px',
					'left': '-17px',
					'right': 'auto'
				}).find('i').removeClass().addClass('um-icon-arrow-left-b');
				break;

			case 'bc':
			default:

				var top_offset = $menu.data('top-offset');
				if ( typeof top_offset !== 'undefined' ) {
					top_p += top_offset;
				}

				$menu.css({
					'top': top_p + 6,
					'width': menu_width,
					'left': left_p,
					'right': 'auto',
					'text-align': 'center'
				});

				$menu.find('.um-dropdown-arr').css({
					'top': '-17px',
					'left': ($menu.width() / 2) - 12,
					'right': 'auto'
				}).find('i').removeClass().addClass('um-icon-arrow-up-b');
				break;
		}
	},
	/**
	 * Show the menu
	 * @param   {object}    menu
	 * @returns {undefined}
	 */
	show: function (menu) {

		var $menu = jQuery(menu);
		UM.dropdown.hideAll();
		UM.dropdown.setPosition($menu);
		$menu.show();

	}
};


/**
 * Hide all menus
 * @deprecated since 2.1.16, use UM.dropdown.hideAll() instead
 * @returns    {undefined}
 */
function UM_hide_menus() {
	UM.dropdown.hideAll();
}


/**
 * Update menu position
 */
function UM_domenus() {
	jQuery('.um-dropdown').each( function( i, menu ) {
		UM.dropdown.setPosition( menu );
	});
}


function UM_check_password_matched() {
	jQuery(document).on('keyup', 'input[data-key=user_password],input[data-key=confirm_user_password]', function(e) {
		var value = jQuery('input[data-key=user_password]').val();
		var match = jQuery('input[data-key=confirm_user_password]').val();
		var field = jQuery('input[data-key=user_password],input[data-key=confirm_user_password]');

		if(!value && !match) {
			field.removeClass('um-validate-matched').removeClass('um-validate-not-matched');
		} else if(value !== match) {
			field.removeClass('um-validate-matched').addClass('um-validate-not-matched');
		} else {
			field.removeClass('um-validate-not-matched').addClass('um-validate-matched');
		}
	});
}


function um_responsive(){

	jQuery('.um').each(function(){

		element_width = jQuery(this).width();

		if ( element_width <= 340 ) {

			jQuery(this).removeClass('uimob340');
			jQuery(this).removeClass('uimob500');
			jQuery(this).removeClass('uimob800');
			jQuery(this).removeClass('uimob960');

			jQuery(this).addClass('uimob340');

		} else if ( element_width <= 500 ) {

			jQuery(this).removeClass('uimob340');
			jQuery(this).removeClass('uimob500');
			jQuery(this).removeClass('uimob800');
			jQuery(this).removeClass('uimob960');

			jQuery(this).addClass('uimob500');

		} else if ( element_width <= 800 ) {

			jQuery(this).removeClass('uimob340');
			jQuery(this).removeClass('uimob500');
			jQuery(this).removeClass('uimob800');
			jQuery(this).removeClass('uimob960');

			jQuery(this).addClass('uimob800');

		} else if ( element_width <= 960 ) {

			jQuery(this).removeClass('uimob340');
			jQuery(this).removeClass('uimob500');
			jQuery(this).removeClass('uimob800');
			jQuery(this).removeClass('uimob960');

			jQuery(this).addClass('uimob960');

		} else if ( element_width > 960 ) {

			jQuery(this).removeClass('uimob340');
			jQuery(this).removeClass('uimob500');
			jQuery(this).removeClass('uimob800');
			jQuery(this).removeClass('uimob960');

		}

		if (  jQuery('.um-account-nav').length > 0 && jQuery('.um-account-side').is(':visible') && jQuery('.um-account-tab:visible').length == 0 ) {
			jQuery('.um-account-side li a.current').trigger('click');
		}

		jQuery(this).css('opacity',1);

	});

	jQuery('.um-cover, .um-member-cover, .um-cover-e').each(function(){

		var elem = jQuery(this);
		var ratio = elem.data('ratio');
		var width = elem.width();
		var ratios = ratio.split(':');

		calcHeight = Math.round( width / ratios[0] ) + 'px';
		elem.height( calcHeight );
		elem.find('.um-cover-add').height( calcHeight );

	});

	UM_domenus();
}


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

	var user_id = 0;

	if( jQuery('#um_upload_single:visible').data('user_id') ){
        user_id = jQuery('#um_upload_single:visible').data('user_id');
    }

	trigger.uploadFile({
		url: wp.ajax.settings.url,
		method: "POST",
		multiple: false,
		formData: {
			action: 'um_imageupload',
			key: trigger.data('key'),
			set_id: trigger.data('set_id'),
			set_mode: trigger.data('set_mode'),
			_wpnonce: trigger.data('nonce'),
			timestamp: trigger.data('timestamp'),
			user_id: user_id
		 },
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
		returnType: 'json',
		onSubmit:function(files){

			trigger.parents('.um-modal-body').find('.um-error-block').remove();

		},
		onSuccess:function( files, response, xhr ){

			trigger.selectedFiles = 0;

			if ( response.success && response.success == false || typeof response.data.error !== 'undefined' ) {

				trigger.parents('.um-modal-body').append('<div class="um-error-block">'+response.data.error+'</div>');
				trigger.parents('.um-modal-body').find('.upload-statusbar').hide(0);
				um_modal_responsive();

			} else {

				jQuery.each( response.data, function( i, d ) {

					var img_id = trigger.parents('.um-modal-body').find('.um-single-image-preview img');
					var img_id_h = trigger.parents('.um-modal-body').find('.um-single-image-preview');

					var cache_ts = new Date();

					img_id.attr("src", d.url + "?"+cache_ts.getTime() );
					img_id.data("file", d.file );

					img_id.on( 'load', function() {

						trigger.parents('.um-modal-body').find('.um-modal-btn.um-finish-upload.disabled').removeClass('disabled');
						trigger.parents('.um-modal-body').find('.ajax-upload-dragdrop,.upload-statusbar').hide(0);
						img_id_h.show(0);
						um_modal_responsive();

					});

				});

			}

		},
		onError: function ( e ){
			console.log( e );
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

	if( jQuery('#um_upload_single:visible').data('user_id') ){
        user_id = jQuery('#um_upload_single:visible').data('user_id');
    }

	trigger.uploadFile({
		url: wp.ajax.settings.url,
		method: "POST",
		multiple: false,
		formData: {
			action: 'um_fileupload',
			key: trigger.data('key'),
			set_id: trigger.data('set_id'),
			user_id: trigger.data('user_id'),
			set_mode: trigger.data('set_mode'),
			_wpnonce: trigger.data('nonce'),
			timestamp: trigger.data('timestamp')
		},
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
		onSuccess:function( files, response ,xhr ){

			trigger.selectedFiles = 0;

			if ( response.success && response.success == false || typeof response.data.error !== 'undefined' ) {

				trigger.parents('.um-modal-body').append('<div class="um-error-block">'+ response.data.error+'</div>');
				trigger.parents('.um-modal-body').find('.upload-statusbar').hide(0);

				setTimeout(function(){
					um_modal_responsive();
				},1000);

			} else {

				jQuery.each(  response.data , function(key, value) {

					trigger.parents('.um-modal-body').find('.um-modal-btn.um-finish-upload.disabled').removeClass('disabled');
					trigger.parents('.um-modal-body').find('.ajax-upload-dragdrop,.upload-statusbar').hide(0);
					trigger.parents('.um-modal-body').find('.um-single-file-preview').show(0);

					if ( key == 'icon' ) {

						trigger.parents('.um-modal-body').find('.um-single-fileinfo i').removeClass().addClass( value );

					} else if ( key == 'icon_bg' ) {

						trigger.parents('.um-modal-body').find('.um-single-fileinfo span.icon').css({'background-color' : value } );

					} else if ( key == 'filename' ) {

						trigger.parents('.um-modal-body').find('.um-single-fileinfo a').attr('data-file', value );

					}else if( key == 'original_name' ){

						trigger.parents('.um-modal-body').find('.um-single-fileinfo a').attr('data-orignal-name', value );
						trigger.parents('.um-modal-body').find('.um-single-fileinfo span.filename').html( value );

					} else if ( key == 'url' ) {

						trigger.parents('.um-modal-body').find('.um-single-fileinfo a').attr('href', value);

					}

				});

				setTimeout(function(){
					um_modal_responsive();
				},1000);

			}

		},
		onError: function ( e ){
			console.log( e );
		}
	});

}

function initCrop_UM() {

	var target_img = jQuery('.um-modal .um-single-image-preview img').first();
	var target_img_parent = jQuery('.um-modal .um-single-image-preview');

	var crop_data = target_img.parent().attr('data-crop');
	var min_width = target_img.parent().attr('data-min_width');
	var min_height = target_img.parent().attr('data-min_height');
	var ratio = target_img.parent().attr('data-ratio');

	if ( jQuery('.um-modal').find('#um_upload_single').attr('data-ratio') ) {
		var ratio =  jQuery('.um-modal').find('#um_upload_single').attr('data-ratio');
		var ratio_split = ratio.split(':');
		var ratio = ratio_split[0];
	}

	if ( target_img.length ) {

		if ( target_img.attr('src') != '' ) {

			var max_height = jQuery(window).height() - ( jQuery('.um-modal-footer a').height() + 20 ) - 50 - ( jQuery('.um-modal-header:visible').height() );
			target_img.css({'height' : 'auto'});
			target_img_parent.css({'height' : 'auto'});
			if ( jQuery(window).height() <= 400 ) {
				target_img_parent.css({ 'height': max_height +'px', 'max-height' : max_height + 'px' });
				target_img.css({ 'height' : 'auto' });
			} else {
				target_img.css({ 'height': 'auto', 'max-height' : max_height + 'px' });
				target_img_parent.css({ 'height': target_img.height(), 'max-height' : max_height + 'px' });
			}

			if ( crop_data == 'square' ) {

				var opts = {
					minWidth: min_width,
					minHeight: min_height,
					dragCrop: false,
					aspectRatio: 1.0,
					zoomable: false,
					rotatable: false,
					dashed: false,
					done: function(data) {
						target_img.parent().attr('data-coord', Math.round(data.x) + ',' + Math.round(data.y) + ',' + Math.round(data.width) + ',' + Math.round(data.height) );
					}
				};

			} else if ( crop_data == 'cover' ) {
				if( Math.round( min_width / ratio ) > 0 ){
					min_height = Math.round( min_width / ratio )
				}
				var opts = {
					minWidth: min_width,
					minHeight: min_height,
					dragCrop: false,
					aspectRatio: ratio,
					zoomable: false,
					rotatable: false,
					dashed: false,
					done: function(data) {
						target_img.parent().attr('data-coord', Math.round(data.x) + ',' + Math.round(data.y) + ',' + Math.round(data.width) + ',' + Math.round(data.height) );
					}
				};

			} else if ( crop_data == 'user' ) {

				var opts = {
					minWidth: min_width,
					minHeight: min_height,
					dragCrop: true,
					aspectRatio: "auto",
					zoomable: false,
					rotatable: false,
					dashed: false,
					done: function(data) {
						target_img.parent().attr('data-coord', Math.round(data.x) + ',' + Math.round(data.y) + ',' + Math.round(data.width) + ',' + Math.round(data.height) );
					}
				};

			}

			if ( crop_data != 0 ) {
					target_img.cropper( opts );
					jQuery('.um-single-image-preview img.cropper-hidden').cropper('destroy');
					jQuery('.um-single-image-preview img.lazyloaded').addClass('cropper-hidden');
					jQuery('.um-single-image-preview img.lazyloaded').removeClass('lazyloaded');
					jQuery('.um-single-image-preview .cropper-container').append('<div class="um-clear"></div>');
			}

		}
	}

}

function um_new_modal( id, size, isPhoto, source ) {
	var modalOverlay = jQuery('.um-modal-overlay');
	if ( modalOverlay.length !== 0 ) {
		modalOverlay.hide();
		modalOverlay.next('.um-modal').hide();
	}

	jQuery('.tipsy').hide();

	UM.dropdown.hideAll();

	jQuery( 'body,html,textarea' ).css( 'overflow', 'hidden' );

	jQuery( document ).bind( "touchmove", function(e){e.preventDefault();});
	jQuery( '.um-modal' ).on('touchmove', function(e){e.stopPropagation();});

	var $tpl = jQuery( '<div class="um-modal-overlay"></div><div class="um-modal"></div>' );
	var $modal = $tpl.filter('.um-modal');
	$modal.append( jQuery( '#' + id ) );

	jQuery('body').append( $tpl );

	if ( isPhoto ) {
		var photo_ = jQuery('<img src="' + source + '" />'),
			photo_maxw = jQuery(window).width() - 60,
			photo_maxh = jQuery(window).height() - jQuery(window).height() * 0.25;

		photo_.on( 'load', function() {
			$modal.find('.um-modal-photo').html( photo_ );

			$modal.addClass('is-photo').css({
				'width': photo_.width(),
				'margin-left': '-' + photo_.width() / 2 + 'px'
			}).show().children().show();

			photo_.css({
				'opacity': 0,
				'max-width': photo_maxw,
				'max-height': photo_maxh
			}).animate({'opacity' : 1}, 1000);

			um_modal_responsive();
		});
	} else {

		$modal.addClass('no-photo').show().children().show();

		um_modal_size( size );

		initImageUpload_UM( jQuery('.um-modal:visible .um-single-image-upload') );
		initFileUpload_UM( jQuery('.um-modal:visible .um-single-file-upload') );

		um_modal_responsive();

	}

}

function um_modal_responsive() {

	var w = window.innerWidth
		|| document.documentElement.clientWidth
		|| document.body.clientWidth;

	var h = window.innerHeight
		|| document.documentElement.clientHeight
		|| document.body.clientHeight;

	var modal = jQuery('.um-modal:visible').not('.um-modal-hidden');
	var photo_modal = modal.find('.um-modal-body.photo:visible');

	if ( photo_modal.length ) {

		modal.removeClass('uimob340');
		modal.removeClass('uimob500');

		var photo_ = jQuery('.um-modal-photo img');
		var photo_maxw = w - 60;
		var photo_maxh = h - ( h * 0.25 );

		photo_.css({'opacity': 0});
		photo_.css({'max-width': photo_maxw });
		photo_.css({'max-height': photo_maxh });

		modal.css({
			'width': photo_.width(),
			'margin-left': '-' + photo_.width() / 2 + 'px'
		});

		photo_.animate({'opacity' : 1}, 1000);

		var half_gap = ( h - modal.innerHeight() ) / 2 + 'px';
		modal.animate({ 'bottom' : half_gap }, 300);

	} else if ( modal.length ) {

		modal.removeClass('uimob340');
		modal.removeClass('uimob500');

		if ( w <= 340 ) {

			modal.addClass('uimob340');
			initCrop_UM();
			modal.animate({ 'bottom' : 0 }, 300);

		} else if ( w <= 500 ) {

			modal.addClass('uimob500');
			initCrop_UM();
			modal.animate({ 'bottom' : 0 }, 300);

		} else if ( w <= 800 ) {

			initCrop_UM();
			var half_gap = ( h - modal.innerHeight() ) / 2 + 'px';
			modal.animate({ 'bottom' : half_gap }, 300);

		} else if ( w <= 960 ) {

			initCrop_UM();
			var half_gap = ( h - modal.innerHeight() ) / 2 + 'px';
			modal.animate({ 'bottom' : half_gap }, 300);

		} else if ( w > 960 ) {

			initCrop_UM();
			var half_gap = ( h - modal.innerHeight() ) / 2 + 'px';
			modal.animate({ 'bottom' : half_gap }, 300);

		}

	}

}

function um_remove_modal() {
	jQuery('img.cropper-hidden').cropper('destroy');

	jQuery('body,html,textarea').css("overflow", "auto");

	jQuery(document).unbind('touchmove');

	jQuery('body > .um-modal div[id^="um_"]').hide().appendTo('body');
	jQuery('body > .um-modal, body > .um-modal-overlay').remove();

}

function um_modal_size( aclass ) {
	jQuery('.um-modal:visible').not('.um-modal-hidden').addClass( aclass );
}

/**
 * Maybe deprecated
 *
 * @deprecated since 2.1.16
 *
 * @param id
 * @param value
 */
function um_modal_add_attr( id, value ) {
	jQuery('.um-modal:visible').not('.um-modal-hidden').data( id, value );
}

function prepare_Modal() {
	if ( jQuery('.um-popup-overlay').length == 0 ) {
		jQuery('body').append('<div class="um-popup-overlay"></div>');
		jQuery('body').append('<div class="um-popup"></div>');
		jQuery('.um-popup').addClass('loading');
		jQuery("body,html").css({ overflow: 'hidden' });
	}
}

function remove_Modal() {
	if ( jQuery('.um-popup-overlay').length ) {
		wp.hooks.doAction( 'um_before_modal_removed', jQuery('.um-popup') );

		jQuery('.tipsy').remove();
		jQuery('.um-popup').empty().remove();
		jQuery('.um-popup-overlay').empty().remove();
		jQuery("body,html").css({ overflow: 'auto' });
	}
}

function show_Modal( contents ) {
	if ( jQuery('.um-popup-overlay').length ) {
		jQuery('.um-popup').removeClass('loading').html( contents );
		jQuery('.um-tip-n').tipsy({gravity: 'n', opacity: 1, offset: 3 });
		jQuery('.um-tip-w').tipsy({gravity: 'w', opacity: 1, offset: 3 });
		jQuery('.um-tip-e').tipsy({gravity: 'e', opacity: 1, offset: 3 });
		jQuery('.um-tip-s').tipsy({gravity: 's', opacity: 1, offset: 3 });
	}
}

function responsive_Modal() {
	if ( jQuery('.um-popup-overlay').length ) {

		ag_height = jQuery(window).height() - jQuery('.um-popup .um-popup-header').outerHeight() - jQuery('.um-popup .um-popup-footer').outerHeight() - 80;
		if ( ag_height > 350 ) {
			ag_height = 350;
		}

		if ( jQuery('.um-popup-autogrow:visible').length ) {

			jQuery('.um-popup-autogrow:visible').css({'height': ag_height + 'px'});

		} else if ( jQuery('.um-popup-autogrow2:visible').length ) {

			jQuery('.um-popup-autogrow2:visible').css({'max-height': ag_height + 'px'});

		}
	}
}

function um_reset_field( dOm ){
	//console.log(dOm);
	jQuery(dOm)
	 .find('div.um-field-area')
	 .find('input,textarea,select')
	 .not(':button, :submit, :reset, :hidden')
	 .val('')
	 .prop('checked', false)
	 .prop('selected', false);
}

jQuery(function(){

	// Submit search form on keypress 'Enter'
	jQuery(".um-search form *").on( 'keypress', function(e){
			 if (e.which == 13) {
			    jQuery('.um-search form').trigger('submit');
			    return false;
			  }
	});

	if( jQuery('input[data-key=user_password],input[data-key=confirm_user_password]').length == 2 ) {
		UM_check_password_matched();
	}

});


function um_selected( selected, current ){

	if( selected == current ){
		return "selected='selected'";
	}
}