if ( typeof ( window.UM ) !== 'object' ) {
	window.UM = {};
}

if ( typeof ( UM.frontend ) !== 'object' ) {
	UM.frontend = {};
}

UM.frontend.directories = {
	list: [],
	getHash: function( $directory ) {
		return $directory.data( 'hash' );
	},
	filtersHash: function( str ) {
		let hash = 0;
		for (let i = 0; i < str.length; i++) {
			const char = str.charCodeAt(i);
			hash = ((hash << 5) - hash) + char;
			hash |= 0; // Convert to 32-bit integer
		}
		return hash;
	},
	filters: {
		checkEmpty: function ( $filtersForm ) {
			let $clearFilters = $filtersForm.find('.um-clear-filters-a');
			let $filters = $filtersForm.find('.um-search-filter input, .um-search-filter select');

			let emptyFilters = 0;
			$filters.each( function () {
				let $filterWrapper = jQuery(this).parents( '.um-search-filter' );

				let filterType = $filterWrapper.data( 'filter-type' );

				if ( 'text' === filterType ) {
					let filterValue = jQuery(this).val();
					if ( '' === filterValue ) {
						emptyFilters++;
					}
				} else if ( 'select' === filterType ) {
					let filterValue = jQuery(this).val();
					if ( 0 === filterValue.length ) {
						emptyFilters++;
					}
				} else if ( 'slider' === filterType ) {
					// Get from "from" and "to" fields.
					let $rangeContainer = jQuery(this).parents( '.um-range-container' );
					const fromSlider = $rangeContainer.find( '.um-from-slider' )[0];
					const toSlider = $rangeContainer.find( '.um-to-slider' )[0];

					// Empty when selected full range.
					if ( parseInt( fromSlider.value, 10) === parseInt( fromSlider.min, 10 ) &&
						parseInt( toSlider.value, 10) === parseInt( toSlider.max, 10 ) ) {
						emptyFilters++;
					}
				} else if ( 'datepicker' === filterType ) {
					let $rangeContainer = jQuery(this).parents( '.um-date-range-row' );
					const fromDate = $rangeContainer.find( '[data-range="from"]' );
					const toDate = $rangeContainer.find( '[data-range="to"]' );

					if ( '' === fromDate.val() && '' === toDate.val() ) {
						emptyFilters++;
					}
				} else if ( 'timepicker' === filterType ) {
					let $rangeContainer = jQuery(this).parents( '.um-time-range-row' );
					const fromTime = $rangeContainer.find( '[data-range="from"]' );
					const toTime = $rangeContainer.find( '[data-range="to"]' );

					if ( '' === fromTime.val() && '' === toTime.val() ) {
						emptyFilters++;
					}
				}
			});

			// Show clear filters button in case where are not empty filters.
			if ( $filters.length === emptyFilters ) {
				$clearFilters.addClass( 'um-hidden' ).prop( 'disabled', true );
			} else {
				$clearFilters.removeClass( 'um-hidden' ).prop( 'disabled', false );
			}
		}
	},
};

UM.frontend.directory = function( hash ) {
	this.hash = hash;
	this.wrapper = jQuery('.um-directory[data-hash="' + hash + '"]');
};

UM.frontend.directory.prototype = {
	hash: '',
	wrapper: null,
	isBusy: false,
	getHash: function () {
		return this.hash;
	},
	getWrapper: function () {
		return this.wrapper;
	},
	preloaderShow: function () {
		this.isBusy = true;
		this.wrapper.find('.um-members-overlay').show();
	},
	preloaderHide: function () {
		this.isBusy = false;
		this.wrapper.find('.um-members-overlay').hide();
	}
}

var um_members_directory_busy = [];

var um_member_directory_last_data = [];

function um_parse_current_url() {
	var data = {};

	var query = window.location.search.substring(1);
	var attrs = query.split( '&' );
	jQuery.each( attrs, function( i ) {
		var attr = attrs[ i ].split( '=' );
		data[ attr[0] ] = attr[1];
	});
	return data;
}


function um_get_data_for_directory( directory, search_key ) {
	var hash = um_members_get_hash( directory );
	var data = {};

	var url_data = UM.frontend.url.parseData();
	jQuery.each( url_data, function( key ) {
		if ( key.indexOf( '_' + hash ) !== -1 && url_data[ key ] !== '' ) {
			data[ key.replace( '_' + hash, '' ) ] = url_data[ key ];
		}
	});

	if ( ! search_key ) {
		return data;
	} else {
		if ( typeof data[ search_key ] !== 'undefined' ) {
			try {
				//data[ search_key ] = decodeURI( data[ search_key ] );
				data[ search_key ] = decodeURIComponent( data[ search_key ] );
			} catch(e) { // catches a malformed URI
				console.error(e);
			}
		}

		return data[ search_key ];
	}
}


function um_set_url_from_data( directory, key, value ) {
	var hash = um_members_get_hash( directory );
	var data = um_get_data_for_directory( directory );

	var new_data = {};

	if ( Array.isArray( value ) ) {
		jQuery.each( value, function( i ) {
			value[ i ] = encodeURIComponent( value[ i ] );
		});
		value = value.join( '||' );
	} else if ( ! jQuery.isNumeric( value ) ) {
		value = value.split( '||' );
		jQuery.each( value, function( i ) {
			value[ i ] = encodeURIComponent( value[ i ] );
		});
		value = value.join( '||' );
	}

	if ( value !== '' ) {
		new_data[ key + '_' + hash ] = value;
	}
	jQuery.each( data, function( data_key ) {
		if ( key === data_key ) {
			if ( value !== '' ) {
				new_data[ data_key + '_' + hash ] = value;
			}
		} else {
			new_data[ data_key + '_' + hash ] = data[ data_key ];
		}
	});

	// added data of other directories to the url
	jQuery.each( UM.frontend.directories.list, function( k ) {
		let dirHash = UM.frontend.directories.list[ k ].getHash();
		if ( dirHash !== hash ) {
			var other_directory = jQuery( '.um-directory[data-hash="' + dirHash + '"]' );
			var dir_data = um_get_data_for_directory( other_directory );

			jQuery.each( dir_data, function( data_key ) {
				new_data[ data_key + '_' + dirHash ] = dir_data[ data_key ];
			});
		}
	});

	var query_strings = [];
	jQuery.each( new_data, function( data_key ) {
		query_strings.push( data_key + '=' + new_data[ data_key ] );
	});

	query_strings = wp.hooks.applyFilters( 'um_member_directory_url_attrs', query_strings );

	var query_string = '?' + query_strings.join( '&' );
	if ( query_string === '?' ) {
		query_string = '';
	}

	window.history.pushState("string", "UM Member Directory", window.location.origin + window.location.pathname + query_string );
}


function um_members_get_hash( directory ) {
	return directory.data( 'hash' );
}

function um_is_directory_busy( directory ) {
	var hash = um_members_get_hash( directory );
	return typeof um_members_directory_busy[ hash ] != 'undefined' && um_members_directory_busy[ hash ];
}


function um_members_show_preloader( directory ) {
	um_members_directory_busy[ um_members_get_hash( directory ) ] = true;
	directory.find('.um-members-overlay').show();
}


