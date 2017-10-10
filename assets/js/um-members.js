var um_members_layout = [];
var um_members_last_data = [];

var um_members_hash_data = {};

var um_members_directory_busy = [];

jQuery(document).ready(function() {

	//change layout
	jQuery( document ).on( 'click', '.um-member-directory-view-type-a', function(e){
		e.preventDefault();

		var directory = jQuery(this).parents('.um-directory');

		if ( is_directory_busy( directory ) )
			return false;

		var layout = um_members_get_layout( directory );

		layout = ( layout == 'grid' ) ? 'list' : 'grid';

		um_members_set_layout( directory, layout );

		var button_class = ( layout == 'list' ) ? 'um-faicon-list' : 'um-faicon-th';
		directory.find('.um-member-directory-view-type-a i').attr( 'class', button_class );

		var tooltip_title = ( layout == 'list' ) ? 'Change to Grid' : 'Change to List';
		directory.find('.um-member-directory-view-type-a').attr( 'original-title', tooltip_title );

		directory.find('.um-member-directory-view-type-a').tipsy('hide').tipsy('show');

		var data = um_members_get_last_data( directory );
		if ( typeof data !== 'undefined' ) {
			um_build_template( directory, data );
		} else {
			um_members_show_preloader( directory );

			um_ajax_get_members( directory );
		}
	});


	//pagination
	jQuery( document ).on( 'click', '.um-directory .pagi:not(.current)', function(e){
		e.preventDefault();

		if ( jQuery(this).hasClass('disabled') )
			return;

		var directory = jQuery(this).parents('.um-directory');

		if ( is_directory_busy( directory ) )
			return false;

		var page;
		if ( 'first' == jQuery(this).data('page') ) {
			page = 1;
		} else if ( 'prev' == jQuery(this).data('page') ) {
			page = directory.data( 'page' )*1 - 1;
		} else if ( 'next' == jQuery(this).data('page') ) {
			page = directory.data( 'page' )*1 + 1;
		} else if ( 'last' == jQuery(this).data('page') ) {
			page = directory.data( 'total_pages' );
		} else {
			page = jQuery(this).data('page');
		}

		if ( page == 1 ) {
			directory.find('.pagi[data-page="first"], .pagi[data-page="prev"]').addClass('disabled');
			directory.find('.pagi[data-page="prev"], .pagi[data-page="last"]').removeClass('disabled');
		} else if ( page == directory.data( 'total_pages' ) ) {
			directory.find('.pagi[data-page="prev"], .pagi[data-page="last"]').addClass('disabled');
			directory.find('.pagi[data-page="first"], .pagi[data-page="prev"]').removeClass('disabled');
		} else {
			directory.find('.pagi[data-page="prev"], .pagi[data-page="last"]').removeClass('disabled');
			directory.find('.pagi[data-page="first"], .pagi[data-page="prev"]').removeClass('disabled');
		}

		directory.find('.pagi').removeClass('current');
		directory.find('.pagi[data-page="' + page + '"]').addClass('current');

		um_members_set_page( directory, page );

		um_members_hash_data[um_members_get_unique_id( directory )].page = page;

		window.location.hash = um_members_create_hash_string();
		return false;
	});


	//mobile pagination
	jQuery( document ).on( 'change', '.um-directory .um-members-pagi-dropdown', function(e){
		e.preventDefault();

		var directory = jQuery(this).parents('.um-directory');

		if ( is_directory_busy( directory ) )
			return false;

		var page = jQuery(this).val();

		directory.find('.pagi').removeClass('current');
		directory.find('.pagi[data-page="' + page + '"]').addClass('current');

		um_members_set_page( directory, page );

		um_members_hash_data[um_members_get_unique_id( directory )].page = page;

		window.location.hash = um_members_create_hash_string();
		return false;
	});


	/**
	 * Searching
	 */
	jQuery( document ).on( 'click', '.um-do-search', function(e){
		e.preventDefault();

		var directory = jQuery(this).parents('.um-directory');

		if ( is_directory_busy( directory ) )
			return false;

		var unique = um_members_get_unique_id( directory );

		um_members_hash_data[ unique ].general_search = directory.find('.um-search-line').val();
		um_members_hash_data[ unique ].page = 1;

		um_members_clear_hash();

		window.location.hash = um_members_create_hash_string();
		return false;
	});

	jQuery( document ).on('keypress', '.um-search-line', function(e) {
		if ( e.which == '13' ) {
			var directory = jQuery(this).parents('.um-directory');
			directory.find('.um-do-search').trigger('click');
		}
	});


	//filters controls
	jQuery('.um-member-directory-filters').click( function(e){
		e.preventDefault();
		var search_bar = jQuery(this).parents('.um-directory').find('.um-search');

		if ( search_bar.is(':visible') ) {
			search_bar.slideUp(750);
		} else {
			search_bar.slideDown(750);
		}
	});

	jQuery('.um-close-filter').click( function(e){
		e.preventDefault();
		var search_bar = jQuery(this).parents('.um-directory').find('.um-search');

		search_bar.slideUp(750);
	});


	//filtration process
	jQuery( document ).on( 'change', '.um-search-filter select', function(e){
		e.preventDefault();

		if ( jQuery(this).val() == '' )
			return false;

		var directory = jQuery(this).parents('.um-directory');

		if ( is_directory_busy( directory ) )
			return false;

		var unique = um_members_get_unique_id( directory );

		var global_hash = um_members_hash_data[ unique ][ jQuery(this).prop('name') ];

		if ( typeof global_hash == 'undefined' ) {
			global_hash = [];
		} else if( typeof global_hash == 'string' ) {
			global_hash = [ global_hash ];
		}

		if ( -1 == jQuery.inArray( jQuery(this).val(), global_hash ) ) {
			global_hash.push( jQuery(this).val() );

			um_members_hash_data[ unique ][ jQuery(this).prop('name') ] = global_hash;

			um_members_hash_data[ unique ].page = 1;
			um_members_clear_hash();

			window.location.hash = um_members_create_hash_string();
		}

		jQuery(this).val('').trigger('change');

		return false;
	});

	jQuery( document ).on( 'click', '.um-members-filter-remove', function(e){
		e.preventDefault();

		var directory = jQuery(this).parents('.um-directory');

		if ( is_directory_busy( directory ) )
			return false;

		var unique = um_members_get_unique_id( directory );

		var removeItem = jQuery(this).data('value');
		if ( typeof um_members_hash_data[ unique ][ jQuery(this).data('name') ] == 'string' ) {
			um_members_hash_data[ unique ][ jQuery(this).data('name') ] = [um_members_hash_data[ unique ][ jQuery(this).data('name') ]];
		}

		um_members_hash_data[ unique ][ jQuery(this).data('name') ] = jQuery.grep( um_members_hash_data[ unique ][ jQuery(this).data('name') ], function(value) {
			return value != removeItem;
		});

		um_members_hash_data[ unique ].page = 1;

		um_members_clear_hash();

		directory.find('.um-members-filter-tag').remove();

		window.location.hash = um_members_create_hash_string();
		return false;
	});

	jQuery( document ).on( 'click', '.um-clear-filters-a', function(e){
		e.preventDefault();

		var directory = jQuery(this).parents('.um-directory');

		if ( is_directory_busy( directory ) )
			return false;

		var unique = um_members_get_unique_id( directory );

		directory.find( '.um-members-filter-remove' ).each( function() {
			var removeItem = jQuery(this).data('value');
			if ( typeof um_members_hash_data[ unique ][ jQuery(this).data('name') ] == 'string' ) {
				um_members_hash_data[ unique ][ jQuery(this).data('name') ] = [um_members_hash_data[ unique ][ jQuery(this).data('name') ]];
			}

			um_members_hash_data[ unique ][ jQuery(this).data('name') ] = jQuery.grep( um_members_hash_data[ unique ][ jQuery(this).data('name') ], function(value) {
				return value != removeItem;
			});
		});

		um_members_hash_data[ unique ].page = 1;

		directory.find('.um-members-filter-tag').remove();

		um_members_clear_hash();

		window.location.hash = um_members_create_hash_string();
		return false;
	});


	//sorting
	jQuery( document ).on( 'change', '.um-member-directory-sorting-options', function(e){
		var directory = jQuery(this).parents('.um-directory');

		if ( is_directory_busy( directory ) )
			return false;

		var unique = um_members_get_unique_id( directory );
		um_members_hash_data[ unique ].sorting = jQuery(this).val();
		um_members_hash_data[ unique ].page = 1;
		window.location.hash = um_members_create_hash_string();
		return false;
	});


	//grid controls
	jQuery(document).on( 'click', '.um-member-more a', function(e){
		e.preventDefault();

		var directory = jQuery(this).parents('.um-directory');

		var block = jQuery(this).parents('.um-member');
		block.find('.um-member-more').hide();
		block.find('.um-member-meta').slideDown( function() {
			block.find('.um-member-less').fadeIn();
			if ( directory.find('.um-members').length ) {
				UM_Member_Grid( directory.find('.um-members') );
			}
		});

		setTimeout( function() {
			if ( directory.find('.um-members').length ) {
				UM_Member_Grid( directory.find('.um-members') );
			}
		}, 100 );
	});

	jQuery(document).on( 'click', '.um-member-less a', function(e){
		e.preventDefault();

		var directory = jQuery(this).parents('.um-directory');

		var block = jQuery(this).parents('.um-member');
		block.find('.um-member-less').hide();
		block.find('.um-member-meta').slideUp( function() {
			block.find('.um-member-more').fadeIn();
			if ( directory.find('.um-members').length ) {
				UM_Member_Grid( directory.find('.um-members') );
			}
		});
	});


	//history events when back/forward and change window.location.hash
	window.addEventListener( "popstate", function(e) {
		um_members_directory_loop();
	});

	um_members_directory_loop();
});


