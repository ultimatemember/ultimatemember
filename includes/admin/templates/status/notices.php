<div class="um-status-notices">
	<table class="us-notices-table widefat">
		<?php foreach ( $notices as $notice ) { ?>
			<tr class="error">
				<td><?php echo wp_kses( $notice['message'], UM()->get_allowed_html( 'admin_notice' ) ); ?></td>
			</tr>
		<?php } ?>
	</table>
</div>