function um_members_hide_preloader( directory ) {
	um_members_directory_busy[ um_members_get_hash( directory ) ] = false;
	directory.find('.um-members-overlay').hide();
}


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


function um_get_search( directory ) {
	if ( directory.find('.um-search-line').length ) {
		return directory.find( '.um-search-line' ).val();
	} else {
		return '';
	}
}

function um_get_sort( directory ) {
	return directory.data( 'sorting' );
}

function um_get_current_page( directory ) {
	var page = directory.data( 'page' );
	if ( ! page || typeof page == 'undefined' ) {
		page = 1;
	}
	return page;
}

function um_time_convert( time, range ) {
	var hours = Math.floor( time / 60 );
	var minutes = time % 60;

	if ( minutes >= 60 ) {
		minutes = 0;
		hours = hours + 1;

		if ( hours >= 24 ) {
			hours = 0;
		}
	}

	if ( minutes < 10 ) {
		minutes = '0' + minutes;
	}

	return hours + ":" + minutes;
}

function um_ajax_get_members( directory, args ) {
	/**
	 * Operates with the next data:
	 *
	 * 1) Page - getting from directory data 'page'
	 * 2) Sort - getting from 'um-member-directory-sorting-options' field value
	 * 3) Search - getting from 'um-search-line' field value
	 * 4) Filters - getting from URL data by 'um_get_data_for_directory' function
	 *
	 */

	var hash = um_members_get_hash( directory );

	var allow = wp.hooks.applyFilters( 'um_member_directory_get_members_allow', true, hash, directory );
	if ( ! allow ) {
		setTimeout( um_ajax_get_members, 600, directory, args );
		return;
	}

	var page = um_get_current_page( directory );
	var search = um_get_search( directory );
	var sorting = um_get_sort( directory );

	var local_date = new Date();
	var gmt_hours = -local_date.getTimezoneOffset() / 60;

	var request = {
		directory_id:   hash,
		page:           page,
		search:         search,
		sorting:        sorting,
		gmt_offset:     gmt_hours,
		post_refferer:  directory.data('base-post'),
		nonce:          directory.data('nonce')
	};

	if ( directory.find('.um-search-filter').length ) {
		directory.find('.um-search-filter').each( function() {
			var filter = jQuery(this);

			if ( filter.find( '.um-slider' ).length ) {
				var filter_name = filter.find( '.um-slider' ).data('field_name');

				var value_from = um_get_data_for_directory( directory, 'filter_' + filter_name + '_from' );
				var value_to = um_get_data_for_directory( directory, 'filter_' + filter_name + '_to' );
				if ( typeof value_from != 'undefined' || typeof value_to != 'undefined' ) {
					request[ filter_name ] = [ value_from, value_to ];
				}
			} else if ( filter.find( '.um-datepicker-filter' ).length ) {
				var filter_name = filter.find( '.um-datepicker-filter' ).data('filter_name');
				var value_from = um_get_data_for_directory( directory, 'filter_' + filter_name + '_from' );
				var value_to = um_get_data_for_directory( directory, 'filter_' + filter_name + '_to' );
				if (  typeof value_from != 'undefined' || typeof value_to != 'undefined') {
					request[ filter_name ] = [ value_from, value_to ];
				}
			} else if ( filter.find( '.um-timepicker-filter' ).length ) {
				var filter_name = filter.find( '.um-timepicker-filter' ).data('filter_name');
				var value_from = um_get_data_for_directory( directory, 'filter_' + filter_name + '_from' );
				var value_to = um_get_data_for_directory( directory, 'filter_' + filter_name + '_to' );

				if ( typeof value_from != 'undefined' ) {
					var value_from = value_from.split(':');
					var hours = value_from[0]*1;
					if ( hours < 10 ) {
						hours = '0' + hours;
					}

					var minutes = value_from[1]*1;
					if ( minutes < 10 ) {
						minutes = '0' + minutes;
					}

					value_from = hours + ':' + minutes + ':00';
				}
				if ( typeof value_to != 'undefined' ) {
					var val_to = value_to.split(':');
					var minutes = val_to[1]*1;

					var hours = val_to[0]*1;
					if ( hours < 10 ) {
						hours = '0' + hours;
					}

					if ( minutes < 10 ) {
						minutes = '0' + minutes;
					}

					value_to = hours + ':' + minutes + ':59';
				}

				if ( typeof value_from != 'undefined' || typeof value_to != 'undefined' ) {
					request[ filter_name ] = [ value_from, value_to ];
				}
			} else if (  filter.find( 'select' ).length ) {
				var filter_name = filter.find('select').attr('name');
				var value = um_get_data_for_directory( directory, 'filter_' + filter_name );
				if ( typeof value != 'undefined' ) {
					value = UM.common.form.unsanitizeValue( value );
					request[ filter_name ] = value.split( '||' );
				}
			} else if ( filter.hasClass( 'um-text-filter-type' ) && filter.find('input[type="text"]').length ) {
				var filter_name = filter.find('input[type="text"]').attr('name');
				var value = um_get_data_for_directory( directory, 'filter_' + filter_name );
				if ( typeof value != 'undefined' ) {
					value = UM.common.form.unsanitizeValue( value );
					request[ filter_name ] = value;
				}
			} else {
				request = wp.hooks.applyFilters( 'um_member_directory_custom_filter_handler', request, filter, directory );
			}
		});
	}

	request = wp.hooks.applyFilters( 'um_member_directory_filter_request', request );

	wp.ajax.send( 'um_get_members', {
		data:  request,
		success: function( answer ) {
			//set last data hard for using on layouts reloading
			um_member_directory_last_data[ hash ] = answer;

			um_build_template( directory, answer );

			// var pagination_template = wp.template( 'um-members-pagination' );
			//directory.find('.um-members-pagination-box').html( pagination_template( answer ) );
			directory.find('.um-members-pagination-box').html( answer.pagination );

			directory.data( 'total_pages', answer.total_pages );

			if ( answer.total_pages ) {
				directory.find( '.um-member-directory-sorting-options' ).prop( 'disabled', false );
				directory.find( '.um-member-directory-view-type' ).removeClass( 'um-disabled' );
			} else {
				directory.find( '.um-member-directory-sorting-options' ).prop( 'disabled', true );
				directory.find( '.um-member-directory-view-type' ).addClass( 'um-disabled' );
			}

			wp.hooks.doAction( 'um_member_directory_loaded', directory, answer );

			um_init_new_dropdown();

			um_members_hide_preloader( directory );
		},
		error: function( data ) {
			console.log( data );

			um_members_hide_preloader( directory );
		}
	});
}


function um_build_template( directory, data ) {
	let layout = directory.data('view_type');
	// var template = wp.template( 'um-member-' + layout + '-' + um_members_get_hash( directory ) );

	if( jQuery('.um-' + um_members_get_hash( directory )).length ) {
		directory.find('.um-members-wrapper').html('').prepend(data[ 'content_' + layout ]);

		if ( '' !== data.counter ) {
			directory.find('.um-members-counter').text( data.counter ).show();
		} else {
			directory.find('.um-members-counter').text( data.counter ).hide();
		}

		// var header_template = wp.template('um-members-header');
		// directory.find('.um-members-intro').remove();
		//
		// var generate_header = wp.hooks.applyFilters('um_member_directory_generate_header', false, directory);
		//
		// if ((typeof data.is_search != 'undefined' && data.is_search) || generate_header) {
		// 	directory.find('.um-members-wrapper').prepend(header_template(data));
		// }

		directory.addClass('um-loaded');

		// It's made via hook because resize is triggered with debounce delay.
		// wp.hooks.addAction( 'um_window_resize', 'um_members', function() {
		// 	if (directory.find('.um-members.um-members-grid').length) {
		// 		UM_Member_Grid(directory.find('.um-members.um-members-grid'));
		// 	}
		// });

		jQuery(document).trigger('um_build_template', [directory, data]);
		jQuery(window).trigger('resize');

		wp.hooks.doAction( 'um_member_directory_build_template', directory );

		UM.common.tipsy.init();
	}
}


