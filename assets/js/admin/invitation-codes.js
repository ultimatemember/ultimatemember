jQuery( document ).ready( function( $ ) {
	$( document ).on( 'click', '#um-invitation-generate', function( e ) {
		e.preventDefault();

		var $btn   = $( this );
		var $wrap  = $( '.um-invitation-generate' );
		var $error = $wrap.find( '.um-invitation-generate-error' );

		$btn.prop( 'disabled', true );
		$wrap.find( '.spinner' ).addClass( 'is-active' );
		$error.hide().text( '' );

		$.post( window.ajaxurl, {
			action: 'um_invitation_codes_generate',
			nonce: window.um_admin_scripts.nonce,
			um_ic_count: $( '#um_ic_count' ).val(),
			um_ic_prefix: $( '#um_ic_prefix' ).val(),
			um_ic_length: $( '#um_ic_length' ).val(),
			um_ic_expiry: $( '#um_ic_expiry' ).val()
		} ).done( function( response ) {
			if ( response.success && response.data.redirect ) {
				window.location.href = response.data.redirect;
			} else {
				$error.text( ( response.data && ! response.success ) ? response.data : 'Error' ).show();
				$btn.prop( 'disabled', false );
			}
		} ).fail( function() {
			$error.text( 'Request failed. Please try again.' ).show();
			$btn.prop( 'disabled', false );
		} ).always( function() {
			$wrap.find( '.spinner' ).removeClass( 'is-active' );
		} );
	} );
} );
