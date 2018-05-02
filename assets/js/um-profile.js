jQuery(document).ready(function() {

	jQuery('.um-profile.um-viewing .um-profile-body .um-row').each(function(){
		var this_row = jQuery(this);
		if ( this_row.find('.um-field').length == 0 ) {
			this_row.prev('.um-row-heading').remove();
			this_row.remove();
		}
	});
	
	if ( jQuery('.um-profile.um-viewing .um-profile-body').length && jQuery('.um-profile.um-viewing .um-profile-body').find('.um-field').length == 0 ) {
		jQuery('.um-row-heading,.um-row').remove();
		jQuery('.um-profile-note').show();
	}
	
	jQuery(document).on('click', '.um-profile-save', function(e){
		e.preventDefault();
		jQuery(this).parents('.um').find('form').submit();
		return false;
	});
	
	jQuery(document).on('click', '.um-profile-edit-a', function(e){
		jQuery(this).addClass('active');
	});

    jQuery(document).on('click', '.um-cover a.um-cover-add, .um-photo a', function(e){
		e.preventDefault();
		return false;
	});

	jQuery(document).on('click', '.um-photo-modal', function(e){
		e.preventDefault();
		var photo_src = jQuery(this).attr('data-src');
		um_new_modal( 'um_view_photo', 'fit', true, photo_src );
		return false;
	});

	jQuery(document).on('click', '.um-reset-profile-photo', function(e){
		jQuery('.um-profile-photo-img img').attr('src', jQuery(this).attr('data-default_src') );
		
		user_id = jQuery(this).attr('data-user_id');
		metakey = 'profile_photo';
		
		jQuery.ajax({
			url: um_scripts.delete_profile_photo,
			type: 'post',
			data: {
				metakey: metakey,
				user_id: user_id
			}
		});
	});

	jQuery(document).on('click', '.um-reset-cover-photo', function(e){
		jQuery('.um-cover-overlay').hide();
		
		jQuery('.um-cover-e').html('<a href="#" class="um-cover-add um-manual-trigger" data-parent=".um-cover" data-child=".um-btn-auto-width"><span class="um-cover-add-i"><i class="um-icon-plus um-tip-n" title="Upload a cover photo"></i></span></a>');
		
		jQuery('.um-dropdown').hide();
		
		um_responsive();
		
		user_id = jQuery(this).attr('data-user_id');
		metakey = 'cover_photo';
		
		jQuery.ajax({
			url: um_scripts.delete_cover_photo,
			type: 'post',
			data: {
				metakey: metakey,
				user_id: user_id
			}
		});
	});

	// Bio characters limit
	function um_update_bio_countdown() {
		var meta_bio_obj = jQuery('textarea[id="um-meta-bio"]');
		if ( typeof meta_bio_obj.val() !== 'undefined' ){
			var um_bio_limit = meta_bio_obj.attr( "data-character-limit" );
			var remaining = um_bio_limit - meta_bio_obj.val().length;
			jQuery('span.um-meta-bio-character span.um-bio-limit').text( remaining );
			var meta_color = '';
			if ( remaining  < 5 ) {
				meta_color = 'red';
			}
			jQuery('span.um-meta-bio-character').css( 'color',meta_color );
		}
	}

	um_update_bio_countdown();
	jQuery('textarea[id="um-meta-bio"]').change( um_update_bio_countdown ).keyup( um_update_bio_countdown );

	jQuery('.um-profile-edit a.um_delete-item').click(function(e){
		e.preventDefault();
		var a = confirm('Are you sure that you want to delete this user?');
		if ( ! a ) {
			return false;
		}
	});


	//Profile Page
	jQuery( document ).on( 'click', '.um-dropdown a', function(e){
		return false;
	});


	//Profile Page
	jQuery( document ).on( 'click', '.um-dropdown a.real_url', function(e){
		window.location = jQuery(this).attr('href');
	});


	//Profile Page
	jQuery( document ).on( 'click', '.um-trigger-menu-on-click', function(e){
		jQuery('.um-dropdown').hide();
		menu = jQuery(this).find('.um-dropdown');
		menu.show();
		return false;
	});


	//Profile Page
	jQuery( document ).on( 'click', '.um-dropdown-hide', function(e){
		UM_hide_menus();
	});


	//Profile Page
	jQuery( document ).on('click', 'a.um-manual-trigger', function(){
		var child = jQuery(this).attr('data-child');
		var parent = jQuery(this).attr('data-parent');
		jQuery(this).parents( parent ).find( child ).trigger('click');
	});


	//Profile Page
	jQuery(document).on('click', '.um .um-single-image-preview a.cancel', function(e){
		e.preventDefault();
		var parent = jQuery(this).parents('.um-field');
		var src = jQuery(this).parents('.um-field').find('.um-single-image-preview img').attr('src');
		parent.find('.um-single-image-preview img').attr('src','');
		parent.find('.um-single-image-preview').hide();
		parent.find('.um-btn-auto-width').html('Upload');
		parent.find('input[type=hidden]').val('empty_file');

		jQuery.ajax({
			url: um_scripts.remove_file,
			type: 'post',
			data: {
				src: src
			}
		});

		return false;
	});

	//Profile Page
	jQuery(document).on('click', '.um .um-single-file-preview a.cancel', function(e){
		e.preventDefault();
		var parent = jQuery(this).parents('.um-field');
		var src = jQuery(this).parents('.um-field').find('.um-single-fileinfo a').attr('href');
		parent.find('.um-single-file-preview').hide();
		parent.find('.um-btn-auto-width').html('Upload');
		parent.find('input[type=hidden]').val('empty_file');

		jQuery.ajax({
			url: um_scripts.remove_file,
			type: 'post',
			data: {
				src: src
			}
		});

		return false;
	});


	//Profile Form
	var um_select_options_cache = {};

	/**
	 * Find all select fields with parent select fields
	 */
	jQuery('select[data-um-parent]').each( function(){

		var me = jQuery(this);
		var parent_option = me.data('um-parent');
		var um_ajax_url = me.data('um-ajax-url');
		var um_ajax_source = me.data('um-ajax-source');
		var original_value = me.val();

		me.attr('data-um-init-field', true );

		jQuery(document).on('change','select[name="'+parent_option+'"]',function(){
			var parent  = jQuery(this);
			var form_id = parent.closest('form').find('input[type=hidden][name=form_id]').val();
			var arr_key = parent.val();

			if( parent.val() != '' && typeof um_select_options_cache[ arr_key ] != 'object' ){
				jQuery.ajax({
					url: um_scripts.ajax_select_options,
					type: 'post',
					data: {
						parent_option_name: parent_option,
						parent_option: parent.val(),
						child_callback: um_ajax_source,
						child_name:  me.attr('name'),
						form_id: form_id
					},
					success: function( data ){
						if( data.status == 'success' && parent.val() != '' ){
							um_field_populate_child_options( me, data, arr_key);
						}

						if( typeof data.debug !== 'undefined' ){
							console.log( data );
						}
					},
					error: function( e ){
						console.log( e );
					}
				});

			}

			if( parent.val() != '' && typeof um_select_options_cache[ arr_key ] == 'object' ){
				var data = um_select_options_cache[ arr_key ];
				um_field_populate_child_options( me, data, arr_key );
			}

			if( parent.val() == '' ){
				me.find('option[value!=""]').remove();
				me.val('').trigger('change');
			}

		});

		jQuery('select[name="'+parent_option+'"]').trigger('change');

	});

	/**
	 * Populates child options and cache ajax response
	 * @param  DOM me     child option elem
	 * @param  array data
	 * @param  string key
	 */
	function um_field_populate_child_options( me, data, arr_key, arr_items ) {

		var parent_option = me.data('um-parent');
		var child_name = me.attr('name');
		var parent_dom = jQuery('select[name="'+parent_option+'"]');
		me.find('option[value!=""]').remove();

		if ( ! me.hasClass('um-child-option-disabled') ) {
			me.removeAttr('disabled');
		}

		var arr_items = [];
		jQuery.each( data.items, function( k, v ) {
			arr_items.push({id: k, text: v});
		});

		me.select2('destroy');
		me.select2({
			data: arr_items,
			allowClear: true,
			minimumResultsForSearch: 10
		});

		if ( typeof data.field.default !== 'undefined' && ! me.data('um-original-value') ) {
			me.val( data.field.default ).trigger('change');
		} else if( me.data('um-original-value') != '' ) {
			me.val( me.data('um-original-value') ).trigger('change');
		}

		if ( data.field.editable == 0 ) {
			me.addClass('um-child-option-disabled');
			me.attr('disabled','disabled');
		}

		um_select_options_cache[ arr_key ] = data;

	}

	jQuery( document ).on( "um_responsive_event", um_menu_responsive );
	jQuery( document ).on( "um_before_new_modal_event", UM_hide_menus );
	jQuery( document ).on( "um_after_new_modal_nophoto_event", um_init_image_upload );
	jQuery( document ).on( "um_after_new_modal_nophoto_event", um_init_file_upload );
	jQuery( document ).on( "um_after_modal_responsive_event", um_init_cropper );
});


