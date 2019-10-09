// NEW CONDITION LOGIC
function condition_fields( all_conds, form_id ) {
console.log(all_conds)
	jQuery.each( all_conds, function( metakey ) {
		var greater, less;
		var first_group = 0,
			state_array = [],
			state = 'show',
			less_greater = [];

		jQuery.each( all_conds[ metakey ], function() {

			var action = this[0],
				field = this[1],
				op = this[2],
				val = this[3],
				group = this[5],
				depend_field;

			var input = jQuery('.um.um-' + form_id + ' .um-field[data-key="' + field + '"] .um-field-area input'),
				select = jQuery('.um.um-' + form_id + ' .um-field[data-key="' + field + '"] .um-field-area>select'),
				textarea = jQuery('.um.um-' + form_id + ' .um-field[data-key="' + field + '"] .um-field-area>textarea'),
				content_block = jQuery('.um.um-' + form_id + ' .um-field[data-key="' + field + '"] .um-field-block'),
				depend_arr = [],
				output_field = jQuery('.um.um-' + form_id + ' .um-field[data-key="' + field + '"] .um-field-value');

			if ( input.length > 0 && select.length === 0 ) {

				if ( input.is(':checkbox') ) {

					var checked = jQuery('.um.um-' + form_id + ' .um-field[data-key="' + field + '"] input:checked');
					checked.each(function () {
						var checked_vals = jQuery(this).val();
						depend_arr.push(checked_vals);
					});

				} else if ( input.is(':radio') ) {

					depend_field = jQuery('.um.um-' + form_id + ' .um-field[data-key="' + field + '"] input:checked').val();

				} else if ( input.is(':hidden') ) {

					depend_field = jQuery('.um.um-' + form_id + ' .um-field[data-key="' + field + '"] input').val();
					if ( depend_field === 'empty_file' ) {
						depend_field = '';
					}

				} else {

					depend_field = input.val();

				}

			} else if ( select.length > 0 ) {

				var multi = select.prop('multiple');
				if( multi === true ){

					depend_arr = select.val();

				} else {
					if ( jQuery.inArray(val, select.val()) > 0 ) {
						depend_field = val;
					} else {
						depend_field = select.val();
					}
				}

			} else if ( textarea.length > 0 ) {

				depend_field = textarea.val();

			} else if ( content_block.length > 0 ) {

				depend_field = content_block.text();
				if ( output_field.hasClass('um-field-value-multiselect') || output_field.hasClass('um-field-value-checkbox') ) {

				} else if ( output_field.length > 0 ) {
					depend_arr = output_field.text().split(', ');
				} else {
					depend_field = output_field.text();
				}

			}
			if( !depend_field ){
				depend_field = output_field.text();
			}

			// If another group rule
			if ( parseInt(group) !== first_group ) {

				if ( action === 'show' ) {

					switch (op) {
						case 'equals to':

							if ( depend_arr && depend_arr.length > 0 ) {
								depend_arr = depend_arr.toString();
								if( depend_arr === val ){
									state = 'show';
								} else {
									state = 'hide';
								}
							} else {
								if ( depend_field == val ) {
									state = 'show';
								} else {
									state = 'hide';
								}
							}

							break;

						case 'not equals':

							if ( depend_arr && depend_arr.length > 0 ) {
								depend_arr = depend_arr.toString();
								if( depend_arr !== val ){
									state = 'show';
								} else {
									state = 'hide';
								}
							} else {
								if ( depend_field != val ) {
									state = 'show';
								} else {
									state = 'hide';
								}
							}

							break;

						case 'empty':

							if ( depend_arr && depend_arr.length > 0 ) {
								if( depend_arr.length === 0 ){
									state = 'show';
								} else {
									state = 'hide';
								}
							} else {
								if ( !depend_field || depend_field === '' ) {
									state = 'show';
								} else {
									state = 'hide';
								}
							}

							break;

						case 'not empty':

							if ( depend_arr && depend_arr.length > 0 ) {
								if( depend_arr.length > 0 ){
									state = 'show';
								} else {
									state = 'hide';
								}
							} else {
								if ( depend_field && depend_field !== '' ) {
									state = 'show';
								} else {
									state = 'hide';
								}
							}

							break;

						case 'greater than':

							if ( depend_arr && depend_arr.length > 0 ) {
								jQuery.each(depend_arr, function () {
									if ( jQuery.isNumeric(val) && jQuery.isNumeric(this) ) {
										if ( parseInt(val) <= parseInt(this) ) {
											state = 'show';
											return false;
										} else {
											state = 'hide';
										}
									} else {
										state = 'hide';
									}
								});
							} else {
								if ( jQuery.isNumeric(val) && jQuery.isNumeric(depend_field) ) {
									if ( parseInt(val) <= parseInt(depend_field) ) {
										state = 'show';
									} else {
										state = 'hide';
									}
								} else {
									state = 'hide';
								}
							}

							break;

						case 'less than':
							if ( depend_arr && depend_arr.length > 0 ) {
								jQuery.each(depend_arr, function () {
									if ( jQuery.isNumeric(val) && jQuery.isNumeric(this) ) {
										if ( parseInt(val) >= parseInt(this) ) {
											state = 'show';
											return false;
										} else {
											state = 'hide';
										}
									} else {
										state = 'hide';
									}
								});
							} else {
								if ( jQuery.isNumeric(val) && jQuery.isNumeric(depend_field) ) {
									if ( parseInt(val) >= parseInt(depend_field) ) {
										state = 'show';
									} else {
										state = 'hide';
									}
								} else {
									state = 'hide';
								}
							}

							break;

						case 'contains':
							if ( depend_arr && depend_arr.length > 0 ) {
								jQuery.each(depend_arr, function () {
									if ( this && this.search(val) >= 0 ) {
										state = 'show';
										return false;
									} else {
										state = 'hide';
									}
								});
							} else {
								if ( depend_field && depend_field.search(val) >= 0 ) {
									state = 'show';
								} else {
									state = 'hide';
								}
							}

							break;

					}

				} else { // if hide

					switch (op) {
						case 'equals to':

							if ( depend_arr && depend_arr.length > 0 ) {
								depend_arr = depend_arr.toString();
								if( depend_arr === val ){
									state = 'hide';
								} else {
									state = 'show';
								}
							} else {
								if ( depend_field == val ) {
									state = 'hide';
								} else {
									state = 'show';
								}
							}

							break;

						case 'not equals':

							if ( depend_arr && depend_arr.length > 0 ) {
								depend_arr = depend_arr.toString();
								if( depend_arr !== val ){
									state = 'hide';
								} else {
									state = 'show';
								}
							} else {
								if ( depend_field != val ) {
									state = 'hide';
								} else {
									state = 'show';
								}
							}

							break;

						case 'empty':

							if ( depend_arr && depend_arr.length > 0 ) {
								if( depend_arr.length === 0 ){
									state = 'hide';
								} else {
									state = 'show';
								}

							} else {
								if ( !depend_field || depend_field === '' ) {
									state = 'hide';
								} else {
									state = 'show';
								}
							}

							break;

						case 'not empty':

							if ( depend_arr && depend_arr.length > 0 ) {
								if( depend_arr.length > 0 ){
									state = 'hide';
								} else {
									state = 'show';
								}
							} else {
								if ( depend_field && depend_field !== '' ) {
									state = 'hide';
								} else {
									state = 'show';
								}
							}

							break;

						case 'greater than':

							if ( depend_arr && depend_arr.length > 0 ) {
								jQuery.each(depend_arr, function () {
									if ( jQuery.isNumeric(val) && jQuery.isNumeric(this) ) {
										if ( parseInt(val) < parseInt(this) ) {
											state = 'hide';
											return false;
										} else {
											state = 'show';
										}
									} else {
										state = 'show';
									}
								});
							} else {
								if ( jQuery.isNumeric(val) && jQuery.isNumeric(depend_field) ) {
									if ( parseInt(val) < parseInt(depend_field) ) {
										state = 'hide';
									} else {
										state = 'show';
									}
								} else {
									state = 'show';
								}
							}

							break;

						case 'less than':

							if ( depend_arr && depend_arr.length > 0 ) {
								jQuery.each(depend_arr, function () {
									if ( jQuery.isNumeric(val) && jQuery.isNumeric(this) ) {
										if ( parseInt(val) > parseInt(this) ) {
											state = 'hide';
											return false;
										} else {
											state = 'show';
										}
									} else {
										state = 'show';
									}
								});
							} else {
								if ( jQuery.isNumeric(val) && jQuery.isNumeric(depend_field) ) {
									if ( parseInt(val) > parseInt(depend_field) ) {
										state = 'hide';
									} else {
										state = 'show';
									}
								} else {
									state = 'show';
								}
							}

							break;

						case 'contains':
							if ( depend_arr && depend_arr.length > 0 ) {
								jQuery.each(depend_arr, function () {
									if ( this && this.search(val) >= 0 ) {
										state = 'hide';
										return false;
									} else {
										state = 'show';
									}
								});
							} else {
								if ( depend_field && depend_field.search(val) >= 0 ) {
									state = 'hide';
								} else {
									state = 'show';
								}
							}

							break;

					}

				}
				first_group++;
				state_array.push(state);
			} else { // If the same group rule

				if ( action === 'show' ) {

					switch (op) {
						case 'equals to':

							if ( depend_arr && depend_arr.length > 0 ) {
								depend_arr = depend_arr.toString();
								if( depend_arr === val ){
									state = 'show';
								} else {
									state = 'hide';
								}
							} else {
								if ( depend_field == val ) {
									state = 'show';
								} else {
									state = 'hide';
								}
							}

							break;

						case 'not equals':

							if ( depend_arr && depend_arr.length > 0 ) {
								depend_arr = depend_arr.toString();
								if( depend_arr !== val ){
									state = 'show';
								} else {
									state = 'hide';
								}
							} else {
								if ( depend_field != val ) {
									state = 'show';
								} else {
									state = 'hide';
								}
							}

							break;

						case 'empty':

							if ( depend_arr && depend_arr.length > 0 ) {
								if( depend_arr.length === 0 ){
									state = 'show';
								} else {
									state = 'hide';
								}

							} else {
								if ( !depend_field || depend_field === '' ) {
									state = 'show';
								} else {
									state = 'hide';
								}
							}

							break;

						case 'not empty':

							if ( depend_arr && depend_arr.length > 0 ) {
								if( depend_arr.length > 0 ){
									state = 'show';
								} else {
									state = 'hide';
								}

							} else {
								if ( depend_field && depend_field !== '' ) {
									state = 'show';
								} else {
									state = 'hide';
								}
							}

							break;

						case 'greater than':

							if ( depend_arr && depend_arr.length > 0 ) {
								jQuery.each(depend_arr, function () {

									if ( jQuery.isNumeric(val) && jQuery.isNumeric(this) ) {
										if ( parseInt(val) <= parseInt(this) ) {
											state = 'show';
											return false;
										} else {
											state = 'hide';
										}
									} else {
										state = 'hide';
									}
								});
							} else {

								if ( jQuery.isNumeric(val) && jQuery.isNumeric(depend_field) ) {
									if ( parseInt(val) <= parseInt(depend_field) ) {
										state = 'show';
									} else {
										state = 'hide';
									}
								} else {
									state = 'hide';
								}
							}

							break;

						case 'less than':

							if ( depend_arr && depend_arr.length > 0 ) {
								jQuery.each(depend_arr, function () {
									if ( jQuery.isNumeric(val) && jQuery.isNumeric(this) ) {
										if ( parseInt(val) >= parseInt(this) ) {
											state = 'show';
											return false;
										} else {
											state = 'hide';
										}
									} else {
										state = 'hide';
									}
								});
							} else {
								if ( jQuery.isNumeric(val) && jQuery.isNumeric(depend_field) ) {
									if ( parseInt(val) >= parseInt(depend_field) ) {
										state = 'show';
									} else {
										state = 'hide';
									}
								} else {
									state = 'hide';
								}
							}

							break;

						case 'contains':

							if ( depend_arr && depend_arr.length > 0 ) {
								jQuery.each(depend_arr, function () {
									if ( this && this.search(val) >= 0 ) {
										state = 'show';
										return false;
									} else {
										state = 'hide';
									}
								});
							} else {
								if ( depend_field && depend_field.search(val) >= 0 ) {
									state = 'show';
								} else {
									state = 'hide';
								}
							}

							break;

					}
				} else { // if hide

					switch (op) {
						case 'equals to':
							if ( depend_arr && depend_arr.length > 0 ) {
								depend_arr = depend_arr.toString();
								if( depend_arr === val ){
									state = 'hide';
								} else {
									state = 'show';
								}
							} else {
								if ( depend_field == val ) {
									state = 'hide';
								} else {
									state = 'show';
								}
							}
							break;

						case 'not equals':

							if ( depend_arr && depend_arr.length > 0 ) {
								depend_arr = depend_arr.toString();
								if( depend_arr !== val ){
									state = 'hide';
								} else {
									state = 'show';
								}
							} else {
								if ( depend_field != val ) {
									state = 'hide';
								} else {
									state = 'show';
								}
							}

							break;

						case 'empty':

							if ( depend_arr && depend_arr.length > 0 ) {
								if( depend_arr.length === 0 ){
									state = 'hide';
								} else {
									state = 'show';
								}

							} else {
								if ( !depend_field || depend_field === '' ) {
									state = 'hide';
								} else {
									state = 'show';
								}
							}


							break;

						case 'not empty':

							if ( depend_arr && depend_arr.length > 0 ) {
								if( depend_arr.length > 0 ){
									state = 'hide';
								} else {
									state = 'show';
								}

							} else {
								if ( depend_field && depend_field !== '' ) {
									state = 'hide';
								} else {
									state = 'show';
								}
							}

							break;

						case 'greater than':

							if ( depend_arr && depend_arr.length > 0 ) {
								jQuery.each(depend_arr, function () {
									if ( jQuery.isNumeric(val) && jQuery.isNumeric(this) ) {
										if ( parseInt(val) > parseInt(this) ) {
											state = 'show';
										} else {
											state = 'hide';
										}
									} else {
										state = 'show';
									}
								});
							} else {
								if ( jQuery.isNumeric(val) && jQuery.isNumeric(depend_field) ) {
									if ( parseInt(val) > parseInt(depend_field) ) {
										state = 'show';
									} else {
										state = 'hide';
									}
								} else {
									state = 'show';
								}

							}
							less_greater['greater'] = state;
							greater = state;

							if( less && (less !== 'hide' || state !== 'hide') ){
								state = 'show';
							}
							break;

						case 'less than':

							if ( depend_arr && depend_arr.length > 0 ) {
								jQuery.each(depend_arr, function () {
									if ( jQuery.isNumeric(val) && jQuery.isNumeric(this) ) {
										if ( parseInt(val) < parseInt(this) ) {
											state = 'show';
										} else {
											state = 'hide';
										}
									} else {
										state = 'show';
									}
								});
							} else {
								if ( jQuery.isNumeric(val) && jQuery.isNumeric(depend_field) ) {
									if ( parseInt(val) < parseInt(depend_field) ) {
										state = 'show';
									} else {
										state = 'hide';
									}
								} else {
									state = 'show';
								}
							}
							less_greater['less'] = state;
							less = state;
							if( greater && (greater !== 'hide' || state !== 'hide') ){
								state = 'show';
							}

							break;

						case 'contains':

							if ( depend_arr && depend_arr.length > 0 ) {
								jQuery.each(depend_arr, function () {
									if ( this && this.search(val) >= 0 ) {
										state = 'hide';
										return false;
									} else {
										state = 'show';
									}
								});
							} else {
								if ( depend_field && depend_field.search(val) >= 0 ) {
									state = 'hide';
								} else {
									state = 'show';
								}
							}

							break;
					}


				}

				if ( state_array[first_group] ) {
					if( action === 'hide' ){
						if ( state_array[first_group] === 'hide' && state === 'hide' ) {
							state_array[first_group] = 'hide';
						} else {
							state_array[first_group] = 'show';
						}
					} else {
						if ( state_array[first_group] === 'show' && state === 'show' ) {
							state_array[first_group] = 'show';
						} else {
							state_array[first_group] = 'hide';
						}
					}
				} else {
					if ( state === 'show' ) {
						state_array[first_group] = 'show';
					} else {
						state_array[first_group] = 'hide';
					}
				}


			}

		});

		// var field = jQuery('.um-field[data-key="' + metakey + '"]');

		var field = jQuery('.um.um-' + form_id + ' .um-field[data-key="' + metakey + '"]');

		if ( all_conds[metakey][0][0] === 'show' ){
			if ( jQuery.inArray('show', state_array ) < 0) {
				hide_field(field);
			} else {
				show_field(field);
			}
		} else {
			if ( jQuery.inArray('hide', state_array) < 0 ) {
				show_field(field);
			} else {
				hide_field(field);
			}
			if( less_greater ){
				if( less_greater['less'] === 'hide' && less_greater['greater'] === 'hide' ){
					hide_field(field);
				} else if( less_greater['less'] === 'show' && less_greater['greater'] === 'hide' ){
					show_field(field);
				}
			}
		}


	});

}

