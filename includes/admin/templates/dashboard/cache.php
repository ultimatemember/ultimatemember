<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$count = $wpdb->get_var(
	"SELECT COUNT( option_id )
	FROM {$wpdb->options}
	WHERE option_name LIKE 'um_cache_userdata_%'"
);

$url_user_cache = add_query_arg(
	array(
		'um_adm_action' => 'user_cache',
		'_wpnonce'      => wp_create_nonce( 'user_cache' ),
	)
);

$url_user_status_cache = add_query_arg(
	array(
		'um_adm_action' => 'user_status_cache',
		'_wpnonce'      => wp_create_nonce( 'user_status_cache' ),
	)
);
?>

<p><?php esc_html_e( 'Run this task from time to time to keep your DB clean.', 'ultimate-member' ); ?></p>

<p>
	<a href="<?php echo esc_url( $url_user_cache ); ?>" class="button">
		<?php
		// translators: %s: users number.
		echo esc_html( sprintf( __( 'Clear cache of %s users', 'ultimate-member' ), $count ) );
		?>
	</a>
	<a href="<?php echo esc_url( $url_user_status_cache ); ?>" class="button">
		<?php esc_html_e( 'Clear user statuses cache', 'ultimate-member' ); ?>
	</a>
</p>
