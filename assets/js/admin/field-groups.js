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
			let currentTab = row.find('.um-field-row-tabs > div[data-tab].current').data('tab');
			row.find('.um-field-row-tabs > div[data-tab]').remove();

			row.find('.um-field-row-tabs-content > div[data-tab]').each( function() {
				if ( ! newSettingsTabs.includes( $(this).data('tab') ) ) {
					$(this).remove();
				}
			});

			let newTabsHTML = '';
			for (var i = 0; i < newSettingsTabs.length; i++) {
				newTabsHTML += '<div data-tab="' + newSettingsTabs[i] + '">' + um_admin_field_groups_data.field_tabs[newSettingsTabs[i]] + '</div>';

				if ( ! row.find('.um-field-row-tabs-content > div[data-tab="' + newSettingsTabs[i] + '"]').length ) {
					row.find('.um-field-row-tabs-content').append('<div data-tab="' + newSettingsTabs[i] + '"></div>');
				}
			}
			row.find('.um-field-row-tabs').html(newTabsHTML);
			row.find('.um-field-row-tabs > div[data-tab="' + currentTab + '"]').addClass('current');

			row.find('.um-field-row-tabs-content > div[data-tab="' + currentTab + '"]').addClass('current');
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
					$(this).find('.um-field-row').each( function(i) {
						let index = i * 1 + 1;
						$(this).find('.um-field-row-order').val(index);
						$(this).find('.um-field-row-header .um-field-row-move-link').html(index);
					});
				}
			}).on('sortupdate',function(){
				// $(this) means sorting wrapper block
				$(this).find('.um-field-row').each( function(i) {
					let index = i * 1 + 1;
					$(this).find('.um-field-row-order').val(index);
					$(this).find('.um-field-row-header .um-field-row-move-link').html(index);
				});
			});
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
			$('.um-fields-column-content').find('.um-field-row').each( function() {
				let id = $(this).data('field'); // id
				let type = $(this).find('.um-field-row-type-select').val(); // type

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

				$(this).find('.um-field-row-tabs > div[data-tab]').each(function () {
					let tabKey = $(this).data('tab');

					if ( typeof settingsTab !== 'undefined' ) {
						if ( settingsTab != tabKey ) {
							return;
						}
					}

					UM.fields_groups.field.settingsScreens[ id ][ type ][ tabKey ] = $(this).parents('.um-field-row-tabs').siblings( '.um-field-row-tabs-content' ).find( 'div[data-tab="' + tabKey + '"] > .form-table' )[0].outerHTML;
				});
			});
		},
		add: function ( button,$ ) {
			let $wrapper = button.closest('.um-fields-column').find('.um-fields-column-content');
			let $cloned = button.closest('.um-fields-column').siblings('.um-field-row-template').clone().addClass('um-field-row').removeClass('um-field-row-template');

			UM.fields_groups.field.newIndex ++;
			let newIndex = UM.fields_groups.field.newIndex;
			$cloned.data( 'field', 'new_' + newIndex ).attr( 'data-field', 'new_' + newIndex );

			let newOrderIndex = $wrapper.find( '.um-field-row' ).length + 1;
			$cloned.find('.um-field-row-move-link').html( newOrderIndex );

			let fieldID = $cloned.find('.um-field-row-id');
			fieldID.removeAttr('disabled').prop('disabled', false);
			let fieldIDName = fieldID.attr('name');
			if ( 'undefined' !== typeof fieldIDName ) {
				let newName = fieldIDName.replace( '\{index\}', newIndex );
				fieldID.attr('name', newName);
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

			if ( $wrapper.find('.um-field-row').length > 1 ) {
				UM.fields_groups.sortable.reInit($wrapper,$);
			} else {
				$wrapper.removeClass('hidden');
				$wrapper.siblings('.um-fields-column-header').removeClass('hidden');
				$wrapper.siblings('.um-fields-column-empty-content').addClass('hidden');
				UM.fields_groups.sortable.init($wrapper,$);
			}

			UM.fields_groups.field.conditional.prepareFieldsList($);
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
			if ( row.find('.um-field-row-multiple-input').length ) {
				row.find('.um-field-row-multiple-input').trigger('change');
			}
		},
		hideEdit: function ( row, $ ) {
			row.removeClass('um-field-row-edit-mode');
		},
		duplicate: function ( obj, $ ) {
			let $wrapper = obj.closest('.um-fields-column-content');
			let $cloned = obj.closest('.um-field-row').clone();
			obj.closest('.um-field-row').after( $cloned );
			UM.fields_groups.sortable.reInit($wrapper,$);
			UM.fields_groups.field.conditional.prepareFieldsList($);
			//UM.fields_groups.field.prepareSettings($);
		},
		delete: function ( obj, $ ) {
			let $wrapper = obj.closest('.um-fields-column-content');
			obj.closest('.um-field-row').remove();

			if ( ! $wrapper.find('.um-field-row').length ) {
				$wrapper.addClass('hidden');
				$wrapper.siblings('.um-fields-column-header').addClass('hidden');
				$wrapper.siblings('.um-fields-column-empty-content').removeClass('hidden');
				UM.fields_groups.sortable.destroy($wrapper);
			} else {
				UM.fields_groups.sortable.reInit($wrapper,$);
			}
			UM.fields_groups.field.conditional.prepareFieldsList($);
			//UM.fields_groups.field.prepareSettings($);
		},
		conditional: {
			fieldsList: {}, /* format id:{title,type}*/
			prepareFieldsList: function($) {
				$('.um-fields-column-content').find('.um-field-row').each( function() {
					let id = $(this).data('field'); // id
					let type = $(this).find('.um-field-row-type-select').val(); // type
					let title = $(this).find('.um-field-row-title-input').val(); // title

					if ( um_admin_field_groups_data.field_types[type].conditional_rules.length ) {
						let rules = {};
						um_admin_field_groups_data.field_types[type].conditional_rules.forEach((element) => {
							rules[element] = um_admin_field_groups_data.conditional_rules[ element ];
						});

						let valueOptions = null;
						if ( 'choice' === um_admin_field_groups_data.field_types[type].category ) {
							valueOptions = {};
							// @todo get options from field settings
							//$(this).find('.um-field-row-type-select').val()
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

				UM.fields_groups.field.prepareSettings($);
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

				UM.fields_groups.field.prepareSettings($);
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

				UM.fields_groups.field.prepareSettings($);
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
			let oldType = UM.fields_groups.field.conditional.fieldsList[fieldID].type;
			let oldTypeData = um_admin_field_groups_data.field_types[oldType];
			let oldTypeSettings = oldTypeData.settings;

			let type = row.find('.um-field-row-type-select').val();
			let typeData = um_admin_field_groups_data.field_types[type];

			let typeSettings = typeData.settings;

			let currentSettingsTabs = Object.keys(oldTypeSettings);
			let newSettingsTabs = Object.keys(typeSettings);

			let typeHTML = row.find('.um-field-row-type-select option[value="' + row.find('.um-field-row-type-select').val() + '"]').html();
			row.find( '.um-field-row-type' ).html( typeHTML ); // change field type in a row header

			// add/remove necessary/unnecessary tabs and tab-content blocks
			if ( ! UM.fields_groups.tabs.compare( currentSettingsTabs, newSettingsTabs ) ) {
				UM.fields_groups.tabs.reBuild( row, newSettingsTabs, $ );
			}

			row.find('.um-field-row-tabs > div[data-tab]').addClass('disabled');

			if ( typeof ( UM.fields_groups.field.settingsScreens[ fieldID ] ) === 'object' &&  typeof ( UM.fields_groups.field.settingsScreens[ fieldID ][ type ] ) === 'object' ) {
				$.each( UM.fields_groups.field.settingsScreens[ fieldID ][ type ], function ( tab, html ) {
					let tabSettingsWrapper = row.find( '.um-field-row-tabs-content > div[data-tab="' + tab + '"]' );

					let temporaryScreenObj = $('<div>').append( html ).find('.form-table');

					if ( 'conditional' === tab ) {
						// make conditional rules static always
						temporaryScreenObj.find('[data-field_type="conditional_rules"]').html( tabSettingsWrapper.find('[data-field_type="conditional_rules"]').html() );
					}

					tabSettingsWrapper.find('.um-field-row-static-setting').each(function () {
						temporaryScreenObj.find( '[name="' + $(this).attr('name') + '"]' ).val( $(this).val() );
					});

					// change HTML based on type
					row.find( '.um-field-row-tabs-content > div[data-tab="' + tab + '"]' ).html( temporaryScreenObj[0].outerHTML );
				});

				row.find('.um-field-row-type-select').removeClass('disabled').prop('disabled', false);

				run_check_conditions();
				UM.fields_groups.field.conditional.prepareFieldsList($);

				row.find('.um-field-row-tabs > div[data-tab]').removeClass('disabled');

				if ( row.find('.um-field-row-multiple-input').length ) {
					row.find('.um-field-row-multiple-input').trigger('change');
				}
			} else {
				if ( ! UM.fields_groups.tabs.compareDeep( oldTypeSettings.general, typeSettings.general ) ) {
					row.find( '.um-field-row-tabs-content > div[data-tab="general"] .um-forms-field:not(.um-field-row-static-setting)' ).parents('.um-forms-line').remove();
					let skeletonRows = Object.keys( typeSettings.general ).length - 2;
					if ( skeletonRows > 0 ) {
						let i = 0;
						while (i < skeletonRows) {
							row.find( '.um-field-row-tabs-content > div[data-tab="general"] > .form-table tbody' ).append('<tr class="um-forms-line um-forms-skeleton"><th><span class="um-skeleton-box" style="width:100%;height:20px;"></span></th><td><span class="um-skeleton-box" style="width:100%;height:40px;margin-bottom:4px;"></span><span class="um-skeleton-box" style="width:100%;height:14px;"></span></td></tr>');
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

							let tabSettingsWrapper = row.find( '.um-field-row-tabs-content > div[data-tab="' + tab + '"]' );

							if ( 'conditional' === tab ) {
								// make conditional rules static always
								temporaryScreenObj.find('[data-field_type="conditional_rules"]').html( tabSettingsWrapper.find('[data-field_type="conditional_rules"]').html() );
							}

							tabSettingsWrapper.find('.um-field-row-static-setting').each(function () {
								temporaryScreenObj.find( '[name="' + $(this).attr('name') + '"]' ).val( $(this).val() );
							});

							// change HTML based on type
							tabSettingsWrapper.html( temporaryScreenObj[0].outerHTML );
						});

						row.find('.um-field-row-type-select').removeClass('disabled').prop('disabled', false);

						run_check_conditions();

						UM.fields_groups.field.conditional.prepareFieldsList($);

						row.find('.um-field-row-tabs > div[data-tab]').removeClass('disabled');

						if ( row.find('.um-field-row-multiple-input').length ) {
							row.find('.um-field-row-multiple-input').trigger('change');
						}
					},
					error: function( data ) {
						console.error( data );
					}
				});
			}
		}
	},
};

jQuery( function($) {
	if ( $('#message[data-error-field]').length > 0 ) {
		let scrollToID = $('#message[data-error-field]').data('error-field');
		$( '#' + scrollToID ).addClass( 'um-error' );
		$('#message[data-error-field]')[0].scrollIntoView();
	}

	UM.fields_groups.field.conditional.prepareFieldsList($);

	$('.um-fields-column-content').find('.um-field-row').each( function() {
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

	UM.fields_groups.field.prepareSettings($);
	console.log( UM.fields_groups.field.settingsScreens );


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
	$('.um-fields-column-content').each( function () {
		UM.fields_groups.sortable.init($(this),$);
	});

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

	$(document.body).on('click','.um-field-row', function(e){
		if ( typeof e.target !== 'undefined' && e.target.classList.contains('um-field-row-toggle-edit') ) {
			e.preventDefault();
			UM.fields_groups.field.toggleEdit($(this),$);
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

	$(document.body).on('change','.um-conditional-rule-field-col select, .um-conditional-rule-condition-col select, .um-conditional-rule-value-col select,.um-conditional-rule-value-col input', function(e){
		let conditionsTab = $(this).parents('.um-field-row-tabs-content > div[data-tab="conditional"]');
		if ( UM.fields_groups.field.conditional.isEmpty(conditionsTab,$) ) {
			UM.fields_groups.field.conditional.hideReset($(this),$);
		} else {
			UM.fields_groups.field.conditional.showReset($(this),$);
		}

		//UM.fields_groups.field.prepareSettings($);
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
		//UM.fields_groups.field.prepareSettings($);
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

		//UM.fields_groups.field.prepareSettings($);
	});

	$('.um-conditional-rules-wrapper .um-conditional-rule-condition-col .um-conditional-rule-setting').trigger('change');

	$(document.body).on('change','.um-field-row-title-input', function(e){
		$(this).closest('.um-field-row').find( '.um-field-row-title' ).html( $(this).val() );
		UM.fields_groups.field.conditional.prepareFieldsList($);
	});

	$(document.body).on('change','.um-forms-field:not(.um-field-row-type-select)', function(e){
		let row = $(this).closest('.um-field-row');
		let fieldID = row.data('field');
		let settingsTab = $(this).closest('div[data-tab]').data('tab');

		UM.fields_groups.field.prepareSettings($, fieldID, settingsTab);
	});

	$(document.body).on('change','.um-field-row-type-select', function(e){
		if ( $(this).hasClass('disabled') ) {
			return;
		}

		$(this).addClass('disabled').prop('disabled', true);
		let row = $(this).closest('.um-field-row');
		UM.fields_groups.field.changeType(row,$);
	});

	$(document.body).on('change','.um-field-row-metakey-input', function(e){
		let metakey = wp.i18n.__( '(no metakey)', 'ultimate-member' );
		if ( '' !== $(this).val() ) {
			metakey = $(this).val();
		}
		$(this).closest('.um-field-row').find( '.um-field-row-metakey' ).html( metakey );
	});

	/* Handlers for options */

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
		//var html = $('.um-admin-option-row-placeholder')[0].outerHTML.replace( 'um-admin-option-row-placeholder', 'um-admin-option-row' );

		let html_wrapper = $('<div>').append( html );
		html_wrapper.find('.um-admin-option-key').prop('disabled', false);
		html_wrapper.find('.um-admin-option-val').prop('disabled', false);
		html_wrapper.find('.um-admin-option-default').prop('disabled', false);
		html_wrapper.find('.um-admin-option-default-multi').prop('disabled', false);

		html_wrapper.find('.um-admin-option-row').insertAfter( optionRow );
		um_recalculate_indexes( optionRowsWrapper );
		um_multi_or_not( multipleInput, fieldRow );
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
});
