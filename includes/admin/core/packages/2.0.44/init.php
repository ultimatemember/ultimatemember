<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<script type="text/javascript">
	jQuery( document ).ready( function() {
		//upgrade styles
		um_add_upgrade_log( '<?php echo esc_js( __( 'Upgrade friends table...', 'ultimate-member' ) ) ?>' );

		jQuery.ajax({
			url: wp.ajax.settings.url,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'um_friends2044',
				nonce: um_admin_scripts.nonce
			},
			success: function( response ) {
				if ( typeof response.data != 'undefined' ) {
					um_add_upgrade_log( response.data.message );
					//switch to the next package
					um_predefined_fields2044();
				} else {
					um_wrong_ajax();
				}
			},
			error: function() {
				um_something_wrong();
			}
		});


		//clear users cache
		function um_predefined_fields2044() {
			um_add_upgrade_log( '<?php echo esc_js( __( 'Upgrade predefined metafields...', 'ultimate-member' ) ) ?>' );
			jQuery.ajax({
				url: wp.ajax.settings.url,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'um_fields2044',
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