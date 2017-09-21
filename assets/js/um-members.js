var um_members_layout = [];
var um_members_last_data = [];

jQuery(document).ready(function() {

	jQuery( '.um-directory' ).each( function() {
		um_members_set_layout( jQuery(this), 'grid' );

		um_members_show_preloader( jQuery(this) );

		um_ajax_get_members( jQuery(this) );
	});


	jQuery('.um-member-directory-filters').click( function(){
		var search_bar = jQuery(this).parents('.um-directory').find('.um-search');

		if ( search_bar.is(':visible') ) {
			search_bar.slideUp(750);
		} else {
			search_bar.slideDown(750);
		}
	});


	jQuery( document ).on( 'click', '.um_change_view_link:not(.current)', function(e){
		e.preventDefault();
		var layout = jQuery(this).data('view');
		var directory = jQuery(this).parents('.um-directory');

		directory.find( '.um_change_view_link' ).removeClass('current');
		jQuery(this).addClass('current');

		var button_class = ( layout == 'list' ) ? 'um-faicon-th-list' : 'um-faicon-th-large';
		directory.find('.um-member-directory-view-type-a i').attr( 'class', button_class );

		um_members_set_layout( directory, layout );

		var data = um_members_get_last_data( directory );
		if ( typeof data !== 'undefined' ) {
			um_build_template( directory, data );
		} else {
			um_members_show_preloader( directory );

			um_ajax_get_members( directory );
		}

	});


	jQuery( document ).on( 'click', '.um-directory .pagi:not(.current)', function(e){
		e.preventDefault();
		var directory = jQuery(this).parents('.um-directory');



		var page;
		if ( 'first' == jQuery(this).data('page') ) {
			page = 1;
		} else if ( 'prev' == jQuery(this).data('page') ) {
			page = directory.data( 'page' )*1 - 1;
		} else if ( 'next' == jQuery(this).data('page') ) {
			page = directory.data( 'page' )*1 + 1;
		} else if ( 'last' == jQuery(this).data('page') ) {
			page = directory.data( 'page' )*1 + 1;
		} else {
			page = jQuery(this).data('page');
		}


		directory.find('.pagi').removeClass('current');
		jQuery(this).addClass('current');

		um_members_set_page( directory, jQuery(this).data('page') );

		um_members_show_preloader( directory );

		um_ajax_get_members( directory );
	});



	jQuery('.um-member-connect').each(function(){
		if ( jQuery(this).find('a').length == 0 ) {
			jQuery(this).remove();
		}
	});
	
	jQuery('.um-member-meta-main').each(function(){
		if ( jQuery(this).find('.um-member-metaline').length == 0 && jQuery(this).find('.um-member-connect').find('a').length == 0 ) {
			jQuery(this).remove();
		}
	});

	jQuery(document).on('click', '.um-member-more a', function(e){
		e.preventDefault();

		var block = jQuery(this).parents('.um-member');
		var container = jQuery(this).parents('.um-members');
		block.find('.um-member-more').hide();
		block.find('.um-member-meta').slideDown( function(){ UM_Member_Grid( container ) } );
		block.find('.um-member-less').fadeIn( );
		
		setTimeout(function(){ UM_Member_Grid( container ) }, 100);

		return false;
	});

	jQuery(document).on('click', '.um-member-less a', function(e){
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

	jQuery(document).on('click', '.um-do-search', function(e){
		e.preventDefault();

		jQuery(this).parents('form').find('input').filter(function(e){
			if ( this.value.length ===0 ) {
				return true;
			}
		}).prop('disabled', true);


		jQuery(this).parents('form').find('select').filter(function(e){
			if ( this.value.length ===0 ) {
				return true;
			}
		}).prop('disabled', true);
		jQuery(this).parents('form').submit();
		return false;
	});

});

function um_members_set_layout( directory, layout ) {
	um_members_layout[ um_members_get_unique_id( directory ) ] = layout;
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


function um_members_show_preloader( directory ) {
	directory.find('.um-members-overlay').show();
}

function um_members_hide_preloader( directory ) {
	directory.find('.um-members-overlay').hide();
}

function um_ajax_get_members( directory ) {
	var page = um_members_get_page( directory );

	jQuery.ajax({
		url: um_scripts.ajax_get_members,
		type: 'post',
		data: {
			args : um_members_args,
			page : page
		},
		success: function( answer ) {
			um_members_set_last_data( directory, answer );

			um_build_template( directory, answer );

			um_members_hide_preloader( directory );
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
}
