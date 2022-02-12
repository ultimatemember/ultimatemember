<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>


<p><?php _e( 'Run this task from time to time if you have issues with WP Cron and need to get UM extension updates.', 'ultimate-member' ) ?></p>
<p>
	<a href="<?php echo esc_url( add_query_arg( 'um_adm_action', 'manual_upgrades_request' ) ); ?>" class="button">
		<?php _e( 'Get latest versions', 'ultimate-member' ) ?>
	</a>
</p>