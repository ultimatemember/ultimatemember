<?php
namespace um\admin\core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\admin\core\Admin_Users' ) ) {

	/**
	 * Class Admin_Users
	 * @package um\admin\core
	 */
	class Admin_Users {

		/**
		 * Admin_Users constructor.
		 */
		public function __construct() {
			//add_action( 'admin_init', array( &$this, 'um_bulk_users_edit' ), 9 );

			//add_action( 'um_admin_user_action_hook', array( &$this, 'user_action_hook' ), 10, 1 );
		}

		/**
		 * Does an action to user asap
		 *
		 * @param string $action
		 */
		public function user_action_hook( $action ) {
			switch ( $action ) {
				default:
					/**
					 * UM hook
					 *
					 * @type action
					 * @title um_admin_custom_hook_{$action}
					 * @description Integration hook on user action
					 * @input_vars
					 * [{"var":"$user_id","type":"int","desc":"User ID"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage add_action( 'um_admin_custom_hook_{$action}', 'function_name', 10, 1 );
					 * @example
					 * <?php
					 * add_action( 'um_admin_custom_hook_{$action}', 'my_admin_custom_hook', 10, 1 );
					 * function my_admin_after_main_notices( $user_id ) {
					 *     // your code here
					 * }
					 * ?>
					 */
					do_action( "um_admin_custom_hook_{$action}", UM()->user()->id );
					break;

				case 'um_delete':
					if ( is_admin() ) {
						wp_die( esc_html__( 'This action is not allowed in backend.', 'ultimate-member' ) );
					}
					UM()->user()->delete();
					break;
			}
		}

		/**
		 * Add UM Bulk actions to Users List Table
		 * @deprecated 2.8.7
		 */
		public function restrict_manage_users() {
			?>
			<div style="float:right;margin:0 4px">

				<label class="screen-reader-text" for="um_bulk_action"><?php _e( 'UM Action', 'ultimate-member' ); ?></label>

				<select name="um_bulk_action[]" id="um_bulk_action" class="" style="width: 200px">
					<option value="0"><?php _e( 'UM Action', 'ultimate-member' ); ?></option>
					<?php echo $this->get_bulk_admin_actions(); ?>
				</select>

				<input name="um_bulkedit" id="um_bulkedit" class="button" value="<?php esc_attr_e( 'Apply', 'ultimate-member' ); ?>" type="submit" />

			</div>

			<?php if ( ! empty( $_REQUEST['um_status'] ) ) { ?>
				<input type="hidden" name="um_status" id="um_status" value="<?php echo esc_attr( sanitize_key( $_REQUEST['um_status'] ) );?>"/>
				<?php
			}
		}

		/**
		 * Bulk user editing actions
		 */
		public function um_bulk_users_edit() {
			// bulk edit users
			if ( ! empty( $_REQUEST['users'] ) && ! empty( $_REQUEST['um_bulkedit'] ) && ! empty( $_REQUEST['um_bulk_action'] ) ) {

				$rolename = UM()->roles()->get_priority_user_role( get_current_user_id() );
				$role     = get_role( $rolename );

				if ( empty( $role ) ) {
					wp_die( esc_html__( 'You do not have enough permissions to do that.', 'ultimate-member' ) );
				}

				if ( ! current_user_can( 'edit_users' ) && ! $role->has_cap( 'edit_users' ) ) {
					wp_die( esc_html__( 'You do not have enough permissions to do that.', 'ultimate-member' ) );
				}

				check_admin_referer( 'bulk-users' );

				$users       = array_map( 'absint', (array) $_REQUEST['users'] );
				$bulk_action = current( array_filter( $_REQUEST['um_bulk_action'] ) );

				foreach ( $users as $user_id ) {
					UM()->user()->set( $user_id );

					/**
					 * UM hook
					 *
					 * @type action
					 * @title um_admin_user_action_hook
					 * @description Action on bulk user action
					 * @input_vars
					 * [{"var":"$bulk_action","type":"string","desc":"Bulk Action"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage add_action( 'um_admin_user_action_hook{$action}', 'function_name', 10, 1 );
					 * @example
					 * <?php
					 * add_action( 'um_admin_user_action_hook', 'my_admin_user_action', 10, 1 );
					 * function my_admin_user_action( $bulk_action ) {
					 *     // your code here
					 * }
					 * ?>
					 */
					do_action( 'um_admin_user_action_hook', $bulk_action );

					/**
					 * UM hook
					 *
					 * @type action
					 * @title um_admin_user_action_{$bulk_action}_hook
					 * @description Action on bulk user action
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage add_action( 'um_admin_user_action_{$bulk_action}_hook', 'function_name', 10 );
					 * @example
					 * <?php
					 * add_action( 'um_admin_user_action_{$bulk_action}_hook', 'my_admin_user_action', 10 );
					 * function my_admin_user_action() {
					 *     // your code here
					 * }
					 * ?>
					 */
					do_action( "um_admin_user_action_{$bulk_action}_hook" );
				}

				$uri = $this->set_redirect_uri( admin_url( 'users.php' ) );
				$uri = add_query_arg( 'update', 'um_users_updated', $uri );

				wp_safe_redirect( $uri );
				exit;
			}

			if ( ! empty( $_REQUEST['um_bulkedit'] ) ) {
				$uri = $this->set_redirect_uri( admin_url( 'users.php' ) );
				wp_safe_redirect( $uri );
				exit;
			}
		}
	}
}
