var um_live_field;
var um_live_value;
var um_field_conditions_array = {};
var um_field_relations = {};
var um_field_added = [];
var um_field_loaded = false;
var um_field_do_init = false;

function um_field_init(){

	jQuery('.um-field[data-key]').each(function(){
		um_field_set_conditions( jQuery(this), true );
	});

	um_field_relationship();

	um_field_apply_conditions();
    um_field_loaded = true;
	
	
				
}

function um_field_set_conditions( um_field_dom, add_fade ){
	var um_field_key = um_field_dom.data('key');
	var arr_field = [];
	for (var i = 0; i < 5; i++) {

			var action0 	= um_field_dom.data('cond-'+i+'-action');
			var field0 		= um_field_dom.data('cond-'+i+'-field');
			var operator0 	= um_field_dom.data('cond-'+i+'-operator');
			var value0 		= um_field_dom.data('cond-'+i+'-value');

			if( typeof value0 !== 'undefined' && um_field_loaded == false ){
				arr_field.push({
					child: um_field_key,
					action: action0,
					field: field0,
					operator: operator0,
					value: value0
				});

			}
		
	} // end for

	if( jQuery.inArray( um_field_key, um_field_added ) <= -1 && um_field_loaded == false ){
			um_field_added.push( um_field_key );
			um_field_conditions_array[ um_field_key ] = arr_field;
	}
	
}

