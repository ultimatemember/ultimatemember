<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>


<script type="text/javascript">
	jQuery( document ).ready( function() {
		var users_pages;
		var current_page = 1;
		var users_per_page = 50;

		um_add_upgrade_log( '<?php echo esc_js( __( 'Upgrade user metadata...', 'ultimate-member' ) ) ?>' );

		jQuery.ajax({
			url: wp.ajax.settings.url,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'um_users_count213beta3',
				nonce: um_admin_scripts.nonce
			},
			success: function( response ) {
				if ( typeof response.data.count != 'undefined' ) {
					um_add_upgrade_log( '<?php echo esc_js( __( 'There are ', 'ultimate-member' ) ) ?>' + response.data.count + '<?php echo esc_js( __( ' users...', 'ultimate-member' ) ) ?>' );
					um_add_upgrade_log( '<?php echo esc_js( __( 'Start metadata upgrading...', 'ultimate-member' ) ) ?>' );

					users_pages = Math.ceil( response.data.count / users_per_page );

					um_update_metadata_per_user213beta3();
				} else {
					um_wrong_ajax();
				}
			},
			error: function() {
				um_something_wrong();
			}
		});

		function um_update_metadata_per_user213beta3() {
			if ( current_page <= users_pages ) {
				jQuery.ajax({
					url: wp.ajax.settings.url,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'um_metadata_per_user213beta3',
						page: current_page,
						nonce: um_admin_scripts.nonce
					},
					success: function( response ) {
						if ( typeof response.data != 'undefined' ) {
							um_add_upgrade_log( response.data.message );
							current_page++;
							um_update_metadata_per_user213beta3();
						} else {
							um_wrong_ajax();
						}
					},
					error: function() {
						um_something_wrong();
					}
				});
			} else {
				um_metatable213beta3();
			}
		}


		//clear users cache
		function um_metatable213beta3() {
			um_add_upgrade_log( '<?php echo esc_js( __( 'Create additional metadata table...', 'ultimate-member' ) ) ?>' );
			jQuery.ajax({
				url: wp.ajax.settings.url,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'um_metatable213beta3',
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