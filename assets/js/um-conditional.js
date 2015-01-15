jQuery(document).ready(function() {
	
	jQuery(document).on('input', '.um-field input[type=text]', function(){
		
		live_field = jQuery(this).parents('.um-field').data('key');
		live_value = jQuery(this).val();
		um_conditional();
		
	});
	jQuery('.um-field input[type=text]').trigger('input');
	
	jQuery(document).on('change', '.um-field select, .um-field input[type=radio], .um-field input[type=checkbox]', function(){
		
		live_field = jQuery(this).parents('.um-field').data('key');
		live_value = jQuery(this).val();
		
		if ( jQuery(this).is(':checkbox') ) {
			live_value = jQuery(this).parents('.um-field').find('input:checked').val();
		}
		
		um_conditional();
		
	});

});