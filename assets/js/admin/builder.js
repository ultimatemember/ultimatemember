if ( typeof (window.UM) !== 'object' ) {
	window.UM = {};
}

if ( typeof (window.UM.admin) !== 'object' ) {
	window.UM.admin = {};
}

UM.admin.builder = {
	deleteProcess: [],
	fieldsToDelete: [],
	fieldConditions: {
		refresh: function() {
			let $conditionalRows = jQuery('.um-adm-conditional');
			if ( $conditionalRows.length > 0 ) {
				$conditionalRows.each( function() {
					jQuery(this).trigger('change');
				});
			}
		}
	},
	tinyMCE: {
		editor: {},
		init: function ( id, content ) {
			var object = jQuery('#' + id);

			if ( typeof( tinyMCE ) === 'object' && tinyMCE.get( id ) !== null ) {
				tinyMCE.triggerSave();
				tinyMCE.EditorManager.execCommand( 'mceRemoveEditor', true, id );
				"4" === tinyMCE.majorVersion ? window.tinyMCE.execCommand( "mceRemoveEditor", !0, id ) : window.tinyMCE.execCommand( "mceRemoveControl", !0, id );
				UM.admin.builder.tinyMCE.editor = jQuery('<div>').append( object.parents( '#wp-' + id + '-wrap' ).clone() );
				object.parents('#wp-' + id + '-wrap').replaceWith('<div class="um_tiny_placeholder"></div>');
				jQuery('.um-admin-editor:visible').html( jQuery( UM.admin.builder.tinyMCE.editor ).html() );

				var init;
				if( typeof tinyMCEPreInit.mceInit[ id ] == 'undefined' ){
					init = tinyMCEPreInit.mceInit[ id ] = tinyMCE.extend( {}, tinyMCEPreInit.mceInit[ id ] );
				} else {
					init = tinyMCEPreInit.mceInit[ id ];
				}
				if ( typeof(QTags) == 'function' ) {
					QTags( tinyMCEPreInit.qtInit[ id ] );
					QTags._buttonsInit();
				}
				if ( typeof( window.switchEditors ) === 'object' ) {
					window.switchEditors.go( id );
				}
				tinyMCE.init( init );
				tinyMCE.get( id ).setContent( content );
				object.html( content );
			} else {
				UM.admin.builder.tinyMCE.editor = jQuery('<div>').append( object.parents('#wp-' + id + '-wrap').clone() );
				object.parents('#wp-' + id + '-wrap').replaceWith('<div class="um_tiny_placeholder"></div>');

				jQuery('.um-admin-editor:visible').html( jQuery( UM.admin.builder.tinyMCE.editor ).html() );

				if ( typeof(QTags) == 'function' ) {
					QTags( tinyMCEPreInit.qtInit[ id ] );
					QTags._buttonsInit();
				}

				//use duplicate because it's new element
				jQuery('#' + id).html( content );
			}

			jQuery( 'body' ).on( 'click', '.wp-switch-editor', function() {
				var target = jQuery(this);

				if ( target.hasClass( 'wp-switch-editor' ) && typeof( window.switchEditors ) === 'object' ) {
					var mode = target.hasClass( 'switch-tmce' ) ? 'tmce' : 'html';
					window.switchEditors.go( id, mode );
				}
			});
		}
	},
	previewResize: function() {
		if ( jQuery('.um-admin-modal-body > .um').length ) {
			jQuery('.um-admin-modal-body > .um').each(function(){

				element_width = jQuery(this).width();

				if ( element_width <= 340 ) {

					jQuery(this).removeClass('uimob340');
					jQuery(this).removeClass('uimob500');
					jQuery(this).removeClass('uimob800');
					jQuery(this).removeClass('uimob960');

					jQuery(this).addClass('uimob340');

				} else if ( element_width <= 500 ) {

					jQuery(this).removeClass('uimob340');
					jQuery(this).removeClass('uimob500');
					jQuery(this).removeClass('uimob800');
					jQuery(this).removeClass('uimob960');

					jQuery(this).addClass('uimob500');

				} else if ( element_width <= 800 ) {

					jQuery(this).removeClass('uimob340');
					jQuery(this).removeClass('uimob500');
					jQuery(this).removeClass('uimob800');
					jQuery(this).removeClass('uimob960');

					jQuery(this).addClass('uimob800');

				} else if ( element_width <= 960 ) {

					jQuery(this).removeClass('uimob340');
					jQuery(this).removeClass('uimob500');
					jQuery(this).removeClass('uimob800');
					jQuery(this).removeClass('uimob960');

					jQuery(this).addClass('uimob960');

				} else if ( element_width > 960 ) {

					jQuery(this).removeClass('uimob340');
					jQuery(this).removeClass('uimob500');
					jQuery(this).removeClass('uimob800');
					jQuery(this).removeClass('uimob960');

				}

				jQuery(this).css('opacity',1);
			});

			jQuery('.um-admin-modal-body .um-cover, .um-admin-modal-body .um-cover-e').each(function(){
				var elem = jQuery(this);
				var ratio = elem.data('ratio');
				var width = elem.width();
				var ratios = ratio.split(':');

				calcHeight = Math.round( width / ratios[0] ) + 'px';
				elem.height( calcHeight );
				elem.find('.um-cover-add').height( calcHeight );
			});
		}
	}
}

