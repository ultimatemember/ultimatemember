wp.hooks.addAction( 'um_after_account_tab_changed', 'um_jobboardwp', function( tab_ ) {
	if ( 'jobboardwp' === tab_ ) {
		jb_responsive();
	}
});

wp.hooks.addAction( 'um_account_active_tab_inited', 'um_jobboardwp', function( tab_ ) {
	if ( 'jobboardwp' === tab_ ) {
		jb_responsive();
	}
});

// show header if there is map
wp.hooks.addFilter( 'um_bookmarks_remove_button_args', 'um_jobboardwp', function( data ) {
	data.job_list = true;
	return data;
}, 10 );


wp.hooks.addFilter( 'um_bookmarks_add_button_args', 'um_jobboardwp', function( data ) {
	data += '&job_list=1';
	return data;
}, 10 );
wp.hooks.addFilter("um_bookmarks_remove_button_args","um_jobboardwp",function(o){return o.job_list=!0,o},10),wp.hooks.addFilter("um_bookmarks_add_button_args","um_jobboardwp",function(o){return o+="&job_list=1"},10);
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
jQuery(document).ready(function(){var e=jQuery(".um-online");e.length&&(e.each(function(){var e=jQuery(this).attr("data-max");0<e&&jQuery(this).find(".um-online-user").length>e&&(e=e-1,jQuery(this).find(".um-online-user:gt("+e+")").hide(),e=jQuery(this).find(".um-online-user").length-jQuery(this).find(".um-online-user:visible").length,jQuery('<div class="um-online-user show-all">+'+e+"</div>").insertAfter(jQuery(this).find(".um-online-user:visible:last")))}),jQuery(document.body).on("click",".um-online-user.show-all",function(){return jQuery(this).parents(".um-online").find(".um-online-user").show(),jQuery(this).hide(),!1}))});
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