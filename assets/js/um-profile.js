jQuery(document).ready(function() {

	/* auto-submit form with icon */
	jQuery(document).on('click', '.um-profile-save', function(e){
		e.preventDefault();

		jQuery(this).parents('.um').find('form').submit();
		
		return false;
	});

});