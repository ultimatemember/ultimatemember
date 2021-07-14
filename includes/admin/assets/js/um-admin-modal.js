var um_admin_scripts, um_tinymce_init;

/**
 * Load the body of the admin modal by AJAX.
 * @version 3.0
 *
 * @param   {jqXHR|null} jqXHR  The jqXHR object or Null
 * @param   {Object} $modal     The modal jQuery object.
 * @param   {Object} $btn       The button jQuery object.
 * @param   {Object} data       Parameters.
 * @returns {jqXHR}             The jqXHR Object.
 */
function um_admin_modal_ajaxcall(jqXHR, $modal, $btn, data) {

	if ( !$btn.data( 'dynamic-content' ) ) {
		return jqXHR;
	}

	let in_row = '',
			in_sub_row = '',
			in_column = '',
			in_group = '',
			form_mode = jQuery( 'input#form__um_mode' ).val(),
			colDemonSettings = jQuery( 'div.um-col-demon-settings' );

	if ( colDemonSettings.data( 'in_column' ) ) {
		in_row = colDemonSettings.data( 'in_row' );
		in_sub_row = colDemonSettings.data( 'in_sub_row' );
		in_column = colDemonSettings.data( 'in_column' );
		in_group = colDemonSettings.data( 'in_group' );
	}

	return jQuery.ajax( {
		url: wp.ajax.settings.url,
		type: 'POST',
		data: {
			action: 'um_dynamic_modal_content',
			act_id: $btn.data( 'dynamic-content' ),
			arg1: $btn.data( 'arg1' ) || data.arg1,
			arg2: $btn.data( 'arg2' ) || data.arg2,
			arg3: $btn.data( 'arg3' ) || data.arg3,
			in_row: in_row,
			in_sub_row: in_sub_row,
			in_column: in_column,
			in_group: in_group,
			nonce: um_admin_scripts.nonce,
			form_mode: form_mode
		},
		success: function (data) {
			$modal = UM.modal.getModal( $modal );
			$modal.removeClass( 'loading' );
			$modal.find( '.um-admin-modal-body' ).html( data );
			UM.modal.responsive( $modal );
		},
		error: function (data) {
			console.error( data );
		}
	} );
}

/**
 * Load the body of the admin modal by AJAX.
 */
wp.hooks.addFilter( 'um-modal-ajax', 'ultimatemember', um_admin_modal_ajaxcall, 10 );


/**
 * Additional scripts after the admin modal opening.
 * @since 3.0
 *
 * @param {object} $modal  The modal jQuery object.
 * @param {object} $btn    The button jQuery object.
 * @param {object} data    Options.
 * @param {jqXHR}  jqXHR   The jqXHR Object.
 */
function um_admin_modal_shown($modal, $btn, data, jqXHR) {
	if ( typeof data.modal === 'string' && jqXHR ) {
		jqXHR.done( function () {
			switch ( data.modal ) {

				case 'UM_add_field':
				case 'UM_edit_field':
				case 'UM_edit_row':

					$modal.find( "#_custom_dropdown_options_source" ).trigger( 'blur' );

					$modal.find( '.um-adm-conditional' ).each( function () {
						jQuery( this ).trigger( 'change' );
					} );

					if ( $modal.find( '.um-admin-editor' ).length && typeof um_tinymce_init === 'function' ) {
						um_tinymce_init( 'um_editor_edit', $modal.find( '.dynamic-mce-content' ).html() );
					}

					let $colorpicker = $modal.find( '.um-admin-colorpicker' );
					if ( $colorpicker.length ) {
						$colorpicker.wpColorPicker();
					}

					um_admin_init_datetimepicker();
					um_init_tooltips();
					break;

				case 'UM_fonticons':
					var current_icon = $btn.parent().find( 'input#_icon' ).val();
					if ( !current_icon ) {
						$modal.find( '.um-admin-icons span.highlighted' ).removeClass( 'highlighted' );
					} else {
						$modal.find( '.um-admin-icons span[data-code="' + current_icon + '"]' ).addClass( 'highlighted' ).siblings( '.highlighted' ).removeClass( 'highlighted' );
					}
					break;

				case 'UM_preview_form':
					um_responsive();
					break;

			}
		} );
	}
}

/**
 * Call additional scripts after the modal opening
 */
wp.hooks.addAction( 'um-modal-opened', 'ultimatemember', um_admin_modal_shown, 10 );


