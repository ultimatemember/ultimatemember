wp.hooks.addAction( 'um_after_account_tab_changed', 'um_jobboardwp', function( tab_ ) {
	if ( 'jobboardwp' === tab_ ) {
		jb_responsive();
	}
});

wp.hooks.addAction( 'um_account_active_tab_inited', 'um_jobboardwp', function( tab_ ) {
	if ( 'jobboardwp' === tab_ ) {
		jb_responsive();
	}
});
wp.hooks.addAction("um_after_account_tab_changed","um_jobboardwp",function(o){"jobboardwp"===o&&jb_responsive()}),wp.hooks.addAction("um_account_active_tab_inited","um_jobboardwp",function(o){"jobboardwp"===o&&jb_responsive()});
// show header if there is map
wp.hooks.addFilter( 'um_bookmarks_remove_button_args', 'um_jobboardwp', function( data ) {
	data.job_list = true;
	return data;
}, 10 );


wp.hooks.addFilter( 'um_bookmarks_add_button_args', 'um_jobboardwp', function( data ) {
	data += '&job_list=1';
	return data;
}, 10 );
wp.hooks.addFilter("um_bookmarks_remove_button_args","um_jobboardwp",function(o){return o.job_list=!0,o},10),wp.hooks.addFilter("um_bookmarks_add_button_args","um_jobboardwp",function(o){return o+="&job_list=1"},10);
var um_members_directory_busy = [];

