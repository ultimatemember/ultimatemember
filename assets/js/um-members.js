jQuery(document).ready(function() {

	jQuery('.um-directory .um-member-connect').each(function(){
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

	jQuery(".um-search form *").keypress( function(e){
		if (e.which == 13) {
			jQuery('.um-search form').submit();
			return false;
		}
	});


	/**
	 * @used jquery-masonry
	 * @param container
	 * @constructor
	 */
	function UM_Member_Grid( container ) {
		container.masonry({
			itemSelector: '.um-member',
			columnWidth: '.um-member',
			gutter: '.um-gutter-sizer'
		});
	}

	function um_init_members_grid() {
		jQuery('.um-members').each( function(){
			UM_Member_Grid( jQuery(this) );
		});
	}

	um_init_members_grid();

	jQuery( document ).on( "um_responsive_event", um_init_members_grid );
});