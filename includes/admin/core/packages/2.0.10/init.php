<?php ?>

<script type="text/javascript">
	jQuery( document ).ready( function() {
		//upgrade styles
		um_add_upgrade_log( '<?php echo esc_js( __( 'Upgrade Styles...', 'ultimate-member' ) ) ?>' );

		jQuery.ajax({
			url: wp.ajax.settings.url,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'um_styles2010',
				nonce: um_admin_scripts.nonce
			},
			success: function( response ) {
				if ( typeof response.data != 'undefined' ) {
					um_add_upgrade_log( response.data.message );
					setTimeout( function () {
						um_clear_cache2010();
					}, um_request_throttle );
				} else {
					um_wrong_ajax();
				}
			},
			error: function() {
				um_something_wrong();
			}
		});


		//clear users cache
		function um_clear_cache2010() {
			um_add_upgrade_log( '<?php echo esc_js( __( 'Clear Users Cache...', 'ultimate-member' ) ) ?>' );
			jQuery.ajax({
				url: wp.ajax.settings.url,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'um_cache2010',
					nonce: um_admin_scripts.nonce
				},
				success: function( response ) {
					if ( typeof response.data != 'undefined' ) {
						um_add_upgrade_log( response.data.message );
						//switch to the next package
						um_run_upgrade();
					} else {
						um_wrong_ajax();
					}
				},
				error: function() {
					um_something_wrong();
				}
			});
		}
	});
</script>