function is_directory_busy( directory ) {
	return typeof um_members_directory_busy[ um_members_get_unique_id( directory ) ] != 'undefined' && um_members_directory_busy[ um_members_get_unique_id( directory ) ];
}

function um_members_set_layout( directory, layout ) {
	um_members_layout[ um_members_get_unique_id( directory ) ] = layout;
	directory.data('view_type', layout);
}

function um_members_get_layout( directory ) {
	return um_members_layout[ um_members_get_unique_id( directory ) ];
}

function um_members_set_last_data( directory, data ) {
	um_members_last_data[ um_members_get_unique_id( directory ) ] = data;
}

function um_members_get_last_data( directory ) {
	return um_members_last_data[ um_members_get_unique_id( directory ) ];
}

function um_members_get_unique_id( directory ) {
	return directory.data( 'unique_id' );
}

function um_members_get_page( directory ) {
	return directory.data( 'page' );
}

function um_members_set_page( directory, page ) {
	return directory.data( 'page', page );
}


function um_members_set_search( directory ) {
	directory.find('.um-search-line').val( um_members_hash_data[ um_members_get_unique_id( directory ) ].general_search );
}

function um_members_set_sorting( directory ) {
	directory.find('.um-member-directory-sorting-options').val( um_members_hash_data[ um_members_get_unique_id( directory ) ].sorting ).trigger('change');
}

