jQuery(document).ready( function() {


	/**
	 * Multi-selects sort
	 */
	jQuery('.um-multi-selects-list.um-sortable-multi-selects').sortable({
		items:                  '.um-admin-drag-fld',
		connectWith:            '.um-admin-drag-col,.um-admin-drag-group',
		forcePlaceholderSize:   true
	});


	/**
	 * Multi-selects field
	 */
	jQuery( document.body ).on( 'click', '.um-multi-selects-option-line .um-select-delete', function() {
		jQuery( this ).parents( 'li.um-multi-selects-option-line' ).remove();
	});

	/**
	 * Multi-selects field
	 */
	jQuery( document.body ).on( 'click', '.um-md-default-filters-option-line .um-select-delete', function() {
		jQuery( this ).parents( 'li.um-md-default-filters-option-line' ).remove();
	});

	jQuery( '.um-multi-selects-add-option' ).click( function() {
		var list = jQuery(this).siblings('ul.um-multi-selects-list');

		var sortable = list.hasClass( 'um-sortable-multi-selects' );

		var field_id = list.data('field_id');
		var k = 0;
		if ( list.find( 'li:last select.um-forms-field' ).length > 0 ) {
			k = list.find( 'li:last select.um-forms-field' ).attr('id').split("-");
			k = k[1]*1 + 1;
		}

		var selector_html = jQuery( '<div>' ).append( list.siblings('.um-hidden-multi-selects').clone() ).html();

		var html = '<li class="um-multi-selects-option-line' + ( sortable ? ' um-admin-drag-fld' : '' ) + '">';
		if ( sortable ) {
			html += '<span class="um-field-icon"><i class="um-faicon-sort"></i></span>';
		}

		html += '<span class="um-field-wrapper">' + selector_html + '</span>' +
			'<span class="um-field-control">' +
				'<a href="javascript:void(0);" class="um-select-delete">' + wp.i18n.__( 'Remove', 'ultimate-member' ) + '</a>' +
			'</span>' +
		'</li>';
		list.append( html );

		list.find('li:last .um-hidden-multi-selects').attr('name', jQuery(this).data('name') ).
		addClass('um-forms-field um-long-field').removeClass('um-hidden-multi-selects').attr('id', list.data('id_attr') + '-' + k);
	});

	var um_local_date = new Date();
	var um_gmt_hours = -um_local_date.getTimezoneOffset() / 60;
	jQuery('input[name="um-gmt-offset"]').val( um_gmt_hours );

	//slider filter
	jQuery('.um-admin-metabox').find('.um-slider').each( function() {
		var slider = jQuery( this );

		var min_default_value = parseInt( slider.data('min') );
		var max_default_value = parseInt( slider.data('max') );

		if ( typeof jQuery( '#' + slider.data('field_name') + '_min' ).val() != 'undefined' ) {
			min_default_value = jQuery( '#' + slider.data('field_name') + '_min' ).val();
		}
		if ( typeof jQuery( '#' + slider.data('field_name') + '_max' ).val() != 'undefined' ) {
			max_default_value = jQuery( '#' + slider.data('field_name') + '_max' ).val();
		}

		var default_value = [ min_default_value, max_default_value ];

		slider.slider({
			range: true,
			min: parseInt( slider.data('min') ),
			max: parseInt( slider.data('max') ),
			values: default_value,
			create: function( event, ui ) {
				//console.log( ui );
			},
			step: 1,
			slide: function( event, ui ) {
				um_set_range_label( jQuery( this ), ui );
			},
			stop: function( event, ui ) {

			}
		});

		um_set_range_label( slider );
	});


	//datepicker filter
	jQuery('.um-admin-metabox').find('.um-datepicker-filter').each( function() {
		var elem = jQuery(this);

		var min = new Date( elem.data('date_min')*1000 );
		var max = new Date( elem.data('date_max')*1000 );

		var $input = elem.pickadate({
			selectYears: true,
			min: min,
			max: max,
			formatSubmit: 'yyyy/mm/dd',
			hiddenName: true,
			onOpen: function() {
				elem.blur();
			},
			onClose: function() {
				elem.blur();
			},
			onSet: function( context ) {

			}
		});

		var $picker = $input.pickadate('picker');
		$picker.set( 'select', elem.data('value')*1000 );
	});


	//timepicker filter
	jQuery('.um-admin-metabox').find('.um-timepicker-filter').each( function() {
		var elem = jQuery(this);

		//using arrays formatted as [HOUR,MINUTE]

		var min = elem.data('min');
		var max = elem.data('max');
		var picker_min = min.split(':');
		var picker_max = max.split(':');

		var $input = elem.pickatime({
			format:         elem.data('format'),
			interval:       parseInt( elem.data('intervals') ),
			min: [picker_min[0],picker_min[1]],
			max: [picker_max[0],picker_max[1]],
			formatSubmit:   'HH:i',
			hiddenName:     true,
			onOpen:         function() { elem.blur(); },
			onClose:        function() { elem.blur(); },
			onSet:          function( context ) {

			}
		});
	});

	var um_member_dir_filters_busy = false;

	jQuery( document.body ).on( 'change', '.um-md-default-filters-option-line .um-field-wrapper select', function() {
		if ( um_member_dir_filters_busy ) {
			return;
		}

		var obj = jQuery(this);
		var filter_key = obj.val();
		var directory_id = obj.data('member_directory');

		um_member_dir_filters_busy = true;
		wp.ajax.send( 'um_member_directory_default_filter_settings', {
			data: {
				key: filter_key,
				directory_id: directory_id,
				nonce: um_admin_scripts.nonce
			},
			success: function( data ) {
				var field_wrapper = obj.parents( '.um-md-default-filters-option-line' ).find('.um-field-wrapper2');
				field_wrapper.html( data.field_html );

				um_member_dir_filters_busy = false;

				//slider filter
				field_wrapper.find('.um-slider').each( function() {
					var slider = jQuery( this );
					var min_default_value = parseInt( slider.data('min') );
					var max_default_value = parseInt( slider.data('max') );

					var default_value = [ min_default_value, max_default_value ];

					slider.slider({
						range: true,
						min: parseInt( slider.data('min') ),
						max: parseInt( slider.data('max') ),
						values: default_value,
						create: function( event, ui ) {
							//console.log( ui );
						},
						step: 1,
						slide: function( event, ui ) {
							um_set_range_label( jQuery( this ), ui );
						},
						stop: function( event, ui ) {

						}
					});

					um_set_range_label( slider );
				});


				//datepicker filter
				field_wrapper.find('.um-datepicker-filter').each( function() {
					var elem = jQuery(this);

					var min = new Date( elem.data('date_min')*1000 );
					var max = new Date( elem.data('date_max')*1000 );

					var $input = elem.pickadate({
						selectYears: true,
						min: min,
						max: max,
						formatSubmit: 'yyyy/mm/dd',
						hiddenName: true,
						onOpen: function() {
							elem.blur();
						},
						onClose: function() {
							elem.blur();
						},
						onSet: function( context ) {

						}
					});
				});


				//timepicker filter
				field_wrapper.find('.um-timepicker-filter').each( function() {
					var elem = jQuery(this);

					//using arrays formatted as [HOUR,MINUTE]

					var min = elem.data('min');
					var max = elem.data('max');
					var picker_min = min.split(':');
					var picker_max = max.split(':');

					var $input = elem.pickatime({
						format:         elem.data('format'),
						interval:       parseInt( elem.data('intervals') ),
						min: [picker_min[0],picker_min[1]],
						max: [picker_max[0],picker_max[1]],
						formatSubmit:   'HH:i',
						hiddenName:     true,
						onOpen:         function() { elem.blur(); },
						onClose:        function() { elem.blur(); },
						onSet:          function( context ) {

						}
					});
				});


			},
			error: function( data ) {
				return false;
			}
		});

	});

	function um_set_range_label( slider, ui ) {
		var placeholder = slider.siblings( '.um-slider-range' ).data( 'placeholder' );

		if( ui ) {
			placeholder = placeholder.replace( '\{min_range\}', ui.values[ 0 ] )
				.replace( '\{max_range\}', ui.values[ 1 ] )
				.replace( '\{field_label\}', slider.siblings( '.um-slider-range' )
					.data('label') );
		} else {
			placeholder = placeholder.replace( '\{min_range\}', slider.slider( "values", 0 ) )
				.replace( '\{max_range\}', slider.slider( "values", 1 ) )
				.replace( '\{field_label\}', slider.siblings( '.um-slider-range' )
					.data('label') );
		}
		slider.siblings( '.um-slider-range' ).html( placeholder );

		slider.siblings( ".um_range_min" ).val( slider.slider( "values", 0 ) );
		slider.siblings( ".um_range_max" ).val( slider.slider( "values", 1 ) );
	}


	jQuery( '.um-md-default-filters-add-option' ).click( function() {
		if ( um_member_dir_filters_busy ) {
			return;
		}

		var list = jQuery(this).siblings('ul.um-md-default-filters-list');

		var field_id = list.data('field_id');
		var k = 0;
		if ( list.find( 'li:last select.um-forms-field' ).length > 0 ) {
			k = list.find( 'li:last select.um-forms-field' ).attr('id').split("-");
			k = k[1]*1 + 1;
		}

		var selector_html = jQuery( '<div>' ).append( list.siblings('.um-hidden-md-default-filters').clone() ).html();

		list.append(
			'<li class="um-md-default-filters-option-line"><span class="um-field-wrapper">' + selector_html +
			'</span></span><span class="um-field-control"><a href="javascript:void(0);" class="um-select-delete">' + wp.i18n.__( 'Remove', 'ultimate-member' ) + '</a></span><span class="um-field-wrapper2 um"></li>'
		);

		list.find('li:last .um-hidden-md-default-filters').attr('name', jQuery(this).data('name') ).
		addClass('um-forms-field um-long-field').removeClass('um-hidden-md-default-filters').attr('id', list.data('id_attr') + '-' + k);

		list.find('li:last .um-field-wrapper select').trigger('change');
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
			'</span><span class="um-field-control"><a href="javascript:void(0);" class="um-text-delete">' + wp.i18n.__( 'Remove', 'ultimate-member' ) + '</a></span></li>'
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
					text: wp.i18n.__( 'Select', 'ultimate-member' )
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


	function um_distinct( value, index, self ) {
		return self.indexOf( value ) === index;
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
			if ( conditional[0].indexOf( '||' ) === -1 ) {
				var condition_field = jQuery( '#' + prefix + '_' + conditional[0] );

				if ( typeof condition_field.parents('.um-forms-line').data('conditional') !== 'undefined' ) {
					parent_condition = check_condition( condition_field.parents('.um-forms-line') );
				}
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
		} else if ( condition === '><' ) {
			var condition_field = jQuery( '#' + prefix + '_' + conditional[0] + '_' + conditional[2] );

			if ( typeof condition_field.parents('.um-forms-line').data('conditional') !== 'undefined' ) {
				parent_condition = check_condition( condition_field.parents('.um-forms-line') );
			}
		}

		var own_condition = false;
		if ( condition === '=' ) {
			if ( conditional[0].indexOf( '||' ) !== -1 ) {
				var selectors = conditional[0].split('||');
				var complete_condition = false;

				jQuery.each( selectors, function(i) {
					var cond_field = jQuery( '#' + prefix + '_' + selectors[i] );

					own_condition = false;
					parent_condition = true;

					if ( typeof cond_field.parents('.um-forms-line').data('conditional') !== 'undefined' ) {
						parent_condition = check_condition( cond_field.parents('.um-forms-line') );
					}

					var tagName = cond_field.prop("tagName").toLowerCase();

					if ( tagName === 'input' ) {
						var input_type = cond_field.attr('type');
						if ( input_type === 'checkbox' ) {
							own_condition = ( value == '1' ) ? cond_field.is(':checked') : ! cond_field.is(':checked');
						} else {
							own_condition = ( cond_field.val() == value );
						}
					} else if ( tagName === 'select' ) {
						own_condition = ( cond_field.val() == value );
					}

					if ( own_condition && parent_condition ) {
						complete_condition = true;
					}
				});

				return complete_condition;
			} else {
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

				return ( own_condition && parent_condition );
			}

		} else if ( condition === '!=' ) {
			if ( conditional[0].indexOf( '||' ) !== -1 ) {
				var selectors = conditional[0].split('||');
				var complete_condition = false;

				jQuery.each( selectors, function(i) {
					var cond_field = jQuery( '#' + prefix + '_' + selectors[i] );

					own_condition = false;
					parent_condition = true;
					if ( typeof cond_field.parents('.um-forms-line').data('conditional') !== 'undefined' ) {
						parent_condition = check_condition( cond_field.parents('.um-forms-line') );
					}

					var tagName = cond_field.prop("tagName").toLowerCase();

					if ( tagName === 'input' ) {
						var input_type = cond_field.attr('type');
						if ( input_type === 'checkbox' ) {
							own_condition = ( value == '1' ) ? ! cond_field.is(':checked') : cond_field.is(':checked');
						} else {
							own_condition = ( cond_field.val() != value );
						}
					} else if ( tagName === 'select' ) {
						own_condition = ( cond_field.val() != value );
					}

					if ( own_condition && parent_condition ) {
						complete_condition = true;
					}
				});

				return complete_condition;
			} else {
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

				return ( own_condition && parent_condition );
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
				} else if ( tagName == 'select' ) {
					if ( ! value && condition_field.val() ) {
						visible_options = visible_options.concat( condition_field.val() );
						visible_options = visible_options.filter( um_distinct );
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

			return ( own_condition && parent_condition );
		} else if ( condition === '><' ) {

			var tagName = condition_field.prop("tagName").toLowerCase();

			if ( tagName == 'input' ) {
				var input_type = condition_field.attr('type');
				if ( input_type == 'checkbox' ) {
					own_condition = condition_field.is(':checked');
				}
			}

			return ( own_condition && parent_condition );

		}

		return false;
	}

});