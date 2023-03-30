jQuery(window).on( 'load', function($) {
	var observer = new MutationObserver(function(mutations) {
		mutations.forEach(function(mutation) {
			jQuery(mutation.addedNodes).find('.um.um-directory').each(function() {
				jQuery('.um-directory input, .um-directory select, .um-directory button').attr('disabled', 'disabled');
				jQuery('.um-directory a').attr('href', '');
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
