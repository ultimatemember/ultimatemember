<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>


<script type="text/javascript">
	jQuery( document ).ready( function() {
		var metarows_pages;
		var current_page = 1;
		var metarows_per_page = 100;

		um_add_upgrade_log( '<?php echo esc_js( __( 'Upgrade SkypeID fields in UM Forms and generally in predefined fields...', 'ultimate-member' ) ) ?>' );

		jQuery.ajax({
			url: wp.ajax.settings.url,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'um_skypeid_fields230',
				nonce: um_admin_scripts.nonce
			},
			success: function( response ) {
				if ( typeof response.data.message != 'undefined' ) {
					um_add_upgrade_log( response.data.message );

					setTimeout( function () {
						if ( response.data.count > 0 ) {
							um_update_get_usermeta_count230();
						} else {
							um_reset_password230();
						}
					}, um_request_throttle );
				} else {
					um_wrong_ajax();
				}
			},
			error: function() {
				um_something_wrong();
			}
		});


		function um_update_get_usermeta_count230() {
			um_add_upgrade_log( '<?php echo esc_js( __( 'Upgrade SkypeID fields metadata for users...', 'ultimate-member' ) ) ?>' );

			jQuery.ajax({
				url: wp.ajax.settings.url,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'um_usermeta_count230',
					nonce: um_admin_scripts.nonce
				},
				success: function( response ) {
					if ( typeof response.data.count != 'undefined' ) {
						um_add_upgrade_log( '<?php echo esc_js( __( 'There are ', 'ultimate-member' ) ) ?>' + response.data.count + '<?php echo esc_js( __( ' metadata rows...', 'ultimate-member' ) ) ?>' );
						um_add_upgrade_log( '<?php echo esc_js( __( 'Start metadata upgrading...', 'ultimate-member' ) ) ?>' );

						metarows_pages = Math.ceil( response.data.count / metarows_per_page );

						setTimeout( function () {
							um_update_usermeta_part230();
						}, um_request_throttle );
					} else {
						um_wrong_ajax();
					}
				},
				error: function() {
					um_something_wrong();
				}
			});
		}


		function um_update_usermeta_part230() {
			if ( current_page <= metarows_pages ) {
				jQuery.ajax({
					url: wp.ajax.settings.url,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'um_usermeta_part230',
						page: current_page,
						nonce: um_admin_scripts.nonce
					},
					success: function( response ) {
						if ( typeof response.data != 'undefined' ) {
							um_add_upgrade_log( response.data.message );
							current_page++;
							setTimeout( function() {
								um_update_usermeta_part230();
							}, um_request_throttle );
						} else {
							um_wrong_ajax();
						}
					},
					error: function() {
						um_something_wrong();
					}
				});
			} else {
				setTimeout( function () {
					um_reset_password230();
				}, um_request_throttle );
			}
		}


		function um_reset_password230() {
			um_add_upgrade_log( '<?php echo esc_js( __( 'Upgrade the "Require strong password" options...', 'ultimate-member' ) ) ?>' );

			jQuery.ajax({
				url: wp.ajax.settings.url,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'um_reset_password230',
					nonce: um_admin_scripts.nonce
				},
				success: function( response ) {
					if ( typeof response.data.message != 'undefined' ) {
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
