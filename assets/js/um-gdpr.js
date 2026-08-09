(function ( $ ) {
	'use strict';

	$( document ).on( 'click', '.um-toggle-gdpr', function ( e ) {
		let $a = jQuery( e.currentTarget );
		let $area = $a.closest( '.um-field-area' );
		let $content = $area.find( '.um-gdpr-content' );

		if ( $content.is( ':visible' ) ) {
			$area.find( '.um-toggle-gdpr' ).text( $a.data( 'toggle-show' ) );
			$content.hide().find( '.um-toggle-gdpr' ).remove();
			if ( $a.length ) {
				$a.get( 0 ).scrollIntoView();
			}
		} else {
			$area.find( '.um-toggle-gdpr' ).text( $a.data( 'toggle-hide' ) );
			$content.show().prepend( $a.clone() );
		}

		e.preventDefault();
	} );

})( jQuery );