function show_field( field ) {
	field.show();
	field.find('input, textarea').removeAttr('disabled').removeAttr('readonly');
	// field.find('label.active input').attr('checked', 'checked')
}

function hide_field( field ) {
	field.hide();
	var parent_field = field.closest('form');
	if( parent_field.length>0 ){
		field.find('input, textarea').attr('disabled', 'disabled').attr('readonly','readonly');
		// field.find('input, textarea').removeAttr('checked');
	}
}

function check_parent( all_conds, form_id ) {

	jQuery.each( all_conds, function( metakey ) {

		var first_group = 0;
		var group_array = [];
		jQuery.each( all_conds[ metakey ], function() {

			var action = this[0];
			var field = this[1];
			var group = this[5];
			var state;

			var check_field_visible = jQuery('.um.um-' + form_id + ' .um-field[data-key="' + field + '"]').not('.empty-field').is(':visible');
			var check_field_empty = jQuery('.um.um-' + form_id + ' .empty-field.um-field[data-key="' + field + '"] .um-field-value').html();
			var check = jQuery('.um.um-' + form_id + ' .um-field[data-key="' + metakey + '"]');



			if( first_group !== parseInt(group) ){
				if( check_field_visible === false && check_field_empty !== '' ) {
					state = 'hide';
				} else {
					state = 'show';
				}
				first_group++;
				group_array.push(state)
			} else {

				if( check_field_visible === false && check_field_empty !== '' ) {
					state = 'hide';
				} else {
					state = 'show';
				}
				if ( group_array[first_group] ) {
					if ( group_array[first_group] === 'show' && state === 'show' ) {
						group_array[first_group] = 'show';
					} else {
						group_array[first_group] = 'hide';
					}
				} else {
					if ( state === 'show' ) {
						group_array[first_group] = 'show';
					} else {
						group_array[first_group] = 'hide';
					}
				}
			}

			if ( jQuery.inArray('show', group_array) < 0 ) {
				check.addClass('hide-after-parent-check');
			} else {
				check.removeClass('hide-after-parent-check');
			}

			if( action === 'hide' ){
				if( check_field_visible === false && check_field_empty !== '' ){
					check.show()
					check.find('input, textarea').removeAttr('disabled').removeAttr('readonly');
					check.removeClass('hide-after-parent-check');
				}
			}

		});

	});
}