function um_field_apply_conditions(){
		
		var field_results = {};

		jQuery.each( um_field_added, function( i, field_key ){
			
			if( um_field_relations[ field_key ].length <= 0 ){
				um_field_relations[ field_key ] = [{
					child: field_key,
					action:'child',
					field: '',
					operator: '',
				}];
			}
			
			jQuery.each( um_field_relations[ field_key ], function( ii, conditions ){
				
				var add_fade = true;
				var action0	 = conditions.action;
				var value0 = conditions.value;
				var operator0 = conditions.operator;
				var field0 = conditions.field;
				var um_field_parent_dom = '';

				if(  action0 == 'show' ||  action0 == 'hide'  ){
					//console.log( um_field_do_init  );
				}
				um_field_parent_dom = jQuery('.um-field[data-key="'+field_key+'"]:visible').find('input[type=text],textarea,input[type=checkbox],input[type=radio],select');
				
				var um_field_data = um_get_field_data( um_field_parent_dom );
				var um_live_field = um_field_data.key;
				var um_live_value = um_field_data.value;
				
				var um_field_child_dom = jQuery('.um-field[data-key="'+conditions.child+'"]').find('input[type=text],textarea,input[type=checkbox],input[type=radio],select');
				var um_field_child_dom_hide = jQuery('.um-field[data-key="'+conditions.child+'"]');
				
				if (  action0 == 'show'  && typeof value0 !== 'undefined' ) {

					if ( operator0 == 'empty' ) {
						if ( !um_live_value || um_live_value == '' ) {
							um_field_show( um_field_child_dom, add_fade, operator0, um_live_field );
							field_results[ conditions.child ] = [ action0, true, operator0 ];
						}else{
							um_field_child_dom_hide.hide();
						}
					}

					if ( operator0 == 'not empty' ) {
						if ( um_live_value && um_live_value != '' ) {
							um_field_show( um_field_child_dom, add_fade, operator0, um_live_field );
							field_results[ conditions.child ] = [ action0, true, operator0 ];
						}else{
							um_field_child_dom_hide.hide();
						} 
					}

					if ( operator0 == 'equals to' ) {
						if ( value0 == um_live_value  ) {
							um_field_show( um_field_child_dom, add_fade, operator0, um_live_field );
							field_results[ conditions.child ] = [ action0, true, operator0 ];
						}else{
							um_field_child_dom_hide.hide();
						} 
					}

					if ( operator0 == 'not equals' ) {
						if ( jQuery.isNumeric( value0 ) && parseInt( um_live_value ) != parseInt( value0 ) && um_live_value  ) {
							um_field_show( um_field_child_dom, add_fade, operator0, um_live_field );
							field_results[ conditions.child ] = [ action0, true, operator0 ];
						} else if ( !jQuery.isNumeric( value0 ) && value0 != um_live_value  ) {
							um_field_show( um_field_child_dom, add_fade, operator0, um_live_field );
							field_results[ conditions.child ] = [ action0, true, operator0 ];
						}else{
							um_field_child_dom_hide.hide();
						}
					}

					if ( operator0 == 'greater than' ) {
						if ( jQuery.isNumeric( value0 ) && parseInt( um_live_value ) > parseInt( value0 )  ) {
							um_field_show( um_field_child_dom, add_fade, operator0, um_live_field );
							field_results[ conditions.child ] = [ action0, true, operator0 ];
						}else{
							um_field_child_dom_hide.hide();
						}
					}

					if ( operator0 == 'less than' ) {
						if ( jQuery.isNumeric( value0 ) && parseInt( um_live_value ) < parseInt( value0 ) && um_live_value  ) {
							um_field_show( um_field_child_dom, add_fade, operator0, um_live_field );
							field_results[ conditions.child ] = [ action0, true, operator0 ];
						}else{
							um_field_child_dom_hide.hide();
						} 
					}

					if ( operator0 == 'contains' ) {
						if ( um_live_value && um_live_value.indexOf( value0 ) >= 0  ) {
							um_field_show( um_field_child_dom, add_fade, operator0, um_live_field );
							field_results[ conditions.child ] = [ action0, true, operator0 ];
						}else{
							um_field_child_dom_hide.hide();
						} 
					}

				}

				if (  action0 == 'hide' && typeof value0 !== 'undefined'  ) {

					if ( operator0 == 'empty' ) {
						if ( !um_live_value || um_live_value == '' ) {
							um_field_hide( um_field_child_dom, add_fade, operator0, um_live_field );
							field_results[ conditions.child ] = [ action0, true, operator0 ];
						}else{
							um_field_child_dom_hide.show();
						} 
					}

					if ( operator0 == 'not empty' ) {
						if ( um_live_value && um_live_value != '' ) {
							um_field_hide( um_field_child_dom, add_fade, operator0, um_live_field );
							field_results[ conditions.child ] = [ action0, true, operator0 ];
						}else{
							um_field_child_dom_hide.show();
						} 
					}

					if ( operator0 == 'equals to' ) {
						if ( value0 == um_live_value ) {
							um_field_hide( um_field_child_dom, add_fade, operator0, um_live_field );
							field_results[ conditions.child ] = [ action0, true, operator0 ];
						}else{
							um_field_child_dom_hide.show();
						} 
					}

					if ( operator0 == 'not equals' ) {
						if ( jQuery.isNumeric( value0 ) && parseInt( um_live_value ) != parseInt( value0 ) && um_live_value ) {
							um_field_hide( um_field_child_dom, add_fade, operator0, um_live_field );
							field_results[ conditions.child ] = [ action0, true, operator0 ];
						} else if ( !jQuery.isNumeric( value0 ) && value0 != um_live_value ) {
							um_field_hide( um_field_child_dom, add_fade, operator0, um_live_field );
							field_results[ conditions.child ] = [ action0, true, operator0 ];
						}else{
							um_field_child_dom_hide.show();
						} 
					}

					if ( operator0 == 'greater than' ) {
						if ( jQuery.isNumeric( value0 ) && parseInt( um_live_value ) > parseInt( value0 ) ) {
							um_field_hide( um_field_child_dom, add_fade, operator0, um_live_field );
							field_results[ conditions.child ] = [ action0, true, operator0 ];
						}else{
							um_field_child_dom_hide.show();
						} 
					}

					if ( operator0 == 'less than' ) {
						if ( jQuery.isNumeric( value0 ) && parseInt( um_live_value ) < parseInt( value0 ) && um_live_value ) {
							um_field_hide( um_field_child_dom, add_fade, operator0, um_live_field );
							field_results[ conditions.child ] = [ action0, true, operator0 ];
						}else{
							um_field_child_dom_hide.show();
						} 
					}

					if ( operator0 == 'contains' ) {
						if ( um_live_value && um_live_value.indexOf( value0 ) >= 0 ) {
							um_field_hide( um_field_child_dom, add_fade, operator0, um_live_field );
							field_results[ conditions.child ] = [ action0, true, operator0 ];
						}else{
							um_field_child_dom_hide.show();
						} 
					}
				
				}
			});

		});
		
		
		// jQuery.each( field_results, function( k, options  ){

		// 		var field = jQuery('.um-field[data-key='+k+']');

		// 		if( options[0] == "show" && options[1] == true ){
		// 			field.fadeIn();
		// 		}

		// 		if( options[0] == "hide" && options[1] == true ){
		// 			 field.hide();
		// 		}

		// });
		
		// console.log( field_results );

		
}

