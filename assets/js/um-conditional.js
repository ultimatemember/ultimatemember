function condition_fields() {
	var all_conds = JSON.parse( JSON.stringify(jQuery('.condition-data').data( 'conds' )) );

	jQuery.each( all_conds, function( metakey ) {
		var first_group = 0,
			state_array = [],
			count = state_array.length,
			state = 'show';

		jQuery.each( all_conds[ metakey ], function() {
			var action = this[0],
				field = this[1],
				op = this[2],
				val = this[3],
				group = this[5],
				depend_field;

			var input = jQuery('.um-field[data-key="' + field + '"] .um-field-area input'),
				select = jQuery('.um-field[data-key="' + field + '"] .um-field-area>select'),
				textarea = jQuery('.um-field[data-key="' + field + '"] .um-field-area>textarea'),
				content_block = jQuery('.um-field[data-key="' + field + '"] .um-field-block'),
				depend_arr = [],
				output_field = jQuery('.um-field[data-key="' + field + '"] .um-field-value');

			if (input.length > 0 && select.length === 0) {

				if (input.is(':checkbox')) {
					var checked = jQuery('.um-field[data-key="' + field + '"] input:checked');
					checked.each(function () {
						var checked_vals = jQuery(this).val();
						depend_arr.push(checked_vals);
					});

				} else if (input.is(':radio')) {
					depend_field = jQuery('.um-field[data-key="' + field + '"] input:checked').val();
				} else if (input.is(':hidden')) {
					depend_field = jQuery('.um-field[data-key="' + field + '"] input').val();
					if (depend_field === 'empty_file') {
						depend_field = '';
					}

				} else {
					depend_field = input.val();
				}

			} else if (select.length > 0) {

				if (jQuery.inArray(val, select.val()) > 0) {
					depend_field = val;
				} else {
					depend_field = select.val()
				}

			} else if (textarea.length > 0) {

				depend_field = textarea.val();

			} else if (content_block.length > 0) {

				depend_field = content_block.text();

			} else if (output_field.length > 0) {
				if (output_field.hasClass('um-field-value-multiselect') || output_field.hasClass('um-field-value-checkbox')) {
					depend_arr = output_field.text().split(', ');

				} else {
					depend_field = output_field.text();
				}


			}

			// If another group rule
			if (parseInt(group) !== first_group) {

				if (action === 'show') {

					switch (op) {
						case 'equals to':

							if (depend_arr.length > 0) {
								jQuery.each(depend_arr, function () {
									if (this == val) {
										state = 'show';
									} else {
										state = 'hide';
									}
								});
							} else {
								if (depend_field == val) {
									state = 'show';
								} else {
									state = 'hide';
								}
							}

							break;

						case 'not equals':

							if (depend_arr.length > 0) {
								jQuery.each(depend_arr, function () {
									if (this != val) {
										state = 'show';
										return false;
									} else {
										state = 'hide';
									}
								});
							} else {
								if (depend_field != val) {
									state = 'show';
								} else {
									state = 'hide';
								}
							}

							break;

						case 'empty':

							if (!depend_field || depend_field === '') {
								state = 'show';
							} else {
								state = 'hide';
							}

							break;

						case 'not empty':

							if (depend_field && depend_field !== '') {
								state = 'show';
							} else {
								state = 'hide';
							}

							break;

						case 'greater than':

							if (depend_arr.length > 0) {
								jQuery.each(depend_arr, function () {
									if (jQuery.isNumeric(val) && jQuery.isNumeric(this)) {
										if (parseInt(val) < parseInt(this)) {
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
								if (jQuery.isNumeric(val) && jQuery.isNumeric(depend_field)) {
									if (parseInt(val) < parseInt(depend_field)) {
										state = 'show'
									} else {
										state = 'hide'
									}
								} else {
									state = 'hide';
								}
							}

							break;

						case 'less than':
							if (depend_arr.length > 0) {
								jQuery.each(depend_arr, function () {
									if (jQuery.isNumeric(val) && jQuery.isNumeric(this)) {
										if (parseInt(val) > parseInt(this)) {
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
								if (jQuery.isNumeric(val) && jQuery.isNumeric(depend_field)) {
									if (parseInt(val) > parseInt(depend_field)) {
										state = 'show'
									} else {
										state = 'hide'
									}
								} else {
									state = 'hide';
								}
							}

							break;

						case 'contains':
							if (depend_arr.length > 0) {
								jQuery.each(depend_arr, function () {
									if (this && this.search(val) >= 0) {
										state = 'show';
										return false;
									} else {
										state = 'hide';
									}
								});
							} else {
								if (depend_field && depend_field.search(val) >= 0) {
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
							if (depend_arr.length > 0) {

							} else {
								if (depend_field == val) {
									state = 'hide';
								} else {
									state = 'show';
								}
							}

							break;

						case 'not equals':

							if (depend_arr.length > 0) {

							} else {
								if (depend_field != val) {
									state = 'hide';
								} else {
									state = 'show';
								}
							}

							break;

						case 'empty':

							if (!depend_field || depend_field === '') {
								state = 'hide';
							} else {
								state = 'show';
							}

							break;

						case 'not empty':

							if (depend_field && depend_field !== '') {
								state = 'hide';
							} else {
								state = 'show';
							}

							break;

						case 'greater than':

							if (depend_arr.length > 0) {
								jQuery.each(depend_arr, function () {
									if (jQuery.isNumeric(val) && jQuery.isNumeric(this)) {
										if (parseInt(val) < parseInt(this)) {
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
								if (jQuery.isNumeric(val) && jQuery.isNumeric(depend_field)) {
									if (parseInt(val) < parseInt(depend_field)) {
										state = 'hide'
									} else {
										state = 'show'
									}
								} else {
									state = 'show';
								}
							}

							break;

						case 'less than':

							if (depend_arr.length > 0) {
								jQuery.each(depend_arr, function () {
									if (jQuery.isNumeric(val) && jQuery.isNumeric(this)) {
										if (parseInt(val) > parseInt(this)) {
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
								if (jQuery.isNumeric(val) && jQuery.isNumeric(depend_field)) {
									if (parseInt(val) > parseInt(depend_field)) {
										state = 'hide'
									} else {
										state = 'show'
									}
								} else {
									state = 'show';
								}
							}

							break;

						case 'contains':
							if (depend_arr.length > 0) {
								jQuery.each(depend_arr, function () {
									if (this && this.search(val) >= 0) {
										state = 'hide';
										return false;
									} else {
										state = 'show';
									}
								});
							} else {
								if (depend_field && depend_field.search(val) >= 0) {
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

				if (action === 'show') {

					switch (op) {
						case 'equals to':

							if (depend_arr.length > 0) {
								jQuery.each(depend_arr, function () {
									if (this == val) {
										state = 'show';
										return false;
									} else {
										state = 'hide';
									}
								});
							} else {
								if (depend_field == val) {
									state = 'show';
								} else {
									state = 'hide';
								}
							}

							break;

						case 'not equals':

							if (depend_arr.length > 0) {
								jQuery.each(depend_arr, function () {
									if (this != val) {
										state = 'show';
										return false;
									} else {
										state = 'hide';
									}
								});
							} else {
								if (depend_field != val) {
									state = 'show';
								} else {
									state = 'hide';
								}
							}

							break;

						case 'empty':

							if (!depend_field || depend_field === '') {
								state = 'show';
							} else {
								state = 'hide';
							}

							break;

						case 'not empty':

							if (depend_field && depend_field !== '') {
								state = 'show';
							} else {
								state = 'hide';
							}

							break;

						case 'greater than':

							if (depend_arr.length > 0) {
								jQuery.each(depend_arr, function () {

									if (jQuery.isNumeric(val) && jQuery.isNumeric(this)) {
										if (parseInt(val) < parseInt(this)) {
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

								if (jQuery.isNumeric(val) && jQuery.isNumeric(depend_field)) {
									if (parseInt(val) < parseInt(depend_field)) {
										state = 'show'
									} else {
										state = 'hide'
									}
								} else {
									state = 'hide';
								}
							}

							break;

						case 'less than':

							if (depend_arr.length > 0) {
								jQuery.each(depend_arr, function () {
									if (jQuery.isNumeric(val) && jQuery.isNumeric(this)) {
										if (parseInt(val) > parseInt(this)) {
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
								if (jQuery.isNumeric(val) && jQuery.isNumeric(depend_field)) {
									if (parseInt(val) > parseInt(depend_field)) {
										state = 'show'
									} else {
										state = 'hide'
									}
								} else {
									state = 'hide';
								}
							}

							break;

						case 'contains':

							if (depend_arr.length > 0) {
								jQuery.each(depend_arr, function () {
									if (this && this.search(val) >= 0) {
										state = 'show';
										return false;
									} else {
										state = 'hide';
									}
								});
							} else {
								if (depend_field && depend_field.search(val) >= 0) {
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

							if (depend_arr.length > 0) {

							} else {
								if (depend_field == val) {
									state = 'hide';
								} else {
									state = 'show';
								}
							}

							break;

						case 'not equals':

							if (depend_arr.length > 0) {

							} else {
								if (depend_field != val) {
									state = 'hide';
								} else {
									state = 'show';
								}
							}

							break;

						case 'empty':

							if (!depend_field || depend_field === '') {
								state = 'hide';
							} else {
								state = 'show';
							}

							break;

						case 'not empty':

							if (depend_field && depend_field !== '') {
								state = 'hide';
							} else {
								state = 'show';
							}

							break;

						case 'greater than':

							if (depend_arr.length > 0) {
								jQuery.each(depend_arr, function () {
									if (jQuery.isNumeric(val) && jQuery.isNumeric(this)) {
										if (parseInt(val) < parseInt(this)) {
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
								if (jQuery.isNumeric(val) && jQuery.isNumeric(depend_field)) {
									if (parseInt(val) < parseInt(depend_field)) {
										state = 'hide'
									} else {
										state = 'show'
									}
								} else {
									state = 'show';
								}
							}

							break;

						case 'less than':

							if (depend_arr.length > 0) {
								jQuery.each(depend_arr, function () {
									if (jQuery.isNumeric(val) && jQuery.isNumeric(this)) {
										if (parseInt(val) > parseInt(this)) {
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
								if (jQuery.isNumeric(val) && jQuery.isNumeric(depend_field)) {

									if (parseInt(val) > parseInt(depend_field)) {
										state = 'hide'
									} else {
										state = 'show'
									}
								} else {
									state = 'show';
								}
							}

							break;

						case 'contains':
							if (depend_arr.length > 0) {
								jQuery.each(depend_arr, function () {
									if (this && this.search(val) >= 0) {
										state = 'hide';
										return false;
									} else {
										state = 'show';
									}
								});
							} else {
								if (depend_field && depend_field.search(val) >= 0) {
									state = 'hide';
								} else {
									state = 'show';
								}
							}

							break;

					}

				}

				if (state_array[count]) {
					if (state_array[count] == 'show' && state === 'show') {
						state_array[count] = 'show';
					} else {
						state_array[count] = 'hide';
					}
				} else {
					if (state === 'show') {
						state_array[count] = 'show';
					} else {
						state_array[count] = 'hide';
					}
				}

			}

		});

		var field = jQuery('.um-field[data-key="' + metakey + '"]');
		if (jQuery.inArray('show', state_array) < 0) {
			field.hide();
			field.find('input, textarea').attr('disabled', 'disabled').attr('readonly','readonly');
		} else {
			field.show();
			field.find('input, textarea').removeAttr('disabled').removeAttr('readonly');
		}

	});
}

function check_parent() {
	var all_conds = JSON.parse( JSON.stringify(jQuery('.condition-data').data('conds')) );

	jQuery.each( all_conds, function( metakey ) {
		jQuery.each( all_conds[ metakey ], function() {
			var field = this[1];
			var check_field = jQuery('.um-field[data-key="' + field + '"]').is(':visible');
			var check = jQuery('.um-field[data-key="' + metakey + '"]');

			if( check_field === false ) {
				check.hide();
				check.find('input, textarea').attr('disabled', 'disabled').attr('readonly','readonly');
				return false;
			}

		});
	});
}

jQuery(document).ready(function() {

	condition_fields();
	check_parent();

	jQuery('.um-field input, .um-field textarea').on('change keyup', function () {
		condition_fields();
		check_parent();
	});

	jQuery('.um-field select').on('change', function () {
		condition_fields();
		check_parent();
	});

	jQuery(document).on('click','.um-modal .um-finish-upload', function () {
		condition_fields();
		check_parent();
	});


	jQuery(document).on('click','.um-field-area .cancel', function () {
		setTimeout(function () {
			condition_fields();
			check_parent();
		})
	});
});