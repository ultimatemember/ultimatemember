jQuery(document).ready(function() {

	
	jQuery(document).on('input change', '.um-field input[type=text]', function(){
		if( um_field_do_init  ){
			um_field_init();
		}
	});

	jQuery(document).on('change', '.um-field select, .um-field input[type=radio], .um-field input[type=checkbox]', function(){
		if( um_field_do_init  ){
			um_field_init();
		}

	});
	
	um_field_init();
	um_field_do_init = true;
	
	
});
