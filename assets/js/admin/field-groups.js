// wp-admin scripts that must be enqueued on Fields Groups list table and individual Field Group page

var um_radioStates = {};

/**
 * Recalculate options indexes
 */
function um_recalculate_indexes( optionRowsWrapper ) {
	optionRowsWrapper.find('.um-admin-option-row').each( function (i) {
		jQuery(this).show().attr('data-option_index', i).data('option_index', i);

		let baseNameDefaultMulti = jQuery(this).find('.um-admin-option-default-multi').data('base-name');
		jQuery(this).find('.um-admin-option-default-multi').attr( 'name', baseNameDefaultMulti + '[' + i + ']');

		jQuery(this).find('.um-admin-option-default').attr('value',i);

		let baseNameOptionKey = jQuery(this).find('.um-admin-option-key').data('base-name');
		jQuery(this).find('.um-admin-option-key').attr('name',baseNameOptionKey + '[' + i + ']');

		let baseNameOptionVal = jQuery(this).find('.um-admin-option-val').data('base-name');
		jQuery(this).find('.um-admin-option-val').attr('name',baseNameOptionVal + '[' + i + ']');
	} );

	um_radio_set_states( optionRowsWrapper );
}

function um_multi_or_not( input, row ) {
	let optionRowsWrapper = row.find( '.um-admin-option-rows' );
	let rows = optionRowsWrapper.find( '.um-admin-option-row' );

	if ( input.is(':checked') ) {
		rows.find('.um-admin-option-default-multi').prop('disabled', false).show();
		rows.find('.um-admin-option-default').prop('disabled', true).prop('checked', false).hide();
	} else {
		rows.find('.um-admin-option-default-multi').prop('disabled', true).prop('checked', false).hide();
		rows.find('.um-admin-option-default').prop('disabled', false).show();
	}

	um_radio_set_states( optionRowsWrapper );
}

function um_admin_init_draggable( optionRowsWrapper ) {
	/**
	 * Sort options
	 */
	optionRowsWrapper.sortable({
		items:                  '.um-admin-option-row',
		forcePlaceholderSize:   true,
		update: function( event, ui ) {
			um_recalculate_indexes( optionRowsWrapper );
		}
	});

	um_radio_set_states( optionRowsWrapper );
}