var um_member_directories = [];

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

	var url_data = um_parse_current_url();
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

	var other_directories = um_member_directories;

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
	jQuery.each( um_member_directories, function( k ) {
		var dir_hash = um_member_directories[ k ];
		if ( dir_hash !== hash ) {
			var other_directory = jQuery( '.um-directory[data-hash="' + dir_hash + '"]' );
			var dir_data = um_get_data_for_directory( other_directory );

			jQuery.each( dir_data, function( data_key ) {
				new_data[ data_key + '_' + dir_hash ] = dir_data[ data_key ];
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
		nonce:          um_scripts.nonce
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
					value = um_unsanitize_value( value );
					request[ filter_name ] = value.split( '||' );
				}
			} else if ( filter.hasClass( 'um-text-filter-type' ) && filter.find('input[type="text"]').length ) {
				var filter_name = filter.find('input[type="text"]').attr('name');
				var value = um_get_data_for_directory( directory, 'filter_' + filter_name );
				if ( typeof value != 'undefined' ) {
					value = um_unsanitize_value( value );
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

			var pagination_template = wp.template( 'um-members-pagination' );
			directory.find('.um-members-pagination-box').html( pagination_template( answer ) );

			directory.data( 'total_pages', answer.pagination.total_pages );

			if ( answer.pagination.total_pages ) {
				directory.find( '.um-member-directory-sorting-options' ).prop( 'disabled', false );
				directory.find( '.um-member-directory-view-type' ).removeClass( 'um-disabled' );
			} else {
				directory.find( '.um-member-directory-sorting-options' ).prop( 'disabled', true );
				directory.find( '.um-member-directory-view-type' ).addClass( 'um-disabled' );
			}

			//args.directory = directory;
			wp.hooks.doAction( 'um_member_directory_loaded', directory, answer );
			//jQuery( document ).trigger('um_members_rendered', [ directory, answer ] );

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
	var layout = directory.data('view_type');
	var template = wp.template( 'um-member-' + layout + '-' + um_members_get_hash( directory ) );

	directory.find('.um-members-grid, .um-members-list').remove();
	directory.find('.um-members-wrapper').prepend( template( data.users ) );

	var header_template = wp.template( 'um-members-header' );
	directory.find('.um-members-intro').remove();

	var generate_header = wp.hooks.applyFilters( 'um_member_directory_generate_header', false, directory );

	if ( ( typeof data.is_search != 'undefined' && data.is_search ) || generate_header ) {
		directory.find('.um-members-wrapper').prepend( header_template( data ) );
	}

	directory.addClass('um-loaded');

	if ( directory.find('.um-members.um-members-grid').length ) {
		UM_Member_Grid( directory.find('.um-members.um-members-grid') );
	}

	jQuery( document ).trigger( 'um_build_template', [ directory, data ] );
	jQuery( window ).trigger( 'resize' );

	init_tipsy();
}



function UM_Member_Grid( container ) {
	if ( container.find( '.um-member' ).length ) {
		container.imagesLoaded( function() {

			var masonry_args = wp.hooks.applyFilters( 'um_member_directory_grid_masonry_attrs', {
				itemSelector: '.um-member',
				columnWidth: '.um-member',
				gutter: '.um-gutter-sizer'
			}, container );

			var $grid = container.masonry( masonry_args );

			$grid.on( 'layoutComplete', function( event, laidOutItems ) {
				jQuery( document ).trigger( "um_grid_initialized", [ event, laidOutItems ] );
			});
		});
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
				filter_value = um_unsanitize_value( filter_value );
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


function um_change_tag( directory ) {
	var filters_data = um_get_filters_data( directory );

	directory.find('.um-members-filter-tag').remove();

	var filtered_line = directory.find('.um-filtered-line');
	if ( filtered_line.length ) {
		var filters_template = wp.template( 'um-members-filtered-line' );
		filtered_line.prepend( filters_template( {'filters': filters_data} ) );

		if ( directory.find( '.um-members-filter-remove' ).length === 0 ) {
			directory.find('.um-clear-filters').hide();
			directory.find('.um-clear-filters').parents('.um-member-directory-header-row').addClass( 'um-header-row-invisible' );
		} else {
			directory.find('.um-clear-filters').show();
			directory.find('.um-clear-filters').parents('.um-member-directory-header-row').removeClass( 'um-header-row-invisible' );
		}
	}


}



function um_run_search( directory ) {
	if ( um_is_directory_busy( directory ) ) {
		return;
	}
	um_members_show_preloader( directory );

	var pre_search = um_get_data_for_directory( directory, 'search' );

	var search = um_sanitize_value( directory.find('.um-search-line').val() );
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


jQuery(document.body).ready( function() {


	jQuery( '.um-directory .um-search-filter select' ).each( function() {
		if ( jQuery(this).find('option:not(:disabled)').length === 1 ) {
			jQuery(this).prop('disabled', true);
		}

		var obj = jQuery(this);
		obj.select2('destroy').select2({
			dropdownParent: obj.parent(),
			containerCssClass : 'um-select2-selection',
			dropdownCssClass: 'um-select2-dropdown',
		});
	});

	/**
	 * Change View Type Handlers
	 */


	//UI for change view type button
	jQuery( document.body ).on( 'mouseover', '.um-directory .um-member-directory-view-type', function() {
		if ( jQuery(this).hasClass('um-disabled') ) {
			return;
		}

		var $obj = jQuery(this).find('.um-member-directory-view-type-a:visible');

		$obj.hide();

		if ( $obj.next().length ) {
			$obj.next().show().tipsy('show');
		} else {
			jQuery(this).find( '.um-member-directory-view-type-a:first' ).show().tipsy('show');
		}
	}).on( 'mouseout', '.um-directory .um-member-directory-view-type', function() {
		if ( jQuery(this).hasClass('um-disabled') ) {
			return;
		}

		jQuery(this).find('.um-member-directory-view-type-a').hide().tipsy('hide');
		jQuery(this).find('.um-member-directory-view-type-a[data-type="' + jQuery(this).parents( '.um-directory' ).data('view_type') + '"]').show();
	});

	//change layout handler
	jQuery( document.body ).on( 'click', '.um-directory .um-member-directory-view-type-a', function() {
		var directory = jQuery(this).parents('.um-directory');
		if ( um_is_directory_busy( directory ) ) {
			return false;
		}

		var $this = jQuery(this);
		var views = $this.parents('.um-member-directory-view-type');

		if ( views.hasClass('um-disabled') ) {
			return;
		}

		um_members_show_preloader( directory );

		var $obj = views.find('.um-member-directory-view-type-a:visible');

		$obj.hide();

		if ( $obj.next().length ) {
			$obj.next().show().tipsy('show');
		} else {
			views.find( '.um-member-directory-view-type-a:first' ).show().tipsy('show');
		}

		var data = um_member_directory_last_data[ um_members_get_hash( directory ) ];
		if ( data !== null ) {
			var layout = $this.data('type');

			um_set_url_from_data( directory, 'view_type', layout );
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


	//searching
	jQuery( document.body ).on( 'click', '.um-directory .um-do-search', function() {
		var directory = jQuery(this).parents('.um-directory');
		um_run_search( directory );
	});


	//make search on Enter click
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

	jQuery( document.body ).on( 'click', '.um-new-dropdown[data-element=".um-member-directory-sorting-a"] li a', function() {
		if ( jQuery( this ).data('selected') === 1 ) {
			return;
		}

		var directory_hash = jQuery(this).data('directory-hash');
		var directory = jQuery('.um-directory[data-hash="' + directory_hash + '"]');

		if ( um_is_directory_busy( directory ) ) {
			return;
		}

		um_members_show_preloader( directory );

		var sorting_label = jQuery( this ).html();
		var sort = jQuery(this).data('value');

		directory.data( 'sorting', sort );
		um_set_url_from_data( directory, 'sort', sort );

		um_ajax_get_members( directory );

		directory.find('.um-new-dropdown[data-element=".um-member-directory-sorting-a"]').find('a').data('selected', 0).prop('data-selected', 0).attr('data-selected', 0);
		directory.find('.um-new-dropdown[data-element=".um-member-directory-sorting-a"] a[data-value="' + sort + '"]').data('selected', 1).prop('data-selected', 1).attr('data-selected', 1);
		directory.find('.um-member-directory-sorting-a').find('> a').html( sorting_label );
	});

	/**
	 * END: Sorting
	 */



	/**
	 * Pagination
	 */


	jQuery( document.body ).on( 'click', '.um-directory .pagi:not(.current)', function() {
		if ( jQuery(this).hasClass('disabled') ) {
			return;
		}

		var directory = jQuery(this).parents('.um-directory');
		if ( um_is_directory_busy( directory ) ) {
			return;
		}

		um_members_show_preloader( directory );

		var page;
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
			directory.find('.pagi[data-page="first"], .pagi[data-page="prev"]').addClass('disabled');
			directory.find('.pagi[data-page="prev"], .pagi[data-page="last"]').removeClass('disabled');
		} else if ( page === parseInt( directory.data( 'total_pages' ) ) ) {
			directory.find('.pagi[data-page="prev"], .pagi[data-page="last"]').addClass('disabled');
			directory.find('.pagi[data-page="first"], .pagi[data-page="prev"]').removeClass('disabled');
		} else {
			directory.find('.pagi[data-page="prev"], .pagi[data-page="last"]').removeClass('disabled');
			directory.find('.pagi[data-page="first"], .pagi[data-page="prev"]').removeClass('disabled');
		}

		directory.find('.pagi').removeClass('current');
		directory.find('.pagi[data-page="' + page + '"]').addClass('current');

		directory.data( 'page', page );
		if ( page === 1 ) {
			um_set_url_from_data( directory, 'page', '' );
		} else {
			um_set_url_from_data( directory, 'page', page );
		}

		um_ajax_get_members( directory );
	});


	//mobile pagination
	jQuery( document.body ).on( 'change', '.um-directory .um-members-pagi-dropdown', function() {
		var directory = jQuery(this).parents('.um-directory');

		if ( um_is_directory_busy( directory ) ) {
			return;
		}

		um_members_show_preloader( directory );

		var page = jQuery(this).val();

		directory.find('.pagi').removeClass('current');
		directory.find('.pagi[data-page="' + page + '"]').addClass('current');

		directory.data( 'page', page );
		if ( page === 1 ) {
			um_set_url_from_data( directory, 'page', '' );
		} else {
			um_set_url_from_data( directory, 'page', page );
		}

		um_ajax_get_members( directory );
	});


	/**
	 * END: Pagination
	 */


	/**
	 * Profile Cards actions
	 */

	jQuery( document.body ).on('click', '.um-directory .um-members.um-members-list .um-member-more a', function(e){
		e.preventDefault();

		var block = jQuery(this).parents('.um-member');

		block.find('.um-member-more').hide();
		block.find('.um-member-meta-main').slideDown();
		block.find('.um-member-less').fadeIn();

		return false;
	});

	jQuery( document.body ).on('click', '.um-directory .um-members.um-members-list .um-member-less a', function(e){
		e.preventDefault();

		var block = jQuery(this).parents('.um-member');

		block.find('.um-member-less').hide();
		block.find('.um-member-meta-main').slideUp();
		block.find('.um-member-more').fadeIn();

		return false;
	});


	jQuery( document.body ).on('click', '.um-directory .um-members.um-members-grid .um-member-more a', function(e){
		e.preventDefault();

		var block = jQuery(this).parents('.um-member');
		var container = jQuery(this).parents('.um-members');
		block.find('.um-member-more').hide();
		block.find('.um-member-meta').slideDown( function(){ UM_Member_Grid( container ) } );
		block.find('.um-member-less').fadeIn( );

		setTimeout(function(){ UM_Member_Grid( container ) }, 100);

		return false;
	});

	jQuery( document.body ).on('click', '.um-directory .um-members.um-members-grid .um-member-less a', function(e){
		e.preventDefault();

		var block = jQuery(this).parents('.um-member');
		var container = jQuery(this).parents('.um-members');
		block.find('.um-member-less').hide();
		block.find('.um-member-meta').slideUp( function() {
			block.find('.um-member-more').fadeIn();
			UM_Member_Grid( container );
		});

		return false;
	});

	/**
	 * END: Profile Cards actions
	 */


	//filters controls
	jQuery('.um-member-directory-filters-a').on( 'click', function() {
		var obj = jQuery(this);
		var search_bar = obj.parents('.um-directory').find('.um-search');

		if ( search_bar.is( ':visible' ) ) {
			search_bar.slideUp( 250, function(){
				obj.toggleClass('um-member-directory-filters-visible');
				search_bar.parents('.um-member-directory-header-row').toggleClass('um-header-row-invisible');
			});
		} else {
			search_bar.slideDown({
				duration: 250,
				start: function() {
					jQuery(this).css({
						display: "grid"
					});
					obj.toggleClass('um-member-directory-filters-visible');
					search_bar.parents('.um-member-directory-header-row').toggleClass('um-header-row-invisible');
				}
			} );
		}
	});


	//filtration process
	jQuery( document.body ).on( 'change', '.um-directory .um-search-filter select', function() {
		var selected_val_raw = jQuery(this).val();
		var selected_val = um_sanitize_value( selected_val_raw );

		if ( selected_val === '' ) {
			return;
		}

		var directory = jQuery(this).parents('.um-directory');

		if ( um_is_directory_busy( directory ) ) {
			return;
		}

		um_members_show_preloader( directory );

		var filter_name = jQuery(this).prop('name');

		var current_value = um_get_data_for_directory( directory, 'filter_' + filter_name );
		if ( typeof current_value == 'undefined' ) {
			current_value = [];
		} else {
			current_value = current_value.split( '||' );
		}

		if ( -1 === jQuery.inArray( selected_val, current_value ) ) {
			current_value.push( selected_val );
			current_value = current_value.join( '||' );

			um_set_url_from_data( directory, 'filter_' + filter_name, current_value );

			//set 1st page after filtration
			directory.data( 'page', 1 );
			um_set_url_from_data( directory, 'page', '' );
		}

		//disable options and disable select if all options are disabled
		jQuery(this).find('option[value="' + selected_val_raw + '"]').prop('disabled', true).hide();
		if ( jQuery(this).find('option:not(:disabled)').length === 1 ) {
			jQuery(this).prop('disabled', true);
		}

		var obj = jQuery(this);
		obj.select2('destroy').select2({
			dropdownParent: obj.parent(),
			containerCssClass : 'um-select2-selection',
			dropdownCssClass: 'um-select2-dropdown',
		});
		obj.val('').trigger( 'change' );

		um_ajax_get_members( directory );

		um_change_tag( directory );

		directory.data( 'searched', 1 );
		directory.find( '.um-member-directory-sorting-options' ).prop( 'disabled', false );
		directory.find( '.um-member-directory-view-type' ).removeClass( 'um-disabled' );
		// if ( directory.find( '.um-search-filter select[data-um-parent="' + filter_name + '"]' ).length > 0 ) {
		// 	jQuery(this).trigger('change');
		// }
	});


	jQuery( document.body ).on( 'blur', '.um-directory .um-search-filter.um-text-filter-type input[type="text"]', function() {
		var directory = jQuery(this).parents('.um-directory');

		if ( um_is_directory_busy( directory ) ) {
			return;
		}

		var current_value = um_sanitize_value( jQuery(this).val() );
		var filter_name = jQuery(this).prop('name');
		var url_value = um_get_data_for_directory( directory, 'filter_' + filter_name );

		if ( typeof url_value == 'undefined' ) {
			url_value = '';
		}

		if ( current_value === url_value ) {
			return;
		}

		um_members_show_preloader( directory );
		um_set_url_from_data( directory, 'filter_' + filter_name, current_value );

		//set 1st page after filtration
		directory.data( 'page', 1 );
		um_set_url_from_data( directory, 'page', '' );

		um_ajax_get_members( directory );

		um_change_tag( directory );

		directory.data( 'searched', 1 );
		directory.find( '.um-member-directory-sorting-options' ).prop( 'disabled', false );
		directory.find( '.um-member-directory-view-type' ).removeClass( 'um-disabled' );
	});


	//make search on Enter click
	jQuery( document.body ).on( 'keypress', '.um-directory .um-search-filter.um-text-filter-type input[type="text"]', function(e) {
		if ( e.which === 13 ) {
			var directory = jQuery(this).parents('.um-directory');

			if ( um_is_directory_busy( directory ) ) {
				return;
			}

			var current_value = um_sanitize_value( jQuery(this).val() );
			var filter_name = jQuery(this).prop('name');
			var url_value = um_get_data_for_directory( directory, 'filter_' + filter_name );

			if ( typeof url_value == 'undefined' ) {
				url_value = '';
			}

			if ( current_value === url_value ) {
				return;
			}

			um_members_show_preloader( directory );
			um_set_url_from_data( directory, 'filter_' + filter_name, current_value );

			//set 1st page after filtration
			directory.data( 'page', 1 );
			um_set_url_from_data( directory, 'page', '' );

			um_ajax_get_members( directory );

			um_change_tag( directory );

			directory.data( 'searched', 1 );
			directory.find( '.um-member-directory-sorting-options' ).prop( 'disabled', false );
			directory.find( '.um-member-directory-view-type' ).removeClass( 'um-disabled' );
		}
	});



	jQuery( document.body ).on( 'click', '.um-directory .um-members-filter-remove', function() {
		var directory = jQuery(this).parents('.um-directory');

		if ( um_is_directory_busy(directory) || ! directory ) {
			return;
		}

		um_members_show_preloader( directory );

		var removeItem = jQuery(this).data('value');
		var filter_name = jQuery(this).data('name');

		var type = jQuery(this).data('type');
		if ( type === 'text' ) {
			um_set_url_from_data( directory, 'filter_' + filter_name,'' );

			jQuery( '.um-search-filter input[name="' + filter_name + '"]' ).val('');

		} else if ( type === 'select' ) {

			var current_value = um_get_data_for_directory( directory, 'filter_' + filter_name );
			if ( typeof current_value == 'undefined' ) {
				current_value = [];
			} else {
				current_value = um_unsanitize_value( current_value );
				current_value = current_value.split( '||' );
			}

			if ( -1 !== jQuery.inArray( removeItem.toString(), current_value ) ) {
				current_value = jQuery.grep( current_value, function( value ) {
					return value !== removeItem.toString();
				});
			}

			if ( ! current_value.length ) {
				current_value = '';
			}

			um_set_url_from_data( directory, 'filter_' + filter_name, current_value );

			var select = jQuery( '.um-search-filter select[name="' + filter_name + '"]' );
			select.find('option[value="' + removeItem + '"]').prop('disabled', false).show();

			//disable options and disable select if all options are disabled
			if ( select.find('option:not(:disabled)').length > 1 ) {
				select.prop('disabled', false);
			}

			select.select2('destroy').select2({
				dropdownParent: select.parent(),
				containerCssClass : 'um-select2-selection',
				dropdownCssClass: 'um-select2-dropdown',
			});

			if ( directory.find( '.um-search-filter select[data-um-parent="' +  filter_name + '"]' ).length > 0 ) {
				select.trigger('change');
			}

		} else if ( type === 'slider' ) {
			um_set_url_from_data( directory, 'filter_' + filter_name + '_from','' );
			um_set_url_from_data( directory, 'filter_' + filter_name + '_to', '' );


			var $slider = jQuery( '.um-search-filter #' + filter_name + '_min' ).siblings('.um-slider');
			var options = $slider.slider( 'option' );

			$slider.slider( 'values', [ options.min, options.max ] );

			jQuery( '.um-search-filter #' + filter_name + '_min' ).val('');
			jQuery( '.um-search-filter #' + filter_name + '_max' ).val('');

			um_set_range_label( $slider );
		} else if ( type === 'datepicker' ) {
			um_set_url_from_data( directory, 'filter_' + filter_name + '_from','' );
			um_set_url_from_data( directory, 'filter_' + filter_name + '_to', '' );

			jQuery( '.um-search-filter #' + filter_name + '_from' ).val('');
			jQuery( '.um-search-filter #' + filter_name + '_to' ).val('');
		} else if ( type === 'timepicker' ) {
			um_set_url_from_data( directory, 'filter_' + filter_name + '_from','' );
			um_set_url_from_data( directory, 'filter_' + filter_name + '_to', '' );

			jQuery( '.um-search-filter #' + filter_name + '_from' ).val('');
			jQuery( '.um-search-filter #' + filter_name + '_to' ).val('');
		} else {
			wp.hooks.doAction( 'um_member_directory_filter_remove', type, directory, filter_name, removeItem );
		}


		//set 1st page after filtration
		directory.data( 'page', 1 );
		um_set_url_from_data( directory, 'page', '' );

		jQuery(this).tipsy('hide');
		jQuery(this).parents('.um-members-filter-tag').remove();

		if ( directory.find( '.um-members-filter-remove' ).length === 0 ) {
			directory.find('.um-clear-filters').hide();
		} else {
			directory.find('.um-clear-filters').show();
		}

		var ignore_after_search = false;
		ignore_after_search = wp.hooks.applyFilters( 'um_member_directory_ignore_after_search', ignore_after_search, directory );

		if ( ! ignore_after_search ) {
			var show_after_search = directory.data('must-search');
			if ( show_after_search === 1 ) {
				var search = um_get_search( directory );
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

		directory.find( '.um-member-directory-sorting-options' ).prop( 'disabled', false );
		directory.find( '.um-member-directory-view-type' ).removeClass( 'um-disabled' );

		um_ajax_get_members( directory );
	});


	jQuery( document.body ).on( 'click', '.um-directory .um-clear-filters-a', function() {
		var directory = jQuery(this).parents('.um-directory');
		if ( um_is_directory_busy( directory ) ) {
			return;
		}

		um_members_show_preloader( directory );

		directory.find( '.um-members-filter-remove' ).each( function() {
			var removeItem = jQuery(this).data('value');
			var filter_name = jQuery(this).data('name');

			var type = jQuery(this).data('type');
			if ( type === 'text' ) {
				um_set_url_from_data( directory, 'filter_' + filter_name,'' );

				jQuery( '.um-search-filter input[name="' + filter_name + '"]' ).val('');

			} else if ( type === 'select' ) {

				var current_value = um_get_data_for_directory( directory, 'filter_' + filter_name );
				if ( typeof current_value == 'undefined' ) {
					current_value = [];
				} else {
					current_value = um_unsanitize_value( current_value );
					current_value = current_value.split( '||' );
				}

				if ( -1 !== jQuery.inArray( removeItem.toString(), current_value ) ) {
					current_value = jQuery.grep( current_value, function( value ) {
						return value !== removeItem.toString();
					});
				}

				if ( ! current_value.length ) {
					current_value = '';
				}

				um_set_url_from_data( directory, 'filter_' + filter_name, current_value );

				var select = jQuery( '.um-search-filter select[name="' + filter_name + '"]' );
				select.find('option[value="' + removeItem + '"]').prop('disabled', false).show();

				//disable options and disable select if all options are disabled
				if ( select.find('option:not(:disabled)').length > 1 ) {
					select.prop('disabled', false);
				}
				select.select2('destroy').select2({
					dropdownParent: select.parent(),
					containerCssClass : 'um-select2-selection',
					dropdownCssClass: 'um-select2-dropdown',
				});

				if ( directory.find( '.um-search-filter select[data-um-parent="' +  filter_name + '"]' ).length > 0 ) {
					select.trigger('change');
				}

			} else if ( type === 'slider' ) {
				um_set_url_from_data( directory, 'filter_' + filter_name + '_from','' );
				um_set_url_from_data( directory, 'filter_' + filter_name + '_to', '' );

				var $slider = jQuery( '.um-search-filter #' + filter_name + '_min' ).siblings('.um-slider');
				var options = $slider.slider( 'option' );

				$slider.slider( 'values', [ options.min, options.max ] );

				jQuery( '.um-search-filter #' + filter_name + '_min' ).val('');
				jQuery( '.um-search-filter #' + filter_name + '_max' ).val('');

				um_set_range_label( $slider );
			} else if ( type === 'datepicker' ) {
				um_set_url_from_data( directory, 'filter_' + filter_name + '_from','' );
				um_set_url_from_data( directory, 'filter_' + filter_name + '_to', '' );

				jQuery( '.um-search-filter #' + filter_name + '_from' ).val('');
				jQuery( '.um-search-filter #' + filter_name + '_to' ).val('');
			} else if ( type === 'timepicker' ) {
				um_set_url_from_data( directory, 'filter_' + filter_name + '_from','' );
				um_set_url_from_data( directory, 'filter_' + filter_name + '_to', '' );

				jQuery( '.um-search-filter #' + filter_name + '_from' ).val('');
				jQuery( '.um-search-filter #' + filter_name + '_to' ).val('');
			} else {
				wp.hooks.doAction( 'um_member_directory_clear_filters', type, directory, filter_name, removeItem );
			}
		});

		//set 1st page after filtration
		directory.data( 'page', 1 );
		um_set_url_from_data( directory, 'page', '' );
		directory.find('.um-members-filter-tag').remove();

		//jQuery(this).hide();
		if ( directory.find( '.um-members-filter-remove' ).length === 0 ) {
			directory.find('.um-clear-filters').hide();
			directory.find('.um-clear-filters').parents('.um-member-directory-header-row').addClass( 'um-header-row-invisible' );
		} else {
			directory.find('.um-clear-filters').show();
			directory.find('.um-clear-filters').parents('.um-member-directory-header-row').removeClass( 'um-header-row-invisible' );
		}

		var ignore_after_search = false;
		ignore_after_search = wp.hooks.applyFilters( 'um_member_directory_ignore_after_search', ignore_after_search, directory );

		if ( ! ignore_after_search ) {
			var show_after_search = directory.data('must-search');
			if ( show_after_search === 1 ) {
				var search = um_get_search( directory );
				if ( ! search ) {
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

		directory.find( '.um-member-directory-sorting-options' ).prop( 'disabled', false );
		directory.find( '.um-member-directory-view-type' ).removeClass( 'um-disabled' );

		um_ajax_get_members( directory );
	});


	/**
	 * First Page Loading
	 */

	wp.hooks.doAction( 'um_member_directory_on_first_pages_loading' );

	//Init Directories
	jQuery( '.um-directory' ).each( function() {
		var directory = jQuery(this);
		var hash = um_members_get_hash( directory );

		um_member_directories.push( hash );

		// slideup/slidedown animation fix for grid filters bar
		if ( directory.find('.um-search').length ) {
			if ( ! directory.find('.um-search').is(':visible') ) {
				directory.find('.um-search').css({
					display: "grid"
				}).slideUp( 1 );
			}
		}

		//slider filter
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

						um_change_tag( directory );

						directory.data( 'searched', 1 );
						directory.find( '.um-member-directory-sorting-options' ).prop( 'disabled', false );
						directory.find( '.um-member-directory-view-type' ).removeClass( 'um-disabled' );
					}
				}
			});

			um_set_range_label( slider );
		});


		//datepicker filter
		directory.find('.um-datepicker-filter').each( function() {
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

					if ( ! context.select ) {
						return;
					}

					var directory = elem.parents('.um-directory');

					if ( um_is_directory_busy( directory ) ) {
						return;
					}

					um_members_show_preloader( directory );

					var filter_name = elem.data( 'filter_name' );
					var range = elem.data( 'range' );

					var current_value_from = um_get_data_for_directory( directory, 'filter_' + filter_name + '_from' );
					var current_value_to = um_get_data_for_directory( directory, 'filter_' + filter_name + '_to' );
					if ( typeof current_value_from === "undefined" ) {
						current_value_from = min / 1000;
					}
					if ( typeof current_value_to === "undefined" ) {
						current_value_to = max / 1000;
					}

					var select_val = context.select / 1000;
					var change_val = elem.val();

					if ( range === 'from' ) {
						current_value_from = select_val;
					} else if ( range === 'to' ) {
						current_value_to = select_val;
					}

					um_set_url_from_data( directory, 'filter_' + filter_name + '_from', current_value_from );
					um_set_url_from_data( directory, 'filter_' + filter_name + '_to', current_value_to );

					//set 1st page after filtration
					directory.data( 'page', 1 );
					um_set_url_from_data( directory, 'page', '' );

					um_ajax_get_members( directory );

					um_change_tag( directory );

					directory.data( 'searched', 1 );
					directory.find( '.um-member-directory-sorting-options' ).prop( 'disabled', false );
					directory.find( '.um-member-directory-view-type' ).removeClass( 'um-disabled' );
				}
			});

			var $picker = $input.pickadate('picker');
			var $fname = elem.data('filter_name');
			var $frange = elem.data('range');
			var $directory = elem.parents('.um-directory');

			var query_value = um_get_data_for_directory( $directory, 'filter_' + $fname + '_' + $frange );
			if ( typeof query_value !== 'undefined' ) {
				$picker.set( 'select', query_value*1000 );
			}

		});


		//timepicker filter
		directory.find('.um-timepicker-filter').each( function() {
			var elem = jQuery(this);
			var elemID = elem.attr('id');
			var elem_filter_name = elem.data('filter_name');

			//using arrays formatted as [HOUR,MINUTE]
			var min = elem.attr('data-min');
			var max = elem.attr('data-max');

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

					if ( ! context.select ) {
						return;
					}

					var directory = elem.parents('.um-directory');

					if ( um_is_directory_busy( directory ) ) {
						return;
					}

					um_members_show_preloader( directory );

					var filter_name = elem.data( 'filter_name' );
					var range = elem.data( 'range' );

					var current_value_from = um_get_data_for_directory( directory, 'filter_' + filter_name + '_from' );
					var current_value_to = um_get_data_for_directory( directory, 'filter_' + filter_name + '_to' );
					if ( typeof current_value_from === "undefined" ) {
						current_value_from = min;
					}
					if ( typeof current_value_to === "undefined" ) {
						current_value_to = max;
					}

					if ( typeof context.select !== 'undefined' ) {
						var select_val = um_time_convert( context.select, range );

						//var select_val = context.select / 60;

						if ( range === 'from' ) {
							current_value_from = select_val;
						} else if ( range === 'to' ) {
							current_value_to = select_val;
						}
					} else {
						if ( range === 'from' ) {
							current_value_from = min;
						} else if ( range === 'to' ) {
							current_value_to = max;
						}
					}

					var time = jQuery( '#' + elemID ).val();

					if ( elem.data('range') === 'from' ) {
						jQuery( '#' + elem_filter_name + '_to' ).pickatime('picker').set('min', time);
					} else {
						jQuery( '#' + elem_filter_name + '_from').pickatime('picker').set('max', time);
					}

					um_set_url_from_data( directory, 'filter_' + filter_name + '_from', current_value_from );
					um_set_url_from_data( directory, 'filter_' + filter_name + '_to', current_value_to );

					//set 1st page after filtration
					directory.data( 'page', 1 );
					um_set_url_from_data( directory, 'page', '' );

					um_ajax_get_members( directory );

					um_change_tag( directory );

					directory.data( 'searched', 1 );
					directory.find( '.um-member-directory-sorting-options' ).prop( 'disabled', false );
					directory.find( '.um-member-directory-view-type' ).removeClass( 'um-disabled' );

				}
			});

			// first loading timepicker select
			var $picker = $input.pickatime('picker');
			var $fname = elem.data('filter_name');
			var $frange = elem.data('range');
			var $directory = elem.parents('.um-directory');

			var query_value = um_get_data_for_directory( $directory, 'filter_' + $fname + '_' + $frange );
			if ( typeof query_value !== 'undefined' ) {
				var arr = query_value.split(':');
				$picker.set( 'select', arr[0]*60 + arr[1]*1 );
			}

		});

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
			um_change_tag( directory );
		}
	});


	//history events when back/forward and change window.location.hash
	window.addEventListener( "popstate", function(e) {
		jQuery( '.um-directory' ).each( function() {
			var directory = jQuery(this);
			var hash = um_members_get_hash( directory );
			um_member_directories.push( hash );

			um_members_show_preloader( directory );

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
				um_change_tag( directory );
			}
		});
	});

});

