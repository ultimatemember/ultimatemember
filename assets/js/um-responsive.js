function UM_domenus(){

	jQuery('.um-dropdown').each(function(){
		
		var menu = jQuery(this);
		var element = jQuery(this).attr('data-element');
		var position = jQuery(this).attr('data-position');
		
		jQuery(element).addClass('um-trigger-menu-on-'+menu.attr('data-trigger'));

		if ( position == 'bc' ){

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
			}

			menu.css({
				'top' : top_p,
				'width': 200,
				'left': left_p + left_gap,
				'text-align' : 'center',
			});
			
			menu.find('.um-dropdown-arr').find('i').addClass('um-icon-arrow-sans-up');
			
			menu.find('.um-dropdown-arr').css({
				'top' : '-18px',
				'left' : ( menu.width() / 2 ) - 12,
			});
		
		}
	});
	
}

function um_responsive(){
	
	// responsive um shortcode
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
		
		// account nav
		if (  jQuery('.um-account-nav').length > 0 && jQuery('.um-account-side').is(':visible') && jQuery('.um-account-tab:visible').length == 0 ) {
			jQuery('.um-account-side li a.current').trigger('click');
		}
		
		// show
		jQuery(this).css('opacity',1);
	
	});
	
	// responsive cover
	jQuery('.um-cover, .um-member-cover').each(function(){
	
		var elem = jQuery(this);
		var ratio = elem.data('ratio');
		var width = elem.width();
		var ratios = ratio.split(':');

		calcHeight = Math.round( width / ratios[0] ) + 'px';
		elem.height( calcHeight );
		elem.find('.um-cover-add').height( calcHeight );
		
	});
	
	// members directory
	jQuery('.um-members').each(function(){
		UM_Member_Grid( jQuery(this) );
	});

	// menus
	UM_domenus();
	
}

/* Run only when window is loaded */
jQuery(window).load(function() {

	um_responsive();
	um_modal_responsive();

});

/* Run only when window is resized */
jQuery(window).resize(function() {

	jQuery('.um-modal .um-single-image-preview.crop:visible img').cropper("destroy");
	
	um_responsive();
	um_modal_responsive();

});