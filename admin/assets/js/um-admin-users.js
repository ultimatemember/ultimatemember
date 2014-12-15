jQuery(document).ready(function() {

	jQuery('.um_single_user_action').each(function(){
	
		jQuery(this).change(function(){
	
		val = jQuery(this).val();

		if ( val != '' ){
			var href=  jQuery(this).parents('tr').find('a.button').attr('href');
			var new_href = href + '&um_single_user_action=' + val;
			jQuery(this).parents('tr').find('a.button').attr('href',new_href);
		}
	
		});
		
	});
	
});