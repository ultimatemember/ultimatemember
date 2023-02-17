// wp-admin scripts that must be enqueued on Fields Groups list table and individual Field Group page

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
			$('.um-fields-groups-fields-wrapper').sortable({
				items: '.um-fields-groups-field-row',
				connectWith: 'um-fields-groups-fields-wrapper',
				placeholder: 'um-fields-groups-field-placeholder',
				forcePlaceholderSize:true,
				axis: 'y',
				cursor: 'move',
				handle: '.um-fields-groups-field-move-link',
				update: function(event, ui){

					// jQuery('#publish').attr('disabled','disabled');
					// jQuery('#save').attr('disabled','disabled');
					//
					// if ( ui.item.hasClass('um-field-type-group') && ui.item.parents('.um-field-type-group').length > 0  ) {
					//
					// 	jQuery('.um-admin-drag-col,.um-admin-drag-group').sortable('cancel');
					//
					// 	jQuery('#publish').prop('disabled', false);
					// 	jQuery('#save').prop('disabled', false);
					//
					// } else {
					//
					// 	UM_Change_Field_Col();
					//
					// 	UM_Change_Field_Grp();
					//
					// 	UM_Rows_Refresh();
					//
					// }

				}
			});
		},
		destroy: function ($) {
			$('.um-fields-groups-fields-wrapper').sortable('destroy');
		},
		reInit: function ($) {
			UM.fields_groups.sortable.destroy($);
			UM.fields_groups.sortable.init($);
		}
	},
	field: {
		add: function ( $ ) {
			let $wrapper = $('.um-fields-groups-fields-wrapper');
			let $cloned = $('.um-fields-groups-field-row-template').clone().addClass('um-fields-groups-field-row').removeClass('um-fields-groups-field-row-template');
			$wrapper.append( $cloned );

			if ( $wrapper.find('.um-fields-groups-field-row').length > 1 ) {
				UM.fields_groups.sortable.reInit($);
			} else {
				$wrapper.removeClass('hidden');
				$wrapper.siblings('.um-fields-groups-fields-wrapper-empty').addClass('hidden');
				UM.fields_groups.sortable.init($);
			}
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
			let $cloned = obj.parents('.um-fields-groups-field-row').clone();
			obj.parents('.um-fields-groups-field-row').after( $cloned );
			UM.fields_groups.sortable.reInit($);
		},
		delete: function ( obj, $ ) {
			let $wrapper = obj.parents('.um-fields-groups-fields-wrapper');
			obj.parents('.um-fields-groups-field-row').remove();

			if ( ! $wrapper.find('.um-fields-groups-field-row').length ) {
				$wrapper.addClass('hidden');
				$wrapper.siblings('.um-fields-groups-fields-wrapper-empty').removeClass('hidden');
				UM.fields_groups.sortable.destroy($);
			} else {
				UM.fields_groups.sortable.reInit($);
			}
		},
		conditional: {
			reset: function() {

			}
		}
	}
};

jQuery( function($) {
	UM.fields_groups.sortable.init($);

	$(document.body).on('click','.um-fields-groups-field-row', function(e){
		e.preventDefault();
		if ( typeof e.target !== 'undefined' && e.target.classList.contains('um-fields-groups-field-row-header') ) {
			UM.fields_groups.field.toggleEdit($(this),$);
		}
	});

	$(document.body).on('click','.um-fields-groups-field-edit', function(e){
		e.preventDefault();
		let row = $(this).parents('.um-fields-groups-field-row');
		UM.fields_groups.field.toggleEdit(row,$);
	});

	$(document.body).on('click','.um-fields-groups-field-delete', function(e){
		e.preventDefault();
		UM.fields_groups.field.delete($(this),$);
	});

	$(document.body).on('click','.um-fields-groups-field-duplicate', function(e){
		e.preventDefault();
		UM.fields_groups.field.duplicate($(this),$);
	});

	$(document.body).on('click','.um-add-fields-groups-field', function(e){
		e.preventDefault();
		UM.fields_groups.field.add($);
	});

	$(document.body).on('click','.um-edit-field-tabs > div[data-tab]:not(.current)', function(e){
		e.preventDefault();
		UM.fields_groups.tabs.setActive($(this),$);
	});

	$(document.body).on('click','.um-fields-groups-field-reset-all-conditions', function(e){
		e.preventDefault();
		UM.fields_groups.tabs.setActive($(this),$);
	});
});
