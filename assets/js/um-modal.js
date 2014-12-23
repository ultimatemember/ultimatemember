function um_new_modal( id, size ){
	
	var modal = jQuery('body').find('.um-modal-overlay');
	
	jQuery('.tipsy').hide();

	jQuery('body,html').css("overflow", "hidden");
	jQuery('body').append('<div class="um-modal-overlay" /><div class="um-modal" />');

	jQuery('#' + id).prependTo('.um-modal');
	jQuery('#' + id).show();
	jQuery('.um-modal').show();

	um_modal_size( size );
	
	initImageUpload_UM( jQuery('.um-modal:visible').find('.um-single-image-upload') );
	
	um_modal_responsive();
	
}

function um_modal_responsive() {
	
	var modal = jQuery('.um-modal:visible');
	
	if ( modal.length ) {
		
		var window_height_diff = jQuery(window).height() - modal.innerHeight();
		
		var half_gap = window_height_diff / 2 + 'px';
		
		var element_width = jQuery(window).width();
		
		modal.removeClass('uimob340');
		modal.removeClass('uimob500');
		
		if ( element_width <= 340 ) {
			
			modal.addClass('uimob340');
			modal.animate({ 'bottom' : 0 }, 300);
			
		} else if ( element_width <= 500 ) {
		
			modal.addClass('uimob500');
			modal.animate({ 'bottom' : 0 }, 300);
				
		} else if ( element_width <= 800 ) {
				
			modal.animate({ 'bottom' : half_gap }, 300);
				
		} else if ( element_width <= 960 ) {
		
			modal.animate({ 'bottom' : half_gap }, 300);
				
		} else if ( element_width > 960 ) {
			
			modal.animate({ 'bottom' : half_gap }, 300);
			
		}
	
	}
}

function um_remove_modal(){
	jQuery('.um-single-image-preview.crop img').imgAreaSelect({ hide: true });
	jQuery('body,html').css("overflow", "auto");
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
		remove uploaded image
	**/
	jQuery(document).on('click', '.um-modal .um-single-image-preview a.cancel', function(e){
		e.preventDefault();
		
		var parent = jQuery(this).parents('.um-modal-body');

		parent.find('.um-single-image-preview').hide();
		
		parent.find('.ajax-upload-dragdrop').show();
		
		parent.find('.um-modal-btn.um-finish-upload').addClass('disabled');
		
		um_modal_responsive();
		
		return false;
	});
	
	/**
		finish upload
	**/
	jQuery(document).on('click', '.um-finish-upload', function(){
		
		var key = jQuery(this).attr('data-key');
		
		var src = jQuery(this).parents('.um-modal-body').find('.um-single-image-preview img').attr('src');
		
		jQuery('.um-single-image-preview[data-key='+key+']').fadeIn().find('img').attr('src', src);
		
		um_remove_modal();
		
		jQuery('.um-single-image-preview[data-key='+key+']').parents('.um-field').find('.um-btn-auto-width').html('Change/Modify Photo');
		
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
			um_new_modal( modal_id, size );
		} else {
			um_new_modal( modal_id, size );
		}
		
	});

});