function um_members_set_filters( directory ) {

	if ( ! jQuery('#tmpl-um-members-filtered-line').length )
		return false;

	var unique_id = um_members_get_unique_id( directory );
	var filters_data = [];

	directory.find('.um-search-filter').each( function() {
		var filter_name = jQuery(this).find('select').attr('name');
		var query_value = um_members_hash_data[ unique_id ][ filter_name ];

		var filter_title = jQuery(this).find('select').data('placeholder');
		var filter_value_title;

        var filter = jQuery(this);

		if ( typeof( query_value ) != 'undefined' ) {
			if ( typeof( query_value ) == 'string' ) {
                filter_value_title = filter.find('select option[value="' + query_value + '"]').data('value_label');
				filters_data.push( {'name':filter_name, 'label':filter_title, 'value_label':filter_value_title, 'value':query_value, 'unique_id':unique_id} );
			} else {
				jQuery.each( query_value, function(e) {
                    filter_value_title = filter.find('select option[value="' + query_value[e] + '"]').data('value_label');
					filters_data.push( {'name': filter_name, 'label':filter_title, 'value_label':filter_value_title, 'value':query_value[e], 'unique_id':unique_id} );
				});
			}
		}
	});

	directory.find('.um-members-filter-tag').remove();

	var filters_template = wp.template( 'um-members-filtered-line' );
	directory.find('.um-filtered-line').prepend( filters_template( {'filters': filters_data} ) );

	if ( filters_data.length > 0 ) {
		directory.find('.um-filtered-line').show();
	} else {
		directory.find('.um-filtered-line').hide();
	}
}

function um_members_show_preloader( directory ) {
	directory.find('.um-members-overlay').show();
}

function um_members_hide_preloader( directory ) {
	directory.find('.um-members-overlay').hide();
}

