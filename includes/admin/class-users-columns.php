<?php
namespace um\admin;

use WP_User;
use WP_User_Query;

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
			add_filter( 'manage_users_columns', array( &$this, 'manage_users_columns' ) );
			add_filter( 'manage_users_custom_column', array( &$this, 'manage_users_custom_column' ), 10, 3 );

			add_action( 'pre_user_query', array( &$this, 'sort_by_newest' ) );
			add_filter( 'users_list_table_query_args', array( &$this, 'hide_by_caps' ), 1 );
			add_filter( 'views_users', array( &$this, 'restrict_role_links' ) );

			add_filter( 'user_row_actions', array( &$this, 'user_row_actions' ), 10, 2 );
			add_filter( 'bulk_actions-users', array( &$this, 'add_bulk_actions' ) );
			add_filter( 'handle_bulk_actions-users', array( &$this, 'handle_bulk_actions' ), 10, 3 );

			add_action( 'manage_users_extra_tablenav', array( &$this, 'add_status_filter' ) );
			add_action( 'pre_user_query', array( &$this, 'filter_users_by_status' ) );

			add_filter( 'removable_query_args', array( &$this, 'add_removable_query_args' ) );
		}

		/**
		 * Filter: Add column 'Status'
		 *
		 * @param array $columns
		 *
		 * @return array
		 */
		public function manage_users_columns( $columns ) {
			$columns['um_account_status'] = __( 'Status', 'ultimate-member' );
			return $columns;
		}

		/**
		 * Filter: Show column 'Status'
		 *
		 * @param string $value
		 * @param string $column_name
		 * @param int    $user_id
		 *
		 * @return string
		 */
		public function manage_users_custom_column( $value, $column_name, $user_id ) {
			if ( 'um_account_status' !== $column_name ) {
				return $value;
			}

			$status = UM()->common()->users()->get_status( $user_id, 'formatted' );

			$status = apply_filters( 'um_users_column_account_status', $status, $user_id );

			$value = '<span class="um-user-status">' . esc_html( $status ) . '</span>';

			if ( get_current_user_id() === $user_id ) {
				return $value;
			}

			$row_actions = array();
			if ( UM()->common()->users()->can_be_approved( $user_id ) ) {
				$url = add_query_arg(
					array(
						'um_adm_action' => 'approve_user',
						'uid'           => $user_id,
						'_wpnonce'      => wp_create_nonce( 'approve_user' . $user_id ),
					),
					admin_url( 'users.php' )
				);

				$row_actions[] = '<a href="' . esc_url( $url ) . '" class="um-set-status-approved">' . esc_html__( 'Approve', 'ultimate-member' ) . '</a>';
			}
			if ( UM()->common()->users()->can_be_rejected( $user_id ) ) {
				$url = add_query_arg(
					array(
						'um_adm_action' => 'reject_user',
						'uid'           => $user_id,
						'_wpnonce'      => wp_create_nonce( 'reject_user' . $user_id ),
					),
					admin_url( 'users.php' )
				);

				$row_actions[] = '<a href="' . esc_url( $url ) . '" class="um-set-status-rejected" onclick="return confirm( \'' . esc_js( __( 'Are you sure you want to reject this user membership?', 'ultimate-member' ) ) . '\' );">' . esc_html__( 'Reject', 'ultimate-member' ) . '</a>';
			}
			if ( UM()->common()->users()->can_be_reactivated( $user_id ) ) {
				$url = add_query_arg(
					array(
						'um_adm_action' => 'reactivate_user',
						'uid'           => $user_id,
						'_wpnonce'      => wp_create_nonce( 'reactivate_user' . $user_id ),
					),
					admin_url( 'users.php' )
				);

				$row_actions[] = '<a href="' . esc_url( $url ) . '" class="um-reactivate-user">' . esc_html__( 'Reactivate', 'ultimate-member' ) . '</a>';
			}
			if ( UM()->common()->users()->can_be_set_as_pending( $user_id ) ) {
				$url = add_query_arg(
					array(
						'um_adm_action' => 'put_user_as_pending',
						'uid'           => $user_id,
						'_wpnonce'      => wp_create_nonce( 'put_user_as_pending' . $user_id ),
					),
					admin_url( 'users.php' )
				);

				$row_actions[] = '<a href="' . esc_url( $url ) . '" class="um-set-status-pending">' . esc_html__( 'Put as pending', 'ultimate-member' ) . '</a>';
			}
			if ( UM()->common()->users()->can_activation_send( $user_id ) ) {
				$url = add_query_arg(
					array(
						'um_adm_action' => 'resend_user_activation',
						'uid'           => $user_id,
						'_wpnonce'      => wp_create_nonce( 'resend_user_activation' . $user_id ),
					),
					admin_url( 'users.php' )
				);

				$title = __( 'Send activation email', 'ultimate-member' );
				if ( UM()->common()->users()->has_status( $user_id, 'awaiting_email_confirmation' ) ) {
					$title = __( 'Resend activation email', 'ultimate-member' );
				}

				$row_actions[] = '<a href="' . esc_url( $url ) . '" class="um-resend-activation-email">' . esc_html( $title ) . '</a>';
			}
			if ( UM()->common()->users()->can_be_deactivated( $user_id ) ) {
				$url = add_query_arg(
					array(
						'um_adm_action' => 'deactivate_user',
						'uid'           => $user_id,
						'_wpnonce'      => wp_create_nonce( 'deactivate_user' . $user_id ),
					),
					admin_url( 'users.php' )
				);

				$row_actions[] = '<a href="' . esc_url( $url ) . '" class="um-deactivate-user" onclick="return confirm( \'' . esc_js( __( 'Are you sure you want to deactivate this user?', 'ultimate-member' ) ) . '\' );">' . esc_html__( 'Deactivate', 'ultimate-member' ) . '</a>';
			}

			$row_actions = apply_filters( 'um_users_column_account_status_row_actions', $row_actions, $user_id );
			if ( ! empty( $row_actions ) ) {
				$value .= '<div class="row-actions"><ul class="um-user-status-row-actions"><li>' . implode( '</li><li> | </li><li>', $row_actions ) . '</li></ul></div>';
			}
			return $value;
		}

		/**
		 * Change default sorting at WP Users list table
		 *
		 * @param WP_User_Query $query Current instance of WP_User_Query (passed by reference).
		 */
		public function sort_by_newest( $query ) {
			global $pagenow;

			// phpcs:ignore WordPress.Security.NonceVerification -- situated in WP native query and just checking sorting
			if ( 'users.php' === $pagenow && ! isset( $_REQUEST['orderby'] ) && is_admin() ) {
				$query->query_vars['order'] = 'desc';
				$query->query_orderby       = ' ORDER BY user_registered DESC';
			}
		}

		/**
		 * Hide users who are hidden by role access for not Administrator user
		 *
		 * @param array $args Arguments passed to WP_User_Query to retrieve items for the current
		 *                    users list table
		 *
		 * @return array
		 */
		public function hide_by_caps( $args ) {
			if ( current_user_can( 'manage_options' ) ) {
				return $args;
			}

			// @todo avoid um_user() function using
			// @todo check another restrictions not only the role settings. We need to exclude users per user ID.
			$can_view_roles = um_user( 'can_view_roles' );
			if ( ! empty( $can_view_roles ) && um_user( 'can_view_all' ) ) {
				$args['role__in'] = $can_view_roles;
			}

			return $args;
		}

		/**
		 * Hide role filters with not accessible roles
		 *
		 * @param array $views
		 * @return array
		 */
		public function restrict_role_links( $views ) {
			if ( current_user_can( 'manage_options' ) ) {
				return $views;
			}

			$can_view_roles = um_user( 'can_view_roles' );
			if ( ! empty( $can_view_roles ) && um_user( 'can_view_all' ) ) {
				$wp_roles = wp_roles();
				foreach ( $wp_roles->get_names() as $this_role => $name ) {
					if ( ! in_array( $this_role, $can_view_roles, true ) ) {
						unset( $views[ $this_role ] );
					}
				}
			}

			return $views;
		}

		/**
		 * Custom row actions for users page
		 *
		 * @param array   $actions
		 * @param WP_User $user_object
		 *
		 * @return array
		 */
		public function user_row_actions( $actions, $user_object ) {
			$user_id = $user_object->ID;

			// Link to Ultimate Member Profile.
			$actions['frontend_profile'] = '<a href="' . esc_url( um_user_profile_url( $user_id ) ) . '">' . esc_html__( 'View profile', 'ultimate-member' ) . '</a>';

			// The link for open popup with the registration data submitted through Ultimate Member Registration form.
			$submitted = get_user_meta( $user_id, 'submitted', true );
			if ( ! empty( $submitted ) ) {
				$actions['view_info'] = '<a href="#" data-modal="UM_preview_registration" data-modal-size="smaller"
				data-dynamic-content="um_admin_review_registration" data-arg1="' . esc_attr( $user_id ) . '" data-arg2="edit_registration">' . esc_html__( 'Info', 'ultimate-member' ) . '</a>';
				// For new modal below.
				// $actions['view_info'] = '<a href="#" class="um-preview-registration" data-user_id="' . esc_attr( $user_id ) . '">' . esc_html__( 'Info', 'ultimate-member' ) . '</a>';
			}

			// Remove row actions for now Administrator role and who cannot view profiles of row's user.
			if ( ! current_user_can( 'manage_options' ) && ! um_can_view_profile( $user_id ) ) {
				unset( $actions['frontend_profile'], $actions['view_info'], $actions['view'] );
			}

			/**
			 * Filters the rows actions for the user in wp-admin > Users List Table screen.
			 *
			 * Note: Row actions format is 'key' => 'action_link_html'
			 *
			 * @since 1.3.x
			 * @hook  um_admin_user_row_actions
			 *
			 * @param {array} $actions User's row actions.
			 * @param {int}   $user_id Row's user ID.
			 *
			 * @return {array} User's row actions.
			 */
			return apply_filters( 'um_admin_user_row_actions', $actions, $user_id );
		}

		/**
		 * Get the list with the bulk actions.
		 *
		 * @return array
		 */
		private function get_user_bulk_actions() {
			$um_actions = array(
				'um_approve_membership' => __( 'Approve Membership', 'ultimate-member' ),
				'um_reject_membership'  => __( 'Reject Membership', 'ultimate-member' ),
				'um_put_as_pending'     => __( 'Put as Pending Review', 'ultimate-member' ),
				'um_resend_activation'  => __( 'Resend Activation E-mail', 'ultimate-member' ),
				'um_deactivate'         => __( 'Deactivate', 'ultimate-member' ),
				'um_reactivate'         => __( 'Reactivate', 'ultimate-member' ), // um_reenable
			);
			/**
			 * Filters wp-admin > Users List Table bulk actions.
			 *
			 * @since 1.3.x
			 * @since 2.8.7 changed format from `$action_slug => array( 'label' => $action_title )` to `$action_slug => $action_title`
			 * @hook um_admin_bulk_user_actions_hook
			 *
			 * @param {array} $um_actions Users admin actions.
			 *
			 * @return {array} Users admin actions.
			 *
			 * @example <caption>Add `$action_title` to Users List Table bulk actions.</caption>
			 * function um_custom_admin_bulk_user_actions_hook( $um_actions ) {
			 *     $um_actions[ $action_slug ] = $action_title;
			 *     return $um_actions;
			 * }
			 * add_filter( 'um_admin_bulk_user_actions_hook', 'um_custom_admin_bulk_user_actions_hook' );
			 */
			return apply_filters( 'um_admin_bulk_user_actions_hook', $um_actions );
		}

		/**
		 * @param array $actions
		 *
		 * @return array
		 */
		public function add_bulk_actions( $actions ) {
			$rolename = UM()->roles()->get_priority_user_role( get_current_user_id() );
			$role     = get_role( $rolename );

			if ( null === $role ) {
				return $actions;
			}

			// Add Ultimate Member bulk actions only when the current user has 'edit_users' capability.
			if ( ! current_user_can( 'edit_users' ) && ! $role->has_cap( 'edit_users' ) ) {
				return $actions;
			}

			$actions[ esc_html__( 'Ultimate Member', 'ultimate-member' ) ] = $this->get_user_bulk_actions();
			return $actions;
		}

		private function get_statuses_filter_options() {
			$statuses = UM()->common()->users()->statuses_list();
			/**
			 * Filters the user statuses added via Ultimate Member plugin.
			 *
			 * Note: Statuses format is 'key' => 'title'
			 *
			 * @since 2.8.7
			 * @hook  um_user_statuses_admin_filter_options
			 *
			 * @param {array} $statuses User statuses in Ultimate Member environment.
			 *
			 * @return {array} User statuses.
			 */
			return apply_filters( 'um_user_statuses_admin_filter_options', $statuses );
		}

		/**
		 * Adds HTML with the filter by the Ultimate Member status.
		 *
		 * @param string $which Where the callback's hook fired.
		 */
		public function add_status_filter( $which ) {
			if ( 'top' !== $which ) {
				return;
			}

			// Set default statuses if not already done.
			UM()->setup()->set_default_user_status();

			$id = 'um_user_status';

			// need to add there additional nonce field because WordPress native _wpnonce field isn't visible on the users.php screen then custom actions
			wp_nonce_field( 'um-bulk-users', '_um_wpnonce', false );

			$statuses = $this->get_statuses_filter_options();
			?>
			<div class="alignleft actions um-filter-by-status">
				<label class="screen-reader-text" for="<?php echo esc_attr( $id ); ?>"><?php esc_html_e( 'All Statuses', 'ultimate-member' ); ?></label>
				<select name="<?php echo esc_attr( $id ); ?>" id="<?php echo esc_attr( $id ); ?>">
					<option value=""><?php esc_html_e( 'All Statuses', 'ultimate-member' ); ?></option>
					<?php
					foreach ( $statuses as $k => $v ) {
						$selected = isset( $_GET[ $id ] ) && sanitize_key( $_GET[ $id ] ) === $k; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- native WordPress nonce is used
						?>
						<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $selected ); ?>><?php echo esc_html( $v ); ?></option>
						<?php
					}
					?>
				</select>
				<?php submit_button( __( 'Filter', 'ultimate-member' ), '', 'um_filter_users', false ); ?>
			</div>
			<?php
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
			check_admin_referer( 'um-bulk-users', '_um_wpnonce' );

			$rolename = UM()->roles()->get_priority_user_role( get_current_user_id() );
			$role     = get_role( $rolename );

			if ( null === $role ) {
				return $sendback;
			}

			// Make Ultimate Member bulk actions only when the current user has 'edit_users' capability.
			if ( ! current_user_can( 'edit_users' ) && ! $role->has_cap( 'edit_users' ) ) {
				wp_die( esc_html__( 'You do not have enough permissions to do that.', 'ultimate-member' ) );
			}

			$users = array_map( 'absint', $userids );
			$users = array_diff( $users, array( get_current_user_id() ) ); // cannot make any action related to himself.

			switch ( $current_action ) {
				case 'um_approve_membership':
					$approved_count = 0;
					foreach ( $users as $user_id ) {
						$res = UM()->common()->users()->approve( $user_id );
						if ( $res ) {
							++$approved_count;
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
						$res = UM()->common()->users()->reactivate( $user_id );
						if ( $res ) {
							++$reactivated_count;
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
						$res = UM()->common()->users()->reject( $user_id );
						if ( $res ) {
							++$rejected_count;
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
						$res = UM()->common()->users()->deactivate( $user_id );
						if ( $res ) {
							++$deactivated_count;
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
						$res = UM()->common()->users()->set_as_pending( $user_id );
						if ( $res ) {
							++$pending_count;
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
						$res = UM()->common()->users()->send_activation( $user_id, true );
						if ( $res ) {
							++$email_pending_count;
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
					/**
					 * Fires when a custom Ultimate Member bulk action for wp-admin > Users list table should be handled.
					 *
					 * The redirect link should be modified with success or failure feedback
					 * from the action to be used to display feedback to the user.
					 *
					 * The dynamic portion of the hook name, `$current_action`, refers to the current bulk action.
					 * Use together with custom actions added via `um_admin_bulk_user_actions_hook` hook.
					 *
					 * @param {string} $sendback The redirect URL.
					 * @param {array}  $userids  Selected users in bulk action.
					 *
					 * @return {string} The redirect URL.
					 *
					 * @since 2.8.7
					 * @hook um_handle_bulk_actions-users-{$current_action}
					 *
					 * @example <caption>Handle custom-action and set redirect after it.</caption>
					 * function um_custom_bulk_actions_users( $sendback, $userids ) {
					 *     foreach ( $userids as $user_id ) {
					 *         // make some action here
					 *     }
					 *     return add_query_arg( 'action_counter', 'completed action count', $sendback );
					 * }
					 * add_filter( 'um_handle_bulk_actions-users-custom-action', 'um_custom_bulk_actions_users' );
					 */
					$sendback = apply_filters( "um_handle_bulk_actions-users-{$current_action}", $sendback, $userids ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
					break;
			}

			return $sendback;
		}

		/**
		 * Filter WP users by UM Status
		 *
		 * WP_User_Query $query Current instance of WP_User_Query (passed by reference).
		 */
		public function filter_users_by_status( $query ) {
			global $wpdb, $pagenow;

			if ( 'users.php' !== $pagenow || ! is_admin() ) {
				return;
			}

			if ( empty( $_REQUEST['um_user_status'] ) ) {
				return;
			}

			$status = sanitize_key( $_REQUEST['um_user_status'] );

			/**
			 * Filters the marker to disable Ultimate Member default filter by user status.
			 *
			 * @since 2.8.7
			 * @hook  um_skip_filter_users_by_status
			 *
			 * @param {bool}   $skip   Marker to skip Ultimate Member core user filter handler.
			 * @param {string} $status User Status
			 *
			 * @return {array} User's row actions.
			 */
			$skip_status_filter = apply_filters( 'um_skip_filter_users_by_status', false, $status );
			if ( false !== $skip_status_filter ) {
				return;
			}

			$query->query_where = str_replace(
				'WHERE 1=1',
				$wpdb->prepare(
					"WHERE 1=1 AND
					{$wpdb->users}.ID IN (
						SELECT {$wpdb->usermeta}.user_id
						FROM $wpdb->usermeta
						WHERE {$wpdb->usermeta}.meta_key = 'account_status' AND
							{$wpdb->usermeta}.meta_value = %s
					)",
					$status
				),
				$query->query_where
			);
		}

		/**
		 * Sets redirect URI after bulk action
		 *
		 * @param string $uri
		 * @return string
		 */
		public function set_redirect_uri( $uri ) {
			if ( ! empty( $_REQUEST['s'] ) ) {
				$uri = add_query_arg( 's', sanitize_text_field( $_REQUEST['s'] ), $uri );
			}

			if ( ! empty( $_REQUEST['um_user_status'] ) ) {
				$uri = add_query_arg( 'um_user_status', sanitize_key( $_REQUEST['um_user_status'] ), $uri );
			}

			return $uri;
		}

		/**
		 * Add query args to list of query variable names to remove.
		 *
		 * @param array $removable_query_args An array of query variable names to remove from a URL
		 *
		 * @return array
		 */
		public function add_removable_query_args( $removable_query_args ) {
			$removable_query_args[] = '_um_wpnonce'; // need to add there additional nonce field because WordPress native _wpnonce field isn't visible on the users.php screen then custom actions
			$removable_query_args[] = 'approved_count';
			$removable_query_args[] = 'rejected_count';
			$removable_query_args[] = 'reactivated_count';
			$removable_query_args[] = 'deactivated_count';
			$removable_query_args[] = 'pending_count';
			$removable_query_args[] = 'resend_activation_count';
			return $removable_query_args;
		}
	}
}