// OLD CONDITION LOGIC

var arr_all_conditions = []; //raw
var um_field_conditions = {}; // filtered
var um_field_default_values = {};

/**
 * Get field default value
 * @param object $dom
 * @return string
 */
function um_get_field_default_value( $dom ) {
	var default_value = '';
	var type = um_get_field_type($dom);
	switch ( type ) {

		case 'text':
		case 'number':
		case 'date':
		case 'textarea':
		case 'select':
			default_value = $dom.find('input:text,input[type=number],textarea,select').val();
			break;

		case 'multiselect':
			default_value = $dom.find('select').val();
			break;

		case 'radio':
			if ($dom.find('input[type=radio]:checked').length >= 1) {
				default_value = $dom.find('input[type=radio]:checked').val();
			}

			break;
		case 'checkbox':

			if ($dom.find('input[type=checkbox]:checked').length >= 1) {

				if ($dom.find('input[type=checkbox]:checked').length > 1) {
					$dom.find('input[type=checkbox]:checked').each(function () {
						default_value = default_value + jQuery(this).val() + ' ';
					});
				} else {
					default_value = $dom.find('input[type=checkbox]:checked').val();
				}

			}
			break;
	}

	return {type: type, value: default_value};
}

/**
 * Get field element by field wrapper
 * @param  object $dom
 * @return object
 */
