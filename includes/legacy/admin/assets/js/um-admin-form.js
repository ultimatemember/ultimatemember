function um_form_select_tab( tab, set_val ) {
	var mode_block = jQuery('input#form__um_mode');
	tab.parents('.um-admin-boxed-links').find('a').removeClass('um-admin-activebg');
	tab.addClass('um-admin-activebg');

	jQuery('.um-admin div#side-sortables').show();
	jQuery('div[id^="um-admin-form"]').hide();
	jQuery('#submitdiv').show();
	jQuery('div#um-admin-form-mode,div#um-admin-form-title,div#um-admin-form-builder,div#um-admin-form-shortcode').show();
	jQuery('div[id^="um-admin-form-' + tab.data('role') + '"]').show();

	if ( set_val ) {
		mode_block.val( tab.data('role') );
	}

	jQuery('.empty-container').css({'border' : 'none'});
	jQuery('.um-admin-builder').removeClass().addClass( 'um-admin-builder ' + mode_block.val() );
}

jQuery(document).ready(function() {
	/* Default form tab */
	if ( jQuery('.um-admin-boxed-links').length > 0 ) {
		var tab = jQuery('.um-admin-boxed-links a[data-role="'+jQuery('input#form__um_mode').val()+'"]');
		um_form_select_tab( tab, false );
	}


	/* Creating new form button */
	jQuery('.um-admin-boxed-links:not(.is-core-form) a').on( 'click', function() {
		um_form_select_tab( jQuery(this), true );
	});
});