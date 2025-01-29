<?php
namespace um\frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Users
 *
 * @package um\frontend
 */
class Users {

	public function hooks() {
	}

	/**
	 * @param int $user_id
	 *
	 * @return array
	 */
	public function get_actions_list( $user_id ) {
		$actions = array();

		um_fetch_user( $user_id );

		$priority_role = UM()->roles()->get_priority_user_role( get_current_user_id() );
		$role          = get_role( $priority_role );

		$can_edit_users = null !== $role && current_user_can( 'edit_users' ) && $role->has_cap( 'edit_users' );
		if ( $can_edit_users ) {
			if ( UM()->common()->users()->can_be_approved( $user_id ) ) {
				$actions['approve_user'] = array( 'label' => __( 'Approve Membership', 'ultimate-member' ) );
			}
			if ( UM()->common()->users()->can_be_rejected( $user_id ) ) {
				$actions['reject_user'] = array(
					'label'   => __( 'Reject Membership', 'ultimate-member' ),
					'confirm' => __( 'Are you sure you want to reject this user account?', 'ultimate-member' ),
				);
			}
			if ( UM()->common()->users()->can_be_reactivated( $user_id ) ) {
				$actions['reactivate_user'] = array( 'label' => __( 'Reactivate this account', 'ultimate-member' ) );
			}
			if ( UM()->common()->users()->can_be_set_as_pending( $user_id ) ) {
				$actions['put_user_as_pending'] = array( 'label' => __( 'Put as Pending Review', 'ultimate-member' ) );
			}
			if ( UM()->common()->users()->can_activation_send( $user_id ) ) {
				$title = __( 'Send activation email', 'ultimate-member' );
				if ( UM()->common()->users()->has_status( $user_id, 'awaiting_email_confirmation' ) ) {
					$title = __( 'Resend activation email', 'ultimate-member' );
				}
				$actions['resend_user_activation'] = array( 'label' => $title );
			}
			if ( UM()->common()->users()->can_be_deactivated( $user_id ) ) {
				$actions['deactivate_user'] = array(
					'label'   => __( 'Deactivate this account', 'ultimate-member' ),
					'confirm' => __( 'Are you sure you want to deactivate this user account?', 'ultimate-member' ),
				);
			}
		}

		if ( UM()->roles()->um_current_user_can( 'delete', $user_id ) ) {
			$actions['delete'] = array(
				'label'   => __( 'Delete this user', 'ultimate-member' ),
				'confirm' => __( 'Are you sure you want to delete this user? This action cannot be undone.', 'ultimate-member' ),
			);
		}

		if ( current_user_can( 'manage_options' ) && ! is_super_admin( $user_id ) && UM()->common()->users()->has_status( $user_id, 'approved' ) ) {
			$actions['switch_user'] = array(
				'label'   => __( 'Login as this user', 'ultimate-member' ),
				'confirm' => __( 'Are you sure you want to login as this user? The current session will be finished.', 'ultimate-member' ),
			);
		}

		/**
		 * Filters users actions list in Ultimate Member frontend.
		 *
		 * @since 1.3.x
		 * @hook um_admin_user_actions_hook
		 *
		 * @param {array} $actions CPT keys.
		 * @param {int}   $user_id User ID.
		 *
		 * @return {array} CPT keys.
		 *
		 * @example <caption>Add `um_custom_action` action to the users actions list on frontend.</caption>
		 * function um_custom_admin_user_actions_hook( $actions, $user_id ) {
		 *     $actions['um_custom_action'] = array( 'label' => 'um_custom_action_label' );
		 *     return $actions;
		 * }
		 * add_filter( 'um_admin_user_actions_hook', 'um_custom_admin_user_actions_hook', 10, 2 );
		 */
		return apply_filters( 'um_admin_user_actions_hook', $actions, $user_id );
	}