function um_get_filters_data( directory ) {
	var filters_data = [];

	directory.find('.um-search-filter').each( function() {

		var filter = jQuery(this);
		var filter_name,
			filter_title;

		var filter_type;
		if ( filter.find('input.um-datepicker-filter').length ) {
			filter_type = 'datepicker';

			filter.find('input.um-datepicker-filter').each( function() {
				var range = jQuery(this).data('range');
				if ( range === 'to' ) {
					return;
				}

				var filter_name = jQuery(this).data('filter_name');

				var filter_value_from = um_get_data_for_directory( directory, 'filter_' + filter_name + '_from' );
				var filter_value_to = um_get_data_for_directory( directory, 'filter_' + filter_name + '_to' );
				if ( typeof filter_value_from === 'undefined' && typeof filter_value_to === 'undefined' ) {
					return;
				}

				var from_val = jQuery(this).val();
				var to_val = directory.find('input.um-datepicker-filter[data-range="to"][data-filter_name="' + filter_name + '"]').val();

				var value;
				if ( from_val === to_val ) {
					value = to_val;
				} else if ( from_val !== '' &&  to_val !== '' ) {
					value = from_val + ' - ' + to_val;
				} else if ( from_val === '' ) {
					value = 'before ' + to_val;
				} else if ( to_val === '' ) {
					value = 'since ' + from_val;
				}

				filters_data.push( {'name':filter_name, 'label':jQuery(this).data('filter-label'), 'value_label': value, 'value':[filter_value_from, filter_value_to], 'type':filter_type} );
			});

		} else if( filter.find('input.um-timepicker-filter').length ) {
			filter_type = 'timepicker';

			filter.find('input.um-timepicker-filter').each( function() {
				var range = jQuery(this).data('range');
				if ( range === 'to' ) {
					return;
				}

				var filter_name = jQuery(this).data('filter_name');

				var filter_value_from = um_get_data_for_directory( directory, 'filter_' + filter_name + '_from' );
				var filter_value_to = um_get_data_for_directory( directory, 'filter_' + filter_name + '_to' );
				if ( typeof filter_value_from === 'undefined' && typeof filter_value_to === 'undefined' ) {
					return;
				}

				var from_val = jQuery(this).val();
				var to_val = directory.find('input.um-timepicker-filter[data-range="to"][data-filter_name="' + filter_name + '"]').val();

				var value;
				if ( from_val === to_val ) {
					value = to_val;
				} else if ( from_val !== '' &&  to_val !== '' ) {
					value = from_val + ' - ' + to_val;
				} else if ( from_val === '' ) {
					value = 'before ' + to_val;
				} else if ( to_val === '' ) {
					value = 'since ' + from_val;
				}

				filters_data.push( {'name':filter_name, 'label':jQuery(this).data('filter-label'), 'value_label': value, 'value':[filter_value_from, filter_value_to], 'type':filter_type} );
			});
		} else if( filter.find('select').length ) {

			filter_type = 'select';
			filter_name = filter.find('select').attr('name');
			filter_title = filter.find('select').data('placeholder');

			var filter_value = um_get_data_for_directory( directory, 'filter_' + filter_name );

			if ( typeof filter_value == 'undefined' ) {
				filter_value = [];
			} else {
				filter_value = UM.common.form.unsanitizeValue( filter_value );
				filter_value = filter_value.split( '||' );
			}

			jQuery.each( filter_value, function(i) {
				var filter_value_title = filter.find('select option[value="' + filter_value[ i ] + '"]').data('value_label');
				filters_data.push( {'name':filter_name, 'label':filter_title, 'value_label':filter_value_title, 'value':filter_value[ i ], 'type':filter_type} );
			});

		} else if( filter.hasClass('um-text-filter-type') && filter.find('input[type="text"]').length ) {

			filter_type = 'text';
			filter_name = filter.find('input[type="text"]').attr('name');
			filter_title = filter.find('input[type="text"]').attr('placeholder');

			var filter_value = um_get_data_for_directory( directory, 'filter_' + filter_name );
			if ( typeof filter_value == 'undefined' ) {
				filter_value = '';
			}

			if ( filter_value != '' ) {
				filters_data.push( {'name':filter_name, 'label':filter_title, 'value_label':filter_value, 'value':filter_value, 'type':filter_type} );
			}

		} else if( filter.find('div.ui-slider').length ) {
			filter_type = 'slider';

			filter_name = filter.find('div.ui-slider').data( 'field_name' );
			var filter_value_from = um_get_data_for_directory( directory, 'filter_' + filter_name + '_from' );
			var filter_value_to = um_get_data_for_directory( directory, 'filter_' + filter_name + '_to' );

			if ( typeof filter_value_from === 'undefined' && typeof filter_value_to === 'undefined' ) {
				return;
			}

			var filter_value_title;
			if ( filter_value_from === filter_value_to ) {
				filter_value_title = filter.find('div.um-slider-range').data( 'placeholder-s' ).replace( '\{value\}', filter_value_from )
					.replace( '\{field_label\}', filter.find('div.um-slider-range').data('label') );
			} else {
				filter_value_title = filter.find('div.um-slider-range').data( 'placeholder-p' ).replace( '\{min_range\}', filter_value_from )
					.replace( '\{max_range\}', filter_value_to )
					.replace( '\{field_label\}', filter.find('div.um-slider-range').data('label') );
			}

			filter_title = filter.find('div.um-slider-range').data('label');

			filters_data.push( {'name':filter_name, 'label':filter_title, 'value_label':filter_value_title, 'value':[filter_value_from, filter_value_to], 'type':filter_type} );
		} else {

			filters_data = wp.hooks.applyFilters( 'um_member_directory_get_filter_data', filters_data, directory, filter );

		}
	});

	return filters_data;
}



