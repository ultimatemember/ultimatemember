if ( typeof (window.UM) !== 'object' ) {
	window.UM = {};
}

if ( typeof (window.UM.admin) !== 'object' ) {
	window.UM.admin = {};
}

UM.admin.modal = {
	getVisible: function() {
		return jQuery('.um-admin-modal:visible');
	},
	preload: function () {
		jQuery('.um-admin-modal:visible').addClass('loading');
		jQuery('.um-admin-modal-body:visible').empty();
	},
	loaded: function() {
		jQuery('.um-admin-modal:visible').removeClass('loading');
	},
	setSize: function ( size ) {
		jQuery('.um-admin-modal:visible').addClass( size );
	},
	setAttr: function( id, value ) {
		jQuery('.um-admin-modal:visible').data( id, value );
	},
	remove: function () {
		wp.hooks.doAction( 'um_admin_modal_remove' );

		UM.admin.tooltip.close();
		UM.common.tipsy.hide();

		jQuery('body').removeClass('um-admin-modal-open');

		let $modalBlock = jQuery('.um-admin-modal div[id^="UM_"]');
		let $modalInner = $modalBlock.find( '.um-admin-modal-body.um-admin-metabox' );
		$modalInner.html('').attr('data-select2-id', null);
		$modalBlock.hide().appendTo('body');

		jQuery('.um-admin-modal,.um-admin-overlay').remove();
	},
	resize: function () {
		var required_margin = jQuery('.um-admin-modal:visible').innerHeight() / 2 + 'px';
		jQuery('.um-admin-modal:visible').css({'margin-top': '-' + required_margin });

		wp.hooks.doAction( 'um_admin_modal_resize' );
	},
	show: function( id, ajax, size ) {
		UM.common.tipsy.hide();

		UM.admin.modal.remove();

		jQuery('body').addClass('um-admin-modal-open').append('<div class="um-admin-overlay"></div><div class="um-admin-modal"></div>');
		jQuery('#' + id).prependTo('.um-admin-modal');
		jQuery('#' + id).show();
		jQuery('.um-admin-modal').show();

		jQuery('.um-admin-modal-head').append('<a href="javascript:void(0);" data-action="UM_remove_modal" class="um-admin-modal-close"><i class="um-faicon-times"></i></a>');

		if ( ajax == true ) {
			UM.admin.modal.setSize( size );
			UM.admin.modal.preload();
			UM.admin.modal.resize();
		} else {
			UM.admin.modal.resize();
		}
	},
	contentRequest: function( act_id, arg1, arg2, arg3 ) {
		let in_row     = '';
		let in_sub_row = '';
		let in_column  = '';
		let in_group   = '';

		let $hiddenModalData = jQuery('.um-col-demon-settings');

		if ( $hiddenModalData.data('in_column') ) {
			in_row     = $hiddenModalData.data('in_row');
			in_sub_row = $hiddenModalData.data('in_sub_row');
			in_column  = $hiddenModalData.data('in_column');
			in_group   = $hiddenModalData.data('in_group');
		}

		let form_mode = jQuery('input[type="hidden"][id="form__um_mode"]').val();

		jQuery.ajax({
			url: wp.ajax.settings.url,
			type: 'POST',
			data: {
				action:'um_dynamic_modal_content',
				act_id: act_id,
				arg1 : arg1,
				arg2 : arg2,
				arg3: arg3,
				in_row: in_row,
				in_sub_row: in_sub_row,
				in_column: in_column,
				in_group: in_group,
				nonce: um_admin_scripts.nonce,
				form_mode: form_mode
			},
			complete: function(){
				UM.admin.modal.loaded();
				UM.admin.modal.resize();
			},
			success: function(data) {
				let $adminModal = jQuery('.um-admin-modal');
				$adminModal.find('.um-admin-modal-body').html( data );

				wp.hooks.doAction( 'um_admin_modal_success_result', $adminModal, act_id );

				UM.admin.tooltip.init();
			},
			error: function(data) {

			}
		});
	}
}