/**
 *
 * @constructor
 */
function UM_hide_menus() {
	menu = jQuery('.um-dropdown');
	menu.parents('div').find('a').removeClass('active');
	menu.hide();
}


/**
 *
 * @constructor
 */
function um_menu_responsive() {

	jQuery('.um-dropdown').each( function() {

		var menu = jQuery(this);
		var element = jQuery(this).attr('data-element');
		var position = jQuery(this).attr('data-position');

		jQuery( element ).addClass( 'um-trigger-menu-on-' + menu.attr( 'data-trigger' ) );

		if ( jQuery(window).width() <= 1200 && element == 'div.um-profile-edit' ) {
			position = 'lc';
		}

		if ( position == 'lc' ) {

			if ( 200 > jQuery(element).find('img').width() ) {
				left_p = ( ( jQuery(element).width() - jQuery(element).find('img').width() ) / 2 ) + ( ( jQuery(element).find('img').width() - 200 ) / 2 );
			} else {
				left_p = ( ( jQuery(element).width() - jQuery(element).find('img').width() ) / 2 );
			}

			top_ = parseInt( jQuery(element).find('a').css('top') );

			if ( top_ ) {
				top_p = jQuery(element).find('img').height() + 4 + top_;
				left_gap = 4;
			} else {
				top_p = jQuery(element).find('img').height() + 4;
				left_gap = 0;
			}

			if ( top_p == 4 && element == 'div.um-cover' ) {
				top_p = jQuery(element).height() / 2 + ( menu.height() / 2 );
			} else if ( top_p == 4 ) {
				top_p = jQuery(element).height() + 20;
			}

			gap_right = jQuery(element).width() + 17;
			menu.css({
				'top' : 0,
				'width': 200,
				'left': 'auto',
				'right' : gap_right + 'px',
				'text-align' : 'center'
			});

			menu.find('.um-dropdown-arr').find('i').removeClass().addClass('um-icon-arrow-right-b');

			menu.find('.um-dropdown-arr').css({
				'top' : '4px',
				'left' : 'auto',
				'right' : '-17px'
			});

		}

		if ( position == 'bc' ) {

			if ( 200 > jQuery(element).find('img').width() ) {
				left_p = ( ( jQuery(element).width() - jQuery(element).find('img').width() ) / 2 ) + ( ( jQuery(element).find('img').width() - 200 ) / 2 );
			} else {
				left_p = ( ( jQuery(element).width() - jQuery(element).find('img').width() ) / 2 );
			}

			top_ = parseInt( jQuery(element).find('a').css('top') );

			if ( top_ ) {
				top_p = jQuery(element).find('img').height() + 4 + top_;
				left_gap = 4;
			} else {
				top_p = jQuery(element).find('img').height() + 4;
				left_gap = 0;
			}

			if ( top_p == 4 && element == 'div.um-cover' ) {
				top_p = jQuery(element).height() / 2 + ( menu.height() / 2 );
			} else if ( top_p == 4 ) {
				top_p = jQuery(element).height() + 20;
			}

			menu.css({
				'top' : top_p,
				'width': 200,
				'left': left_p + left_gap,
				'right' : 'auto',
				'text-align' : 'center'
			});

			menu.find('.um-dropdown-arr').find('i').removeClass().addClass('um-icon-arrow-up-b');

			menu.find('.um-dropdown-arr').css({
				'top' : '-17px',
				'left' : ( menu.width() / 2 ) - 12,
				'right' : 'auto'
			});

		}
	});

}


