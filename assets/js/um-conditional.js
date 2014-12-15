var live_field;
var live_value;

function um_conditional(){

	jQuery('.um-field.um-is-conditional').each(function(){
	
		for (var i = 0; i < 5; i++) {
		
			var action0 = jQuery(this).data('cond-'+i+'-action');
			var field0 = jQuery(this).data('cond-'+i+'-field');
			var operator0 = jQuery(this).data('cond-'+i+'-operator');
			var value0 = jQuery(this).data('cond-'+i+'-value');
			
			if (  action0 == 'show' && field0 == live_field ) {
			
				if ( operator0 == 'empty' ) {
					if ( live_value == '' ) {
						jQuery(this).fadeIn();
					} else {
						jQuery(this).hide();
					}
				}
				
				if ( operator0 == 'not empty' ) {
					if ( live_value != '' ) {
						jQuery(this).fadeIn();
					} else {
						jQuery(this).hide();
					}
				}
				
				if ( operator0 == 'equals to' ) {
					if ( value0 == live_value ) {
						jQuery(this).fadeIn();
					} else {
						jQuery(this).hide();
					}
				}
				
				if ( operator0 == 'not equals' ) {
					if ( jQuery.isNumeric( value0 ) && parseInt( live_value ) != parseInt( value0 ) && live_value ) {
						jQuery(this).fadeIn();
					} else if ( !jQuery.isNumeric( value0 ) && value0 != live_value ) {
						jQuery(this).fadeIn();
					} else {
						jQuery(this).hide();
					}
				}
				
				if ( operator0 == 'greater than' ) {
					if ( jQuery.isNumeric( value0 ) && parseInt( live_value ) > parseInt( value0 ) ) {
						jQuery(this).fadeIn();
					} else {
						jQuery(this).hide();
					}
				}
				
				if ( operator0 == 'less than' ) {
					if ( jQuery.isNumeric( value0 ) && parseInt( live_value ) < parseInt( value0 ) && live_value ) {
						jQuery(this).fadeIn();
					} else {
						jQuery(this).hide();
					}
				}
				
				if ( operator0 == 'contains' ) {
					if ( live_value.indexOf( value0 ) >= 0 ) {
						jQuery(this).fadeIn();
					} else {
						jQuery(this).hide();
					}
				}
				
			}
			
			if (  action0 == 'hide' && field0 == live_field ) {
			
				if ( operator0 == 'empty' ) {
					if ( live_value == '' ) {
						jQuery(this).hide();
					} else {
						jQuery(this).fadeIn();
					}
				}
				
				if ( operator0 == 'not empty' ) {
					if ( live_value != '' ) {
						jQuery(this).hide();
					} else {
						jQuery(this).fadeIn();
					}
				}
				
				if ( operator0 == 'equals to' ) {
					if ( value0 == live_value ) {
						jQuery(this).hide();
					} else {
						jQuery(this).fadeIn();
					}
				}
				
				if ( operator0 == 'not equals' ) {
					if ( jQuery.isNumeric( value0 ) && parseInt( live_value ) != parseInt( value0 ) && live_value ) {
						jQuery(this).hide();
					} else if ( !jQuery.isNumeric( value0 ) && value0 != live_value ) {
						jQuery(this).hide();
					} else {
						jQuery(this).fadeIn();
					}
				}
				
				if ( operator0 == 'greater than' ) {
					if ( jQuery.isNumeric( value0 ) && parseInt( live_value ) > parseInt( value0 ) ) {
						jQuery(this).hide();
					} else {
						jQuery(this).fadeIn();
					}
				}
				
				if ( operator0 == 'less than' ) {
					if ( jQuery.isNumeric( value0 ) && parseInt( live_value ) < parseInt( value0 ) && live_value ) {
						jQuery(this).hide();
					} else {
						jQuery(this).fadeIn();
					}
				}
				
				if ( operator0 == 'contains' ) {
					if ( live_value.indexOf( value0 ) >= 0 ) {
						jQuery(this).hide();
					} else {
						jQuery(this).fadeIn();
					}
				}
				
			}
		
		}

	});

}

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