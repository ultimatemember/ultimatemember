<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$role = $object['data']; ?>

<div class="um-admin-metabox">
	<?php UM()->admin_forms( array(
		'class'		=> 'um-role-publish um-top-label',
		'prefix_id'	=> 'role',
		'fields' => array(
			array(
				'id'		    => '_um_priority',
				'type'		    => 'text',
				'label'    		=> __( 'Role Priority', 'ultimate-member' ),
				'tooltip' 	=> __( 'The higher the number, the higher the priority', 'ultimate-member' ),
				'value'		    => ! empty( $role['_um_priority'] ) ? $role['_um_priority'] : '',
			),
		)
	) )->render_form(); ?>
</div>
<div class="submitbox" id="submitpost">
	<div id="major-publishing-actions">
		<input type="submit" value="<?php echo ! empty( $_GET['id'] ) ? __( 'Update Role', 'ultimate-member' ) : __( 'Create Role', 'ultimate-member' ) ?>" class="button-primary" id="create_role" name="create_role">
		<input type="button" class="cancel_popup button" value="<?php _e( 'Cancel', 'ultimate-member' ) ?>" onclick="window.location = '<?php echo add_query_arg( array( 'page' => 'um_roles' ), admin_url( 'admin.php' ) ) ?>';" />
		<div class="clear"></div>
	</div>
</div>