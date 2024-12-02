<?php
namespace um\common;

use WP_Error;
use WP_Session_Tokens;
use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Users
 *
 * @package um\common
 */
class Users {

	public function hooks() {
		add_filter( 'user_has_cap', array( &$this, 'map_caps_by_role' ), 10, 3 );
		add_filter( 'editable_roles', array( &$this, 'restrict_roles' ) );
	}

	/**
	 * Restrict the edit/delete users via wp-admin screen due UM role capabilities
	 *
	 * @param bool[]   $allcaps Array of key/value pairs where keys represent a capability name
	 *                          and boolean values represent whether the user has that capability.
	 * @param string[] $caps    Required primitive capabilities for the requested capability.
	 * @param array    $args    {
	 *     Arguments that accompany the requested capability check.
	 *
	 *     @type string $0 Requested capability.
	 *     @type int    $1 Concerned user ID.
	 *     @type mixed  ...$2 Optional second and further parameters, typically object ID.
	 * }
	 *
	 * @return bool[]
	 */
	public function map_caps_by_role( $allcaps, $caps, $args ) {
		if ( ! isset( $caps[0], $args[0], $args[1] ) ) {
			return $allcaps;
		}

		if ( ! in_array( $caps[0], array( 'edit_users', 'delete_users', 'list_users' ), true ) ) {
			return $allcaps;
		}

		if ( user_can( $args[1], 'manage_options' ) ) {
			return $allcaps;
		}

		if ( 'edit_users' === $caps[0] && 'edit_user' === $args[0] ) {
			if ( isset( $args[2] ) && ! UM()->roles()->um_current_user_can( 'edit', $args[2] ) ) {
				$allcaps[ $caps[0] ] = false;
			}
		} elseif ( 'delete_users' === $caps[0] && 'delete_user' === $args[0] ) {
			if ( isset( $args[2] ) && ! UM()->roles()->um_current_user_can( 'delete', $args[2] ) ) {
				$allcaps[ $caps[0] ] = false;
			}
		} elseif ( 'list_users' === $caps[0] ) {
			if ( 'list_users' === $args[0] && ! um_user( 'can_view_all' ) ) {
				$allcaps[ $caps[0] ] = false;
			}
		}

		return $allcaps;
	}

	/**
	 * Hide role filters with not accessible roles
	 *
	 * @param array $roles
	 * @return array
	 */
	public function restrict_roles( $roles ) {
		if ( current_user_can( 'manage_options' ) ) {
			return $roles;
		}

		$can_view_roles = UM()->roles()->um_user_can( 'can_view_roles' );
		if ( UM()->roles()->um_user_can( 'can_view_all' ) && empty( $can_view_roles ) ) {
			return $roles;
		}

		if ( ! empty( $can_view_roles ) ) {
			$wp_roles = wp_roles();
			foreach ( $wp_roles->get_names() as $this_role => $name ) {
				if ( ! in_array( $this_role, $can_view_roles, true ) ) {
					unset( $roles[ $this_role ] );
				}
			}
		}

		return $roles;
	}

	/**
	 * Get the user statuses list.
	 *
	 * @return array
	 */
	public function statuses_list() {
		$statuses = array(
			'approved'                    => __( 'Approved', 'ultimate-member' ),
			'awaiting_admin_review'       => __( 'Pending administrator review', 'ultimate-member' ),
			'awaiting_email_confirmation' => __( 'Waiting email confirmation', 'ultimate-member' ),
			'inactive'                    => __( 'Membership inactive', 'ultimate-member' ),
			'rejected'                    => __( 'Membership rejected', 'ultimate-member' ),
		);
		/**
		 * Filters the user statuses added via Ultimate Member plugin.
		 *
		 * Note: Statuses format is 'key' => 'title'
		 *
		 * @since 2.8.7
		 * @hook  um_user_statuses
		 *
		 * @param {array} $statuses User statuses in Ultimate Member environment.
		 *
		 * @return {array} User statuses.
		 */
		return apply_filters( 'um_user_statuses', $statuses );
	}