function um_get_field_element( $dom ) {
	var default_value = '';
	var type = um_get_field_type($dom);

	switch ( type ) {

		case 'text':
		case 'number':
		case 'date':
		case 'textarea':
		case 'select':
		case 'multiselect':
		case 'radio':
		case 'checkbox':
			return $dom.find('input,textarea,select');
			break;


	}

	return '';
}

/**
 * Get field type
 * @param  object $dom
 * @return string
 */
function um_get_field_type($dom) {
	var type = '';
	var classes = $dom.attr( 'class' );
	jQuery.each( classes.split(' '), function (i, d) {
		if (d.indexOf('um-field-type') != -1) {
			type = d.split('_')[1];
		}
	});

	return type;

}

/**
 * Get field siblings/chidren conditions
 * @param  string field_key
 * @return array
 */
function um_get_field_children(field_key) {
	var arr_conditions = [];
	jQuery.each(arr_all_conditions, function (ii, condition) {
		if (condition.field.parent == field_key) {
			arr_conditions.push(condition.field.condition);
		}
	});

	return arr_conditions;

}

/**
 * Split single array to multi-dimensional array
 * @param  array arr
 * @param  integer n
 * @return array
 */
function um_splitup_array(arr, n) {
	var rest = arr.length % n,
		restUsed = rest,
		partLength = Math.floor(arr.length / n),
		result = [];

	for (var i = 0; i < arr.length; i += partLength) {
		var end = partLength + i,
			add = false;

		if (rest !== 0 && restUsed) {
			end++;
			restUsed--;
			add = true;
		}

		result.push(arr.slice(i, end));

		if (add) {
			i++;
		}
	}

	var obj_result = [];
	jQuery.each(result, function (ii, dd) {
		obj_result.push({
			action: dd[0],
			if_field: dd[1],
			operator: dd[2],
			value: dd[3]
		})
	});

	return obj_result;
}

