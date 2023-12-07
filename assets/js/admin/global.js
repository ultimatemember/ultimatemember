/**
 * Global wp-admin scripts that must be enqueued everywhere on wp-admin.
 */

jQuery(document).ready( function() {
	// wp.org reviews admin notice: Removed for now to avoid the bad reviews
	// jQuery(document.body).on('click', '#um_add_review_love', function () {
	// 	jQuery(this).parents('#um_start_review_notice').hide();
	// 	jQuery('.um-hidden-notice[data-key="love"]').show();
	// });
	//
	// jQuery(document.body).on('click', '#um_add_review_good', function () {
	// 	jQuery(this).parents('#um_start_review_notice').hide();
	// 	jQuery('.um-hidden-notice[data-key="good"]').show();
	// });
	//
	// jQuery(document.body).on('click', '#um_add_review_bad', function () {
	// 	jQuery(this).parents('#um_start_review_notice').hide();
	// 	jQuery('.um-hidden-notice[data-key="bad"]').show();
	// });
	//
	// jQuery(document.body).on('click', '.um_review_link', function () {
	// 	jQuery(this).parents('.um-admin-notice').find( '.notice-dismiss' ).trigger('click');
	// });

	jQuery(document.body).on('click', '.um_secondary_dismiss', function () {
		jQuery(this).parents('.um-admin-notice').find( '.notice-dismiss' ).trigger('click');
	});

	jQuery(document.body).on( 'click', '.um-admin-notice.is-dismissible .notice-dismiss', function() {
		let notice_key = jQuery(this).parents('.um-admin-notice').data('key');

		wp.ajax.send( 'um_dismiss_notice', {
			data: {
				key: notice_key,
				nonce: um_admin_scripts.nonce
			},
			success: function() {
				return true;
			},
			error: function() {
				// On error make the force notice's dismiss via action link.
				let href_index;
				if ( window.location.href.indexOf('?') > -1 ) {
					href_index = window.location.href + '&';
				} else {
					href_index = window.location.href + '?';
				}
				window.location.href = href_index + 'um_dismiss_notice=' + notice_key + '&um_admin_nonce=' + um_admin_scripts.nonce;

				return false;
			}
		});
	});
});