function um_field_show( field, add_fade, optr, k ){

	field = field.parents('.um-field');

	if( field.is(':hidden') ){
		if( add_fade ){
			field.fadeIn();
		}else{
			field.show();
		}
	}
	//console.log('show um field "'+k+'" operator:'+optr );
}

function um_field_hide( field, add_fade, optr, k ){

	field = field.parents('.um-field');
	
	if( field.is(':visible') ){
		if( add_fade ){
			field.fadeOut();
		}else{
			field.hide();
		}
	}
	//console.log('hide um field "'+k+'" operator:'+optr );
}

function um_get_field_data( um_field_dom ){
	um_live_field = um_field_dom.parents('.um-field').data('key');
	um_live_value = um_field_dom.val();

	if ( um_field_dom.is(':checkbox') ) {
			if ( um_field_dom.parents('.um-field').find('input:checked').length > 1 ) {
				um_live_value = '';
				um_field_dom.parents('.um-field').find('input:checked').each(function(){
					um_live_value = um_live_value + um_field_dom.val() + ' ';
				});
			} else {
				um_live_value = um_field_dom.parents('.um-field').find('input:checked').val();
			}
	}

	if ( um_field_dom.is(':radio') ) {
			um_live_value = um_field_dom.parents('.um-field').find('input[type=radio]:checked').val();
	}

	return {
		key: um_live_field,
		value: um_live_value
	};

}

function um_field_relationship(){
		var arr_fields  = um_field_conditions_array;
		if( um_field_loaded == false ){
			jQuery.each( arr_fields, function(k, f) { 

				var new_arr_field = [];
				var arr_um_field_exists = [];

				jQuery.each( arr_fields, function(ii,field) { 
						for (var i = 0; i <= field.length; i++ ){

							if( typeof field[ i ]  !== 'undefined' ){
								if(  k == field[ i ].field  ){
									new_arr_field.push(  field[ i ] );
									arr_um_field_exists.push( field[ i ].child );
								}
							}
							

						}
				});

				um_field_relations[ k ] = new_arr_field;
			});
		}


}

function UM_check_password_matched() {
	jQuery(document).on('keyup', 'input[data-key=user_password],input[data-key=confirm_user_password]', function(e) {
		var value = jQuery('input[data-key=user_password]').val();
		var match = jQuery('input[data-key=confirm_user_password]').val();
		var field = jQuery('input[data-key=user_password],input[data-key=confirm_user_password]');

		if(!value && !match) {
			field.removeClass('um-validate-matched').removeClass('um-validate-not-matched');
		} else if(value !== match) {
			field.removeClass('um-validate-matched').addClass('um-validate-not-matched');
		} else {
			field.removeClass('um-validate-not-matched').addClass('um-validate-matched');
		}
	});
}

function UM_hide_menus() {

		menu = jQuery('.um-dropdown');
		menu.parents('div').find('a').removeClass('active');
		menu.hide();

}

