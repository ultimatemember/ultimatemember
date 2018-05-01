jQuery(document).ready(function() {

	jQuery('.um-profile.um-viewing .um-profile-body .um-row').each(function(){
		var this_row = jQuery(this);
		if ( this_row.find('.um-field').length == 0 ) {
			this_row.prev('.um-row-heading').remove();
			this_row.remove();
		}
	});
	
	if ( jQuery('.um-profile.um-viewing .um-profile-body').length && jQuery('.um-profile.um-viewing .um-profile-body').find('.um-field').length == 0 ) {
		jQuery('.um-row-heading,.um-row').remove();
		jQuery('.um-profile-note').show();
	}
	
	jQuery(document).on('click', '.um-profile-save', function(e){
		e.preventDefault();
		jQuery(this).parents('.um').find('form').submit();
		return false;
	});
	
	jQuery(document).on('click', '.um-profile-edit-a', function(e){
		jQuery(this).addClass('active');
	});

    jQuery(document).on('click', '.um-cover a.um-cover-add, .um-photo a', function(e){
		e.preventDefault();
		return false;
	});

	jQuery(document).on('click', '.um-photo-modal', function(e){
		e.preventDefault();
		var photo_src = jQuery(this).attr('data-src');
		um_new_modal('um_view_photo', 'fit', true, photo_src );
		return false;
	});

	jQuery(document).on('click', '.um-reset-profile-photo', function(e){
		jQuery('.um-profile-photo-img img').attr('src', jQuery(this).attr('data-default_src') );
		
		user_id = jQuery(this).attr('data-user_id');
		metakey = 'profile_photo';
		
		jQuery.ajax({
			url: um_scripts.delete_profile_photo,
			type: 'post',
			data: {
				metakey: metakey,
				user_id: user_id
			}
		});
	});

	jQuery(document).on('click', '.um-reset-cover-photo', function(e){
		jQuery('.um-cover-overlay').hide();
		
		jQuery('.um-cover-e').html('<a href="#" class="um-cover-add um-manual-trigger" data-parent=".um-cover" data-child=".um-btn-auto-width"><span class="um-cover-add-i"><i class="um-icon-plus um-tip-n" title="Upload a cover photo"></i></span></a>');
		
		jQuery('.um-dropdown').hide();
		
		um_responsive();
		
		user_id = jQuery(this).attr('data-user_id');
		metakey = 'cover_photo';
		
		jQuery.ajax({
			url: um_scripts.delete_cover_photo,
			type: 'post',
			data: {
				metakey: metakey,
				user_id: user_id
			}
		});
	});

	// Bio characters limit
	function um_update_bio_countdown() {
		var meta_bio_obj = jQuery('textarea[id="um-meta-bio"]');
		if ( typeof meta_bio_obj.val() !== 'undefined' ){
			var um_bio_limit = meta_bio_obj.attr( "data-character-limit" );
			var remaining = um_bio_limit - meta_bio_obj.val().length;
			jQuery('span.um-meta-bio-character span.um-bio-limit').text( remaining );
			var meta_color = '';
			if ( remaining  < 5 ) {
				meta_color = 'red';
			}
			jQuery('span.um-meta-bio-character').css( 'color',meta_color );
		}
	}

	um_update_bio_countdown();
	jQuery('textarea[id="um-meta-bio"]').change( um_update_bio_countdown ).keyup( um_update_bio_countdown );

	jQuery('.um-profile-edit a.um_delete-item').click(function(e){
		e.preventDefault();
		var a = confirm('Are you sure that you want to delete this user?');
		if ( ! a ) {
			return false;
		}
	});


	//Profile Page
	jQuery( document ).on( 'click', '.um-dropdown a', function(e){
		return false;
	});


	//Profile Page
	jQuery( document ).on( 'click', '.um-dropdown a.real_url', function(e){
		window.location = jQuery(this).attr('href');
	});


	//Profile Page
	jQuery( document ).on( 'click', '.um-trigger-menu-on-click', function(e){
		jQuery('.um-dropdown').hide();
		menu = jQuery(this).find('.um-dropdown');
		menu.show();
		return false;
	});


	//Profile Page
	jQuery( document ).on( 'click', '.um-dropdown-hide', function(e){
		UM_hide_menus();
	});


	//Profile Page
	jQuery( document ).on('click', 'a.um-manual-trigger', function(){
		var child = jQuery(this).attr('data-child');
		var parent = jQuery(this).attr('data-parent');
		jQuery(this).parents( parent ).find( child ).trigger('click');
	});


	//Profile Page
	jQuery(document).on('click', '.um .um-single-image-preview a.cancel', function(e){
		e.preventDefault();
		var parent = jQuery(this).parents('.um-field');
		var src = jQuery(this).parents('.um-field').find('.um-single-image-preview img').attr('src');
		parent.find('.um-single-image-preview img').attr('src','');
		parent.find('.um-single-image-preview').hide();
		parent.find('.um-btn-auto-width').html('Upload');
		parent.find('input[type=hidden]').val('empty_file');

		jQuery.ajax({
			url: um_scripts.remove_file,
			type: 'post',
			data: {
				src: src
			}
		});

		return false;
	});

	//Profile Page
	jQuery(document).on('click', '.um .um-single-file-preview a.cancel', function(e){
		e.preventDefault();
		var parent = jQuery(this).parents('.um-field');
		var src = jQuery(this).parents('.um-field').find('.um-single-fileinfo a').attr('href');
		parent.find('.um-single-file-preview').hide();
		parent.find('.um-btn-auto-width').html('Upload');
		parent.find('input[type=hidden]').val('empty_file');

		jQuery.ajax({
			url: um_scripts.remove_file,
			type: 'post',
			data: {
				src: src
			}
		});

		return false;
	});


	//Profile Form
	var um_select_options_cache = {};

	/**
	 * Find all select fields with parent select fields
	 */
	jQuery('select[data-um-parent]').each( function(){

		var me = jQuery(this);
		var parent_option = me.data('um-parent');
		var um_ajax_url = me.data('um-ajax-url');
		var um_ajax_source = me.data('um-ajax-source');
		var original_value = me.val();

		me.attr('data-um-init-field', true );

		jQuery(document).on('change','select[name="'+parent_option+'"]',function(){
			var parent  = jQuery(this);
			var form_id = parent.closest('form').find('input[type=hidden][name=form_id]').val();
			var arr_key = parent.val();

			if( parent.val() != '' && typeof um_select_options_cache[ arr_key ] != 'object' ){
				jQuery.ajax({
					url: um_scripts.ajax_select_options,
					type: 'post',
					data: {
						parent_option_name: parent_option,
						parent_option: parent.val(),
						child_callback: um_ajax_source,
						child_name:  me.attr('name'),
						form_id: form_id
					},
					success: function( data ){
						if( data.status == 'success' && parent.val() != '' ){
							um_field_populate_child_options( me, data, arr_key);
						}

						if( typeof data.debug !== 'undefined' ){
							console.log( data );
						}
					},
					error: function( e ){
						console.log( e );
					}
				});

			}

			if( parent.val() != '' && typeof um_select_options_cache[ arr_key ] == 'object' ){
				var data = um_select_options_cache[ arr_key ];
				um_field_populate_child_options( me, data, arr_key );
			}

			if( parent.val() == '' ){
				me.find('option[value!=""]').remove();
				me.val('').trigger('change');
			}

		});

		jQuery('select[name="'+parent_option+'"]').trigger('change');

	});

	/**
	 * Populates child options and cache ajax response
	 * @param  DOM me     child option elem
	 * @param  array data
	 * @param  string key
	 */
	function um_field_populate_child_options( me, data, arr_key, arr_items ) {

		var parent_option = me.data('um-parent');
		var child_name = me.attr('name');
		var parent_dom = jQuery('select[name="'+parent_option+'"]');
		me.find('option[value!=""]').remove();

		if ( ! me.hasClass('um-child-option-disabled') ) {
			me.removeAttr('disabled');
		}

		var arr_items = [];
		jQuery.each( data.items, function( k, v ) {
			arr_items.push({id: k, text: v});
		});

		me.select2('destroy');
		me.select2({
			data: arr_items,
			allowClear: true,
			minimumResultsForSearch: 10
		});

		if ( typeof data.field.default !== 'undefined' && ! me.data('um-original-value') ) {
			me.val( data.field.default ).trigger('change');
		} else if( me.data('um-original-value') != '' ) {
			me.val( me.data('um-original-value') ).trigger('change');
		}

		if ( data.field.editable == 0 ) {
			me.addClass('um-child-option-disabled');
			me.attr('disabled','disabled');
		}

		um_select_options_cache[ arr_key ] = data;

	}

	jQuery( document ).on( "um_responsive_event", um_domenus );
});


