
function condition_fields(template) {
	var all_conds = jQuery('.condition-data').attr('data-conds');
	var array = JSON.parse(all_conds);

	jQuery.each(array, function () {
		var first_group = 0,
			state_array = [],
			count = state_array.length,
			state = 'show',
			metakey = this.metakey;

		jQuery.each(this.conditions, function () {
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

			if (parseInt(group) !== first_group) {

				if (action === 'show') {

					switch (op) {
						case 'equals to':
							console.log('sss')
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
				first_group++;
				state_array.push(state);
			} else {

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
									state = 'not_show';
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
										state = 'not_show';
									}
								});
							} else {
								if (depend_field != val) {
									state = 'show';
								} else {
									state = 'not_show';
								}
							}

							break;

						case 'empty':

							if (!depend_field || depend_field === '') {
								state = 'show';
							} else {
								state = 'not_show';
							}

							break;

						case 'not empty':

							if (depend_field && depend_field !== '') {
								state = 'show';
							} else {
								state = 'not_show';
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
											state = 'not_show';
										}
									} else {
										state = 'not_show';
									}
								});
							} else {

								if (jQuery.isNumeric(val) && jQuery.isNumeric(depend_field)) {
									if (parseInt(val) < parseInt(depend_field)) {
										state = 'show'
									} else {
										state = 'not_show'
									}
								} else {
									state = 'not_show';
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
											state = 'not_show';
										}
									} else {
										state = 'not_show';
									}
								});
							} else {
								if (jQuery.isNumeric(val) && jQuery.isNumeric(depend_field)) {
									if (parseInt(val) > parseInt(depend_field)) {
										state = 'show'
									} else {
										state = 'not_show'
									}
								} else {
									state = 'not_show';
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
										state = 'not_show';
									}
								});
							} else {
								if (depend_field && depend_field.search(val) >= 0) {
									state = 'show';
								} else {
									state = 'not_show';
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
									state = 'not_hide';
								}
							}

							break;

						case 'not equals':

							if (depend_arr.length > 0) {

							} else {
								if (depend_field != val) {
									state = 'hide';
								} else {
									state = 'not_hide';
								}
							}

							break;

						case 'empty':

							if (!depend_field || depend_field === '') {
								state = 'hide';
							} else {
								state = 'not_hide';
							}

							break;

						case 'not empty':

							if (depend_field && depend_field !== '') {
								state = 'hide';
							} else {
								state = 'not_hide';
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
											state = 'not_hide';
										}
									} else {
										state = 'not_hide';
									}
								});
							} else {
								if (jQuery.isNumeric(val) && jQuery.isNumeric(depend_field)) {
									if (parseInt(val) < parseInt(depend_field)) {
										state = 'hide'
									} else {
										state = 'not_hide'
									}
								} else {
									state = 'not_hide';
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
											state = 'not_hide';
										}
									} else {
										state = 'not_hide';
									}
								});
							} else {
								if (jQuery.isNumeric(val) && jQuery.isNumeric(depend_field)) {

									if (parseInt(val) > parseInt(depend_field)) {
										state = 'hide'
									} else {
										state = 'not_hide'
									}
								} else {
									state = 'not_hide';
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
										state = 'not_hide';
									}
								});
							} else {
								if (depend_field && depend_field.search(val) >= 0) {
									state = 'hide';
								} else {
									state = 'not_hide';
								}
							}

							break;

					}

				}

				if (state_array[count]) {
					if (state_array[count] === 'show' || state_array[count] === 'not_hide') {
						if (state === 'show' || state === 'not_hide') {
							state_array[count] = 'show';
						} else {
							state_array[count] = 'hide';
						}
					} else {
						state_array[count] = 'hide';
					}
				} else {
					if (state === 'show' || state === 'not_hide') {
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
		} else {
			field.show();
		}

	});
}

jQuery(document).ready(function () {
	var template = jQuery('.um-form form').clone();

	condition_fields(template);

	jQuery('.um-field input, .um-field textarea').on('change keyup', function () {
		condition_fields(template);
	});
	jQuery(document).on('click','.um-modal .um-finish-upload', function () {
		condition_fields(template);
	});
	jQuery(document).on('click','.um-field-area .cancel', function () {
		setTimeout(function () {
			condition_fields(template);
		})
	});
	jQuery('.um-field select').on('change', function () {
		condition_fields(template);
	});

});