function UM_domenus(){

	jQuery('.um-dropdown').each(function(){

		var menu = jQuery(this);
		var element = jQuery(this).attr('data-element');
		var position = jQuery(this).attr('data-position');

		jQuery(element).addClass('um-trigger-menu-on-'+menu.attr('data-trigger'));

		if ( jQuery(window).width() <= 1200 && element == 'div.um-profile-edit' ) {
			position = 'lc';
		}

		if ( position == 'lc' ){

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

		if ( position == 'bc' ){

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

function um_responsive(){

	jQuery('.um').each(function(){

		element_width = jQuery(this).width();

		if ( element_width <= 340 ) {

			jQuery(this).removeClass('uimob340');
			jQuery(this).removeClass('uimob500');
			jQuery(this).removeClass('uimob800');
			jQuery(this).removeClass('uimob960');

			jQuery(this).addClass('uimob340');

		} else if ( element_width <= 500 ) {

			jQuery(this).removeClass('uimob340');
			jQuery(this).removeClass('uimob500');
			jQuery(this).removeClass('uimob800');
			jQuery(this).removeClass('uimob960');

			jQuery(this).addClass('uimob500');

		} else if ( element_width <= 800 ) {

			jQuery(this).removeClass('uimob340');
			jQuery(this).removeClass('uimob500');
			jQuery(this).removeClass('uimob800');
			jQuery(this).removeClass('uimob960');

			jQuery(this).addClass('uimob800');

		} else if ( element_width <= 960 ) {

			jQuery(this).removeClass('uimob340');
			jQuery(this).removeClass('uimob500');
			jQuery(this).removeClass('uimob800');
			jQuery(this).removeClass('uimob960');

			jQuery(this).addClass('uimob960');

		} else if ( element_width > 960 ) {

			jQuery(this).removeClass('uimob340');
			jQuery(this).removeClass('uimob500');
			jQuery(this).removeClass('uimob800');
			jQuery(this).removeClass('uimob960');

		}

		if (  jQuery('.um-account-nav').length > 0 && jQuery('.um-account-side').is(':visible') && jQuery('.um-account-tab:visible').length == 0 ) {
			jQuery('.um-account-side li a.current').trigger('click');
		}

		jQuery(this).css('opacity',1);

	});

	jQuery('.um-cover, .um-member-cover').each(function(){

		var elem = jQuery(this);
		var ratio = elem.data('ratio');
		var width = elem.width();
		var ratios = ratio.split(':');

		calcHeight = Math.round( width / ratios[0] ) + 'px';
		elem.height( calcHeight );
		elem.find('.um-cover-add').height( calcHeight );

	});

	jQuery('.um-members').each(function(){
		UM_Member_Grid( jQuery(this) );
	});

	UM_domenus();

}

function UM_Member_Grid( container ) {
	container.masonry({
		itemSelector: '.um-member',
		columnWidth: '.um-member',
		gutter: '.um-gutter-sizer'
	});

}

function initImageUpload_UM( trigger ) {

		if (trigger.data('upload_help_text')){
			upload_help_text = '<span class="help">' + trigger.data('upload_help_text') + '</span>';
		} else {
			upload_help_text = '';
		}

		if ( trigger.data('icon') ) {
			icon = '<span class="icon"><i class="'+ trigger.data('icon') + '"></i></span>';
		} else {
			icon = '';
		}

		if ( trigger.data('upload_text') ) {
			upload_text = '<span class="str">' + trigger.data('upload_text') + '</span>';
		} else {
			upload_text = '';
		}

		trigger.uploadFile({
			url: um_scripts.imageupload,
			method: "POST",
			multiple: false,
			formData: {
				key: trigger.data('key'), 
				set_id: trigger.data('set_id'), 
				set_mode: trigger.data('set_mode'),
				_wpnonce: trigger.data('nonce'),
				timestamp: trigger.data('timestamp')
			 },
			fileName: trigger.data('key'),
			allowedTypes: trigger.data('allowed_types'),
			maxFileSize: trigger.data('max_size'),
			dragDropStr: icon + upload_text + upload_help_text,
			sizeErrorStr: trigger.data('max_size_error'),
			extErrorStr: trigger.data('extension_error'),
			maxFileCountErrorStr: trigger.data('max_files_error'),
			maxFileCount: 1,
			showDelete: false,
			showAbort: false,
			showDone: false,
			showFileCounter: false,
			showStatusAfterSuccess: true,
			onSubmit:function(files){

				trigger.parents('.um-modal-body').find('.um-error-block').remove();

			},
			onSuccess:function(files,data,xhr){

				trigger.selectedFiles = 0;

				try{
				data = jQuery.parseJSON(data);
				} catch (e) {
				   console.log( e, data );
				    return;
				}

				if (data.error && data.error != '') {

					trigger.parents('.um-modal-body').append('<div class="um-error-block">'+data.error+'</div>');
					trigger.parents('.um-modal-body').find('.upload-statusbar').hide(0);
					um_modal_responsive();

				} else {

					jQuery.each( data, function(key, value) {

						var img_id = trigger.parents('.um-modal-body').find('.um-single-image-preview img');
						var img_id_h = trigger.parents('.um-modal-body').find('.um-single-image-preview');

						img_id.attr("src", value);
						img_id.load(function(){

							trigger.parents('.um-modal-body').find('.um-modal-btn.um-finish-upload.disabled').removeClass('disabled');
							trigger.parents('.um-modal-body').find('.ajax-upload-dragdrop,.upload-statusbar').hide(0);
							img_id_h.show(0);
							um_modal_responsive();

						});

					});

				}

			}
		});

}

function initFileUpload_UM( trigger ) {

		if (trigger.data('upload_help_text')){
			upload_help_text = '<span class="help">' + trigger.data('upload_help_text') + '</span>';
		} else {
			upload_help_text = '';
		}

		if ( trigger.data('icon') ) {
			icon = '<span class="icon"><i class="'+ trigger.data('icon') + '"></i></span>';
		} else {
			icon = '';
		}

		if ( trigger.data('upload_text') ) {
			upload_text = '<span class="str">' + trigger.data('upload_text') + '</span>';
		} else {
			upload_text = '';
		}

		trigger.uploadFile({
			url: um_scripts.fileupload,
			method: "POST",
			multiple: false,
			formData: {
				key: trigger.data('key'), 
				set_id: trigger.data('set_id'), 
				set_mode: trigger.data('set_mode'),
				_wpnonce: trigger.data('nonce'),
				timestamp: trigger.data('timestamp')
			},
			fileName: trigger.data('key'),
			allowedTypes: trigger.data('allowed_types'),
			maxFileSize: trigger.data('max_size'),
			dragDropStr: icon + upload_text + upload_help_text,
			sizeErrorStr: trigger.data('max_size_error'),
			extErrorStr: trigger.data('extension_error'),
			maxFileCountErrorStr: trigger.data('max_files_error'),
			maxFileCount: 1,
			showDelete: false,
			showAbort: false,
			showDone: false,
			showFileCounter: false,
			showStatusAfterSuccess: true,
			onSubmit:function(files){

				trigger.parents('.um-modal-body').find('.um-error-block').remove();

			},
			onSuccess:function(files,data,xhr){

				trigger.selectedFiles = 0;

				data = jQuery.parseJSON(data);
				if (data.error && data.error != '') {

					trigger.parents('.um-modal-body').append('<div class="um-error-block">'+data.error+'</div>');
					trigger.parents('.um-modal-body').find('.upload-statusbar').hide(0);
					
					setTimeout(function(){
						um_modal_responsive();
					},1000);

				} else {

					jQuery.each( data, function(key, value) {

						trigger.parents('.um-modal-body').find('.um-modal-btn.um-finish-upload.disabled').removeClass('disabled');
						trigger.parents('.um-modal-body').find('.ajax-upload-dragdrop,.upload-statusbar').hide(0);
						trigger.parents('.um-modal-body').find('.um-single-file-preview').show(0);

						if (key == 'icon') {
							trigger.parents('.um-modal-body').find('.um-single-fileinfo i').removeClass().addClass(value);
						} else if ( key == 'icon_bg' ) {
							trigger.parents('.um-modal-body').find('.um-single-fileinfo span.icon').css({'background-color' : value } );
						} else if ( key == 'filename' ) {
							trigger.parents('.um-modal-body').find('.um-single-fileinfo span.filename').html(value);
						} else {
							trigger.parents('.um-modal-body').find('.um-single-fileinfo a').attr('href', value);
						}

					});

					setTimeout(function(){
						um_modal_responsive();
					},1000);

				}

			}
		});

}

function initCrop_UM() {

	var target_img = jQuery('.um-modal .um-single-image-preview img').first();
	var target_img_parent = jQuery('.um-modal .um-single-image-preview');

	var crop_data = target_img.parent().attr('data-crop');
	var min_width = target_img.parent().attr('data-min_width');
	var min_height = target_img.parent().attr('data-min_height');
	var ratio = target_img.parent().attr('data-ratio');

	if ( jQuery('.um-modal').find('#um_upload_single').attr('data-ratio') ) {
		var ratio =  jQuery('.um-modal').find('#um_upload_single').attr('data-ratio');
		var ratio_split = ratio.split(':');
		var ratio = ratio_split[0];
	}

	if ( target_img.length ) {

		if ( target_img.attr('src') != '' ) {

			var max_height = jQuery(window).height() - ( jQuery('.um-modal-footer a').height() + 20 ) - 50 - ( jQuery('.um-modal-header:visible').height() );
			target_img.css({'height' : 'auto'});
			target_img_parent.css({'height' : 'auto'});
			if ( jQuery(window).height() <= 400 ) {
				target_img_parent.css({ 'height': max_height +'px', 'max-height' : max_height + 'px' });
				target_img.css({ 'height' : 'auto' });
			} else {
				target_img.css({ 'height': 'auto', 'max-height' : max_height + 'px' });
				target_img_parent.css({ 'height': target_img.height(), 'max-height' : max_height + 'px' });
			}

			if ( crop_data == 'square' ) {

				var opts = {
					minWidth: min_width,
					minHeight: min_height,
					dragCrop: false,
					aspectRatio: 1.0,
					zoomable: false,
					rotatable: false,
					dashed: false,
					done: function(data) {
						target_img.parent().attr('data-coord', Math.round(data.x) + ',' + Math.round(data.y) + ',' + Math.round(data.width) + ',' + Math.round(data.height) );
					}
				};

			} else if ( crop_data == 'cover' ) {
				if( Math.round( min_width / ratio ) > 0 ){
					min_height = Math.round( min_width / ratio )
				}
				var opts = {
					minWidth: min_width,
					minHeight: min_height,
					dragCrop: false,
					aspectRatio: ratio,
					zoomable: false,
					rotatable: false,
					dashed: false,
					done: function(data) {
						target_img.parent().attr('data-coord', Math.round(data.x) + ',' + Math.round(data.y) + ',' + Math.round(data.width) + ',' + Math.round(data.height) );
					}
				};

			} else if ( crop_data == 'user' ) {

				var opts = {
					minWidth: min_width,
					minHeight: min_height,
					dragCrop: true,
					aspectRatio: "auto",
					zoomable: false,
					rotatable: false,
					dashed: false,
					done: function(data) {
						target_img.parent().attr('data-coord', Math.round(data.x) + ',' + Math.round(data.y) + ',' + Math.round(data.width) + ',' + Math.round(data.height) );
					}
				};

			}

			if ( crop_data != 0 ) {
					target_img.cropper( opts );
					jQuery('.um-single-image-preview img.cropper-hidden').cropper('destroy');
					jQuery('.um-single-image-preview img.lazyloaded').addClass('cropper-hidden');
					jQuery('.um-single-image-preview img.lazyloaded').removeClass('lazyloaded');
					jQuery('.um-single-image-preview .cropper-container').append('<div class="um-clear"></div>');
			}

		}
	}

}

function um_new_modal( id, size, isPhoto, source ){

	var modal = jQuery('body').find('.um-modal-overlay');

	if ( modal.length == 0 ) {

	jQuery('.tipsy').hide();

	UM_hide_menus();

	jQuery('body,html,textarea').css("overflow", "hidden");

	jQuery(document).bind("touchmove", function(e){e.preventDefault();});
	jQuery('.um-modal').on('touchmove', function(e){e.stopPropagation();});

	if ( isPhoto ) {
	jQuery('body').append('<div class="um-modal-overlay"></div><div class="um-modal is-photo"></div>');
	} else {
	jQuery('body').append('<div class="um-modal-overlay"></div><div class="um-modal no-photo"></div>');
	}

	jQuery('#' + id).prependTo('.um-modal');

	if ( isPhoto ) {

		jQuery('.um-modal').find('.um-modal-photo').html('<img />');

		var photo_ = jQuery('.um-modal-photo img');
		var photo_maxw = jQuery(window).width() - 60;
		var photo_maxh = jQuery(window).height() - ( jQuery(window).height() * 0.25 );

		photo_.attr("src", source);
		photo_.load(function(){

			jQuery('#' + id).show();
			jQuery('.um-modal').show();

			photo_.css({'opacity': 0});
			photo_.css({'max-width': photo_maxw });
			photo_.css({'max-height': photo_maxh });

			jQuery('.um-modal').css({
				'width': photo_.width(),
				'margin-left': '-' + photo_.width() / 2 + 'px'
			});

			photo_.animate({'opacity' : 1}, 1000);

			um_modal_responsive();

		});

	} else {

		jQuery('#' + id).show();
		jQuery('.um-modal').show();

		um_modal_size( size );

		initImageUpload_UM( jQuery('.um-modal:visible').find('.um-single-image-upload') );
		initFileUpload_UM( jQuery('.um-modal:visible').find('.um-single-file-upload') );

		um_modal_responsive();

	}

	}

}

function um_modal_responsive() {

	var modal = jQuery('.um-modal:visible');
	var photo_modal = jQuery('.um-modal-body.photo:visible');

	if ( photo_modal.length ) {

		modal.removeClass('uimob340');
		modal.removeClass('uimob500');

		var photo_ = jQuery('.um-modal-photo img');
		var photo_maxw = jQuery(window).width() - 60;
		var photo_maxh = jQuery(window).height() - ( jQuery(window).height() * 0.25 );

		photo_.css({'opacity': 0});
		photo_.css({'max-width': photo_maxw });
		photo_.css({'max-height': photo_maxh });

		jQuery('.um-modal').css({
			'width': photo_.width(),
			'margin-left': '-' + photo_.width() / 2 + 'px'
		});

		photo_.animate({'opacity' : 1}, 1000);

		var half_gap = ( jQuery(window).height() - modal.innerHeight() ) / 2 + 'px';
		modal.animate({ 'bottom' : half_gap }, 300);

	} else if ( modal.length ) {

		var element_width = jQuery(window).width();

		modal.removeClass('uimob340');
		modal.removeClass('uimob500');

		if ( element_width <= 340 ) {

			modal.addClass('uimob340');
			initCrop_UM();
			modal.animate({ 'bottom' : 0 }, 300);

		} else if ( element_width <= 500 ) {

			modal.addClass('uimob500');
			initCrop_UM();
			modal.animate({ 'bottom' : 0 }, 300);

		} else if ( element_width <= 800 ) {

			initCrop_UM();
			var half_gap = ( jQuery(window).height() - modal.innerHeight() ) / 2 + 'px';
			modal.animate({ 'bottom' : half_gap }, 300);

		} else if ( element_width <= 960 ) {

			initCrop_UM();
			var half_gap = ( jQuery(window).height() - modal.innerHeight() ) / 2 + 'px';
			modal.animate({ 'bottom' : half_gap }, 300);

		} else if ( element_width > 960 ) {

			initCrop_UM();
			var half_gap = ( jQuery(window).height() - modal.innerHeight() ) / 2 + 'px';
			modal.animate({ 'bottom' : half_gap }, 300);

		}

	}

}

function um_remove_modal(){

	jQuery('img.cropper-hidden').cropper('destroy');

	jQuery('body,html,textarea').css("overflow", "auto");

	jQuery(document).unbind('touchmove');

	jQuery('.um-modal div[id^="um_"]').hide().appendTo('body');
	jQuery('.um-modal,.um-modal-overlay').remove();

}

function um_modal_size( aclass ) {
	
	jQuery('.um-modal:visible').addClass(aclass);

}

function um_modal_add_attr( id, value ) {
	
	jQuery('.um-modal:visible').data( id, value );

}

function prepare_Modal() {
	if ( jQuery('.um-popup-overlay').length == 0 ) {
		jQuery('body').append('<div class="um-popup-overlay"></div>');
		jQuery('body').append('<div class="um-popup"></div>');
		jQuery('.um-popup').addClass('loading');
		jQuery("body,html").css({ overflow: 'hidden' });
	}
}

function remove_Modal() {
	if ( jQuery('.um-popup-overlay').length ) {
		jQuery('.tipsy').remove();
		jQuery('.um-popup').empty().remove();
		jQuery('.um-popup-overlay').empty().remove();
		jQuery("body,html").css({ overflow: 'auto' });
	}
}

function show_Modal( contents ) {
	if ( jQuery('.um-popup-overlay').length ) {
		jQuery('.um-popup').removeClass('loading').html( contents );
		jQuery('.um-tip-n').tipsy({gravity: 'n', opacity: 1, offset: 3 });
		jQuery('.um-tip-w').tipsy({gravity: 'w', opacity: 1, offset: 3 });
		jQuery('.um-tip-e').tipsy({gravity: 'e', opacity: 1, offset: 3 });
		jQuery('.um-tip-s').tipsy({gravity: 's', opacity: 1, offset: 3 });
	}
}

function responsive_Modal() {
	if ( jQuery('.um-popup-overlay').length ) {

		ag_height = jQuery(window).height() - jQuery('.um-popup um-popup-header').outerHeight() - jQuery('.um-popup .um-popup-footer').outerHeight() - 80;
		if ( ag_height > 350 ) {
			ag_height = 350;
		}

		if ( jQuery('.um-popup-autogrow:visible').length ) {

			jQuery('.um-popup-autogrow:visible').css({'height': ag_height + 'px'});
			jQuery('.um-popup-autogrow:visible').mCustomScrollbar({ theme:"dark-3", mouseWheelPixels:500 }).mCustomScrollbar("scrollTo", "bottom",{ scrollInertia:0} );

		} else if ( jQuery('.um-popup-autogrow2:visible').length ) {

			jQuery('.um-popup-autogrow2:visible').css({'max-height': ag_height + 'px'});
			jQuery('.um-popup-autogrow2:visible').mCustomScrollbar({ theme:"dark-3", mouseWheelPixels:500 });

		}
	}
}

function um_reset_field( dOm ){
	//console.log(dOm);
	jQuery(dOm)
	 .find('div.um-field-area')
	 .find('input,textarea,select')
	 .not(':button, :submit, :reset, :hidden')
	 .val('')
	 .removeAttr('checked')
	 .removeAttr('selected');
}

jQuery(function(){

	// Submit search form on keypress 'Enter'
	jQuery(".um-search form *").keypress(function(e){
			 if (e.which == 13) {
			    jQuery('.um-search form').submit();
			    return false;
			  }
	});

	if( jQuery('input[data-key=user_password],input[data-key=confirm_user_password]').length == 2 ) {
		UM_check_password_matched();
	}

});

