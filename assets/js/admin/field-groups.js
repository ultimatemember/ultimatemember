// wp-admin scripts that must be enqueued on Fields Groups list table and individual Field Group page

if ( 'undefined' === typeof ( um_admin_field_groups_data ) ) {
	um_admin_field_groups_data = {};
}

if ( typeof ( window.UM ) !== 'object' ) {
	window.UM = {};
}

if ( typeof ( UM.fields_groups ) !== 'object' ) {
	UM.fields_groups = {};
}

UM.fields_groups = {
	tabs: {
		setActive: function( tab_obj, $ ) {
			let tab = tab_obj.data('tab');

			let tabs = tab_obj.parents('.um-edit-field-tabs');
			tabs.find('div[data-tab]').removeClass('current');
			tab_obj.addClass('current');

			let contents = tab_obj.parents('.um-edit-field-tabs').siblings('.um-edit-field-tabs-content');
			contents.find('div[data-tab]').removeClass('current');
			contents.find('div[data-tab="' + tab + '"]').addClass('current');
		}
	},
	sortable: {
		init: function ($) {
			$('.um-field-groups-fields-wrapper').sortable({
				items: '.um-field-groups-field-row',
				connectWith: 'um-field-groups-fields-wrapper',
				placeholder: 'um-field-groups-field-placeholder',
				forcePlaceholderSize:true,
				axis: 'y',
				cursor: 'move',
				handle: '.um-field-groups-field-move-link',
				update: function(event, ui){
					$('.um-field-groups-field-row').each( function(i) {
						let index = i * 1 + 1;
						$(this).find('.um-field-groups-field-order').val(index);
						$(this).find('.um-field-groups-field-row-header .um-field-groups-field-move-link').html(index);
					});
				}
			}).on('sortupdate',function(){
				$('.um-field-groups-field-row').each( function(i) {
					let index = i * 1 + 1;
					$(this).find('.um-field-groups-field-order').val(index);
					$(this).find('.um-field-groups-field-row-header .um-field-groups-field-move-link').html(index);
				});
			});
		},
		destroy: function ($) {
			$('.um-field-groups-fields-wrapper').sortable('destroy');
		},
		reInit: function ($) {
			UM.fields_groups.sortable.destroy($);
			UM.fields_groups.sortable.init($);
			$('.um-field-groups-fields-wrapper').trigger('sortupdate');
		}
	},
	field: {
		newIndex: 0,
		add: function ( $ ) {
			let $wrapper = $('.um-field-groups-fields-wrapper');
			let $cloned = $('.um-field-groups-field-row-template').clone().addClass('um-field-groups-field-row').removeClass('um-field-groups-field-row-template');

			UM.fields_groups.field.newIndex ++;
			let newIndex = UM.fields_groups.field.newIndex;
			$cloned.data( 'field', 'new_' + newIndex ).attr( 'data-field', 'new_' + newIndex );

			let newOrderIndex = $wrapper.find( '.um-field-groups-field-row' ).length + 1;
			$cloned.find('.um-field-groups-field-move-link').html( newOrderIndex );

			let fieldID = $cloned.find('.um-field-groups-field-id');
			fieldID.removeAttr('disabled').prop('disabled', false);
			let fieldIDName = fieldID.attr('name');
			if ( 'undefined' !== typeof fieldIDName ) {
				let newName = fieldIDName.replace( '\{index\}', newIndex );
				fieldID.attr('name', newName);
			}

			let fieldOrder = $cloned.find('.um-field-groups-field-order');
			fieldOrder.val( newOrderIndex ).removeAttr('disabled').prop('disabled', false);
			let fieldOrderName = fieldOrder.attr('name');
			if ( 'undefined' !== typeof fieldOrderName ) {
				let newName = fieldOrderName.replace( '\{index\}', newIndex );
				fieldOrder.attr('name', newName);
			}

			$cloned.find('.um-form-table').each( function(i) {
				let extraClass = $(this).data('extra-class');
				if ( 'undefined' !== typeof extraClass ) {
					extraClass = extraClass.replace( '\{index\}', newIndex );
					if ( extraClass ) {
						$(this).removeAttr( 'class' ).addClass( 'form-table um-form-table ' + extraClass );
					}
				}
			});

			$cloned.find('.um-forms-line').each( function(i) {
				let currentPrefix = $(this).data('prefix');
				if ( 'undefined' !== typeof currentPrefix ) {
					let newPrefix = currentPrefix.replace( 'new_index', 'new_' + newIndex );
					$(this).data('prefix',newPrefix).attr('data-prefix', newPrefix);
				}

				let label = $(this).find('label');
				let labelFor = label.attr('for');
				if ( 'undefined' !== typeof labelFor ) {
					let newFor = labelFor.replace( 'new_index', 'new_' + newIndex );
					label.attr('for', newFor);
				}

				let field = $(this).find('.um-forms-field');

				let fieldID = field.attr('id');
				if ( 'undefined' !== typeof fieldID ) {
					let newID = fieldID.replace( 'new_index', 'new_' + newIndex );
					field.attr('id', newID);
				}

				let fieldName = field.attr('name');
				if ( 'undefined' !== typeof fieldName ) {
					let newName = fieldName.replace( '\{index\}', newIndex );
					field.attr('name', newName);
				}

				field.removeAttr('disabled').prop('disabled', false);

				let fieldHidden = field.siblings('input[type="hidden"]');
				if ( fieldHidden.length ) {
					let fieldID = fieldHidden.attr('id');
					if ( 'undefined' !== typeof fieldID ) {
						let newID = fieldID.replace( 'new_index', 'new_' + newIndex );
						fieldHidden.attr('id', newID);
					}

					let fieldName = fieldHidden.attr('name');
					if ( 'undefined' !== typeof fieldName ) {
						let newName = fieldName.replace( '\{index\}', newIndex );
						fieldHidden.attr('name', newName);
					}

					fieldHidden.removeAttr('disabled').prop('disabled', false);
				}
			});

			let row_fieldID = $cloned.data('field');
			$cloned.find('.um-conditional-rule-field-col .um-conditional-rule-setting').each( function() {
				$(this).find('option').each( function(){
					if ( $(this).is(':selected') || '' === $(this).attr('value') ) {
						return;
					}
					$(this).remove();
				});

				let selectField = $(this);
				let selectFieldVal = selectField.val();
				$.each( UM.fields_groups.field.conditional.fieldsList, function ( id, field ) {
					if ( ! selectField.find( 'option[value="' + id + '"]' ).length && row_fieldID != id ) {
						selectField.find( 'option[value=""]' ).after( '<option value="' + id + '">' + field.title + '</option>' );
					}
				});

				let $options = selectField.find('option').detach();
				$options.sort(function(a, b) {
					if ( '' === $(a).val() ) {
						return 0;
					}
					if ($(a).text() > $(b).text()) return 1;
					if ($(a).text() < $(b).text()) return -1;
					return 0;
				});
				selectField.append($options).val( selectFieldVal );
			} );

			$wrapper.append( $cloned );

			if ( $wrapper.find('.um-field-groups-field-row').length > 1 ) {
				UM.fields_groups.sortable.reInit($);
			} else {
				$wrapper.removeClass('hidden');
				$wrapper.siblings('.um-field-groups-builder-header').removeClass('hidden');
				$wrapper.siblings('.um-field-groups-fields-wrapper-empty').addClass('hidden');
				UM.fields_groups.sortable.init($);
			}

			UM.fields_groups.field.conditional.prepareFieldsList($);

			// let group_id = $('#fields_group_draft_id').val();
			// if ( '' === group_id ) {
			// 	group_id = $('#fields_group_id').val();
			// }
			//
			// wp.ajax.send( 'um_fields_groups_add_field', {
			// 	data: {
			// 		group_id: group_id,
			// 		nonce: um_admin_scripts.nonce
			// 	},
			// 	beforeSend: function() {
			// 		// $modal.addClass('loading');
			// 	},
			// 	success: function( data ) {
			// 		console.log( data.field_id );
			// 		console.log( $cloned );
			//
			//
			// 	},
			// 	error: function( data ) {
			// 		// $modal.removeClass('loading');
			// 		console.error( data );
			// 	}
			// });
		},
		toggleEdit: function ( row, $ ) {
			if ( row.hasClass('um-field-row-edit-mode') ) {
				UM.fields_groups.field.hideEdit(row,$);
			} else {
				UM.fields_groups.field.showEdit(row,$);
			}
		},
		showEdit: function ( row, $ ) {
			row.addClass('um-field-row-edit-mode');
		},
		hideEdit: function ( row, $ ) {
			row.removeClass('um-field-row-edit-mode');
		},
		duplicate: function ( obj, $ ) {
			let $cloned = obj.parents('.um-field-groups-field-row').clone();
			obj.parents('.um-field-groups-field-row').after( $cloned );
			UM.fields_groups.sortable.reInit($);
			UM.fields_groups.field.conditional.prepareFieldsList($);
		},
		delete: function ( obj, $ ) {
			let $wrapper = obj.parents('.um-field-groups-fields-wrapper');
			obj.parents('.um-field-groups-field-row').remove();

			if ( ! $wrapper.find('.um-field-groups-field-row').length ) {
				$wrapper.addClass('hidden');
				$wrapper.siblings('.um-field-groups-builder-header').addClass('hidden');
				$wrapper.siblings('.um-field-groups-fields-wrapper-empty').removeClass('hidden');
				UM.fields_groups.sortable.destroy($);
			} else {
				UM.fields_groups.sortable.reInit($);
			}
			UM.fields_groups.field.conditional.prepareFieldsList($);
		},
		conditional: {
			fieldsList: {}, /* format id:{title,type}*/
			prepareFieldsList: function($) {
				$('.um-field-groups-fields-wrapper').find('.um-field-groups-field-row').each( function() {
					let id = $(this).data('field'); // id
					let type = $(this).find('.um-field-groups-field-type-select').val(); // type
					let title = $(this).find('.um-field-groups-field-title-input').val(); // title

					if ( um_admin_field_groups_data.field_types[type].conditional_rules.length ) {
						let rules = {};
						um_admin_field_groups_data.field_types[type].conditional_rules.forEach((element) => {
							rules[element] = um_admin_field_groups_data.conditional_rules[ element ];
						});

						let valueOptions = null;
						if ( 'choice' === um_admin_field_groups_data.field_types[type].category ) {
							valueOptions = {};
							// @todo get options from field settings
							//$(this).find('.um-field-groups-field-type-select').val()
						}

						UM.fields_groups.field.conditional.fieldsList[id] = {
							'type': type,
							'title': title,
							'conditions': rules,
							'valueOptions': valueOptions
						};
					}
				});
			},
			reset: function(resetRulesBtn,$) {
				let conditionsTab = resetRulesBtn.parents('.um-edit-field-tabs-content > div[data-tab="conditional"]');
				conditionsTab.find('.um-conditional-rules-wrapper .um-conditional-remove-rule').each(function(i) {
					$(this).trigger('click');
				});
			},
			addRulesGroup: function(rulesWrapper,$) {
				let $cloned = rulesWrapper.siblings('.um-conditional-rules-group-template').clone().addClass('um-conditional-rules-group').removeClass('um-conditional-rules-group-template');

				let newGroupIndex = rulesWrapper.find('.um-conditional-rules-group').length;
				$cloned.data( 'group-index', newGroupIndex ).attr( 'data-group-index', newGroupIndex );
				$cloned.find('.um-conditional-rule-setting').each( function(i) {
					let fieldName = $(this).attr('name');
					if ( 'undefined' !== typeof fieldName ) {
						let newName = fieldName.replace( '\{group_key\}', newGroupIndex );
						$(this).attr('name', newName);
						if ( $(this).parents('.um-conditional-rule-field-col').length ) {
							$(this).prop('disabled', false).removeAttr('disabled');
						}
					}
				});

				rulesWrapper.find('.um-conditional-rules-wrapper-bottom').before( $cloned );
				UM.fields_groups.field.conditional.showReset(rulesWrapper,$);
			},
			addRule: function(currentRuleRow,$) {
				let rulesWrapper = currentRuleRow.parents('.um-conditional-rules-wrapper');
				let $cloned = rulesWrapper.siblings('.um-conditional-rule-row-template').clone().addClass('um-conditional-rule-row').removeClass('um-conditional-rule-row-template');

				let newGroupIndex = currentRuleRow.parents('.um-conditional-rules-group').data('group-index');
				let newRuleIndex = currentRuleRow.parents('.um-conditional-rules-group').find('.um-conditional-rule-row').length;
				$cloned.find('.um-conditional-rule-setting').each( function(i) {
					let fieldName = $(this).attr('name');
					if ( 'undefined' !== typeof fieldName ) {
						let newName = fieldName.replace( '\{group_key\}', newGroupIndex );
						newName = newName.replace( '\{row_key\}', newRuleIndex );
						$(this).attr('name', newName);
						if ( $(this).parents('.um-conditional-rule-field-col').length ) {
							$(this).prop('disabled', false).removeAttr('disabled');
						}
					}
				});

				currentRuleRow.after( $cloned );

				UM.fields_groups.field.conditional.changeInputIndex(rulesWrapper,$);
				UM.fields_groups.field.conditional.showReset(rulesWrapper,$);
			},
			removeRule: function(currentRuleRow,$) {
				let rulesWrapper = currentRuleRow.parents('.um-conditional-rules-wrapper');
				let rulesRows    = rulesWrapper.find( '.um-conditional-rule-row' );

				// don't remove latest rule row, just flush the rules inside
				if ( rulesRows.length === 1 ) {
					rulesRows.find('.um-conditional-rule-field-col select').val('');
					rulesRows.find('.um-conditional-rule-condition-col select').val('');
					rulesRows.find('.um-conditional-rule-value-col select,.um-conditional-rule-value-col input').val('');
					UM.fields_groups.field.conditional.hideReset(rulesWrapper,$);
					return;
				}

				let rulesGroup = currentRuleRow.parents('.um-conditional-rules-group');
				if ( rulesGroup.find( '.um-conditional-rule-row' ).length === 1 ) {
					rulesGroup.remove();
				} else {
					currentRuleRow.remove();
				}

				UM.fields_groups.field.conditional.changeInputIndex(rulesWrapper,$);
			},
			changeInputIndex: function(rulesWrapper,$) {
				let groupIndex = 0;
				rulesWrapper.find('.um-conditional-rules-group').each( function(i) {
					let fieldIndex = 0;
					$(this).find('.um-conditional-rule-row').each( function(ii) {
						$(this).find('.um-conditional-rule-setting').each(function(iii){
							let fieldBaseName = $(this).data('base-name');
							if ( 'undefined' !== typeof fieldBaseName ) {
								let newName = fieldBaseName.replace( '\{group_key\}', groupIndex );
								newName = newName.replace( '\{row_key\}', fieldIndex );
								$(this).attr('name', newName);
							}
						});
						fieldIndex++;
					});
					$(this).data( 'group-index', groupIndex ).attr( 'data-group-index', groupIndex );
					groupIndex++;
				});
			},
			showReset: function(obj,$) {
				let conditionsTab = obj.parents('.um-edit-field-tabs-content > div[data-tab="conditional"]');
				conditionsTab.find('.um-field-groups-field-reset-all-conditions').css({'visibility':'visible'});
			},
			hideReset: function(obj,$) {
				let conditionsTab = obj.parents('.um-edit-field-tabs-content > div[data-tab="conditional"]');
				conditionsTab.find('.um-field-groups-field-reset-all-conditions').css({'visibility':'hidden'});
			},
			parseRules: function(conditionsTab,$) {
				let rows = [];
				conditionsTab.find('.um-conditional-rule-field-col select,.um-conditional-rule-condition-col select,.um-conditional-rule-value-col select,.um-conditional-rule-value-col input').each( function(i) {
					if ( '' !== $(this).val() ) {
						rows.push($(this));
					}
				});
				return rows;
			},
			isEmpty: function(conditionsTab,$) {
				let rules = UM.fields_groups.field.conditional.parseRules(conditionsTab,$);
				return ! rules.length;
			}
		},
		changeType: function (row, $) {
			let typeHTML = row.find('.um-groups-fields-field-type-select option[value="' + row.find('.um-groups-fields-field-type-select').val() + '"]').html();
			row.find( '.um-field-groups-field-type' ).html( typeHTML );
			UM.fields_groups.field.conditional.prepareFieldsList($);
		}
	},
};

