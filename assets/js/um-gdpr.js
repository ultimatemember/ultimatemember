(function( $ ) {
	'use strict';

	$(document).on('click', "a.um-toggle-gdpr" ,function() {
		 
		var me = jQuery(this);

		$( ".um-gdpr-content" ).toggle( "fast", function() {
			if( $( ".um-gdpr-content" ).is(':visible') ){
				me.text( me.data('toggle-hide') );
		   	}

			if( $( ".um-gdpr-content" ).is(':hidden') ){
				me.text( me.data('toggle-show') );
		  	}
		    
		});

	});


})( jQuery );