var um_members_directory_busy=[],um_member_directories=[],um_member_directory_last_data=[];function um_parse_current_url(){var r={},t=window.location.search.substring(1).split("&");return jQuery.each(t,function(e){e=t[e].split("=");r[e[0]]=e[1]}),r}function um_get_data_for_directory(e,r){var t=um_members_get_hash(e),a={},i=um_parse_current_url();if(jQuery.each(i,function(e){-1!==e.indexOf("_"+t)&&""!==i[e]&&(a[e.replace("_"+t,"")]=i[e])}),r){if(void 0!==a[r])try{a[r]=decodeURIComponent(a[r])}catch(e){console.error(e)}return a[r]}return a}function um_set_url_from_data(e,r,t){var a=um_members_get_hash(e),i=um_get_data_for_directory(e),m={};Array.isArray(t)?(jQuery.each(t,function(e){t[e]=encodeURIComponent(t[e])}),t=t.join("||")):jQuery.isNumeric(t)||(t=t.split("||"),jQuery.each(t,function(e){t[e]=encodeURIComponent(t[e])}),t=t.join("||")),""!==t&&(m[r+"_"+a]=t),jQuery.each(i,function(e){r===e?""!==t&&(m[e+"_"+a]=t):m[e+"_"+a]=i[e]}),jQuery.each(um_member_directories,function(e){var r,t=um_member_directories[e];t!==a&&(r=um_get_data_for_directory(jQuery('.um-directory[data-hash="'+t+'"]')),jQuery.each(r,function(e){m[e+"_"+t]=r[e]}))});var d=[];jQuery.each(m,function(e){d.push(e+"="+m[e])});e="?"+(d=wp.hooks.applyFilters("um_member_directory_url_attrs",d)).join("&");"?"===e&&(e=""),window.history.pushState("string","UM Member Directory",window.location.origin+window.location.pathname+e)}function um_members_get_hash(e){return e.data("hash")}function um_is_directory_busy(e){e=um_members_get_hash(e);return void 0!==um_members_directory_busy[e]&&um_members_directory_busy[e]}function um_members_show_preloader(e){um_members_directory_busy[um_members_get_hash(e)]=!0,e.find(".um-members-overlay").show()}function um_members_hide_preloader(e){um_members_directory_busy[um_members_get_hash(e)]=!1,e.find(".um-members-overlay").hide()}function um_set_range_label(e,r){var t,a="",i=e.siblings(".um-slider-range").data("placeholder-s"),m=e.siblings(".um-slider-range").data("placeholder-p"),m=r?(a=(r.values[0]===r.values[1]?i.replace("{value}",r.values[0]):m.replace("{min_range}",r.values[0]).replace("{max_range}",r.values[1])).replace("{field_label}",e.siblings(".um-slider-range").data("label")),t=r.values[0],r.values[1]):(a=(e.slider("values",0)===e.slider("values",1)?i.replace("{value}",e.slider("values",0)):m.replace("{min_range}",e.slider("values",0)).replace("{max_range}",e.slider("values",1))).replace("{field_label}",e.siblings(".um-slider-range").data("label")),t=e.slider("values",0),e.slider("values",1));e.siblings(".um-slider-range").html(a),e.siblings(".um_range_min").val(t),e.siblings(".um_range_max").val(m)}function um_get_search(e){return e.find(".um-search-line").length?e.find(".um-search-line").val():""}function um_get_sort(e){return e.data("sorting")}function um_get_current_page(e){e=e.data("page");return e=!e||void 0===e?1:e}function um_time_convert(e,r){var t=Math.floor(e/60),e=e%60;return 60<=e&&(e=0,24<=(t+=1)&&(t=0)),t+":"+(e=e<10?"0"+e:e)}function um_ajax_get_members(u,e){var r,t,a,i,o,m=um_members_get_hash(u);wp.hooks.applyFilters("um_member_directory_get_members_allow",!0,m,u)?(r=um_get_current_page(u),t=um_get_search(u),a=um_get_sort(u),i=-(new Date).getTimezoneOffset()/60,o={directory_id:m,page:r,search:t,sorting:a,gmt_offset:i,post_refferer:u.data("base-post"),nonce:um_scripts.nonce},u.find(".um-search-filter").length&&u.find(".um-search-filter").each(function(){var e,r,t,a,i,m,d,s=jQuery(this);s.find(".um-slider").length?(m=s.find(".um-slider").data("field_name"),r=um_get_data_for_directory(u,"filter_"+m+"_from"),e=um_get_data_for_directory(u,"filter_"+m+"_to"),void 0===r&&void 0===e||(o[m]=[r,e])):s.find(".um-datepicker-filter").length?(m=s.find(".um-datepicker-filter").data("filter_name"),r=um_get_data_for_directory(u,"filter_"+m+"_from"),e=um_get_data_for_directory(u,"filter_"+m+"_to"),void 0===r&&void 0===e||(o[m]=[r,e])):s.find(".um-timepicker-filter").length?(m=s.find(".um-timepicker-filter").data("filter_name"),r=um_get_data_for_directory(u,"filter_"+m+"_from"),e=um_get_data_for_directory(u,"filter_"+m+"_to"),void 0!==r&&(r=(i=(i=+(r=r.split(":"))[0])<10?"0"+i:i)+":"+(a=(a=+r[1])<10?"0"+a:a)+":00"),void 0!==e&&(a=+(t=e.split(":"))[1],e=(i=(i=+t[0])<10?"0"+i:i)+":"+(a=a<10?"0"+a:a)+":59"),void 0===r&&void 0===e||(o[m]=[r,e])):s.find("select").length?(m=s.find("select").attr("name"),void 0!==(d=um_get_data_for_directory(u,"filter_"+m))&&(d=um_unsanitize_value(d),o[m]=d.split("||"))):s.hasClass("um-text-filter-type")&&s.find('input[type="text"]').length?(m=s.find('input[type="text"]').attr("name"),void 0!==(d=um_get_data_for_directory(u,"filter_"+m))&&(d=um_unsanitize_value(d),o[m]=d)):o=wp.hooks.applyFilters("um_member_directory_custom_filter_handler",o,s,u)}),o=wp.hooks.applyFilters("um_member_directory_filter_request",o),wp.ajax.send("um_get_members",{data:o,success:function(e){um_member_directory_last_data[m]=e,um_build_template(u,e);var r=wp.template("um-members-pagination");u.find(".um-members-pagination-box").html(r(e)),u.data("total_pages",e.pagination.total_pages),e.pagination.total_pages?(u.find(".um-member-directory-sorting-options").prop("disabled",!1),u.find(".um-member-directory-view-type").removeClass("um-disabled")):(u.find(".um-member-directory-sorting-options").prop("disabled",!0),u.find(".um-member-directory-view-type").addClass("um-disabled")),wp.hooks.doAction("um_member_directory_loaded",u,e),um_init_new_dropdown(),um_members_hide_preloader(u)},error:function(e){console.log(e),um_members_hide_preloader(u)}})):setTimeout(um_ajax_get_members,600,u,e)}function um_build_template(e,r){var t=e.data("view_type"),a=wp.template("um-member-"+t+"-"+um_members_get_hash(e));e.find(".um-members-grid, .um-members-list").remove(),e.find(".um-members-wrapper").prepend(a(r.users));t=wp.template("um-members-header");e.find(".um-members-intro").remove();a=wp.hooks.applyFilters("um_member_directory_generate_header",!1,e);(void 0!==r.is_search&&r.is_search||a)&&e.find(".um-members-wrapper").prepend(t(r)),e.addClass("um-loaded"),e.find(".um-members.um-members-grid").length&&UM_Member_Grid(e.find(".um-members.um-members-grid")),jQuery(document).trigger("um_build_template",[e,r]),jQuery(window).trigger("resize"),init_tipsy()}function UM_Member_Grid(r){r.find(".um-member").length&&r.imagesLoaded(function(){var e=wp.hooks.applyFilters("um_member_directory_grid_masonry_attrs",{itemSelector:".um-member",columnWidth:".um-member",gutter:".um-gutter-sizer"},r);r.masonry(e).on("layoutComplete",function(e,r){jQuery(document).trigger("um_grid_initialized",[e,r])})})}function um_get_filters_data(u){var o=[];return u.find(".um-search-filter").each(function(){var d,t,a,i,e,r,m,s=jQuery(this);s.find("input.um-datepicker-filter").length?(d="datepicker",s.find("input.um-datepicker-filter").each(function(){var e,r,t,a,i,m;"to"!==jQuery(this).data("range")&&(e=jQuery(this).data("filter_name"),r=um_get_data_for_directory(u,"filter_"+e+"_from"),t=um_get_data_for_directory(u,"filter_"+e+"_to"),void 0===r&&void 0===t||((a=jQuery(this).val())===(i=u.find('input.um-datepicker-filter[data-range="to"][data-filter_name="'+e+'"]').val())?m=i:""!==a&&""!==i?m=a+" - "+i:""===a?m="before "+i:""===i&&(m="since "+a),o.push({name:e,label:jQuery(this).data("filter-label"),value_label:m,value:[r,t],type:d})))})):s.find("input.um-timepicker-filter").length?(d="timepicker",s.find("input.um-timepicker-filter").each(function(){var e,r,t,a,i,m;"to"!==jQuery(this).data("range")&&(e=jQuery(this).data("filter_name"),r=um_get_data_for_directory(u,"filter_"+e+"_from"),t=um_get_data_for_directory(u,"filter_"+e+"_to"),void 0===r&&void 0===t||((a=jQuery(this).val())===(i=u.find('input.um-timepicker-filter[data-range="to"][data-filter_name="'+e+'"]').val())?m=i:""!==a&&""!==i?m=a+" - "+i:""===a?m="before "+i:""===i&&(m="since "+a),o.push({name:e,label:jQuery(this).data("filter-label"),value_label:m,value:[r,t],type:d})))})):s.find("select").length?(d="select",t=s.find("select").attr("name"),a=s.find("select").data("placeholder"),i=void 0===(i=um_get_data_for_directory(u,"filter_"+t))?[]:(i=um_unsanitize_value(i)).split("||"),jQuery.each(i,function(e){var r=s.find('select option[value="'+i[e]+'"]').data("value_label");o.push({name:t,label:a,value_label:r,value:i[e],type:d})})):s.hasClass("um-text-filter-type")&&s.find('input[type="text"]').length?(d="text",t=s.find('input[type="text"]').attr("name"),a=s.find('input[type="text"]').attr("placeholder"),""!=(i=void 0===(i=um_get_data_for_directory(u,"filter_"+t))?"":i)&&o.push({name:t,label:a,value_label:i,value:i,type:d})):s.find("div.ui-slider").length?(d="slider",t=s.find("div.ui-slider").data("field_name"),e=um_get_data_for_directory(u,"filter_"+t+"_from"),r=um_get_data_for_directory(u,"filter_"+t+"_to"),void 0===e&&void 0===r||(m=(e===r?s.find("div.um-slider-range").data("placeholder-s").replace("{value}",e):s.find("div.um-slider-range").data("placeholder-p").replace("{min_range}",e).replace("{max_range}",r)).replace("{field_label}",s.find("div.um-slider-range").data("label")),a=s.find("div.um-slider-range").data("label"),o.push({name:t,label:a,value_label:m,value:[e,r],type:d}))):o=wp.hooks.applyFilters("um_member_directory_get_filter_data",o,u,s)}),o}function um_change_tag(e){var r=um_get_filters_data(e);e.find(".um-members-filter-tag").remove();var t,a=e.find(".um-filtered-line");a.length&&(t=wp.template("um-members-filtered-line"),a.prepend(t({filters:r})),0===e.find(".um-members-filter-remove").length?(e.find(".um-clear-filters").hide(),e.find(".um-clear-filters").parents(".um-member-directory-header-row").addClass("um-header-row-invisible")):(e.find(".um-clear-filters").show(),e.find(".um-clear-filters").parents(".um-member-directory-header-row").removeClass("um-header-row-invisible")))}function um_run_search(e){if(!um_is_directory_busy(e)){um_members_show_preloader(e);var r=um_get_data_for_directory(e,"search"),t=um_sanitize_value(e.find(".um-search-line").val());if(t===r||""===t&&void 0===r)um_members_hide_preloader(e);else{e.data("general_search",t),um_set_url_from_data(e,"search",t),e.data("page",1),um_set_url_from_data(e,"page","");if(!wp.hooks.applyFilters("um_member_directory_ignore_after_search",!1,e)){r=e.data("must-search");if(1===r&&(t=um_get_search(e),0===e.find(".um-members-filter-remove").length&&!t))return e.data("searched",0),e.find(".um-members-grid, .um-members-list, .um-members-intro").remove(),e.find(".um-member-directory-sorting-options").prop("disabled",!0),e.find(".um-member-directory-view-type").addClass("um-disabled"),wp.hooks.doAction("um_member_directory_clear_not_searched",e),void um_members_hide_preloader(e)}e.data("searched",1),e.find(".um-member-directory-sorting-options").prop("disabled",!1),e.find(".um-member-directory-view-type").removeClass("um-disabled"),um_ajax_get_members(e)}}}jQuery(document.body).ready(function(){jQuery(".um-directory .um-search-filter select").each(function(){1===jQuery(this).find("option:not(:disabled)").length&&jQuery(this).prop("disabled",!0);var e=jQuery(this);e.select2("destroy").select2({dropdownParent:e.parent(),containerCssClass:"um-select2-selection",dropdownCssClass:"um-select2-dropdown"})}),jQuery(document.body).on("mouseover",".um-directory .um-member-directory-view-type",function(){var e;jQuery(this).hasClass("um-disabled")||((e=jQuery(this).find(".um-member-directory-view-type-a:visible")).hide(),(e.next().length?e.next():jQuery(this).find(".um-member-directory-view-type-a:first")).show().tipsy("show"))}).on("mouseout",".um-directory .um-member-directory-view-type",function(){jQuery(this).hasClass("um-disabled")||(jQuery(this).find(".um-member-directory-view-type-a").hide().tipsy("hide"),jQuery(this).find('.um-member-directory-view-type-a[data-type="'+jQuery(this).parents(".um-directory").data("view_type")+'"]').show())}),jQuery(document.body).on("click",".um-directory .um-member-directory-view-type-a",function(){var e=jQuery(this).parents(".um-directory");if(um_is_directory_busy(e))return!1;var r,t=jQuery(this),a=t.parents(".um-member-directory-view-type");a.hasClass("um-disabled")||(um_members_show_preloader(e),(r=a.find(".um-member-directory-view-type-a:visible")).hide(),(r.next().length?r.next():a.find(".um-member-directory-view-type-a:first")).show().tipsy("show"),null!==(a=um_member_directory_last_data[um_members_get_hash(e)])&&(um_set_url_from_data(e,"view_type",t=t.data("type")),e.data("view_type",t),um_build_template(e,a),um_init_new_dropdown()),um_members_hide_preloader(e))}),jQuery(document.body).on("click",".um-directory .um-do-search",function(){um_run_search(jQuery(this).parents(".um-directory"))}),jQuery(document.body).on("keypress",".um-directory .um-search-line",function(e){13===e.which&&um_run_search(jQuery(this).parents(".um-directory"))}),jQuery(document.body).on("click",'.um-new-dropdown[data-element=".um-member-directory-sorting-a"] li a',function(){var e,r,t;1!==jQuery(this).data("selected")&&(t=jQuery(this).data("directory-hash"),um_is_directory_busy(e=jQuery('.um-directory[data-hash="'+t+'"]'))||(um_members_show_preloader(e),r=jQuery(this).html(),t=jQuery(this).data("value"),e.data("sorting",t),um_set_url_from_data(e,"sort",t),um_ajax_get_members(e),e.find('.um-new-dropdown[data-element=".um-member-directory-sorting-a"]').find("a").data("selected",0).prop("data-selected",0).attr("data-selected",0),e.find('.um-new-dropdown[data-element=".um-member-directory-sorting-a"] a[data-value="'+t+'"]').data("selected",1).prop("data-selected",1).attr("data-selected",1),e.find(".um-member-directory-sorting-a").find("> a").html(r)))}),jQuery(document.body).on("click",".um-directory .pagi:not(.current)",function(){var e,r;jQuery(this).hasClass("disabled")||(um_is_directory_busy(e=jQuery(this).parents(".um-directory"))||(um_members_show_preloader(e),1===(r="first"===jQuery(this).data("page")?1:"prev"===jQuery(this).data("page")?+e.data("page")-1:"next"===jQuery(this).data("page")?+e.data("page")+1:"last"===jQuery(this).data("page")?parseInt(e.data("total_pages")):parseInt(jQuery(this).data("page")))?(e.find('.pagi[data-page="first"], .pagi[data-page="prev"]').addClass("disabled"),e.find('.pagi[data-page="prev"], .pagi[data-page="last"]').removeClass("disabled")):(r===parseInt(e.data("total_pages"))?e.find('.pagi[data-page="prev"], .pagi[data-page="last"]').addClass("disabled"):e.find('.pagi[data-page="prev"], .pagi[data-page="last"]').removeClass("disabled"),e.find('.pagi[data-page="first"], .pagi[data-page="prev"]').removeClass("disabled")),e.find(".pagi").removeClass("current"),e.find('.pagi[data-page="'+r+'"]').addClass("current"),e.data("page",r),um_set_url_from_data(e,"page",1===r?"":r),um_ajax_get_members(e)))}),jQuery(document.body).on("change",".um-directory .um-members-pagi-dropdown",function(){var e,r=jQuery(this).parents(".um-directory");um_is_directory_busy(r)||(um_members_show_preloader(r),e=jQuery(this).val(),r.find(".pagi").removeClass("current"),r.find('.pagi[data-page="'+e+'"]').addClass("current"),r.data("page",e),um_set_url_from_data(r,"page",1===e?"":e),um_ajax_get_members(r))}),jQuery(document.body).on("click",".um-directory .um-members.um-members-list .um-member-more a",function(e){e.preventDefault();e=jQuery(this).parents(".um-member");return e.find(".um-member-more").hide(),e.find(".um-member-meta-main").slideDown(),e.find(".um-member-less").fadeIn(),!1}),jQuery(document.body).on("click",".um-directory .um-members.um-members-list .um-member-less a",function(e){e.preventDefault();e=jQuery(this).parents(".um-member");return e.find(".um-member-less").hide(),e.find(".um-member-meta-main").slideUp(),e.find(".um-member-more").fadeIn(),!1}),jQuery(document.body).on("click",".um-directory .um-members.um-members-grid .um-member-more a",function(e){e.preventDefault();var e=jQuery(this).parents(".um-member"),r=jQuery(this).parents(".um-members");return e.find(".um-member-more").hide(),e.find(".um-member-meta").slideDown(function(){UM_Member_Grid(r)}),e.find(".um-member-less").fadeIn(),setTimeout(function(){UM_Member_Grid(r)},100),!1}),jQuery(document.body).on("click",".um-directory .um-members.um-members-grid .um-member-less a",function(e){e.preventDefault();var r=jQuery(this).parents(".um-member"),t=jQuery(this).parents(".um-members");return r.find(".um-member-less").hide(),r.find(".um-member-meta").slideUp(function(){r.find(".um-member-more").fadeIn(),UM_Member_Grid(t)}),!1}),jQuery(".um-member-directory-filters-a").on("click",function(){var e=jQuery(this),r=e.parents(".um-directory").find(".um-search");r.is(":visible")?r.slideUp(250,function(){e.toggleClass("um-member-directory-filters-visible"),r.parents(".um-member-directory-header-row").toggleClass("um-header-row-invisible")}):r.slideDown({duration:250,start:function(){jQuery(this).css({display:"grid"}),e.toggleClass("um-member-directory-filters-visible"),r.parents(".um-member-directory-header-row").toggleClass("um-header-row-invisible")}})}),jQuery(document.body).on("change",".um-directory .um-search-filter select",function(){var e,r,t,a=jQuery(this).val(),i=um_sanitize_value(a);""!==i&&(um_is_directory_busy(e=jQuery(this).parents(".um-directory"))||(um_members_show_preloader(e),t=void 0===(t=um_get_data_for_directory(e,"filter_"+(r=jQuery(this).prop("name"))))?[]:t.split("||"),-1===jQuery.inArray(i,t)&&(t.push(i),um_set_url_from_data(e,"filter_"+r,t=t.join("||")),e.data("page",1),um_set_url_from_data(e,"page","")),jQuery(this).find('option[value="'+a+'"]').prop("disabled",!0).hide(),1===jQuery(this).find("option:not(:disabled)").length&&jQuery(this).prop("disabled",!0),(a=jQuery(this)).select2("destroy").select2({dropdownParent:a.parent(),containerCssClass:"um-select2-selection",dropdownCssClass:"um-select2-dropdown"}),a.val("").trigger("change"),um_ajax_get_members(e),um_change_tag(e),e.data("searched",1),e.find(".um-member-directory-sorting-options").prop("disabled",!1),e.find(".um-member-directory-view-type").removeClass("um-disabled")))}),jQuery(document.body).on("blur",'.um-directory .um-search-filter.um-text-filter-type input[type="text"]',function(){var e,r,t,a=jQuery(this).parents(".um-directory");um_is_directory_busy(a)||(e=um_sanitize_value(jQuery(this).val()))!==(t=void 0===(t=um_get_data_for_directory(a,"filter_"+(r=jQuery(this).prop("name"))))?"":t)&&(um_members_show_preloader(a),um_set_url_from_data(a,"filter_"+r,e),a.data("page",1),um_set_url_from_data(a,"page",""),um_ajax_get_members(a),um_change_tag(a),a.data("searched",1),a.find(".um-member-directory-sorting-options").prop("disabled",!1),a.find(".um-member-directory-view-type").removeClass("um-disabled"))}),jQuery(document.body).on("keypress",'.um-directory .um-search-filter.um-text-filter-type input[type="text"]',function(e){var r,t,a;13===e.which&&(um_is_directory_busy(r=jQuery(this).parents(".um-directory"))||(t=um_sanitize_value(jQuery(this).val()))!==(e=void 0===(e=um_get_data_for_directory(r,"filter_"+(a=jQuery(this).prop("name"))))?"":e)&&(um_members_show_preloader(r),um_set_url_from_data(r,"filter_"+a,t),r.data("page",1),um_set_url_from_data(r,"page",""),um_ajax_get_members(r),um_change_tag(r),r.data("searched",1),r.find(".um-member-directory-sorting-options").prop("disabled",!1),r.find(".um-member-directory-view-type").removeClass("um-disabled")))}),jQuery(document.body).on("click",".um-directory .um-members-filter-remove",function(){var e=jQuery(this).parents(".um-directory");if(!um_is_directory_busy(e)&&e){um_members_show_preloader(e);var r,t,a=jQuery(this).data("value"),i=jQuery(this).data("name"),m=jQuery(this).data("type");"text"===m?(um_set_url_from_data(e,"filter_"+i,""),jQuery('.um-search-filter input[name="'+i+'"]').val("")):"select"===m?(r=void 0===(r=um_get_data_for_directory(e,"filter_"+i))?[]:(r=um_unsanitize_value(r)).split("||"),um_set_url_from_data(e,"filter_"+i,r=!(r=-1!==jQuery.inArray(a.toString(),r)?jQuery.grep(r,function(e){return e!==a.toString()}):r).length?"":r),(t=jQuery('.um-search-filter select[name="'+i+'"]')).find('option[value="'+a+'"]').prop("disabled",!1).show(),1<t.find("option:not(:disabled)").length&&t.prop("disabled",!1),t.select2("destroy").select2({dropdownParent:t.parent(),containerCssClass:"um-select2-selection",dropdownCssClass:"um-select2-dropdown"}),0<e.find('.um-search-filter select[data-um-parent="'+i+'"]').length&&t.trigger("change")):"slider"===m?(um_set_url_from_data(e,"filter_"+i+"_from",""),um_set_url_from_data(e,"filter_"+i+"_to",""),t=(r=jQuery(".um-search-filter #"+i+"_min").siblings(".um-slider")).slider("option"),r.slider("values",[t.min,t.max]),jQuery(".um-search-filter #"+i+"_min").val(""),jQuery(".um-search-filter #"+i+"_max").val(""),um_set_range_label(r)):"datepicker"===m||"timepicker"===m?(um_set_url_from_data(e,"filter_"+i+"_from",""),um_set_url_from_data(e,"filter_"+i+"_to",""),jQuery(".um-search-filter #"+i+"_from").val(""),jQuery(".um-search-filter #"+i+"_to").val("")):wp.hooks.doAction("um_member_directory_filter_remove",m,e,i,a),e.data("page",1),um_set_url_from_data(e,"page",""),jQuery(this).tipsy("hide"),jQuery(this).parents(".um-members-filter-tag").remove(),0===e.find(".um-members-filter-remove").length?e.find(".um-clear-filters").hide():e.find(".um-clear-filters").show();i=wp.hooks.applyFilters("um_member_directory_ignore_after_search",!1,e);if(!i)if(1===e.data("must-search")){i=um_get_search(e);if(0===e.find(".um-members-filter-remove").length&&!i)return e.data("searched",0),e.find(".um-members-grid, .um-members-list, .um-members-intro").remove(),e.find(".um-member-directory-sorting-options").prop("disabled",!0),e.find(".um-member-directory-view-type").addClass("um-disabled"),wp.hooks.doAction("um_member_directory_clear_not_searched",e),void um_members_hide_preloader(e)}e.find(".um-member-directory-sorting-options").prop("disabled",!1),e.find(".um-member-directory-view-type").removeClass("um-disabled"),um_ajax_get_members(e)}}),jQuery(document.body).on("click",".um-directory .um-clear-filters-a",function(){var m=jQuery(this).parents(".um-directory");if(!um_is_directory_busy(m)){um_members_show_preloader(m),m.find(".um-members-filter-remove").each(function(){var e,r,t=jQuery(this).data("value"),a=jQuery(this).data("name"),i=jQuery(this).data("type");"text"===i?(um_set_url_from_data(m,"filter_"+a,""),jQuery('.um-search-filter input[name="'+a+'"]').val("")):"select"===i?(e=void 0===(e=um_get_data_for_directory(m,"filter_"+a))?[]:(e=um_unsanitize_value(e)).split("||"),(e=-1!==jQuery.inArray(t.toString(),e)?jQuery.grep(e,function(e){return e!==t.toString()}):e).length||(e=""),um_set_url_from_data(m,"filter_"+a,e),(r=jQuery('.um-search-filter select[name="'+a+'"]')).find('option[value="'+t+'"]').prop("disabled",!1).show(),1<r.find("option:not(:disabled)").length&&r.prop("disabled",!1),r.select2("destroy").select2({dropdownParent:r.parent(),containerCssClass:"um-select2-selection",dropdownCssClass:"um-select2-dropdown"}),0<m.find('.um-search-filter select[data-um-parent="'+a+'"]').length&&r.trigger("change")):"slider"===i?(um_set_url_from_data(m,"filter_"+a+"_from",""),um_set_url_from_data(m,"filter_"+a+"_to",""),r=(e=jQuery(".um-search-filter #"+a+"_min").siblings(".um-slider")).slider("option"),e.slider("values",[r.min,r.max]),jQuery(".um-search-filter #"+a+"_min").val(""),jQuery(".um-search-filter #"+a+"_max").val(""),um_set_range_label(e)):"datepicker"===i||"timepicker"===i?(um_set_url_from_data(m,"filter_"+a+"_from",""),um_set_url_from_data(m,"filter_"+a+"_to",""),jQuery(".um-search-filter #"+a+"_from").val(""),jQuery(".um-search-filter #"+a+"_to").val("")):wp.hooks.doAction("um_member_directory_clear_filters",i,m,a,t)}),m.data("page",1),um_set_url_from_data(m,"page",""),m.find(".um-members-filter-tag").remove(),0===m.find(".um-members-filter-remove").length?(m.find(".um-clear-filters").hide(),m.find(".um-clear-filters").parents(".um-member-directory-header-row").addClass("um-header-row-invisible")):(m.find(".um-clear-filters").show(),m.find(".um-clear-filters").parents(".um-member-directory-header-row").removeClass("um-header-row-invisible"));if(!wp.hooks.applyFilters("um_member_directory_ignore_after_search",!1,m)){var e=m.data("must-search");if(1===e)if(!um_get_search(m))return m.data("searched",0),m.find(".um-members-grid, .um-members-list, .um-members-intro").remove(),m.find(".um-member-directory-sorting-options").prop("disabled",!0),m.find(".um-member-directory-view-type").addClass("um-disabled"),wp.hooks.doAction("um_member_directory_clear_not_searched",m),void um_members_hide_preloader(m)}m.find(".um-member-directory-sorting-options").prop("disabled",!1),m.find(".um-member-directory-view-type").removeClass("um-disabled"),um_ajax_get_members(m)}}),wp.hooks.doAction("um_member_directory_on_first_pages_loading"),jQuery(".um-directory").each(function(){var e=jQuery(this),r=um_members_get_hash(e);um_member_directories.push(r),e.find(".um-search").length&&(e.find(".um-search").is(":visible")||e.find(".um-search").css({display:"grid"}).slideUp(1)),e.find(".um-slider").each(function(){var e=jQuery(this),t=e.parents(".um-directory"),a=e.data("field_name"),r=um_get_data_for_directory(t,"filter_"+a+"_from"),i=um_get_data_for_directory(t,"filter_"+a+"_to"),i=[r=void 0===r?parseInt(e.data("min")):r,i=void 0===i?parseInt(e.data("max")):i];e.slider({range:!0,min:parseInt(e.data("min")),max:parseInt(e.data("max")),values:i,create:function(e,r){},step:1,slide:function(e,r){um_set_range_label(jQuery(this),r)},stop:function(e,r){um_is_directory_busy(t)||(um_members_show_preloader(t),um_set_url_from_data(t,"filter_"+a+"_from",r.values[0]),um_set_url_from_data(t,"filter_"+a+"_to",r.values[1]),t.data("page",1),um_set_url_from_data(t,"page",""),um_ajax_get_members(t),um_change_tag(t),t.data("searched",1),t.find(".um-member-directory-sorting-options").prop("disabled",!1),t.find(".um-member-directory-view-type").removeClass("um-disabled"))}}),um_set_range_label(e)}),e.find(".um-datepicker-filter").each(function(){var d=jQuery(this),s=new Date(1e3*d.data("date_min")),u=new Date(1e3*d.data("date_max")),e=d.pickadate({selectYears:!0,min:s,max:u,formatSubmit:"yyyy/mm/dd",hiddenName:!0,onOpen:function(){d.blur()},onClose:function(){d.blur()},onSet:function(e){var r,t,a,i,m;e.select&&(um_is_directory_busy(r=d.parents(".um-directory"))||(um_members_show_preloader(r),t=d.data("filter_name"),a=d.data("range"),i=um_get_data_for_directory(r,"filter_"+t+"_from"),m=um_get_data_for_directory(r,"filter_"+t+"_to"),void 0===i&&(i=s/1e3),void 0===m&&(m=u/1e3),e=e.select/1e3,d.val(),"from"===a?i=e:"to"===a&&(m=e),um_set_url_from_data(r,"filter_"+t+"_from",i),um_set_url_from_data(r,"filter_"+t+"_to",m),r.data("page",1),um_set_url_from_data(r,"page",""),um_ajax_get_members(r),um_change_tag(r),r.data("searched",1),r.find(".um-member-directory-sorting-options").prop("disabled",!1),r.find(".um-member-directory-view-type").removeClass("um-disabled")))}}).pickadate("picker"),r=d.data("filter_name"),t=d.data("range"),t=um_get_data_for_directory(d.parents(".um-directory"),"filter_"+r+"_"+t);void 0!==t&&e.set("select",1e3*t)}),e.find(".um-timepicker-filter").each(function(){var d=jQuery(this),s=d.attr("id"),u=d.data("filter_name"),o=d.attr("data-min"),_=d.attr("data-max"),e=o.split(":"),r=_.split(":"),t=d.pickatime({format:d.data("format"),interval:parseInt(d.data("intervals")),min:[e[0],e[1]],max:[r[0],r[1]],formatSubmit:"HH:i",hiddenName:!0,onOpen:function(){d.blur()},onClose:function(){d.blur()},onSet:function(e){var r,t,a,i,m;e.select&&(um_is_directory_busy(r=d.parents(".um-directory"))||(um_members_show_preloader(r),t=d.data("filter_name"),m=d.data("range"),a=um_get_data_for_directory(r,"filter_"+t+"_from"),i=um_get_data_for_directory(r,"filter_"+t+"_to"),void 0===a&&(a=o),void 0===i&&(i=_),void 0!==e.select?(e=um_time_convert(e.select,m),"from"===m?a=e:"to"===m&&(i=e)):"from"===m?a=o:"to"===m&&(i=_),m=jQuery("#"+s).val(),"from"===d.data("range")?jQuery("#"+u+"_to").pickatime("picker").set("min",m):jQuery("#"+u+"_from").pickatime("picker").set("max",m),um_set_url_from_data(r,"filter_"+t+"_from",a),um_set_url_from_data(r,"filter_"+t+"_to",i),r.data("page",1),um_set_url_from_data(r,"page",""),um_ajax_get_members(r),um_change_tag(r),r.data("searched",1),r.find(".um-member-directory-sorting-options").prop("disabled",!1),r.find(".um-member-directory-view-type").removeClass("um-disabled")))}}).pickatime("picker"),e=d.data("filter_name"),r=d.data("range"),r=um_get_data_for_directory(d.parents(".um-directory"),"filter_"+e+"_"+r);void 0!==r&&(r=r.split(":"),t.set("select",60*r[0]+ +r[1]))}),wp.hooks.doAction("um_member_directory_on_init",e,r);var t=wp.hooks.applyFilters("um_member_directory_ignore_after_search",!1,e);if(!t&&1===e.data("must-search")){t=um_get_search(e);if(!um_get_filters_data(e).length&&!t)return}wp.hooks.applyFilters("um_member_directory_prevent_default_first_loading",!1,e,r)||(um_members_show_preloader(e),um_ajax_get_members(e,{first_load:!0}),um_change_tag(e))}),window.addEventListener("popstate",function(e){jQuery(".um-directory").each(function(){var a=jQuery(this),e=um_members_get_hash(a);um_member_directories.push(e),um_members_show_preloader(a),a.find(".um-members-grid, .um-members-list, .um-members-intro").remove(),a.find(".um-member-directory-search-line").length&&(m=um_get_data_for_directory(a,"search"),a.data("general_search",m=void 0===m?"":m),a.find(".um-search-line").val(m));var r,t=um_get_data_for_directory(a,"page");void 0===t?t=1:t>a.data("total_pages")&&(t=a.data("total_pages")),a.data("page",t).attr("data-page",t),a.find(".um-member-directory-sorting").length&&(void 0===(r=um_get_data_for_directory(a,"sort"))&&(r=a.find('.um-new-dropdown[data-element=".um-member-directory-sorting-a"]').find('a[data-default="1"]').data("value")),a.data("sorting",r),(t=a.find('.um-new-dropdown[data-element=".um-member-directory-sorting-a"]')).find("a").data("selected",0).prop("data-selected",0).attr("data-selected",0),t.find('a[data-value="'+r+'"]').data("selected",1).prop("data-selected",1).attr("data-selected",1),a.find(".um-member-directory-sorting-a").find("> a").html(t.find('a[data-value="'+r+'"]').html())),a.find(".um-member-directory-view-type").length&&(void 0===(i=um_get_data_for_directory(a,"view_type"))&&(i=a.find('.um-member-directory-view-type-a[data-default="1"]').data("type")),a.data("view_type",i),a.find(".um-member-directory-view-type .um-member-directory-view-type-a").hide(),a.find('.um-member-directory-view-type .um-member-directory-view-type-a[data-type="'+i+'"]').show()),a.find(".um-datepicker-filter").each(function(){var e=jQuery(this),r=e.pickadate("picker"),t=e.data("filter_name"),e=e.data("range"),e=um_get_data_for_directory(a,"filter_"+t+"_"+e);void 0!==e?r.set("select",1e3*e):r.clear()}),a.find(".um-slider").each(function(){var e=jQuery(this),r=e.data("field_name"),t=um_get_data_for_directory(a,"filter_"+r+"_from"),r=um_get_data_for_directory(a,"filter_"+r+"_to");void 0===t&&(t=e.data("min")),t=parseInt(t),void 0===r&&(r=e.data("max")),r=parseInt(r),e.slider("values",[t,r]),um_set_range_label(e)}),a.find(".um-timepicker-filter").each(function(){var e=jQuery(this),r=e.pickatime("picker"),t=e.data("filter_name"),e=e.data("range"),e=um_get_data_for_directory(a,"filter_"+t+"_"+e);void 0!==e?(e=e.split(":"),r.set("select",60*e[0])):r.clear()});var i=wp.hooks.applyFilters("um_member_directory_ignore_after_search",!1,a);if(!i&&1===a.data("must-search")){var m=um_get_search(a);if(!um_get_filters_data(a).length&&!m)return a.data("searched",0),void um_members_hide_preloader(a);a.data("searched",1)}wp.hooks.applyFilters("um_member_directory_prevent_default_first_loading",!1,a,e)||(um_ajax_get_members(a),um_change_tag(a))})})});
jQuery( document ).ready( function() {
	var online_blocks = jQuery( '.um-online' );

	if ( online_blocks.length ) {
		online_blocks.each( function() {
			var max = jQuery(this).attr('data-max');
			if ( max > 0 && jQuery(this).find('.um-online-user').length > max ) {
				var n = max - 1;
				jQuery(this).find('.um-online-user:gt('+n+')').hide();
				var more = jQuery(this).find('.um-online-user').length - jQuery(this).find('.um-online-user:visible').length;
				jQuery('<div class="um-online-user show-all">+'+ more + '</div>').insertAfter( jQuery(this).find('.um-online-user:visible:last') );
			}
		});

		jQuery( document.body ).on( 'click', '.um-online-user.show-all', function() {
			jQuery(this).parents('.um-online').find('.um-online-user').show();
			jQuery(this).hide();
			return false;
		});
	}
});
jQuery(document).ready(function(){var e=jQuery(".um-online");e.length&&(e.each(function(){var e=jQuery(this).attr("data-max");0<e&&jQuery(this).find(".um-online-user").length>e&&(e=e-1,jQuery(this).find(".um-online-user:gt("+e+")").hide(),e=jQuery(this).find(".um-online-user").length-jQuery(this).find(".um-online-user:visible").length,jQuery('<div class="um-online-user show-all">+'+e+"</div>").insertAfter(jQuery(this).find(".um-online-user:visible:last")))}),jQuery(document.body).on("click",".um-online-user.show-all",function(){return jQuery(this).parents(".um-online").find(".um-online-user").show(),jQuery(this).hide(),!1}))});
jQuery(document).ready(function () {
	if ( typeof ( um_recaptcha_refresh ) === 'function' ) {
		jQuery( document ).on( "um_messaging_open_login_form", function (e) {
			um_recaptcha_refresh();
		});

		jQuery( document ).on( "um_messaging_close_login_form", function (e) {
			um_recaptcha_refresh();
		});
	}
});