/**
 * Get field live value
 * @param  object $dom
 * @return mixed
 */
function um_get_field_data($dom) {
	um_live_field = $dom.parents('.um-field').data('key');
	um_live_value = $dom.val();

	if ($dom.is(':checkbox')) {

		um_live_value = '';

		if ($dom.parents('.um-field').find('input:checked').length > 1) {
			$dom.parents('.um-field').find('input:checked').each(function () {
				um_live_value = um_live_value + jQuery(this).val() + ' ';
			});
		} else {
			if ($dom.parents('.um-field').find('input:checked').length >= 1) {
				um_live_value = $dom.parents('.um-field').find('input:checked').val();
			}
		}

	}

	if ($dom.is(':radio')) {
		um_live_value = $dom.parents('.um-field').find('input[type=radio]:checked').val();
	}

	return um_live_value;

}

function um_in_array(needle, haystack, strict){
	var found = false, key, strict = !!strict;
	for (key in haystack) {
		if ((strict && haystack[key] === needle) || (!strict && haystack[key] == needle)) {
			found = true;
			break;
		}
	}

	return found;
}

/**
 * Apply field conditions
 * @param  object  $dom
 * @param  boolean is_single_update
 */
function um_apply_conditions($dom, is_single_update) {
	var operators = ['empty', 'not empty', 'equals to', 'not equals', 'greater than', 'less than', 'contains'];
	var key = $dom.parents('.um-field[data-key]').data('key');
	var conditions = um_field_conditions[key];

	var live_field_value = um_get_field_data($dom);

	var $owners = {};
	var $owners_values = {};
	var $owner_conditions = {};

	jQuery.each(conditions, function (index, condition) {
		if (typeof $owners_values[condition.owner] == 'undefined') {
			$owners_values[condition.owner] = [];
			$owner_conditions[condition.owner] = {}
		}
		$owners_values[condition.owner].push(condition.value);
		$owner_conditions[condition.owner] = condition;
	});

	jQuery.each(conditions, function (index, condition) {
		if (typeof $owners[condition.owner] == 'undefined') {
			$owners[condition.owner] = {};
		}
		if (condition.operator == 'empty') {
			if (!live_field_value || live_field_value == '' && um_in_array(live_field_value, $owners_values[condition.owner])) {
				$owners[condition.owner][index] = true;
			} else {
				$owners[condition.owner][index] = false;
			}
		}

		if (condition.operator == 'not empty') {
			if (live_field_value && live_field_value != '' && !um_in_array(live_field_value, $owners_values[condition.owner])) {
				$owners[condition.owner][index] = true;
			} else {
				$owners[condition.owner][index] = false;
			}
		}

		if (condition.operator == 'equals to') {
			if (condition.value == live_field_value && um_in_array(live_field_value, $owners_values[condition.owner])) {
				$owners[condition.owner][index] = true;
			} else {
				$owners[condition.owner][index] = false;
			}
		}

		if (condition.operator == 'not equals') {
			if (jQuery.isNumeric(condition.value) && parseInt(live_field_value) != parseInt(condition.value) && live_field_value && !um_in_array(live_field_value, $owners_values[condition.owner])) {
				$owners[condition.owner][index] = true;
			} else if (condition.value != live_field_value && !um_in_array(live_field_value, $owners_values[condition.owner])) {
				$owners[condition.owner][index] = true;
			} else {
				$owners[condition.owner][index] = false;
			}
		}

		if (condition.operator == 'greater than') {
			if (jQuery.isNumeric(condition.value) && parseInt(live_field_value) > parseInt(condition.value)) {
				$owners[condition.owner][index] = true;
			} else {
				$owners[condition.owner][index] = false;
			}
		}

		if (condition.operator == 'less than') {
			if (jQuery.isNumeric(condition.value) && parseInt(live_field_value) < parseInt(condition.value)) {
				$owners[condition.owner][index] = true;
			} else {
				$owners[condition.owner][index] = false;
			}
		}

		if ( condition.operator == 'contains' ) {
			if ( 'multiselect' == um_get_field_type( $dom.parents('.um-field[data-key]') ) ) {
				if ( live_field_value && live_field_value.indexOf( condition.value ) >= 0 && um_in_array( condition.value, live_field_value ) ) {
					$owners[condition.owner][index] = true;
				} else {
					$owners[condition.owner][index] = false;
				}
			} else if ( 'checkbox' == um_get_field_type( $dom.parents('.um-field[data-key]') ) ) {
				if ( live_field_value && live_field_value.indexOf( condition.value ) >= 0 ) {
					$owners[condition.owner][index] = true;
				} else {
					$owners[condition.owner][index] = false;
				}
			} else {
				if ( live_field_value && live_field_value.indexOf( condition.value ) >= 0 && um_in_array( live_field_value, $owners_values[ condition.owner ] ) ) {
					$owners[condition.owner][index] = true;
				} else {
					$owners[condition.owner][index] = false;
				}
			}
		}

	}); // end foreach `conditions`
	jQuery.each($owners, function (index, field) {
		if (um_in_array(true, field)) {
			um_field_apply_action($dom, $owner_conditions[index], true);
		} else {
			um_field_apply_action($dom, $owner_conditions[index], false);
		}
	});
	$dom.trigger('um_fields_change');

}

