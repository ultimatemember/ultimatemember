<?php
namespace um\common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Directory
 *
 * @package um\common
 */
class Directory {

	public $cover_size;

	public $avatar_size;

	/**
	 * Directory constructor.
	 */
	public function __construct() {
	}

	/**
	 * Getting member directory post ID via the hash.
	 * Hash is unique attr, which we use visible at frontend
	 *
	 * @param string $hash
	 *
	 * @return bool|int
	 */
	public function get_directory_by_hash( $hash ) {
		global $wpdb;

		$directory_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE SUBSTRING( MD5( ID ), 11, 5 ) = %s", $hash ) );

		if ( empty( $directory_id ) ) {
			return false;
		}

		return (int) $directory_id;
	}

	/**
	 * @param int $user_id
	 *
	 * @return array
	 */
	public function build_user_actions_list( $user_id ) {
		$actions = array();
		if ( ! is_user_logged_in() ) {
			return $actions;
		}

		$user_id = absint( $user_id );

//		$items = array(
//			array(
//				'<a href="javascript:void(0);" class="um-manual-trigger">' . __( 'Action 1', 'ultimate-member' ) . '</a>',
//				'<a href="javascript:void(0);" class="um-reset-profile-photo">' . __( 'Action 2', 'ultimate-member' ) . '</a>',
//			),
//			array(
//				'<a href="javascript:void(0);" class="um-manual-trigger">' . __( 'Action 3', 'ultimate-member' ) . '</a>',
//				'<a href="javascript:void(0);" class="um-reset-profile-photo">' . __( 'Action 4', 'ultimate-member' ) . '</a>',
//			),
//			array(
//				'<a href="javascript:void(0);" class="um-manual-trigger">' . __( 'Action 5', 'ultimate-member' ) . '</a>',
//				'<a href="javascript:void(0);" class="um-reset-profile-photo">' . __( 'Action 6', 'ultimate-member' ) . '</a>',
//			),
//		);

		if ( get_current_user_id() !== $user_id ) {

//			if ( UM()->roles()->um_current_user_can( 'edit', $user_id ) ) {
//				$actions['um-editprofile'] = array(
//					'title' => esc_html__( 'Edit Profile', 'ultimate-member' ),
//					'url'   => um_edit_profile_url(),
//				);
//			}

			$actions = array(
				array(
					'<a href="' . esc_url( um_edit_profile_url() ) . '" class="um-editprofile">' . esc_html__( 'Edit Profile', 'ultimate-member' ) . '</a>',
					//'<a href="' . esc_url( um_get_core_page( 'account' ) ) . '" class="um-myaccount">' . esc_html__( 'My Account', 'ultimate-member' ) . '</a>',
				),
//				array(
//					'<a href="' . esc_url( um_get_core_page( 'logout' ) ) . '" class="um-logout">' . esc_html__( 'Logout', 'ultimate-member' ) . '</a>',
//				),
			);

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_admin_user_actions_hook
			 * @description Extend admin actions for each user
			 * @input_vars
			 * [{"var":"$actions","type":"array","desc":"Actions for user"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_admin_user_actions_hook', 'function_name', 10, 1 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_admin_user_actions_hook', 'my_admin_user_actions', 10, 1 );
			 * function my_admin_user_actions( $actions ) {
			 *     // your code here
			 *     return $actions;
			 * }
			 * ?>
			 */
			$admin_actions = apply_filters( 'um_admin_user_actions_hook', array(), $user_id );
			if ( ! empty( $admin_actions ) ) {
				foreach ( $admin_actions as $id => $arr ) {
					$url = add_query_arg(
						array(
							'um_action' => $id,
							'uid'       => $user_id,
						),
						um_get_core_page( 'user' )
					);

					if ( 'um_switch_user' === $id ) {
						$actions[2][] = '<a href="' . esc_url( $url ) . '" class="' . esc_attr( $id ) . '">' . esc_html( $arr['label'] ) . '</a>';
					} else {
						$actions[1][] = '<a href="' . esc_url( $url ) . '" class="' . esc_attr( $id ) . '">' . esc_html( $arr['label'] ) . '</a>';
					}

//					$actions[ $id ] = array(
//						'title' => esc_html( $arr['label'] ),
//						'url'   => esc_url( $url ),
//					);
				}
			}

			if ( ! UM()->roles()->um_current_user_can( 'edit', $user_id ) ) {
				unset( $actions[0][0] );
			}

			$actions = apply_filters( 'um_member_directory_users_card_actions', $actions, $user_id );
		} else {
//			if ( empty( UM()->user()->cannot_edit ) ) {
//				$actions['um-editprofile'] = array(
//					'title' => esc_html__( 'Edit Profile', 'ultimate-member' ),
//					'url'   => um_edit_profile_url(),
//				);
//			}
//
//			$actions['um-myaccount'] = array(
//				'title' => esc_html__( 'My Account', 'ultimate-member' ),
//				'url'   => um_get_core_page( 'account' ),
//			);
//
//			$actions['um-logout'] = array(
//				'title' => esc_html__( 'Logout', 'ultimate-member' ),
//				'url'   => um_get_core_page( 'logout' ),
//			);

			$actions = array(
				array(
					'<a href="' . esc_url( um_edit_profile_url() ) . '" class="um-editprofile">' . esc_html__( 'Edit Profile', 'ultimate-member' ) . '</a>',
					'<a href="' . esc_url( um_get_core_page( 'account' ) ) . '" class="um-myaccount">' . esc_html__( 'My Account', 'ultimate-member' ) . '</a>',
				),
				array(
					'<a href="' . esc_url( um_get_core_page( 'logout' ) ) . '" class="um-logout">' . esc_html__( 'Logout', 'ultimate-member' ) . '</a>',
				),
			);

			if ( ! empty( UM()->user()->cannot_edit ) ) {
				unset( $actions[0][0] );
			}

			$actions = apply_filters( 'um_member_directory_my_user_card_actions', $actions, $user_id );
		}

		return $actions;
	}

	/**
	 * @param int $user_id
	 * @param array $directory_data
	 *
	 * @return array
	 */
	public function build_user_card_data( $user_id, $directory_data ) {

		um_fetch_user( $user_id );

		$dropdown_actions = $this->build_user_actions_list( $user_id );

		$actions  = array();
		$can_edit = UM()->roles()->um_current_user_can( 'edit', $user_id );

		// Replace hook 'um_members_just_after_name'
		ob_start();
		do_action( 'um_members_just_after_name', $user_id, $directory_data );
		$hook_just_after_name = ob_get_clean();

		// Replace hook 'um_members_after_user_name'
		ob_start();
		do_action( 'um_members_after_user_name', $user_id, $directory_data );
		$hook_after_user_name = ob_get_clean();

		$data_array = array(
			'card_anchor'          => esc_html( substr( md5( $user_id ), 10, 5 ) ),
			'id'                   => absint( $user_id ),
			'role'                 => esc_html( um_user( 'role' ) ),
			'account_status'       => esc_html( um_user( 'account_status' ) ),
			'account_status_name'  => esc_html( um_user( 'account_status_name' ) ),
			'cover_photo'          => wp_kses( um_user( 'cover_photo', $this->cover_size ), UM()->get_allowed_html( 'templates' ) ),
			'display_name'         => esc_html( um_user( 'display_name' ) ),
			'profile_url'          => esc_url( um_user_profile_url() ),
			'can_edit'             => (bool) $can_edit,
			'edit_profile_url'     => esc_url( um_edit_profile_url() ),
			'avatar'               => wp_kses( get_avatar( $user_id, $this->avatar_size ), UM()->get_allowed_html( 'templates' ) ),
			'display_name_html'    => wp_kses( um_user( 'display_name', 'html' ), UM()->get_allowed_html( 'templates' ) ),
			'dropdown_actions'     => $dropdown_actions,
			'hook_just_after_name' => wp_kses( preg_replace( '/^\s+/im', '', $hook_just_after_name ), UM()->get_allowed_html( 'templates' ) ),
			'hook_after_user_name' => wp_kses( preg_replace( '/^\s+/im', '', $hook_after_user_name ), UM()->get_allowed_html( 'templates' ) ),
		);

		if ( ! empty( $directory_data['show_tagline'] ) ) {

			if ( ! empty( $directory_data['tagline_fields'] ) ) {
				$directory_data['tagline_fields'] = maybe_unserialize( $directory_data['tagline_fields'] );

				if ( is_array( $directory_data['tagline_fields'] ) ) {
					foreach ( $directory_data['tagline_fields'] as $key ) {
						if ( ! $key ) {
							continue;
						}

						if ( '_um_last_login' === $key ) {
							$show_last_login = get_user_meta( $user_id, 'um_show_last_login', true );
							if ( ! empty( $show_last_login ) && 'no' === $show_last_login[0] ) {
								continue;
							}
						}

						$value = um_filtered_value( $key );

						if ( ! $value ) {
							continue;
						}

						$data_array[ $key ] = wp_kses( $value, UM()->get_allowed_html( 'templates' ) );
					}
				}
			}
		}

		if ( ! empty( $directory_data['show_userinfo'] ) ) {

			if ( ! empty( $directory_data['reveal_fields'] ) ) {

				$directory_data['reveal_fields'] = maybe_unserialize( $directory_data['reveal_fields'] );

				if ( is_array( $directory_data['reveal_fields'] ) ) {
					foreach ( $directory_data['reveal_fields'] as $key ) {
						if ( ! $key ) {
							continue;
						}

						if ( '_um_last_login' === $key ) {
							$show_last_login = get_user_meta( $user_id, 'um_show_last_login', true );
							if ( ! empty( $show_last_login ) && 'no' === $show_last_login[0] ) {
								continue;
							}
						}

						$value = um_filtered_value( $key );
						if ( ! $value ) {
							continue;
						}

						$label = UM()->fields()->get_label( $key );
						if ( $key == 'role_select' || $key == 'role_radio' ) {
							$label = strtr( $label, array(
								' (Dropdown)'   => '',
								' (Radio)'      => ''
							) );
						}

						$data_array[ "label_{$key}" ] = esc_html__( $label, 'ultimate-member' );
						$data_array[ $key ] = wp_kses( $value, UM()->get_allowed_html( 'templates' ) );
					}
				}
			}

			if ( ! empty( $directory_data['show_social'] ) ) {
				ob_start();
				UM()->fields()->show_social_urls();
				$social_urls = ob_get_clean();

				$data_array['social_urls'] = wp_kses( $social_urls, UM()->get_allowed_html( 'templates' ) );
			}
		}

		$data_array = apply_filters( 'um_ajax_get_members_data', $data_array, $user_id, $directory_data );

		um_reset_user_clean();

		return $data_array;
	}
}
