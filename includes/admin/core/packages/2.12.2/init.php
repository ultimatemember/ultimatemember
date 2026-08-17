<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<script type="text/javascript">
	jQuery( document ).ready( function() {
		var users_pages;
		var current_page = 1;
		var users_per_page = 100;

		um_add_upgrade_log( '<?php echo esc_js( __( 'Upgrade country fields...', 'ultimate-member' ) ); ?>' );

		jQuery.ajax({
			url: wp.ajax.settings.url,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'um_country_fields2122',
				nonce: um_admin_scripts.nonce
			},
			success: function( response ) {
				if ( typeof response.data.message != 'undefined' ) {
					um_add_upgrade_log( response.data.message );

					setTimeout( function () {
						um_usermeta_count2122();
					}, um_request_throttle );
				} else {
					um_wrong_ajax();
				}
			},
			error: function() {
				um_something_wrong();
			}
		});

		function um_usermeta_count2122() {
			um_add_upgrade_log( '<?php echo esc_js( __( 'Get user metadata with country fields...', 'ultimate-member' ) ); ?>' );

			jQuery.ajax({
				url: wp.ajax.settings.url,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'um_usermeta_count2122',
					nonce: um_admin_scripts.nonce
				},
				success: function( response ) {
					if ( typeof response.data.count != 'undefined' ) {
						um_add_upgrade_log( '<?php echo esc_js( __( 'There are %s metarows...', 'ultimate-member' ) ); ?>'.replace( '%s', response.data.count ) );
						um_add_upgrade_log( '<?php echo esc_js( __( 'Start metadata upgrading...', 'ultimate-member' ) ); ?>' );

						users_pages = Math.ceil( response.data.count / users_per_page );

						setTimeout( function () {
							um_update_usermeta_per_page2122();
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

		function um_update_usermeta_per_page2122() {
			if ( current_page <= users_pages ) {
				jQuery.ajax({
					url: wp.ajax.settings.url,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'um_usermeta_part2122',
						page: current_page,
						nonce: um_admin_scripts.nonce
					},
					success: function( response ) {
						if ( response.success && typeof response.data.message != 'undefined' ) {
							um_add_upgrade_log( response.data.message );
							current_page++;
							setTimeout( function () {
								um_update_usermeta_per_page2122();
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
					um_option_update2122();
				}, um_request_throttle );
			}
		}

		//clear users cache
		function um_option_update2122() {
			um_add_upgrade_log( '<?php echo esc_js( __( 'Update options table...', 'ultimate-member' ) ); ?>' );
			jQuery.ajax({
				url: wp.ajax.settings.url,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'um_update_options2122',
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