jQuery(window).on( 'resize', function() {
	UM.admin.builder.previewResize();
});

wp.hooks.addAction( 'um_admin_modal_success_result', 'um_admin_builder', function( $adminModal, act_id ) {
	UM.admin.builder.fieldConditions.refresh();

	jQuery( "#_custom_dropdown_options_source" ).trigger('blur');

	if ( jQuery('.um-admin-editor:visible').length > 0 ) {

		if ( 'um_admin_edit_field_popup' === act_id ) {
			UM.admin.builder.tinyMCE.init( 'um_editor_edit', jQuery('.um-admin-modal:visible .dynamic-mce-content').html() );
		} else {
			UM.admin.builder.tinyMCE.init( 'um_editor_new', '' );
		}

	}

	if ( 'um_admin_preview_form' === act_id ) {
		// Make responsive script only when live preview.
		UM.admin.builder.previewResize();

		if ( typeof( jQuery.fn.um_raty ) === 'function' ) {
			jQuery('.um-rating').um_raty({
				half:       false,
				starType:   'i',
				number:     function() {
					return jQuery(this).attr('data-number');
				},
				score:      function() {
					return jQuery(this).attr('data-score');
				},
				scoreName:  function() {
					return jQuery(this).attr('data-key');
				},
				hints:      false,
			});
		}

		// Fix for overlay in scrollable preview modal.
		let $previewOverlay = jQuery('.um-admin-preview-overlay');
		$previewOverlay.css('height', $previewOverlay.siblings('.um').outerHeight(true)*1 + 20 + 'px' );
	}

	UM.admin.colorPicker.init();
	UM.common.datetimePicker.init();
	UM.admin.iconSelector.init();
});

wp.hooks.addAction( 'um_admin_modal_resize', 'um_admin_builder', function() {
	if ( jQuery('#UM_preview_form .um-s1').length ) {
		jQuery("#UM_preview_form .um-s1").css({'display':'block'}).select2({
			allowClear: true
		});
	}

	if ( jQuery('#UM_preview_form .um-s2').length ) {
		jQuery("#UM_preview_form .um-s2").css({'display':'block'}).select2({
			allowClear: false,
			minimumResultsForSearch: 10
		});
	}
});

wp.hooks.addAction( 'um_admin_modal_remove', 'um_admin_builder', function() {
	if ( jQuery('.um-admin-editor:visible').length > 0 ) {
		tinyMCE.triggerSave();

		if ( jQuery('.um-admin-modal:visible').find('form').parent().attr('id') == 'UM_edit_field' ) {
			jQuery('#wp-um_editor_edit-wrap').remove();
		} else {
			jQuery('#wp-um_editor_new-wrap').remove();
		}

		jQuery('.um_tiny_placeholder').replaceWith( jQuery( UM.admin.builder.tinyMCE.editor ).html() );
	}
});

function UM_Drag_and_Drop() {
	jQuery('.um-admin-drag-col,.um-admin-drag-group').sortable({
		items: '.um-admin-drag-fld',
		connectWith: '.um-admin-drag-col,.um-admin-drag-group',
		placeholder: "um-fld-placeholder",
		forcePlaceholderSize:true,
		update: function(event, ui){

			jQuery('#publish').attr('disabled','disabled');

			if ( ui.item.hasClass('um-field-type-group') && ui.item.parents('.um-field-type-group').length > 0  ) {

				jQuery('.um-admin-drag-col,.um-admin-drag-group').sortable('cancel');

				jQuery('#publish').prop('disabled', false);

			} else {

				UM_Change_Field_Col();

				UM_Change_Field_Grp();

				UM_Rows_Refresh();

			}

		}
	});

	jQuery('.um-admin-drag-rowsubs').sortable({
		items: '.um-admin-drag-rowsub',
		placeholder: "um-rowsub-placeholder",
		forcePlaceholderSize:true,
		zIndex: 9999999999,
		update: function(){

			jQuery('#publish').attr('disabled','disabled');

			UM_update_subrows();

			UM_Rows_Refresh();

		}
	}).disableSelection();

	jQuery('.um-admin-drag-rowsub').sortable({
		items: '.um-admin-drag-col',
		zIndex: 9999999999,
		update: function(){

			jQuery('#publish').attr('disabled','disabled');

			row = jQuery(this);
			row.find('.um-admin-drag-col').removeClass('cols-1 cols-2 cols-3 cols-last cols-middle');
			row.find('.um-admin-drag-col').addClass('cols-' + row.find('.um-admin-drag-col').length );
			row.find('.um-admin-drag-col:last').addClass('cols-last');
			if ( row.find('.um-admin-drag-col').length == 3 ) {row.find('.um-admin-drag-col:eq(1)').addClass('cols-middle');}

			UM_Change_Field_Col();

			UM_Change_Field_Grp();

			UM_Rows_Refresh();

		}
	}).disableSelection();

	jQuery('.um-admin-drag-ajax').sortable({
		items: '.um-admin-drag-row',
		handle: ".um-admin-drag-row-start",
		zIndex: 9999999999,
		placeholder: "um-row-placeholder",
		forcePlaceholderSize:true,
		out: function(){
			UM.common.tipsy.hide();
		},
		update: function(){

			jQuery('#publish').attr('disabled','disabled');

			UM_update_rows();

			UM_Change_Field_Col();

			UM_Change_Field_Grp();

			UM_Rows_Refresh();

		}
	}).disableSelection();
}