function um_ajax_get_members( directory ) {
	//set filters tab
	var temp_hash = um_members_parse_hash( directory );
	temp_hash.args = um_members_args;

	jQuery.ajax({
		url: um_scripts.ajax_get_members,
		type: 'post',
		data: temp_hash,
		success: function( answer ) {
			um_members_set_last_data( directory, answer );

			um_build_template( directory, answer );

			um_members_hide_preloader( directory );

			var pagination_template = wp.template( 'um-members-pagination' );
			directory.find('.um-members-pagination-box').html( pagination_template( answer.data ) );

			directory.data( 'total_pages', answer.data.pagi.total_pages );

			um_members_directory_busy[ um_members_get_unique_id( directory ) ] = false;
		},
		error: function( e ) {
			console.log( e );
		}
	});
}

function um_build_template( directory, data ) {
	var layout = um_members_get_layout( directory );

	var template = wp.template( 'um-member-' + layout );

	directory.find('.um-members, .um-members-list').remove();
	directory.find('.um-members-wrapper').prepend( template( data.data ) );
	directory.addClass('um-loaded');
	if ( directory.find('.um-members').length ) {
		UM_Member_Grid( directory.find('.um-members') );
		jQuery(window).trigger('resize');
	}


    jQuery( document ).trigger( "um_build_template", [ directory ] );
}


/**
 * Create HASH string for browser from global variable
 *
 * @returns {string}
 */
function um_members_create_hash_string() {
	var hash_array = [];
	for ( var unique in um_members_hash_data ) {
		for ( var index in um_members_hash_data[unique] ) {
			hash_array.push( index + '_' + unique + '=' + um_members_hash_data[unique][index] );
		}
	}

	return '#' + hash_array.join('&');
}


/**
 * Parse HASH to object from browser HASH
 *
 * @param directory
 * @returns {{}}
 */
function um_members_parse_hash( directory ) {
	var hash_obj = {};
	var hash = window.location.hash.substring( 1, window.location.hash.length );

	if ( hash == '' ) {
		return hash_obj;
	}

	var unique = um_members_get_unique_id( directory );
	var hash_array = hash.split('&');

	for ( var index in hash_array ) {
		var temp = hash_array[index].split('=');
		if( temp[0].search( new RegExp( "_" + unique + "$", "g" ) ) !== -1 ) {
			temp[0] = temp[0].replace( new RegExp( "_" + unique + "$", "g" ), '' );

			if ( temp[1].search( new RegExp( ",", "g" ) ) !== -1 ) {
				hash_obj[temp[0]] = temp[1].split(",");
			} else {
				hash_obj[temp[0]] = temp[1];
			}
		}
	}

	return hash_obj;
}


/**
 * Set HASH to object from browser HASH
 *
 * @param directory
 * @returns {{}}
 */
function um_members_set_hash( directory ) {
	um_members_hash_data[ um_members_get_unique_id( directory ) ] = um_members_parse_hash( directory );
}


/**
 * Clear global HASH variable
 * set browser HASH if set_hash = true
 *
 * @param set_hash
 */
function um_members_clear_hash( set_hash ) {
	jQuery.each( um_members_hash_data, function( unique ) {
		if ( ! jQuery('.um-directory[data-unique_id="' + unique + '"]').length ) {
			delete um_members_hash_data[unique];
		} else {
			jQuery.each( um_members_hash_data[unique], function( property ) {
				if ( um_members_hash_data[unique][property] == '' ) {
					delete um_members_hash_data[unique][property];
				}
			});
		}
	});

	if ( set_hash ) {
		unique_table = false;
		window.location.hash = um_members_create_hash_string();
	}
}


function um_members_directory_loop() {
	jQuery( '.um-directory' ).each( function() {

		um_members_directory_busy[ um_members_get_unique_id( jQuery(this) ) ] = true;

		um_members_set_layout( jQuery(this), jQuery(this).data('view_type') );

		um_members_set_hash( jQuery(this) );

		um_members_set_search( jQuery(this) );

		um_members_set_sorting( jQuery(this) );

		um_members_set_filters( jQuery(this) );

		//show results after search
		if ( jQuery(this).data('only_search') == '1' && ! um_members_hash_data[ um_members_get_unique_id( jQuery(this) ) ].hasOwnProperty('general_search') ) {
			um_members_hide_preloader( jQuery(this) );
			um_members_directory_busy[ um_members_get_unique_id( jQuery(this) ) ] = false;
			return false;
		}

		um_members_show_preloader( jQuery(this) );

		um_ajax_get_members( jQuery(this) );
	});
}