/**
 * Apply condition's action
 * @param  object  $dom
 * @param  string  condition
 * @param  boolean is_true
 */
function um_field_apply_action($dom, condition, is_true) {
	var child_dom = jQuery('div.um-field[data-key="' + condition.owner + '"]');

	if (condition.action == 'show' && is_true /*&& child_dom.is(':hidden')*/) {
		child_dom.show();
		_show_in_ie( child_dom );
		um_field_restore_default_value(child_dom);
	}

	if (condition.action == 'show' && !is_true /*&& child_dom.is(':visible') */) {
		child_dom.hide();
		_hide_in_ie( child_dom );
	}

	if (condition.action == 'hide' && is_true  /*&& child_dom.is(':visible')*/) {
		child_dom.hide();
		_hide_in_ie( child_dom );
	}

	if (condition.action == 'hide' && !is_true /*&& child_dom.is(':hidden')*/) {
		child_dom.show();
		_show_in_ie( child_dom );
		um_field_restore_default_value( child_dom );

	}
	$dom.removeClass('um-field-has-changed');
}

/**
 * Restores default field value
 * @param  object $dom
 */
function um_field_restore_default_value( $dom ) {
	//um_field_default_values

	var type = um_get_field_type( $dom );
	var key = $dom.data('key');
	var field = um_field_default_values[key];
	switch ( type ) {

		case 'text':
		case 'number':
		case 'date':
		case 'textarea':
			$dom.find('input:text,input[type=number],textareas').val(field.value);
			break;

		case 'select':
			$dom.find('select').find('option').prop('selected', false);
			$dom.find('select').val(field.value);
			$dom.find('select').trigger('change');
			break;

		case 'multiselect':
			$dom.find('select').find('option').prop('selected', false);
			jQuery.each(field.value, function (i, value) {
				$dom.find('select').find('option[value="' + value + '"]').attr('selected', true);
			});
			$dom.find('select').trigger('change');
			break;

		case 'checkbox':

			if ( $dom.find('input[type=checkbox]:checked').length >= 1 ) {

				$dom.find('input[type=checkbox]:checked').removeAttr('checked');
				$dom.find('span.um-field-checkbox-state i').removeClass('um-icon-android-checkbox-outline');
				$dom.find('span.um-field-checkbox-state i').addClass('um-icon-android-checkbox-outline-blank');
				$dom.find('.um-field-checkbox.active').removeClass('active');

				if (jQuery.isArray(field.value)) {
					jQuery.each(field.value, function (i, value) {
						var cbox_elem = $dom.find('input[type=checkbox][value="' + value + '"]');
						cbox_elem.attr('checked', true);
						cbox_elem.closest('.um-field-checkbox').find('i').removeClass('um-icon-android-checkbox-outline-blank');
						cbox_elem.closest('.um-field-checkbox').find('i').addClass('um-icon-android-checkbox-outline');
						cbox_elem.closest('.um-field-checkbox').addClass('active');
					});
				} else {
					var cbox_elem = $dom.find('input[type=checkbox][value="' + field.value + '"]');
					cbox_elem.attr('checked', true);
					cbox_elem.closest('.um-field-checkbox').find('i').removeClass('um-icon-android-checkbox-outline-blank');
					cbox_elem.closest('.um-field-checkbox').find('i').addClass('um-icon-android-checkbox-outline');
					cbox_elem.closest('.um-field-checkbox').addClass('active');
				}

			}

			break;
		case 'radio':

			if ( $dom.find('input[type=radio]:checked').length >= 1 ) {

				setTimeout(function () {

					$dom.find('input[type=radio]:checked').removeAttr('checked');

					$dom.find('span.um-field-radio-state i').removeClass('um-icon-android-radio-button-on');
					$dom.find('span.um-field-radio-state i').addClass('um-icon-android-radio-button-off');
					$dom.find('.um-field-radio.active').removeClass('active');

					var radio_elem = $dom.find("input[type=radio][value='" + field.value + "']");
					radio_elem.attr('checked', true);
					radio_elem.closest('.um-field-radio').find('i').removeClass('um-icon-android-radio-button-off');
					radio_elem.closest('.um-field-radio').find('i').addClass('um-icon-android-radio-button-on');
					radio_elem.closest('.um-field-radio').addClass('active');

				}, 100);
			}

			break;


	} // end switch type


	if ( ! $dom.hasClass( 'um-field-has-changed' ) ) {
		var me = um_get_field_element( $dom );

		if ( type == 'radio' || type == 'checkbox' ) {
			me = me.find( ':checked' );
		}

		if ( me ) {
			me.trigger( 'change' );
			$dom.addClass( 'um-field-has-changed' );
		}

		/*
		maybe future fix
		if ( me ) {
			if ( type == 'radio' || type == 'checkbox' ) {
				me.each( function() {
				   if ( jQuery(this).is(':checked') ) {
					   jQuery(this).trigger('change');
				   }
				});
			} else {
				me.trigger( 'change' );
			}

			$dom.addClass( 'um-field-has-changed' );
		}*/
	}
}

