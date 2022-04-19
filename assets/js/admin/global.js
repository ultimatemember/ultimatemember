// Global wp-admin scripts that must be enqueued every where on wp-admin

jQuery(document).ready( function() {


	jQuery(document.body).on('click', '#um_add_review_love', function (e) {
		jQuery(this).parents('#um_start_review_notice').hide();
		jQuery('.um-hidden-notice[data-key="love"]').show();
	});


	jQuery(document.body).on('click', '#um_add_review_good', function (e) {
		jQuery(this).parents('#um_start_review_notice').hide();
		jQuery('.um-hidden-notice[data-key="good"]').show();
	});


	jQuery(document.body).on('click', '#um_add_review_bad', function (e) {
		jQuery(this).parents('#um_start_review_notice').hide();
		jQuery('.um-hidden-notice[data-key="bad"]').show();
	});


	jQuery(document.body).on('click', '.um_review_link', function (e) {
		jQuery(this).parents('.um-admin-notice').find( '.notice-dismiss' ).trigger('click');
	});


	jQuery(document.body).on('click', '.um_secondary_dismiss', function (e) {
		jQuery(this).parents('.um-admin-notice').find( '.notice-dismiss' ).trigger('click');
	});


	jQuery(document.body).on( 'click', '.um-admin-notice.is-dismissible .notice-dismiss', function(e) {
		var notice_key = jQuery(this).parents('.um-admin-notice').data('key');

		wp.ajax.send( 'um_dismiss_notice', {
			data: {
				key: notice_key,
				nonce: um_admin_scripts.nonce
			},
			success: function( data ) {
				return true;
			},
			error: function( data ) {
				// on error make the force notice's dismiss via action link
				var href_index = "";
				if ( window.location.href.indexOf("?") > -1 ) {
					href_index = window.location.href + "&";
				} else {
					href_index = window.location.href + "?";
				}
				window.location.href = href_index + "um_dismiss_notice=" + notice_key + "&um_admin_nonce=" + um_admin_scripts.nonce;

				return false;
			}
		});
	});
});