	/**
	 * Get admin actions for individual user.
	 *
	 * @param int    $user_id
	 * @param string $context
	 *
	 * @return array
	 */
	public function get_dropdown_items( $user_id, $context = 'profile' ) {
		$items   = array();
		$user_id = absint( $user_id );

		if ( is_user_logged_in() ) {
			if ( get_current_user_id() === $user_id ) {
				if ( 'profile' !== $context ) {
					$items = array(
						array(
							'<a href="' . esc_url( um_edit_profile_url( $user_id ) ) . '" class="um-editprofile">' . esc_html__( 'Edit Profile', 'ultimate-member' ) . '</a>',
							'<a href="' . esc_url( um_get_predefined_page_url( 'account' ) ) . '" class="um-myaccount">' . esc_html__( 'My Account', 'ultimate-member' ) . '</a>',
						),
						array(
							'<a href="' . esc_url( um_get_predefined_page_url( 'logout' ) ) . '" class="um-logout">' . esc_html__( 'Logout', 'ultimate-member' ) . '</a>',
						),
					);

					if ( ! empty( UM()->user()->cannot_edit ) ) {
						unset( $items[0][0] );
					}
				} else {
					$items = array(
						'<a href="' . esc_url( um_get_predefined_page_url( 'account' ) ) . '">' . esc_html__( 'My Account', 'ultimate-member' ) . '</a>',
						'<a href="' . esc_url( um_get_predefined_page_url( 'logout' ) ) . '">' . esc_html__( 'Logout', 'ultimate-member' ) . '</a>',
					);
				}
			} else {
				if ( 'profile' !== $context && UM()->roles()->um_current_user_can( 'edit', $user_id ) ) {
					$items[] = array(
						'<a href="' . esc_url( um_edit_profile_url( $user_id ) ) . '" class="um-editprofile">' . esc_html__( 'Edit Profile', 'ultimate-member' ) . '</a>',
					);
				}

				$admin_actions = $this->get_actions_list( $user_id );
				if ( ! empty( $admin_actions ) ) {
					$admin_items = array();
					foreach ( $admin_actions as $id => $arr ) {
						$url_args = array(
							'um_action' => $id,
							'uid'       => $user_id,
							'nonce'     => wp_create_nonce( $id . $user_id ),
						);

						if ( 'directory' === $context ) {
							$url = add_query_arg( $url_args, wp_get_referer() );
						} else {
							// get proper referer via WordPress native function in AJAX for member directories
							$url = add_query_arg( $url_args, um_get_predefined_page_url( 'user' ) );
						}

						$link_classes = array(
							'um-user-action',
							'um_' . $id,
						);
						if ( 'delete' === $id ) {
							$link_classes[] = 'um-destructive';
						}

						$confirm = '';
						if ( ! empty( $arr['confirm'] ) ) {
							$confirm = ' data-confirm-onclick="' . esc_attr( $arr['confirm'] ) . '"';
						}

						$link_html = '<a href="' . esc_url( $url ) . '" class="' . esc_attr( implode( ' ', $link_classes ) ) . '"' . $confirm . '>' . esc_html( $arr['label'] ) . '</a>';
						if ( 'switch_user' === $id ) {
							if ( ! isset( $admin_items[1] ) ) {
								$admin_items[1] = array();
							}
							$admin_items[1][] = $link_html;
						} else {
							if ( ! isset( $admin_items[0] ) ) {
								$admin_items[0] = array();
							}
							$admin_items[0][] = $link_html;
						}
					}

					$items = array_merge( $items, $admin_items );
				}
			}
		}

		/**
		 * Filters the dropdown menu with "More actions" for user.
		 *
		 * @since 3.0.0
		 * @hook um_user_dropdown_items
		 *
		 * @param {array}  $items   Possible dropdown items list.
		 * @param {int}    $user_id User ID.
		 * @param {string} $context Place from where we call base function. It's 'profile' by default.
		 *
		 * @return {array} Possible dropdown items list.
		 *
		 * @example <caption>Add `um_custom_action` as one of dropdown items.</caption>
		 * function um_custom_user_dropdown_items( $items, $user_id, $context ) {
		 *     // single level dropdown
		 *     $items[] = '<a href="' . esc_url( $item_url ) . '">' . esc_html( $item_title ) . '</a>';
		 *     // dropdown with separators
		 *     $items[] = array( '<a href="' . esc_url( $item_url ) . '">' . esc_html( $item_title ) . '</a>' );
		 *     return $items;
		 * }
		 * add_filter( 'um_user_dropdown_items', 'um_custom_user_dropdown_items', 10, 3 );
		 */
		return apply_filters( 'um_user_dropdown_items', $items, $user_id, $context );
	}
}
