function um_new_modal( id, ajax, size ){
	
	var modal = jQuery('body').find('.um-modal-overlay');
	
	jQuery('.tipsy').hide();
	
	um_remove_modal();
		
	jQuery('body,html').css("overflow", "hidden");
	jQuery('body').append('<div class="um-modal-overlay" /><div class="um-modal" />');
	jQuery('#' + id).prependTo('.um-modal');
	jQuery('#' + id).show();
	jQuery('.um-modal').show();
	
	um_modal_size( size );
	if ( ajax == true ) { um_modal_preload(); }
	um_modal_responsive();
	
}

function um_modal_ajaxcall( act_id, arg1, arg2, arg3 ) {

	if ( arg2 ) {
		jQuery('.um-modal-header:visible').html( arg2 );
	}
	
	jQuery.ajax({
		url: ultimatemember_ajax_url,
		type: 'POST',
		data: {action: 'ultimatemember_frontend_modal', act_id: act_id, arg1 : arg1, arg2 : arg2, arg3: arg3 },
		complete: function(){
			um_modal_loaded();
			um_modal_responsive();
		},
		success: function(data){

			jQuery('.um-modal').find('.um-modal-body').html( data );

		}
	});
	return false;
}

function um_modal_responsive() {
	var required_margin = jQuery('.um-modal:visible').innerHeight() / 2 + 'px';
	jQuery('.um-modal:visible').css({'margin-top': '-' + required_margin });
}

function um_remove_modal(){
	jQuery('body,html').css("overflow", "inherit");
	jQuery('.um-modal div[id^="um_"]').hide().appendTo('body');
	jQuery('.um-modal,.um-modal-overlay').remove();
}

function um_modal_preload() {
	jQuery('.um-modal:visible').addClass('loading');
	jQuery('.um-modal-body:visible').empty();
}

function um_modal_loaded() {
	jQuery('.um-modal:visible').removeClass('loading');
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
		fire new modal
	**/
	jQuery(document).on('click', 'a[data-modal^="um_"], span[data-modal^="um_"]', function(){
		
		var modal_id = jQuery(this).attr('data-modal');
		
		if ( jQuery(this).data('modal-size')  ) {
			var size = jQuery(this).data('modal-size');
		} else {
			var size = 'normal';
		}
		
		if ( jQuery(this).data('dynamic-content') ) {
			um_new_modal( modal_id, true, size );
			um_modal_ajaxcall( jQuery(this).data('dynamic-content'), jQuery(this).data('arg1'), jQuery(this).data('arg2'), jQuery(this).data('arg3') );
		} else {
			um_new_modal( modal_id, false, size );
		}

	});

});