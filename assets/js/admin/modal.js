/**
 * Custom modal scripting
 * @returns
 */
jQuery( function() {

	jQuery('.um-preview-registration').umModal({
		header: wp.i18n.__( 'Review Registration Details', 'ultimate-member' ),
		content: function( event, options ) {
			let $modal = this;
			let $btn = options.relatedButton;

			wp.ajax.send( 'um_admin_review_registration', {
				data: {
					user_id: $btn.data( 'user_id' ),
					nonce: um_admin_scripts.nonce
				},
				beforeSend: function() {
					$modal.addClass('loading');
				},
				success: function( data ) {
					$modal.removeClass('loading');
					$modal.find('.um-modal-body').html( data );
					UM.modal.responsive( $modal );
				},
				error: function( data ) {
					$modal.removeClass('loading');
					console.error( data );
				}
			});
		},
	});

});