	/**
	 * Set user's account status.
	 *
	 * @param int    $user_id User ID.
	 * @param string $status  Status key.
	 *
	 * @return bool
	 */
	public function set_status( $user_id, $status ) {
		$old_status = $this->get_status( $user_id );

		/**
		 * Fires before User status is set.
		 *
		 * @since 2.8.7
		 * @hook um_before_user_status_is_set
		 *
		 * @param {string} $status     New status key.
		 * @param {int}    $user_id    User ID.
		 * @param {string} $old_status Old status key.
		 */
		do_action( 'um_before_user_status_is_set', $status, $user_id, $old_status );

		$result = update_user_meta( $user_id, 'account_status', $status );

		// false on failure or if the value passed to the function is the same as the one that is already in the database.
		if ( false !== $result ) {
			// backward compatibility. @todo maybe uncomment it after some testing.
			// UM()->user()->profile['account_status'] = $status;

			// Reset cache.
			$this->remove_cache( $user_id );

			/**
			 * Fires just after User status is changed.
			 *
			 * @since 1.3.x
			 * @since 2.0   Added $user_id
			 * @since 2.8.7 Added $old_status
			 *
			 * @hook um_after_user_status_is_changed
			 *
			 * @param {string} $status     Status key.
			 * @param {int}    $user_id    User ID. Since 2.0
			 * @param {string} $old_status Old status key. Since 2.8.7
			 */
			do_action( 'um_after_user_status_is_changed', $status, $user_id, $old_status );

			return true;
		}

		return false;
	}

	/**
	 * Get user account status.
	 *
	 * @param int $user_id User ID
	 *
	 * @return string
	 */
	public function get_status( $user_id, $format = 'raw' ) {
		$status = get_user_meta( $user_id, 'account_status', true );
		if ( 'raw' === $format ) {
			return $status;
		}

		$all_statuses = $this->statuses_list();
		if ( array_key_exists( $status, $all_statuses ) ) {
			return $all_statuses[ $status ];
		}

		return __( 'Undefined', 'ultimate-member' );
	}

	/**
	 * Check if user has selected account status.
	 *
	 * @since 2.8.7
	 *
	 * @param int    $user_id        User ID.
	 * @param string $status_control Status key.
	 *
	 * @return bool
	 */
	public function has_status( $user_id, $status_control ) {
		$status = $this->get_status( $user_id );
		return $status === $status_control;
	}

	/**
	 * Reset User cache
	 *
	 * @param int $user_id User ID.
	 */
	public function remove_cache( $user_id ) {
		delete_option( "um_cache_userdata_{$user_id}" );
	}

	/**
	 * Reset Activation link hash.
	 *
	 * @param int $user_id User ID.
	 */
	public function reset_activation_link( $user_id ) {
		delete_user_meta( $user_id, 'account_secret_hash' );
		delete_user_meta( $user_id, 'account_secret_hash_expiry' );
	}

	/**
	 * Set user's activation link hash
	 *
	 * @param int $user_id User ID.
	 */
	public function assign_secretkey( $user_id ) {
		if ( ! $this->has_status( $user_id, 'awaiting_email_confirmation' ) ) {
			return;
		}

		/**
		 * Fires before user activation link hash is generated.
		 *
		 * @since 1.3.x
		 * @since 2.8.7 Added $user_id
		 * @hook um_before_user_hash_is_changed
		 *
		 * @param {int} $user_id User ID. Since 2.8.7
		 */
		do_action( 'um_before_user_hash_is_changed', $user_id );

		$hash = UM()->validation()->generate();
		update_user_meta( $user_id, 'account_secret_hash', $hash );
		// backward compatibility. @todo maybe uncomment it after some testing.
		// UM()->user()->profile['account_secret_hash'] = $hash;

		$expiration  = '';
		$expiry_time = UM()->options()->get( 'activation_link_expiry_time' );
		if ( ! empty( $expiry_time ) && is_numeric( $expiry_time ) ) {
			$expiration = time() + $expiry_time * DAY_IN_SECONDS;
			update_user_meta( $user_id, 'account_secret_hash_expiry', $expiration );
			// backward compatibility. @todo maybe uncomment it after some testing.
			// UM()->user()->profile['account_secret_hash_expiry'] = $expiration;
		}

		/**
		 * Fires after user activation link hash is changed.
		 *
		 * @since 1.3.x
		 * @since 2.8.7 Added $user_id, $hash, $expiration
		 * @hook um_before_user_hash_is_changed
		 *
		 * @param {int}    $user_id    User ID. Since 2.8.7.
		 * @param {string} $hash       Activation link hash. Since 2.8.7.
		 * @param {int}    $expiration Expiration timestamp. Since 2.8.7.
		 */
		do_action( 'um_after_user_hash_is_changed', $user_id, $hash, $expiration );

		$this->remove_cache( $user_id ); // Don't remove this line. It's required removing cache duplicate for the force case when re-send activation email.
	}