// for unchecking the radio
function um_radio_set_states( optionRowsWrapper ) {
	um_radioStates = {};
	jQuery.each( optionRowsWrapper.find( '.um-admin-option-default' ), function(index, rd) {
		um_radioStates[rd.value] = jQuery(rd).is(':checked');
	});
}

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

			let tabs = tab_obj.parents('.um-field-row-tabs');
			tabs.find('div[data-tab]').removeClass('current');
			tab_obj.addClass('current');

			let contents = tab_obj.parents('.um-field-row-tabs').siblings('.um-field-row-tabs-content');
			contents.find('div[data-tab]').removeClass('current');
			contents.find('div[data-tab="' + tab + '"]').addClass('current');
		},
		compare: function (a, b) {
			if (a.length !== b.length) return false;
			else {
				// Comparing each element of your array
				for (var i = 0; i < a.length; i++) {
					if (a[i] !== b[i]) {
						return false;
					}
				}
				return true;
			}
		},
		compareDeep: function (a, b) {
			if ( typeof a !== typeof b ) {
				return false;
			}

			if ( typeof a === 'object' ) {
				let rowsCompare = UM.fields_groups.tabs.compare( Object.keys(a), Object.keys(b) );
				if ( ! rowsCompare ) {
					return false;
				}

				let result = true;
				Object.entries( a ).forEach(([i, sub]) => {
					result = UM.fields_groups.tabs.compareDeep( sub, b[i] );
					if ( ! result ) {
						return false;
					}
				});

				return result;
			} else {
				return a === b;
			}
		},
		reBuild: function( row, newSettingsTabs, $ ) {
			let $rowTabs = row.children('.um-field-row-content').children('.um-field-row-tabs');
			let $rowTabsContent = row.children('.um-field-row-content').children('.um-field-row-tabs-content');
			let currentTab = $rowTabs.children('div[data-tab].current').data('tab');
			$rowTabs.children('div[data-tab]').remove();

			$rowTabsContent.children('div[data-tab]').each( function() {
				if ( ! newSettingsTabs.includes( $(this).data('tab') ) ) {
					$(this).remove();
				}
			});

			let newTabsHTML = '';
			for (var i = 0; i < newSettingsTabs.length; i++) {
				newTabsHTML += '<div data-tab="' + newSettingsTabs[i] + '">' + um_admin_field_groups_data.field_tabs[newSettingsTabs[i]] + '</div>';

				if ( ! $rowTabsContent.children('div[data-tab="' + newSettingsTabs[i] + '"]').length ) {
					$rowTabsContent.append('<div data-tab="' + newSettingsTabs[i] + '"></div>');
				}
			}
			$rowTabs.html(newTabsHTML);
			$rowTabs.children('div[data-tab="' + currentTab + '"]').addClass('current');

			$rowTabsContent.children('div[data-tab="' + currentTab + '"]').addClass('current');
		}
	},
	sortable: {
		init: function ($wrapper,$) {
			$wrapper.sortable({
				items: '.um-field-row',
				connectWith: 'um-fields-column-content',
				placeholder: 'um-field-row-placeholder',
				forcePlaceholderSize:true,
				axis: 'y',
				cursor: 'move',
				handle: '.um-field-row-move-link',
				update: function(){
					// $(this) means sorting wrapper block
					UM.fields_groups.sortable.update($(this),$);
				}
			}).on('sortupdate',function(){
				// $(this) means sorting wrapper block
				UM.fields_groups.sortable.update($(this),$);
			});
		},
		update: function ($wrapper,$) {
			$wrapper.children('.um-field-row').each( function(i) {
				let index = i * 1 + 1;
				$(this).children('.um-field-row-order').val(index);
				$(this).children('.um-field-row-header').children('.um-field-row-move-link').text(index);
			});

			UM.fields_groups.field.conditional.prepareFieldsList($);
			UM.fields_groups.field.conditional.showHideConditionalTabs($);
			UM.fields_groups.field.conditional.fillRulesFields($);
		},
		destroy: function ($wrapper) {
			if ( $wrapper.hasClass('ui-sortable') ) {
				$wrapper.sortable('destroy');
			}
		},
		reInit: function ($wrapper,$) {
			UM.fields_groups.sortable.destroy($wrapper);
			UM.fields_groups.sortable.init($wrapper,$);
			$wrapper.trigger('sortupdate');
		}
	},
	field: {
		newIndex: 0,
		settingsScreens: {},
		prepareSettings: function($, fieldID, settingsTab) {
			$('.um-fields-column-content > .um-field-row').each( function() {
				let id   = $(this).data('field'); // id
				let type = $(this).children('.um-field-row-content').children('.um-field-row-tabs-content').children('div[data-tab="general"]').children('.um-form-table').children('tbody').children('.um-forms-line[data-field_id="type"]').find('.um-field-row-type-select').val(); // type

				if ( typeof fieldID !== 'undefined' ) {
					if ( fieldID != id ) {
						return;
					}
				}

				if ( typeof ( UM.fields_groups.field.settingsScreens[ id ] ) !== 'object' ) {
					UM.fields_groups.field.settingsScreens[ id ] = {};
				}

				if ( typeof ( UM.fields_groups.field.settingsScreens[ id ][ type ] ) !== 'object' ) {
					UM.fields_groups.field.settingsScreens[ id ][ type ] = {};
				}

				$(this).children('.um-field-row-content').children('.um-field-row-tabs').children('div[data-tab]').each(function () {
					let tabKey = $(this).data('tab');

					if ( typeof settingsTab !== 'undefined' ) {
						if ( settingsTab != tabKey ) {
							return;
						}
					}

					let screenObj = $(this).closest('.um-field-row-tabs').siblings( '.um-field-row-tabs-content' ).children( 'div[data-tab="' + tabKey + '"]').children('.um-form-table').clone();
					screenObj.find( '.wp-editor-wrap' ).replaceWith('<div class="um-admin-editor"></div><div class="um-admin-editor-content" data-editor_id="' + screenObj.find( 'textarea.wp-editor-area' ).attr('id') + '" data-editor_name="' + screenObj.find( 'textarea.wp-editor-area' ).attr('name') + '">' + screenObj.find( 'textarea.wp-editor-area' ).val() + '</div>');

					UM.fields_groups.field.settingsScreens[ id ][ type ][ tabKey ] = screenObj[0].outerHTML;
				});
			});
		},
		sanitizeInput: function(value) {
			return value.replace(/<(|\/|[^>\/bi]|\/[^>bi]|[^\/>][^>]+|\/[^>][^>]+)>/g, '');
		},
		checkIfMultiple: function(row) {
			// If multiple checkbox is in the field row settings then trigger its change for affecting to Options selector.
			if ( row.find('.um-field-row-multiple-input').length ) {
				row.find('.um-field-row-multiple-input').trigger('change');
			}
		},
		add: function ( button,$ ) {
			let $wrapper = button.closest('.um-fields-column').children('.um-fields-column-content');

			let parentRowFieldID = button.closest('.um-field-row').data('field');
			if ( 'undefined' === typeof parentRowFieldID ) {
				parentRowFieldID = 0;
			}

			// Only one row template for all builder. Avoid duplicates
			let $cloned = $('.um-field-row-template').clone().addClass('um-field-row').removeClass('um-field-row-template');

			UM.fields_groups.field.newIndex ++;
			let newIndex = UM.fields_groups.field.newIndex;
			$cloned.data( 'field', 'new_' + newIndex ).attr( 'data-field', 'new_' + newIndex );

			let newOrderIndex = $wrapper.find( '> .um-field-row' ).length + 1;
			$cloned.find('.um-field-row-move-link').text( newOrderIndex );

			let fieldID = $cloned.find('.um-field-row-id');
			fieldID.removeAttr('disabled').prop('disabled', false);
			let fieldIDName = fieldID.attr('name');
			if ( 'undefined' !== typeof fieldIDName ) {
				let newName = fieldIDName.replace( '\{index\}', newIndex );
				fieldID.attr('name', newName);
			}

			let fieldParentID = $cloned.find('.um-field-row-parent-id');
			fieldParentID.removeAttr('disabled').prop('disabled', false);
			let fieldParentIDName = fieldParentID.attr('name');
			if ( 'undefined' !== typeof fieldParentIDName ) {
				let newName = fieldParentIDName.replace( '\{index\}', newIndex );
				fieldParentID.attr('name', newName).val( parentRowFieldID );
			}

			let fieldOrder = $cloned.find('.um-field-row-order');
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
						$(this).removeAttr( 'class' ).removeAttr( 'data-extra-class' ).addClass( 'form-table um-form-table ' + extraClass );
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

				let conditionalFields = $(this).find('.um-conditional-rule-setting');
				conditionalFields.each(function(i) {
					let baseName = $(this).data('base-name');
					if ( 'undefined' !== typeof baseName ) {
						let newBaseName = baseName.replace( '\{index\}', newIndex );
						$(this).data('base-name', newBaseName).attr('data-base-name',newBaseName);
					}

					let name = $(this).attr('name');
					if ( 'undefined' !== typeof name ) {
						let newName = name.replace( '\{index\}', newIndex );
						$(this).attr('name',newName);
					}
				});

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

				let fieldHiddens = [
					field.siblings('input[type="hidden"]'),
					$(this).find('#' + fieldID + '_hidden' )
				];
				$.each( fieldHiddens, function(i) {
					let fieldHidden = fieldHiddens[i];

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
			});

			// let row_fieldID = $cloned.data('field');
			// $cloned.find('.um-conditional-rule-field-col .um-conditional-rule-setting').each( function() {
			// 	$(this).find('option').each( function(){
			// 		if ( $(this).is(':selected') || '' === $(this).attr('value') ) {
			// 			return;
			// 		}
			// 		$(this).remove();
			// 	});
			//
			// 	let selectField = $(this);
			// 	let selectFieldVal = selectField.val();
			// 	$.each( UM.fields_groups.field.conditional.fieldsList, function ( id, field ) {
			// 		if ( ! selectField.find( 'option[value="' + id + '"]' ).length && row_fieldID != id ) {
			// 			selectField.find( 'option[value=""]' ).after( '<option value="' + id + '">' + field.title + '</option>' );
			// 		}
			// 	});
			//
			// 	let $options = selectField.find('option').detach();
			// 	$options.sort(function(a, b) {
			// 		if ( '' === $(a).val() ) {
			// 			return 0;
			// 		}
			// 		if ($(a).text() > $(b).text()) return 1;
			// 		if ($(a).text() < $(b).text()) return -1;
			// 		return 0;
			// 	});
			// 	selectField.append($options).val( selectFieldVal );
			// } );

			$wrapper.append( $cloned );

			if ( $wrapper.children('.um-field-row').length > 1 ) {
				UM.fields_groups.sortable.reInit($wrapper,$);
			} else {
				$wrapper.removeClass('hidden');
				$wrapper.siblings('.um-fields-column-header').removeClass('hidden');
				$wrapper.siblings('.um-fields-column-empty-content').addClass('hidden');
				UM.fields_groups.sortable.init($wrapper,$);
			}

			run_check_conditions();

			UM.fields_groups.field.conditional.prepareFieldsList($);
			UM.fields_groups.field.conditional.showHideConditionalTabs($);
			UM.fields_groups.field.conditional.fillRulesFields($);
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
			UM.fields_groups.field.checkIfMultiple(row);
		},
		hideEdit: function ( row, $ ) {
			row.removeClass('um-field-row-edit-mode');
		},
		duplicate: function ( obj, $ ) {
			let $wrapper = obj.closest('.um-fields-column-content');
			let $cloned = obj.closest('.um-field-row').clone();
			// todo changing the fields names and field ID
			obj.closest('.um-field-row').after( $cloned );
			UM.fields_groups.sortable.reInit($wrapper,$);
			UM.fields_groups.field.conditional.prepareFieldsList($);
			//UM.fields_groups.field.prepareSettings($);
		},
		delete: function ( obj, $ ) {
			let $wrapper = obj.closest('.um-fields-column-content');
			let row = obj.closest('.um-field-row');

			// flush object with settings screen for this field
			delete UM.fields_groups.field.settingsScreens[ row.data('field') ];

			row.remove();

			if ( ! $wrapper.find('.um-field-row').length ) {
				$wrapper.addClass('hidden');
				$wrapper.siblings('.um-fields-column-header').addClass('hidden');
				$wrapper.siblings('.um-fields-column-empty-content').removeClass('hidden');
				UM.fields_groups.sortable.destroy($wrapper);
			} else {
				UM.fields_groups.sortable.reInit($wrapper,$);
			}
			UM.fields_groups.field.conditional.prepareFieldsList($);
			UM.fields_groups.field.conditional.showHideConditionalTabs($);
			UM.fields_groups.field.conditional.fillRulesFields($);
		},
		conditional: {
			fieldsList: {}, /* format id:{title,type}*/
			prepareFieldsList: function($) {
				$('.um-fields-column-content').each( function () {
					let uniqid = $(this).data('uniqid');
					UM.fields_groups.field.conditional.fieldsList[uniqid] = {};
					$(this).children('.um-field-row').each( function() {
						let rowContent = $(this).children('.um-field-row-content');
						let generalTabFormsLine = rowContent.find('> .um-field-row-tabs-content > div[data-tab="general"] > .um-form-table > tbody > .um-forms-line:not([data-field_type="sub_fields"])');

						let type = generalTabFormsLine.find('.um-field-row-type-select').val(); // type
						if ( um_admin_field_groups_data.field_types[type].conditional_rules.length ) {
							let id = $(this).data('field'); // id
							let title = UM.fields_groups.field.sanitizeInput( generalTabFormsLine.find('.um-field-row-title-input').val() ); // title
							let order = $(this).children('.um-field-row-order').val();

							let rules = {};
							um_admin_field_groups_data.field_types[type].conditional_rules.forEach((element) => {
								rules[element] = um_admin_field_groups_data.conditional_rules[ element ];
							});

							let valueOptions = null;
							if ( 'choice' === um_admin_field_groups_data.field_types[type].category ) {
								valueOptions = [];
								if ( 'bool' === type ) {
									valueOptions = [
										{'key':0,'value':wp.i18n.__('False','ultimate-member')},
										{'key':1,'value':wp.i18n.__('True','ultimate-member')},
									];
								} else {
									generalTabFormsLine.each(function (){
										if ( 'choices' === $(this).data('field_type') ) {
											$(this).find('.um-admin-option-row').each(function (){
												let key = $(this).find('.um-admin-option-key').val();
												let value = $(this).find('.um-admin-option-val').val();
												valueOptions.push( {'key':key,'value':value} );
											});
										}
									});
								}
							}

							UM.fields_groups.field.conditional.fieldsList[uniqid][order] = {
								'id': id,
								'type': type,
								'title': title,
								'conditions': rules,
								'valueOptions': valueOptions,
								'order': order
							};
						}
					});
				});
				// $('.um-fields-column-content').find('.um-field-row').each( function() {
				// 	let id = $(this).data('field'); // id
				// 	let type = $(this).find('.um-field-row-type-select').val(); // type
				// 	let title = UM.fields_groups.field.sanitizeInput( $(this).find('.um-field-row-title-input').val() ); // title
				//
				// 	if ( um_admin_field_groups_data.field_types[type].conditional_rules.length ) {
				// 		let rules = {};
				// 		um_admin_field_groups_data.field_types[type].conditional_rules.forEach((element) => {
				// 			rules[element] = um_admin_field_groups_data.conditional_rules[ element ];
				// 		});
				//
				// 		let valueOptions = null;
				// 		if ( 'choice' === um_admin_field_groups_data.field_types[type].category ) {
				// 			valueOptions = {};
				// 			// @todo get options from field settings
				// 			//$(this).find('.um-field-row-type-select').val()
				// 		}
				//
				// 		UM.fields_groups.field.conditional.fieldsList[id] = {
				// 			'type': type,
				// 			'title': title,
				// 			'conditions': rules,
				// 			'valueOptions': valueOptions
				// 		};
				// 	}
				// });
			},
			showHideConditionalTabs: function($) {
				$('.um-fields-column-content').each(function (){
					let firstFieldRow = $(this).children('.um-field-row:first');

					firstFieldRow.each( function () {
						$(this).data('conditional-disabled',true);
						let rowContent = $(this).children('.um-field-row-content');

						let conditionalTab = rowContent.children('.um-field-row-tabs').children('div[data-tab="conditional"]');
						if ( conditionalTab.hasClass('current') ) {
							conditionalTab.siblings('div[data-tab="general"]').trigger('click');
						}
						conditionalTab.hide();

						let conditionalForm = rowContent.children('.um-field-row-content').children('div[data-tab="conditional"]');

						conditionalForm.find('.um-forms-field').prop('disabled', true);
						conditionalForm.find('.um-conditional-rule-condition-col .um-conditional-rule-setting:not(.um-force-disabled)').prop('disabled', true);
						conditionalForm.find('.um-conditional-rule-value-col input.um-conditional-rule-setting:not(.um-force-disabled)').prop('disabled', true);
						conditionalForm.find('.um-conditional-rule-value-col select.um-conditional-rule-setting:not(.um-force-disabled)').prop('disabled', true);
					});

					let anotherFieldRows = $(this).children('.um-field-row:not(:first)');

					anotherFieldRows.each( function () {
						$(this).data('conditional-disabled', false);
						let rowContent = $(this).children('.um-field-row-content');

						rowContent.children('.um-field-row-tabs').children('div[data-tab="conditional"]').show();

						let conditionalForm = rowContent.children('.um-field-row-content').children('div[data-tab="conditional"]');

						conditionalForm.find('.um-forms-field').prop('disabled', false);
						conditionalForm.find('.um-conditional-rule-condition-col .um-conditional-rule-setting:not(.um-force-disabled)').prop('disabled', false);
						conditionalForm.find('.um-conditional-rule-value-col input.um-conditional-rule-setting:not(.um-force-disabled)').prop('disabled', false);
						conditionalForm.find('.um-conditional-rule-value-col select.um-conditional-rule-setting:not(.um-force-disabled)').prop('disabled', false);
					});
				});

				run_check_conditions();
			},
			fillRulesFields: function($) {
				let $fieldsColumnsObj = $('.um-fields-column-content');
				$fieldsColumnsObj.each( function () {
					let uniqid = $(this).data('uniqid');

					$(this).children('.um-field-row').each( function() {
						if ( true === $(this).data('conditional-disabled') ) {
							return;
						}

						if ( 'undefined' === typeof UM.fields_groups.field.conditional.fieldsList[ uniqid ] ) {
							return;
						}

						let currentFieldOrder = $(this).children('.um-field-row-order').val();

						let conditionalForm = $(this).children('.um-field-row-content').children('.um-field-row-tabs-content').children('div[data-tab="conditional"]');

						conditionalForm.find('.um-conditional-rule-field-col .um-conditional-rule-setting').each( function() {
							let selectField = $(this);
							let selectFieldVal = selectField.val();

							// Remove all options except empty.
							selectField.find('option').each( function(){
								if ( '' === $(this).attr('value') ) {
									return;
								}
								$(this).remove();
							});

							// Fill dropdown by the fields from the builder in necessary order.
							$.each( UM.fields_groups.field.conditional.fieldsList[ uniqid ], function ( order, field ) {
								// Show only fields that has previous order and are situated above the current field in the form.
								if ( order >= currentFieldOrder ) {
									return;
								}

								if ( ! selectField.find( 'option[value="' + field.id + '"]' ).length /*&& String( fieldID ) !== String( field.id )*/ ) {
									selectField.append( '<option value="' + field.id + '">' + field.title + '</option>' );
								}

								// Set predefined value that was selected before changing options.
								if ( String( field.id ) === String( selectFieldVal ) ) {
									selectField.val( selectFieldVal ).trigger('change');
								}
							});

							let fieldsRow = $(this).closest('.um-conditional-rule-fields');
							if ( '' === selectFieldVal ) {
								fieldsRow.find('.um-conditional-rule-condition-col .um-conditional-rule-setting').prop('disabled', true);
								fieldsRow.find('.um-conditional-rule-value-col input.um-conditional-rule-setting').prop('disabled', true).show();
								fieldsRow.find('.um-conditional-rule-value-col select.um-conditional-rule-setting').prop('disabled', true).hide();
							} else {

								let fieldRowOrder = $('.um-field-row[data-field="' + selectFieldVal + '"]').children('.um-field-row-order').val();

								let conditionalsField = fieldsRow.find('.um-conditional-rule-condition-col .um-conditional-rule-setting');
								let conditionalsFieldVal = conditionalsField.val();
								conditionalsField.prop('disabled', false);

								conditionalsField.find('option').each( function(){
									if ( '' === $(this).attr('value') ) {
										return;
									}
									$(this).remove();
								});

								// Fill dropdown by the condition rules based on the field-type.
								$.each( UM.fields_groups.field.conditional.fieldsList[ uniqid ][fieldRowOrder].conditions, function ( key, title ) {
									if ( ! conditionalsField.find( 'option[value="' + key + '"]' ).length ) {
										conditionalsField.append( '<option value="' + key + '">' + title + '</option>' );
									}

									// Set predefined value that was selected before changing options.
									if ( String( key ) === String( conditionalsFieldVal ) ) {
										conditionalsField.val( conditionalsFieldVal );
									}
								});

								let valueField = null;
								if ( null === UM.fields_groups.field.conditional.fieldsList[ uniqid ][fieldRowOrder].valueOptions ) {
									valueField = fieldsRow.find('.um-conditional-rule-value-col input.um-conditional-rule-setting');
									if ( '' !== conditionalsFieldVal && '!=empty' !== conditionalsFieldVal && '==empty' !== conditionalsFieldVal ) {
										valueField.prop('disabled', false);
									} else {
										valueField.prop('disabled', true);
									}
									valueField.show();

									fieldsRow.find('.um-conditional-rule-value-col select.um-conditional-rule-setting').prop('disabled', true).hide();
								} else {
									valueField = fieldsRow.find('.um-conditional-rule-value-col select.um-conditional-rule-setting');
									let valueFieldVal = valueField.val();
									fieldsRow.find('.um-conditional-rule-value-col input.um-conditional-rule-setting').prop('disabled', true).hide();

									if ( '' !== conditionalsFieldVal && '!=empty' !== conditionalsFieldVal && '==empty' !== conditionalsFieldVal ) {
										valueField.prop('disabled', false);
									} else {
										valueField.prop('disabled', true);
									}
									valueField.show();

									valueField.find('option').each( function(){
										if ( '' === $(this).attr('value') ) {
											return;
										}
										$(this).remove();
									});

									// Fill dropdown by the fields values from the builder in necessary order.
									$.each( UM.fields_groups.field.conditional.fieldsList[ uniqid ][fieldRowOrder].valueOptions, function ( order, option ) {
										if ( ! valueField.find( 'option[value="' + option.key + '"]' ).length ) {
											valueField.append( '<option value="' + option.key + '">' + option.value + '</option>' );
										}

										// Set predefined value that was selected before changing options.
										if ( String( option.key ) === String( valueFieldVal ) ) {
											valueField.val( valueFieldVal );
										}
									});
								}
							}
						});
					});
				});
			},
			reset: function(resetRulesBtn,$) {
				let conditionsTab = resetRulesBtn.parents('.um-field-row-tabs-content > div[data-tab="conditional"]');
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

				//UM.fields_groups.field.prepareSettings($);
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

				//UM.fields_groups.field.prepareSettings($);
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

				// UM.fields_groups.field.prepareSettings($);
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
				let conditionsTab = obj.parents('.um-field-row-tabs-content > div[data-tab="conditional"]');
				conditionsTab.find('.um-field-row-reset-all-conditions').css({'visibility':'visible'});
			},
			hideReset: function(obj,$) {
				let conditionsTab = obj.parents('.um-field-row-tabs-content > div[data-tab="conditional"]');
				conditionsTab.find('.um-field-row-reset-all-conditions').css({'visibility':'hidden'});
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
			let fieldID = row.data('field');
			let oldType = row.data('previousValue');
			let oldTypeData = um_admin_field_groups_data.field_types[oldType];
			let oldTypeSettings = oldTypeData.settings;

			let $rowTabs = row.children('.um-field-row-content').children('.um-field-row-tabs');
			let $rowTabsContent = row.children('.um-field-row-content').children('.um-field-row-tabs-content');
			let $typeSelect = $rowTabsContent.children('div[data-tab="general"]').children('.um-form-table').children('tbody').children('.um-forms-line[data-field_id="type"]').find('.um-field-row-type-select');

			let type = $typeSelect.val();
			let typeData = um_admin_field_groups_data.field_types[type];

			let typeSettings = typeData.settings;

			let currentSettingsTabs = Object.keys(oldTypeSettings);
			let newSettingsTabs = Object.keys(typeSettings);

			let typeHTML = $typeSelect.find('option[value="' + type + '"]').html();
			row.find( '> .um-field-row-header > .um-field-row-type' ).text( UM.fields_groups.field.sanitizeInput( typeHTML ) ); // change field type in a row header

			// remove old type wp_editor.
			if ( typeof ( UM.fields_groups.field.settingsScreens[ fieldID ] ) === 'object' &&  typeof ( UM.fields_groups.field.settingsScreens[ fieldID ][ oldType ] ) === 'object' ) {
				$.each( UM.fields_groups.field.settingsScreens[ fieldID ][ oldType ], function ( tab, html ) {
					let tabSettingsWrapper = $rowTabsContent.children( 'div[data-tab="' + tab + '"]' );
					let editorTextarea = tabSettingsWrapper.children('.um-form-table').children('tbody').children('.um-forms-line[data-field_type="wp_editor"]').find('textarea.wp-editor-area');
					if ( editorTextarea.length ) {
						let id = editorTextarea.attr('id');
						if ( typeof( tinyMCE ) === 'object' && tinyMCE.get( id ) !== null ) {
							tinyMCE.triggerSave();
							tinyMCE.EditorManager.execCommand( 'mceRemoveEditor', true, id );
							"4" === tinyMCE.majorVersion ? window.tinyMCE.execCommand( "mceRemoveEditor", !0, id ) : window.tinyMCE.execCommand( "mceRemoveControl", !0, id );
						}
					}
				});
			}

			// add/remove necessary/unnecessary tabs and tab-content blocks
			if ( ! UM.fields_groups.tabs.compare( currentSettingsTabs, newSettingsTabs ) ) {
				UM.fields_groups.tabs.reBuild( row, newSettingsTabs, $ );
			}

			$rowTabs.children('div[data-tab]').addClass('disabled');

			if ( typeof ( UM.fields_groups.field.settingsScreens[ fieldID ] ) === 'object' &&  typeof ( UM.fields_groups.field.settingsScreens[ fieldID ][ type ] ) === 'object' ) {
				$.each( UM.fields_groups.field.settingsScreens[ fieldID ][ type ], function ( tab, html ) {
					let tabSettingsWrapper = $rowTabsContent.children( 'div[data-tab="' + tab + '"]' );

					let temporaryScreenObj = $('<div>').append( html ).find('.form-table');

					if ( 'conditional' === tab ) {
						// Make conditional rules static always.
						temporaryScreenObj.find('[data-field_type="conditional_rules"]').html( tabSettingsWrapper.find('[data-field_type="conditional_rules"]').html() );
					}

					// Set static settings values from old field type.
					tabSettingsWrapper.find('.um-field-row-static-setting').each(function () {
						let staticValue = $(this).val();
						temporaryScreenObj.find( '[name="' + $(this).attr('name') + '"]' ).each( function () {
							if ( 'hidden' !== $(this).attr('type') && 'checkbox' !== $(this).attr('type') ) {
								$(this).val( staticValue );
							}
						});
					});

					// Change HTML based on type.
					tabSettingsWrapper.html( temporaryScreenObj[0].outerHTML );

					um_maybe_init_tinymce( tabSettingsWrapper );
				});

				$rowTabsContent.children('div[data-tab="general"]').children('.um-form-table').children('tbody').children('.um-forms-line[data-field_id="type"]').find('.um-field-row-type-select').removeClass('disabled').prop('disabled', false);

				run_check_conditions();
				UM.fields_groups.field.conditional.prepareFieldsList($);

				$rowTabs.children('div[data-tab]').removeClass('disabled');
				UM.fields_groups.field.checkIfMultiple(row);
			} else {
				if ( ! UM.fields_groups.tabs.compareDeep( oldTypeSettings.general, typeSettings.general ) ) {
					$rowTabsContent.children('div[data-tab="general"]').find( '> .form-table > tbody > .um-forms-line .um-forms-field:not(.um-field-row-static-setting)' ).closest('.um-forms-line').remove();

					let skeletonRows = Object.keys( typeSettings.general ).length - 2;
					if ( skeletonRows > 0 ) {
						let i = 0;
						while (i < skeletonRows) {
							$rowTabsContent.children( 'div[data-tab="general"]').children('.form-table').children('tbody' ).append('<tr class="um-forms-line um-forms-skeleton"><th><span class="um-skeleton-box" style="width:100%;height:20px;"></span></th><td><span class="um-skeleton-box" style="width:100%;height:40px;margin-bottom:4px;"></span><span class="um-skeleton-box" style="width:100%;height:14px;"></span></td></tr>');
							i++;
						}
					}
				}

				wp.ajax.send( 'um_fields_groups_get_settings_form', {
					data: {
						field_id: fieldID,
						type: type,
						nonce: um_admin_scripts.nonce
					},
					success: function( data ) {
						if ( typeof ( UM.fields_groups.field.settingsScreens[ fieldID ] ) !== 'object' ) {
							UM.fields_groups.field.settingsScreens[ fieldID ] = {};
						}

						if ( typeof ( UM.fields_groups.field.settingsScreens[ fieldID ][ type ] ) !== 'object' ) {
							UM.fields_groups.field.settingsScreens[ fieldID ][ type ] = {};
						}

						$.each( data.fields, function ( tab, html ) {
							UM.fields_groups.field.settingsScreens[ fieldID ][ type ][ tab ] = html;

							let temporaryScreenObj = $('<div>').append( html ).find('.form-table');

							let tabSettingsWrapper = $rowTabsContent.children( 'div[data-tab="' + tab + '"]' );

							if ( 'conditional' === tab ) {
								// make conditional rules static always
								temporaryScreenObj.find('[data-field_type="conditional_rules"]').html( tabSettingsWrapper.find('[data-field_type="conditional_rules"]').html() );
							}

							// Set static settings values from old field type.
							tabSettingsWrapper.find('.um-field-row-static-setting').each(function () {
								let staticValue = $(this).val();
								temporaryScreenObj.find( '[name="' + $(this).attr('name') + '"]' ).each( function () {
									if ( 'hidden' !== $(this).attr('type') && 'checkbox' !== $(this).attr('type') ) {
										$(this).val( staticValue );
									}
								});
							});

							// change HTML based on type
							tabSettingsWrapper.html( temporaryScreenObj[0].outerHTML );

							um_maybe_init_tinymce( tabSettingsWrapper );
						});

						$rowTabsContent.children('div[data-tab="general"]').children('.um-form-table').children('tbody').children('.um-forms-line[data-field_id="type"]').find('.um-field-row-type-select').removeClass('disabled').prop('disabled', false);

						run_check_conditions();

						UM.fields_groups.field.conditional.prepareFieldsList($);

						$rowTabs.children('div[data-tab]').removeClass('disabled');

						UM.fields_groups.field.checkIfMultiple(row);
					},
					error: function( data ) {
						console.error( data );
					}
				});
			}
		}
	},
};