function um_run_search( directory ) {
	if ( um_is_directory_busy( directory ) ) {
		return;
	}
	um_members_show_preloader( directory );

	var pre_search = um_get_data_for_directory( directory, 'search' );

	var search = UM.common.form.sanitizeValue( directory.find('.um-search-line').val() );
	if ( search === pre_search || ( search === '' && typeof pre_search == 'undefined' ) ) {
		um_members_hide_preloader( directory );
		return;
	}

	directory.data( 'general_search', search );
	um_set_url_from_data( directory, 'search', search );

	//set 1st page after search
	directory.data( 'page', 1 );
	um_set_url_from_data( directory, 'page', '' );


	var ignore_after_search = false;
	ignore_after_search = wp.hooks.applyFilters( 'um_member_directory_ignore_after_search', ignore_after_search, directory );

	if ( ! ignore_after_search ) {
		var show_after_search = directory.data('must-search');
		if ( show_after_search === 1 ) {
			search = um_get_search( directory );
			if ( directory.find( '.um-members-filter-remove' ).length === 0 && ! search ) {
				directory.data( 'searched', 0 );
				directory.find('.um-members-grid, .um-members-list, .um-members-intro').remove();
				directory.find( '.um-member-directory-sorting-options' ).prop( 'disabled', true );
				directory.find( '.um-member-directory-view-type' ).addClass( 'um-disabled' );

				wp.hooks.doAction( 'um_member_directory_clear_not_searched', directory );

				um_members_hide_preloader( directory );
				return;
			}
		}
	}

	directory.data( 'searched', 1 );

	directory.find( '.um-member-directory-sorting-options' ).prop( 'disabled', false );
	directory.find( '.um-member-directory-view-type' ).removeClass( 'um-disabled' );

	um_ajax_get_members( directory );
}


function um_slider_filter_init( directory ) {
	directory.find('.um-slider').each( function() {
		var slider = jQuery( this );
		var directory = slider.parents('.um-directory');

		var filter_name = slider.data('field_name');

		var min_default_value = um_get_data_for_directory( directory, 'filter_' + filter_name + '_from' );
		var max_default_value = um_get_data_for_directory( directory, 'filter_' + filter_name + '_to' );
		if ( typeof min_default_value == 'undefined' ) {
			min_default_value = parseInt( slider.data('min') );
		}

		if ( typeof max_default_value == 'undefined' ) {
			max_default_value =  parseInt( slider.data('max') );
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
				if ( ! um_is_directory_busy( directory ) ) {

					um_members_show_preloader( directory );

					um_set_url_from_data( directory, 'filter_' + filter_name + '_from', ui.values[0] );
					um_set_url_from_data( directory, 'filter_' + filter_name + '_to', ui.values[1] );

					//set 1st page after filtration
					directory.data( 'page', 1 );
					um_set_url_from_data( directory, 'page', '' );
					um_ajax_get_members( directory );

					// um_change_tag( directory );

					directory.data( 'searched', 1 );
					directory.find( '.um-member-directory-sorting-options' ).prop( 'disabled', false );
					directory.find( '.um-member-directory-view-type' ).removeClass( 'um-disabled' );
				}
			}
		});

		um_set_range_label( slider );
	});
}