/**
 * Hides sibling/child field when parent field is hidden
 */
function um_field_hide_siblings() {

	jQuery.each(um_field_conditions, function (index, conditions) {
		if (jQuery('.um-field[data-key="' + index + '"]:hidden').length >= 1 || jQuery('.um-field[data-key="' + index + '"]').css('display') == 'none') {
			jQuery.each(conditions, function (key, condition) {
				jQuery('.um-field[data-key="' + condition.owner + '"]').hide();
			});
		}

	});

}

/**
 * Hides div for IE browser
 * @param  object $dom
 */
function _hide_in_ie( $dom ){
	if ( typeof( jQuery.browser ) != 'undefined' && jQuery.browser.msie ) {
		$dom.css({"visibility":"hidden"});
	}
}

/**
 * Shows div for IE browser
 * @param  object $dom
 */
function _show_in_ie( $dom ){
	if ( typeof( jQuery.browser ) != 'undefined' && jQuery.browser.msie ) {
		$dom.css({"visibility":"visible"});
	}
}

/**
 * UM Conditional fields Init
 */
function um_init_field_conditions() {
	var arr_field_keys = [];

	jQuery( '.um-field[data-key]' ).each( function() {

		var key = jQuery(this).data( 'key' );

		arr_field_keys.push( key );

		var parse_attrs = {};
		jQuery.each( jQuery(this)[0].attributes, function ( index, attribute ) {
			if ( attribute.name.indexOf( 'data-cond' ) != -1 ) {
				// replace "data-cond-"
				var cond_field_id_and_attr = attribute.name.slice( 10 );
				// return "n"
				var cond_field_id = cond_field_id_and_attr.substring( 1, 0 );
				//replace "n-"
				var cond_field_attr = cond_field_id_and_attr.slice( 2 );

				if ( typeof parse_attrs[cond_field_id] == 'undefined' )
					parse_attrs[cond_field_id] = {};

				parse_attrs[cond_field_id][cond_field_attr] = attribute.value;
			}
		});

		jQuery.each( parse_attrs, function ( ii, dd ) {
			var obj = {'field' :{
					owner: key,
					action: dd.action,
					parent: dd.field,
					operator: dd.operator,
					value: dd.value,
					condition: {
						owner: key,
						action: dd.action,
						operator: dd.operator,
						value: dd.value
					}
				}};

			arr_all_conditions.push(obj);
		});

		um_field_default_values[jQuery(this).data('key')] = um_get_field_default_value( jQuery(this) );
	});

	jQuery.each( arr_field_keys, function ( i, field_key ) {
		um_field_conditions[field_key] = um_get_field_children( field_key );
	});

	jQuery( '.um-field[data-key]:visible' ).each( function() {
		var $wrap_dom = jQuery(this);
		var me = um_get_field_element( $wrap_dom );
		if ( typeof me.trigger !== 'undefined' ) {
			me.trigger( 'change' );
		}
	});

}

