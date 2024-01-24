<?php
/**
 * Notices template
 *
 * @param array $notices
 *
 * @since 2.8.3
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<p>
	<?php esc_html_e( 'Here you can see all your Ultimate member notices that may cause problems with your site.', 'ultimate-member' ); ?>
</p>
<div class="um-status-notices">
	<table class="wp-list-table widefat fixed striped table-view-list us-notices-table widefat">
		<?php foreach ( $notices as $notice ) { ?>
			<tr class="error">
				<td><?php echo wp_kses( $notice['message'], UM()->get_allowed_html( 'admin_notice' ) ); ?></td>
			</tr>
		<?php } ?>
	</table>
</div>
