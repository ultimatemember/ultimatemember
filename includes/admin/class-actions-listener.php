<?php
namespace um\admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\admin\Actions_Listener' ) ) {

	/**
	 * Class Actions_Listener
	 *
	 * @package um\admin
	 */
	class Actions_Listener {

		/**
		 * Actions_Listener constructor.
		 */
		public function __construct() {
			add_action( 'admin_init', array( $this, 'actions_listener' ) );
			add_filter( 'um_adm_action_individual_nonce_actions', array( $this, 'extends_individual_nonce_actions' ) ); // @todo remove soon after UM core update

			add_action( 'load-ultimate-member_page_um_restriction_rules', array( &$this, 'handle_restriction_rules_list' ) );
			add_action( 'load-ultimate-member_page_um_restriction_rules', array( &$this, 'handle_save_restriction_rule' ) );
		}

		/**
		 * Handle wp-admin actions
		 *
		 * @since 2.8.7
		 */
		public function actions_listener() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			if ( ! empty( $_REQUEST['um_adm_action'] ) ) {
				switch ( sanitize_key( $_REQUEST['um_adm_action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- there is nonce verification below for each case
					case 'approve_user':
						if ( empty( $_REQUEST['uid'] ) || ! is_numeric( $_REQUEST['uid'] ) ) {
							die( esc_html__( 'Invalid user ID', 'ultimate-member' ) );
						}

						$user_id = absint( $_REQUEST['uid'] );

						check_admin_referer( "approve_user{$user_id}" );

						$redirect = wp_get_referer();
						if ( UM()->common()->users()->can_current_user_edit_user( $user_id ) ) {
							$result = UM()->common()->users()->approve( $user_id );
							if ( $result ) {
								$redirect = add_query_arg(
									array(
										'update'         => 'um_approved',
										'approved_count' => 1,
									),
									$redirect
								);
							}
						}

						wp_safe_redirect( $redirect );
						exit;
					case 'reactivate_user':
						if ( empty( $_REQUEST['uid'] ) || ! is_numeric( $_REQUEST['uid'] ) ) {
							die( esc_html__( 'Invalid user ID', 'ultimate-member' ) );
						}

						$user_id = absint( $_REQUEST['uid'] );

						check_admin_referer( "reactivate_user{$user_id}" );

						$redirect = wp_get_referer();
						if ( UM()->common()->users()->can_current_user_edit_user( $user_id ) ) {
							$result = UM()->common()->users()->reactivate( $user_id );
							if ( $result ) {
								$redirect = add_query_arg(
									array(
										'update' => 'um_reactivated',
										'reactivated_count' => 1,
									),
									$redirect
								);
							}
						}
						wp_safe_redirect( $redirect );
						exit;
					case 'put_user_as_pending':
						if ( empty( $_REQUEST['uid'] ) || ! is_numeric( $_REQUEST['uid'] ) ) {
							die( esc_html__( 'Invalid user ID', 'ultimate-member' ) );
						}

						$user_id = absint( $_REQUEST['uid'] );

						check_admin_referer( "put_user_as_pending{$user_id}" );

						$redirect = wp_get_referer();
						if ( UM()->common()->users()->can_current_user_edit_user( $user_id ) ) {
							$result = UM()->common()->users()->set_as_pending( $user_id );
							if ( $result ) {
								$redirect = add_query_arg(
									array(
										'update'        => 'um_pending',
										'pending_count' => 1,
									),
									$redirect
								);
							}
						}
						wp_safe_redirect( $redirect );
						exit;
					case 'resend_user_activation':
						if ( empty( $_REQUEST['uid'] ) || ! is_numeric( $_REQUEST['uid'] ) ) {
							die( esc_html__( 'Invalid user ID', 'ultimate-member' ) );
						}

						$user_id = absint( $_REQUEST['uid'] );

						check_admin_referer( "resend_user_activation{$user_id}" );

						$redirect = wp_get_referer();
						if ( UM()->common()->users()->can_current_user_edit_user( $user_id ) ) {
							$result = UM()->common()->users()->send_activation( $user_id, true );
							if ( $result ) {
								$redirect = add_query_arg(
									array(
										'update' => 'um_resend_activation',
										'resend_activation_count' => 1,
									),
									$redirect
								);
							}
						}
						wp_safe_redirect( $redirect );
						exit;
					case 'reject_user':
						if ( empty( $_REQUEST['uid'] ) || ! is_numeric( $_REQUEST['uid'] ) ) {
							die( esc_html__( 'Invalid user ID', 'ultimate-member' ) );
						}

						$user_id = absint( $_REQUEST['uid'] );

						check_admin_referer( "reject_user{$user_id}" );

						$redirect = wp_get_referer();
						if ( UM()->common()->users()->can_current_user_edit_user( $user_id ) ) {
							$result = UM()->common()->users()->reject( $user_id );
							if ( $result ) {
								$redirect = add_query_arg(
									array(
										'update'         => 'um_rejected',
										'rejected_count' => 1,
									),
									$redirect
								);
							}
						}
						wp_safe_redirect( $redirect );
						exit;
					case 'deactivate_user':
						if ( empty( $_REQUEST['uid'] ) || ! is_numeric( $_REQUEST['uid'] ) ) {
							die( esc_html__( 'Invalid user ID', 'ultimate-member' ) );
						}

						$user_id = absint( $_REQUEST['uid'] );

						check_admin_referer( "deactivate_user{$user_id}" );

						$redirect = wp_get_referer();
						if ( UM()->common()->users()->can_current_user_edit_user( $user_id ) ) {
							$result = UM()->common()->users()->deactivate( $user_id );
							if ( $result ) {
								$redirect = add_query_arg(
									array(
										'update' => 'um_deactivate',
										'deactivated_count' => 1,
									),
									$redirect
								);
							}
						}
						wp_safe_redirect( $redirect );
						exit;
				}
			}
		}

		public function extends_individual_nonce_actions( $actions ) {
			$actions[] = 'approve_user';
			$actions[] = 'reactivate_user';
			$actions[] = 'put_user_as_pending';
			$actions[] = 'resend_user_activation';
			$actions[] = 'reject_user';
			$actions[] = 'deactivate_user';
			return $actions;
		}

		public function handle_restriction_rules_list() {
			if ( ! empty( $_GET['tab'] ) ) {
				return;
			}

			if ( isset( $_REQUEST['_wp_http_referer'] ) ) {
				$redirect = remove_query_arg( array( '_wp_http_referer' ), wp_unslash( $_REQUEST['_wp_http_referer'] ) );
			} else {
				$redirect = get_admin_url() . 'admin.php?page=um_restriction_rules';
			}

			if ( isset( $_GET['action'] ) ) {
				$rule_keys = array();
				switch ( sanitize_key( $_GET['action'] ) ) {
					case 'delete':
						if ( isset( $_REQUEST['id'] ) ) {
							check_admin_referer( 'um_restriction_delete' . sanitize_title( $_REQUEST['id'] ) . get_current_user_id() );
							$rule_keys = (array) absint( $_REQUEST['id'] );
						} elseif ( isset( $_REQUEST['item'] ) ) {
							check_admin_referer( 'bulk-' . sanitize_key( __( 'Rules', 'ultimate-member' ) ) );
							$rule_keys = array_map( 'sanitize_title', $_REQUEST['item'] );
						}

						if ( ! count( $rule_keys ) ) {
							wp_safe_redirect( $redirect );
							exit;
						}

						$um_rules = get_option( 'um_restriction_rules', array() );

						foreach ( $rule_keys as $k => $rule_key ) {
							$rule_meta = get_option( "um_restriction_rule_{$rule_key}" );

							delete_option( "um_restriction_rule_{$rule_key}" );

							/**
							 * Fires after delete UM restriction rule.
							 *
							 * @since 2.8.x
							 * @hook um_after_delete_restriction_rule
							 *
							 * @param {string} $rule_key  Rule key.
							 * @param {array}  $rule_meta Rule meta.
							 *
							 * @example <caption>Make any custom action after deleting UM rule.</caption>
							 * function my_um_after_delete_restriction_rule( $rule_key, $rule_meta ) {
							 *     // your code here
							 * }
							 * add_action( 'um_after_delete_restriction_rule', 'my_um_after_delete_restriction_rule', 10, 2 );
							 */
							do_action( 'um_after_delete_restriction_rule', $rule_key, $rule_meta );

							unset( $um_rules[ $rule_key ] );
						}

						update_option( 'um_restriction_rules', $um_rules );

						wp_safe_redirect( add_query_arg( 'msg', 'd', $redirect ) );
						exit;

					case 'activate':
						if ( isset( $_REQUEST['id'] ) ) {
							check_admin_referer( 'um_restriction_activate' . sanitize_title( $_REQUEST['id'] ) . get_current_user_id() );
							$rule_keys = (array) absint( $_REQUEST['id'] );
						} elseif ( isset( $_REQUEST['item'] ) ) {
							check_admin_referer( 'bulk-' . sanitize_key( __( 'Rules', 'ultimate-member' ) ) );
							$rule_keys = array_map( 'sanitize_title', $_REQUEST['item'] );
						}

						if ( ! count( $rule_keys ) ) {
							wp_safe_redirect( $redirect );
							exit;
						}

						$um_rules = get_option( 'um_restriction_rules', array() );

						foreach ( $rule_keys as $k => $rule_key ) {
							$um_rules[ $rule_key ]['_um_status'] = 'active';
						}

						update_option( 'um_restriction_rules', $um_rules );

						wp_safe_redirect( add_query_arg( 'msg', 'act', $redirect ) );
						exit;

					case 'deactivate':
						if ( isset( $_REQUEST['id'] ) ) {
							check_admin_referer( 'um_restriction_deactivate' . sanitize_title( $_REQUEST['id'] ) . get_current_user_id() );
							$rule_keys = (array) absint( $_REQUEST['id'] );
						} elseif ( isset( $_REQUEST['item'] ) ) {
							check_admin_referer( 'bulk-' . sanitize_key( __( 'Rules', 'ultimate-member' ) ) );
							$rule_keys = array_map( 'sanitize_title', $_REQUEST['item'] );
						}

						if ( ! count( $rule_keys ) ) {
							wp_safe_redirect( $redirect );
							exit;
						}

						$um_rules = get_option( 'um_restriction_rules', array() );

						foreach ( $rule_keys as $k => $rule_key ) {
							$um_rules[ $rule_key ]['_um_status'] = 'inactive';
						}

						update_option( 'um_restriction_rules', $um_rules );

						wp_safe_redirect( add_query_arg( 'msg', 'deact', $redirect ) );
						exit;

				}
			}
		}

		public function handle_save_restriction_rule() {
			if ( empty( $_GET['tab'] ) || ! in_array( sanitize_key( $_GET['tab'] ), array( 'edit', 'add' ), true ) ) {
				return;
			}

			$tab = sanitize_key( $_GET['tab'] );

			if ( empty( $_POST['um_restriction_rules'] ) ) {
				return;
			}

			if ( 'edit' === $tab && empty( $_GET['id'] ) ) {
				return;
			}

			$restriction_id = '';
			$rule_error     = '';

			if ( 'add' === $tab ) {
				check_admin_referer( 'um-add-restriction-rule', 'um_nonce' );
			} else {
				$restriction_id = absint( $_GET['id'] );
				check_admin_referer( 'um-edit-restriction-rule' . $restriction_id, 'um_nonce' );
			}

			if ( empty( $rule_error ) ) {
				$data         = UM()->admin()->sanitize_restriction_rule_meta( $_POST['um_restriction_rules'] );
				$data_action  = UM()->admin()->sanitize_restriction_rule_meta( $_POST['um_restriction_rules_action'] );
				$data_include = array();
				$data_exclude = array();
				$data_rules   = array();

				if ( ! empty( $_POST['um_restriction_rule_content']['_um_include'] ) ) {
					$data_include = UM()->admin()->sanitize_restriction_rule_meta( $_POST['um_restriction_rule_content'] );
				}

				if ( ! empty( $_POST['um_restriction_rule_content']['_um_exclude'] ) ) {
					$data_exclude = UM()->admin()->sanitize_restriction_rule_meta( $_POST['um_restriction_rule_content'] );
				}

				if ( 'loggedout' !== $_POST['um_restriction_rules_users']['_um_authentification'] ) {
					if ( ! empty( $_POST['um_restriction_rules_users']['_um_users'] ) ) {
						$_POST['um_restriction_rules_users']['_um_users'] = array_values( $_POST['um_restriction_rules_users']['_um_users'] );
						$data_rules                                       = UM()->admin()->sanitize_restriction_rule_meta( $_POST['um_restriction_rules_users'] );
					}
					$data_rules['_um_authentification'] = 'loggedin';
				} else {
					$data_rules['_um_authentification'] = 'loggedout';
				}

				// @todo v3 type hardcode
				$data['_um_type'] = 'post';

				$data['title'] = trim( esc_html( wp_strip_all_tags( $data['title'] ) ) );
				if ( empty( $data['title'] ) ) {
					$rule_error .= __( 'Title is empty!', 'ultimate-member' ) . '<br />';
				}

				if ( 'add' === $tab ) {
					$auto_increment = UM()->options()->get( 'custom_restriction_rules_increment' );
					$auto_increment = ! empty( $auto_increment ) ? $auto_increment : 1;
					$restriction_id = $auto_increment;

					$redirect = add_query_arg(
						array(
							'page' => 'um_restriction_rules',
							'tab'  => 'edit',
							'id'   => $restriction_id,
							'msg'  => 'a',
						),
						admin_url( 'admin.php' )
					);
				} else {
					$redirect = add_query_arg(
						array(
							'page' => 'um_restriction_rules',
							'tab'  => 'edit',
							'id'   => $restriction_id,
							'msg'  => 'u',
						),
						admin_url( 'admin.php' )
					);
				}

				if ( '' === $rule_error ) {
					$rules       = get_option( 'um_restriction_rules', array() );
					$rules_count = count( $rules );
					$update      = true;
					$data['id']  = $restriction_id;

					$data['title']           = wp_unslash( $data['title'] );
					$data['_um_description'] = wp_unslash( $data['_um_description'] );

					if ( 'add' === $tab ) {
						$data['_um_priority']     = $rules_count;
						$rules[ $restriction_id ] = $data;

						if ( isset( $auto_increment ) ) {
							++$auto_increment;
							UM()->options()->update( 'custom_restriction_rules_increment', $auto_increment );
						}
						$update = false;
					} else {
						$id                       = $data['id'];
						$data['_um_priority']     = $rules[ $id ]['_um_priority'];
						$rules[ $restriction_id ] = $data;
					}

					update_option( 'um_restriction_rules', $rules );

					$rule_meta['action']  = $data_action;
					$rule_meta['include'] = $data_include;
					$rule_meta['exclude'] = $data_exclude;
					$rule_meta['rules']   = $data_rules;

					/**
					 * Filters the restriction rule meta before save it to DB.
					 *
					 * @since 2.9.0
					 * @hook  um_restriction_rule_edit_data
					 *
					 * @param {array}   $data            Rule meta.
					 * @param {string}  $restriction_id  Rule ID.
					 * @param {bool}    $update          Create or update rule. "True" if update.
					 *
					 * @return {array}  Rule meta.
					 *
					 * @example <caption>Add custom metadata for rule on saving.</caption>
					 * function my_um_restriction_rule_edit_data( $rule_meta, $restriction_id, $update ) {
					 *     $rule_meta['{meta_key}'] = {meta_value}; // set your meta key and meta value
					 *     return $rule_meta;
					 * }
					 * add_filter( 'um_restriction_rule_edit_data', 'my_um_restriction_rule_edit_data', 10, 3 );
					 */
					$rule_meta = apply_filters( 'um_restriction_rule_edit_data', $rule_meta, $restriction_id, $update );
					unset( $rule_meta['id'] );

					update_option( "um_restriction_rule_{$restriction_id}", $rule_meta );

					wp_safe_redirect( $redirect );
					exit;
				}
			}
		}
	}
}
