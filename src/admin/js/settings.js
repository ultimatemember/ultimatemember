jQuery( document ).ready( function() {


	/**
	 * Licenses
	 */
	jQuery( document.body ).on( 'click', '.um_license_deactivate', function() {
		jQuery(this).siblings('.um-option-field').val('');
		if ( jQuery(this).siblings('#submit').length ) {
			// clear = true for passing the empty field value to the license form submission
			jQuery(this).siblings('#submit').trigger('click',[ true ]);
		} else {
			jQuery(this).parents('form.um-settings-form').trigger('submit');
		}
	});


	jQuery( document.body ).on( 'click', '.um-settings-form #submit', function( e, clear ) {
		if ( ! clear && '' === jQuery(this).siblings('.um-option-field').val() ) {
			return false;
		}
	});


	/**
	 * Not licenses page
	 */
	if ( jQuery( '#licenses_settings' ).length === 0 ) {
		var changed = false;

		jQuery( 'input, textarea, select' ).on('change', function() {
			changed = true;
		});

		jQuery( '#um-settings-wrap .um-nav-tab-wrapper a, #um-settings-wrap .subsubsub a' ).on( 'click', function() {
			if ( changed ) {
				window.onbeforeunload = function() {
					return wp.i18n.__( 'Are sure, maybe some settings not saved', 'ultimate-member' );
				};
			} else {
				window.onbeforeunload = '';
			}
		});

		jQuery( '.submit input' ).on( 'click', function() {
			window.onbeforeunload = '';
		});
	}


	/**
	 * API key fields ( type = 'api_key' ).
	 *
	 * Markup: a hidden input holds the real value ( the only submitted element ), a single visible
	 * read-only display input shows either the partially-masked value or, once revealed, the full
	 * editable value, and an eye button toggles between the two states.
	 */
	function umApiKeyMask( value, prefix, suffix ) {
		if ( ! value ) {
			return '';
		}
		if ( value.length <= ( prefix + suffix ) ) {
			return new Array( value.length + 1 ).join( '•' );
		}
		var middle = new Array( Math.max( 4, value.length - prefix - suffix ) + 1 ).join( '•' );
		return value.slice( 0, prefix ) + middle + value.slice( value.length - suffix );
	}

	function umApiKeyRefresh( $field ) {
		var value    = $field.find( '.um-api-key-value' ).val();
		var $display = $field.find( '.um-api-key-display' );
		var revealed = $field.hasClass( 'is-revealed' );

		if ( revealed ) {
			$display.val( value ).prop( 'readonly', false );
		} else {
			var prefix = parseInt( $field.data( 'reveal-prefix' ), 10 );
			var suffix = parseInt( $field.data( 'reveal-suffix' ), 10 );
			prefix = isNaN( prefix ) ? 6 : prefix;
			suffix = isNaN( suffix ) ? 4 : suffix;
			$display.val( umApiKeyMask( value, prefix, suffix ) ).prop( 'readonly', true );
		}
	}

	jQuery( '.um-api-key-field' ).each( function() {
		var $field = jQuery( this );
		// Nothing saved yet → start revealed so admins can type a new key immediately.
		if ( '' === $field.find( '.um-api-key-value' ).val() ) {
			$field.addClass( 'is-revealed' );
		}
		umApiKeyRefresh( $field );
	});

	// Keep the hidden value in sync while the display input is editable ( revealed ).
	jQuery( document.body ).on( 'input', '.um-api-key-display', function() {
		var $field = jQuery( this ).closest( '.um-api-key-field' );
		if ( $field.hasClass( 'is-revealed' ) ) {
			$field.find( '.um-api-key-value' ).val( jQuery( this ).val() );
		}
	});

	jQuery( document.body ).on( 'click', '.um-api-key-toggle', function() {
		var $field = jQuery( this ).closest( '.um-api-key-field' );
		var $icon  = jQuery( this ).find( '.dashicons' );

		$field.toggleClass( 'is-revealed' );
		umApiKeyRefresh( $field );

		if ( $field.hasClass( 'is-revealed' ) ) {
			$icon.removeClass( 'dashicons-visibility' ).addClass( 'dashicons-hidden' );
			jQuery( this ).attr({ 'aria-label': wp.i18n.__( 'Hide key', 'ultimate-member' ), title: wp.i18n.__( 'Hide key', 'ultimate-member' ) });
			$field.find( '.um-api-key-display' ).trigger( 'focus' );
		} else {
			$icon.removeClass( 'dashicons-hidden' ).addClass( 'dashicons-visibility' );
			jQuery( this ).attr({ 'aria-label': wp.i18n.__( 'Show key', 'ultimate-member' ), title: wp.i18n.__( 'Show key', 'ultimate-member' ) });
		}
	});
});
