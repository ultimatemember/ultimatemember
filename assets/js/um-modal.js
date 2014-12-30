function initCrop_UM() {

	// only when a crop image is in view
	var target_img = jQuery('.um-modal:visible .um-single-image-preview img');
	var target_img_parent = jQuery('.um-modal:visible .um-single-image-preview');
	
	var crop_data = target_img.parent().attr('data-crop');
	var min_width = target_img.parent().attr('data-min_width');
	var min_height = target_img.parent().attr('data-min_height');
	var ratio = target_img.parent().attr('data-ratio');
	
	// custom defined ratio maybe
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

			var opts = {
				minWidth: min_width,
				minHeight: Math.round( min_width / ratio ),
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
			}
			
		}
	}
	
}

function um_new_modal( id, size ){
	
	var modal = jQuery('body').find('.um-modal-overlay');
	
	jQuery('.tipsy').hide();

	jQuery('.um-dropdown').hide();
	
	jQuery('body,html').css("overflow", "hidden");
	
	jQuery(document).bind("touchmove", function(e){e.preventDefault();});
	jQuery('.um-modal').on('touchmove', function(e){e.stopPropagation();});
	
	jQuery('body').append('<div class="um-modal-overlay" /><div class="um-modal" />');

	jQuery('#' + id).prependTo('.um-modal');
	jQuery('#' + id).show();
	jQuery('.um-modal').show();

	um_modal_size( size );
	
	initImageUpload_UM( jQuery('.um-modal:visible').find('.um-single-image-upload') );

	initFileUpload_UM( jQuery('.um-modal:visible').find('.um-single-file-upload') );
	
	um_modal_responsive();
	
}

function um_modal_responsive() {
	
	var modal = jQuery('.um-modal:visible');

	if ( modal.length ) {

		var element_width = jQuery(window).width();
		
		modal.removeClass('uimob340');
		modal.removeClass('uimob500');
		
		if ( element_width <= 340 ) {
			
			modal.addClass('uimob340');
			initCrop_UM();
			modal.animate({ 'bottom' : 0 }, 300);
			
		} else if ( element_width <= 500 ) {
		
			modal.addClass('uimob500');
			initCrop_UM();
			modal.animate({ 'bottom' : 0 }, 300);
				
		} else if ( element_width <= 800 ) {
				
			initCrop_UM();
			var half_gap = ( jQuery(window).height() - modal.innerHeight() ) / 2 + 'px';
			modal.animate({ 'bottom' : half_gap }, 300);
				
		} else if ( element_width <= 960 ) {
		
			initCrop_UM();
			var half_gap = ( jQuery(window).height() - modal.innerHeight() ) / 2 + 'px';
			modal.animate({ 'bottom' : half_gap }, 300);
				
		} else if ( element_width > 960 ) {
			
			initCrop_UM();
			var half_gap = ( jQuery(window).height() - modal.innerHeight() ) / 2 + 'px';
			modal.animate({ 'bottom' : half_gap }, 300);
			
		}
		
	}
}

function um_remove_modal(){

	jQuery('.um-modal .um-single-image-preview img').cropper("destroy");
	
	jQuery('body,html').css("overflow", "auto");
	
	jQuery(document).unbind('touchmove');

	jQuery('.um-modal div[id^="um_"]').hide().appendTo('body');
	jQuery('.um-modal,.um-modal-overlay').remove();
	
}

function um_modal_size( aclass ) {
	jQuery('.um-modal:visible').addClass(aclass);
}

function um_modal_add_attr( id, value ) {
	jQuery('.um-modal:visible').data( id, value );
}

/**
	Custom modal scripting starts
**/

