jQuery( document ).ready( function() {
	var online_blocks = jQuery( '.um-online' );

	if ( online_blocks.length ) {
		online_blocks.each( function() {
			var max = jQuery(this).attr('data-max');
			if ( max > 0 && jQuery(this).find('.um-online-user').length > max ) {
				var n = max - 1;
				jQuery(this).find('.um-online-user:gt('+n+')').hide();
				var more = jQuery(this).find('.um-online-user').length - jQuery(this).find('.um-online-user:visible').length;
				jQuery('<div class="um-online-user show-all">+'+ more + '</div>').insertAfter( jQuery(this).find('.um-online-user:visible:last') );
			}
		});

		jQuery( document.body ).on( 'click', '.um-online-user.show-all', function() {
			jQuery(this).parents('.um-online').find('.um-online-user').show();
			jQuery(this).hide();
			return false;
		});
	}
});



(function( $ ) {
	'use strict';

	$(document).on('click', "a.um-toggle-terms" ,function() {
		 
		var me = jQuery(this);

		$( ".um-terms-conditions-content" ).toggle( "fast", function() {
			if( $( ".um-terms-conditions-content" ).is(':visible') ){
				me.text( me.data('toggle-hide') );
		   	}

			if( $( ".um-terms-conditions-content" ).is(':hidden') ){
				me.text( me.data('toggle-show') );
		  	}
		    
		});

	});


	$(document).on('click', "a.um-hide-terms" ,function() {

		var me = jQuery(this).parents('.um-field-area' ).find('a.um-toggle-terms');

		$( ".um-terms-conditions-content" ).toggle( "fast", function() {
			if( $( ".um-terms-conditions-content" ).is(':visible') ) {
				me.text( me.data('toggle-hide') );
		   	}

			if( $( ".um-terms-conditions-content" ).is(':hidden') ) {
				me.text( me.data('toggle-show') );
		  	}

		});

	});


})( jQuery );

!function(e){"use strict";e(document).on("click","a.um-toggle-terms",function(){var t=jQuery(this);e(".um-terms-conditions-content").toggle("fast",function(){e(".um-terms-conditions-content").is(":visible")&&t.text(t.data("toggle-hide")),e(".um-terms-conditions-content").is(":hidden")&&t.text(t.data("toggle-show"))})}),e(document).on("click","a.um-hide-terms",function(){var t=jQuery(this).parents(".um-field-area").find("a.um-toggle-terms");e(".um-terms-conditions-content").toggle("fast",function(){e(".um-terms-conditions-content").is(":visible")&&t.text(t.data("toggle-hide")),e(".um-terms-conditions-content").is(":hidden")&&t.text(t.data("toggle-show"))})})}(jQuery);