function UM_update_rows() {
	var c = 0;
	jQuery('a[data-remove_element="um-admin-drag-row"]').remove();
	jQuery('.um-admin-drag-row').each(function(){
		c++;
		row = jQuery(this);
		if ( c != 1 ) {
			row.find('.um-admin-drag-row-icons').append( '<a href="#" class="um-tip-n" title="Delete Row" data-remove_element="um-admin-drag-row"><i class="um-faicon-trash-o"></i></a>' );
		}
	});
}

function UM_update_subrows(){
	jQuery('a[data-remove_element="um-admin-drag-rowsub"]').remove();
	jQuery('.um-admin-drag-row').each(function(){
		c = 0;
		jQuery(this).find('.um-admin-drag-rowsub').each(function(){
			c++;
			row = jQuery(this);
			if ( c != 1 ) {
				row.find('.um-admin-drag-rowsub-icons').append('<a href="#" class="um-tip-n" title="Delete Row" data-remove_element="um-admin-drag-rowsub"><i class="um-faicon-trash-o"></i></a>');
			}
		});
	});
}

function UM_Change_Field_Col(){
	jQuery('.um-admin-drag-col .um-admin-drag-fld').each(function(){
		cols =  jQuery(this).parents('.um-admin-drag-rowsub').find('.um-admin-drag-col').length;
		col = jQuery(this).parents('.um-admin-drag-col');
		if ( col.hasClass('cols-last') ) {
			if ( cols == 1 ) {
				saved_col = 1;
			}
			if ( cols == 3 ) {
				saved_col = 3;
			} else if ( cols == 2 ) {
				saved_col = 2;
			}
		} else if ( col.hasClass('cols-middle') && cols == 3 ) {
			saved_col = 2;
		} else {
			saved_col = 1;
		}

		jQuery(this).data('column', saved_col);
	});
}

function UM_Change_Field_Grp(){
	jQuery('.um-admin-drag-col .um-admin-drag-fld:not(.um-field-type-group)').each(function(){
		if ( jQuery(this).parents('.um-admin-drag-group').length == 0 ){
			jQuery(this).data('group', '');
		} else {
			jQuery(this).data('group', jQuery(this).parents('.um-admin-drag-fld.um-field-type-group').data('key') );
		}
	});
}

function UM_Rows_Refresh(){

	jQuery('.um_update_order_fields').empty();

	/* ROWS */
	var c = 0;
	jQuery('.um-admin-drag-row').each(function(){
		c++;

		row = jQuery(this);

		col_num = '';
		row.find('.um-admin-drag-rowsub').each(function(){

			subrow = jQuery(this);

			subrow.find('.um-admin-drag-col').removeClass('cols-1 cols-2 cols-3 cols-last cols-middle');
			subrow.find('.um-admin-drag-col').addClass('cols-' + subrow.find('.um-admin-drag-col').length );
			subrow.find('.um-admin-drag-col:last').addClass('cols-last');
			if ( subrow.find('.um-admin-drag-col').length == 3 ) {subrow.find('.um-admin-drag-col:eq(1)').addClass('cols-middle');}

			if ( !col_num ) {
				col_num = subrow.find('.um-admin-drag-col').length;
			} else {
				col_num = col_num + ':' + subrow.find('.um-admin-drag-col').length;
			}

		});

		jQuery('.um_update_order_fields').append('<input type="hidden" name="_um_rowcols_'+c+'_cols" id="_um_rowcols_'+c+'_cols" value="'+col_num+'" />');

		sub_rows_count = row.find('.um-admin-drag-rowsub').length;

		var origin_id = jQuery(this).attr('data-original');

		jQuery('.um_update_order_fields').append('<input type="hidden" name="_um_row_'+c+'" id="_um_row_'+c+'" value="_um_row_'+c+'" />');
		jQuery('.um_update_order_fields').append('<input type="hidden" name="_um_roworigin_'+c+'_val" id="_um_roworigin_'+c+'_val" value="'+origin_id+'" />');
		jQuery('.um_update_order_fields').append('<input type="hidden" name="_um_rowsub_'+c+'_rows" id="_um_rowsub_'+c+'_rows" value="'+sub_rows_count+'" />');

		jQuery(this).attr('data-original', '_um_row_'+c );

	});

	/* FIELDS */
	var order;
	order = 0;
	jQuery('.um-admin-drag-col .um-admin-drag-fld').each(function(){

		if ( !jQuery(this).hasClass('group') ) {
			var group = jQuery(this).data('group');
			if ( group != '' ) {
				if ( jQuery('.um-admin-drag-fld.um-field-type-group.' + group ).find('.um-admin-drag-group').find( jQuery(this) ).length == 0 ) {
					jQuery(this).appendTo(  jQuery('.um-admin-drag-fld.um-field-type-group.' + group ).find('.um-admin-drag-group') );
				} else {
					//jQuery(this).prependTo(  jQuery('.um-admin-drag-fld.um-field-type-group.' + group ).find('.um-admin-drag-group') );
				}
				jQuery('.um_update_order_fields').append('<input type="hidden" name="um_group_'+jQuery(this).data('key')+'" id="um_group_'+jQuery(this).data('key')+'" value="'+group+'" />');
			} else {
				jQuery('.um_update_order_fields').append('<input type="hidden" name="um_group_'+jQuery(this).data('key')+'" id="um_group_'+jQuery(this).data('key')+'" value="" />');
			}
		}

		order++;

		row = jQuery(this).parents('.um-admin-drag-row').index()+1;
		row = '_um_row_'+row;

		saved_col = jQuery(this).data('column');

		if ( saved_col == 3 ){
			jQuery(this).appendTo( jQuery(this).parents('.um-admin-drag-rowsub').find('.um-admin-drag-col:eq(2)') );
		}
		if ( saved_col == 2 ){
			jQuery(this).appendTo( jQuery(this).parents('.um-admin-drag-rowsub').find('.um-admin-drag-col:eq(1)') );
		}

		sub_row = jQuery(this).parents('.um-admin-drag-rowsub').index();

		jQuery('.um_update_order_fields').append('<input type="hidden" name="um_position_'+jQuery(this).data('key')+'" id="um_position_'+jQuery(this).data('key')+'" value="'+order+'" />');

		jQuery('.um_update_order_fields').append('<input type="hidden" name="um_row_'+jQuery(this).data('key')+'" id="um_row_'+jQuery(this).data('key')+'" value="'+row+'" />');

		jQuery('.um_update_order_fields').append('<input type="hidden" name="um_subrow_'+jQuery(this).data('key')+'" id="um_subrow_'+jQuery(this).data('key')+'" value="'+sub_row+'" />');

		jQuery('.um_update_order_fields').append('<input type="hidden" name="um_col_'+jQuery(this).data('key')+'" id="um_col_'+jQuery(this).data('key')+'" value="'+saved_col+'" />');

	});

	UM_Drag_and_Drop();

	UM_Add_Icon();

	jQuery.ajax({
		url: wp.ajax.settings.url,
		type: 'POST',
		data: jQuery( '.um_update_order' ).serialize(),
		success: function(){
			jQuery('#publish').prop('disabled', false);
		}
	});

}

