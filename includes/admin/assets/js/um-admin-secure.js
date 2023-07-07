jQuery(document).on("ready", function(){

	const scan_results_wrapper = jQuery(".um-secure-scan-results");
	const scan_button_elem = jQuery(".um-secure-scan-content");
	const scan_capabilities = jQuery("input[data-field_id^='banned_capabilities']");

	var UM_Secure = {
		init: function() {
			scan_results_wrapper.css({
				'margin-top': '10px',
				'padding': '10px',
				'padding-bottom': '10px',
				'background-color': '#fff',
				'display': 'block',
				'max-height': '200px',
				'height': '500px',
				'overflow-y': 'scroll',
			});

			scan_button_elem.on("click", function(e){
				UM_Secure.effect();
				e.preventDefault();
				var me = jQuery(this);
				me.prop("disabled", true);
				scan_results_wrapper.empty();

				UM_Secure.log( wp.i18n.__( 'Scanning site..', 'ultimate-member' ) );

				UM_Secure.ajax('');

			});
			scan_capabilities.on("change", function(){
				scan_button_elem.after( ' <small style="color: red;">' + wp.i18n.__( 'You can start the scan now but you must save the settings to apply the selected capabilities after the scan is complete.', 'ultimate-member' ) + '</small>' );
				scan_capabilities.off("change");
			})
		},
		ajax: function( last_capability ) {
			let checkedCaps = [];
			let checkedCapsInputs = scan_results_wrapper.parents('.um-form-table').find('input[type="checkbox"][data-field_id^="banned_capabilities_"]:checked');
			checkedCapsInputs.each(function (){
				checkedCaps.push( jQuery(this).data('field_id').replace('banned_capabilities_', '') );
			});

			var request = {
				nonce: um_admin_scripts.nonce,
				capabilities: checkedCaps,
				last_scanned_capability: last_capability,
			};

			wp.ajax.send('um_secure_scan_affected_users', {
				data: request,
				success: function (response) {
					if ( ! response.completed ) {
						UM_Secure.ajax( response.last_scanned_capability );
						UM_Secure.log( response.message );
					} else if ( response.completed ) {
						scan_results_wrapper.empty();
						UM_Secure.log( response.recommendations );
						scan_results_wrapper.find('.current').removeClass('current');
						scan_button_elem.removeAttr('disabled');
					}
				},
			});
		},
		log: function( str ) {
			scan_results_wrapper.find('.current').removeClass('current');
			scan_results_wrapper.append( '<span class="current">' + str + '</span><br/>' );
		},
		effect: function() {
			var blink = function(){
				scan_results_wrapper.find(".current").fadeTo(100, 0.1).fadeTo(200, 1.0);
			};
			setInterval(blink, 1000);
		}
	};

	UM_Secure.init();
});
