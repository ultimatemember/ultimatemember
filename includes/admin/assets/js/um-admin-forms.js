jQuery(document).ready( function() {
	/**
	 * Multi-selects field
	 */
	jQuery( document.body ).on( 'click', '.um-select-delete', function() {
		jQuery( this ).parents( 'li.um-multi-selects-option-line' ).remove();
	});


	jQuery( '.um-multi-selects-add-option' ).click( function() {
		var list = jQuery(this).siblings('ul.um-multi-selects-list');

		var field_id = list.data('field_id');
		var k = 0;
		if ( list.find( 'li:last select.um-forms-field' ).length > 0 ) {
			k = list.find( 'li:last select.um-forms-field' ).attr('id').split("-");
			k = k[1]*1 + 1;
		}

		var selector_html = jQuery( '<div>' ).append( list.siblings('.um-hidden-multi-selects').clone() ).html();

		list.append(
			'<li class="um-multi-selects-option-line"><span class="um-field-wrapper">' + selector_html +
			'</span><span class="um-field-control"><a href="javascript:void(0);" class="um-select-delete">' + php_data.texts.remove + '</a></span></li>'
		);

		list.find('li:last .um-hidden-multi-selects').attr('name', jQuery(this).data('name') ).
		addClass('um-forms-field um-long-field').removeClass('um-hidden-multi-selects').attr('id', list.data('id_attr') + '-' + k);

	});


	/**
	 * Multi-text field
	 */
	jQuery( document.body ).on( 'click', '.um-text-delete', function() {
		jQuery(this).parents('li.um-multi-text-option-line').remove();
	});


	jQuery( '.um-multi-text-add-option' ).click( function() {
		var list = jQuery(this).siblings( 'ul.um-multi-text-list' );

		var field_id = list.data( 'field_id' );
		var k = 0;
		if ( list.find( 'li:last input.um-forms-field' ).length > 0 ) {
			k = list.find( 'li:last input.um-forms-field' ).attr('id').split("-");
			k = k[1]*1 + 1;
		}

		var text_html = jQuery( '<div>' ).append( list.siblings('.um-hidden-multi-text').clone() ).html();

		var classes = list.find('li:last').attr('class');

		list.append(
			'<li class="' + classes + '"><span class="um-field-wrapper">' + text_html +
			'</span><span class="um-field-control"><a href="javascript:void(0);" class="um-text-delete">' + php_data.texts.remove + '</a></span></li>'
		);

		list.find('li:last .um-hidden-multi-text').attr('name', jQuery(this).data('name') ).
		addClass('um-forms-field um-long-field').removeClass('um-hidden-multi-text').attr('id', list.data('id_attr') + '-' + k);
	});


	/**
	 * Media uploader
	 */
	jQuery( '.um-media-upload' ).each( function() {
		var field = jQuery(this).find( '.um-forms-field' );
		var default_value = field.data('default');

		if ( field.val() != '' && field.val() != default_value ) {
			field.siblings('.um-set-image').hide();
			field.siblings('.um-clear-image').show();
			field.siblings('.icon_preview').show();
		} else {
			if ( field.val() == default_value ) {
				field.siblings('.icon_preview').show();
			}
			field.siblings('.um-set-image').show();
			field.siblings('.um-clear-image').hide();
		}
	});


	if ( typeof wp !== 'undefined' && wp.media && wp.media.editor ) {
		var frame;

		jQuery( '.um-set-image' ).click( function(e) {
			var button = jQuery(this);

			e.preventDefault();

			// If the media frame already exists, reopen it.
			if ( frame ) {
				frame.remove();
				/*frame.open();
				 return;*/
			}

			// Create a new media frame
			frame = wp.media({
				title: button.data('upload_frame'),
				button: {
					text: php_data.texts.select
				},
				multiple: false  // Set to true to allow multiple files to be selected
			});

			// When an image is selected in the media frame...
			frame.on( 'select', function() {
				// Get media attachment details from the frame state
				var attachment = frame.state().get('selection').first().toJSON();

				// Send the attachment URL to our custom image input field.
				button.siblings('.icon_preview').attr( 'src', attachment.url ).show();

				button.siblings('.um-forms-field').val( attachment.url );
				button.siblings('.um-media-upload-data-id').val(attachment.id);
				button.siblings('.um-media-upload-data-width').val(attachment.width);
				button.siblings('.um-media-upload-data-height').val(attachment.height);
				button.siblings('.um-media-upload-data-thumbnail').val(attachment.thumbnail);
				button.siblings('.um-media-upload-data-url').trigger('change');
				button.siblings('.um-media-upload-url').val(attachment.url);

				button.siblings('.um-clear-image').show();
				button.hide();

				jQuery( document ).trigger( 'um_media_upload_select', [button, attachment] );
			});

			frame.open();
		});

		jQuery('.icon_preview').click( function(e) {
			jQuery(this).siblings('.um-set-image').trigger('click');
		});

		jQuery('.um-clear-image').click( function(e) {
			var clear_button = jQuery(this);
			var default_image_url = clear_button.siblings('.um-forms-field').data('default');
			clear_button.siblings('.um-set-image').show();
			clear_button.hide();
			clear_button.siblings('.icon_preview').attr( 'src', default_image_url );
			clear_button.siblings('.um-media-upload-data-id').val('');
			clear_button.siblings('.um-media-upload-data-width').val('');
			clear_button.siblings('.um-media-upload-data-height').val('');
			clear_button.siblings('.um-media-upload-data-thumbnail').val('');
			clear_button.siblings('.um-forms-field').val( default_image_url );
			clear_button.siblings('.um-media-upload-data-url').trigger('change');
			clear_button.siblings('.um-media-upload-url').val( default_image_url );

			jQuery( document ).trigger( 'um_media_upload_clear', clear_button );
		});
	}


	/**
	 * On option fields change
	 */
	jQuery( document.body ).on('change', '.um-forms-field', function() {
		if ( jQuery('.um-forms-line[data-conditional*=\'"' + jQuery(this).data('field_id') + '",\']').length > 0 || jQuery('.um-forms-line[data-conditional*=\'' + jQuery(this).data('field_id') + '|\']').length > 0 || jQuery('.um-forms-line[data-conditional*=\'|' + jQuery(this).data('field_id') + '\']').length > 0 ) {
			run_check_conditions();
		}
	});


	//first load hide unconditional fields
	run_check_conditions();


	/**
	 * Run conditional logic
	 */
	function run_check_conditions() {
		jQuery( '.um-forms-line' ).removeClass('um-forms-line-conditioned').each( function() {
			if ( typeof jQuery(this).data('conditional') === 'undefined' || jQuery(this).hasClass('um-forms-line-conditioned') )
				return;

			if ( check_condition( jQuery(this) ) ) {
				jQuery(this).show();
			} else {
				jQuery(this).hide();
			}
		});
	}


	/**
	 * Conditional logic
	 *
	 * true - show field
	 * false - hide field
	 *
	 * @returns {boolean}
	 */
	function check_condition( form_line ) {

		form_line.addClass( 'um-forms-line-conditioned' );

		var conditional = form_line.data('conditional');
		var condition = conditional[1];
		var value = conditional[2];

		var prefix = form_line.data( 'prefix' );
		var parent_condition = true;

		if ( condition === '=' || condition === '!=' ) {
			var condition_field = jQuery( '#' + prefix + '_' + conditional[0] );

			if ( typeof condition_field.parents('.um-forms-line').data('conditional') !== 'undefined' ) {
				parent_condition = check_condition( condition_field.parents('.um-forms-line') );
			}
		} else if ( condition === '~' ) {
			var selectors = conditional[0].split('|');
			var condition_fields = [];
			jQuery.each( selectors, function(i) {
				condition_fields.push( jQuery( '#' + prefix + '_' + selectors[i] ) );
			});
			if ( typeof condition_fields[0].parents('.um-forms-line').data('conditional') !== 'undefined' ) {
				parent_condition = check_condition( condition_fields[0].parents('.um-forms-line') );
			}
		}

		var own_condition = false;
		if ( condition === '=' ) {
			var tagName = condition_field.prop("tagName").toLowerCase();

			if ( tagName == 'input' ) {
				var input_type = condition_field.attr('type');
				if ( input_type == 'checkbox' ) {
					own_condition = ( value == '1' ) ? condition_field.is(':checked') : ! condition_field.is(':checked');
				} else {
					own_condition = ( condition_field.val() == value );
				}
			} else if ( tagName == 'select' ) {
				own_condition = ( condition_field.val() == value );
			}
		} else if ( condition === '!=' ) {
			var tagName = condition_field.prop("tagName").toLowerCase();

			if ( tagName == 'input' ) {
				var input_type = condition_field.attr('type');
				if ( input_type == 'checkbox' ) {
					own_condition = ( value == '1' ) ? ! condition_field.is(':checked') : condition_field.is(':checked');
				} else {
					own_condition = ( condition_field.val() != value );
				}
			} else if ( tagName == 'select' ) {
				own_condition = ( condition_field.val() != value );
			}
		} else if ( condition === '~' ) {

			var field_id = form_line.find( form_line.data('field_type') ).data('field_id');
			var visible_options = [];
			jQuery.each( condition_fields, function(i) {
				var condition_field = condition_fields[ i ];

				var tagName = condition_field.prop("tagName").toLowerCase();

				if ( tagName === 'input' ) {
					var input_type = condition_field.attr('type');
					if ( input_type === 'checkbox' ) {
						if ( value == '1' && condition_field.is(':checked') ) {
							visible_options.push( condition_field.data( 'fill_' + field_id ) );
						}
					}
				}
			});

			var lines_field = jQuery( '[data-field_id="' + field_id + '"]' );

			if ( visible_options.length ) {
				lines_field.find( 'option' ).hide();
				jQuery.each( visible_options, function(i) {
					lines_field.find( 'option[value="' + visible_options[ i ] + '"]' ).show();
				});
				if ( visible_options.indexOf( lines_field.val() ) === -1 ) {
					lines_field.val( visible_options[0] );
					lines_field.find( 'option' ).attr( 'selected', false ).prop( 'selected', false );
					lines_field.find( 'option[value="' + visible_options[0] + '"]' ).attr( 'selected', true ).prop( 'selected', true );
				}
				own_condition = true;
			} else {
				lines_field.val( null );
				lines_field.find( 'option' ).attr( 'selected', false ).prop( 'selected', false );
			}
		}

		return ( own_condition && parent_condition );
	}

});