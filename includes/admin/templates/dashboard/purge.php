<?php if ( ! defined( 'ABSPATH' ) ) exit;


if ( $this->dir_size( 'temp' ) > 0.1 ) { ?>

	<p>
		<?php printf( __( 'You can free up <span class="red">%s MB</span> by purging your temp upload directory.', 'ultimate-member' ), $this->dir_size( 'temp' ) ); ?>
	</p>

	<p>
		<a href="<?php echo esc_url( add_query_arg( 'um_adm_action', 'purge_temp' ) ); ?>" class="button">
			<?php _e( 'Purge Temp', 'ultimate-member' ); ?>
		</a>
	</p>

<?php } else { ?>

	<p>
		<?php _e( 'Your temp uploads directory is <span class="ok">clean</span>. There is nothing to purge.', 'ultimate-member' ); ?>
	</p>

<?php } ?>