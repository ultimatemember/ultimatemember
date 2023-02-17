<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="submitbox" id="submitpost">
	<div id="major-publishing-actions">
		<div id="delete-action" style="float: left;">
			<a class="submitdelete deletion" href="<?php echo add_query_arg( array( 'page' => 'um_fields_groups' ), admin_url( 'admin.php' ) ); ?>"><?php esc_html_e( 'Back to fields groups', 'ultimate-member' ) ?></a>
		</div>

		<div id="publishing-action">
			<input type="submit" value="<?php echo ! empty( $_GET['id'] ) ? esc_attr__( 'Update', 'ultimate-member' ) : esc_attr__( 'Publish', 'ultimate-member' ) ?>" class="button-primary" id="create_fields_group" name="create_fields_group" />
		</div>
		<div class="clear"></div>
	</div>
</div>