jQuery(document.body).ready( function() {

	/**
	 * Change View Type Handlers
	 */

	//change layout handler
	jQuery( document.body ).on( 'click', '.um-directory .um-member-view-switcher:not(.um-disabled) .um-button-in-group:not(.current)', function() {
		let $this = jQuery(this);
		let directory = $this.parents('.um-directory');
		if ( um_is_directory_busy( directory ) ) {
			return false;
		}

		um_members_show_preloader( directory );

		let data = um_member_directory_last_data[ um_members_get_hash( directory ) ];
		if ( data !== null ) {

			let prevType = $this.parents('.um-member-view-switcher').find('.um-button-in-group.current').data('type');
			directory.find('.um-members-wrapper.um-members-' + prevType).removeClass('um-members-' + prevType);

			$this.parents('.um-member-view-switcher').find('.um-button-in-group').removeClass('current');
			$this.addClass('current');

			let layout = $this.data('type');
			let defaultView = $this.data('default');

			directory.find('.um-members-wrapper').addClass('um-members-' + layout);

			if ( defaultView ) {
				um_set_url_from_data( directory, 'view_type', '' );
			} else {
				um_set_url_from_data( directory, 'view_type', layout );
			}
			directory.data( 'view_type', layout );

			um_build_template( directory, data );

			um_init_new_dropdown();
		}
		um_members_hide_preloader( directory );
	});


	/**
	 * END: Change View Type Handlers
	 */


	/**
	 * General Search
	 */

	// Searching
	jQuery( document.body ).on( 'click', '.um-directory .um-do-search', function() {
		var directory = jQuery(this).parents('.um-directory');
		um_run_search( directory );
	});


	// Make search on Enter click
	jQuery( document.body ).on( 'keypress', '.um-directory .um-search-line', function(e) {
		if ( e.which === 13 ) {
			var directory = jQuery(this).parents('.um-directory');
			um_run_search( directory );
		}
	});

	/**
	 * END: General Search
	 */

	/**
	 * Sorting
	 */
	jQuery( document.body ).on( 'click', '.um-members-sorting', function(e) {
		e.preventDefault();

		if ( jQuery( this ).data('selected') === 1 ) {
			return;
		}

		var directory_hash = jQuery(this).data('directory-hash');
		var directory = jQuery('.um-directory[data-hash="' + directory_hash + '"]');

		if ( um_is_directory_busy( directory ) ) {
			return;
		}

		um_members_show_preloader( directory );

		var sorting_label = jQuery( this ).text();
		var sort = jQuery(this).data('value');
		var defaultSorting = jQuery(this).data('default');

		directory.data( 'sorting', sort );
		if ( defaultSorting ) {
			um_set_url_from_data( directory, 'sort', '' );
		} else {
			um_set_url_from_data( directory, 'sort', sort );
		}

		um_ajax_get_members( directory );

		directory.find('.um-dropdown[data-element=".um-members-sorting-toggle"]').find('a').data('selected', 0).prop('data-selected', 0).attr('data-selected', 0);
		directory.find('.um-dropdown[data-element=".um-members-sorting-toggle"] a[data-value="' + sort + '"]').data('selected', 1).prop('data-selected', 1).attr('data-selected', 1);
		directory.find('.um-members-sorting-toggle .um-button-content').text( sorting_label );
	});

	/**
	 * END: Sorting
	 */

	/**
	 * Pagination
	 */

	jQuery( document.body ).on( 'click', '.um-directory .um-pagination-item:not(.current)', function() {
		if ( jQuery(this).hasClass('disabled') ) {
			return;
		}

		let directory = jQuery(this).parents('.um-directory');
		if ( um_is_directory_busy( directory ) ) {
			return;
		}

		um_members_show_preloader( directory );

		let page;
		if ( 'first' === jQuery(this).data('page') ) {
			page = 1;
		} else if ( 'prev' === jQuery(this).data('page') ) {
			page = directory.data( 'page' )*1 - 1;
		} else if ( 'next' === jQuery(this).data('page') ) {
			page = directory.data( 'page' )*1 + 1;
		} else if ( 'last' === jQuery(this).data('page') ) {
			page = parseInt( directory.data( 'total_pages' ) );
		} else {
			page = parseInt( jQuery(this).data('page') );
		}

		if ( page === 1 ) {
			directory.find('.um-pagination-item[data-page="prev"]').addClass('disabled');
			directory.find('.um-pagination-item[data-page="next"]').removeClass('disabled');
		} else if ( page === parseInt( directory.data( 'total_pages' ) ) ) {
			directory.find('.um-pagination-item[data-page="next"]').addClass('disabled');
			directory.find('.um-pagination-item[data-page="prev"]').removeClass('disabled');
		} else {
			directory.find('.um-pagination-item[data-page="prev"], .um-pagination-item[data-page="next"]').removeClass('disabled');
		}

		directory.find('.um-pagination-item').removeClass('current');
		directory.find('.um-pagination-item[data-page="' + page + '"]').addClass('current');

		directory.data( 'page', page );
		if ( page === 1 ) {
			um_set_url_from_data( directory, 'page', '' );
		} else {
			um_set_url_from_data( directory, 'page', page );
		}

		um_ajax_get_members( directory );
	});

	jQuery( document.body ).on( 'change', '.um-directory .um-pagination-current-page-input', function() {
		if ( jQuery(this).hasClass('disabled') ) {
			return;
		}

		let directory = jQuery(this).parents('.um-directory');
		if ( um_is_directory_busy( directory ) ) {
			return;
		}

		let currentPage = parseInt( directory.data( 'page' ) );
		let page        = parseInt( jQuery(this).val() );
		let totalPages  = parseInt( directory.data( 'total_pages' ) );
		if ( currentPage === page ) {
			return;
		}

		if ( page < 1 ) {
			jQuery(this).val( 1 );
			page = 1;
		}

		if ( page > totalPages ) {
			jQuery(this).val( totalPages );
			page = totalPages;
		}

		um_members_show_preloader( directory );

		if ( page === 1 ) {
			directory.find('.um-pagination-item[data-page="prev"]').addClass('disabled');
			directory.find('.um-pagination-item[data-page="next"]').removeClass('disabled');
		} else if ( page === totalPages ) {
			directory.find('.um-pagination-item[data-page="next"]').addClass('disabled');
			directory.find('.um-pagination-item[data-page="prev"]').removeClass('disabled');
		} else {
			directory.find('.um-pagination-item[data-page="prev"], .um-pagination-item[data-page="next"]').removeClass('disabled');
		}

		directory.data( 'page', page );
		if ( page === 1 ) {
			um_set_url_from_data( directory, 'page', '' );
		} else {
			um_set_url_from_data( directory, 'page', page );
		}

		um_ajax_get_members( directory );
	});

	jQuery( document.body ).on( 'keypress', '.um-directory .um-pagination-current-page-input', function(e) {
		if ( 13 === e.which ) {
			jQuery(this).trigger('change');
			return false;
		}
	});

	/**
	 * END: Pagination
	 */

	/**
	 * START: Filters
	 */

	jQuery( document.body ).on( 'change', '.um-directory .um-search-filter input, .um-directory .um-search-filter select', function() {
		let $filtersForm = jQuery(this).parents('.um-filters-form');
		let $applyFilters = $filtersForm.find('.um-apply-filters');

		UM.frontend.directories.filters.checkEmpty( $filtersForm );

		// Enable filters submission as soon as first filter is changed.
		$applyFilters.prop( 'disabled', false );
	});

	// jQuery( document.body ).on( 'change', '.um-directory .um-search-filter input', function() {
	// 	jQuery(this).parents('.um-filters-form').find('.um-clear-filters-a').removeClass( 'um-hidden' );
	// 	jQuery(this).parents('.um-filters-form').find('.um-clear-filters-a').addClass( 'um-hidden' );
	// });


	// Filtration process
	// jQuery( document.body ).on( 'change', '.um-directory .um-search-filter select', function() {
	// 	let selected_val_raw = jQuery(this).val();
	// 	let selected_val = [];
	// 	for ( let i = 0; i < selected_val_raw.length; i++ ) {
	// 		selected_val[i] = UM.common.form.sanitizeValue( selected_val_raw[i] );
	// 	}
	//
	// 	let directory = jQuery(this).parents('.um-directory');
	// 	let hash      = UM.frontend.directories.getHash( directory );
	//
	// 	// let directoryObj = UM.frontend.directories.list[ hash ];
	// 	// if ( directoryObj.isBusy ) {
	// 	// 	return;
	// 	// }
	// 	//
	// 	// directoryObj.preloaderShow();
	//
	// 	// um_members_show_preloader( directory );
	//
	// 	let $filtersBar = directory.find('.um-member-directory-filters-bar');
	// 	var filter_name = jQuery(this).prop('name');
	//
	// 	var current_value = um_get_data_for_directory( directory, 'filter_' + filter_name );
	// 	if ( typeof current_value == 'undefined' ) {
	// 		current_value = [];
	// 	} else {
	// 		current_value = current_value.split( '||' );
	// 	}
	//
	// 	if ( selected_val.length ) {
	// 		um_set_url_from_data( directory, 'filter_' + filter_name, selected_val.join( '||' ) );
	// 		$filtersBar.find('.um-clear-filters-a').removeClass('um-hidden');
	// 		directory.data( 'searched', 1 );
	// 		directory.find( '.um-member-directory-sorting-options' ).prop( 'disabled', false );
	// 		directory.find( '.um-member-directory-view-type' ).removeClass( 'um-disabled' );
	// 	} else {
	// 		um_set_url_from_data( directory, 'filter_' + filter_name, '' );
	// 	}
	//
	// 	// set 1st page after filtration
	// 	directory.data( 'page', 1 );
	// 	um_set_url_from_data( directory, 'page', '' );
	//
	// 	um_ajax_get_members( directory );
	//
	//
	// 	// if ( directory.find( '.um-search-filter select[data-um-parent="' + filter_name + '"]' ).length > 0 ) {
	// 	// 	jQuery(this).trigger('change');
	// 	// }
	// });

	jQuery( document.body ).on( 'reset', '.um-directory .um-filters-form', function() {
		let $filtersForm = jQuery(this);

		let $clearFilters = $filtersForm.find('.um-clear-filters-a');
		let $applyFilters = $filtersForm.find('.um-apply-filters');

		$clearFilters.addClass('um-hidden').prop('disabled', true);
		$applyFilters.prop('disabled', true);

		let directory = jQuery(this).parents('.um-directory');

		directory.data( 'page', 1 );
		// if ( um_is_directory_busy( directory ) ) {
		// 	return;
		// }
		//
		// um_members_show_preloader( directory );
		//
		//
		// um_set_url_from_data( directory, 'page', '' );
		//
		// directory.find( '.um-member-directory-sorting-options' ).prop( 'disabled', false );
		// directory.find( '.um-member-directory-view-type' ).removeClass( 'um-disabled' );
		//
		// um_ajax_get_members( directory );
	});

	jQuery( document.body ).on( 'click', '.um-directory .um-apply-filters', function(e) {
		e.preventDefault();
		let directory = jQuery(this).parents('.um-directory');
		let $filtersForm = jQuery(this).parents('.um-filters-form');

		jQuery(this).prop('disabled',true);
		UM.frontend.directories.filters.checkEmpty( $filtersForm );

		let $filters = $filtersForm.find('.um-search-filter input, .um-search-filter select');
		$filters.each( function () {
			let $filterWrapper = jQuery(this).parents( '.um-search-filter' );
			let filterType = $filterWrapper.data( 'filter-type' );
			let filterName = $filterWrapper.data( 'filter-name' );

			if ( 'text' === filterType ) {
				let filterValue = UM.common.form.sanitizeValue( jQuery(this).val() );
				//let filterName = jQuery(this).prop('name');

				// let urlValue = um_get_data_for_directory( directory, 'filter_' + filterName );
				// if ( typeof urlValue == 'undefined' ) {
				// 	urlValue = '';
				// }
				//
				// if ( filterValue === urlValue ) {
				// 	return;
				// }

				um_set_url_from_data( directory, 'filter_' + filterName, filterValue );
			} else if ( 'select' === filterType ) {
				//let filterName = jQuery(this).prop('name');

				let filterValueRaw = jQuery(this).val();
				let filterValue = [];
				for ( let i = 0; i < filterValueRaw.length; i++ ) {
					filterValue[i] = UM.common.form.sanitizeValue( filterValueRaw[i] );
				}

				// let urlValue = um_get_data_for_directory( directory, 'filter_' + filterName );
				// if ( typeof urlValue == 'undefined' ) {
				// 	urlValue = [];
				// } else {
				// 	urlValue = urlValue.split( '||' );
				// }

				if ( filterValue.length ) {
					um_set_url_from_data( directory, 'filter_' + filterName, filterValue.join( '||' ) );
				} else {
					um_set_url_from_data( directory, 'filter_' + filterName, '' );
				}
			} else if ( 'slider' === filterType ) {
				// Get from "from" and "to" fields.
				let $rangeContainer = jQuery(this).parents( '.um-range-container' );
				const fromSlider = $rangeContainer.find( '.um-from-slider' )[0];
				const toSlider = $rangeContainer.find( '.um-to-slider' )[0];

				if ( parseInt( fromSlider.value, 10) === parseInt( fromSlider.min, 10 ) ) {
					um_set_url_from_data( directory, 'filter_' + filterName + '_from', '' );
				} else {
					um_set_url_from_data( directory, 'filter_' + filterName + '_from', parseInt( fromSlider.value, 10) );
				}

				if ( parseInt( toSlider.value, 10) === parseInt( toSlider.max, 10 ) ) {
					um_set_url_from_data( directory, 'filter_' + filterName + '_to', '' );
				} else {
					um_set_url_from_data( directory, 'filter_' + filterName + '_to', parseInt( toSlider.value, 10) );
				}
			} else if ( 'datepicker' === filterType ) {
				let $rangeContainer = jQuery(this).parents( '.um-date-range-row' );
				const fromDate = $rangeContainer.find( '[data-range="from"]' );
				const toDate = $rangeContainer.find( '[data-range="to"]' );

				um_set_url_from_data( directory, 'filter_' + filterName + '_from', UM.common.form.sanitizeValue( fromDate.val() ) );
				um_set_url_from_data( directory, 'filter_' + filterName + '_to', UM.common.form.sanitizeValue( toDate.val() ) );

			} else if ( 'timepicker' === filterType ) {
				let $rangeContainer = jQuery(this).parents( '.um-time-range-row' );
				const fromTime = $rangeContainer.find( '[data-range="from"]' );
				const toTime = $rangeContainer.find( '[data-range="to"]' );

				um_set_url_from_data( directory, 'filter_' + filterName + '_from', UM.common.form.sanitizeValue( fromTime.val() ) );
				um_set_url_from_data( directory, 'filter_' + filterName + '_to', UM.common.form.sanitizeValue( toTime.val() ) );
			}
		});

		// Set 1st page after filtration.
		directory.data( 'page', 1 );
		um_set_url_from_data( directory, 'page', '' );

		directory.data( 'searched', 1 );
		directory.find( '.um-member-directory-sorting-options' ).prop( 'disabled', false );
		directory.find( '.um-member-directory-view-type' ).removeClass( 'um-disabled' );

		//directory.data( 'page', 1 );
	});

	// jQuery( document.body ).on( 'click', '.um-directory .um-clear-filters-a', function() {
	// 	var directory = jQuery(this).parents('.um-directory');
	// 	if ( um_is_directory_busy( directory ) ) {
	// 		return;
	// 	}
	//
	// 	jQuery(this).addClass( 'um-hidden' ).prop('disabled',true);
	//
	// 	// um_members_show_preloader( directory );
	// 	//
	// 	// jQuery(this).addClass( 'um-hidden' );
	// 	//
	// 	// let $filtersBar = directory.find('.um-member-directory-filters-bar');
	// 	// $filtersBar.find('.um-search-filter').each( function () {
	// 	// 	let type = 'text';
	// 	// 	if ( jQuery(this).hasClass('um-select-filter-type') ) {
	// 	// 		type = 'select';
	// 	// 	} else if ( jQuery(this).hasClass('um-slider-filter-type') ) {
	// 	// 		type = 'slider';
	// 	// 	} else if ( jQuery(this).hasClass('um-datepicker-filter-type') ) {
	// 	// 		type = 'datepicker';
	// 	// 	} else if ( jQuery(this).hasClass('um-timepicker-filter-type') ) {
	// 	// 		type = 'timepicker';
	// 	// 	}
	// 	//
	// 	// 	let filterName = jQuery(this).data('filter-name');
	// 	//
	// 	// 	if ( type === 'text' ) {
	// 	// 		um_set_url_from_data( directory, 'filter_' + filterName,'' );
	// 	// 		jQuery( '.um-search-filter input[name="' + filterName + '"]' ).val('');
	// 	// 	} /*else if ( type === 'select' ) {
	// 	//
	// 	// 		var current_value = um_get_data_for_directory( directory, 'filter_' + filterName );
	// 	// 		if ( typeof current_value == 'undefined' ) {
	// 	// 			current_value = [];
	// 	// 		} else {
	// 	// 			current_value = UM.common.form.unsanitizeValue( current_value );
	// 	// 			current_value = current_value.split( '||' );
	// 	// 		}
	// 	//
	// 	// 		if ( -1 !== jQuery.inArray( removeItem.toString(), current_value ) ) {
	// 	// 			current_value = jQuery.grep( current_value, function( value ) {
	// 	// 				return value !== removeItem.toString();
	// 	// 			});
	// 	// 		}
	// 	//
	// 	// 		if ( ! current_value.length ) {
	// 	// 			current_value = '';
	// 	// 		}
	// 	//
	// 	// 		um_set_url_from_data( directory, 'filter_' + filterName, current_value );
	// 	//
	// 	// 		var select = jQuery( '.um-search-filter select[name="' + filterName + '"]' );
	// 	// 		select.find('option[value="' + removeItem + '"]').prop('disabled', false).show();
	// 	//
	// 	// 		if ( directory.find( '.um-search-filter select[data-um-parent="' +  filterName + '"]' ).length > 0 ) {
	// 	// 			select.trigger('change');
	// 	// 		}
	// 	//
	// 	// 	}*/ else if ( type === 'slider' ) {
	// 	// 		um_set_url_from_data( directory, 'filter_' + filterName + '_from','' );
	// 	// 		um_set_url_from_data( directory, 'filter_' + filterName + '_to', '' );
	// 	//
	// 	// 		var $slider = jQuery( '.um-search-filter #' + filterName + '_min' ).siblings('.um-slider');
	// 	// 		var options = $slider.slider( 'option' );
	// 	//
	// 	// 		$slider.slider( 'values', [ options.min, options.max ] );
	// 	//
	// 	// 		jQuery( '.um-search-filter #' + filterName + '_min' ).val('');
	// 	// 		jQuery( '.um-search-filter #' + filterName + '_max' ).val('');
	// 	//
	// 	// 		um_set_range_label( $slider );
	// 	// 	} else if ( type === 'datepicker' ) {
	// 	// 		um_set_url_from_data( directory, 'filter_' + filterName + '_from','' );
	// 	// 		um_set_url_from_data( directory, 'filter_' + filterName + '_to', '' );
	// 	//
	// 	// 		jQuery( '.um-search-filter #' + filterName + '_from' ).val('');
	// 	// 		jQuery( '.um-search-filter #' + filterName + '_to' ).val('');
	// 	// 	} else if ( type === 'timepicker' ) {
	// 	// 		um_set_url_from_data( directory, 'filter_' + filterName + '_from','' );
	// 	// 		um_set_url_from_data( directory, 'filter_' + filterName + '_to', '' );
	// 	//
	// 	// 		jQuery( '.um-search-filter #' + filterName + '_from' ).val('');
	// 	// 		jQuery( '.um-search-filter #' + filterName + '_to' ).val('');
	// 	// 	} else {
	// 	// 		wp.hooks.doAction( 'um_member_directory_clear_filters', type, directory, filterName );
	// 	// 	}
	// 	// });
	//
	// 	// directory.find( '.um-members-filter-remove' ).each( function() {
	// 	// 	var removeItem = jQuery(this).data('value');
	// 	// 	var filter_name = jQuery(this).data('name');
	// 	//
	// 	// 	var type = jQuery(this).data('type');
	// 	// 	if ( type === 'text' ) {
	// 	// 		um_set_url_from_data( directory, 'filter_' + filter_name,'' );
	// 	//
	// 	// 		jQuery( '.um-search-filter input[name="' + filter_name + '"]' ).val('');
	// 	//
	// 	// 	} else if ( type === 'select' ) {
	// 	//
	// 	// 		var current_value = um_get_data_for_directory( directory, 'filter_' + filter_name );
	// 	// 		if ( typeof current_value == 'undefined' ) {
	// 	// 			current_value = [];
	// 	// 		} else {
	// 	// 			current_value = UM.common.form.unsanitizeValue( current_value );
	// 	// 			current_value = current_value.split( '||' );
	// 	// 		}
	// 	//
	// 	// 		if ( -1 !== jQuery.inArray( removeItem.toString(), current_value ) ) {
	// 	// 			current_value = jQuery.grep( current_value, function( value ) {
	// 	// 				return value !== removeItem.toString();
	// 	// 			});
	// 	// 		}
	// 	//
	// 	// 		if ( ! current_value.length ) {
	// 	// 			current_value = '';
	// 	// 		}
	// 	//
	// 	// 		um_set_url_from_data( directory, 'filter_' + filter_name, current_value );
	// 	//
	// 	// 		var select = jQuery( '.um-search-filter select[name="' + filter_name + '"]' );
	// 	// 		select.find('option[value="' + removeItem + '"]').prop('disabled', false).show();
	// 	//
	// 	// 		//disable options and disable select if all options are disabled
	// 	// 		if ( select.find('option:not(:disabled)').length > 1 ) {
	// 	// 			select.prop('disabled', false);
	// 	// 		}
	// 	// 		// select.select2('destroy').select2({
	// 	// 		// 	dropdownParent: select.parent()
	// 	// 		// });
	// 	//
	// 	// 		if ( directory.find( '.um-search-filter select[data-um-parent="' +  filter_name + '"]' ).length > 0 ) {
	// 	// 			select.trigger('change');
	// 	// 		}
	// 	//
	// 	// 	} else if ( type === 'slider' ) {
	// 	// 		um_set_url_from_data( directory, 'filter_' + filter_name + '_from','' );
	// 	// 		um_set_url_from_data( directory, 'filter_' + filter_name + '_to', '' );
	// 	//
	// 	// 		var $slider = jQuery( '.um-search-filter #' + filter_name + '_min' ).siblings('.um-slider');
	// 	// 		var options = $slider.slider( 'option' );
	// 	//
	// 	// 		$slider.slider( 'values', [ options.min, options.max ] );
	// 	//
	// 	// 		jQuery( '.um-search-filter #' + filter_name + '_min' ).val('');
	// 	// 		jQuery( '.um-search-filter #' + filter_name + '_max' ).val('');
	// 	//
	// 	// 		um_set_range_label( $slider );
	// 	// 	} else if ( type === 'datepicker' ) {
	// 	// 		um_set_url_from_data( directory, 'filter_' + filter_name + '_from','' );
	// 	// 		um_set_url_from_data( directory, 'filter_' + filter_name + '_to', '' );
	// 	//
	// 	// 		jQuery( '.um-search-filter #' + filter_name + '_from' ).val('');
	// 	// 		jQuery( '.um-search-filter #' + filter_name + '_to' ).val('');
	// 	// 	} else if ( type === 'timepicker' ) {
	// 	// 		um_set_url_from_data( directory, 'filter_' + filter_name + '_from','' );
	// 	// 		um_set_url_from_data( directory, 'filter_' + filter_name + '_to', '' );
	// 	//
	// 	// 		jQuery( '.um-search-filter #' + filter_name + '_from' ).val('');
	// 	// 		jQuery( '.um-search-filter #' + filter_name + '_to' ).val('');
	// 	// 	} else {
	// 	// 		wp.hooks.doAction( 'um_member_directory_clear_filters', type, directory, filter_name, removeItem );
	// 	// 	}
	// 	// });
	//
	// 	//set 1st page after filtration
	// 	directory.data( 'page', 1 );
	// 	um_set_url_from_data( directory, 'page', '' );
	// 	directory.find('.um-members-filter-tag').remove();
	//
	// 	//jQuery(this).hide();
	// 	if ( directory.find( '.um-members-filter-remove' ).length === 0 ) {
	// 		directory.find('.um-clear-filters').hide();
	// 		directory.find('.um-clear-filters').parents('.um-member-directory-header-row').addClass( 'um-header-row-invisible' );
	// 	} else {
	// 		directory.find('.um-clear-filters').show();
	// 		directory.find('.um-clear-filters').parents('.um-member-directory-header-row').removeClass( 'um-header-row-invisible' );
	// 	}
	//
	// 	var ignore_after_search = false;
	// 	ignore_after_search = wp.hooks.applyFilters( 'um_member_directory_ignore_after_search', ignore_after_search, directory );
	//
	// 	if ( ! ignore_after_search ) {
	// 		var show_after_search = directory.data('must-search');
	// 		if ( show_after_search === 1 ) {
	// 			var search = um_get_search( directory );
	// 			if ( ! search ) {
	// 				directory.data( 'searched', 0 );
	// 				directory.find('.um-members-grid, .um-members-list, .um-members-intro').remove();
	// 				directory.find( '.um-member-directory-sorting-options' ).prop( 'disabled', true );
	// 				directory.find( '.um-member-directory-view-type' ).addClass( 'um-disabled' );
	//
	// 				wp.hooks.doAction( 'um_member_directory_clear_not_searched', directory );
	//
	// 				um_members_hide_preloader( directory );
	// 				return;
	// 			}
	// 		}
	// 	}
	//
	// 	directory.find( '.um-member-directory-sorting-options' ).prop( 'disabled', false );
	// 	directory.find( '.um-member-directory-view-type' ).removeClass( 'um-disabled' );
	//
	// 	um_ajax_get_members( directory );
	// });


	/**
	 * First Page Loading
	 */

	wp.hooks.doAction( 'um_member_directory_on_first_pages_loading' );

	//Init Directories
	jQuery( '.um-directory' ).each( function() {
		let directory = jQuery(this);
		let hash      = UM.frontend.directories.getHash( directory );
		let directoryObj = new UM.frontend.directory( hash );
		UM.frontend.directories.list[ hash ] = directoryObj;

		//slider filter
		um_slider_filter_init( directory );

		//datepicker filter
//		um_datepicker_filter_init( directory );

		//timepicker filter
//		um_timepicker_filter_init( directory );

		wp.hooks.doAction( 'um_member_directory_on_init', directory, hash );

		var ignore_after_search = false;
		ignore_after_search = wp.hooks.applyFilters( 'um_member_directory_ignore_after_search', ignore_after_search, directory );

		if ( ! ignore_after_search ) {
			var show_after_search = directory.data('must-search');
			if ( show_after_search === 1 ) {
				var search = um_get_search( directory );
				var filters_data = um_get_filters_data( directory );
				if ( ! filters_data.length && ! search ) {
					return;
				}
			}
		}

		var prevent_default = wp.hooks.applyFilters( 'um_member_directory_prevent_default_first_loading', false, directory, hash );

		if ( ! prevent_default ) {
			um_members_show_preloader( directory );
			um_ajax_get_members( directory, {first_load:true} );
		}
	});


	//history events when back/forward and change window.location.hash
	window.addEventListener( "popstate", function(e) {
		jQuery( '.um-directory' ).each( function() {
			let directory = jQuery(this);
			let hash      = UM.frontend.directories.getHash( directory );
			let directoryObj = new UM.frontend.directory( hash );
			UM.frontend.directories.list[ hash ] = directoryObj;
			directoryObj.preloaderShow();

			//um_members_show_preloader( directory );

			// clear layout and header
			directory.find('.um-members-grid, .um-members-list, .um-members-intro').remove();

			// set search from history
			if ( directory.find( '.um-member-directory-search-line' ).length ) {
				var search = um_get_data_for_directory( directory, 'search' );
				if ( typeof search == 'undefined' ) {
					search = '';
				}
				directory.data( 'general_search', search );
				directory.find('.um-search-line').val( search );
			}

			var page = um_get_data_for_directory( directory, 'page' );
			if ( typeof page == 'undefined' ) {
				page = 1;
			} else if ( page > directory.data( 'total_pages' ) ) {
				page = directory.data( 'total_pages' );
			}

			directory.data( 'page', page ).attr( 'data-page', page );

			//sorting from history
			if ( directory.find( '.um-member-directory-sorting' ).length ) {
				var sort = um_get_data_for_directory( directory, 'sort' );
				if ( typeof sort == 'undefined' ) {
					sort = directory.find( '.um-new-dropdown[data-element=".um-member-directory-sorting-a"]' ).find('a[data-default="1"]').data('value');
				}
				directory.data( 'sorting', sort );

				var sort_dropdown = directory.find( '.um-new-dropdown[data-element=".um-member-directory-sorting-a"]' );
				sort_dropdown.find('a').data('selected', 0).prop('data-selected', 0).attr('data-selected', 0);
				sort_dropdown.find('a[data-value="' + sort + '"]').data('selected', 1).prop('data-selected', 1).attr('data-selected', 1);
				directory.find('.um-member-directory-sorting-a').find('> a').html( sort_dropdown.find('a[data-value="' + sort + '"]').html() );
			}

			//view type from history
			if ( directory.find( '.um-member-directory-view-type' ).length ) {
				var layout = um_get_data_for_directory( directory, 'view_type' );
				if ( typeof layout == 'undefined' ) {
					layout = directory.find( '.um-member-directory-view-type-a[data-default="1"]' ).data('type');
				}
				directory.data( 'view_type', layout );

				directory.find('.um-member-directory-view-type .um-member-directory-view-type-a').hide();
				directory.find('.um-member-directory-view-type .um-member-directory-view-type-a[data-type="' + layout + '"]').show();
			}

			//datepicker filter
			directory.find('.um-datepicker-filter').each( function() {
				var elem = jQuery(this);

				var $picker = elem.pickadate('picker');
				var $fname = elem.data('filter_name');
				var $frange = elem.data('range');

				var query_value = um_get_data_for_directory( directory, 'filter_' + $fname + '_' + $frange );
				if ( typeof query_value !== 'undefined' ) {
					$picker.set( 'select', query_value*1000 );
				} else {
					$picker.clear();
				}
			});

			directory.find('.um-slider').each( function() {
				var slider = jQuery( this );
				var filter_name = slider.data('field_name');

				var min_default_value = um_get_data_for_directory( directory, 'filter_' + filter_name + '_from' );
				var max_default_value = um_get_data_for_directory( directory, 'filter_' + filter_name + '_to' );
				if ( typeof min_default_value == 'undefined' ) {
					min_default_value = slider.data('min');
				}
				min_default_value = parseInt( min_default_value );

				if ( typeof max_default_value == 'undefined' ) {
					max_default_value =  slider.data('max');
				}
				max_default_value = parseInt( max_default_value );

				slider.slider( 'values', [min_default_value, max_default_value] );
				um_set_range_label( slider );
			});

			//timepicker filter
			directory.find('.um-timepicker-filter').each( function() {
				var elem = jQuery(this);

				var $picker = elem.pickatime('picker');
				var $fname = elem.data('filter_name');
				var $frange = elem.data('range');

				var query_value = um_get_data_for_directory( directory, 'filter_' + $fname + '_' + $frange );
				if ( typeof query_value !== 'undefined' ) {
					var arr = query_value.split(':');
					$picker.set( 'select', arr[0]*60 );
				} else {
					$picker.clear();
				}
			});

			var ignore_after_search = false;
			ignore_after_search = wp.hooks.applyFilters( 'um_member_directory_ignore_after_search', ignore_after_search, directory );

			if ( ! ignore_after_search ) {
				var show_after_search = directory.data('must-search');
				if ( show_after_search === 1 ) {
					var search = um_get_search( directory );
					var filters_data = um_get_filters_data( directory );
					if ( ! filters_data.length && ! search ) {
						directory.data( 'searched', 0 );
						um_members_hide_preloader( directory );
						return;
					} else {
						directory.data( 'searched', 1 );
					}
				}
			}

			var prevent_default = wp.hooks.applyFilters( 'um_member_directory_prevent_default_first_loading', false, directory, hash );

			if ( ! prevent_default ) {
				um_ajax_get_members( directory );
			}
		});
	});

});


// Set toggle block based on parent.
wp.hooks.addFilter( 'um_toggle_block', 'um_member_directory', function( $toggleBlock, $toggleButton ) {
	if ( $toggleButton.hasClass('um-meta-toggle') ) {
		$toggleBlock = $toggleButton.parents('.um-member').find( $toggleButton.data('um-toggle') );
		let textAfter  = $toggleButton.data('toggle-text');
		let textBefore = $toggleButton.text();
		$toggleButton.data('toggle-text',textBefore).text(textAfter);
	} else if ( $toggleButton.hasClass('um-filters-toggle') ) {
		$toggleBlock = $toggleButton.parents('.um-directory').find( $toggleButton.data('um-toggle') );
	}

	return $toggleBlock;
});
