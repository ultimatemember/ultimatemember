jQuery(document).ready(function() {

	var um_live_field;
	var um_live_value;
	var um_field_conditions_array = {};
	var um_field_relations = {};
	var um_field_added = [];
	var um_field_loaded = false;
	var um_field_do_init = false;

	function um_field_init(){

		jQuery('.um-field[data-key]').each(function(){
			um_field_set_conditions( jQuery(this), true );
		});

		um_field_relationship();

		um_field_apply_conditions();
	    um_field_loaded = true;
		
		
					
	}

	function um_field_set_conditions( um_field_dom, add_fade ){
		var um_field_key = um_field_dom.data('key');
		var arr_field = [];
		for (var i = 0; i < 5; i++) {

				var action0 	= um_field_dom.data('cond-'+i+'-action');
				var field0 		= um_field_dom.data('cond-'+i+'-field');
				var operator0 	= um_field_dom.data('cond-'+i+'-operator');
				var value0 		= um_field_dom.data('cond-'+i+'-value');

				if( typeof value0 !== 'undefined' && um_field_loaded == false ){
					arr_field.push({
						child: um_field_key,
						action: action0,
						field: field0,
						operator: operator0,
						value: value0
					});

				}
			
		} // end for

		if( jQuery.inArray( um_field_key, um_field_added ) <= -1 && um_field_loaded == false ){
				um_field_added.push( um_field_key );
				um_field_conditions_array[ um_field_key ] = arr_field;
		}
		
	}

	function um_field_apply_conditions(){
			
			var field_results = {};
			
			jQuery.each( um_field_added, function( i, field_key ){
				
				if( um_field_relations[ field_key ].length <= 0 ){
					um_field_relations[ field_key ] = [{
						child: field_key,
						action:'child',
						field: '',
						operator: '',
					}];
				}
				
				jQuery.each( um_field_relations[ field_key ], function( ii, conditions ){
					
					var add_fade = true;
					var action0	 = conditions.action;
					var value0 = conditions.value;
					var operator0 = conditions.operator;
					var field0 = conditions.field;
					var um_field_parent_dom = '';
					
					um_field_parent_dom = jQuery('.um-field[data-key="'+field_key+'"]:visible').find('input[type=text],textarea,input[type=checkbox],input[type=radio],select,[class=um-field-block]');

					var um_field_data = um_get_field_data( um_field_parent_dom );
					var um_live_field = um_field_data.key;
					var um_live_value = um_field_data.value;
					
					var um_field_child_dom = jQuery('.um-field[data-key="'+conditions.child+'"]').find('input[type=text],textarea,input[type=checkbox],input[type=radio],select,[class=um-field-block]');
					var um_field_child_dom_hide = jQuery('.um-field[data-key="'+conditions.child+'"]');
					
					

					if (  action0 == 'show'  && typeof value0 !== 'undefined' ) {

						if ( operator0 == 'empty' ) {
							if ( !um_live_value || um_live_value == '' ) {
								um_field_show( um_field_child_dom, add_fade, operator0, um_live_field , field_key );
								field_results[ conditions.child ] = { act: action0, op: operator0 };
							}else{
								um_field_child_dom_hide.hide();
								field_results[ conditions.child ] = { act: 'hide', op: operator0 };
							}
						}

						if ( operator0 == 'not empty' ) {
							if ( um_live_value && um_live_value != '' ) {
								um_field_show( um_field_child_dom, add_fade, operator0, um_live_field , field_key );
								field_results[ conditions.child ] = { act: action0, op: operator0 };
							}else{
								um_field_child_dom_hide.hide();
								field_results[ conditions.child ] = { act: 'hide', op: operator0 };
							} 
						}

						if ( operator0 == 'equals to' ) {
							if ( value0 == um_live_value  ) {
								um_field_show( um_field_child_dom, add_fade, operator0, um_live_field , field_key );
								field_results[ conditions.child ] = { act: action0, op: operator0 };
							}else{
								um_field_child_dom_hide.hide();
								field_results[ conditions.child ] = { act: 'hide', op: operator0 };
							} 
						}

						if ( operator0 == 'not equals' ) {
							if ( jQuery.isNumeric( value0 ) && parseInt( um_live_value ) != parseInt( value0 ) && um_live_value  ) {
								um_field_show( um_field_child_dom, add_fade, operator0, um_live_field , field_key );
								field_results[ conditions.child ] = { act: action0, op: operator0 };
							} else if ( !jQuery.isNumeric( value0 ) && value0 != um_live_value  ) {
								um_field_show( um_field_child_dom, add_fade, operator0, um_live_field , field_key );
								field_results[ conditions.child ] = { act: action0, op: operator0 };
							}else{
								um_field_child_dom_hide.hide();
								field_results[ conditions.child ] = { act: 'hide', op: operator0 };
							}
						}

						if ( operator0 == 'greater than' ) {
							if ( jQuery.isNumeric( value0 ) && parseInt( um_live_value ) > parseInt( value0 )  ) {
								um_field_show( um_field_child_dom, add_fade, operator0, um_live_field , field_key );
								field_results[ conditions.child ] = { act: action0, op: operator0 };
							}else{
								um_field_child_dom_hide.hide();
								field_results[ conditions.child ] = { act: 'hide', op: operator0 };
							}
						}

						if ( operator0 == 'less than' ) {
							if ( jQuery.isNumeric( value0 ) && parseInt( um_live_value ) < parseInt( value0 ) && um_live_value  ) {
								um_field_show( um_field_child_dom, add_fade, operator0, um_live_field , field_key );
								field_results[ conditions.child ] = { act: action0, op: operator0 };
							}else{
								um_field_child_dom_hide.hide();
								field_results[ conditions.child ] = { act: 'hide', op: operator0 };
							} 
						}

						if ( operator0 == 'contains' ) {
							if ( um_live_value && um_live_value.indexOf( value0 ) >= 0  ) {
								um_field_show( um_field_child_dom, add_fade, operator0, um_live_field , field_key );
								field_results[ conditions.child ] = { act: action0, op: operator0 };
							}else{
								um_field_child_dom_hide.hide();
								field_results[ conditions.child ] = { act: 'hide', op: operator0 };
							} 
						}

					}

					if (  action0 == 'hide' && typeof value0 !== 'undefined'  ) {

						if ( operator0 == 'empty' ) {
							if ( !um_live_value || um_live_value == '' ) {
								um_field_hide( um_field_child_dom, add_fade, operator0, um_live_field , field_key );
								field_results[ conditions.child ] = { act: action0, op: operator0 };
							}else{
								field_results[ conditions.child ] = { act: 'show', op: operator0 };
							} 
						}

						if ( operator0 == 'not empty' ) {
							if ( um_live_value && um_live_value != '' ) {
								um_field_hide( um_field_child_dom, add_fade, operator0, um_live_field , field_key );
								field_results[ conditions.child ] = { act: action0, op: operator0 };
							}else{
								field_results[ conditions.child ] = { act: 'show', op: operator0 };
							} 
						}

						if ( operator0 == 'equals to' ) {
							if ( value0 == um_live_value ) {
								um_field_hide( um_field_child_dom, add_fade, operator0, um_live_field , field_key );
								field_results[ conditions.child ] = { act: action0, op: operator0 };
							}else{
								field_results[ conditions.child ] = { act: 'show', op: operator0 };
							} 
						}

						if ( operator0 == 'not equals' ) {
							if ( jQuery.isNumeric( value0 ) && parseInt( um_live_value ) != parseInt( value0 ) && um_live_value ) {
								um_field_hide( um_field_child_dom, add_fade, operator0, um_live_field , field_key );
								field_results[ conditions.child ] = { act: action0, op: operator0 };
							} else if ( !jQuery.isNumeric( value0 ) && value0 != um_live_value ) {
								um_field_hide( um_field_child_dom, add_fade, operator0, um_live_field , field_key );
								field_results[ conditions.child ] = { act: action0, op: operator0 };
							}else{
								um_field_child_dom_hide.show();
								field_results[ conditions.child ] = { act: 'show', op: operator0 };
							} 
						}

						if ( operator0 == 'greater than' ) {
							if ( jQuery.isNumeric( value0 ) && parseInt( um_live_value ) > parseInt( value0 ) ) {
								um_field_hide( um_field_child_dom, add_fade, operator0, um_live_field , field_key );
								field_results[ conditions.child ] = { act: action0, op: operator0 };
							}else{
								um_field_child_dom_hide.show();
								field_results[ conditions.child ] = { act: 'show', op: operator0 };
							} 
						}

						if ( operator0 == 'less than' ) {
							if ( jQuery.isNumeric( value0 ) && parseInt( um_live_value ) < parseInt( value0 ) && um_live_value ) {
								um_field_hide( um_field_child_dom, add_fade, operator0, um_live_field , field_key );
								field_results[ conditions.child ] = { act: action0, op: operator0 };
							}else{
								field_results[ conditions.child ] = { act: 'show', op: operator0 };
							} 
						}

						if ( operator0 == 'contains' ) {
							if ( um_live_value && um_live_value.indexOf( value0 ) >= 0 ) {
								um_field_hide( um_field_child_dom, add_fade, operator0, um_live_field , field_key );
								field_results[ conditions.child ] = { act: action0, op: operator0 };
							}else{
								field_results[ conditions.child ] = { act: 'show', op: operator0 };
							} 
						}
					
					}

					var c_child = field_results[ conditions.child ];
					
					if( action0 == 'child' && typeof c_child !== 'undefined' ){
						if( c_child.act == 'hide' ){
							jQuery('.um-field[data-key="'+field_key+'"]').hide();
						}else if( c_child.act == 'show' ){
							jQuery('.um-field[data-key="'+field_key+'"]').show();
						}
					}

				});

			});
			

			
	}

	function um_field_show( field, add_fade, optr, k, field_key ){

		field = field.parents('.um-field');

		if( field.is(':hidden') ){
			if( add_fade ){
				field.fadeIn(1);
			}else{
				field.show();
			}
			//console.log( field_key );
		}
	
	}

	function um_field_hide( field, add_fade, optr, k, field_key ){

		field = field.parents('.um-field');
		
		if( field.is(':visible') ){
			if( add_fade ){
				field.fadeOut(1);
			}else{
				field.hide();
			}
			//console.log( field_key );
		}
	}

	function um_get_field_data( um_field_dom ){
		um_live_field = um_field_dom.parents('.um-field').data('key');
		um_live_value = um_field_dom.val();

		if ( um_field_dom.is(':checkbox') ) {

				if ( um_field_dom.parents('.um-field').find('input:checked').length > 1 ) {
					um_live_value = '';
					um_field_dom.parents('.um-field').find('input:checked').each(function(){
						um_live_value = um_live_value + jQuery(this).val() + ' ';
					});
				} else {
					um_live_value = um_field_dom.parents('.um-field').find('input:checked').val();
				}

		}

		if ( um_field_dom.is(':radio') ) {
				um_live_value = um_field_dom.parents('.um-field').find('input[type=radio]:checked').val();
		}

		return {
			key: um_live_field,
			value: um_live_value
		};

	}

	function um_field_relationship(){
			var arr_fields  = um_field_conditions_array;
			if( um_field_loaded == false ){
				jQuery.each( arr_fields, function(k, f) { 

					var new_arr_field = [];
					var arr_um_field_exists = [];

					jQuery.each( arr_fields, function(ii,field) { 
							for (var i = 0; i <= field.length; i++ ){

								if( typeof field[ i ]  !== 'undefined' ){
									if(  k == field[ i ].field  ){
										new_arr_field.push(  field[ i ] );
										arr_um_field_exists.push( field[ i ].child );
									}
								}
								

							}
					});

					um_field_relations[ k ] = new_arr_field;
				});
			}


	}
	
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
