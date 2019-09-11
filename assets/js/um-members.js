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
				data[ search_key ] = decodeURI( data[ search_key ] );
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

	var query_strings = [];
	jQuery.each( new_data, function( data_key ) {
		query_strings.push( data_key + '=' + new_data[ data_key ] );
	});
	var query_string = '?' + query_strings.join( '&' );

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


function um_get_search( directory ) {
	if ( directory.find('.um-search-line').length ) {
		return directory.find( '.um-search-line' ).val();
	} else {
		return '';
	}
}

function um_get_sort( directory ) {
	if ( directory.find('.um-member-directory-sorting-options').length ) {
		return directory.find( '.um-member-directory-sorting-options' ).val();
	} else {
		return '';
	}
}

function um_get_current_page( directory ) {
	var page = directory.data( 'page' );
	if ( ! page || typeof page == 'undefined' ) {
		page = 1;
	}
	return page;
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
				if (  typeof value_from != 'undefined' || typeof value_to != 'undefined' ) {
					request[ filter_name ] = [ value_from, value_to ];
				}
			} else {
				var filter_name = filter.find('select').attr('name');
				var value = um_get_data_for_directory( directory, 'filter_' + filter_name );
				if ( typeof value != 'undefined' ) {
					request[ filter_name ] = value.split( '||' );
				}
			}
		});
	}

	wp.ajax.send( 'um_get_members', {
		data:  request,
		success: function( answer ) {
			//set last data hard for using on layouts reloading
			um_member_directory_last_data[ hash ] = answer;

			um_build_template( directory, answer.users );

			var pagination_template = wp.template( 'um-members-pagination' );
			directory.find('.um-members-pagination-box').html( pagination_template( answer ) );

			directory.data( 'total_pages', answer.pagination.total_pages );

			jQuery( document ).trigger('um_members_rendered');

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
	var template = wp.template( 'um-member-' + layout );

	directory.find('.um-members-grid, .um-members-list').remove();
	directory.find('.um-members-wrapper').prepend( template( data ) );
	directory.addClass('um-loaded');

	if ( directory.find('.um-members').length ) {
		UM_Member_Grid( directory.find('.um-members') );
	}

	jQuery( document ).trigger( 'um_build_template', [ directory, data ] );
	jQuery( window ).trigger( 'resize' );

	init_tipsy();
}



function UM_Member_Grid( container ) {
	if ( container.find( '.um-member' ).length ) {
		container.imagesLoaded( function() {
			var $grid = container.masonry({
				itemSelector: '.um-member',
				columnWidth: '.um-member',
				gutter: '.um-gutter-sizer'
			});

			$grid.on( 'layoutComplete', function( event, laidOutItems ) {
				jQuery( document ).trigger( "um_grid_initialized", [ event, laidOutItems ] );
			});
		});
	}
}




function um_change_tag( directory ) {
	var filters_data = [];
	var filter_array = [];

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
				if ( from_val !== '' &&  to_val !== '' ) {
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
				if ( from_val !== '' &&  to_val !== '' ) {
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
				filter_value = filter_value.split( '||' );
			}

			jQuery.each( filter_value, function(i) {
				var filter_value_title = filter.find('select option[value="' + filter_value[ i ] + '"]').data('value_label');
				filters_data.push( {'name':filter_name, 'label':filter_title, 'value_label':filter_value_title, 'value':filter_value[ i ], 'type':filter_type} );
			});

		} else if( filter.find('div.ui-slider').length ) {
			filter_type = 'slider';

			filter_name = filter.find('div.ui-slider').data( 'field_name' );
			var filter_value_from = um_get_data_for_directory( directory, 'filter_' + filter_name + '_from' );
			var filter_value_to = um_get_data_for_directory( directory, 'filter_' + filter_name + '_to' );

			if ( typeof filter_value_from === 'undefined' && typeof filter_value_to === 'undefined' ) {
				return;
			}

			filter_title = filter.find('div.um-slider-range').data('label');

			var filter_value_title = filter.find('div.um-slider-range').data( 'placeholder' ).replace( '\{min_range\}', filter_value_from )
			.replace( '\{max_range\}', filter_value_to )
			.replace( '\{field_label\}', filter.find('div.um-slider-range').data('label') );

			filters_data.push( {'name':filter_name, 'label':filter_title, 'value_label':filter_value_title, 'value':[filter_value_from, filter_value_to], 'type':filter_type} );
		}
	});

	directory.find('.um-members-filter-tag').remove();

	var filtered_line = directory.find('.um-filtered-line');
	if( filtered_line.length ){
		var filters_template = wp.template( 'um-members-filtered-line' );
		filtered_line.prepend( filters_template( {'filters': filters_data} ) );

		if ( filters_data.length > 0 ) {
			filtered_line.show();
			show_after_search = false;
		} else {
			filtered_line.hide();
			show_after_search = true;
		}

		if ( directory.find( '.um-members-filter-remove' ).length === 0 ) {
			directory.find('.um-clear-filters-a').hide();
		} else {
			directory.find('.um-clear-filters-a').show();
		}
	}


}


