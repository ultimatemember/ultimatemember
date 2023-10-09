function um_admin_init_users_select() {
	if ( jQuery('.um-user-select-field:visible:not(.um-select2-inited)').length ) {
		function avatarformat( data ) {
			var option;
			if ( ! data.id ) {
				return data.text;
			}

			if ( 'undefined' !== typeof data.img ) {
				option = jQuery('<span><img style="vertical-align: sub; width: 20px; height: 20px;" src="' + data.img + '" /> ' + data.text + '</span>');
			} else {
				let img;
				if ( 'undefined' !== typeof data.element ) {
					if ( 'undefined' !== typeof data.element.attributes['data-img'] ) {
						img = data.element.attributes['data-img']['value'];
					}
				}
				if ( img ) {
					option = jQuery('<img style="vertical-align: sub; width: 20px; height: 20px;" src="' + img + '" /> ' + data.text + '</span>');
				} else {
					option = jQuery('<span>' + data.text + '</span>');
				}
			}
			return option;
		}

		var select2_atts = {
			ajax: {
				url: wp.ajax.settings.url,
				dataType: 'json',
				delay: 250, // delay in ms while typing when to perform a AJAX search
				data: function( params ) {
					var args = {
						action: 'um_get_users', // AJAX action for admin-ajax.php
						search: params.term, // search query
						page: params.page || 1, // infinite scroll pagination
						nonce: um_admin_scripts.nonce
					};

					jQuery.each( jQuery(this)[0].attributes, function() {
						// this.attributes is not a plain object, but an array
						// of attribute nodes, which contain both the name and value
						if ( this.specified ) {
							if ( -1 !== this.name.indexOf( 'data-ajax-args-' ) ) {
								var arg_name = this.name.replace( 'data-ajax-args-', '' ).trim();
								args[ arg_name ] = this.value;
							}
						}
					});

					return args;
				},
				processResults: function( response, params ) {
					params.page = params.page || 1;
					var options = [];

					if ( response.data.users ) {
						jQuery.each( response.data.users, function( index, text ) {
							if ( typeof text.img !== 'undefined' ) {
								options.push({ id: text.ID, text: text.user_login + ' (#' + text.ID + ')', img: text.img });
							} else {
								options.push( { id: text.ID, text: text.user_login + ' (#' + text.ID + ')' } );
							}
						});
					}

					return {
						results: options,
						pagination: {
							more: ( params.page * 20 ) < response.data.total_count
						}
					};
				},
				cache: true
			},
			minimumInputLength: 0, // the minimum of symbols to input before perform a search
			allowClear: true,
			width: "100%",
			allowHtml: true,
			dropdownCssClass: 'um-select2-users-dropdown',
			containerCssClass : 'um-select2-users-container',
			placeholder: jQuery(this).data('placeholder'),
			templateSelection: avatarformat,
			templateResult: avatarformat
		};

		let selector = jQuery('.um-user-select-field:visible:not(.um-select2-inited)');

		selector.each( function() {
			if ( jQuery(this).hasClass('select2-hidden-accessible') ) {
				jQuery(this).removeClass('um-select2-inited').select2( 'destroy' );
			}
		});

		selector.addClass('um-select2-inited').select2( select2_atts );
	}
}


/**
 *
 * @param field_key
 * @param line
 */
function um_add_same_page_log( field_key, line ) {
	var log_field = jQuery( '.um-same-page-update-' + field_key ).find( '.upgrade_log' );
	var previous_html = log_field.html();
	log_field.html( previous_html + line + "<br />" );
}


function um_same_page_wrong_ajax( field_key ) {
	um_add_same_page_log( field_key, wp.i18n.__( 'Wrong AJAX response...', 'ultimate-member' ) );
	um_add_same_page_log( field_key, wp.i18n.__( 'Your upgrade was crashed, please contact with support', 'ultimate-member' ) );
}


function um_same_page_something_wrong( field_key ) {
	um_add_same_page_log( field_key, wp.i18n.__( 'Something went wrong with AJAX request...', 'ultimate-member' ) );
	um_add_same_page_log( field_key, wp.i18n.__( 'Your upgrade was crashed, please contact with support', 'ultimate-member' ) );
}


