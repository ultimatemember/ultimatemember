<?php ?>

<script type="text/javascript">
	jQuery( document ).ready( function() {
		um_add_upgrade_log( 'Upgrade Usermeta...' );

		jQuery.ajax({
			url: '<?php echo admin_url( 'admin-ajax.php' ) ?>',
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'um_usermetaquery1339'
			},
			success: function( response ) {
				if ( typeof response.data != 'undefined' ) {
					um_add_upgrade_log( response.data.message );
					//switch to the next package
					um_run_upgrade();
				} else {
					um_add_upgrade_log( 'Wrong AJAX response...' );
					um_add_upgrade_log( 'Your upgrade was crashed, please contact with support' );
				}
			},
			error: function() {
				um_add_upgrade_log( 'Something went wrong with AJAX request...' );
				um_add_upgrade_log( 'Your upgrade was crashed, please contact with support' );
			}
		});
	});
</script>