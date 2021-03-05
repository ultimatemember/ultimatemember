jQuery(document).ready(function() {

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
		jQuery(this).parents('.um').find('form').trigger('submit');
		return false;
	});

	jQuery( document.body ).on( 'click', '.um-profile-edit-a', function(e){
		jQuery(this).addClass('active');
	});

	jQuery( document.body ).on( 'click', '.um-cover a.um-cover-add, .um-photo a', function(e){
		e.preventDefault();
	});

	jQuery( document.body ).on('click', '.um-photo-modal', function(e){
		e.preventDefault();
		var photo_src = jQuery(this).attr('data-src');
		um_new_modal('um_view_photo', 'fit', true, photo_src );
		return false;
	});

	jQuery(document.body).on('click', '.um-reset-profile-photo', function(e) {

		jQuery('.um-profile-photo-img img').attr( 'src', jQuery(this).attr( 'data-default_src' ) );

		user_id = jQuery(this).attr('data-user_id');
		metakey = 'profile_photo';

		UM.dropdown.hideAll();

		jQuery.ajax({
			url: wp.ajax.settings.url,
			type: 'post',
			data: {
				action:'um_delete_profile_photo',
				metakey: metakey,
				user_id: user_id,
				nonce: um_scripts.nonce
			}
		});

		jQuery(this).parents('li').hide();
		return false;
	});

	jQuery(document.body).on('click', '.um-reset-cover-photo', function(e){
		var obj = jQuery(this);

		jQuery('.um-cover-overlay').hide();

		jQuery('.um-cover-e').html('<a href="javascript:void(0);" class="um-cover-add" style="height: 370px;"><span class="um-cover-add-i"><i class="um-icon-plus um-tip-n" original-title="Upload a cover photo"></i></span></a>');

		um_responsive();

		user_id = jQuery(this).attr('data-user_id');
		metakey = 'cover_photo';

		jQuery.ajax({
			url: wp.ajax.settings.url,
			type: 'post',
			data: {
				action: 'um_delete_cover_photo',
				metakey: metakey,
				user_id: user_id,
				nonce: um_scripts.nonce
			},
			success: function( response ) {
				obj.hide();
			}
		});

		UM.dropdown.hideAll();
		return false;
	});

	/*function um_update_bio_countdown() {
		//
		jQuery(this)
		if ( typeof jQuery('textarea[id="um-meta-bio"]').val() !== 'undefined' ){
			var um_bio_limit = jQuery('textarea[id="um-meta-bio"]').attr( "data-character-limit" );
			var remaining = um_bio_limit - jQuery('textarea[id="um-meta-bio"]').val().length;
			jQuery('span.um-meta-bio-character span.um-bio-limit').text( remaining );
			if ( remaining  < 5 ) {
				jQuery('span.um-meta-bio-character').css('color','red');
			} else {
				jQuery('span.um-meta-bio-character').css('color','');
			}
		}
	}*/

	//um_update_bio_countdown();
	//jQuery( 'textarea[id="um-meta-bio"]' ).on('change', um_update_bio_countdown ).keyup( um_update_bio_countdown ).trigger('change');

	// Bio characters limit
	jQuery( document.body ).on( 'change, keyup', 'textarea[id="um-meta-bio"]', function() {
		if ( typeof jQuery(this).val() !== 'undefined' ) {
			var um_bio_limit = jQuery(this).attr( "data-character-limit" );
			var remaining = um_bio_limit - jQuery(this).val().length;
			jQuery( 'span.um-meta-bio-character span.um-bio-limit' ).text( remaining );
			if ( remaining  < 5 ) {
				jQuery('span.um-meta-bio-character').css('color','red');
			} else {
				jQuery('span.um-meta-bio-character').css('color','');
			}
		}
	});
	jQuery( 'textarea[id="um-meta-bio"]' ).trigger('change');


	jQuery( '.um-profile-edit a.um_delete-item' ).on( 'click', function(e) {
		e.preventDefault();

		if ( ! confirm( wp.i18n.__( 'Are you sure that you want to delete this user?', 'ultimate-member' ) ) ) {
			return false;
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