jQuery(document).ready( function() {

	/**
	 * Change View Type Handlers
	 */


	//UI for change view type button
	jQuery( document.body ).on( 'mouseover', '.um-member-directory-view-type', function() {
		var $obj = jQuery(this).find('.um-member-directory-view-type-a:visible');

		$obj.hide();

		if ( $obj.next().length ) {
			$obj.next().show().tipsy('show');
		} else {
			jQuery(this).find( '.um-member-directory-view-type-a:first' ).show().tipsy('show');
		}
	}).on( 'mouseout', '.um-member-directory-view-type', function() {
		jQuery(this).find('.um-member-directory-view-type-a').hide().tipsy('hide');
		jQuery(this).find('.um-member-directory-view-type-a[data-type="' + jQuery(this).parents( '.um-directory' ).data('view_type') + '"]').show();
	});

	//change layout handler
	jQuery( document.body ).on( 'click', '.um-member-directory-view-type-a', function() {
		var directory = jQuery(this).parents('.um-directory');
		if ( um_is_directory_busy( directory ) ) {
			return false;
		}
		um_members_show_preloader( directory );

		var $this = jQuery(this);
		var views = $this.parents('.um-member-directory-view-type');

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

			um_build_template( directory, data.users );
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
	jQuery( document.body ).on( 'click', '.um-do-search', function() {

		var directory = jQuery(this).parents('.um-directory');
		if ( um_is_directory_busy( directory ) ) {
			return;
		}
		um_members_show_preloader( directory );

		var pre_search = um_get_data_for_directory( directory, 'search' );

		var search = directory.find('.um-search-line').val();
		if ( search === pre_search || ( search === '' && typeof pre_search == 'undefined' ) ) {
			um_members_hide_preloader( directory );
			return;
		}

		directory.data( 'general_search', search );
		um_set_url_from_data( directory, 'search', search );

		//set 1st page after search
		directory.data( 'page', 1 );
		um_set_url_from_data( directory, 'page', 1 );

		um_ajax_get_members( directory );
	});


	//make search on Enter click
	jQuery( document.body ).on( 'keypress', '.um-search-line', function(e) {
		if ( e.which === 13 ) {
			var directory = jQuery(this).parents('.um-directory');
			directory.find('.um-do-search').trigger('click');
		}
	});


	/**
	 * END: General Search
	 */



	/**
	 * Sorting
	 */
	jQuery( document.body ).on( 'change', '.um-member-directory-sorting-options', function() {
		var directory = jQuery(this).parents('.um-directory');

		if ( um_is_directory_busy( directory ) ) {
			return;
		}

		um_members_show_preloader( directory );

		var sort = jQuery(this).val();

		directory.data( 'sorting', jQuery(this).val() );
		um_set_url_from_data( directory, 'sort', sort );

		um_ajax_get_members( directory );
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
		um_set_url_from_data( directory, 'page', page );

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
		um_set_url_from_data( directory, 'page', page );

		um_ajax_get_members( directory );
	});


	/**
	 * END: Pagination
	 */


	/**
	 * Profile Cards actions
	 */

	jQuery( document.body ).on('click', '.um-member-more a', function(e){
		e.preventDefault();

		var block = jQuery(this).parents('.um-member');
		var container = jQuery(this).parents('.um-members');
		block.find('.um-member-more').hide();
		block.find('.um-member-meta').slideDown( function(){ UM_Member_Grid( container ) } );
		block.find('.um-member-less').fadeIn( );

		setTimeout(function(){ UM_Member_Grid( container ) }, 100);

		return false;
	});

	jQuery( document.body ).on('click', '.um-member-less a', function(e){
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
	jQuery('.um-member-directory-filters a').click( function() {
		var search_bar = jQuery(this).parents('.um-directory').find('.um-search');

		if ( search_bar.is( ':visible' ) ) {
			search_bar.slideUp(650);
		} else {
			search_bar.slideDown(650);
		}
	});


	//filtration process
	jQuery( document.body ).on( 'change', '.um-search-filter select', function() {
		if ( jQuery(this).val() === '' ) {
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

		if ( -1 === jQuery.inArray( jQuery(this).val(), current_value ) ) {
			current_value.push( jQuery(this).val() );
			current_value = current_value.join( '||' );

			um_set_url_from_data( directory, 'filter_' + filter_name, current_value );

			//set 1st page after filtration
			directory.data( 'page', 1 );
			um_set_url_from_data( directory, 'page', 1 );
		}

		jQuery(this).val('').trigger( 'change' );

		um_ajax_get_members( directory );

		um_change_tag( directory );
	});


	//slider filter
	jQuery( '.um-slider' ).each( function() {
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
					um_set_url_from_data( directory, 'filter_' + filter_name + '_from', ui.values[0] );
					um_set_url_from_data( directory, 'filter_' + filter_name + '_to', ui.values[1] );

					//set 1st page after filtration
					directory.data( 'page', 1 );
					um_set_url_from_data( directory, 'page', 1 );
					um_ajax_get_members( directory );

					um_change_tag( directory );
				}
			}
		});

		um_set_range_label( slider );
	});


	//datepicker filter
	jQuery('.um-datepicker-filter').each( function() {
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
				um_set_url_from_data( directory, 'page', 1 );

				um_ajax_get_members( directory );

				um_change_tag( directory );
			}
		});

		var $picker 	= $input.pickadate('picker');
		var $fname		= elem.data('filter_name');
		var $frange		= elem.data('range');
		var $directory	= elem.parents('.um-directory');

		var query_value = um_get_data_for_directory( $directory, 'filter_' + $fname + '_' + $frange );
		if ( typeof query_value !== 'undefined' ) {
			$picker.set( 'select', query_value*1000 );
			//elem.attr('data-value',elem.val());
			//elem.val(elem.val());
			//elem.val('data-value', query_value*100);
		}

	});


	//timepicker filter
	jQuery('.um-timepicker-filter').each( function() {
		var elem = jQuery(this);

		//using arrays formatted as [HOUR,MINUTE]

		var min = elem.data('min');
		var max = elem.data('max');

		var $input = elem.pickatime({
			format:         elem.data('format'),
			interval:       parseInt( elem.data('intervals') ),
			min:            elem.data('min'),
			max:            elem.data('max'),
			formatSubmit:   'HH:i',
			hiddenName:     true,
			onOpen:         function() { elem.blur(); },
			onClose:        function() { elem.blur(); },
			onSet:          function( context ) {
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
					var select_val = context.select / 60;
					var change_val = elem.val();

					if ( range === 'from' ) {
						current_value_from = select_val + ':00';
					} else if ( range === 'to' ) {
						current_value_to = select_val + ':00';
					}
				} else {
					if ( range === 'from' ) {
						current_value_from = min;
					} else if ( range === 'to' ) {
						current_value_to = max;
					}
				}

				um_set_url_from_data( directory, 'filter_' + filter_name + '_from', current_value_from );
				um_set_url_from_data( directory, 'filter_' + filter_name + '_to', current_value_to );

				//set 1st page after filtration
				directory.data( 'page', 1 );
				um_set_url_from_data( directory, 'page', 1 );

				um_ajax_get_members( directory );

				um_change_tag( directory );
			}
		});


		var $picker 	= $input.pickatime('picker');
		var $fname		= elem.data('filter_name');
		var $frange		= elem.data('range');
		var $directory	= elem.parents('.um-directory');

		var query_value = um_get_data_for_directory( $directory, 'filter_' + $fname + '_' + $frange );
		if ( typeof query_value !== 'undefined' ) {
			$picker.set( 'select', query_value );
		}
	});


	jQuery( document.body ).on( 'click', '.um-members-filter-remove', function() {
		var directory = jQuery(this).parents('.um-directory');

		if ( um_is_directory_busy( directory ) ) {
			return;
		}

		um_members_show_preloader( directory );

		var removeItem = jQuery(this).data('value');
		var filter_name = jQuery(this).data('name');

		var type = jQuery(this).data('type');
		if ( type === 'select' ) {

			var current_value = um_get_data_for_directory( directory, 'filter_' + filter_name );
			if ( typeof current_value == 'undefined' ) {
				current_value = [];
			} else {
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

		} else if ( type === 'slider' ) {
			um_set_url_from_data( directory, 'filter_' + filter_name + '_from','' );
			um_set_url_from_data( directory, 'filter_' + filter_name + '_to', '' );
		} else if ( type === 'datepicker' ) {
			um_set_url_from_data( directory, 'filter_' + filter_name + '_from','' );
			um_set_url_from_data( directory, 'filter_' + filter_name + '_to', '' );
		} else if ( type === 'timepicker' ) {
			um_set_url_from_data( directory, 'filter_' + filter_name + '_from','' );
			um_set_url_from_data( directory, 'filter_' + filter_name + '_to', '' );
		}


		//set 1st page after filtration
		directory.data( 'page', 1 );
		um_set_url_from_data( directory, 'page', 1 );
		jQuery(this).parents('.um-members-filter-tag').remove();

		um_ajax_get_members( directory );

		if ( directory.find( '.um-members-filter-remove' ).length === 0 ) {
			directory.find('.um-clear-filters-a').hide();
		} else {
			directory.find('.um-clear-filters-a').show();
		}
	});


	jQuery( document.body ).on( 'click', '.um-clear-filters-a', function() {
		var directory = jQuery(this).parents('.um-directory');
		if ( um_is_directory_busy( directory ) ) {
			return;
		}

		um_members_show_preloader( directory );

		directory.find( '.um-members-filter-remove' ).each( function() {
			var removeItem = jQuery(this).data('value');
			var filter_name = jQuery(this).data('name');

			var type = jQuery(this).data('type');
			if ( type === 'select' ) {

				var current_value = um_get_data_for_directory( directory, 'filter_' + filter_name );
				if ( typeof current_value == 'undefined' ) {
					current_value = [];
				} else {
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

			} else if ( type === 'slider' ) {
				um_set_url_from_data( directory, 'filter_' + filter_name + '_from','' );
				um_set_url_from_data( directory, 'filter_' + filter_name + '_to', '' );
			} else if ( type === 'datepicker' ) {
				um_set_url_from_data( directory, 'filter_' + filter_name + '_from','' );
				um_set_url_from_data( directory, 'filter_' + filter_name + '_to', '' );
			} else if ( type === 'timepicker' ) {
				um_set_url_from_data( directory, 'filter_' + filter_name + '_from','' );
				um_set_url_from_data( directory, 'filter_' + filter_name + '_to', '' );
			}
		});

		//set 1st page after filtration
		directory.data( 'page', 1 );
		um_set_url_from_data( directory, 'page', 1 );
		directory.find('.um-members-filter-tag').remove();

		um_ajax_get_members( directory );

		jQuery(this).hide();
	});


	/**
	 * First Page Loading
	 */


	//Init Directories
	jQuery( '.um-directory' ).each( function() {
		var directory = jQuery(this);
		var hash = um_members_get_hash( directory );
		um_member_directories.push( hash );

		var show_after_search = jQuery(this).data('show');
		if ( show_after_search !== false ) {
			return;
		}

		um_members_show_preloader( directory );
		um_ajax_get_members( directory );

		um_change_tag( directory );
	});

});