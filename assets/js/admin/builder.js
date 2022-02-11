var $um_tiny_editor;

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
			jQuery('.tipsy').remove();
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
			row.find('.um-admin-drag-row-icons').append( '<a href="#" class="um-admin-tipsy-n" title="' + wp.i18n.__( 'Delete Row', 'ultimate-member' ) + '" data-remove_element="um-admin-drag-row"><i class="far fa-trash-alt"></i></a>' );
		}
	});
}

function UM_update_subrows() {
	jQuery('a[data-remove_element="um-admin-drag-rowsub"]').remove();
	jQuery('.um-admin-drag-row').each(function(){
		c = 0;
		jQuery(this).find('.um-admin-drag-rowsub').each(function(){
			c++;
			row = jQuery(this);
			if ( c != 1 ) {
				row.find('.um-admin-drag-rowsub-icons').append('<a href="#" class="um-admin-tipsy-n" title="' + wp.i18n.__( 'Delete Row', 'ultimate-member' ) + '" data-remove_element="um-admin-drag-rowsub"><i class="far fa-trash-alt"></i></a>');
			}
		});
	});
}

function UM_Change_Field_Col() {
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

function UM_Change_Field_Grp() {
	jQuery('.um-admin-drag-col .um-admin-drag-fld:not(.um-field-type-group)').each(function(){
		if ( jQuery(this).parents('.um-admin-drag-group').length == 0 ){
			jQuery(this).data('group', '');
		} else {
			jQuery(this).data('group', jQuery(this).parents('.um-admin-drag-fld.um-field-type-group').data('key') );
		}
	});
}

function UM_Rows_Refresh() {

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


	jQuery('.um_admin_edit_field_popup.um-admin-drag-row-edit').umModal({
		header: wp.i18n.__( 'Edit Row Settings', 'ultimate-member' ),
		size: 'large',
		classes: 'um-admin-modal um-admin-fields-form',
		content: function( event, options ) {
			let $modal = this;
			let $btn = options.relatedButton;

			wp.ajax.send( 'um_admin_edit_field_popup', {
				data: {
					field_type: $btn.data( 'field_type' ),
					form_id: $btn.data( 'form_id' ),
					field_key: $btn.data( 'field_key' ),
					nonce: um_admin_scripts.nonce
				},
				beforeSend: function() {
					$modal.addClass('loading');
				},
				success: function( data ) {
					$modal.removeClass('loading');
					$modal.find('.um-modal-body').html( data );

					um_trigger_callback_dropdown( $modal );
					um_trigger_conditional_fields( $modal );

					um_init_helptips();
					um_admin_init_icon_select();
					um_admin_init_colorpicker();
					um_maybe_init_tinymce( $modal, 'edit' );
					um_admin_init_datetimepicker();

					UM.modal.responsive( $modal );
				},
				error: function( data ) {
					$modal.removeClass('loading');
					console.error( data );
				}
			});
		},
	});


	jQuery('.um_admin_edit_field_popup:not(.um-admin-drag-row-edit)').umModal({
		header: wp.i18n.__( 'Edit Field', 'ultimate-member' ),
		size: 'large',
		classes: 'um-admin-modal um-admin-fields-form',
		content: function( event, options ) {
			let $modal = this;
			let $btn = options.relatedButton;

			wp.ajax.send( 'um_admin_edit_field_popup', {
				data: {
					field_type: $btn.data( 'field_type' ),
					form_id: $btn.data( 'form_id' ),
					field_key: $btn.data( 'field_key' ),
					nonce: um_admin_scripts.nonce
				},
				beforeSend: function() {
					$modal.addClass('loading');
				},
				success: function( data ) {
					$modal.removeClass('loading');
					$modal.find('.um-modal-body').html( data );

					um_trigger_callback_dropdown( $modal );
					um_trigger_conditional_fields( $modal );

					um_init_helptips();
					um_admin_init_icon_select();
					um_admin_init_colorpicker();

					um_maybe_init_tinymce( $modal, 'edit' );
					um_admin_init_datetimepicker();

					UM.modal.responsive( $modal );
				},
				error: function( data ) {
					$modal.removeClass('loading');
					console.error( data );
				}
			});
		},
	});
}

function um_trigger_callback_dropdown( $modal ) {
	$modal.find( "#_custom_dropdown_options_source" ).trigger( 'blur' );
}

function um_trigger_conditional_fields( $modal ) {
	$modal.find( '.um-adm-conditional' ).each( function() {
		jQuery( this ).trigger( 'change' );
	});
}

function um_maybe_init_tinymce( $modal, action ) {
	if ( $modal.find( '.um-admin-editor' ).length && typeof um_tinymce_init === 'function' ) {
		let id = 'edit' === action ? 'um_editor_edit' : 'um_editor_new';
		let content = 'edit' === action ? $modal.find( '.dynamic-mce-content' ).html() : '';

		um_tinymce_init( id, content );
	}
}

function um_tinymce_init( id, content ) {
	var object = jQuery('#' + id);

	if ( typeof( tinyMCE ) === 'object' && tinyMCE.get( id ) !== null ) {
		tinyMCE.triggerSave();
		tinyMCE.EditorManager.execCommand( 'mceRemoveEditor', true, id );
		"4" === tinyMCE.majorVersion ? window.tinyMCE.execCommand( "mceRemoveEditor", !0, id ) : window.tinyMCE.execCommand( "mceRemoveControl", !0, id );
		$um_tiny_editor = jQuery('<div>').append( object.parents( '#wp-' + id + '-wrap' ).clone() );
		object.parents('#wp-' + id + '-wrap').replaceWith('<div class="um_tiny_placeholder"></div>');
		jQuery('.um-admin-editor:visible').html( jQuery( $um_tiny_editor ).html() );

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
		$um_tiny_editor = jQuery('<div>').append( object.parents('#wp-' + id + '-wrap').clone() );
		object.parents('#wp-' + id + '-wrap').replaceWith('<div class="um_tiny_placeholder"></div>');

		jQuery('.um-admin-editor:visible').html( jQuery( $um_tiny_editor ).html() );

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

/**
 * Initialize and show "+" button (Add field) in every row/column
 * ! reviewed
 */
function UM_Add_Icon() {
	var add_icon_html = '<a href="#" class="um-admin-drag-add-field um-admin-tipsy-n" title="' + wp.i18n.__( 'Add Field', 'ultimate-member' ) + '" data-form_id="' + jQuery('.um-admin-drag-ajax').data('form_id') + '"><i class="fas fa-plus"></i></a>';

	jQuery('.um-admin-drag-col').each(function() {
		if ( jQuery(this).find('.um-admin-drag-add-field').length === 0 ) {
			jQuery(this).append( add_icon_html );
		} else {
			jQuery(this).find('.um-admin-drag-add-field').remove();
			jQuery(this).append( add_icon_html );
		}
	});

	jQuery('.um-admin-drag-group').each(function(){
		if ( jQuery(this).find('.um-admin-drag-add-field').length === 0 ) {
			jQuery(this).append( add_icon_html );
		} else {
			jQuery(this).find('.um-admin-drag-add-field').remove();
			jQuery(this).append( add_icon_html );
		}
	});

	um_admin_init_tipsy();

	jQuery('.um-admin-drag-add-field').umModal({
		header: wp.i18n.__( 'Fields Manager', 'ultimate-member' ),
		content: function( event, options ) {
			let $modal = this;
			let $btn = options.relatedButton;

			wp.ajax.send( 'um_admin_fields_list', {
				data: {
					form_id: $btn.data( 'form_id' ),
					nonce: um_admin_scripts.nonce
				},
				beforeSend: function() {
					$modal.addClass('loading');
				},
				success: function( data ) {
					$modal.removeClass('loading');
					$modal.find('.um-modal-body').html( data );
					UM.modal.responsive( $modal );

					if ( jQuery('.um_admin_new_field_popup').length ) {

						let $builder = jQuery('.um-admin-builder');

						var in_row     = $builder.data( 'in_row' );
						var in_sub_row = $builder.data( 'in_sub_row' );
						var in_column  = $builder.data( 'in_column' );
						var in_group   = $builder.data( 'in_group' );

						jQuery('.um_admin_new_field_popup').umModal({
							header: wp.i18n.__( 'Add a New Field', 'ultimate-member' ),
							size: 'large',
							classes: 'um-admin-modal um-admin-fields-form',
							content: function( event, options ) {
								let $modal = this;
								let $btn = options.relatedButton;

								wp.ajax.send( 'um_admin_new_field_popup', {
									data: {
										field_type: $btn.data( 'field_type' ),
										form_id: $btn.data( 'form_id' ),
										in_row: in_row,
										in_sub_row: in_sub_row,
										in_column: in_column,
										in_group: in_group,
										nonce: um_admin_scripts.nonce
									},
									beforeSend: function() {
										$modal.addClass('loading');
									},
									success: function( data ) {
										$modal.removeClass('loading');
										$modal.find('.um-modal-body').html( data );

										um_trigger_callback_dropdown( $modal );
										um_trigger_conditional_fields( $modal );

										um_init_helptips();
										um_admin_init_icon_select();
										um_admin_init_colorpicker();

										um_maybe_init_tinymce( $modal, 'add' );
										um_admin_init_datetimepicker();

										UM.modal.responsive( $modal );
									},
									error: function( data ) {
										$modal.removeClass('loading');
										console.error( data );
									}
								});
							},
						});
					}

				},
				error: function( data ) {
					$modal.removeClass('loading');
					console.error( data );
				}
			});
		},
	});
}


/**
 * When click on "+" button fill data for getting proper place where insert the new field
 * !reviewed
 *
 * @param settings
 * @param event
 */
function um_admin_modal_button_clicked( settings, event ) {
	let $button = settings.relatedButton;

	if ( ! $button.hasClass('um-admin-drag-add-field') ) {
		return;
	}

	let in_row = $button.parents('.um-admin-drag-row').index();
	let in_sub_row = $button.parents('.um-admin-drag-rowsub').index();

	let in_column = 1;
	if ( $button.parents('.um-admin-drag-rowsub').find('.um-admin-drag-col').length > 1 ) {
		if ( $button.parents('.um-admin-drag-col').hasClass('cols-middle') ) {
			in_column = 2;
		} else if ( $button.parents('.um-admin-drag-col').hasClass('cols-last') ) {
			if ( $button.parents('.um-admin-drag-rowsub').find('.um-admin-drag-col').length === 3 ) {
				in_column = 3;
			} else {
				in_column = 2;
			}
		}
	}

	let in_group = '';
	if ( $button.parents('.um-admin-drag-group').length ) {
		in_group = $button.parents('.um-admin-drag-fld.um-field-type-group').data('key');
	}

	jQuery('.um-admin-builder').data( 'in_row', in_row ).data( 'in_sub_row', in_sub_row ).data( 'in_column', in_column ).data( 'in_group', in_group );
}
wp.hooks.addAction( 'um-modal-button-clicked', 'ultimatemember', um_admin_modal_button_clicked, 10, 2 );


function um_admin_modal_before_close( $modal ) {
	if ( $modal.find('.um-admin-editor').length ) {
		tinyMCE.triggerSave();

		let id = $modal.find( '.um_edit_field' ).length ? 'um_editor_edit' : 'um_editor_new';
		jQuery('#wp-' + id + '-wrap').remove();

		jQuery('.um_tiny_placeholder').replaceWith( jQuery( $um_tiny_editor ).html() );
	}
}
wp.hooks.addAction( 'um-modal-before-close', 'ultimatemember', um_admin_modal_before_close, 10, 1 );


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

	jQuery('.tipsy').hide();

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
			jQuery('.tipsy').hide();

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

	//hide footer text on add/edit UM Forms
	//layouts crashed because we load and hide metaboxes
	//and WP calculate page height
	jQuery('#wpfooter').hide();

	/* Default form tab */
	if ( jQuery('.um-admin-boxed-links').length > 0 ) {
		var tab = jQuery('.um-admin-boxed-links a[data-role="'+jQuery('input#form__um_mode').val()+'"]');
		um_form_select_tab( tab, false );
	}


	/* Creating new form button */
	jQuery('.um-admin-boxed-links:not(.is-core-form) a').on( 'click', function() {
		um_form_select_tab( jQuery(this), true );
	});

	/**
	 Ajax link when select form mode
	 **/
	jQuery('.um-admin-ajaxlink').on('click', function(e){
		e.preventDefault();
		return false;
	});

	/* New tabs view of the add/edit field modal*/
	jQuery(document.body).on('click', '.um-modal-tab a', function() {
		if ( jQuery(this).parents('li').hasClass('active') ) {
			return;
		}

		jQuery(this).parents('.um-modal-tabs').find('.um-modal-tab').removeClass('active');
		jQuery(this).parents('li').addClass('active');

		var key = jQuery(this).data('key');
		var tabs_wrapper = jQuery('.um-modal-tabs-content-wrapper');

		tabs_wrapper.find('.um-modal-tab-content').removeClass('active');
		tabs_wrapper.find('.um-modal-tab-' + key ).addClass('active');
	});


	jQuery('.um_admin_preview_form').umModal({
		header: wp.i18n.__( 'Live Form Preview', 'ultimate-member' ),
		size: 'small',
		classes: 'um-admin-form-preview',
		content: function( event, options ) {
			let $modal = this;
			let $btn = options.relatedButton;

			wp.ajax.send( 'um_admin_preview_form', {
				data: {
					form_id: $btn.data( 'form_id' ),
					nonce: um_admin_scripts.nonce
				},
				beforeSend: function() {
					$modal.addClass('loading');
				},
				success: function( data ) {
					$modal.removeClass('loading');
					$modal.find('.um-modal-body').html( data );
					um_responsive();
					jQuery('.um-admin-preview-overlay').css('height', jQuery('.um-admin-preview-overlay').siblings('.um').outerHeight(true)*1 + 20 + 'px' );
					UM.modal.responsive( $modal );
				},
				error: function( data ) {
					$modal.removeClass('loading');
					console.error( data );
				}
			});
		},
	});


	/**
	 * disable links
	 * @param {object} e  jQuery.Event
	 */
	jQuery( document.body ).on( 'click', '.um-admin-builder a, .um-admin-modal a', function (e) {
		e.preventDefault();
		return false;
	} );

	/**
	 * Retrieve options from a callback function
	 */
	jQuery( document.body ).on( 'blur', "#_custom_dropdown_options_source", function () {
		var me = jQuery( this );
		var um_option_callback = me.val();
		var _options = jQuery( 'textarea[id=_options]' );

		if ( um_option_callback !== '' ) {
			jQuery.ajax( {
				url: wp.ajax.settings.url,
				type: 'POST',
				data: {
					action: 'um_populate_dropdown_options',
					um_option_callback: um_option_callback,
					nonce: um_admin_scripts.nonce
				},
				complete: function () {

				},
				success: function (response) {
					var arr_opts = [];

					for ( var key in response.data ) {
						arr_opts.push( response.data[ key ] );
					}

					_options.val( arr_opts.join( '\n' ) );

				}
			} );
		}
	} );


	/**
	 Conditional fields in "Add/Edit field metabox"
	 **/
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

	if ( jQuery('.um-admin-drag').length ) {
		UM_Drag_and_Drop();

		/* add row */
		jQuery(document.body).on('click', '*[data-row_action="add_row"]', function(){
			var dragg = jQuery('.um-admin-drag-ajax');
			dragg.append( '<div class="um-admin-drag-row">' + jQuery('.um-col-demon-row').html() + '</div>' );
			dragg.find('.um-admin-drag-row:last').find('.um-admin-drag-row-icons').find('a.um-admin-drag-row-edit').attr('data-field_key', '_um_row_' + ( dragg.find('.um-admin-drag-row').length ) );
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

		/* dynamically change columns */
		jQuery(document.body).on('click', '.um-admin-drag-ctrls.columns a', function() {
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
	}


	/**
	 * Remove field permanently
	 * !reviewed
	 */
	jQuery(document.body).on('click', '.um-admin-btns a span.remove', function(e) {
		e.preventDefault();

		if ( confirm( wp.i18n.__( 'This will permanently delete this custom field from a database and from all forms on your site. Are you sure?', 'ultimate-member' ) ) ) {
			var $button = jQuery(this).parents('a');
			var field_key = $button.data('field_key');
			var $buttons_wrapper = jQuery(this).parents('.um-admin-btns');

			wp.ajax.send( 'um_admin_remove_field_global', {
				data: {
					field_key: field_key,
					nonce: um_admin_scripts.nonce
				},
				success: function( data ) {
					jQuery( '#um-admin-form-builder .' + field_key ).remove();
					$button.remove();
					if ( ! $buttons_wrapper.find('a').length ) {
						jQuery('.um-no-custom-fields').show();
					}
				},
				error: function( data ) {
					console.log( data );
				}
			});
		}

		return false;
	});


	/**
	 * Duplicate field
	 * !reviewed
	 */
	jQuery(document.body).on('click', '.um_admin_duplicate_field', function(e) {
		e.preventDefault();

		var form_id = jQuery(this).data('form_id');
		var field_key = jQuery(this).data('field_key');

		wp.ajax.send( 'um_admin_duplicate_field', {
			data: {
				field_key: field_key,
				form_id: form_id,
				nonce: um_admin_scripts.nonce
			},
			success: function( data ) {
				um_admin_update_builder();
			},
			error: function( data ) {
				console.log( data );
			}
		});

		return false;
	});


	/**
	 * Remove field from form
	 * !reviewed
	 */
	jQuery(document.body).on('click', '.um_admin_remove_field', function(e) {
		e.preventDefault();

		var form_id = jQuery(this).data('form_id');
		var field_key = jQuery(this).data('field_key');

		wp.ajax.send( 'um_admin_remove_field', {
			data: {
				field_key: field_key,
				form_id: form_id,
				nonce: um_admin_scripts.nonce
			},
			success: function( data ) {
				um_admin_update_builder();
				UM_Rows_Refresh();
			},
			error: function( data ) {
				console.log( data );
			}
		});

		return false;
	});


	/**
	 * Remove field or rows
	 * !reviewed but
	 *
	 * todo replace remove_element and avoid a lot of AJAX for every field
	 */
	jQuery(document.body).on('click', 'a[data-remove_element^="um-"]',function(){
		let element_class = jQuery(this).data('remove_element');

		jQuery(this).parents('.' + element_class).find('.um-admin-drag-fld').each(function(){
			jQuery(this).find('a.um_admin_remove_field').trigger('click');
		});

		jQuery(this).parents('.' + element_class).remove();
		jQuery('.tipsy').remove();
		UM_Rows_Refresh();
	});


	/**
	 * Add field from the custom list to the form
	 * !reviewed
	 */
	jQuery( document.body ).on('click', '.um_admin_add_field_from_list', function() {
		if ( typeof jQuery(this).attr('disabled') !== 'undefined' ) {
			return false;
		}

		let $builder = jQuery('.um-admin-builder');

		jQuery('.tipsy').hide();

		var in_row     = $builder.data( 'in_row' );
		var in_sub_row = $builder.data( 'in_sub_row' );
		var in_column  = $builder.data( 'in_column' );
		var in_group   = $builder.data( 'in_group' );
		var field_key  = jQuery(this).data( 'field_key' );
		var form_id    = jQuery(this).data( 'form_id' );

		wp.ajax.send( 'um_admin_add_field_from_list', {
			data: {
				field_key: field_key,
				form_id: form_id,
				in_row: in_row,
				in_sub_row: in_sub_row,
				in_column: in_column,
				in_group: in_group,
				nonce: um_admin_scripts.nonce
			},
			success: function( data ) {
				$builder.data( 'in_row', '' ).data( 'in_sub_row', '' ).data( 'in_column', '' ).data( 'in_group', '' );
				um_admin_update_builder();
				UM.modal.close();
			},
			error: function( data ) {
				console.log( data );
			}
		});

		return false;
	});


	/**
	 * Add field from the predefined list to the form
	 * !reviewed
	 */
	jQuery( document.body ).on('click', '.um_admin_add_field_from_predefined', function() {
		if ( typeof jQuery(this).attr('disabled') !== 'undefined' ) {
			return false;
		}

		let $builder = jQuery('.um-admin-builder');

		jQuery('.tipsy').hide();

		var in_row     = $builder.data( 'in_row' );
		var in_sub_row = $builder.data( 'in_sub_row' );
		var in_column  = $builder.data( 'in_column' );
		var in_group   = $builder.data( 'in_group' );
		var field_key  = jQuery(this).data('field_key');
		var form_id    = jQuery(this).data('form_id');

		wp.ajax.send( 'um_admin_add_field_from_predefined', {
			data: {
				field_key: field_key,
				form_id: form_id,
				in_row: in_row,
				in_sub_row: in_sub_row,
				in_column: in_column,
				in_group: in_group,
				nonce: um_admin_scripts.nonce
			},
			success: function( data ) {
				$builder.data( 'in_row', '' ).data( 'in_sub_row', '' ).data( 'in_column', '' ).data( 'in_group', '' );
				um_admin_update_builder();
				UM.modal.close();
			},
			error: function( data ) {
				console.log( data );
			}
		});

		return false;
	});


	/* Add a Field */
	jQuery(document.body).on('submit', 'form.um_add_field', function(e) {
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
			success: function(data){
				if ( data.error ) {

					c = 0;
					jQuery.each(data.error, function(i, v) {
						c++;
						if ( c == 1 ) {
							form.find('#'+i).addClass('um-admin-error').trigger('focus');
							form.find('.um-admin-error-block').show().html(v);
						}
					});

					UM.modal.responsive();

				} else {

					jQuery('.um-admin-builder').data( 'in_row', '' ).data( 'in_sub_row', '' ).data( 'in_column', '' ).data( 'in_group', '' );

					UM.modal.closeAll();
					um_admin_update_builder();

				}

			},
			error: function(data) {
				console.log(data);
			}
		});

		return false;

	});


	jQuery( document.body ).on( 'click', '.um-admin-modal-close', function (e) {
		e.preventDefault();
		UM.modal.close();
	} );

	/* CONDITIONS */

	/**
	 * toggle area
	 * @param {object} e  jQuery.Event
	 */
	jQuery( document.body ).on( 'click', '.um-admin-btn-toggle > a', function (e) {
		e.preventDefault();

		var $btn = jQuery( e.currentTarget );
		var content = $btn.closest( '.um-admin-btn-toggle' ).find( '.um-admin-btn-content' );

		if ( content.is( ':hidden' ) ) {
			content.show();
			$btn.addClass( 'active' ).find( 'i' ).removeClass( 'fas fa-plus' ).addClass( 'fas fa-minus' );
		} else {
			content.hide();
			$btn.removeClass( 'active' ).find( 'i' ).removeClass( 'fas fa-minus' ).addClass( 'fas fa-plus' );
		}
		UM.modal.responsive();
	} );

	/**
	 * clone a condition
	 * @param {object} e  jQuery.Event
	 */
	jQuery( document.body ).on( 'click', '.um-admin-new-condition:not(.disabled)', function (e) {
		e.preventDefault();

		var content = jQuery( this ).parents( '.um-admin-btn-content' );
		var length = content.find( '.um-admin-cur-condition' ).length;

		if ( length < 5 ) {
			var template = jQuery( '.um-admin-btn-content' ).find( '.um-admin-cur-condition-template' ).clone();

			if ( length > 0 ) {
				for ( var i = 1; i < 5; i++ ) {
					if ( content.find( '[id="_conditional_action' + i + '"]' ).length < 1 ) {
						var id = i;
						break;
					}
				}
				template.find( '[id^="_conditional_action"]' ).attr( 'name', '_conditional_action' + id );
				template.find( '[id^="_conditional_action"]' ).attr( 'id', '_conditional_action' + id );
				template.find( '[id^="_conditional_field"]' ).attr( 'name', '_conditional_field' + id );
				template.find( '[id^="_conditional_field"]' ).attr( 'id', '_conditional_field' + id );
				template.find( '[id^="_conditional_operator"]' ).attr( 'name', '_conditional_operator' + id );
				template.find( '[id^="_conditional_operator"]' ).attr( 'id', '_conditional_operator' + id );
				template.find( '[id^="_conditional_value"]' ).attr( 'name', '_conditional_value' + id );
				template.find( '[id^="_conditional_value"]' ).attr( 'id', '_conditional_value' + id );
			}

			template.find( 'input[type=text], select' ).val( '' );
			template.removeClass( "um-admin-cur-condition-template" ).addClass( "um-admin-cur-condition" ).appendTo( content );

			UM.modal.responsive();
		} else {
			jQuery( this ).addClass( 'disabled' );
			alert( wp.i18n.__( 'You already have 5 rules', 'ultimate-member' ) );
		}
	} );

	/**
	 * reset conditions
	 * @param {object} e  jQuery.Event
	 */
	jQuery( document.body ).on( 'click', '.um-admin-reset-conditions a', function (e) {
		e.preventDefault();

		var content = jQuery( this ).parents( '.um-admin-btn-content' );

		content.find( '.um-admin-cur-condition' ).slice( 1 ).remove();
		content.find( 'input[type=text], select' ).val( '' );
		content.find( '.um-admin-new-condition' ).removeClass( 'disabled' );

		UM.modal.responsive();
	} );

	/**
	 * remove a condition
	 * @param {object} e  jQuery.Event
	 */
	jQuery( document.body ).on( 'click', '.um-admin-remove-condition', function (e) {
		e.preventDefault();

		var condition = jQuery( this ).parents( '.um-admin-cur-condition' );
		var content = condition.parents( '.um-admin-btn-content' );

		condition.remove();
		content.find( '.um-admin-new-condition' ).removeClass( 'disabled' );

		UM.modal.responsive();
	} );
});