jQuery(window).load(function() {

	if( jQuery('.new-cond-form').length > 0 ){
		console.log('NEW');
		jQuery('.um-form').each(function () {
			var all_conds = JSON.parse( JSON.stringify( jQuery(this).find('.condition-data').data('conds') ) );
			var form_id = jQuery(this).find('.condition-data').attr('value');

			if( jQuery('.um-field').length > 0 ) {
				var from_top_default;
				condition_fields(all_conds, form_id);
				check_parent(all_conds, form_id);
				// jQuery('.um-field input').on('change keyup', function () {
				// 	console.log('111')
				// });
				jQuery('.um-field input, .um-field textarea').on('change keyup', function () {

					condition_fields(all_conds, form_id);
					check_parent(all_conds, form_id);
				});

				jQuery('.um-field select').on('change', function () {
					condition_fields(all_conds, form_id);
					check_parent(all_conds, form_id);
				});

				jQuery(document).on('click', '.um-modal .um-finish-upload', function () {
					condition_fields(all_conds, form_id);
					check_parent(all_conds, form_id);
				});

				jQuery(document).on('click', '.um-field-area .cancel', function () {
					setTimeout(function () {
						condition_fields(all_conds, form_id);
						check_parent(all_conds, form_id);
					})
				});
			}
		});
	} else {
		if (jQuery('.um-field').length > 0) {
			console.log('OLD');
			jQuery(document).on('change', '.um-field select, .um-field input[type="radio"], .um-field input[type="checkbox"]', function () {
				var me = jQuery(this);
				um_apply_conditions(me, false);
			});

			jQuery(document).on('input change', '.um-field input[type="text"]', function () {
				var me = jQuery(this);
				um_apply_conditions(me, false);
			});

			jQuery(document).on('input change', '.um-field input[type="number"]', function () {
				var me = jQuery(this);
				um_apply_conditions(me, false);
			});

			jQuery(document).on('input change', '.um-field input[type="password"]', function () {
				var me = jQuery(this);
				um_apply_conditions(me, false);
			});

			jQuery(document).on('um_fields_change', function () {
				um_field_hide_siblings();
				um_field_hide_siblings(); // dupes, issue with false field wrapper's visiblity validations. requires optimization.
			});

			um_init_field_conditions();
		}
	}

});