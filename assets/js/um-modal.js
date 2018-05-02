jQuery(document).ready(function() {
	
	jQuery(document).on('click', '.um-popup-overlay', function(){
		remove_Modal();
	});
	
	jQuery(document).on('click', '.um-modal-overlay, a[data-action="um_remove_modal"]', function(){
		um_remove_modal();
	});

	jQuery(document).on('click', 'a[data-modal^="um_"], span[data-modal^="um_"], .um-modal a', function(e){
		e.preventDefault();
		return false;
	});
	
	jQuery(document).on('click', '.um-modal .um-single-file-preview a.cancel', function(e){
		e.preventDefault();
		
		var parent = jQuery(this).parents('.um-modal-body');
		var src = jQuery(this).parents('.um-modal-body').find('.um-single-fileinfo a').attr('href');
		
		parent.find('.um-single-file-preview').hide();
		
		parent.find('.ajax-upload-dragdrop').show();
		
		parent.find('.um-modal-btn.um-finish-upload').addClass('disabled');
		
		um_modal_responsive();
		
		jQuery.ajax({
			url: um_scripts.remove_file,
			type: 'post',
			data: {
				src: src
			}
		});
		
		return false;
	});
	
	jQuery(document).on('click', '.um-modal .um-single-image-preview a.cancel', function(e){
		e.preventDefault();
		
		var parent = jQuery(this).parents('.um-modal-body');
		var src = jQuery(this).parents('.um-modal-body').find('.um-single-image-preview img').attr('src');
		
		jQuery('img.cropper-hidden').cropper('destroy');
		
		parent.find('.um-single-image-preview img').attr('src', '');
		
		parent.find('.um-single-image-preview').hide();
		
		parent.find('.ajax-upload-dragdrop').show();
		
		parent.find('.um-modal-btn.um-finish-upload').addClass('disabled');
		
		um_modal_responsive();
		
		jQuery.ajax({
			url: um_scripts.remove_file,
			type: 'post',
			data: {
				src: src
			}
		});
		
		return false;
	});
	
	jQuery(document).on('click', '.um-finish-upload.file:not(.disabled)', function(){
		
		var key = jQuery(this).attr('data-key');
		var preview = jQuery(this).parents('.um-modal-body').find('.um-single-file-preview').html();
		
		um_remove_modal();
		
		jQuery('.um-single-file-preview[data-key='+key+']').fadeIn().html( preview );
		
		jQuery('.um-single-file-preview[data-key='+key+']').parents('.um-field').find('.um-btn-auto-width').html( jQuery(this).attr('data-change') );
		
		jQuery('.um-single-file-preview[data-key='+key+']').parents('.um-field').find('input[type=hidden]').val( jQuery('.um-single-file-preview[data-key='+key+']').parents('.um-field').find('.um-single-fileinfo a').attr('href') );
	});

	jQuery(document).on('click', '.um-finish-upload.image:not(.disabled)', function(){
		
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
		
		if ( coord ) {
		
			jQuery(this).html( jQuery(this).attr('data-processing') ).addClass('disabled');

			jQuery.ajax({
				url: um_scripts.resize_image,
				type: 'POST',
				data: {
					src : src,
					coord : coord,
					user_id : user_id,
					key: key
				},
				success: function(data){
				
					d = new Date();
					
					if ( key == 'profile_photo') {
						jQuery('.um-profile-photo-img img').attr('src', data + "?"+d.getTime());
					}
					
					if ( key == 'cover_photo') {
						jQuery('.um-cover-e').empty().html('<img src="' + data + "?"+d.getTime() + '" alt="" />');
						if ( jQuery('.um').hasClass('um-editing') ) {
							jQuery('.um-cover-overlay').show();
						}
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

	jQuery( document ).on( "um_responsive_event", um_modal_responsive );
});


/**
 *
 */
function um_remove_modal() {

    jQuery('img.cropper-hidden').cropper('destroy');

    jQuery('body,html,textarea').css("overflow", "auto");

    jQuery(document).unbind('touchmove');

    jQuery('.um-modal div[id^="um_"]').hide().appendTo('body');
    jQuery('.um-modal,.um-modal-overlay').remove();

}


/**
 *
 */
function um_modal_responsive() {

	var modal = jQuery('.um-modal:visible');
	var photo_modal = jQuery('.um-modal-body.photo:visible');
	var half_gap;

	if ( photo_modal.length ) {

		modal.removeClass('uimob340');
		modal.removeClass('uimob500');

		var photo_ = jQuery('.um-modal-photo img');
		var photo_maxw = jQuery(window).width() - 60;
		var photo_maxh = jQuery(window).height() - ( jQuery(window).height() * 0.25 );

		photo_.css({'opacity': 0});
		photo_.css({'max-width': photo_maxw });
		photo_.css({'max-height': photo_maxh });

		jQuery('.um-modal').css({
			'width': photo_.width(),
			'margin-left': '-' + photo_.width() / 2 + 'px'
		});

		photo_.animate({'opacity' : 1}, 1000);

		half_gap = ( jQuery(window).height() - modal.innerHeight() ) / 2 + 'px';
		modal.animate({ 'bottom' : half_gap }, 300);

	} else if ( modal.length ) {

		var element_width = jQuery(window).width();

		modal.removeClass('uimob340');
		modal.removeClass('uimob500');

		if ( element_width <= 340 ) {
			modal.addClass('uimob340');
			half_gap = 0;
		} else if ( element_width <= 500 ) {
			modal.addClass('uimob500');
			half_gap = 0;
		} else if ( element_width <= 800 ) {
			half_gap = ( jQuery(window).height() - modal.innerHeight() ) / 2 + 'px';
		} else if ( element_width <= 960 ) {
			half_gap = ( jQuery(window).height() - modal.innerHeight() ) / 2 + 'px';
		} else if ( element_width > 960 ) {
			half_gap = ( jQuery(window).height() - modal.innerHeight() ) / 2 + 'px';
		}

		jQuery( document ).trigger( 'um_after_modal_responsive_event' );
		modal.animate({ 'bottom' : half_gap }, 300);

	}

}


/**
 *
 * @param id
 * @param size
 * @param isPhoto
 * @param source
 */
function um_new_modal( id, size, isPhoto, source ) {

    var modal = jQuery('body').find('.um-modal-overlay');

    if ( modal.length == 0 ) {

        jQuery( document ).trigger( 'um_before_new_modal_event' );

        jQuery('.tipsy').hide();

        jQuery('body,html,textarea').css("overflow", "hidden");

        jQuery(document).bind("touchmove", function(e){e.preventDefault();});
        jQuery('.um-modal').on('touchmove', function(e){e.stopPropagation();});

        if ( isPhoto ) {
            jQuery('body').append('<div class="um-modal-overlay"></div><div class="um-modal is-photo"></div>');
        } else {
            jQuery('body').append('<div class="um-modal-overlay"></div><div class="um-modal no-photo"></div>');
        }

        jQuery('#' + id).prependTo('.um-modal');

        if ( isPhoto ) {

            jQuery('.um-modal').find('.um-modal-photo').html('<img />');

            var photo_ = jQuery('.um-modal-photo img');
            var photo_maxw = jQuery(window).width() - 60;
            var photo_maxh = jQuery(window).height() - ( jQuery(window).height() * 0.25 );

            photo_.attr("src", source);
            photo_.load(function(){

                jQuery('#' + id).show();
                jQuery('.um-modal').show();

                photo_.css({'opacity': 0});
                photo_.css({'max-width': photo_maxw });
                photo_.css({'max-height': photo_maxh });

                jQuery('.um-modal').css({
                    'width': photo_.width(),
                    'margin-left': '-' + photo_.width() / 2 + 'px'
                });

                photo_.animate({'opacity' : 1}, 1000);

                um_modal_responsive();

            });

        } else {

            jQuery('#' + id).show();
            jQuery('.um-modal').show();
            var visible_modal = jQuery('.um-modal:visible');

            //set modal size
            visible_modal.addClass( size );

			jQuery( document ).trigger( 'um_after_new_modal_nophoto_event', [visible_modal] );

            um_modal_responsive();

        }

    }

}