jQuery( function($) {

	UM.fields_groups.field.conditional.prepareFieldsList($);

	$('.um-field-groups-fields-wrapper').find('.um-field-groups-field-row').each( function() {
		let fieldID = $(this).data('field');
		$(this).find('.um-conditional-rule-field-col .um-conditional-rule-setting').each( function() {
			$(this).find('option').each( function(){
				if ( $(this).is(':selected') || '' === $(this).attr('value') ) {
					return;
				}
				$(this).remove();
			});

			let selectField = $(this);
			let selectFieldVal = selectField.val();
			$.each( UM.fields_groups.field.conditional.fieldsList, function ( id, field ) {
				if ( ! selectField.find( 'option[value="' + id + '"]' ).length && fieldID != id ) {
					selectField.find( 'option[value=""]' ).after( '<option value="' + id + '">' + field.title + '</option>' );
				}
			});

			let $options = selectField.find('option').detach();
			$options.sort(function(a, b) {
				if ( '' === $(a).val() ) {
					return 0;
				}
				if ($(a).text() > $(b).text()) return 1;
				if ($(a).text() < $(b).text()) return -1;
				return 0;
			});
			selectField.append($options).val( selectFieldVal );
		} );
	});



	// var um_settings_changed = false;

	// jQuery( 'input, textarea, select' ).on('change', function() {
	// 	um_settings_changed = true;
	// });
	//
	// jQuery( '.submitdelete.deletion' ).on( 'click', function() {
	// 	if ( um_settings_changed ) {
	// 		window.onbeforeunload = function() {
	// 			return wp.i18n.__( 'Are sure, maybe some settings not saved', 'ultimate-member' );
	// 		};
	// 	} else {
	// 		window.onbeforeunload = '';
	// 	}
	// });
	//
	// jQuery( '#publishing-action input' ).on( 'click', function() {
	// 	window.onbeforeunload = '';
	// });

	UM.fields_groups.sortable.init($);

	// wp.ajax.send( 'um_fields_groups_check_draft', {
	// 	data: {
	// 		group_id: $('#fields_group_id').val(),
	// 		nonce: um_admin_scripts.nonce
	// 	},
	// 	beforeSend: function() {
	// 		$('.um-overlay-loader').show();
	// 	},
	// 	success: function( data ) {
	// 		if ( null !== data.draft_id ) {
	// 			if ( confirm( wp.i18n.__( 'There is not-saved fields group. If you want to use this draft - click "Ok". Otherwise create new one - click "Cancel".', 'ultimate-member' ) ) ) {
	// 				// click "Ok"
	// 				$('.um-overlay-loader').hide();
	// 			} else {
	// 				// click "cancel"
	// 				wp.ajax.send( 'um_fields_groups_flush_draft', {
	// 					data: {
	// 						group_id: $('#fields_group_id').val(),
	// 						nonce: um_admin_scripts.nonce
	// 					},
	// 					success: function() {
	// 						window.location.reload();
	// 					},
	// 					error: function( data ) {
	// 						$('.um-overlay-loader').hide();
	// 						console.error( data );
	// 					}
	// 				});
	// 			}
	// 		} else {
	// 			$('.um-overlay-loader').hide();
	// 		}
	// 	},
	// 	error: function( data ) {
	// 		$('.um-overlay-loader').hide();
	// 		console.error( data );
	// 	}
	// });

	$(document.body).on('click','.um-field-groups-field-row', function(e){
		if ( typeof e.target !== 'undefined' && e.target.classList.contains('um-field-groups-toggle-edit') ) {
			e.preventDefault();
			UM.fields_groups.field.toggleEdit($(this),$);
		}
	});

	$(document.body).on('click','.um-field-groups-field-edit', function(e){
		e.preventDefault();
		let row = $(this).parents('.um-field-groups-field-row');
		UM.fields_groups.field.toggleEdit(row,$);
	});

	$(document.body).on('click','.um-field-groups-field-delete', function(e){
		e.preventDefault();
		if ( confirm( wp.i18n.__( 'Are you sure you want to delete this field?', 'ultimate-member' ) ) ) {
			UM.fields_groups.field.delete($(this),$);
		}
	});

	$(document.body).on('click','.um-field-groups-field-duplicate', function(e){
		e.preventDefault();
		if ( confirm( wp.i18n.__( 'Are you sure you want to duplicate this field?', 'ultimate-member' ) ) ) {
			UM.fields_groups.field.duplicate($(this),$);
		}
	});

	$(document.body).on('click','.um-add-field-groups-field', function(e){
		e.preventDefault();
		UM.fields_groups.field.add($);
	});

	$(document.body).on('click','.um-edit-field-tabs > div[data-tab]:not(.current)', function(e){
		e.preventDefault();
		UM.fields_groups.tabs.setActive($(this),$);
	});

	$(document.body).on('click','.um-field-groups-field-reset-all-conditions', function(e){
		e.preventDefault();
		if ( confirm( wp.i18n.__( 'Are you sure?', 'ultimate-member' ) ) ) {
			UM.fields_groups.field.conditional.reset($(this),$);
		}
	});

	$(document.body).on('click','.um-conditional-add-rules-group', function(e){
		e.preventDefault();
		let rulesWrapper = $(this).parents('.um-conditional-rules-wrapper');
		UM.fields_groups.field.conditional.addRulesGroup(rulesWrapper,$);
	});

	$(document.body).on('click','.um-conditional-add-rule', function(e){
		e.preventDefault();
		let currentRuleRow = $(this).parents('.um-conditional-rule-row');
		UM.fields_groups.field.conditional.addRule(currentRuleRow,$);
	});

	$(document.body).on('click','.um-conditional-remove-rule', function(e){
		e.preventDefault();
		let currentRuleRow = $(this).parents('.um-conditional-rule-row');
		UM.fields_groups.field.conditional.removeRule(currentRuleRow,$);
	});

	$(document.body).on('change','.um-conditional-rule-field-col select, .um-conditional-rule-condition-col select, .um-conditional-rule-value-col select,.um-conditional-rule-value-col input', function(e){
		let conditionsTab = $(this).parents('.um-edit-field-tabs-content > div[data-tab="conditional"]');
		if ( UM.fields_groups.field.conditional.isEmpty(conditionsTab,$) ) {
			UM.fields_groups.field.conditional.hideReset($(this),$);
		} else {
			UM.fields_groups.field.conditional.showReset($(this),$);
		}
	});

	$(document.body).on('change','.um-conditional-rule-field-col .um-conditional-rule-setting', function(e){
		let fieldID = $(this).val();
		let fieldsRow = $(this).parents('.um-conditional-rule-fields');
		if ( '' === fieldID ) {
			fieldsRow.find('.um-conditional-rule-condition-col .um-conditional-rule-setting').prop('disabled', true);
			fieldsRow.find('.um-conditional-rule-value-col input.um-conditional-rule-setting').prop('disabled', true).show();
			fieldsRow.find('.um-conditional-rule-value-col select.um-conditional-rule-setting').prop('disabled', true).hide();
		} else {
			let selectField = fieldsRow.find('.um-conditional-rule-condition-col .um-conditional-rule-setting');
			selectField.prop('disabled', false);

			selectField.find('option').each( function(){
				if ( '' === $(this).attr('value') ) {
					return;
				}
				$(this).remove();
			});

			let selectFieldVal = selectField.val();
			$.each( UM.fields_groups.field.conditional.fieldsList[ fieldID ].conditions, function ( key, title ) {
				if ( ! selectField.find( 'option[value="' + key + '"]' ).length ) {
					selectField.find( 'option[value=""]' ).after( '<option value="' + key + '">' + title + '</option>' );
				}
			});

			let $options = selectField.find('option').detach();
			$options.sort(function(a, b) {
				if ( '' === $(a).val() ) {
					return 0;
				}
				if ($(a).text() > $(b).text()) return 1;
				if ($(a).text() < $(b).text()) return -1;
				return 0;
			});
			selectField.append($options).val('');

			if ( null === UM.fields_groups.field.conditional.fieldsList[ fieldID ].valueOptions ) {
				fieldsRow.find('.um-conditional-rule-value-col input.um-conditional-rule-setting').prop('disabled', true).show();
				fieldsRow.find('.um-conditional-rule-value-col select.um-conditional-rule-setting').prop('disabled', true).hide();
			} else {
				fieldsRow.find('.um-conditional-rule-value-col input.um-conditional-rule-setting').prop('disabled', true).hide();
				fieldsRow.find('.um-conditional-rule-value-col select.um-conditional-rule-setting').prop('disabled', true).show();
			}
		}
	});

	$(document.body).on('change','.um-conditional-rule-condition-col .um-conditional-rule-setting', function(e){
		let ruleKey = $(this).val();
		let fieldsRow = $(this).parents('.um-conditional-rule-fields');
		let fieldID = fieldsRow.find('.um-conditional-rule-field-col .um-conditional-rule-setting').val();

		if ( '' === ruleKey || '!=empty' === ruleKey || '==empty' === ruleKey ) {
			fieldsRow.find('.um-conditional-rule-value-col input.um-conditional-rule-setting').prop('disabled', true).show();
			fieldsRow.find('.um-conditional-rule-value-col select.um-conditional-rule-setting').prop('disabled', true).hide();
		} else {
			if ( null === UM.fields_groups.field.conditional.fieldsList[ fieldID ].valueOptions ) {
				fieldsRow.find('.um-conditional-rule-value-col input.um-conditional-rule-setting').prop('disabled', false).show();
				fieldsRow.find('.um-conditional-rule-value-col select.um-conditional-rule-setting').prop('disabled', true).hide();
			} else {
				fieldsRow.find('.um-conditional-rule-value-col input.um-conditional-rule-setting').prop('disabled', true).hide();
				fieldsRow.find('.um-conditional-rule-value-col select.um-conditional-rule-setting').prop('disabled', false).show();
			}
		}
	});

	$('.um-conditional-rules-wrapper .um-conditional-rule-condition-col .um-conditional-rule-setting').trigger('change');

	$(document.body).on('change','.um-field-groups-field-title-input', function(e){
		$(this).parents('.um-field-groups-field-row').find( '.um-field-groups-field-title' ).html( $(this).val() );
		UM.fields_groups.field.conditional.prepareFieldsList($);
	});

	$(document.body).on('change','.um-field-groups-field-type-select', function(e){
		let row = $(this).parents('.um-field-groups-field-row');
		UM.fields_groups.field.changeType(row,$);
	});
});