jQuery(document).ready(function() {
	/**
		remove modal via action
	**/
	jQuery(document.body).on('click', '.um-admin-overlay, a[data-action="UM_remove_modal"]', function(){
		UM.common.tipsy.hide();
		UM.admin.modal.remove();
	});

	/**
		fire new modal
	**/
	jQuery(document.body).on('click', 'a[data-modal^="UM_"], span[data-modal^="UM_"]', function(e){
		e.preventDefault();

		let modal_id = jQuery(this).attr('data-modal');

		if ( 'UM_fonticons' === modal_id ) {
			let iconField = jQuery(this).data('icon_field');
			if ( '' !== iconField ) {
				jQuery('#UM_fonticons').find('a.um-admin-modal-back').attr("data-icon_field", iconField );
			}
		}

		if ( jQuery(this).attr('data-back') ) {
			jQuery('#UM_fonticons').find('a.um-admin-modal-back').attr("data-modal", jQuery(this).attr('data-back') );
			var current_icon = jQuery( '#' + jQuery(this).attr('data-back') ).find('input#_icon').val();
			if ( '' === current_icon ) {
				jQuery('#UM_fonticons').find('.um-admin-icons span').removeClass('highlighted');
			}
		}

		if ( jQuery(this).data('dynamic-content') ) {
			UM.admin.modal.show( modal_id, true, jQuery(this).data('modal-size') );
			UM.admin.modal.contentRequest( jQuery(this).data('dynamic-content'), jQuery(this).data('arg1'), jQuery(this).data('arg2'), jQuery(this).data('arg3') );
		} else {
			UM.admin.modal.show( modal_id );
		}
	});

	/**
		submit font icon
	**/
	jQuery(document.body).on('click', '#UM_fonticons a.um-admin-modal-back:not(.um-admin-modal-cancel)', function(){
		let baseWrapper = '';
		let iconSelected = jQuery(this).attr('data-code');
		let inModal = false;
		if ( '' !== iconSelected ) {
			if ( jQuery(this).attr('data-modal') ) {
				inModal = true;
				baseWrapper = '#' + jQuery(this).attr('data-modal');
				jQuery( baseWrapper ).find('input#_icon,input#_um_icon,input#notice__um_icon,input#um_profile_tab__icon').val( iconSelected );
				jQuery( baseWrapper ).find('span.um-admin-icon-value').html('<i class="' + iconSelected + '"></i>');
				jQuery( baseWrapper ).find('.um-admin-icon-clear').addClass('show');
			} else {
				baseWrapper = '#' + jQuery(this).data('icon_field');
				jQuery(baseWrapper).val( iconSelected ).parent().find('span.um-admin-icon-value').html('<i class="' + iconSelected + '"></i>');
				jQuery( baseWrapper ).parent().find('.um-admin-icon-clear').addClass('show');
			}
		}
		jQuery(this).attr('data-code', '');
		if ( ! inModal ) {
			UM.common.tipsy.hide();
			UM.admin.modal.remove();
		}
	});

	/**
		restore font icon
	**/
	// jQuery(document.body).on('click', 'span.um-admin-icon-clear', function(){
	// 	var element = jQuery(this).parents('p');
	// 	jQuery('#UM_fonticons a.um-admin-modal-back').attr('data-code', '');
	// 	element.find('input[type="hidden"]').val('');
	// 	element.find('.um-admin-icon-value').html( wp.i18n.__( 'No Icon', 'ultimate-member' ) );
	//
	// 	element = jQuery(this).parents('td');
	// 	element.find('input[type="hidden"]').val('');
	// 	element.find('.um-admin-icon-value').html( wp.i18n.__( 'No Icon', 'ultimate-member' ) );
	// 	jQuery(this).removeClass('show');
	// });

	jQuery(document.body).on('click', 'span.um-admin-icon-clear', function(){
		var element = jQuery(this).parents('p');
		jQuery('#UM_fonticons a.um-admin-modal-back').attr('data-code', '');
		element.find('input[type="hidden"]').val('');
		element.find('.um_admin_fonticon_wrapper').hide();
	//	element.find('.um-admin-icon-value').html( wp.i18n.__( 'No Icon', 'ultimate-member' ) );

		element = jQuery(this).parents('td');
		element.find('input[type="hidden"]').val('');
		element.find('.um_admin_fonticon_wrapper').hide();
		//element.find('.um-admin-icon-value').html( wp.i18n.__( 'No Icon', 'ultimate-member' ) );
		//jQuery(this).removeClass('show');
	});

	/**
		search font icons
	**/
	jQuery(document.body).on('keyup blur', '#_icon_search', function(){
		if ( jQuery(this).val().toLowerCase() !== '' ) {
			jQuery('.um-admin-icons span').hide();
			jQuery('.um-admin-icons span[data-code*="'+jQuery(this).val().toLowerCase()+'"]').show();
		} else {
			jQuery('.um-admin-icons span:hidden').show();
		}
		UM.admin.modal.resize();
	});

	/**
		choose font icon
	 **/
	jQuery(document.body).on('click', '.um-admin-icons span', function(){
		var icon = jQuery(this).attr('data-code');
		jQuery(this).parent().find('span').removeClass('highlighted');
		jQuery(this).addClass('highlighted');
		jQuery('#UM_fonticons').find('a.um-admin-modal-back').attr("data-code", icon);
	});
});
