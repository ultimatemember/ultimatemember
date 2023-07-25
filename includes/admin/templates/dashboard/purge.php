<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$url = add_query_arg(
	array(
		'um_adm_action' => 'purge_temp',
		'_wpnonce'      => wp_create_nonce( 'purge_temp' ),
	)
);

if ( $this->dir_size( 'temp' ) > 0.1 ) { ?>

	<p>
		<?php
		// translators: %s: temp folder size.
		echo wp_kses( sprintf( __( 'You can free up <span class="red">%s MB</span> by purging your temp upload directory.', 'ultimate-member' ), $this->dir_size( 'temp' ) ), UM()->get_allowed_html( 'admin_notice' ) );
		?>
	</p>

	<p>
		<a href="<?php echo esc_url( $url ); ?>" class="button">
			<?php esc_html_e( 'Purge Temp', 'ultimate-member' ); ?>
		</a>
	</p>

<?php } else { ?>

	<p>
		<?php _e( 'Your temp uploads directory is <span class="ok">clean</span>. There is nothing to purge.', 'ultimate-member' ); ?>
	</p>

<?php } ?>