/**
 *
 * @constructor
 */
function um_domenus() {

	jQuery('.um-dropdown').each( function() {

		var menu = jQuery(this);
		var element = jQuery(this).attr('data-element');
		var position = jQuery(this).attr('data-position');

		jQuery( element ).addClass( 'um-trigger-menu-on-' + menu.attr( 'data-trigger' ) );

		if ( jQuery(window).width() <= 1200 && element == 'div.um-profile-edit' ) {
			position = 'lc';
		}

		if ( position == 'lc' ) {

			if ( 200 > jQuery(element).find('img').width() ) {
				left_p = ( ( jQuery(element).width() - jQuery(element).find('img').width() ) / 2 ) + ( ( jQuery(element).find('img').width() - 200 ) / 2 );
			} else {
				left_p = ( ( jQuery(element).width() - jQuery(element).find('img').width() ) / 2 );
			}

			top_ = parseInt( jQuery(element).find('a').css('top') );

			if ( top_ ) {
				top_p = jQuery(element).find('img').height() + 4 + top_;
				left_gap = 4;
			} else {
				top_p = jQuery(element).find('img').height() + 4;
				left_gap = 0;
			}

			if ( top_p == 4 && element == 'div.um-cover' ) {
				top_p = jQuery(element).height() / 2 + ( menu.height() / 2 );
			} else if ( top_p == 4 ) {
				top_p = jQuery(element).height() + 20;
			}

			gap_right = jQuery(element).width() + 17;
			menu.css({
				'top' : 0,
				'width': 200,
				'left': 'auto',
				'right' : gap_right + 'px',
				'text-align' : 'center'
			});

			menu.find('.um-dropdown-arr').find('i').removeClass().addClass('um-icon-arrow-right-b');

			menu.find('.um-dropdown-arr').css({
				'top' : '4px',
				'left' : 'auto',
				'right' : '-17px'
			});

		}

		if ( position == 'bc' ) {

			if ( 200 > jQuery(element).find('img').width() ) {
				left_p = ( ( jQuery(element).width() - jQuery(element).find('img').width() ) / 2 ) + ( ( jQuery(element).find('img').width() - 200 ) / 2 );
			} else {
				left_p = ( ( jQuery(element).width() - jQuery(element).find('img').width() ) / 2 );
			}

			top_ = parseInt( jQuery(element).find('a').css('top') );

			if ( top_ ) {
				top_p = jQuery(element).find('img').height() + 4 + top_;
				left_gap = 4;
			} else {
				top_p = jQuery(element).find('img').height() + 4;
				left_gap = 0;
			}

			if ( top_p == 4 && element == 'div.um-cover' ) {
				top_p = jQuery(element).height() / 2 + ( menu.height() / 2 );
			} else if ( top_p == 4 ) {
				top_p = jQuery(element).height() + 20;
			}

			menu.css({
				'top' : top_p,
				'width': 200,
				'left': left_p + left_gap,
				'right' : 'auto',
				'text-align' : 'center'
			});

			menu.find('.um-dropdown-arr').find('i').removeClass().addClass('um-icon-arrow-up-b');

			menu.find('.um-dropdown-arr').css({
				'top' : '-17px',
				'left' : ( menu.width() / 2 ) - 12,
				'right' : 'auto'
			});

		}
	});

}