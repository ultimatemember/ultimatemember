jQuery( document ).ready( function() {
	/**
	 * Show notice about not saved settings everywhere but not on the modules page
	 */
	if ( jQuery( '#um-modules' ).length === 0 ) {
		var um_settings_changed = false;

		jQuery( 'input, textarea, select' ).on('change', function() {
			um_settings_changed = true;
		});

		jQuery( '#um-settings-wrap .um-nav-tab-wrapper a, #um-settings-wrap .subsubsub a' ).on( 'click', function() {
			if ( um_settings_changed ) {
				window.onbeforeunload = function() {
					return wp.i18n.__( 'Are sure, maybe some settings not saved', 'ultimate-member' );
				};
			} else {
				window.onbeforeunload = '';
			}
		});

		jQuery( '.submit input' ).on( 'click', function() {
			window.onbeforeunload = '';
		});
	}


	jQuery( document.body ).on( 'click', '#um_options_purge_users_cache', function(e) {
		e.preventDefault();

		var obj = jQuery(this);
		obj.prop('disabled', true);

		wp.ajax.send( 'um_purge_users_cache', {
			data: {
				nonce: um_admin_scripts.nonce
			},
			success: function (data) {
				obj.siblings( '.um-setting_ajax_button_response' ).addClass('description complete').html( data.message );

				setTimeout( function() {
					obj.parents('#um-settings-form').find('#submit').trigger('click');
				}, 500 );

				obj.prop('disabled', false);
			},
			error: function (data) {
				console.log(data);
			}
		});

		return false;
	});


	jQuery( document.body ).on( 'click', '#um_options_purge_temp_files', function(e) {
		e.preventDefault();

		var obj = jQuery(this);
		obj.prop('disabled', true);

		wp.ajax.send( 'um_purge_temp_files', {
			data: {
				nonce: um_admin_scripts.nonce
			},
			success: function (data) {
				obj.siblings( '.um-setting_ajax_button_response' ).addClass('description complete').html( data.message );

				setTimeout( function() {
					obj.parents('#um-settings-form').find('#submit').trigger('click');
				}, 500 );

				obj.prop('disabled', false);
			},
			error: function (data) {
				console.log(data);
			}
		});

		return false;
	});
});
