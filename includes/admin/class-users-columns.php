<?php
namespace um\admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\admin\Users_Columns' ) ) {

	/**
	 * Class Users_Columns
	 *
	 * @package um\admin
	 */
	class Users_Columns {

		/**
		 * Users_Columns constructor.
		 */
		public function __construct() {
			add_filter( 'bulk_actions-users', array( &$this, 'add_bulk_actions' ), 10, 1 );
			add_filter( 'handle_bulk_actions-users', array( &$this, 'handle_bulk_actions' ), 10, 3 );
			add_action( 'manage_users_extra_tablenav', array( &$this, 'filter_by_status_action' ), 10, 1 );

			add_filter( 'user_row_actions', array( &$this, 'user_row_actions' ), 10, 2 );

			add_filter( 'users_list_table_query_args', array( &$this, 'hide_by_caps' ), 1, 1 );
			add_action( 'pre_user_query', array( &$this, 'sort_by_newest' ), 10, 1 );
			add_action( 'pre_user_query', array( &$this, 'filter_users_by_status' ), 10, 1 );

			add_filter( 'removable_query_args', array( &$this, 'add_removable_query_args' ), 10, 1 );
		}

		/**
		 * Add query args to list of query variable names to remove.
		 *
		 * @param array $removable_query_args An array of query variable names to remove from a URL
		 *
		 * @return array
		 */
		public function add_removable_query_args( $removable_query_args ) {
			$removable_query_args[] = '_um_wpnonce';
			$removable_query_args[] = 'approved_count';
			$removable_query_args[] = 'rejected_count';
			$removable_query_args[] = 'reactivated_count';
			$removable_query_args[] = 'deactivated_count';
			$removable_query_args[] = 'pending_count';
			$removable_query_args[] = 'resend_activation_count';
			return $removable_query_args;
		}

		/**
		 * Get the list with the bulk actions.
		 *
		 * @return array
		 */
		public function get_user_bulk_actions() {
			// @todo check verified users module for the proper integration. remove old integration way
			$um_actions = apply_filters(
				'um_admin_bulk_user_actions_hook',
				array(
					'um_approve_membership' => __( 'Approve Membership', 'ultimate-member' ),
					'um_reject_membership'  => __( 'Reject Membership', 'ultimate-member' ),
					'um_put_as_pending'     => __( 'Put as Pending Review', 'ultimate-member' ),
					'um_resend_activation'  => __( 'Resend Activation E-mail', 'ultimate-member' ),
					'um_deactivate'         => __( 'Deactivate', 'ultimate-member' ),
					'um_reactivate'         => __( 'Reactivate', 'ultimate-member' ),
				)
			);

			return $um_actions;
		}

		/**
		 * Get the user statuses list.
		 *
		 * @return array
		 */
		public function get_user_statuses() {
			$statuses = apply_filters(
				'um_admin_get_user_statuses',
				array(
					'approved'                    => __( 'Approved', 'ultimate-member' ),
					'awaiting_admin_review'       => __( 'Pending review', 'ultimate-member' ),
					'awaiting_email_confirmation' => __( 'Waiting e-mail confirmation', 'ultimate-member' ),
					'inactive'                    => __( 'Inactive', 'ultimate-member' ),
					'rejected'                    => __( 'Rejected', 'ultimate-member' ),
				)
			);

			return $statuses;
		}

		/**
		 * @param array $actions
		 *
		 * @return array
		 */
		public function add_bulk_actions( $actions ) {
			$rolename = UM()->roles()->get_priority_user_role( get_current_user_id() );
			$role     = get_role( $rolename );

			// Add Ultimate Member bulk actions only when the current user has 'edit_users' capability.
			if ( ! current_user_can( 'edit_users' ) && ! $role->has_cap( 'edit_users' ) ) {
				return $actions;
			}

			$actions[ esc_html__( 'Ultimate Member', 'ultimate-member' ) ] = $this->get_user_bulk_actions();
			return $actions;
		}

		/**
		 * Function for handling custom bulk actions on the Users List Table
		 *
		 * @param string $sendback       URL for redirect after handling bulk action
		 * @param string $current_action Bulk action key
		 * @param array $userids         User IDs
		 *
		 * @return string URL for redirect after handling bulk action
		 */
		public function handle_bulk_actions( $sendback, $current_action, $userids ) {
			$um_actions = $this->get_user_bulk_actions();

			if ( ! array_key_exists( $current_action, $um_actions ) ) {
				return $sendback;
			}

			// need to handle there additional nonce field because WordPress native _wpnonce field isn't visible on the users.php screen then custom actions
			check_admin_referer( 'bulk-users', '_um_wpnonce' );

			$rolename = UM()->roles()->get_priority_user_role( get_current_user_id() );
			$role     = get_role( $rolename );

			// Make Ultimate Member bulk actions only when the current user has 'edit_users' capability.
			if ( ! current_user_can( 'edit_users' ) && ! $role->has_cap( 'edit_users' ) ) {
				wp_die( esc_html__( 'You do not have enough permissions to do that.', 'ultimate-member' ) );
			}

			$users = array_map( 'absint', $userids );

			switch ( $current_action ) {
				case 'um_approve_membership':
					$approved_count = 0;
					foreach ( $users as $user_id ) {
						$res = UM()->common()->user()->approve( $user_id );
						if ( $res ) {
							$approved_count++;
						}
					}

					$sendback = add_query_arg(
						array(
							'approved_count' => $approved_count,
							'update'         => 'um_approved',
						),
						$this->set_redirect_uri( $sendback )
					);
					break;
				case 'um_reactivate':
					$reactivated_count = 0;
					foreach ( $users as $user_id ) {
						$res = UM()->common()->user()->reactivate( $user_id );
						if ( $res ) {
							$reactivated_count++;
						}
					}

					$sendback = add_query_arg(
						array(
							'reactivated_count' => $reactivated_count,
							'update'            => 'um_reactivated',
						),
						$this->set_redirect_uri( $sendback )
					);
					break;
				case 'um_reject_membership':
					$rejected_count = 0;
					foreach ( $users as $user_id ) {
						$res = UM()->common()->user()->reject( $user_id );
						if ( $res ) {
							$rejected_count++;
						}
					}

					$sendback = add_query_arg(
						array(
							'rejected_count' => $rejected_count,
							'update'         => 'um_rejected',
						),
						$this->set_redirect_uri( $sendback )
					);
					break;
				case 'um_deactivate':
					$deactivated_count = 0;
					foreach ( $users as $user_id ) {
						$res = UM()->common()->user()->deactivate( $user_id );
						if ( $res ) {
							$deactivated_count++;
						}
					}

					$sendback = add_query_arg(
						array(
							'deactivated_count' => $deactivated_count,
							'update'            => 'um_deactivate',
						),
						$this->set_redirect_uri( $sendback )
					);
					break;
				case 'um_put_as_pending':
					$pending_count = 0;
					foreach ( $users as $user_id ) {
						$res = UM()->common()->user()->set_as_pending( $user_id );
						if ( $res ) {
							$pending_count++;
						}
					}

					$sendback = add_query_arg(
						array(
							'pending_count' => $pending_count,
							'update'        => 'um_pending',
						),
						$this->set_redirect_uri( $sendback )
					);
					break;
				case 'um_resend_activation':
					$email_pending_count = 0;
					foreach ( $users as $user_id ) {
						$res = UM()->common()->user()->resend_activation( $user_id );
						if ( $res ) {
							$email_pending_count++;
						}
					}

					$sendback = add_query_arg(
						array(
							'resend_activation_count' => $email_pending_count,
							'update'                  => 'um_resend_activation',
						),
						$this->set_redirect_uri( $sendback )
					);
					break;
				default:
					// hook for the handling custom UM actions added via 'um_admin_bulk_user_actions_hook' hook
					$sendback = apply_filters( "um_handle_bulk_actions-users-{$current_action}", $sendback, $userids );
					break;
			}

			return $sendback;
		}

		/**
		 * Adds HTML with the filter by the Ultimate Member status.
		 *
		 * @param string $which Where the callback's hook fired.
		 */
		public function filter_by_status_action( $which ) {
			$id        = 'bottom' === $which ? 'um_status2' : 'um_status';
			$button_id = 'bottom' === $which ? 'um_filter_action2' : 'um_filter_action';

			if ( 'top' === $which ) {
				// need to add there additional nonce field because WordPress native _wpnonce field isn't visible on the users.php screen then custom actions
				wp_nonce_field('bulk-users', '_um_wpnonce', false );
			}

			// Set default statuses if not already done.
			UM()->install()->set_default_user_status();

			$statuses = $this->get_user_statuses();
			?>
			<div class="alignleft actions">
				<label class="screen-reader-text" for="<?php echo esc_attr( $id ); ?>"><?php _e( 'All Statuses', 'ultimate-member' ); ?></label>
				<select name="<?php echo esc_attr( $id ); ?>" id="<?php echo esc_attr( $id ); ?>">
					<option value=""><?php esc_html_e( 'All Statuses', 'ultimate-member' ); ?></option>
					<?php
					foreach ( $statuses as $k => $v ) {
						$selected = isset( $_GET['um_status'] ) && $k === sanitize_key( $_GET['um_status'] );
						?>
						<option value="<?php echo esc_attr( $k ) ?>" <?php selected( $selected ) ?>><?php echo esc_html( $v ); ?></option>
						<?php
					}
					?>
				</select>
				<?php submit_button( __( 'Filter', 'ultimate-member' ), '', $button_id, false ); ?>
			</div>
			<?php
		}

		/**
		 * Custom row actions for users page
		 *
		 * @param array $actions
		 * @param $user_object \WP_User
		 * @return array
		 */
		public function user_row_actions( $actions, $user_object ) {
			$user_id = $user_object->ID;

			// Link to Ultimate Member Profile.
			$actions['frontend_profile'] = '<a href="' . esc_url( um_user_profile_url( $user_id ) ) . '">' . esc_html__( 'View profile', 'ultimate-member' ) . '</a>';

			// The link for open popup with the registration data submitted through Ultimate Member Registration form.
			$submitted = get_user_meta( $user_id, 'submitted', true );
			if ( ! empty( $submitted ) ) {
				$actions['view_info'] = '<a href="#" class="um-preview-registration" data-user_id="' . esc_attr( $user_id ) . '">' . esc_html__( 'Info', 'ultimate-member' ) . '</a>';
			}

			// Remove row actions for now Administrator role and who cannot view profiles of row's user.
			// @todo make the um_can_view_profile() function review. Maybe rewrite it.
			if ( ! current_user_can( 'administrator' ) ) {
				if ( ! um_can_view_profile( $user_id ) ) {
					unset( $actions['frontend_profile'] );
					unset( $actions['view_info'] );
					unset( $actions['view'] );
				}
			}

			/**
			 * Filters the rows actions for the user in wp-admin > Users List Table screen.
			 *
			 * Note: Row actions format is 'key' => 'action_link_html'
			 *
			 * @since 3.0.0
			 * @hook  um_admin_user_row_actions
			 *
			 * @param {array} $actions User's row actions.
			 * @param {int}   $user_id Row's user ID.
			 *
			 * @return {array} User's row actions.
			 */
			$actions = apply_filters( 'um_admin_user_row_actions', $actions, $user_id );
			return $actions;
		}

		/**
		 * Hide users who are hidden by role access for not Administrator user
		 *
		 * @param array $args
		 * @return array
		 */
		public function hide_by_caps( $args ) {
			if ( current_user_can( 'administrator' ) ) {
				return $args;
			}

			// @todo avoid um_user() function using
			$can_view_roles = um_user( 'can_view_roles' );
			if ( um_user( 'can_view_all' ) && ! empty( $can_view_roles ) ) {
				$args['role__in'] = $can_view_roles;
			}

			return $args;
		}

		/**
		 * Change default sorting at WP Users list table
		 *
		 * @param \WP_User_Query $query
		 */
		public function sort_by_newest( $query ) {
			global $pagenow;

			if ( is_admin() && 'users.php' === $pagenow ) {
				if ( ! isset( $_REQUEST['orderby'] ) ) {
					$query->query_vars['order'] = 'desc';
					$query->query_orderby       = ' ORDER BY user_registered ' . ( 'desc' === $query->query_vars['order'] ? 'desc ' : 'asc ' ); //set sort order
				}
			}
		}

		/**
		 * Filter WP users by UM Status
		 *
		 * @param \WP_User_Query $query
		 */
		public function filter_users_by_status( $query ) {
			global $wpdb, $pagenow;

			if ( is_admin() && 'users.php' === $pagenow && ! empty( $_REQUEST['um_status'] ) ) {
				$status = sanitize_key( $_REQUEST['um_status'] );

				$skip_status_filter = apply_filters( 'um_skip_filter_users_by_status', false, $status );
				if ( ! $skip_status_filter ) {
					$query->query_where = str_replace(
						'WHERE 1=1',
						"WHERE 1=1 AND {$wpdb->users}.ID IN (
                                 SELECT {$wpdb->usermeta}.user_id FROM $wpdb->usermeta
                                    WHERE {$wpdb->usermeta}.meta_key = 'account_status'
                                    AND {$wpdb->usermeta}.meta_value = '{$status}')",
						$query->query_where
					);
				}
			}
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

//				case 'um_put_as_pending':
//					UM()->user()->pending();
//					break;

//				case 'um_approve_membership':
//				case 'um_reenable':
//
//					add_filter( 'um_template_tags_patterns_hook', array( UM()->password(), 'add_placeholder' ), 10, 1 );
//					add_filter( 'um_template_tags_replaces_hook', array( UM()->password(), 'add_replace_placeholder' ), 10, 1 );
//
//					UM()->user()->approve();
//					break;

//				case 'um_reject_membership':
//					UM()->user()->reject();
//					break;

//				case 'um_resend_activation':
//
//					add_filter( 'um_template_tags_patterns_hook', array( UM()->user(), 'add_activation_placeholder' ), 10, 1 );
//					add_filter( 'um_template_tags_replaces_hook', array( UM()->user(), 'add_activation_replace_placeholder' ), 10, 1 );
//
//					UM()->user()->email_pending();
//					break;

//				case 'um_deactivate':
//					UM()->user()->deactivate();
//					break;

				case 'um_delete':
					if ( is_admin() ) {
						wp_die( __( 'This action is not allowed in backend.', 'ultimate-member' ) );
					}
					UM()->user()->delete();
					break;
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
				$uri = add_query_arg( 's', sanitize_text_field( $_REQUEST['s'] ), $uri );
			}

			if ( ! empty( $_REQUEST['um_status'] ) ) {
				$uri = add_query_arg( 'um_status', sanitize_key( $_REQUEST['um_status'] ), $uri );
			}

			return $uri;
		}
	}
}
