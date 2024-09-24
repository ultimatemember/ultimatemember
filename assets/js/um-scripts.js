function um_sanitize_value( value, el ) {
	var element = document.createElement( 'div' );
	element.innerText = value;
	var sanitized_value = element.innerHTML;
	if ( el ) {
		jQuery( el ).val( sanitized_value );
	}

	return sanitized_value;
}

function um_unsanitize_value( input ) {
	var e = document.createElement( 'textarea' );
	e.innerHTML = input;
	// handle case of empty input
	return e.childNodes.length === 0 ? "" : e.childNodes[0].nodeValue;
}

jQuery(document).ready(function() {

	jQuery( document.body ).on('click', '.um-dropdown a.real_url', function() {
		window.location = jQuery(this).attr('href');
	});

	jQuery( document.body ).on( 'click', '.um-trigger-menu-on-click', function() {
		var menu = jQuery(this).find('.um-dropdown');
		UM.dropdown.show( menu );
		return false;
	});

	jQuery( document.body ).on('click', '.um-dropdown-hide', function() {
		UM.dropdown.hideAll();
		return false;
	});

	jQuery( document.body ).on('click', 'a.um-manual-trigger', function() {
		var child = jQuery(this).attr('data-child');
		var parent = jQuery(this).attr('data-parent');
		jQuery(this).parents( parent ).find( child ).trigger('click');
		UM.dropdown.hideAll();
		return false;
	});

	jQuery('.um-s1,.um-s2').css({'display':'block'});

	/**
	 * Unselect empty option if something is selected
	 *
	 * @since   2.1.16
	 * @param   {object} e
	 * @returns {undefined}
	 */
	function unselectEmptyOption( e ) {
		var $element = jQuery( e.currentTarget );
		var $selected = $element.find(':selected');

		if ( $selected.length > 1 ) {
			$selected.each( function ( i, option ) {
				if ( option.value === '' ) {
					option.selected = false;
					$element.trigger( 'change' );
				}
			});
		}
	}

	if ( typeof( jQuery.fn.select2 ) === 'function' ) {
		jQuery(".um-s1").each( function( e ) {
			var obj = jQuery(this);

			obj.select2({
				allowClear: true,
				dropdownParent: obj.parent()
			}).on( 'change', unselectEmptyOption );
		} );

		jQuery(".um-s2").each( function( e ) {
			var obj = jQuery(this);

			// fix https://github.com/ultimatemember/ultimatemember/issues/941
			// using .um-custom-shortcode-tab class as temporarily solution
			var atts = {};
			if ( obj.parents('.um-custom-shortcode-tab').length ) {
				atts = {
					allowClear: false
				};
			} else {
				atts = {
					allowClear: false,
					minimumResultsForSearch: 10,
					dropdownParent: obj.parent()
				};
			}
			obj.select2( atts ).on( 'change', unselectEmptyOption );
		} );

		jQuery(".um-s3").each( function( e ) {
			var obj = jQuery(this);

			obj.select2({
				allowClear: false,
				minimumResultsForSearch: -1,
				dropdownParent: obj.parent()
			}).on( 'change', unselectEmptyOption );
		} );
	}

	if ( typeof( jQuery.fn.um_raty ) === 'function' ) {
		jQuery('.um-rating').um_raty({
			half:       false,
			starType:   'i',
			number:     function() {
				return jQuery(this).attr('data-number');
			},
			score:      function() {
				return jQuery(this).attr('data-score');
			},
			scoreName:  function() {
				return jQuery(this).attr('data-key');
			},
			hints:      false,
			click:      function( score, evt ) {
				um_live_field = this.id;
				um_live_value = score;
				um_apply_conditions( jQuery(this), false );
			}
		});

		jQuery('.um-rating-readonly').um_raty({
			half:       false,
			starType:   'i',
			number:     function() {
				return jQuery(this).attr('data-number');
			},
			score:      function() {
				return jQuery(this).attr('data-score');
			},
			scoreName:  function() {
				return jQuery(this).attr('data-key');
			},
			hints:      false,
			readOnly:   true
		});
	}

	jQuery(document).on('change', '.um-field-area input[type="radio"]', function() {
		var field = jQuery(this).parents('.um-field-area');
		var this_field = jQuery(this).parents('label');
		field.find('.um-field-radio').removeClass('active');
		field.find('.um-field-radio').find('i').removeAttr('class').addClass('um-icon-android-radio-button-off');
		this_field.addClass('active');
		this_field.find('i').removeAttr('class').addClass('um-icon-android-radio-button-on');
	});

	jQuery(document).on('change', '.um-field-area input[type="checkbox"]', function() {
		var this_field = jQuery(this).parents('label');
		if ( this_field.hasClass('active') ) {
			this_field.removeClass('active');
			this_field.find('i').removeAttr('class').addClass('um-icon-android-checkbox-outline-blank');
		} else {
			this_field.addClass('active');
			this_field.find('i').removeAttr('class').addClass('um-icon-android-checkbox-outline');
		}
	});

	jQuery(document.body).on('click', '.um-single-image-preview a.cancel', function(e) {
		e.preventDefault();

		let isModal = false;
		if ( jQuery(this).parents('.um-modal-body').length > 0 ) {
			isModal = true;
		}

		let parent, mode, src, args;

		if ( ! isModal ) {
			parent = jQuery(this).parents('.um-field');
			mode   = parent.data('mode');
			src    = parent.find('.um-single-image-preview img').attr('src');

			let filename = parent.find( 'input[type="hidden"]#' + parent.data('key') + '-' + jQuery(this).parents('form').find('input[type="hidden"][name="form_id"]').val() ).val();

			args = {
				data: {
					mode: mode,
					filename: filename,
					src: src,
					nonce: um_scripts.nonce
				},
				success: function() {
					parent.find('.um-single-image-preview img').replaceWith('<img src="" alt="" />');
					parent.find('.um-single-image-preview').removeAttr('style').hide();
					parent.find('.um-btn-auto-width').html( parent.data('upload-label') );
					parent.find('input[type="hidden"]').val( 'empty_file' );
				}
			};

			if ( mode !== 'register' ) {
				args.data.user_id = jQuery(this).parents('form').find('#user_id').val();
			}
		} else {
			parent = jQuery(this).parents('.um-modal-body');
			mode   = parent.find('.um-single-image-upload').data('set_mode');
			src    = parent.find('.um-single-image-preview img').attr('src');

			args = {
				data: {
					src: src,
					mode: mode,
					nonce: um_scripts.nonce
				},
				success: function() {
					wp.hooks.doAction( 'um_after_removing_preview' );

					parent.find('.um-single-image-preview img').replaceWith('<img src="" alt="" />'); // required replaceWith for flushing DOM before re-init Cropper.
					parent.find('.um-single-image-preview').removeAttr('style').hide();
					parent.find('.ajax-upload-dragdrop').show();
					parent.find('.um-modal-btn.um-finish-upload').addClass( 'disabled' );

					um_modal_responsive();
				}
			};
		}

		wp.ajax.send( 'um_remove_file', args );
	});

	jQuery(document.body).on('click', '.um-single-file-preview a.cancel', function(e) {
		e.preventDefault();

		let isModal = false;
		if ( jQuery(this).parents('.um-modal-body').length > 0 ) {
			isModal = true;
		}

		let parent, mode, src, args;

		if ( ! isModal ) {
			parent = jQuery(this).parents('.um-field');
			src    = parent.find('.um-single-fileinfo a').attr('href');
			mode   = parent.data('mode');

			let filename = parent.find( 'input[type="hidden"]#' + parent.data('key') + '-' + jQuery(this).parents('form').find('input[type="hidden"][name="form_id"]').val() ).val();

			args = {
				data: {
					mode: mode,
					filename: filename,
					src: src,
					nonce: um_scripts.nonce
				},
				success: function() {
					parent.find('.um-single-file-preview').hide();
					parent.find('.um-btn-auto-width').html( parent.data('upload-label') );
					parent.find('input[type=hidden]').val( 'empty_file' );
				}
			};

			if ( mode !== 'register' ) {
				args.data.user_id = jQuery(this).parents('form' ).find( '#user_id' ).val();
			}
		} else {
			parent = jQuery(this).parents('.um-modal-body');
			src    = parent.find('.um-single-fileinfo a').attr('href');
			mode   = parent.find('.um-single-file-upload').data('set_mode');

			args = {
				data: {
					src: src,
					mode: mode,
					nonce: um_scripts.nonce
				},
				success: function() {
					parent.find('.um-single-file-preview').hide();
					parent.find('.ajax-upload-dragdrop').show();
					parent.find('.um-modal-btn.um-finish-upload').addClass('disabled');
					um_modal_responsive();
				}
			};
		}
		wp.ajax.send( 'um_remove_file', args );
	});

	// @todo deprecate
	jQuery(document).on('click', '.um-field-group-head:not(.disabled)', function() {
		var field = jQuery(this).parents('.um-field-group');
		var limit = field.data('max_entries');

		if ( field.find('.um-field-group-body').is(':hidden')){
			field.find('.um-field-group-body').show();
		} else {
			field.find('.um-field-group-body:first').clone().appendTo( field );
		}

		var increase_id = 0;
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
	// @todo deprecate
	jQuery(document).on('click', '.um-field-group-cancel', function( e ) {
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


	jQuery( document.body ).on( 'click', '.um-ajax-paginate', function( e ) {
		e.preventDefault();

		var obj = jQuery(this);
		var parent = obj.parent();
		parent.addClass( 'loading' );

		var pages = obj.data('pages')*1;
		var next_page = obj.data('page')*1 + 1;

		var hook = obj.data('hook');

		if ( 'um_load_posts' === hook ) {

			jQuery.ajax({
				url: wp.ajax.settings.url,
				type: 'post',
				data: {
					action: 'um_ajax_paginate_posts',
					author: jQuery(this).data('author'),
					page:   next_page,
					nonce: um_scripts.nonce
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
		} else if ( 'um_load_comments' === hook ) {

			jQuery.ajax({
				url: wp.ajax.settings.url,
				type: 'post',
				data: {
					action: 'um_ajax_paginate_comments',
					user_id: jQuery(this).data('user_id'),
					page: next_page,
					nonce: um_scripts.nonce
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
			var container = jQuery(this).parents('.um.um-profile.um-viewing').find('.um-ajax-items');

			jQuery.ajax({
				url: wp.ajax.settings.url,
				type: 'post',
				data: {
					action: 'um_ajax_paginate',
					hook: hook,
					args: args,
					nonce: um_scripts.nonce
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


	jQuery(document).on('click', '.um-ajax-action', function( e ) {
		e.preventDefault();
		var hook = jQuery(this).data('hook');
		var user_id = jQuery(this).data('user_id');
		var args = jQuery(this).data('args');

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
				arguments: args,
				nonce: um_scripts.nonce
			},
			success: function(data){

			}
		});
		return false;
	});

	jQuery( document.body ).on('click', '#um-search-button', function() {
		var action = jQuery(this).parents('.um-search-form').data('members_page');

		var search_keys = [];
		jQuery(this).parents('.um-search-form').find('input[name="um-search-keys[]"]').each( function() {
			search_keys.push( jQuery(this).val() );
		});

		var search = jQuery(this).parents('.um-search-form').find('.um-search-field').val();

		var url;
		if ( search === '' ) {
			url = action;
		} else {
			var query = '?';
			for ( var i = 0; i < search_keys.length; i++ ) {
				query += search_keys[i] + '=' + search;
				if ( i !== search_keys.length - 1 ) {
					query += '&';
				}
			}

			url = action + query;
		}
		window.location = url;
	});

	//make search on Enter click
	jQuery( document.body ).on( 'keypress', '.um-search-field', function(e) {
		if ( e.which === 13 ) {
			var action = jQuery(this).parents('.um-search-form').data('members_page');

			var search_keys = [];
			jQuery(this).parents('.um-search-form').find('input[name="um-search-keys[]"]').each( function() {
				search_keys.push( jQuery(this).val() );
			});

			var search = jQuery(this).val();

			var url;
			if ( search === '' ) {
				url = action;
			} else {
				var query = '?';
				for ( var i = 0; i < search_keys.length; i++ ) {
					query += search_keys[i] + '=' + search;
					if ( i !== search_keys.length - 1 ) {
						query += '&';
					}
				}

				url = action + query;
			}
			window.location = url;
		}
	});

	jQuery('.um-form input[class="um-button"][type="submit"]').prop('disabled', false);

	jQuery(document).one('click', '.um:not(.um-account) .um-form input[class="um-button"][type="submit"]:not(.um-has-recaptcha)', function() {
		jQuery(this).attr('disabled','disabled');
		jQuery(this).parents('form').trigger('submit');
	});


	var um_select_options_cache = {};

	/**
	 * Find all select fields with parent select fields
	 */
	jQuery('select[data-um-parent]').each( function() {

		var me = jQuery(this);
		var parent_option = me.data('um-parent');
		var um_ajax_source = me.data('um-ajax-source');

		me.attr('data-um-init-field', true );

		jQuery(document).on('change','select[name="' + parent_option + '"]',function() {
			var parent  = jQuery(this);
			var form_id = parent.closest( 'form' ).find( 'input[type="hidden"][name="form_id"]' ).val();

			var arr_key;
			if ( me.attr( 'data-member-directory' ) === 'yes' ) {
				var directory = parent.parents('.um-directory');
				arr_key = um_get_data_for_directory( directory, 'filter_' + parent_option );
				if ( typeof arr_key != 'undefined' ) {
					arr_key = arr_key.split('||');
				} else {
					arr_key = '';
				}
			} else {
				arr_key = parent.val();
			}

			if ( typeof arr_key != 'undefined' && arr_key !== '' && typeof um_select_options_cache[ arr_key ] !== 'object' ) {

				if ( typeof( me.um_wait ) === 'undefined' || me.um_wait === false ) {
					me.um_wait = true;
				} else {
					return;
				}

				jQuery.ajax({
					url: wp.ajax.settings.url,
					type: 'post',
					data: {
						action: 'um_select_options',
						parent_option_name: parent_option,
						parent_option: arr_key,
						child_callback: um_ajax_source,
						child_name: me.attr('name'),
						members_directory: me.attr('data-member-directory'),
						form_id: form_id,
						nonce: um_scripts.nonce
					},
					success: function( data ) {
						if ( data.status === 'success' && arr_key !== '' ) {
							um_select_options_cache[ arr_key ] = data;
							um_field_populate_child_options( me, data, arr_key );
						}

						if ( typeof data.debug !== 'undefined' ) {
							console.log( data );
						}

						me.um_wait = false;
					},
					error: function( e ) {
						console.log( e );
						me.um_wait = false;
					}
				});

			}

			if ( typeof arr_key != 'undefined' && arr_key !== '' && typeof um_select_options_cache[ arr_key ] == 'object' ) {
				setTimeout( um_field_populate_child_options, 10, me, um_select_options_cache[ arr_key ], arr_key );
			}

			if ( typeof arr_key != 'undefined' || arr_key === '' ) {
				me.find('option[value!=""]').remove();
				me.val('').trigger('change');
			}

		});

		jQuery('select[name="' + parent_option + '"]').trigger('change');

	});


	/**
	 * Populates child options and cache ajax response
	 *
	 * @param me
	 * @param data
	 * @param arr_key
	 */
	function um_field_populate_child_options( me, data, arr_key ) {
		var directory = me.parents('.um-directory');
		var child_name = me.attr('name');
		me.find('option[value!=""]').remove();

		if ( ! me.hasClass('um-child-option-disabled') ) {
			me.prop('disabled', false);
		}

		var arr_items = [],
			search_get = '';

		if ( data.post.members_directory === 'yes' ) {
			arr_items.push({id: '', text: '', selected: 1});
		}
		jQuery.each( data.items, function(k,v){
			arr_items.push({id: k, text: v, selected: (v === search_get)});
		});

		me.select2('destroy');
		if ( me.hasClass( 'um-s1' ) ) {
			me.select2({
				data: arr_items,
				allowClear: true,
				dropdownParent: me.parent()
			});
		} else if ( me.hasClass( 'um-s2' ) ) {
			me.select2({
				data: arr_items,
				allowClear: true,
				minimumResultsForSearch: 10,
				dropdownParent: me.parent()
			});
		}



		if ( data.post.members_directory === 'yes' ) {
			me.find('option').each( function() {
				if ( jQuery(this).html() !== '' ) {
					jQuery(this).data( 'value_label', jQuery(this).html() ).attr( 'data-value_label', jQuery(this).html() );
				}
			});

			var current_filter_val = um_get_data_for_directory( directory, 'filter_' + child_name );
			if ( typeof current_filter_val !== 'undefined' ) {
				current_filter_val = current_filter_val.split('||');

				var temp_filter_val = [];
				jQuery.each( current_filter_val, function(i) {
					if ( me.find('option[value="' + current_filter_val[ i ] + '"]').length ) {
						temp_filter_val.push( current_filter_val[ i ] );
					}
					me.find('option[value="' + current_filter_val[ i ] + '"]').prop('disabled', true).hide();
					if ( me.find('option:not(:disabled)').length === 1 ) {
						me.prop('disabled', true);
					}

					me.select2('destroy').select2();
					me.val('').trigger( 'change' );
				});

				temp_filter_val = temp_filter_val.join('||');
				if ( current_filter_val !== temp_filter_val ) {
					um_set_url_from_data( directory, 'filter_' + child_name, temp_filter_val );
					um_ajax_get_members( directory );
				}
			}

			um_change_tag( directory );
		}

		if ( data.post.members_directory !== 'yes' ) {
			if ( typeof data.field.default !== 'undefined' && ! me.data('um-original-value') ) {
				me.val( data.field.default ).trigger('change');
			} else if ( me.data('um-original-value') !== '' ) {
				me.val( me.data('um-original-value') ).trigger('change');
			}

			if ( data.field.editable == 0 ) {
				me.addClass('um-child-option-disabled');
				me.attr('disabled','disabled');
			}
		}
	}

	jQuery( document.body ).on('click', '.um-toggle-password', function (){
		let parent = jQuery(this).closest('.um-field-area-password');
		let passwordField = parent.find('input');
		let type = passwordField.attr('type');
		if ( 'text' === type ) {
			passwordField.attr('type', 'password');
			parent.find('i').toggleClass('um-icon-eye um-icon-eye-disabled');
		} else {
			passwordField.attr('type', 'text');
			parent.find('i').toggleClass('um-icon-eye um-icon-eye-disabled');
		}
	});
});
