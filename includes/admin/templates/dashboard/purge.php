<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$temp_dir_size = UM()->common()->filesystem()->dir_size( UM()->common()->filesystem()->get_tempdir() );

$url = add_query_arg(
	array(
		'um_adm_action' => 'purge_temp',
		'_wpnonce'      => wp_create_nonce( 'purge_temp' ),
	)
);

if ( $temp_dir_size > 0.1 ) { ?>

	<p>
		<?php
		// translators: %s: temp folder size.
		echo wp_kses( sprintf( __( 'You can free up <span class="red">%s MB</span> by purging your temp upload directory.', 'ultimate-member' ), $temp_dir_size ), UM()->get_allowed_html( 'admin_notice' ) );
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