/**
 * Custom modal scripting
 * @returns
 */
jQuery( function () {

	/**
	 * disable links
	 * @param {object} e  jQuery.Event
	 */
	jQuery( document.body ).on( 'click', '.um-admin-builder a, .um-admin-modal a', function (e) {
		e.preventDefault();
		return false;
	} );

	/**
	 * Retrieve options from a callback function
	 */
	jQuery( document.body ).on( 'blur', "#_custom_dropdown_options_source", function () {
		var me = jQuery( this );
		var um_option_callback = me.val();
		var _options = jQuery( 'textarea[id=_options]' );

		if ( um_option_callback !== '' ) {
			jQuery.ajax( {
				url: wp.ajax.settings.url,
				type: 'POST',
				data: {
					action: 'um_populate_dropdown_options',
					um_option_callback: um_option_callback,
					nonce: um_admin_scripts.nonce
				},
				complete: function () {

				},
				success: function (response) {
					var arr_opts = [];

					for ( var key in response.data ) {
						arr_opts.push( response.data[ key ] );
					}

					_options.val( arr_opts.join( '\n' ) );

				}
			} );
		}
	} );


	/* CONDITIONS */

	/**
	 * toggle area
	 * @param {object} e  jQuery.Event
	 */
	jQuery( document.body ).on( 'click', '.um-admin-btn-toggle > a', function (e) {
		e.preventDefault();

		var $btn = jQuery( e.currentTarget );
		var content = $btn.closest( '.um-admin-btn-toggle' ).find( '.um-admin-btn-content' );

		if ( content.is( ':hidden' ) ) {
			content.show();
			$btn.addClass( 'active' ).find( 'i' ).removeClass( 'um-icon-plus' ).addClass( 'um-icon-minus' );
		} else {
			content.hide();
			$btn.removeClass( 'active' ).find( 'i' ).removeClass( 'um-icon-minus' ).addClass( 'um-icon-plus' );
		}
		UM.modal.responsive();
	} );

	/**
	 * clone a condition
	 * @param {object} e  jQuery.Event
	 */
	jQuery( document.body ).on( 'click', '.um-admin-new-condition:not(.disabled)', function (e) {
		e.preventDefault();

		var content = jQuery( this ).parents( '.um-admin-btn-content' );
		var length = content.find( '.um-admin-cur-condition' ).length;

		if ( length < 5 ) {
			var template = jQuery( '.um-admin-btn-content' ).find( '.um-admin-cur-condition-template' ).clone();

			if ( length > 0 ) {
				for ( var i = 1; i < 5; i++ ) {
					if ( content.find( '[id="_conditional_action' + i + '"]' ).length < 1 ) {
						var id = i;
						break;
					}
				}
				template.find( '[id^="_conditional_action"]' ).attr( 'name', '_conditional_action' + id );
				template.find( '[id^="_conditional_action"]' ).attr( 'id', '_conditional_action' + id );
				template.find( '[id^="_conditional_field"]' ).attr( 'name', '_conditional_field' + id );
				template.find( '[id^="_conditional_field"]' ).attr( 'id', '_conditional_field' + id );
				template.find( '[id^="_conditional_operator"]' ).attr( 'name', '_conditional_operator' + id );
				template.find( '[id^="_conditional_operator"]' ).attr( 'id', '_conditional_operator' + id );
				template.find( '[id^="_conditional_value"]' ).attr( 'name', '_conditional_value' + id );
				template.find( '[id^="_conditional_value"]' ).attr( 'id', '_conditional_value' + id );
			}

			template.find( 'input[type=text], select' ).val( '' );
			template.removeClass( "um-admin-cur-condition-template" ).addClass( "um-admin-cur-condition" ).appendTo( content );

			UM.modal.responsive();
		} else {
			jQuery( this ).addClass( 'disabled' );
			alert( 'You already have 5 rules' );
		}
	} );

	/**
	 * reset conditions
	 * @param {object} e  jQuery.Event
	 */
	jQuery( document.body ).on( 'click', '.um-admin-reset-conditions a', function (e) {
		e.preventDefault();

		var content = jQuery( this ).parents( '.um-admin-btn-content' );

		content.find( '.um-admin-cur-condition' ).slice( 1 ).remove();
		content.find( 'input[type=text], select' ).val( '' );
		content.find( '.um-admin-new-condition' ).removeClass( 'disabled' );

		UM.modal.responsive();
	} );

	/**
	 * remove a condition
	 * @param {object} e  jQuery.Event
	 */
	jQuery( document.body ).on( 'click', '.um-admin-remove-condition', function (e) {
		e.preventDefault();

		var condition = jQuery( this ).parents( '.um-admin-cur-condition' );
		var content = condition.parents( '.um-admin-btn-content' );

		condition.remove();
		content.find( '.um-admin-new-condition' ).removeClass( 'disabled' );

		UM.modal.responsive();
	} );


	/* ICON */

	/**
	 * search font icons
	 */
	jQuery( document.body ).on( 'keyup blur', '#_icon_search', function () {
		if ( this.value.toLowerCase() !== '' ) {
			jQuery( '.um-admin-icons span' ).hide();
			jQuery( '.um-admin-icons span[data-code*="' + this.value.toLowerCase() + '"]' ).show();
		} else {
			jQuery( '.um-admin-icons span' ).show();
		}
		UM.modal.responsive();
	} );

	/**
	 * choose font icon
	 */
	jQuery( document.body ).on( 'click', '.um-admin-icons span', function () {
		var $icon = jQuery( this );
		$icon.addClass( 'highlighted' ).siblings( '.highlighted' ).removeClass( 'highlighted' );
		$icon.closest( '.um-admin-modal' ).find( 'a.um-admin-modal-back' ).attr( 'data-code', $icon.data( 'code' ) );
	} );

	/**
	 * submit font icon
	 * @param {object} e  jQuery.Event
	 */
	jQuery( document.body ).on( 'click', '#UM_fonticons a.um-admin-modal-back', function (e) {
		e.preventDefault();

		var $btn = jQuery( this );
		var icon_selected = $btn.attr( 'data-code' );

		if ( $btn.is( '[data-action="um_remove_modal"]' ) || !icon_selected ) {
			return false;
		}

		var v, v_id = '';
		if ( $btn.attr( 'data-modal' ) ) {
			v_id = '#' + $btn.attr( 'data-modal' );
			v = UM.modal.getModal( v_id );
		} else {
			v_id = '.postbox';
			v = jQuery( v_id );
		}

		if ( v.length > 0 ) {
			v.find( 'input#_icon, input#_um_icon, input#notice__um_icon, input#um_profile_tab__icon' ).val( icon_selected );
			v.find( 'span.um-admin-icon-value' ).html( '<i class="' + icon_selected + '"></i>' );
			v.find( 'span.um-admin-icon-clear' ).css( {display: 'inline'} ).show();
		}

		UM.modal.close();
	} );

	/**
	 * restore font icon
	 * @param {object} e  jQuery.Event
	 */
	jQuery( document.body ).on( 'click', 'span.um-admin-icon-clear', function (e) {
		e.preventDefault();

		var $btn = jQuery( this ).hide();
		var element = $btn.closest( 'p,td' );
		element.find( 'input[type="hidden"]' ).val( '' );
		element.find( '.um-admin-icon-value' ).html( wp.i18n.__( 'No Icon', 'ultimate-member' ) );

		jQuery( '#UM_fonticons' ).find( 'a.um-admin-modal-back' ).removeAttr( 'data-modal' );
	} );

} );



/**
 * @deprecated since 3.0. Use UM.modal.newModal() instead.
 * @param   {string}  id
 * @param   {string}  size
 * @param   {boolean} ajax
 * @returns {object}
 */
function um_admin_new_modal(id, size, ajax) {
	return UM.modal.newModal( id, size, ajax, null, true );
}

/**
 * @deprecated since 3.0. Use UM.modal.responsive() instead.
 */
function um_admin_live_update_scripts() {
}

/**
 * @deprecated since 3.0. Use UM.modal.responsive() instead.
 */
function um_admin_modal_responsive() {
	UM.modal.responsive();
}

/**
 * @deprecated since 3.0. Use UM.modal.close() instead.
 */
function um_admin_remove_modal() {
	UM.modal.close();
}

/**
 * @deprecated since 3.0.
 */
function um_admin_modal_preload() {}

/**
 * @deprecated since 3.0.
 */
function um_admin_modal_loaded() {}

/**
 * @deprecated since 3.0.
 */
function um_admin_modal_size() {}

/**
 * @deprecated since 3.0.
 */
function um_admin_modal_add_attr() {}