/**
 * reCAPTCHA v3
 * @see https://developers.google.com/recaptcha/docs/v3
 * @since version 2.1.2 [2019-09-20]
 */
if (typeof (umRecaptchaData) !== 'undefined' && umRecaptchaData.version === 'v3') {

	function um_recaptcha_validate_form(e) {
		e.preventDefault();

		var $form = jQuery(e.target);
		var action = $form.find('.g-recaptcha').data('mode') || 'homepage';

		grecaptcha.execute(umRecaptchaData.site_key, {
			action: action
		}).then(function (token) {

			if ($form.find('[name="g-recaptcha-response"]').length) {
				$form.find('[name="g-recaptcha-response"]').val(token);
			} else {
				$form.append('<input type="hidden" name="g-recaptcha-response" value="' + token + '">');
			}

			$form.off('submit', um_recaptcha_validate_form).trigger('submit');
		});
	}

	grecaptcha.ready(function () {
		jQuery('.g-recaptcha').closest('form').on('submit', um_recaptcha_validate_form);
	});
}

function um_recaptcha_validate_form(e){e.preventDefault();var a=jQuery(e.target),e=a.find(".g-recaptcha").data("mode")||"homepage";grecaptcha.execute(umRecaptchaData.site_key,{action:e}).then(function(e){a.find('[name="g-recaptcha-response"]').length?a.find('[name="g-recaptcha-response"]').val(e):a.append('<input type="hidden" name="g-recaptcha-response" value="'+e+'">'),a.off("submit",um_recaptcha_validate_form).trigger("submit")})}jQuery(document).ready(function(){"function"==typeof um_recaptcha_refresh&&(jQuery(document).on("um_messaging_open_login_form",function(e){um_recaptcha_refresh()}),jQuery(document).on("um_messaging_close_login_form",function(e){um_recaptcha_refresh()}))}),"undefined"!=typeof umRecaptchaData&&"v3"===umRecaptchaData.version&&grecaptcha.ready(function(){jQuery(".g-recaptcha").closest("form").on("submit",um_recaptcha_validate_form)});
/**
 * reCAPTCHA v3
 * @see https://developers.google.com/recaptcha/docs/v3
 * @since version 2.1.2 [2019-09-20]
 */
