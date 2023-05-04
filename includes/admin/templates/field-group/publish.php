<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="submitbox" id="submitpost">
	<div id="major-publishing-actions">
		<div id="delete-action" style="float: left;">
			<a class="submitdelete deletion" href="<?php echo add_query_arg( array( 'page' => 'um_field_groups' ), admin_url( 'admin.php' ) ); ?>"><?php esc_html_e( 'Back to field groups', 'ultimate-member' ) ?></a>
		</div>

		<div id="publishing-action">
			<input type="hidden" name="um_admin_action" value="save_field_group" />
			<input type="submit" value="<?php echo ! empty( $_GET['id'] ) ? esc_attr__( 'Update', 'ultimate-member' ) : esc_attr__( 'Publish', 'ultimate-member' ) ?>" class="button-primary" id="create_field_group" name="create_field_group" />
		</div>
		<div class="clear"></div>
	</div>
</div>