function um_maybe_init_tinymce( tabSettingsWrapper ) {
	if ( tabSettingsWrapper.children('.um-form-table').children('tbody').children('.um-forms-line[data-field_type="wp_editor"]').find('.um-admin-editor').length ) {
		let id = tabSettingsWrapper.children('.um-form-table').children('tbody').children('.um-forms-line[data-field_type="wp_editor"]').find('.um-admin-editor-content').data('editor_id');
		let content = tabSettingsWrapper.children('.um-form-table').children('tbody').children('.um-forms-line[data-field_type="wp_editor"]').find('.um-admin-editor-content').html();

		um_tinymce_init( id, content );
	}
}

function um_tinymce_init( id, content ) {
	tinyMCEPreInit.mceInit[ id ] = tinyMCEPreInit.mceInit['um_editor_placeholder'];
	tinyMCEPreInit.qtInit[ id ] = tinyMCEPreInit.qtInit['um_editor_placeholder'];

	tinyMCEPreInit.mceInit[ id ] = JSON.parse(JSON.stringify(tinyMCEPreInit.mceInit[ id ]).replaceAll(/um_editor_placeholder/gm,id));
	tinyMCEPreInit.qtInit[ id ] = JSON.parse(JSON.stringify(tinyMCEPreInit.qtInit[ id ]).replaceAll(/um_editor_placeholder/gm,id));

	let fullEditor = jQuery('<div>').append( jQuery('#wp-um_editor_placeholder-wrap').clone() );
	let editorBase = jQuery('.um-admin-editor-content[data-editor_id="' + id + '"]');
	fullEditor.find('#um_editor_placeholder').attr('name', editorBase.data('editor_name') );

	let newHTML = fullEditor[0].innerHTML.replaceAll( new RegExp( 'um_editor_placeholder', 'gm' ), id );
	//editorBase.siblings('.um-admin-editor').html( newHTML );
	editorBase.siblings('.um-admin-editor').replaceWith( newHTML );

	if ( typeof( tinyMCE ) === 'object' && tinyMCE.get( 'um_editor_placeholder' ) !== null ) {

		//editorBase.siblings('.um-admin-editor').find('.wp-editor-container > .mce-tinymce.mce-container.mce-panel').remove();
		editorBase.siblings('.wp-editor-wrap').find('.wp-editor-container > .mce-tinymce.mce-container.mce-panel').remove();

		// let iFrameDoc = document.getElementById('um_editor_placeholder_ifr').contentWindow.document;
		// iFrameDoc.getElementById('tinymce').outerHTML = iFrameDoc.getElementById('tinymce').outerHTML.replaceAll(/um_editor_placeholder/gm,id);
		//
		// let iFrame = editorBase.siblings('.um-admin-editor').find('iframe')[0];
		// iFrame.contentWindow.document.open();
		// iFrame.contentWindow.document.write(iFrameDoc.documentElement.innerHTML);
		// iFrame.contentWindow.document.close();

		// 	tinyMCE.triggerSave();
		//
		// 	var init;
		// 	//if( typeof tinyMCEPreInit.mceInit[ id ] == 'undefined' ){
		// 		init = tinyMCEPreInit.mceInit[ id ] = tinyMCE.extend( {}, tinyMCEPreInit.mceInit[ id ] );
		// //	} else {
		// 	//init = tinyMCEPreInit.mceInit[ id ];
		// //	}

		/*tinyMCE.triggerSave();
		tinyMCE.EditorManager.execCommand( 'mceRemoveEditor', true, id );
		"4" === tinyMCE.majorVersion ? window.tinyMCE.execCommand( "mceRemoveEditor", !0, id ) : window.tinyMCE.execCommand( "mceRemoveControl", !0, id );
*/
		if ( typeof(QTags) == 'function' ) {
			QTags( tinyMCEPreInit.qtInit[ id ] );
			QTags._buttonsInit();
		}

		tinyMCE.init( tinyMCEPreInit.mceInit[ id ] );
		tinyMCE.get( id ).setContent( content );
		jQuery('#' + id).html( content );

		if ( typeof( window.switchEditors ) === 'object' ) {
			window.switchEditors.go( id );
		}
		/*if ( typeof( window.switchEditors ) === 'object' ) {
			window.switchEditors.go( id, 'tmce' );
		}*/
	} else {
		if ( typeof(QTags) == 'function' ) {
			QTags( tinyMCEPreInit.qtInit[ id ] );
			QTags._buttonsInit();
		}

		//use duplicate because it's new element
		jQuery('#' + id).html( content );
	}

	jQuery( document.body ).on( 'click', '.wp-switch-editor', function() {
		var target = jQuery(this);

		if ( target.hasClass( 'wp-switch-editor' ) && typeof( window.switchEditors ) === 'object' ) {
			var mode = target.hasClass( 'switch-tmce' ) ? 'tmce' : 'html';
			window.switchEditors.go( id, mode );
		}
	});

	editorBase.remove();
}