/**
 *
 * @param trigger
 */
function um_init_image_upload( event, trigger ) {

	trigger = trigger.find('.um-single-image-upload');

	if ( trigger.data( 'upload_help_text' ) ) {
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

	trigger.uploadFile({
		url: um_scripts.imageupload,
		method: "POST",
		multiple: false,
		formData: {
			key: trigger.data('key'),
			set_id: trigger.data('set_id'),
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
		onSuccess:function(files,data,xhr){

			trigger.selectedFiles = 0;

			try {
				data = jQuery.parseJSON(data);
			} catch (e) {
				console.log( e, data );
				return;
			}

			if (data.error && data.error != '') {

				trigger.parents('.um-modal-body').append('<div class="um-error-block">'+data.error+'</div>');
				trigger.parents('.um-modal-body').find('.upload-statusbar').hide(0);
				um_modal_responsive();

			} else {

				jQuery.each( data, function(key, value) {

					var img_id = trigger.parents('.um-modal-body').find('.um-single-image-preview img');
					var img_id_h = trigger.parents('.um-modal-body').find('.um-single-image-preview');

					img_id.attr("src", value);
					img_id.load(function(){

						trigger.parents('.um-modal-body').find('.um-modal-btn.um-finish-upload.disabled').removeClass('disabled');
						trigger.parents('.um-modal-body').find('.ajax-upload-dragdrop,.upload-statusbar').hide(0);
						img_id_h.show(0);
						um_modal_responsive();

					});

				});

			}

		}
	});

}


/**
 *
 * @param trigger
 */
function um_init_file_upload( event, trigger ) {

	trigger = trigger.find('.um-single-file-upload');

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

	trigger.uploadFile({
		url: um_scripts.fileupload,
		method: "POST",
		multiple: false,
		formData: {
			key: trigger.data('key'),
			set_id: trigger.data('set_id'),
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
		onSuccess:function(files,data,xhr){

			trigger.selectedFiles = 0;

			data = jQuery.parseJSON(data);
			if (data.error && data.error != '') {

				trigger.parents('.um-modal-body').append('<div class="um-error-block">'+data.error+'</div>');
				trigger.parents('.um-modal-body').find('.upload-statusbar').hide(0);

				setTimeout(function(){
					um_modal_responsive();
				},1000);

			} else {

				jQuery.each( data, function(key, value) {

					trigger.parents('.um-modal-body').find('.um-modal-btn.um-finish-upload.disabled').removeClass('disabled');
					trigger.parents('.um-modal-body').find('.ajax-upload-dragdrop,.upload-statusbar').hide(0);
					trigger.parents('.um-modal-body').find('.um-single-file-preview').show(0);

					if (key == 'icon') {
						trigger.parents('.um-modal-body').find('.um-single-fileinfo i').removeClass().addClass(value);
					} else if ( key == 'icon_bg' ) {
						trigger.parents('.um-modal-body').find('.um-single-fileinfo span.icon').css({'background-color' : value } );
					} else if ( key == 'filename' ) {
						trigger.parents('.um-modal-body').find('.um-single-fileinfo span.filename').html(value);
					} else {
						trigger.parents('.um-modal-body').find('.um-single-fileinfo a').attr('href', value);
					}

				});

				setTimeout(function(){
					um_modal_responsive();
				},1000);

			}

		}
	});

}


/**
 *
 */
function um_init_cropper() {

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
				jQuery('.um-single-image-preview img.lazyloaded').addClass('cropper-hidden').removeClass('lazyloaded');
				jQuery('.um-single-image-preview .cropper-container').append('<div class="um-clear"></div>');
			}

		}
	}

}