function UM_Add_Icon(){

	var add_icon_html = '<a href="#" class="um-admin-drag-add-field um-tip-n" title="Add Field" data-modal="UM_fields" data-modal-size="normal" data-dynamic-content="um_admin_show_fields" data-arg2="'+jQuery('.um-admin-drag-ajax').data('form_id')+'" data-arg1=""><i class="um-icon-plus"></i></a>';

	jQuery('.um-admin-drag-col').each(function(){
		if ( jQuery(this).find('.um-admin-drag-add-field').length == 0 ) {
			jQuery(this).append(add_icon_html);
		} else {
			jQuery(this).find('.um-admin-drag-add-field').remove();
			jQuery(this).append(add_icon_html);
		}
	});

	jQuery('.um-admin-drag-group').each(function(){
		if ( jQuery(this).find('.um-admin-drag-add-field').length == 0 ) {
			jQuery(this).append(add_icon_html);
		} else {
			jQuery(this).find('.um-admin-drag-add-field').remove();
			jQuery(this).append(add_icon_html);
		}
	});

}

function um_builder_delete_field_ajax( callback ) {
	if ( UM.admin.builder.fieldsToDelete.length > 0 ) {
		let fieldDelete = UM.admin.builder.fieldsToDelete.shift();
		let arg1 = jQuery( fieldDelete ).find('[data-silent_action="um_admin_remove_field"]').data('arg1');
		let arg2 = jQuery( fieldDelete ).find('[data-silent_action="um_admin_remove_field"]').data('arg2');

		jQuery.ajax({
			url: wp.ajax.settings.url,
			type: 'POST',
			data: {
				action:'um_do_ajax_action',
				act_id : 'um_admin_remove_field',
				arg1 : arg1,
				arg2 : arg2,
				nonce: um_admin_scripts.nonce
			},
			success: function( data ) {
				um_builder_delete_field_ajax( callback );
			},
			error: function( data ) {
				callback();
			}
		});
	} else {
		callback();
	}
}

function um_form_select_tab( tab, set_val ) {
	var mode_block = jQuery('input#form__um_mode');
	tab.parents('.um-admin-boxed-links').find('a').removeClass('um-admin-activebg');
	tab.addClass('um-admin-activebg');

	jQuery('.um-admin div#side-sortables').show();
	jQuery('div[id^="um-admin-form"]').hide();
	jQuery('#submitdiv').show();
	jQuery('div#um-admin-form-mode,div#um-admin-form-title,div#um-admin-form-builder,div#um-admin-form-shortcode').show();
	jQuery('div[id^="um-admin-form-' + tab.data('role') + '"]').show();

	if ( set_val ) {
		mode_block.val( tab.data('role') );
	}

	jQuery('.empty-container').css({'border' : 'none'});
	jQuery('.um-admin-builder').removeClass().addClass( 'um-admin-builder ' + mode_block.val() );
}

/**
 * This function updates the builder area with fields
 *
 * @returns {boolean}
 */