jQuery(document).ready( function() {
	um_admin_init_users_select();

	/**
	 * Same page upgrade field
	 */
	jQuery( document.body ).on( 'click', '.um-forms-field[data-log-object]', function() {
		var obj = jQuery( this ).data( 'log-object' );
		if ( jQuery( this ).is( ':checked' ) ) {
			jQuery( this ).siblings( '.um-same-page-update-' + obj ).show();
		} else {
			jQuery( this ).siblings( '.um-same-page-update-' + obj ).hide();
		}
	});


	jQuery( document.body ).on( 'click', '.um-admin-form-same-page-update', function() {
		var field_key = jQuery(this).data('upgrade_cb');
		jQuery(this).prop( 'disabled', true );

		um_add_same_page_log( field_key, wp.i18n.__( 'Upgrade Process Started...', 'ultimate-member' ) );

		if ( field_key === 'sync_metatable' ) {
			var metadata_pages = 0;
			var metadata_per_page = 500;
			var current_page;

			jQuery.ajax({
				url: wp.ajax.settings.url,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'um_same_page_update',
					cb_func: 'um_usermeta_fields',
					nonce: um_admin_scripts.nonce
				},
				success: function( response ) {
					get_metadata();
				},
				error: function() {
					um_same_page_something_wrong( field_key );
				}
			});


			/**
			 *
			 * @returns {boolean}
			 */
			function get_metadata() {
				current_page = 1;

				um_add_same_page_log( field_key, wp.i18n.__( 'Getting metadata', 'ultimate-member' ) );
				jQuery.ajax({
					url: wp.ajax.settings.url,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'um_same_page_update',
						cb_func: 'um_get_metadata',
						nonce: um_admin_scripts.nonce
					},
					success: function( response ) {
						if ( typeof response.data.count != 'undefined' ) {
							um_add_same_page_log( field_key, wp.i18n.__( 'There are ', 'ultimate-member' ) + response.data.count + wp.i18n.__( ' metadata rows...', 'ultimate-member' ) );
							um_add_same_page_log( field_key, wp.i18n.__( 'Start metadata upgrading...', 'ultimate-member' ) );

							metadata_pages = Math.ceil( response.data.count / metadata_per_page );

							update_metadata_per_page();
						} else {
							um_same_page_wrong_ajax( field_key );
						}
					},
					error: function() {
						um_same_page_something_wrong( field_key );
					}
				});

				return false;
			}


			function update_metadata_per_page() {
				if ( current_page <= metadata_pages ) {
					jQuery.ajax({
						url: wp.ajax.settings.url,
						type: 'POST',
						dataType: 'json',
						data: {
							action: 'um_same_page_update',
							cb_func: 'um_update_metadata_per_page',
							page: current_page,
							nonce: um_admin_scripts.nonce
						},
						success: function( response ) {
							if ( typeof response.data != 'undefined' ) {
								um_add_same_page_log( field_key, response.data.message );
								current_page++;
								update_metadata_per_page();
							} else {
								um_same_page_wrong_ajax( field_key );
							}
						},
						error: function() {
							um_same_page_something_wrong( field_key );
						}
					});
				} else {
					window.location = um_forms_data.successfully_redirect;
				}
			}
		} else {
			wp.hooks.doAction( 'um_same_page_upgrade', field_key );
		}
	});


	/**
	 * Sortable items
	 */
	jQuery('.um-sortable-items-field').sortable({
		items:                  '.um-sortable-item',
		connectWith:            '.um-admin-drag-col,.um-admin-drag-group',
		forcePlaceholderSize:   true,
		update: function( event, ui ) {
			var sortable_value = [];
			jQuery(this).find('li').each( function() {
				if ( ! jQuery(this).hasClass( 'um-hidden-item' ) ) {
					sortable_value.push( jQuery(this).data('tab-id') );
				}
			});

			jQuery(this).siblings('.um-sortable-items-value' ).val( sortable_value.join( ',' ) );
		}
	});


	/**
	 * Multi-selects sort
	 */
	jQuery('.um-multi-selects-list.um-sortable-multi-selects').sortable({
		items:                  '.um-admin-drag-fld',
		connectWith:            '.um-admin-drag-col,.um-admin-drag-group',
		forcePlaceholderSize:   true
	});

	jQuery('.um-multi-selects-list[data-field_id="_um_sorting_fields"] li').each( function() {
		var if_other = jQuery(this).find( '.um-field-wrapper:not(.um-custom-order-fields) select' ).val();
		if ( if_other === 'other' ) {
			jQuery(this).find( '.um-field-wrapper.um-custom-order-fields' ).show();
		} else {
			jQuery(this).find( '.um-field-wrapper.um-custom-order-fields' ).hide();
		}
	});

	jQuery( '.um-forms-line[data-field_type="md_sorting_fields"] .um-multi-selects-add-option' ).on('click', function() {
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

		let dataTypesOptions = '';
		jQuery.each( um_forms_data.md_sorting_data_types, function( key, label ) {
			dataTypesOptions += '<option value="' + key + '">' + label + '</option>';
		} );

		html += '<span class="um-field-wrapper">' + selector_html + '</span>' +
			'<span class="um-field-control">' +
			'<a href="javascript:void(0);" class="um-select-delete">' + wp.i18n.__( 'Remove', 'ultimate-member' ) + '</a>' +
			'</span>' +
			'<span class="um-field-wrapper um-custom-order-fields"><label>' + wp.i18n.__( 'Meta key', 'ultimate-member' ) + ':&nbsp;<input type="text" name="meta_key" /></label></span>' +
			'<span class="um-field-wrapper um-custom-order-fields"><label>' + wp.i18n.__( 'Data type', 'ultimate-member' ) + ':&nbsp;<select name="data_type" />' +
			dataTypesOptions +
			'</select></label></span>' +
			'<span class="um-field-wrapper um-custom-order-fields"><label>' + wp.i18n.__( 'Order', 'ultimate-member' ) + ':&nbsp;<select name="order" />' +
			'<option value="ASC">' + wp.i18n.__( 'ASC', 'ultimate-member' ) + '</option>' +
			'<option value="DESC">' + wp.i18n.__( 'DESC', 'ultimate-member' ) + '</option>' +
			'</select></label></span>' +
			'<span class="um-field-wrapper um-custom-order-fields"><label>' + wp.i18n.__( 'Label', 'ultimate-member' ) + ':&nbsp;<input type="text" name="label" /></label></span>' +
			'</li>';
		list.append( html );

		list.find('li:last .um-hidden-multi-selects').attr('name', jQuery(this).data('name') ).
		addClass('um-forms-field um-long-field').removeClass('um-hidden-multi-selects').attr('id', list.data('id_attr') + '-' + k).trigger('change');

		jQuery( '#' + list.data('id_attr') + '-' + k ).parents('li').find('.um-field-wrapper.um-custom-order-fields input[name="meta_key"]').attr('name', 'um_metadata[_um_sorting_fields][other_data][' + k + '][meta_key]');
		jQuery( '#' + list.data('id_attr') + '-' + k ).parents('li').find('.um-field-wrapper.um-custom-order-fields input[name="label"]').attr('name', 'um_metadata[_um_sorting_fields][other_data][' + k + '][label]');
		jQuery( '#' + list.data('id_attr') + '-' + k ).parents('li').find('.um-field-wrapper.um-custom-order-fields select[name="data_type"]').attr('name', 'um_metadata[_um_sorting_fields][other_data][' + k + '][data_type]');
		jQuery( '#' + list.data('id_attr') + '-' + k ).parents('li').find('.um-field-wrapper.um-custom-order-fields select[name="order"]').attr('name', 'um_metadata[_um_sorting_fields][other_data][' + k + '][order]');
	});


	jQuery( document.body ).on( 'change', '.um-multi-selects-list[data-field_id="_um_sorting_fields"] .um-field-wrapper:not(.um-custom-order-fields) select', function() {
		var if_other = jQuery(this).val();

		if ( if_other === 'other' ) {
			jQuery(this).parents('li').find( '.um-field-wrapper.um-custom-order-fields' ).show();
		} else {
			jQuery(this).parents('li').find( '.um-field-wrapper.um-custom-order-fields' ).hide();
		}
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

	jQuery( '.um-multi-selects-add-option' ).on('click', function() {
		if ( jQuery(this).parents( '.um-forms-line[data-field_type="md_sorting_fields"]' ).length ) {
			return;
		}

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
		var placeholder = '';
		var placeholder_s = slider.siblings( '.um-slider-range' ).data( 'placeholder-s' );
		var placeholder_p = slider.siblings( '.um-slider-range' ).data( 'placeholder-p' );
		var um_range_min, um_range_max;

		if ( ui ) {
			if ( ui.values[ 0 ] === ui.values[ 1 ] ) {
				placeholder = placeholder_s.replace( '\{value\}', ui.values[ 0 ] )
					.replace( '\{field_label\}', slider.siblings( '.um-slider-range' )
						.data('label') );
			} else {
				placeholder = placeholder_p.replace( '\{min_range\}', ui.values[ 0 ] )
					.replace( '\{max_range\}', ui.values[ 1 ] )
					.replace( '\{field_label\}', slider.siblings( '.um-slider-range' )
						.data('label') );
			}
			um_range_min = ui.values[0];
			um_range_max = ui.values[1];
		} else {
			if ( slider.slider( "values", 0 ) === slider.slider( "values", 1 ) ) {
				placeholder = placeholder_s.replace( '\{value\}', slider.slider( "values", 0 ) )
					.replace( '\{field_label\}', slider.siblings( '.um-slider-range' )
						.data('label') );
			} else {
				placeholder = placeholder_p.replace( '\{min_range\}', slider.slider( "values", 0 ) )
					.replace( '\{max_range\}', slider.slider( "values", 1 ) )
					.replace( '\{field_label\}', slider.siblings( '.um-slider-range' )
						.data('label') );
			}
			um_range_min = slider.slider( "values", 0 );
			um_range_max = slider.slider( "values", 1 );
		}
		slider.siblings( '.um-slider-range' ).html( placeholder );

		slider.siblings( ".um_range_min" ).val( um_range_min );
		slider.siblings( ".um_range_max" ).val( um_range_max );
	}


	jQuery( '.um-md-default-filters-add-option' ).on('click', function() {
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


	jQuery( '.um-multi-text-add-option' ).on('click', function() {
		var list = jQuery(this).siblings( 'ul.um-multi-text-list' );

		var k = 0;
		if ( list.find( 'li:last input.um-forms-field' ).length > 0 ) {
			k = list.find( 'li:last input.um-forms-field' ).attr('id').split("-");
			k = k[1]*1 + 1;
		}

		var text_html = jQuery( '<div>' ).append( list.siblings('.um-hidden-multi-text').clone() ).html();

		var classes = list.data('item_class');

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

		jQuery( '.um-set-image' ).on('click', function(e) {
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

		jQuery('.icon_preview').on('click', function(e) {
			jQuery(this).siblings('.um-set-image').trigger('click');
		});

		jQuery('.um-clear-image').on('click', function(e) {
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
				um_admin_init_users_select();
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
							if ( Array.isArray( value ) ) {
								own_condition = ( value.indexOf( cond_field.val() ) !== -1 );
							} else {
								own_condition = ( cond_field.val() == value );
							}
						}
					} else if ( tagName === 'select' ) {

						if ( Array.isArray( value ) ) {
							own_condition = ( value.indexOf( cond_field.val() ) !== -1 );
						} else {
							own_condition = ( cond_field.val() == value );
						}

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

						if ( Array.isArray( value ) ) {
							own_condition = ( value.indexOf( condition_field.val() ) !== -1 );
						} else {
							own_condition = ( condition_field.val() == value );
						}

					}
				} else if ( tagName == 'select' ) {

					if ( Array.isArray( value ) ) {
						own_condition = ( value.indexOf( condition_field.val() ) !== -1 );
					} else {
						own_condition = ( condition_field.val() == value );
					}

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
			var field_id;
			var visible_options = [];
			var lines_field;

			if ( form_line.data('field_type') === 'sortable_items' ) {
				field_id = form_line.find( '.um-sortable-items-value' ).data('field_id');

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

				lines_field = jQuery( '[data-field_id="' + field_id + '"]' );

				if ( visible_options.length ) {
					lines_field.siblings('.um-sortable-items-field').find('li').addClass('um-hidden-item');
					jQuery.each( visible_options, function(i) {
						lines_field.siblings('.um-sortable-items-field').find('li[data-tab-id="' + visible_options[ i ] + '"]').removeClass('um-hidden-item');
					});

					var sortable_value = [];
					lines_field.siblings('.um-sortable-items-field').find('li').each( function() {
						if ( ! jQuery(this).hasClass( 'um-hidden-item' ) ) {
							sortable_value.push( jQuery(this).data('tab-id') );
						}
					});

					lines_field.val( sortable_value.join( ',' ) );
					lines_field.siblings( '.um-sortable-items-field' ).sortable( 'refresh' );

					own_condition = true;
				} else {
					lines_field.val( null );
				}
			} else {
				field_id = form_line.find( form_line.data('field_type') ).data('field_id');

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

				lines_field = jQuery( '[data-field_id="' + field_id + '"]' );

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
