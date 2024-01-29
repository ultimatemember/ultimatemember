jQuery(document).ready(function($) {

	$(document.body).on('click', '.um-reset-profile-photo', function(e) {
		e.preventDefault;
		let userID = $(this).data('user_id');
		let nonce = $(this).data('nonce');

		wp.ajax.send(
			'um_delete_profile_photo',
			{
				data: {
					user_id: userID,
					nonce: nonce
				},
				success: function (data) {

				},
				error: function (data) {
					console.log(data);
				}
			}
		);
	});
});