	/**
	 * @param WP_User $userdata
	 *
	 * @return string|WP_Error
	 */
	public function maybe_generate_password_reset_key( $userdata ) {
		return get_password_reset_key( $userdata );
	}

	/**
	 * @param int $user_id
	 *
	 * @return bool
	 */
	public function can_current_user_edit_user( $user_id ) {
		$current_user_id = get_current_user_id();
		if ( $current_user_id === $user_id ) {
			return true;
		}

		if ( ! self::user_exists( $user_id ) ) {
			return false;
		}

		$rolename = UM()->roles()->get_priority_user_role( $current_user_id );
		$role     = get_role( $rolename );

		if ( null === $role ) {
			return false;
		}

		// Make Ultimate Member bulk actions only when the current user has 'edit_users' capability.
		if ( ! current_user_can( 'edit_users' ) && ! $role->has_cap( 'edit_users' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Activation can be sent everytime for every user. Even force for current user.
	 *
	 * @param int  $user_id User ID.
	 * @param bool $force   If true - ignore current user condition.
	 *
	 * @return bool
	 */
	public function can_activation_send( $user_id, $force = false ) {
		if ( ! self::user_exists( $user_id ) ) {
			return false;
		}

		if ( ! $force ) {
			$current_user_id = get_current_user_id();
			if ( $current_user_id === $user_id ) {
				return false;
			}
		}

		/*if ( ! $this->can_current_user_edit_user( $user_id ) ) {
			return false;
		}*/

		return true;
	}

	/**
	 * @param int  $user_id User ID.
	 * @param bool $force   If true - ignore current user condition.
	 *
	 * @return bool
	 */
	public function send_activation( $user_id, $force = false ) {
		if ( ! $this->can_activation_send( $user_id, $force ) ) {
			return false;
		}

		/**
		 * Fires before User has been set as pending email confirmation.
		 *
		 * @since 2.8.7
		 * @hook um_before_user_is_set_as_awaiting_email_confirmation
		 *
		 * @param {int} $user_id User ID.
		 */
		do_action( 'um_before_user_is_set_as_awaiting_email_confirmation', $user_id );

		$result = $this->set_status( $user_id, 'awaiting_email_confirmation' );

		// It's `false` on failure or if `$force` and the user already has `awaiting_email_confirmation` status.
		if ( false !== $result || ( $force && $this->has_status( $user_id, 'awaiting_email_confirmation' ) ) ) {
			// Clear all sessions for email confirmation pending users
			self::destroy_all_sessions( $user_id );

			// Set activation link hash.
			$this->assign_secretkey( $user_id );

			$userdata = get_userdata( $user_id );

			$current_user_id = get_current_user_id();
			um_fetch_user( $user_id );

			add_filter( 'um_template_tags_patterns_hook', array( UM()->user(), 'add_activation_placeholder' ) );
			add_filter( 'um_template_tags_replaces_hook', array( UM()->user(), 'add_activation_replace_placeholder' ) );

			UM()->maybe_action_scheduler()->enqueue_async_action( 'um_dispatch_email', array( $userdata->user_email, 'checkmail_email', array( 'fetch_user_id' => $user_id ) ) );

			um_fetch_user( $current_user_id );
			/**
			 * Fires after User has been set as pending email confirmation.
			 *
			 * @since 2.8.7
			 * @hook um_after_user_is_set_as_awaiting_email_confirmation
			 *
			 * @param {int} $user_id User ID.
			 */
			do_action( 'um_after_user_is_set_as_awaiting_email_confirmation', $user_id );
			return true;
		}

		return false;
	}

	/**
	 * @param int  $user_id User ID.
	 *
	 * @return bool
	 */
	public function can_be_deactivated( $user_id ) {
		$current_user_id = get_current_user_id();
		if ( $current_user_id === $user_id || ! self::user_exists( $user_id ) ) {
			return false;
		}

		/*if ( ! $this->can_current_user_edit_user( $user_id ) ) {
			return false;
		}*/

		$status = $this->get_status( $user_id );
		// Break only if the user already approved
		return 'inactive' !== $status;
	}

	/**
	 * @param int  $user_id User ID.
	 *
	 * @return bool
	 */
	public function deactivate( $user_id ) {
		if ( ! $this->can_be_deactivated( $user_id ) ) {
			return false;
		}

		/**
		 * Fires before User has been deactivated.
		 *
		 * @since 2.8.7
		 * @hook um_before_user_is_deactivated
		 *
		 * @param {int} $user_id User ID.
		 */
		do_action( 'um_before_user_is_deactivated', $user_id );

		$result = $this->set_status( $user_id, 'inactive' );

		// It's `false` on failure or if the user already has approved status.
		if ( false !== $result ) {
			// Clear all sessions for inactive users
			self::destroy_all_sessions( $user_id );

			$this->reset_activation_link( $user_id );

			$userdata = get_userdata( $user_id );
			UM()->maybe_action_scheduler()->enqueue_async_action( 'um_dispatch_email', array( $userdata->user_email, 'inactive_email', array( 'fetch_user_id' => $user_id ) ) );

			/**
			 * Fires after User has been deactivated.
			 *
			 * @since 1.3.x
			 * @hook um_after_user_is_inactive
			 *
			 * @param {int} $user_id User ID.
			 */
			do_action( 'um_after_user_is_inactive', $user_id );
			return true;
		}

		return false;
	}

	/**
	 * User can be rejected only after awaiting admin review status.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return bool
	 */
	public function can_be_rejected( $user_id ) {
		$current_user_id = get_current_user_id();
		if ( $current_user_id === $user_id || ! self::user_exists( $user_id ) ) {
			return false;
		}

		/*if ( ! $this->can_current_user_edit_user( $user_id ) ) {
			return false;
		}*/

		$status = $this->get_status( $user_id );

		// User can be rejected only after awaiting admin review status
		return 'awaiting_admin_review' === $status;
	}

	/**
	 * Reject user membership.
	 *
	 * @param int  $user_id User ID.
	 *
	 * @return bool
	 */
	public function reject( $user_id ) {
		if ( ! $this->can_be_rejected( $user_id ) ) {
			return false;
		}

		/**
		 * Fires before User has been rejected.
		 *
		 * @since 2.8.7
		 * @hook um_before_user_is_rejected
		 *
		 * @param {int} $user_id User ID.
		 */
		do_action( 'um_before_user_is_rejected', $user_id );

		$result = $this->set_status( $user_id, 'rejected' );

		// It's `false` on failure or if the user already has rejected status.
		if ( false !== $result ) {
			// Clear all sessions for rejected users
			self::destroy_all_sessions( $user_id );

			$this->reset_activation_link( $user_id );

			$userdata = get_userdata( $user_id );
			UM()->maybe_action_scheduler()->enqueue_async_action( 'um_dispatch_email', array( $userdata->user_email, 'rejected_email', array( 'fetch_user_id' => $user_id ) ) );

			/**
			 * Fires after User has been rejected.
			 *
			 * @since 2.8.7
			 * @hook um_after_user_is_rejected
			 *
			 * @param {int} $user_id User ID.
			 */
			do_action( 'um_after_user_is_rejected', $user_id );
			return true;
		}

		return false;
	}

	/**
	 * Check if the user can be set as pending admin review. Cannot set the same status but any user can be set to pending admin review.
	 *
	 * @param int  $user_id User ID.
	 * @param bool $force   If true - ignore current user condition.
	 *
	 * @return bool
	 */
	public function can_be_set_as_pending( $user_id, $force = false ) {
		if ( ! self::user_exists( $user_id ) ) {
			return false;
		}

		if ( ! $force ) {
			$current_user_id = get_current_user_id();
			if ( $current_user_id === $user_id ) {
				return false;
			}
		}

		/*if ( ! $this->can_current_user_edit_user( $user_id ) ) {
			return false;
		}*/

		$status = $this->get_status( $user_id );
		return 'awaiting_admin_review' !== $status;
	}

	/**
	 * Set user as pending admin review.
	 *
	 * @param int  $user_id User ID.
	 * @param bool $force   If true - ignore current user condition.
	 *
	 * @return bool
	 */
	public function set_as_pending( $user_id, $force = false ) {
		if ( ! $this->can_be_set_as_pending( $user_id, $force ) ) {
			return false;
		}

		/**
		 * Fires before User has been set as pending admin review.
		 *
		 * @since 2.8.7
		 * @hook um_before_user_is_set_as_pending
		 *
		 * @param {int} $user_id User ID.
		 */
		do_action( 'um_before_user_is_set_as_pending', $user_id );

		$result = $this->set_status( $user_id, 'awaiting_admin_review' );

		// It's `false` on failure or if the user already has rejected status.
		if ( false !== $result ) {
			// Clear all sessions for awaiting admin confirmation users
			self::destroy_all_sessions( $user_id );

			$this->reset_activation_link( $user_id );

			$userdata = get_userdata( $user_id );
			UM()->maybe_action_scheduler()->enqueue_async_action( 'um_dispatch_email', array( $userdata->user_email, 'pending_email', array( 'fetch_user_id' => $user_id ) ) );

			/**
			 * Fires after User has been set as pending admin review.
			 *
			 * @since 2.8.7
			 * @hook um_after_user_is_set_as_pending
			 *
			 * @param {int} $user_id User ID.
			 */
			do_action( 'um_after_user_is_set_as_pending', $user_id );
			return true;
		}

		return false;
	}

	/**
	 * Check if the user can be approved. Any user with status that isn't equal to `approved` can be approved.
	 *
	 * @param int  $user_id User ID.
	 * @param bool $force   If true - ignore current user condition.
	 *
	 * @return bool
	 */
	public function can_be_approved( $user_id, $force = false ) {
		if ( ! self::user_exists( $user_id ) ) {
			return false;
		}

		if ( ! $force ) {
			$current_user_id = get_current_user_id();
			if ( $current_user_id === $user_id ) {
				return false;
			}
		}

		/*if ( ! $this->can_current_user_edit_user( $user_id ) ) {
			return false;
		}*/

		$status = $this->get_status( $user_id );
		return 'approved' !== $status && 'inactive' !== $status; // inactive can be only reactivated
	}

	/**
	 * Approve user.
	 *
	 * @param int  $user_id User ID.
	 * @param bool $force   If true - ignore current user condition.
	 *
	 * @return bool `true` if the user has been approved
	 *              `false` on failure or if the user already has approved status.
	 */
	public function approve( $user_id, $force = false ) {
		if ( ! $this->can_be_approved( $user_id, $force ) ) {
			return false;
		}

		/**
		 * Fires before User has been approved.
		 *
		 * @since 2.8.7
		 * @hook um_before_user_is_approved
		 *
		 * @param {int} $user_id User ID.
		 */
		do_action( 'um_before_user_is_approved', $user_id );

		$old_status = $this->get_status( $user_id );

		$result = $this->set_status( $user_id, 'approved' );

		// It's `false` on failure or if the user already has approved status.
		if ( false !== $result ) {
			$userdata = get_userdata( $user_id );

			$this->reset_activation_link( $user_id );

			$email_slug = 'welcome_email';
			if ( 'awaiting_admin_review' === $old_status ) {
				$email_slug = 'approved_email';
				$this->maybe_generate_password_reset_key( $userdata );
			}

			$current_user_id = get_current_user_id();
			um_fetch_user( $user_id );

			add_filter( 'um_template_tags_patterns_hook', array( UM()->password(), 'add_placeholder' ) );
			add_filter( 'um_template_tags_replaces_hook', array( UM()->password(), 'add_replace_placeholder' ) );

			UM()->maybe_action_scheduler()->enqueue_async_action( 'um_dispatch_email', array( $userdata->user_email, $email_slug, array( 'fetch_user_id' => $user_id ) ) );

			um_fetch_user( $current_user_id );
			/**
			 * Fires after User has been approved.
			 *
			 * @since 1.3.x
			 * @hook um_after_user_is_approved
			 *
			 * @param {int} $user_id User ID.
			 */
			do_action( 'um_after_user_is_approved', $user_id );
			return true;
		}

		return false;
	}

	/**
	 * Reactivated can be only `inactive` user.
	 *
	 * @param int  $user_id User ID.
	 *
	 * @return bool
	 */
	public function can_be_reactivated( $user_id ) {
		$current_user_id = get_current_user_id();
		if ( $current_user_id === $user_id || ! self::user_exists( $user_id ) ) {
			return false;
		}

		/*if ( ! $this->can_current_user_edit_user( $user_id ) ) {
			return false;
		}*/

		$status = $this->get_status( $user_id );
		return 'inactive' === $status;
	}

	/**
	 * Reactivate user.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return bool `true` if the user has been reactivated
	 *              `false` on failure or if the user already has approved status.
	 */
	public function reactivate( $user_id ) {
		if ( ! $this->can_be_reactivated( $user_id ) ) {
			return false;
		}

		/**
		 * Fires before User has been reactivated.
		 *
		 * @since 2.8.7
		 * @hook um_before_user_is_reactivated
		 *
		 * @param {int} $user_id User ID.
		 */
		do_action( 'um_before_user_is_reactivated', $user_id );

		$result = $this->set_status( $user_id, 'approved' );

		// It's `false` on failure or if the user already has approved status.
		if ( false !== $result ) {
			// Reset activation link hash.
			$this->reset_activation_link( $user_id );

			$userdata = get_userdata( $user_id );

			$current_user_id = get_current_user_id();
			um_fetch_user( $user_id );

			add_filter( 'um_template_tags_patterns_hook', array( UM()->password(), 'add_placeholder' ) );
			add_filter( 'um_template_tags_replaces_hook', array( UM()->password(), 'add_replace_placeholder' ) );

			UM()->maybe_action_scheduler()->enqueue_async_action( 'um_dispatch_email', array( $userdata->user_email, 'welcome_email', array( 'fetch_user_id' => $user_id ) ) );

			um_fetch_user( $current_user_id );

			/**
			 * Fires after User has been reactivated.
			 *
			 * @since 2.8.7
			 * @hook um_after_user_is_reactivated
			 *
			 * @param {int} $user_id User ID.
			 */
			do_action( 'um_after_user_is_reactivated', $user_id );
			return true;
		}

		return false;
	}

	/**
	 * @param int $user_id
	 *
	 * @return bool
	 */
	public static function user_exists( $user_id ) {
		/**
		 * @var bool[] $search_results
		 */
		static $search_results = array();

		if ( array_key_exists( $user_id, $search_results ) ) {
			return $search_results[ $user_id ];
		}

		$user = get_userdata( $user_id );

		$search_results[ $user_id ] = false !== $user;
		return $search_results[ $user_id ];
	}

	/**
	 * Clear all sessions for user ID.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return void
	 */
	public static function destroy_all_sessions( $user_id ) {
		$user = WP_Session_Tokens::get_instance( $user_id );
		$user->destroy_all();
	}
}
