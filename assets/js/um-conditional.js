jQuery(document).ready(function() {

	jQuery(document).on('input change', '.um-field input[type=text]', function(){

		live_field = jQuery(this).parents('.um-field').data('key');
		live_value = jQuery(this).val();
		um_conditional();

	});
	jQuery('.um-field input[type=text]').trigger('input');

	jQuery(document).on('change', '.um-field select, .um-field input[type=radio], .um-field input[type=checkbox]', function(){

		live_field = jQuery(this).parents('.um-field').data('key');
		live_value = jQuery(this).val();

		if ( jQuery(this).is(':checkbox') ) {
			if ( jQuery(this).parents('.um-field').find('input:checked').length > 1 ) {
				live_value = '';
				jQuery(this).parents('.um-field').find('input:checked').each(function(){
					live_value = live_value + jQuery(this).val() + ' ';
				});
			} else {
				live_value = jQuery(this).parents('.um-field').find('input:checked').val();
			}
		}

		if ( jQuery(this).is(':radio') ) {
			live_value = jQuery(this).parents('.um-field').find('input[type=radio]:checked').val();
		}

		um_conditional();

	});
	jQuery('.um-field select, .um-field input[type=radio], .um-field input[type=checkbox]').trigger('change');

});
