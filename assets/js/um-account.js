jQuery(document).ready(function() {

	var current_tab = jQuery('.um-account-main').attr('data-current_tab');

	if ( current_tab ) {
		jQuery('.um-account-tab[data-tab="'+current_tab+'"]').show();
		jQuery('.um-account-tab:not(:visible)').find( 'input, select, textarea' ).not( ':disabled' ).addClass('um_account_inactive').prop( 'disabled', true ).attr( 'disabled', true );
		wp.hooks.doAction( 'um_account_active_tab_inited', current_tab );
	}

	jQuery( document.body ).on( 'click', '.um-account-side li a', function(e) {
		e.preventDefault();
		var link = jQuery(this);

		link.parents('ul').find('li a').removeClass('current');
		link.addClass('current');

		var url_ = jQuery(this).attr('href');
		var tab_ = jQuery(this).attr('data-tab');

		jQuery('input[id="_um_account_tab"]:hidden').val( tab_ );

		window.history.pushState("", "", url_);

		jQuery('.um-account-tab').hide();
		jQuery('.um-account-tab[data-tab="'+tab_+'"]').fadeIn();

		jQuery('.um-account-tab:visible').find( 'input, select, textarea' ).filter( '.um_account_inactive:disabled' ).removeClass('um_account_inactive').prop( 'disabled', false ).attr( 'disabled', false );
		jQuery('.um-account-tab:not(:visible)').find( 'input, select, textarea' ).not( ':disabled' ).addClass('um_account_inactive').prop( 'disabled', true ).attr( 'disabled', true );

		jQuery('.um-account-nav a').removeClass('current');
		jQuery('.um-account-nav a[data-tab="'+tab_+'"]').addClass('current');

		jQuery(this).parents('.um-account').find('.um-account-main .um-notice').fadeOut();

		wp.hooks.doAction( 'um_after_account_tab_changed', tab_ );

		return false;
	});


	jQuery(document.body).on( 'click', '.um-account-nav a', function(e) {
		e.preventDefault();

		var tab_ = jQuery(this).attr('data-tab');
		var div = jQuery(this).parents('div');
		var link = jQuery(this);


		jQuery('input[id="_um_account_tab"]:hidden').val( tab_ );

		jQuery('.um-account-tab').hide();

		if ( link.hasClass('current') ) {
			div.next('.um-account-tab').slideUp();
			link.removeClass('current');
		} else {
			div.next('.um-account-tab').slideDown();
			link.parents('div').find('a').removeClass('current');
			link.addClass('current');
		}

		jQuery('.um-account-tab:visible').find( 'input, select, textarea' ).filter( '.um_account_inactive:disabled' ).removeClass('um_account_inactive').prop( 'disabled', false ).attr( 'disabled', false );
		jQuery('.um-account-tab:not(:visible)').find( 'input, select, textarea' ).not( ':disabled' ).addClass('um_account_inactive').prop( 'disabled', true ).attr( 'disabled', true );

		jQuery('.um-account-side li a').removeClass('current');
		jQuery('.um-account-side li a[data-tab="'+tab_+'"]').addClass('current');

		wp.hooks.doAction( 'um_after_account_tab_changed', tab_ );

		return false;
	});


	jQuery(document.body).on( 'click', '.um-request-button', function(e) {
		e.preventDefault();

		var request_action = jQuery(this).data('action');
		var password = jQuery('#' + request_action).val();
		jQuery('.um-field-area-response.' + request_action).hide();

		if ( jQuery('#' + request_action).length && password === '' ) {
			jQuery('.um-field-error.' + request_action).show();
		} else {
			jQuery('.um-field-error.' + request_action).hide();
			var request = {
				request_action: request_action,
				nonce: um_scripts.nonce
			};

			if ( jQuery('#' + request_action).length ) {
				request.password = password;
			}

			wp.ajax.send( 'um_request_user_data', {
				data: request,
				success: function (data) {
					jQuery('.um-field-area-response.' + request_action).text( data.answer ).show();
				},
				error: function (data) {
					console.log(data);
				}
			});
		}

	});

});
