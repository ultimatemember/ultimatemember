<?php $all_options = wp_load_alloptions();

$count = 0;
foreach ( $all_options as $k => $v ) {
	if ( strstr( $k, 'um_cache_userdata_' ) !== false ) {
		$count++;
	}
} ?>

<p><?php _e( 'Run this task from time to time to keep your DB clean.', 'ultimate-member' ) ?></p>
<p>
	<a href="<?php echo add_query_arg( 'um_adm_action', 'user_cache' ); ?>" class="button">
		<?php printf( __( 'Clear cache of %s users', 'ultimate-member' ), $count ) ?>
	</a>
</p>