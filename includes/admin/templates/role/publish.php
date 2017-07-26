<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>

<div class="submitbox" id="submitpost">
    <div id="major-publishing-actions">
        <input type="submit" value="<?php echo ! empty( $_GET['id'] ) ? __( 'Update Role', 'ultimate-member' ) : __( 'Create Role', 'ultimate-member' ) ?>" class="button-primary" id="create_role" name="create_role">
        <input type="button" class="cancel_popup button" value="<?php _e( 'Cancel', 'ultimate-member' ) ?>" onclick="window.location = '<?php echo add_query_arg( array( 'page' => 'um_roles' ), admin_url( 'admin.php' ) ) ?>';" />
        <div class="clear"></div>
    </div>
</div>