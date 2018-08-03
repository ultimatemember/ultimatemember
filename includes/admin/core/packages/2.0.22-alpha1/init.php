<?php ?>

<script type="text/javascript">
	jQuery( document ).ready( function() {
		var users;
		var users_per_page = 50;

		var current_page = 1;
		//upgrade styles
		um_add_upgrade_log( '<?php echo esc_js( __( 'Upgrade Usermeta...', 'ultimate-member' ) ) ?>' );

		jQuery.ajax({
			url: '<?php echo admin_url( 'admin-ajax.php' ) ?>',
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'um_get_users2022'
			},
			success: function( response ) {
				if ( typeof response.data != 'undefined' ) {

					um_add_upgrade_log( response.data.message );

					users = Math.ceil( response.data.count / users_per_page );

					update_users_per_page();

				} else {
					um_wrong_ajax();
				}
			},
			error: function() {
				um_something_wrong();
			}
		});


		function update_users_per_page() {
			if ( current_page <= users ) {
				jQuery.ajax({
					url: '<?php echo admin_url( 'admin-ajax.php' ) ?>',
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'um_usermeta2022',
						page: current_page,
						pages: users
					},
					success: function( response ) {
						if ( typeof response.data != 'undefined' ) {
							um_add_upgrade_log( response.data.message );
							current_page++;
							update_users_per_page();
						} else {
							um_wrong_ajax();
						}
					},
					error: function() {
						um_something_wrong();
					}
				});
			} else {
				//switch to the next package
				um_run_upgrade();
			}
		}
	});
</script>