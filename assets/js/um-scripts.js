/*jQuery('body').on('error', '.um-avatar', function() {
	if( jQuery(this).data('load-error') != undefined ) return;
	jQuery(this).data('load-error', '1').attr('src', jQuery(this).data('default'));
});*/
jQuery(document).ready(function() {

    jQuery(document).on('click', '.um-dropdown a', function(e){

        return false;
    });

    jQuery(document).on('click', '.um-dropdown a.real_url', function(e){

        window.location = jQuery(this).attr('href');
    });

    jQuery(document).on('click', '.um-trigger-menu-on-click', function(e){
        jQuery('.um-dropdown').hide();
        menu = jQuery(this).find('.um-dropdown');
        menu.show();
        return false;
    });

    jQuery(document).on('click', '.um-dropdown-hide', function(e){

        UM_hide_menus();
    });

    jQuery(document).on('click', 'a.um-manual-trigger', function(){
        var child = jQuery(this).attr('data-child');
        var parent = jQuery(this).attr('data-parent');
        jQuery(this).parents( parent ).find( child ).trigger('click');
    });

    jQuery('.um-tip-n').tipsy({gravity: 'n', opacity: 1, live: 'a.live', offset: 3 });
    jQuery('.um-tip-w').tipsy({gravity: 'w', opacity: 1, live: 'a.live', offset: 3 });
    jQuery('.um-tip-e').tipsy({gravity: 'e', opacity: 1, live: 'a.live', offset: 3 });
    jQuery('.um-tip-s').tipsy({gravity: 's', opacity: 1, live: 'a.live', offset: 3 });

    jQuery(document).on('change', '.um-field-area input[type=radio]', function(){
        var field = jQuery(this).parents('.um-field-area');
        var this_field = jQuery(this).parents('label');
        field.find('.um-field-radio').removeClass('active');
        field.find('.um-field-radio').find('i').removeAttr('class').addClass('um-icon-android-radio-button-off');
        this_field.addClass('active');
        this_field.find('i').removeAttr('class').addClass('um-icon-android-radio-button-on');
    });

    jQuery(document).on('change', '.um-field-area input[type=checkbox]', function(){

        var field = jQuery(this).parents('.um-field-area');
        var this_field = jQuery(this).parents('label');
        if ( this_field.hasClass('active') ) {
            this_field.removeClass('active');
            this_field.find('i').removeAttr('class').addClass('um-icon-android-checkbox-outline-blank');
        } else {
            this_field.addClass('active');
            this_field.find('i').removeAttr('class').addClass('um-icon-android-checkbox-outline');
        }
    });

    jQuery('.um-datepicker').each(function(){
        elem = jQuery(this);

        if ( elem.attr('data-disabled_weekdays') != '' ) {
            var disable = JSON.parse( elem.attr('data-disabled_weekdays') );
        } else {
            var disable = false;
        }

        var years_n = elem.attr('data-years');

        var minRange = elem.attr('data-date_min');
        var maxRange = elem.attr('data-date_max');

        var minSplit = minRange.split(",");
        var maxSplit = maxRange.split(",");

        var min = minSplit.length ? new Date(minSplit) : null;
        var max = minSplit.length ? new Date(maxSplit) : null;

        // fix min date for safari
        if(min && min.toString() == 'Invalid Date' && minSplit.length == 3) {
            var minDateString = minSplit[1] + '/' + minSplit[2] + '/' + minSplit[0];
            min = new Date(Date.parse(minDateString));
        }

        // fix max date for safari
        if(max && max.toString() == 'Invalid Date' && maxSplit.length == 3) {
            var maxDateString = maxSplit[1] + '/' + maxSplit[2] + '/' + maxSplit[0];
            max = new Date(Date.parse(maxDateString));
        }

        elem.pickadate({
            selectYears: years_n,
            min: min,
            max: max,
            disable: disable,
            format: elem.attr('data-format'),
            formatSubmit: 'yyyy/mm/dd',
            hiddenName: true,
            onOpen: function() { elem.blur(); },
            onClose: function() { elem.blur(); }
        });
    });

    jQuery('.um-timepicker').each(function(){
        elem = jQuery(this);

        elem.pickatime({
            format: elem.attr('data-format'),
            interval: parseInt( elem.attr('data-intervals') ),
            formatSubmit: 'HH:i',
            hiddenName: true,
            onOpen: function() { elem.blur(); },
            onClose: function() { elem.blur(); }
        });
    });

    jQuery('.um-rating').um_raty({
        half: 		false,
        starType: 	'i',
        number: 	function() {return jQuery(this).attr('data-number');},
        score: 		function() {return jQuery(this).attr('data-score');},
        scoreName: 	function(){return jQuery(this).attr('data-key');},
        hints: 		false,
        click: function( score, evt ) {
            live_field = this.id;
            live_value = score;
	        um_apply_conditions( jQuery(this), false );
        }
    });

    jQuery('.um-rating-readonly').um_raty({
        half: 		false,
        starType: 	'i',
        number: 	function() {return jQuery(this).attr('data-number');},
        score: 		function() {return jQuery(this).attr('data-score');},
        scoreName: 	function(){return jQuery(this).attr('data-key');},
        hints: 		false,
        readOnly: true
    });

    jQuery(document).on('click', '.um .um-single-image-preview a.cancel', function(e){
        e.preventDefault();
        var parent = jQuery(this).parents('.um-field');
        var src = jQuery(this).parents('.um-field').find('.um-single-image-preview img').attr('src');
        parent.find('.um-single-image-preview img').attr('src','');
        parent.find('.um-single-image-preview').hide();
        parent.find('.um-btn-auto-width').html('Upload');
        parent.find('input[type=hidden]').val('empty_file');

        jQuery.ajax({
            url: wp.ajax.settings.url,
            type: 'post',
            data: {
	            action: 'um_remove_file',
                src: src
            }
        });

        return false;
    });

    jQuery(document).on('click', '.um .um-single-file-preview a.cancel', function(e){
        e.preventDefault();
        var parent = jQuery(this).parents('.um-field');
        var src = jQuery(this).parents('.um-field').find('.um-single-fileinfo a').attr('href');
        parent.find('.um-single-file-preview').hide();
        parent.find('.um-btn-auto-width').html('Upload');
        parent.find('input[type=hidden]').val('empty_file');

        jQuery.ajax({
            url: wp.ajax.settings.url,
            type: 'post',
            data: {
	            action: 'um_remove_file',
                src: src
            }
        });

        return false;
    });

    jQuery('.um-s1,.um-s2').css({'display':'block'});

    jQuery(".um-s1").select2({

        allowClear: true,
    });

    jQuery(".um-s2").select2({
        allowClear: false,
        minimumResultsForSearch: 10
    });

    jQuery(document).on('click', '.um-field-group-head:not(.disabled)', function(){
        var field = jQuery(this).parents('.um-field-group');
        var limit = field.data('max_entries');

        if ( field.find('.um-field-group-body').is(':hidden')){
            field.find('.um-field-group-body').show();
        } else {
            field.find('.um-field-group-body:first').clone().appendTo( field );
        }

        increase_id = 0;
        field.find('.um-field-group-body').each(function(){
            increase_id++;
            jQuery(this).find('input').each(function(){
                var input = jQuery(this);
                input.attr('id', input.data('key') + '-' + increase_id );
                input.attr('name', input.data('key') + '-' + increase_id );
                input.parent().parent().find('label').attr('for', input.data('key') + '-' + increase_id );
            });
        });

        if ( limit > 0 && field.find('.um-field-group-body').length == limit ) {

            jQuery(this).addClass('disabled');

        }
    });

    jQuery(document).on('click', '.um-field-group-cancel', function(e){
        e.preventDefault();
        var field = jQuery(this).parents('.um-field-group');

        var limit = field.data('max_entries');

        if ( field.find('.um-field-group-body').length > 1 ) {
            jQuery(this).parents('.um-field-group-body').remove();
        } else {
            jQuery(this).parents('.um-field-group-body').hide();
        }

        if ( limit > 0 && field.find('.um-field-group-body').length < limit ) {
            field.find('.um-field-group-head').removeClass('disabled');
        }

        return false;
    });


	jQuery( document.body ).on( 'click', '.um-ajax-paginate', function(e) {
		e.preventDefault();

		var obj = jQuery(this);
		var parent = jQuery(this).parent();
		parent.addClass( 'loading' );

		var hook = jQuery(this).data('hook');
		if ( 'um_load_posts' === hook ) {
			var pages = jQuery(this).data('pages')*1;
			var next_page = jQuery(this).data('page')*1 + 1;

			jQuery.ajax({
				url: wp.ajax.settings.url,
				type: 'post',
				data: {
					action: 'um_ajax_paginate_posts',
					author: jQuery(this).data('author'),
					page:   next_page
				},
				complete: function() {
					parent.removeClass( 'loading' );
				},
				success: function( data ) {
					parent.before( data );
					if ( next_page === pages ) {
						parent.remove();
					} else {
						obj.data( 'page', next_page );
					}
				}
			});
		} else {
			var args = jQuery(this).data('args');
			var container = jQuery(this).parents('.um').find('.um-ajax-items');

			jQuery.ajax({
				url: wp.ajax.settings.url,
				type: 'post',
				data: {
					action: 'um_ajax_paginate',
					hook: hook,
					args: args
				},
				complete: function() {
					parent.removeClass( 'loading' );
				},
				success: function(data){
					parent.remove();
					container.append( data );
				}
			});
		}
	});


    jQuery(document).on('click', '.um-ajax-action', function(e){
        e.preventDefault();
        var hook = jQuery(this).data('hook');
        var user_id = jQuery(this).data('user_id');
        var arguments = jQuery(this).data('arguments');

        if ( jQuery(this).data('js-remove') ){
            jQuery(this).parents('.'+jQuery(this).data('js-remove')).fadeOut('fast');
        }

        jQuery.ajax({
            url: wp.ajax.settings.url,
            type: 'post',
            data: {
                action: 'um_muted_action',
                hook: hook,
                user_id: user_id,
                arguments: arguments
            },
            success: function(data){

            }
        });
        return false;
    });

    jQuery(document).on('click', '#um-search-button', function() {

        jQuery(this).parents('form').submit();
    });

    jQuery('.um-form input[class=um-button][type=submit]').removeAttr('disabled');

    jQuery(document).one('click', '.um:not(.um-account) .um-form input[class=um-button][type=submit]:not(.um-has-recaptcha)', function() {
        jQuery(this).attr('disabled','disabled');
        jQuery(this).parents('form').submit();

    });


    var um_select_options_cache = {};

    /**
     * Find all select fields with parent select fields
     */
    jQuery('select[data-um-parent]').each(function(){

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
                    url: wp.ajax.settings.url,
                    type: 'post',
                    data: {
                        action: 'um_select_options',
                        parent_option_name: parent_option,
                        parent_option: parent.val(),
                        child_callback: um_ajax_source,
                        child_name:  me.attr('name'),
                        members_directory:  me.attr('data-mebers-directory'),
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

            if( parent.val() != '' && typeof um_select_options_cache[ arr_key ] == 'object'  ) {
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
    function um_field_populate_child_options( me, data, arr_key, arr_items ){


        var parent_option = me.data('um-parent');
        var child_name = me.attr('name');
        var parent_dom = jQuery('select[name="'+parent_option+'"]');
        me.find('option[value!=""]').remove();

        if( ! me.hasClass('um-child-option-disabled') ){
            me.removeAttr('disabled');
        }

        var arr_items = [],
            search_get = '';

        if ( data.post.members_directory === 'yes' ) {
            var urlParams = new URLSearchParams(window.location.search);
            search_get = urlParams.get(data.post.child_name);
        }
        jQuery.each( data.items, function(k,v){
            arr_items.push({id: k, text: v, selected: (v === search_get) });
        });

        me.select2('destroy');
        me.select2({
            data: arr_items,
            allowClear: true,
            minimumResultsForSearch: 10
        });

        if ( data.post.members_directory !== 'yes' ) {
            if( typeof data.field.default !== 'undefined' && ! me.data('um-original-value') ){
                me.val( data.field.default ).trigger('change');
            }else if( me.data('um-original-value') != '' ){
                me.val( me.data('um-original-value') ).trigger('change');
            }

            if( data.field.editable == 0 ){
                me.addClass('um-child-option-disabled');
                me.attr('disabled','disabled');
            }
        }
        um_select_options_cache[ arr_key ] = data;


    }

});
