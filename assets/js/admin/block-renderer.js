jQuery(window).on( 'load', function($) {
	var observer = new MutationObserver(function(mutations) {
		mutations.forEach(function(mutation) {
			jQuery(mutation.addedNodes).find('.um.um-directory').each(function() {
				jQuery('.um-directory input, .um-directory select, .um-directory button').attr('disabled', 'disabled');
				jQuery('.um-directory a').attr('href', '');

				if ( typeof( jQuery.fn.select2 ) === 'function' ) {
					jQuery(".um-s1").each( function( e ) {
						var obj = jQuery(this);
						obj.select2({
							allowClear: true,
							dropdownParent: obj.parent()
						}).on( 'change', unselectEmptyOption );
					} );

					jQuery(".um-s2").each( function( e ) {
						var obj = jQuery(this);

						// fix https://github.com/ultimatemember/ultimatemember/issues/941
						// using .um-custom-shortcode-tab class as temporarily solution
						var atts = {};
						if ( obj.parents('.um-custom-shortcode-tab').length ) {
							atts = {
								allowClear: false
							};
						} else {
							atts = {
								allowClear: false,
								minimumResultsForSearch: 10,
								dropdownParent: obj.parent()
							};
						}
						obj.select2( atts ).on( 'change', unselectEmptyOption );
					} );

					jQuery(".um-s3").each( function( e ) {
						var obj = jQuery(this);

						obj.select2({
							allowClear: false,
							minimumResultsForSearch: -1,
							dropdownParent: obj.parent()
						}).on( 'change', unselectEmptyOption );
					} );
				}
			});
			jQuery(mutation.addedNodes).find('.um.um-profile').each(function() {
				jQuery('.um-profile input, .um-profile select, .um-profile button').attr('disabled', 'disabled');
				jQuery('.um-profile a').attr('href', '');
			});
			jQuery(mutation.addedNodes).find('.um.um-account').each(function() {
				jQuery('.um-account input, .um-account select, .um-account button').attr('disabled', 'disabled');
				jQuery('.um-account a').attr('href', '');
			});
			jQuery(mutation.addedNodes).find('.um.um-password').each(function() {
				jQuery('.um-password input, .um-password select, .um-password button').attr('disabled', 'disabled');
				jQuery('.um-password a').attr('href', '');
			});
		});
	});

	observer.observe(document, {attributes: false, childList: true, characterData: false, subtree:true});

});

function unselectEmptyOption( e ) {
	var $element = jQuery( e.currentTarget );
	var $selected = $element.find(':selected');

	if ( $selected.length > 1 ) {
		$selected.each( function ( i, option ) {
			if ( option.value === '' ) {
				option.selected = false;
				$element.trigger( 'change' );
			}
		});
	}
}``