jQuery( function($) {
	// Set newIndex not 0 if form submitted with invalid data and need to resend.
	$('.um-field-row > .um-field-row-id').each(function(){
		if ( '' === $(this).val() ) {
			UM.fields_groups.field.newIndex ++;
		}
	});

	// Make fields columns sortable.
	let $fieldsColumnsObj = $('.um-fields-column-content');
	$fieldsColumnsObj.each( function () {
		UM.fields_groups.sortable.init($(this),$);
	});

	// Prepare conditional logic fields on first page load.
	UM.fields_groups.field.conditional.prepareFieldsList($);
	UM.fields_groups.field.conditional.showHideConditionalTabs($);
	UM.fields_groups.field.conditional.fillRulesFields($);

	console.log( '-------- Conditional -----------' );
	console.log( UM.fields_groups.field.conditional.fieldsList );
	console.log( '-------------' );

	// Prepare field settings screens for operate them on changing field type.
	UM.fields_groups.field.prepareSettings($);

	console.log( UM.fields_groups.field.settingsScreens );

	$(document.body).on('click','.um-field-row', function(e){
		if ( typeof e.target !== 'undefined' && e.target.classList.contains('um-field-row-toggle-edit') ) {
			e.preventDefault();
			let row = $(this);
			UM.fields_groups.field.toggleEdit(row,$);
			e.stopPropagation();
		}
	});

	$(document.body).on('click','.um-field-row-action-edit', function(e){
		e.preventDefault();
		let row = $(this).closest('.um-field-row');
		UM.fields_groups.field.toggleEdit(row,$);
	});

	$(document.body).on('click','.um-field-row-action-delete', function(e){
		e.preventDefault();
		if ( confirm( wp.i18n.__( 'Are you sure you want to delete this field?', 'ultimate-member' ) ) ) {
			UM.fields_groups.field.delete($(this),$);
		}
	});

	// todo make fields duplicate
	$(document.body).on('click','.um-field-row-action-duplicate', function(e){
		e.preventDefault();
		if ( confirm( wp.i18n.__( 'Are you sure you want to duplicate this field?', 'ultimate-member' ) ) ) {
			UM.fields_groups.field.duplicate($(this),$);
		}
	});

	$(document.body).on('click','.um-add-field-to-column', function(e){
		e.preventDefault();
		UM.fields_groups.field.add($(this),$);
	});

	$(document.body).on('click','.um-field-row-tabs > div[data-tab]:not(.current)', function(e){
		e.preventDefault();
		if ( $(this).hasClass('disabled') ) {
			return;
		}
		UM.fields_groups.tabs.setActive($(this),$);
	});

	/* START - Conditional Fields handlers */
	$(document.body).on('click','.um-field-row-reset-all-conditions', function(e){
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

	// Show or Hide "Reset All Rules" button handler.
	$(document.body).on('change','.um-conditional-rule-field-col select, .um-conditional-rule-condition-col select, .um-conditional-rule-value-col select,.um-conditional-rule-value-col input', function(e){
		let conditionsTab = $(this).parents('.um-field-row-tabs-content > div[data-tab="conditional"]');
		if ( UM.fields_groups.field.conditional.isEmpty(conditionsTab,$) ) {
			UM.fields_groups.field.conditional.hideReset($(this),$);
		} else {
			UM.fields_groups.field.conditional.showReset($(this),$);
		}

		//UM.fields_groups.field.prepareSettings($);
	});

	// Change Rule's Field.
	$(document.body).on('change','.um-conditional-rule-field-col .um-conditional-rule-setting', function(e){
		let uniqid = $(this).closest('.um-fields-column-content').data('uniqid');

		if ( 'undefined' === typeof UM.fields_groups.field.conditional.fieldsList[ uniqid ] ) {
			return;
		}

		let fieldID = $(this).val();
		let fieldsRow = $(this).closest('.um-conditional-rule-fields');
		if ( '' === fieldID ) {
			fieldsRow.find('.um-conditional-rule-condition-col .um-conditional-rule-setting').prop('disabled', true);
			fieldsRow.find('.um-conditional-rule-value-col input.um-conditional-rule-setting').prop('disabled', true).show();
			fieldsRow.find('.um-conditional-rule-value-col select.um-conditional-rule-setting').prop('disabled', true).hide();
		} else {

			let fieldRowOrder = $('.um-field-row[data-field="' + fieldID + '"]').children('.um-field-row-order').val();

			let conditionalsField = fieldsRow.find('.um-conditional-rule-condition-col .um-conditional-rule-setting');
			conditionalsField.prop('disabled', false);

			conditionalsField.find('option').each( function(){
				if ( '' === $(this).attr('value') ) {
					return;
				}
				$(this).remove();
			});

			// Fill dropdown by the condition rules based on the field-type.
			$.each( UM.fields_groups.field.conditional.fieldsList[ uniqid ][fieldRowOrder].conditions, function ( key, title ) {
				if ( ! conditionalsField.find( 'option[value="' + key + '"]' ).length ) {
					conditionalsField.append( '<option value="' + key + '">' + title + '</option>' );
				}
			});

			let valueField = null;
			if ( null === UM.fields_groups.field.conditional.fieldsList[ uniqid ][fieldRowOrder].valueOptions ) {
				valueField = fieldsRow.find('.um-conditional-rule-value-col input.um-conditional-rule-setting');
				valueField.prop('disabled', false).show();
				fieldsRow.find('.um-conditional-rule-value-col select.um-conditional-rule-setting').prop('disabled', true).hide();
			} else {
				valueField = fieldsRow.find('.um-conditional-rule-value-col select.um-conditional-rule-setting');

				fieldsRow.find('.um-conditional-rule-value-col input.um-conditional-rule-setting').prop('disabled', true).hide();
				valueField.prop('disabled', false).show();

				valueField.find('option').each( function(){
					if ( '' === $(this).attr('value') ) {
						return;
					}
					$(this).remove();
				});

				// Fill dropdown by the fields values from the builder in necessary order.
				$.each( UM.fields_groups.field.conditional.fieldsList[ uniqid ][fieldRowOrder].valueOptions, function ( order, option ) {
					if ( ! valueField.find( 'option[value="' + option.key + '"]' ).length ) {
						valueField.append( '<option value="' + option.key + '">' + option.value + '</option>' );
					}
				});
			}
			conditionalsField.val('').trigger('change');
			valueField.val('').trigger('change');
		}
		//UM.fields_groups.field.prepareSettings($);
	});

	// Change Rule's Condition.
	$(document.body).on('change','.um-conditional-rule-condition-col .um-conditional-rule-setting', function(e){
		let uniqid = $(this).closest('.um-fields-column-content').data('uniqid');

		if ( 'undefined' === typeof UM.fields_groups.field.conditional.fieldsList[ uniqid ] ) {
			return;
		}

		let ruleKey = $(this).val();
		let fieldsRow = $(this).closest('.um-conditional-rule-fields');
		let fieldID = fieldsRow.find('.um-conditional-rule-field-col .um-conditional-rule-setting').val();

		let fieldRowOrder = $('.um-field-row[data-field="' + fieldID + '"]').children('.um-field-row-order').val();

		if ( '' === ruleKey || '!=empty' === ruleKey || '==empty' === ruleKey ) {
			fieldsRow.find('.um-conditional-rule-value-col input.um-conditional-rule-setting').prop('disabled', true).val('').show();
			fieldsRow.find('.um-conditional-rule-value-col select.um-conditional-rule-setting').prop('disabled', true).val('').hide();
		} else {
			if ( null === UM.fields_groups.field.conditional.fieldsList[ uniqid ][fieldRowOrder].valueOptions ) {
				fieldsRow.find('.um-conditional-rule-value-col input.um-conditional-rule-setting').prop('disabled', false).val('').show();
				fieldsRow.find('.um-conditional-rule-value-col select.um-conditional-rule-setting').prop('disabled', true).val('').hide();
			} else {
				fieldsRow.find('.um-conditional-rule-value-col input.um-conditional-rule-setting').prop('disabled', true).val('').hide();
				fieldsRow.find('.um-conditional-rule-value-col select.um-conditional-rule-setting').prop('disabled', false).val('').show();
			}
		}

		//UM.fields_groups.field.prepareSettings($);
	});

	/* END - Conditional Fields handlers */

	// Changing fields' settings form fields except Field Type.
	$(document.body).on('change','.um-forms-field:not(.um-field-row-type-select)', function(e){
		let row = $(this).closest('.um-field-row');
		let fieldID = row.data('field');
		let settingsTab = $(this).closest('div[data-tab]').data('tab');

		UM.fields_groups.field.prepareSettings($, fieldID, settingsTab);
	});

	// Field Type field handlers.
	$(document.body).on('focusin', '.um-field-row-type-select', function(){
		let row = $(this).closest('.um-field-row');
		row.data('previousValue', $(this).val());
	}).on('change','.um-field-row-type-select', function(){
		if ( $(this).hasClass('disabled') ) {
			return;
		}

		$(this).addClass('disabled').prop('disabled', true);
		let row = $(this).closest('.um-field-row');
		UM.fields_groups.field.changeType(row,$);
	});

	$(document.body).on('change','.um-field-row-title-input', function(e){
		let title = $(this).val();
		$(this).closest('.um-field-row').find( '> .um-field-row-header > .um-field-row-title' ).text( UM.fields_groups.field.sanitizeInput(title) );

		let metakeyField = $(this).closest('.um-form-table').find('> tbody > tr.um-forms-line[data-field_id="meta_key"] .um-field-row-metakey-input');
		if ( '' === metakeyField.val() ) {
			metakeyField.val( wp.url.cleanForSlug( title ) ).trigger('change');
		}

		UM.fields_groups.field.conditional.prepareFieldsList($);
	});

	$(document.body).on('change','.um-field-row-metakey-input', function(e){
		let metakey = wp.i18n.__( '(no metakey)', 'ultimate-member' );
		if ( '' !== $(this).val() ) {
			metakey = $(this).val();
		}

		$(this).closest('.um-field-row').find( '> .um-field-row-header > .um-field-row-metakey' ).text( UM.fields_groups.field.sanitizeInput(metakey) );
	});





	/* Handlers for options */

	// todo optgroup using for the select field type
	/*$( document.body ).on('change', '.um-admin-option-enable-optgroup', function(e){
		let rows = $(this).parents('.um-admin-option-bulk-enable-optgroup-wrapper').siblings('.um-admin-option-rows').find('.um-admin-option-row');

		let trigger_confirm = false;
		if ( rows.length > 1 ) {
			trigger_confirm = true;
		} else {
			if ( '' !== rows.first().find('.um-admin-option-key').val() || '' !== rows.first().find('.um-admin-option-val').val() ) {
				trigger_confirm = true;
			}
		}

		let change_groups = false;
		if ( trigger_confirm ) {
			if ( confirm( wp.i18n.__( 'All current option will be removed. Are you sure you want to change the options using?', 'ultimate-member' ) ) ) {
				change_groups = true;
			} else {
				if ( $(this).is(':checked') ) {
					$(this).prop('checked', false);
				} else {
					$(this).prop('checked', true);
				}
			}
		} else {
			change_groups = true;
		}

		let addOptGroupButton = $(this).parents('.um-admin-option-bulk-add-wrapper').siblings('.um-admin-option-group-row-add-wrapper').find('.um-admin-option-group-row-add');
		let presetsToggle = $(this).parents('.um-admin-option-bulk-add-wrapper').find('.um-admin-option-bulk-toggle');

		if ( change_groups ) {
			if ( $(this).is(':checked') ) {
				rows.each( function() {
					$(this).find('.um-admin-option-row-remove').trigger('click');
				});

				addOptGroupButton.show();
				let bulk_list = $(this).parents('.um-admin-option-bulk-add-wrapper').siblings('.um-admin-option-bulk-add-list');
				if ( bulk_list.is(':visible') ) {
					presetsToggle.trigger('click');
				}

				presetsToggle.hide();
			} else {
				addOptGroupButton.hide();
				presetsToggle.show();
			}
		}
	});*/

	$( document.body ).on('click', '.um-admin-option-bulk-toggle', function() {
		let bulk_list = $(this).parents('.um-admin-option-bulk-add-wrapper').siblings('.um-admin-option-bulk-add-list');

		if ( bulk_list.is(':visible') ) {
			bulk_list.slideUp();
			$(this).text($(this).data('show-label'));
		} else {
			bulk_list.slideDown();
			$(this).text($(this).data('hide-label'));
		}
	});

	$( document.body ).on('click', '.um-admin-option-bulk-add', function(){
		if ( $(this).hasClass('disabled') ) {
			return;
		}

		let bulk_value = $(this).data('bulk_value');
		let bulkAddList = $(this).parents('.um-admin-option-bulk-add-list');
		let rowsList = $(this).parents('.um-admin-option-bulk-add-list').siblings('.um-admin-option-rows');
		let rows = $(this).parents('.um-admin-option-bulk-add-list').siblings('.um-admin-option-rows').find('.um-admin-option-row');

		let trigger_confirm = false;
		if ( rows.length > 1 ) {
			trigger_confirm = true;
		} else {
			if ( '' !== rows.first().find('.um-admin-option-key').val() || '' !== rows.first().find('.um-admin-option-val').val() ) {
				trigger_confirm = true;
			}
		}

		let run_bulk = false;
		if ( trigger_confirm ) {
			if ( confirm( wp.i18n.__( 'All current option will be removed. Are you sure you want to fill option by this preset?', 'ultimate-member' ) ) ) {
				run_bulk = true;
			} else {
				return;
			}
		} else {
			run_bulk = true;
		}

		if ( run_bulk ) {
			rows.hide();

			let skeletonCount = rows.length > 6 ? 6 : rows.length;
			while ( skeletonCount > 0 ) {
				let $cloned = $('.um-admin-option-row-skeleton-placeholder').clone().addClass('um-admin-option-row-skeleton').removeClass('um-admin-option-row-skeleton-placeholder');
				rowsList.prepend($cloned );
				skeletonCount --;
			}

			bulkAddList.siblings('.um-admin-option-bulk-add-wrapper').find('.um-admin-option-bulk-toggle').trigger('click');
			bulkAddList.find('.um-admin-option-bulk-add').addClass('disabled');

			wp.ajax.send( 'um_fields_groups_get_options_preset', {
				data: {
					preset: bulk_value,
					nonce: um_admin_scripts.nonce
				},
				success: function( data ) {
					rows.each( function() {
						$(this).find('.um-admin-option-row-remove').trigger('click');
					});

					let need_clicks = Object.keys(data.options).length - 1;
					while ( need_clicks > 0 ) {
						rowsList.find('.um-admin-option-row').first().find('.um-admin-option-row-add').trigger('click');
						need_clicks --;
					}

					$.each( data.options, function(i) {
						let rowIndex = Object.keys(data.options).indexOf(i);
						rowsList.find('.um-admin-option-key:eq(' + rowIndex + ')').val(i);
						rowsList.find('.um-admin-option-val:eq(' + rowIndex + ')').val(data.options[i]);
					});
					rowsList.find('.um-admin-option-row-skeleton').remove();
					rows.show();

					bulkAddList.find('.um-admin-option-bulk-add').removeClass('disabled');
				},
				error: function( data ) {
					console.error( data );
				}
			});
		}
	});


	$( document.body ).on('change', '.um-field-row-multiple-input', function(){
		let row = $(this).closest('.um-field-row');
		um_multi_or_not($(this),row);
	});

	// for unchecking the radio
	$( document.body ).on('click', '.um-admin-option-rows .um-admin-option-default', function() {
		var val = $(this).val();
		$(this).prop('checked', (um_radioStates[val] = !um_radioStates[val]));

		$.each( $(".um-admin-option-rows .um-admin-option-default"), function(index, rd) {
			if(rd.value !== val) {
				um_radioStates[rd.value] = false;
			}
		});
	});

	$( document.body ).on('click', '.um-admin-option-row-add', function() {
		let fieldRow = $(this).parents('.um-field-row');
		let multipleInput = fieldRow.find('.um-field-row-multiple-input');

		let optionRow = $(this).parents('.um-admin-option-row');
		let optionRowsWrapper = $(this).parents('.um-admin-option-rows');

		let html = optionRowsWrapper.siblings('.um-admin-option-row-placeholder')[0].outerHTML.replace( 'um-admin-option-row-placeholder', 'um-admin-option-row' );

		let html_wrapper = $('<div>').append( html );
		html_wrapper.find('.um-admin-option-key').prop('disabled', false);
		html_wrapper.find('.um-admin-option-val').prop('disabled', false);
		html_wrapper.find('.um-admin-option-default').prop('disabled', false);
		html_wrapper.find('.um-admin-option-default-multi').prop('disabled', false);

		html_wrapper.find('.um-admin-option-row').insertAfter( optionRow );
		um_recalculate_indexes( optionRowsWrapper );
		if ( 'both' === optionRowsWrapper.data('multiple') ) {
			um_multi_or_not( multipleInput, fieldRow );
		}
		um_admin_init_draggable( optionRowsWrapper );
	});

	$( document.body ).on('click', '.um-admin-option-row-remove', function() {
		let optionRow = $(this).parents('.um-admin-option-row');
		let optionRowsWrapper = $(this).parents('.um-admin-option-rows');

		if ( $(this).parents('.um-admin-option-rows').find('.um-admin-option-row').length === 1 ) {
			optionRow.find('.um-admin-option-key').val('');
			optionRow.find('.um-admin-option-val').val('');
			optionRow.find('.um-admin-option-default').prop( "checked", false );
			optionRow.find('.um-admin-option-default-multi').prop( "checked", false );
			return;
		}

		optionRow.remove();
		um_recalculate_indexes( optionRowsWrapper );
		um_admin_init_draggable( optionRowsWrapper );
	});

	let minMaxFields = [
		['min', 'max'],
		['min_chars', 'max_chars'],
		['min_size', 'max_size'],
		['min_width', 'max_width'],
		['min_height', 'max_height'],
		['min_rows', 'max_rows'],
	];

	for ( let i = 0; i < minMaxFields.length; i++ ) {
		let minData = minMaxFields[ i ][0];
		let maxData = minMaxFields[ i ][1];

		$( document.body ).on('change', '.um-forms-field[type="number"][data-field_id="' + minData + '"]', function() {
			let maxField = $(this).closest( '.um-form-table' ).find('.um-forms-field[type="number"][data-field_id="' + maxData + '"]');
			let minValue = parseFloat( $(this).val() );
			let maxValue = parseFloat( maxField.val() );

			if ( '' === $(this).val() ) {
				maxField.removeAttr( 'min' );
			} else {
				maxField.attr( 'min', minValue );

				if ( '' !== maxField.val() ) {
					if ( minValue > maxValue ) {
						maxField.val( minValue );
						$(this).attr('max', minValue);
					}
				}
			}
		});

		$( document.body ).on('change', '.um-forms-field[type="number"][data-field_id="' + maxData + '"]', function() {
			let minField = $(this).closest( '.um-form-table' ).find('.um-forms-field[type="number"][data-field_id="' + minData + '"]');
			let maxValue = parseFloat( $(this).val() );
			let minValue = parseFloat( minField.val() );

			if ( '' === $(this).val() ) {
				minField.removeAttr( 'max' );
			} else {
				minField.attr( 'max', maxValue );
				if ( '' !== minField.val() ) {
					if ( maxValue < minValue ) {
						minField.val( maxValue );
						$(this).attr('min', maxValue);
					}
				}
			}
		});
	}

	$( document.body ).on('change', '.um-forms-field[type="date"][data-field_id="min"]', function() {
		let maxField = $(this).closest( '.um-form-table' ).find('.um-forms-field[type="date"][data-field_id="max"]');
		let minValue = $(this).val();
		let maxValue = maxField.val();

		if ( '' === minValue ) {
			maxField.removeAttr( 'min' );
		} else {
			maxField.attr( 'min', minValue );
			if ( '' !== maxValue ) {
				if ( Date.parse( minValue ) > Date.parse( maxValue ) ) {
					maxField.val( minValue );
					$(this).attr('max', minValue);
				}
			}
		}
	});

	$( document.body ).on('change', '.um-forms-field[type="date"][data-field_id="max"]', function() {
		let minField = $(this).closest( '.um-form-table' ).find('.um-forms-field[type="date"][data-field_id="min"]');
		let maxValue = $(this).val();
		let minValue = minField.val();

		if ( '' === maxValue ) {
			minField.removeAttr( 'max' );
		} else {
			minField.attr( 'max', maxValue );
			if ( '' !== minValue ) {
				if ( Date.parse( maxValue ) < Date.parse( minValue ) ) {
					minField.val( maxValue );
					$(this).attr('min', maxValue);
				}
			}
		}
	});

	// handle errors on the first loading
	let $noticeObj = $('#message[data-error-field]');
	if ( $noticeObj.length > 0 ) {
		let scrollToID = $noticeObj.data('error-field');
		let fieldObj = $( '#' + scrollToID );
		if ( ! fieldObj.is(':visible') ) {
			fieldObj.closest('.um-field-row:not(.um-field-row-edit-mode)').find('> .um-field-row-header > .um-field-row-actions > .um-field-row-action-edit').trigger('click');
		}
		if ( ! fieldObj.is(':visible') ) {
			let tab = fieldObj.closest('.um-form-table').parent().data('tab');
			fieldObj.closest('.um-field-row.um-field-row-edit-mode').find('> .um-field-row-content > .um-field-row-tabs > div[data-tab="' + tab + '"]').trigger('click');
		}
		fieldObj.addClass( 'um-error' );
		$noticeObj[0].scrollIntoView();
	}
});