jQuery(document).ready(function() {
	
	/**
		remove modal via action
	**/
	jQuery(document).on('click', '.um-modal-overlay, a[data-action="um_remove_modal"]', function(){
		um_remove_modal();
	});
	
	/**
		disable link event
	**/
	jQuery(document).on('click', 'a[data-modal^="um_"], span[data-modal^="um_"], .um-modal a', function(e){
		e.preventDefault();
		return false;
	});
	
	/**
		remove uploaded file
	**/
	jQuery(document).on('click', '.um-modal .um-single-file-preview a.cancel', function(e){
		e.preventDefault();
		
		var parent = jQuery(this).parents('.um-modal-body');

		parent.find('.um-single-file-preview').hide();
		
		parent.find('.ajax-upload-dragdrop').show();
		
		parent.find('.um-modal-btn.um-finish-upload').addClass('disabled');
		
		um_modal_responsive();
		
		return false;
	});
	
	/**
		remove uploaded image
	**/
	jQuery(document).on('click', '.um-modal .um-single-image-preview a.cancel', function(e){
		e.preventDefault();
		
		var parent = jQuery(this).parents('.um-modal-body');

		parent.find('.um-modal .um-single-image-preview img').cropper("destroy");
		
		parent.find('.um-single-image-preview img').attr('src', '');
		
		parent.find('.um-single-image-preview').hide();
		
		parent.find('.ajax-upload-dragdrop').show();
		
		parent.find('.um-modal-btn.um-finish-upload').addClass('disabled');
		
		um_modal_responsive();
		
		return false;
	});
	
	/**
		finish file upload
	**/
	jQuery(document).on('click', '.um-finish-upload.file', function(){
		
		var key = jQuery(this).attr('data-key');
		var preview = jQuery(this).parents('.um-modal-body').find('.um-single-file-preview').html();
		
		um_remove_modal();
		
		jQuery('.um-single-file-preview[data-key='+key+']').fadeIn().html( preview );
		
		jQuery('.um-single-file-preview[data-key='+key+']').parents('.um-field').find('.um-btn-auto-width').html( jQuery(this).attr('data-change') );
		
		jQuery('.um-single-file-preview[data-key='+key+']').parents('.um-field').find('input[type=hidden]').val( jQuery('.um-single-file-preview[data-key='+key+']').parents('.um-field').find('.um-single-fileinfo a').attr('href') );
		
	});
	
	/**
		finish image upload
	**/
	jQuery(document).on('click', '.um-finish-upload.image', function(){
		
		var elem = jQuery(this);
		var key = jQuery(this).attr('data-key');
		var img_c = jQuery(this).parents('.um-modal-body').find('.um-single-image-preview');
		var src = img_c.find('img').attr('src');
		var coord = img_c.attr('data-coord');

		if ( jQuery(this).parents('#um_upload_single').attr('data-user_id')  ) {
			var user_id = jQuery(this).parents('#um_upload_single').attr('data-user_id');
		} else {
			var user_id = 0;
		}
		
		if ( coord ) { // crop image first before processing
		
			jQuery(this).html( jQuery(this).attr('data-processing') ).addClass('disabled');

			jQuery.ajax({
				url: ultimatemember_ajax_url,
				type: 'POST',
				data: {
					action: 'ultimatemember_resize_image',
					src : src,
					coord : coord,
					user_id : user_id,
					key: key,
				},
				success: function(data){
				
					d = new Date();
					
					if ( key == 'profile_photo') {
						jQuery('.um-profile-photo-img img').attr('src', data + "?"+d.getTime());
					}
					
					if ( key == 'cover_photo') {
						jQuery('.um-cover-e').empty().html('<a href="#"><img src="' + data + "?"+d.getTime() + '" alt="" /></a>');
						jQuery('.um-cover-overlay').show();
					}
					
					jQuery('.um-single-image-preview[data-key='+key+']').fadeIn().find('img').attr('src', data + "?"+d.getTime());
					um_remove_modal();
					jQuery('.um-single-image-preview[data-key='+key+']').parents('.um-field').find('.um-btn-auto-width').html( elem.attr('data-change') );
					
					jQuery('.um-single-image-preview[data-key='+key+']').parents('.um-field').find('input[type=hidden]').val( data );

				}
			});
		
		} else {

					d = new Date();
					jQuery('.um-single-image-preview[data-key='+key+']').fadeIn().find('img').attr('src', src + "?"+d.getTime());
					um_remove_modal();
					jQuery('.um-single-image-preview[data-key='+key+']').parents('.um-field').find('.um-btn-auto-width').html( elem.attr('data-change') );
					
					jQuery('.um-single-image-preview[data-key='+key+']').parents('.um-field').find('input[type=hidden]').val( src );

			
		}
		
	});
	
	/**
		fire new modal
	**/
	jQuery(document).on('click', 'a[data-modal^="um_"], span[data-modal^="um_"]', function(e){

		var modal_id = jQuery(this).attr('data-modal');
		
		if ( jQuery(this).data('modal-size')  ) {
			var size = jQuery(this).data('modal-size');
		} else {
			var size = 'normal';
		}
		
		if ( jQuery(this).data('modal-copy') ) {
			jQuery('#' + modal_id).html( jQuery(this).parents('.um-field').find('.um-modal-hidden-content').html() );
			
			if ( jQuery(this).parents('.um-profile-photo').attr('data-user_id') ) {
				jQuery('#' + modal_id).attr('data-user_id', jQuery(this).parents('.um-profile-photo').attr('data-user_id') );
			}
			
			if ( jQuery(this).parents('.um-cover').attr('data-ratio') ) {
				jQuery('#' + modal_id).attr('data-ratio',  jQuery(this).parents('.um-cover').attr('data-ratio')  );
			}
			
			if ( jQuery(this).parents('.um-cover').attr('data-user_id') ) {
				jQuery('#' + modal_id).attr('data-user_id',  jQuery(this).parents('.um-cover').attr('data-user_id')  );
			}
			
			um_new_modal( modal_id, size );
		} else {
			um_new_modal( modal_id, size );
		}
		
	});

});