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
		jQuery(this).parents('.um.um-profile.um-editing').find('form').trigger('submit');
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
		let $obj = jQuery(this);
		let user_id = $obj.data('user_id');

		let $dropdownItem = $obj.parents('ul').find('.um-manual-trigger[data-parent=".um-profile-photo"]');
		let altText = $dropdownItem.data('alt_text');
		$dropdownItem.data( 'alt_text', $dropdownItem.text() ).text( altText );

		jQuery('.um-profile-photo-img img').attr( 'src', $obj.data( 'default_src' ) );

		jQuery.ajax({
			url: wp.ajax.settings.url,
			type: 'post',
			data: {
				action:'um_delete_profile_photo',
				user_id: user_id,
				nonce: um_scripts.nonce
			},
			success: function() {
				$obj.removeClass('um-is-visible').hide();
			}
		});

		UM.dropdown.hideAll();
		return false;
	});

	jQuery(document.body).on('click', '.um-reset-cover-photo', function(e){
		let $obj = jQuery(this);
		let user_id = $obj.data('user_id');

		let $dropdownItem = $obj.parents('ul').find('.um-manual-trigger[data-parent=".um-cover"]');
		let altText = $dropdownItem.data('alt_text');
		$dropdownItem.data( 'alt_text', $dropdownItem.text() ).text( altText );

		jQuery('.um-cover-overlay').hide();
		jQuery('.um-cover-e').html('<a href="javascript:void(0);" class="um-cover-add" style="height: 370px;"><span class="um-cover-add-i"><i class="um-icon-plus um-tip-n" title="' + wp.i18n.__( 'Upload a cover photo', 'ultimate-member' ) + '"></i></span></a>');

		um_responsive();

		jQuery.ajax({
			url: wp.ajax.settings.url,
			type: 'post',
			data: {
				action: 'um_delete_cover_photo',
				user_id: user_id,
				nonce: um_scripts.nonce
			},
			success: function() {
				$obj.removeClass('um-is-visible').hide();
			}
		});

		UM.dropdown.hideAll();
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
