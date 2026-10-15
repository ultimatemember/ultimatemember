jQuery(document).ready(function() {

	jQuery(document.body).off('click.umUserAction', '.um-user-action').on('click.umUserAction', '.um-user-action', function(e) {
		e.preventDefault();
		let $link = jQuery(this);
		let message = $link.data('confirm-onclick');
		if ( message ) {
			// Block dropdown navigation until the asynchronous confirmation is accepted.
			wp.hooks.addFilter( 'um_dropdown_link_result', 'ultimate-member', function( result, attrClass, obj ) {
				if ( ! obj.data('confirm-onclick') ) {
					return result;
				}
				return false;
			});
			jQuery.um_confirm({
				object: this,
				title: wp.i18n.__( 'Confirmation', 'ultimate-member' ),
				message: jQuery('<div>').text(message).html(),
				yes_label: wp.i18n.__( 'Yes', 'ultimate-member' ),
				no_label: wp.i18n.__( 'No', 'ultimate-member' ),
				onYes: function() {
					wp.hooks.removeFilter( 'um_dropdown_link_result', 'ultimate-member' );
					window.location.href = $link.attr('href');
				}
			});
			return false;
		} else {
			wp.hooks.removeFilter( 'um_dropdown_link_result', 'ultimate-member' );
		}
	});

	jQuery( document.body ).on( 'click', '.um-user-posts-load-more', function( e ) {
		e.preventDefault();

		let $btn = jQuery(this);
		let $loopWrapper = $btn.siblings('.um-user-profile-posts-loop');
		let page = $btn.data('page')*1 + 1;

		$btn.prop('disabled',true);
		$btn.siblings('.um-user-posts-loader').umShow();
		wp.ajax.send(
			'um_get_user_posts',
			{
				data: {
					author:  $btn.data('author'),
					last_id: $btn.data('last_id'),
					nonce:   $btn.data('nonce')
				},
				success: function( response ) {
					$btn.prop('disabled', false);
					$btn.siblings('.um-user-posts-loader').umHide();
					$loopWrapper.append( response.content );

					let totalPages = $btn.data('pages')*1;
					if ( page === totalPages ) {
						$btn.remove();
					} else {
						$btn.data( 'page', page );
						$btn.data('last_id', response.last_id );
					}
				},
				error: function( data ) {
					$btn.prop('disabled', false);
					$btn.siblings('.um-user-posts-loader').umHide();
					console.log( data );
				}
			}
		);
	});

	jQuery( document.body ).on( 'click', '.um-user-comments-load-more', function( e ) {
		e.preventDefault();

		let $btn = jQuery(this);
		let $loopWrapper = $btn.siblings('.um-user-profile-comments-loop');
		let page = $btn.data('page')*1 + 1;

		$btn.prop('disabled',true);
		$btn.siblings('.um-user-comments-loader').umShow();
		wp.ajax.send(
			'um_get_user_comments',
			{
				data: {
					author:  $btn.data('author'),
					last_id: $btn.data('last_id'),
					nonce:   $btn.data('nonce')
				},
				success: function( response ) {
					$btn.prop('disabled', false);
					$btn.siblings('.um-user-comments-loader').umHide();
					$loopWrapper.append( response.content );

					let totalPages = $btn.data('pages')*1;
					if ( page === totalPages ) {
						$btn.remove();
					} else {
						$btn.data( 'page', page );
						$btn.data('last_id', response.last_id );
					}
				},
				error: function( data ) {
					$btn.prop('disabled', false);
					$btn.siblings('.um-user-comments-loader').umHide();
					console.log( data );
				}
			}
		);
	});

	jQuery('.um-profile.um-viewing .um-profile-body .um-row').each(function(){
		var this_row = jQuery(this);
		if ( this_row.find('.um-field').length == 0 ) {
			this_row.prev('.um-row-heading').remove();
			this_row.remove();
		}
	});

	if ( jQuery('.um-profile.um-viewing .um-profile-body').length && jQuery('.um-profile.um-viewing .um-profile-body').find('.um-field').length == 0 ) {
		jQuery('.um-profile.um-viewing .um-profile-body').find('.um-row-heading,.um-row').remove();
		jQuery('.um-profile-note').show();
	}

	jQuery( document.body ).on( 'click', '.um-profile-save', function(e){
		e.preventDefault();
		jQuery(this).parents('.um.um-profile.um-editing').find('form').trigger('submit');
		return false;
	});

	// Bio characters limit
	jQuery( document.body ).on( 'change keyup', '#um-meta-bio', function() {
		if ( typeof jQuery(this).val() !== 'undefined' ) {
			let um_bio_limit = jQuery(this).data( 'character-limit' );
			let bio_html     = jQuery(this).data( 'html' );

			let remaining = um_bio_limit - jQuery(this).val().length;
			if ( parseInt( bio_html ) === 1 ) {
				remaining = um_bio_limit - jQuery(this).val().replace(/(<([^>]+)>)/ig,'').length;
			}

			remaining = remaining < 0 ? 0 : remaining;

			jQuery( 'span.um-meta-bio-character span.um-bio-limit' ).text( remaining );
			let color = remaining < 5 ? 'red' : '';
			jQuery('span.um-meta-bio-character').css( 'color', color );
		}
	});
	jQuery( '#um-meta-bio' ).trigger('change');

	// Biography (description) fields syncing.
	jQuery( '.um-profile form' ).each( function () {
		let descKey = jQuery(this).data('description_key');
		if ( jQuery(this).find( 'textarea[name="' + descKey + '"]' ).length ) {
			jQuery( document.body ).on( 'change input', 'textarea[name="' + descKey + '"]', function ( e ) {
				jQuery(this).parents( 'form' ).find( 'textarea[name="' + descKey + '"]' ).each( function() {
					jQuery(this).val( e.currentTarget.value );
					if ( jQuery('#um-meta-bio')[0] !== e.currentTarget && jQuery('#um-meta-bio')[0] === jQuery(this)[0] ) {
						jQuery(this).trigger('change');
					}
				});
			});
		}
	});

	/**
	 * Fix profile nav links for iPhone
	 * @see https://www.html5rocks.com/en/mobile/touchandmouse/
	 */
	jQuery( '.um-profile-nav a' ).on( 'touchend', function(e) {
		jQuery( e.currentTarget).trigger( "click" );
	});
});
