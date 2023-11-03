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

/**
 * This function updates the builder area with fields
 *
 * @returns {boolean}
 */
function um_admin_update_builder() {
	var form_id = jQuery('.um-admin-builder').data('form_id');


	jQuery.ajax({
		url: wp.ajax.settings.url,
		type: 'POST',
		data: {
			action:'um_update_builder',
			form_id: form_id,
			nonce: um_admin_scripts.nonce
		},
		success: function( data ) {
			jQuery('.um-admin-drag-ajax').html( data );
			UM.common.tipsy.hide();

			/* trigger columns at start */
			allow_update_via_col_click = false;
			jQuery('.um-admin-drag-ctrls.columns a.active').each( function() {
				jQuery(this).trigger('click');
			}).promise().done( function(){
				allow_update_via_col_click = true;
			});

			UM_Rows_Refresh();
		},
		error: function( data ) {

		}
	});

	return false;
}

jQuery( document ).ready( function() {
	/* Default form tab */
	if ( jQuery('.um-admin-boxed-links').length > 0 ) {
		var tab = jQuery('.um-admin-boxed-links a[data-role="'+jQuery('input#form__um_mode').val()+'"]');
		um_form_select_tab( tab, false );
	}


	/* Creating new form button */
	jQuery('.um-admin-boxed-links:not(.is-core-form) a').on( 'click', function() {
		um_form_select_tab( jQuery(this), true );
	});

	jQuery('#wpfooter').hide();

	/**
	 * Conditional fields in Add/Edit form field modal.
	 */
	jQuery( document.body ).on('change', '.um-adm-conditional', function(){

		var value;
		if ( jQuery(this).attr("type") == 'checkbox' ) {
			value = jQuery(this).is(':checked') ? 1 : 0;
		} else {
			value = jQuery(this).val();
		}

		if ( jQuery(this).data('cond1') ) {
			if ( value == jQuery(this).data('cond1') ) {
				jQuery('.' + jQuery(this).data('cond1-show') ).show();
				jQuery('.' + jQuery(this).data('cond1-hide') ).hide();

				if ( jQuery(this).data('cond1-show') == '_roles' ) {
					return false;
				}

			} else {
				jQuery('.' + jQuery(this).data('cond1-show') ).hide();
				jQuery('.' + jQuery(this).data('cond1-hide') ).show();
			}
		}

		if ( jQuery(this).data('cond2') ) {
			if ( value == jQuery(this).data('cond2') ) {
				jQuery('.' + jQuery(this).data('cond2-show') ).show();
				jQuery('.' + jQuery(this).data('cond2-hide') ).hide();

				if ( jQuery(this).data('cond2-show') == '_roles' ) {
					return false;
				}

			} else {
				jQuery('.' + jQuery(this).data('cond2-show') ).hide();
				jQuery('.' + jQuery(this).data('cond2-hide') ).show();
			}
		}

		if ( jQuery(this).data('cond3') ) {
			if ( value == jQuery(this).data('cond3') ) {
				jQuery('.' + jQuery(this).data('cond3-show') ).show();
				jQuery('.' + jQuery(this).data('cond3-hide') ).hide();
			} else {
				jQuery('.' + jQuery(this).data('cond3-show') ).hide();
				jQuery('.' + jQuery(this).data('cond3-hide') ).show();
			}
		}

	});
	jQuery('.um-adm-conditional').each(function(){jQuery(this).trigger('change');});
});
