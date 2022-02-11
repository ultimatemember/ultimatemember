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