function um_recaptcha_validate_form( e ) {
	e.preventDefault();

	var $form = jQuery( e.target );

	grecaptcha.execute( umRecaptchaData.site_key, {
		action: 'login'
	}).then( function( token ) {

		if ( $form.find('[name="g-recaptcha-response"]').length ) {
			$form.find('[name="g-recaptcha-response"]').val( token );
		} else {
			$form.append('<input type="hidden" name="g-recaptcha-response" value="' + token + '">');
		}

		$form.off( 'submit', um_recaptcha_validate_form ).trigger( 'submit' );
	});
}

grecaptcha.ready( function() {
	jQuery('.g-recaptcha').closest('form').on( 'submit', um_recaptcha_validate_form );
});

function um_recaptcha_validate_form(e){e.preventDefault();var a=jQuery(e.target);grecaptcha.execute(umRecaptchaData.site_key,{action:"login"}).then(function(e){a.find('[name="g-recaptcha-response"]').length?a.find('[name="g-recaptcha-response"]').val(e):a.append('<input type="hidden" name="g-recaptcha-response" value="'+e+'">'),a.off("submit",um_recaptcha_validate_form).trigger("submit")})}grecaptcha.ready(function(){jQuery(".g-recaptcha").closest("form").on("submit",um_recaptcha_validate_form)});
(function( $ ) {
	'use strict';

	$(document).on('click', "a.um-toggle-terms" ,function() {
		 
		var me = jQuery(this);

		$( ".um-terms-conditions-content" ).toggle( "fast", function() {
			if( $( ".um-terms-conditions-content" ).is(':visible') ){
				me.text( me.data('toggle-hide') );
		   	}

			if( $( ".um-terms-conditions-content" ).is(':hidden') ){
				me.text( me.data('toggle-show') );
		  	}
		    
		});

	});


	$(document).on('click', "a.um-hide-terms" ,function() {

		var me = jQuery(this).parents('.um-field-area' ).find('a.um-toggle-terms');

		$( ".um-terms-conditions-content" ).toggle( "fast", function() {
			if( $( ".um-terms-conditions-content" ).is(':visible') ) {
				me.text( me.data('toggle-hide') );
		   	}

			if( $( ".um-terms-conditions-content" ).is(':hidden') ) {
				me.text( me.data('toggle-show') );
		  	}

		});

	});


})( jQuery );

!function(e){"use strict";e(document).on("click","a.um-toggle-terms",function(){var t=jQuery(this);e(".um-terms-conditions-content").toggle("fast",function(){e(".um-terms-conditions-content").is(":visible")&&t.text(t.data("toggle-hide")),e(".um-terms-conditions-content").is(":hidden")&&t.text(t.data("toggle-show"))})}),e(document).on("click","a.um-hide-terms",function(){var t=jQuery(this).parents(".um-field-area").find("a.um-toggle-terms");e(".um-terms-conditions-content").toggle("fast",function(){e(".um-terms-conditions-content").is(":visible")&&t.text(t.data("toggle-hide")),e(".um-terms-conditions-content").is(":hidden")&&t.text(t.data("toggle-show"))})})}(jQuery);