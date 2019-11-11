<?php
namespace um\admin\core;


if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'um\admin\core\Admin_Users' ) ) {


	/**
	 * Class Admin_Users
	 * @package um\admin\core
	 */
	class Admin_Users {


		/**
		 * Admin_Users constructor.
		 */
		function __construct() {

			$this->custom_role = 'um_role';

			add_action( 'restrict_manage_users', array( &$this, 'restrict_manage_users' ) );

			add_filter( 'user_row_actions', array( &$this, 'user_row_actions' ), 10, 2 );

			add_filter( 'pre_user_query', array( &$this, 'sort_by_newest' ) );

			add_filter( 'pre_user_query', array( &$this, 'filter_users_by_status' ) );

			add_filter( 'views_users', array( &$this, 'add_status_links' ) );

			add_action( 'admin_init', array( &$this, 'um_bulk_users_edit' ), 9 );

			add_action( 'um_admin_user_action_hook', array( &$this, 'user_action_hook' ), 10, 1 );
		}


		/**
		 * Does an action to user asap
		 *
		 * @param string $action
		 */
		function user_action_hook( $action ) {
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

				case 'um_put_as_pending':
					UM()->user()->pending();
					break;

				case 'um_approve_membership':
				case 'um_reenable':

					add_filter( 'um_template_tags_patterns_hook', array( UM()->password(), 'add_placeholder' ), 10, 1 );
					add_filter( 'um_template_tags_replaces_hook', array( UM()->password(), 'add_replace_placeholder' ), 10, 1 );

					UM()->user()->approve();
					break;

				case 'um_reject_membership':
					UM()->user()->reject();
					break;

				case 'um_resend_activation':

					add_filter( 'um_template_tags_patterns_hook', array( UM()->user(), 'add_activation_placeholder' ), 10, 1 );
					add_filter( 'um_template_tags_replaces_hook', array( UM()->user(), 'add_activation_replace_placeholder' ), 10, 1 );

					UM()->user()->email_pending();
					break;

				case 'um_deactivate':
					UM()->user()->deactivate();
					break;

				case 'um_delete':
					if ( is_admin() ) {
						wp_die( 'This action is not allowed in backend.', 'ultimate-member' );
					}
					UM()->user()->delete();
					break;
			}
		}


		/**
		 * Add UM Bulk actions to Users List Table
		 *
		 */
		function restrict_manage_users() { ?>
			<div style="float:right;margin:0 4px">

				<label class="screen-reader-text" for="um_bulk_action"><?php _e( 'UM Action', 'ultimate-member' ); ?></label>

				<select name="um_bulk_action[]" id="um_bulk_action" class="" style="width: 200px">
					<option value="0"><?php _e( 'UM Action', 'ultimate-member' ); ?></option>
					<?php echo $this->get_bulk_admin_actions(); ?>
				</select>

				<input name="um_bulkedit" id="um_bulkedit" class="button" value="<?php esc_attr_e( 'Apply', 'ultimate-member' ); ?>" type="submit" />

			</div>

			<?php if ( ! empty( $_REQUEST['status'] ) ) { ?>
				<input type="hidden" name="status" id="um_status" value="<?php echo esc_attr( $_REQUEST['status'] );?>"/>
			<?php }
		}


		/**
		 * Get UM bulk actions HTML
		 *
		 * @return string
		 */
		function get_bulk_admin_actions() {

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_admin_bulk_user_actions_hook
			 * @description Admin Users List Table bulk actions
			 * @input_vars
			 * [{"var":"$actions","type":"array","desc":"User List Table bulk actions"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_admin_bulk_user_actions_hook', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_filter( 'um_admin_bulk_user_actions_hook', 'my_admin_bulk_user_actions', 10, 1 );
			 * function my_admin_bulk_user_actions( $actions ) {
			 *     // your code here
			 *     $actions['my-custom-bulk'] = array(
			 *         'label' => 'My Custom Bulk Action'
			 *     );
			 *     return $actions;
			 * }
			 * ?>
			 */
			$actions = apply_filters( 'um_admin_bulk_user_actions_hook', array(
				'um_approve_membership' => array(
					'label' => __( 'Approve Membership', 'ultimate-member' )
				),
				'um_reject_membership'  => array(
					'label' => __( 'Reject Membership', 'ultimate-member' )
				),
				'um_put_as_pending'     => array(
					'label' => __( 'Put as Pending Review', 'ultimate-member' )
				),
				'um_resend_activation'  => array(
					'label' => __( 'Resend Activation E-mail', 'ultimate-member' )
				),
				'um_deactivate'         => array(
					'label' => __( 'Deactivate', 'ultimate-member' )
				),
				'um_reenable'           => array(
					'label' => __( 'Reactivate', 'ultimate-member' )
				)
			) );

			$output = '';
			foreach ( $actions as $id => $action_data ) {
				$output .= '<option value="' . $id . '" '. disabled( isset( $arr['disabled'] ), true, false ) . '>' . $action_data['label'] . '</option>';
			}
			return $output;
		}


		/**
		 * Custom row actions for users page
		 *
		 * @param array $actions
		 * @param $user_object \WP_User
		 * @return array
		 */
		function user_row_actions( $actions, $user_object ) {
			$user_id = $user_object->ID;


			$actions['frontend_profile'] = "<a class='' href='" . um_user_profile_url( $user_id ) . "'>" . __( 'View profile', 'ultimate-member' ) . "</a>";

			$submitted = get_user_meta( $user_id, 'submitted', true );
			if ( ! empty( $submitted ) )
				$actions['view_info'] = '<a href="javascript:void(0);" data-modal="UM_preview_registration" data-modal-size="smaller" 
				data-dynamic-content="um_admin_review_registration" data-arg1="' . esc_attr( $user_id ) . '" data-arg2="edit_registration">' . __( 'Info', 'ultimate-member' ) . '</a>';

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_admin_user_row_actions
			 * @description Admin views array
			 * @input_vars
			 * [{"var":"$actions","type":"array","desc":"User List Table actions"},
			 * {"var":"$user_id","type":"int","desc":"User ID"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_admin_user_row_actions', 'function_name', 10, 2 );
			 * @example
			 * <?php
			 * add_filter( 'um_admin_user_row_actions', 'my_admin_user_row_actions', 10, 2 );
			 * function my_admin_user_row_actions( $actions, $user_id ) {
			 *     // your code here
			 *     return $actions;
			 * }
			 * ?>
			 */
			$actions = apply_filters( 'um_admin_user_row_actions', $actions, $user_id );

			return $actions;
		}


		/**
		 * Change default sorting at WP Users list table
		 *
		 * @param $query
		 * @return mixed
		 */
		function sort_by_newest( $query ) {
			global $pagenow;

			if ( is_admin() && $pagenow == 'users.php' ) {
				if ( ! isset( $_REQUEST['orderby'] ) ) {
					$query->query_vars["order"] = 'desc';
					$query->query_orderby = " ORDER BY user_registered " . ( $query->query_vars["order"] == "desc" ? "desc " : "asc " ); //set sort order
				}
			}

			return $query;
		}


		/**
		 * Filter WP users by UM Status
		 *
		 * @param $query
		 * @return mixed
		 */
		function filter_users_by_status( $query ) {
			global $wpdb, $pagenow;

			if ( is_admin() && $pagenow == 'users.php' && ! empty( $_GET['status'] ) ) {

				$status = urldecode( $_GET['status'] );

				if ( $status == 'needs-verification' ) {
					$query->query_where = str_replace('WHERE 1=1',
						"WHERE 1=1 AND {$wpdb->users}.ID IN (
                                 SELECT {$wpdb->usermeta}.user_id FROM $wpdb->usermeta
                                    WHERE {$wpdb->usermeta}.meta_key = '_um_verified'
                                    AND {$wpdb->usermeta}.meta_value = 'pending')",
						$query->query_where
					);
				} else {
					$query->query_where = str_replace('WHERE 1=1',
						"WHERE 1=1 AND {$wpdb->users}.ID IN (
                                 SELECT {$wpdb->usermeta}.user_id FROM $wpdb->usermeta
                                    WHERE {$wpdb->usermeta}.meta_key = 'account_status'
                                    AND {$wpdb->usermeta}.meta_value = '{$status}')",
						$query->query_where
					);
				}

			}

			return $query;
		}


		/**
		 * Add status links to WP Users List Table
		 *
		 * @param $views
		 * @return array|mixed|void
		 */
		function add_status_links( $views ) {
			remove_filter( 'pre_user_query', array( &$this, 'filter_users_by_status' ) );

			$old_views = $views;
			$views     = array();

			if ( ! isset( $_REQUEST['role'] ) && ! isset( $_REQUEST['status'] ) ) {
				$views['all'] = '<a href="' . admin_url( 'users.php' ) . '" class="current">All <span class="count">(' . UM()->query()->count_users() . ')</span></a>';
			} else {
				$views['all'] = '<a href="' . admin_url( 'users.php' ) . '">All <span class="count">(' . UM()->query()->count_users() . ')</span></a>';
			}

			$status = array(
				'approved'                      => __( 'Approved', 'ultimate-member' ),
				'awaiting_admin_review'         => __( 'Pending review', 'ultimate-member' ),
				'awaiting_email_confirmation'   => __( 'Waiting e-mail confirmation', 'ultimate-member' ),
				'inactive'                      => __( 'Inactive', 'ultimate-member' ),
				'rejected'                      => __( 'Rejected', 'ultimate-member' )
			);

			UM()->query()->count_users_by_status( 'unassigned' );

			foreach ( $status as $k => $v ) {
				if ( isset( $_REQUEST['status'] ) && $_REQUEST['status'] == $k ) {
					$current = 'class="current"';
				} else {
					$current = '';
				}

				$views[ $k ] = '<a href="' . esc_url( admin_url( 'users.php' ) . '?status=' . $k ) . '" ' . $current . '>'. $v . ' <span class="count">('.UM()->query()->count_users_by_status( $k ).')</span></a>';
			}

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_admin_views_users
			 * @description Admin views array
			 * @input_vars
			 * [{"var":"$views","type":"array","desc":"User Views"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_admin_views_users', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_filter( 'um_admin_views_users', 'my_admin_views_users', 10, 1 );
			 * function my_admin_views_users( $views ) {
			 *     // your code here
			 *     return $views;
			 * }
			 * ?>
			 */
			$views = apply_filters( 'um_admin_views_users', $views );

			// remove all filters
			unset( $old_views['all'] );

			// add separator
			$views['subsep'] = '<span></span>';

			// merge views
			foreach ( $old_views as $key => $view ) {
				$views[ $key ] = $view;
			}

			return $views;
		}


		/**
		 * Bulk user editing actions
		 */
		function um_bulk_users_edit() {
			// bulk edit users
			if ( ! empty( $_REQUEST['users'] ) && ! empty( $_REQUEST['um_bulkedit'] ) && ! empty( $_REQUEST['um_bulk_action'] ) ) {

				$rolename = UM()->roles()->get_priority_user_role( get_current_user_id() );
				$role = get_role( $rolename );

				if ( ! current_user_can( 'edit_users' ) && ! $role->has_cap( 'edit_users' )  ) {
					wp_die( __( 'You do not have enough permissions to do that.', 'ultimate-member' ) );
				}

				check_admin_referer( 'bulk-users' );

				$users = $_REQUEST['users'];
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
					do_action( "um_admin_user_action_hook", $bulk_action );

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

				// Finished. redirect now
				//if ( $admin_err == 0 ) {

				$uri = $this->set_redirect_uri( admin_url( 'users.php' ) );
				$uri = add_query_arg( 'update', 'users_updated', $uri );

				wp_redirect( $uri );
				exit;

				/*} else {
					wp_redirect( admin_url( 'users.php?update=err_users_updated' ) );
					exit;
				}*/

			} elseif ( ! empty( $_REQUEST['um_bulkedit'] ) ) {

				$uri = $this->set_redirect_uri( admin_url( 'users.php' ) );
				wp_redirect( $uri );
				exit;

			}
		}


		/**
		 * Sets redirect URI after bulk action
		 *
		 * @param string $uri
		 * @return string
		 */
		function set_redirect_uri( $uri ) {

			if ( ! empty( $_REQUEST['s'] ) ) {
				$uri = add_query_arg( 's', $_REQUEST['s'], $uri );
			}

			if ( ! empty( $_REQUEST['status'] ) ) {
				$uri = add_query_arg( 'status', $_REQUEST['status'], $uri );
			}

			return $uri;

		}

	}
}