function um_admin_update_builder() {
	var form_id = jQuery('.um-admin-builder').data('form_id');


	jQuery.ajax({
		url: wp.ajax.settings.url,
		type: 'POST',
		data: {
			action:'um_update_builder',
			form_id: form_id,
			nonce: um_admin_scripts.nonce
		},
		success: function( data ) {
			jQuery('.um-admin-drag-ajax').html( data );
			UM.common.tipsy.hide();

			/* trigger columns at start */
			allow_update_via_col_click = false;
			jQuery('.um-admin-drag-ctrls.columns a.active').each( function() {
				jQuery(this).trigger('click');
			}).promise().done( function(){
				allow_update_via_col_click = true;
			});

			UM_Rows_Refresh();
		},
		error: function( data ) {

		}
	});

	return false;
}

jQuery( document ).ready( function() {
	/* Default form tab */
	if ( jQuery('.um-admin-boxed-links').length > 0 ) {
		var tab = jQuery('.um-admin-boxed-links a[data-role="'+jQuery('input#form__um_mode').val()+'"]');
		um_form_select_tab( tab, false );
	}


	/* Creating new form button */
	jQuery('.um-admin-boxed-links:not(.is-core-form) a').on( 'click', function() {
		um_form_select_tab( jQuery(this), true );
	});

	jQuery('#wpfooter').hide();

	/**
	 * Conditional fields in Add/Edit form field modal.
	 */
	jQuery( document.body ).on('change', '.um-adm-conditional', function(){

		var value;
		if ( jQuery(this).attr("type") == 'checkbox' ) {
			value = jQuery(this).is(':checked') ? 1 : 0;
		} else {
			value = jQuery(this).val();
		}

		if ( jQuery(this).data('cond1') ) {
			if ( value == jQuery(this).data('cond1') ) {
				jQuery('.' + jQuery(this).data('cond1-show') ).show();
				jQuery('.' + jQuery(this).data('cond1-hide') ).hide();

				if ( jQuery(this).data('cond1-show') == '_roles' ) {
					return false;
				}

			} else {
				jQuery('.' + jQuery(this).data('cond1-show') ).hide();
				jQuery('.' + jQuery(this).data('cond1-hide') ).show();
			}
		}

		if ( jQuery(this).data('cond2') ) {
			if ( value == jQuery(this).data('cond2') ) {
				jQuery('.' + jQuery(this).data('cond2-show') ).show();
				jQuery('.' + jQuery(this).data('cond2-hide') ).hide();

				if ( jQuery(this).data('cond2-show') == '_roles' ) {
					return false;
				}

			} else {
				jQuery('.' + jQuery(this).data('cond2-show') ).hide();
				jQuery('.' + jQuery(this).data('cond2-hide') ).show();
			}
		}

		if ( jQuery(this).data('cond3') ) {
			if ( value == jQuery(this).data('cond3') ) {
				jQuery('.' + jQuery(this).data('cond3-show') ).show();
				jQuery('.' + jQuery(this).data('cond3-hide') ).hide();
			} else {
				jQuery('.' + jQuery(this).data('cond3-show') ).hide();
				jQuery('.' + jQuery(this).data('cond3-hide') ).show();
			}
		}

	});
	jQuery('.um-adm-conditional').each(function(){jQuery(this).trigger('change');});

	jQuery( document.body ).on('click', 'a[data-silent_action^="um_"]', function() {
		if ( typeof jQuery(this).attr('disabled') !== 'undefined' ) {
			return false;
		}

		var act_id = jQuery(this).data('silent_action');
		var arg1   = jQuery(this).data('arg1');
		var arg2   = jQuery(this).data('arg2');

		var in_row = '';
		var in_sub_row = '';
		var in_column = '';
		var in_group = '';

		var demon_settings = jQuery('.um-col-demon-settings');
		if ( demon_settings.data('in_column') ) {
			in_row = demon_settings.data('in_row');
			in_sub_row = demon_settings.data('in_sub_row');
			in_column = demon_settings.data('in_column');
			in_group = demon_settings.data('in_group');
		}

		UM.admin.modal.remove();
		jQuery.ajax({
			url: wp.ajax.settings.url,
			type: 'POST',
			data: {
				action:'um_do_ajax_action',
				act_id : act_id,
				arg1 : arg1,
				arg2 : arg2,
				in_row: in_row,
				in_sub_row: in_sub_row,
				in_column: in_column,
				in_group: in_group,
				nonce: um_admin_scripts.nonce
			},
			success: function( data ) {
				demon_settings.data('in_row', '').data('in_sub_row', '').data('in_column', '').data('in_group', '');
				UM.admin.modal.resize();
				um_admin_update_builder();
			},
			error: function( data ) {

			}
		});

		return false;
	});

	/* Remove field permanently */
	jQuery(document.body).on('click', '.um-admin-btns a span.remove', function(e){
		e.preventDefault();

		if ( confirm( wp.i18n.__( 'This will permanently delete this custom field from a database and from all forms on your site. Are you sure?', 'ultimate-member' ) ) ) {

			jQuery(this).parents('a').remove();

			var arg1 = jQuery(this).parents('a').data('arg1');

			jQuery.ajax({
				url: wp.ajax.settings.url,
				type: 'POST',
				data: {
					action:'um_do_ajax_action',
					act_id : 'um_admin_remove_field_global',
					arg1 : arg1,
					nonce: um_admin_scripts.nonce

				},
				success: function(data) {
					jQuery('#um-admin-form-builder .' + arg1).remove();
				},
				error: function(data) {

				}
			});
		}

		return false;
	});


	/* Add a Field */
	jQuery(document.body).on('submit', 'form.um_add_field', function(e){
		e.preventDefault();
		var conditions = jQuery('.um-admin-cur-condition');
		//need fields refactor
		jQuery(conditions).each( function ( i ) {
			if ( jQuery( this ).find('[id^="_conditional_action"]').val() === '' ||
				jQuery( this ).find('[id^="_conditional_field"]').val() === '' ||
				jQuery( this ).find('[id^="_conditional_operator"]').val() ==='' )
			{
				jQuery(conditions[i]).find('.um-admin-remove-condition').trigger('click');
			}
		} );
		conditions = jQuery('.um-admin-cur-condition');
		jQuery(conditions).each( function ( i ) {
			var id = i === 0 ? '' : i;

			jQuery( this ).find('[id^="_conditional_action"]').attr('name', '_conditional_action' + id);
			jQuery( this ).find('[id^="_conditional_action"]').attr('id', '_conditional_action' + id);
			jQuery( this ).find('[id^="_conditional_field"]').attr('name', '_conditional_field' + id);
			jQuery( this ).find('[id^="_conditional_field"]').attr('id', '_conditional_field' + id);
			jQuery( this ).find('[id^="_conditional_operator"]').attr('name', '_conditional_operator' + id);
			jQuery( this ).find('[id^="_conditional_operator"]').attr('id', '_conditional_operator' + id);
			jQuery( this ).find('[id^="_conditional_value"]').attr('name', '_conditional_value' + id);
			jQuery( this ).find('[id^="_conditional_value"]').attr('id', '_conditional_value' + id);
		} );
		var form = jQuery(this);

		jQuery.ajax({
			url: wp.ajax.settings.url,
			type: 'POST',
			dataType: 'json',
			data: form.serialize(),
			beforeSend: function(){
				form.css({'opacity': 0.5});
				jQuery('.um-admin-error').removeClass('um-admin-error');
				form.find('.um-admin-error-block').hide();
				form.find('.um-admin-success-block').hide();
			},
			complete: function(){
				form.css({'opacity': 1});
			},
			success: function( response ){
				if ( response.success ) {
					let data = response.data;
					if ( data.error ) {
						let c = 0;
						jQuery.each( data.error, function(i, v){
							c++;
							if ( 1 === c ) {
								form.find('#' + i).addClass('um-admin-error').trigger('focus');
								form.find('.um-admin-error-block').show().html(v);
							}
						});

						UM.admin.modal.resize();
					} else {
						jQuery('.um-col-demon-settings').data('in_row', '').data('in_sub_row', '').data('in_column', '').data('in_group', '');
						UM.common.tipsy.hide();
						UM.admin.modal.remove();
						um_admin_update_builder();
					}
				} else {
					console.log( response );
				}
			},
			error: function( response ){
				console.log( response );
			}
		});
		return false;
	});

	/**
		disable link
	 **/
	jQuery(document.body).on('click', '.um-admin-builder a, .um-admin-modal a:not(.um-preview-upload)', function(e){
		e.preventDefault();
		return false;
	});

	/**
		toggle area
	 **/
	jQuery(document.body).on('click', '.um-admin-btn-toggle a', function(e){
		var content = jQuery(this).parent().find('.um-admin-btn-content');
		var link = jQuery(this);
		if ( content.is(':hidden') ) {
			content.show();
			link.find('i').removeClass().addClass('um-icon-minus');
			link.addClass('active');
		} else {
			content.hide();
			link.find('i').removeClass().addClass('um-icon-plus');
			link.removeClass('active');
		}
		UM.admin.modal.resize();
	});



	/**
		clone a condition
	 **/
	jQuery(document.body).on('click', '.um-admin-new-condition', function() {

		if ( jQuery(this).hasClass('disabled') )
			return false;

		var content = jQuery(this).parents('.um-admin-btn-content'),
			length = content.find('.um-admin-cur-condition').length;

		if ( length < 5 ) {
			//content.find('select').select2('destroy');

			var template = jQuery('.um-admin-btn-content').find('.um-admin-cur-condition-template').clone();
			template.find('input[type=text]').val('');
			template.find('select').val('');

			template.appendTo( content );
			jQuery(template).removeClass("um-admin-cur-condition-template");
			jQuery(template).addClass("um-admin-cur-condition");

			UM.admin.builder.fieldConditions.refresh();
			UM.admin.modal.resize();
		} else {
			jQuery(this).addClass('disabled');
			alert( 'You already have 5 rules' );
		}
		//need fields refactor
		var conditions = jQuery('.um-admin-cur-condition');
		jQuery(conditions).each( function ( i ) {
			id = i === 0 ? '' : i;
			jQuery( this ).find('[id^="_conditional_action"]').attr('name', '_conditional_action' + id);
			jQuery( this ).find('[id^="_conditional_action"]').attr('id', '_conditional_action' + id);
			jQuery( this ).find('[id^="_conditional_field"]').attr('name', '_conditional_field' + id);
			jQuery( this ).find('[id^="_conditional_field"]').attr('id', '_conditional_field' + id);
			jQuery( this ).find('[id^="_conditional_operator"]').attr('name', '_conditional_operator' + id);
			jQuery( this ).find('[id^="_conditional_operator"]').attr('id', '_conditional_operator' + id);
			jQuery( this ).find('[id^="_conditional_value"]').attr('name', '_conditional_value' + id);
			jQuery( this ).find('[id^="_conditional_value"]').attr('id', '_conditional_value' + id);
		} );

	});

	/**
		reset conditions
	 **/
	jQuery(document.body).on('click', '.um-admin-reset-conditions a', function(){
		var content = jQuery(this).parents('.um-admin-btn-content');
		content.find('.um-admin-cur-condition').slice(1).remove();
		content.find('input[type=text]').val('');
		content.find('select').val('');
		jQuery('.um-admin-new-condition').removeClass('disabled');
		UM.admin.builder.fieldConditions.refresh();
		UM.admin.modal.resize();
	});

	/**
		remove a condition
	 **/
	jQuery(document.body).on('click', '.um-admin-remove-condition', function(){
		var condition = jQuery(this).parents('.um-admin-cur-condition');
		jQuery('.um-admin-new-condition').removeClass('disabled');
		UM.common.tipsy.hide();
		condition.remove();
		//need fields refactor
		var conditions = jQuery('.um-admin-cur-condition');
		jQuery(conditions).each( function ( i ) {
			id = i === 0 ? '' : i;
			jQuery( this ).find('[id^="_conditional_action"]').attr('name', '_conditional_action' + id);
			jQuery( this ).find('[id^="_conditional_action"]').attr('id', '_conditional_action' + id);
			jQuery( this ).find('[id^="_conditional_field"]').attr('name', '_conditional_field' + id);
			jQuery( this ).find('[id^="_conditional_field"]').attr('id', '_conditional_field' + id);
			jQuery( this ).find('[id^="_conditional_operator"]').attr('name', '_conditional_operator' + id);
			jQuery( this ).find('[id^="_conditional_operator"]').attr('id', '_conditional_operator' + id);
			jQuery( this ).find('[id^="_conditional_value"]').attr('name', '_conditional_value' + id);
			jQuery( this ).find('[id^="_conditional_value"]').attr('id', '_conditional_value' + id);
		} );
		UM.admin.builder.fieldConditions.refresh();
		UM.admin.modal.resize();
	});


	/**
	 * Retrieve options from a callback function
	 */
	jQuery(document.body).on('blur',"#_custom_dropdown_options_source", function(){
		var me = jQuery(this);
		var _options = jQuery('textarea[id=_options]');

		if( me.val() != '' ){
			var um_option_callback = me.val();
			jQuery.ajax({
				url: wp.ajax.settings.url,
				type: 'POST',
				data: {
					action:'um_populate_dropdown_options',
					um_option_callback: um_option_callback,
					nonce: um_admin_scripts.nonce
				},
				complete: function(){

				},
				success: function( response ){
					var arr_opts = [];

					for (var key in response.data ){
						arr_opts.push( response.data[ key ] );
					}

					_options.val( arr_opts.join('\n') );

				}
			});
		}

	});

	if ( !jQuery('.um-admin-drag').length ) {
		return false;
	}

	UM_Drag_and_Drop();

	/* add field to respected area */
	jQuery( document.body ).on('click', 'a.um-admin-drag-add-field', function() {
		in_row = jQuery(this).parents('.um-admin-drag-row').index();
		in_sub_row = jQuery(this).parents('.um-admin-drag-rowsub').index();
		if ( jQuery(this).parents('.um-admin-drag-rowsub').find('.um-admin-drag-col').length == 1 ) {
			in_column = 1;
		} else {
			if ( jQuery(this).parents('.um-admin-drag-col').hasClass('cols-middle')){
				in_column = 2;
			} else if ( jQuery(this).parents('.um-admin-drag-col').hasClass('cols-last') ) {
				if ( jQuery(this).parents('.um-admin-drag-rowsub').find('.um-admin-drag-col').length == 3 ) {
					in_column = 3;
				} else {
					in_column = 2;
				}
			} else {
				in_column = 1;
			}
		}

		if ( jQuery(this).parents('.um-admin-drag-group').length ) {
			in_group = jQuery(this).parents('.um-admin-drag-fld.um-field-type-group').data('key');
		} else {
			in_group = '';
		}

		jQuery('.um-col-demon-settings').data('in_row', in_row);
		jQuery('.um-col-demon-settings').data('in_sub_row', in_sub_row);
		jQuery('.um-col-demon-settings').data('in_column', in_column);
		jQuery('.um-col-demon-settings').data('in_group', in_group);
	});

	/* add row */
	jQuery(document.body).on('click', '*[data-row_action="add_row"]', function(){
		var dragg = jQuery('.um-admin-drag-ajax');
		dragg.append( '<div class="um-admin-drag-row">' + jQuery('.um-col-demon-row').html() + '</div>' );
		dragg.find('.um-admin-drag-row:last').find('.um-admin-drag-row-icons').find('a.um-admin-drag-row-edit').attr('data-arg3', '_um_row_' + ( dragg.find('.um-admin-drag-row').length ) );
		dragg.find('.um-admin-drag-row:last').attr('data-original', '_um_row_' + ( dragg.find('.um-admin-drag-row').length ) );
		UM_update_rows();
		UM_update_subrows();
		UM_Rows_Refresh();
	});

	/* add sub row */
	jQuery(document.body).on('click', '*[data-row_action="add_subrow"]', function(){
		var dragg = jQuery(this).parents('.um-admin-drag-row').find('.um-admin-drag-rowsubs');
		dragg.append( '<div class="um-admin-drag-rowsub">' + jQuery('.um-col-demon-subrow').html() + '</div>' );
		UM_update_subrows();
		UM_Rows_Refresh();
	});

	/* remove element: Row, Subrow */
	jQuery(document.body).on('click', 'a[data-remove_element^="um-"]',function(){
		let deleteButton   = jQuery(this);
		let element        = jQuery(this).data('remove_element');
		let loadingWrapper = jQuery(this).parents('.' + element ).children('.um-admin-row-loading');

		let row    = jQuery(this).parents('.um-admin-drag-row').index();
		let subrow = jQuery(this).parents('.um-admin-drag-rowsub').index();

		let fieldPosition= {row,subrow};
		let deleteExists= false;
		jQuery.each( UM.admin.builder.deleteProcess, function(i) {
			if ( fieldPosition.row === UM.admin.builder.deleteProcess[i].row && fieldPosition.subrow === UM.admin.builder.deleteProcess[i].subrow ) {
				deleteExists = true;
				return false;
			}
		});

		if ( deleteExists ) {
			return;
		}

		loadingWrapper.show();

		UM.admin.builder.deleteProcess.push({row,subrow});

		UM.admin.builder.fieldsToDelete = jQuery(this).parents('.' +element).find('.um-admin-drag-fld').toArray();

		if ( UM.admin.builder.fieldsToDelete.length > 0 ) {
			um_builder_delete_field_ajax( function () {
				UM.common.tipsy.hide();
				deleteButton.parents('.' +element).remove();
				UM_Rows_Refresh();

				jQuery.each( UM.admin.builder.deleteProcess, function(i) {
					if ( fieldPosition.row === UM.admin.builder.deleteProcess[i].row && fieldPosition.subrow === UM.admin.builder.deleteProcess[i].subrow ) {
						UM.admin.builder.deleteProcess.splice(i, 1);
						return false;
					}
				});

				loadingWrapper.hide();
			} );
		} else {
			UM.common.tipsy.hide();
			jQuery(this).parents('.' +element).remove();
			UM_Rows_Refresh();

			jQuery.each( UM.admin.builder.deleteProcess, function(i) {
				if ( fieldPosition.row === UM.admin.builder.deleteProcess[i].row && fieldPosition.subrow === UM.admin.builder.deleteProcess[i].subrow ) {
					UM.admin.builder.deleteProcess.splice(i, 1);
					return false;
				}
			});

			loadingWrapper.hide();
		}
	});

	/* dynamically change columns */
	jQuery(document.body).on('click', '.um-admin-drag-ctrls.columns a', function(){

		var row = jQuery(this).parents('.um-admin-drag-rowsub');
		var tab = jQuery(this);
		var tabs = jQuery(this).parent();
		tabs.find('a').removeClass('active');
		tab.addClass('active');
		var existing_cols = row.find('.um-admin-drag-col').length;
		var required_cols = tab.data('cols');
		var needed_cols = required_cols - existing_cols;

		if ( needed_cols > 0 ) {

			for (i = 0; i < needed_cols; i++){
				row.find('.um-admin-drag-col-dynamic').append('<div class="um-admin-drag-col"></div>');
			}

			row.find('.um-admin-drag-col').removeClass('cols-1 cols-2 cols-3 cols-last cols-middle');
			row.find('.um-admin-drag-col').addClass('cols-' + row.find('.um-admin-drag-col').length );
			row.find('.um-admin-drag-col:last').addClass('cols-last');

			if ( row.find('.um-admin-drag-col').length == 3 ) {row.find('.um-admin-drag-col:eq(1)').addClass('cols-middle');}

		} else if ( needed_cols < 0 ) {

			needed_cols = needed_cols + 3;
			if ( needed_cols == 2 ) {
				row.find('.um-admin-drag-col:first').append( row.find('.um-admin-drag-col.cols-last').html() );
				row.find('.um-admin-drag-col.cols-last').remove();
			}
			if ( needed_cols == 1 ) {
				row.find('.um-admin-drag-col:first').append( row.find('.um-admin-drag-col.cols-last').html() );
				row.find('.um-admin-drag-col:first').append( row.find('.um-admin-drag-col.cols-middle').html() );
				row.find('.um-admin-drag-col.cols-last').remove();
				row.find('.um-admin-drag-col.cols-middle').remove();
			}

			row.find('.um-admin-drag-col').removeClass('cols-1 cols-2 cols-3 cols-last cols-middle');
			row.find('.um-admin-drag-col').addClass('cols-' + row.find('.um-admin-drag-col:visible').length );
			row.find('.um-admin-drag-col:last').addClass('cols-last');

		}

		if ( allow_update_via_col_click == true ) {
			UM_Change_Field_Col();
			UM_Rows_Refresh();
		}

	});

	/* trigger columns at start */
	allow_update_via_col_click = false;
	jQuery('.um-admin-drag-ctrls.columns a.active').each(function(){
		jQuery(this).trigger('click');
	}).promise().done( function(){ allow_update_via_col_click = true; } );

	UM_Rows_Refresh();
});
