<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$count = $wpdb->get_var(
	"SELECT COUNT( option_id ) 
	FROM {$wpdb->options} 
	WHERE option_name LIKE 'um_cache_userdata_%'"
);
?>

<p><?php _e( 'Run this task from time to time to keep your DB clean.', 'ultimate-member' ) ?></p>

<p>
	<a href="<?php echo esc_url( add_query_arg( 'um_adm_action', 'user_cache' ) ); ?>" class="button">
		<?php echo esc_html( sprintf( __( 'Clear cache of %s users', 'ultimate-member' ), $count ) ); ?>
	</a>
	<a href="<?php echo esc_url( add_query_arg( 'um_adm_action', 'user_status_cache' ) ); ?>" class="button">
		<?php esc_html_e( 'Clear user statuses cache', 'ultimate-member' ); ?>
	</a>
</p>
