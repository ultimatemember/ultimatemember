<?php
namespace um\core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'um\core\Account' ) ) {


	/**
	 * Class Account
	 * @package um\core
	 */
	class Account {


		/**
		 * @var
		 */
		public $tabs;


		/**
		 * @var string
		 */
		public $current_tab = 'general';


		/**
		 * @var array
		 */
		public $displayed_fields = array();


		/**
		 * @var array
		 */
		public $tab_output = array();


		/**
		 * Account constructor.
		 */
		public function __construct() {
			add_action( 'template_redirect', array( &$this, 'account_submit' ), 10002 );
			add_action( 'deleted_user', array( &$this, 'deleted_user_redirect' ), 10 );
		}


		/**
		 * Init AllTabs for user account
		 *
		 * @param $args
		 *
		 * @throws \Exception
		 */
		public function init_tabs( $args ) {
			$tabs = array();

			if ( isset( $args['tab'] ) ) {
				$tabs = explode( ',', $args['tab'] );
				$tabs = array_map( 'trim', $tabs );
				$tabs = array_diff( $tabs, array( '' ) );
			}

			$this->tabs = $this->get_tabs();

			ksort( $this->tabs );

			$tabs_structed = array();
			foreach ( $this->tabs as $k => $arr ) {

				foreach ( $arr as $id => $info ) {

					if ( isset( $args['tab'] ) && 1 < count( $tabs ) && ! array_key_exists( $id, array_flip( $tabs ) ) ) {
						continue;
					}

					if ( ! empty( $args['tab'] ) && 1 >= count( $tabs ) && $id !== $args['tab'] ) {
						continue;
					}

					$tab_args    = $this->get_tab_fields( $id, $args );
					$tab_content = $this->get_tab_content( $id, $args );

					if ( ! empty( $tab_args ) || ! empty( $tab_content ) ) {
						$tabs_structed[ $id ] = $info;
					}
				}
			}
			$this->tabs = $tabs_structed;
		}


		/**
		 * Get all Account tabs
		 *
		 * @return array
		 */
		public function get_tabs() {
			$tabs = array();

			$tabs[100]['general'] = array(
				'icon'         => 'fas fa-user',
				'title'        => __( 'Account', 'ultimate-member' ),
				'submit_title' => __( 'Update Account', 'ultimate-member' ),
			);

			$tabs[200]['password'] = array(
				'icon'         => 'fas fa-asterisk',
				'title'        => __( 'Change Password', 'ultimate-member' ),
				'submit_title' => __( 'Update Password', 'ultimate-member' ),
			);

			$tabs[300]['privacy'] = array(
				'icon'         => 'fas fa-lock',
				'title'        => __( 'Privacy', 'ultimate-member' ),
				'submit_title' => __( 'Update Privacy', 'ultimate-member' ),
			);

			$tabs[400]['notifications'] = array(
				'icon'         => 'far fa-envelope',
				'title'        => __( 'Notifications', 'ultimate-member' ),
				'submit_title' => __( 'Update Notifications', 'ultimate-member' ),
			);

			//if user cannot delete profile hide delete tab
			if ( um_user( 'can_delete_profile' ) || um_user( 'can_delete_everyone' ) ) {

				$tabs[99999]['delete'] = array(
					'icon'         => 'far fa-trash-alt',
					'title'        => __( 'Delete Account', 'ultimate-member' ),
					'submit_title' => __( 'Delete Account', 'ultimate-member' ),
				);

			}

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_account_page_default_tabs_hook
			 * @description Account Page Tabs
			 * @input_vars
			 * [{"var":"$tabs","type":"array","desc":"Account Page Tabs"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_account_page_default_tabs_hook', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_filter( 'um_account_page_default_tabs_hook', 'my_account_page_default_tabs', 10, 1 );
			 * function my_account_page_default_tabs( $tabs ) {
			 *     // your code here
			 *     return $tabs;
			 * }
			 * ?>
			 */
			return apply_filters( 'um_account_page_default_tabs_hook', $tabs );
		}

		/**
		 *  Update account fields to secure the account submission
		 */
		public function account_fields_hash() {
			update_user_meta( um_user( 'ID' ), 'um_account_secure_fields', UM()->account()->displayed_fields );
		}


		/**
		 * Submit Account handler
		 */
		public function account_submit() {

			if ( um_submitting_account_page() ) {

				UM()->form()->post_form = $_POST; // phpcs:ignore WordPress.Security.NonceVerification -- already verified here

				/**
				 * UM hook
				 *
				 * @type action
				 * @title um_submit_account_errors_hook
				 * @description Validate process on account submit
				 * @input_vars
				 * [{"var":"$submitted","type":"array","desc":"Account Page Submitted data"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_action( 'um_submit_account_errors_hook', 'function_name', 10, 1 );
				 * @example
				 * <?php
				 * add_action( 'um_submit_account_errors_hook', 'my_submit_account_errors', 10, 1 );
				 * function my_submit_account_errors( $submitted ) {
				 *     // your code here
				 * }
				 * ?>
				 */
				do_action( 'um_submit_account_errors_hook', UM()->form()->post_form );

				if ( um_is_predefined_page( 'account' ) && get_query_var( 'um_tab' ) ) {
					$this->current_tab = get_query_var( 'um_tab' );
				} else {
					$this->current_tab = UM()->form()->post_form['_um_account_tab'];
				}

				$this->current_tab = sanitize_key( $this->current_tab );

				if ( ! isset( UM()->form()->errors ) ) {
					/**
					 * UM hook
					 *
					 * @type action
					 * @title um_submit_account_details
					 * @description On success account submit
					 * @input_vars
					 * [{"var":"$submitted","type":"array","desc":"Account Page Submitted data"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage add_action( 'um_submit_account_details', 'function_name', 10, 1 );
					 * @example
					 * <?php
					 * add_action( 'um_submit_account_details', 'my_submit_account_details', 10, 1 );
					 * function my_submit_account_details( $submitted ) {
					 *     // your code here
					 * }
					 * ?>
					 */
					do_action( 'um_submit_account_details', UM()->form()->post_form );

				} elseif ( UM()->form()->has_error( 'um_account_security' ) ) {
					$url = '';
					if ( um_is_predefined_page( 'account' ) ) {
						$url = UM()->account()->tab_link( $this->current_tab );
						$url = add_query_arg( 'err', 'account', $url );
					}

					// phpcs:ignore WordPress.Security.SafeRedirect
					wp_redirect( $url );
					exit();
				}
			}

		}


		/**
		 * Get Tab Link
		 * @param  integer $id
		 * @return string
		 */
		public function tab_link( $id ) {

			if ( UM()->is_permalinks ) {

				$url = trailingslashit( untrailingslashit( um_get_predefined_page_url( 'account' ) ) );
				$url = $url . $id . '/';

			} else {

				$url = add_query_arg( 'um_tab', $id, um_get_predefined_page_url( 'account' ) );

			}

			return $url;
		}


		/**
		 * @param $fields
		 * @param $shortcode_args
		 * @return mixed
		 */
		public function filter_fields_by_attrs( $fields, $shortcode_args ) {
			foreach ( $fields as $k => $field ) {
				if ( isset( $shortcode_args[ $field['metakey'] ] ) && 0 === (int) $shortcode_args[ $field['metakey'] ] ) {
					unset( $fields[ $k ] );
				}
			}

			return $fields;
		}


		/**
		 * Init displayed fields for security check
		 *
		 * @param $fields
		 * @param $tab_key
		 */
		public function init_displayed_fields( $fields, $tab_key ) {
			if ( ! $this->is_secure_enabled() ) {
				return;
			}

			if ( ! isset( $this->displayed_fields[ $tab_key ] ) ) {
				$this->displayed_fields[ $tab_key ] = array_keys( $fields );
			} else {
				$this->displayed_fields[ $tab_key ] = array_merge( $this->displayed_fields[ $tab_key ], array_keys( $fields ) );
				$this->displayed_fields[ $tab_key ] = array_unique( $this->displayed_fields[ $tab_key ] );
			}
		}


		/**
		 * @param $field_key
		 * @param $tab_key
		 */
		public function add_displayed_field( $field_key, $tab_key ) {
			if ( ! $this->is_secure_enabled() ) {
				return;
			}

			if ( ! isset( $this->displayed_fields[ $tab_key ] ) ) {
				$this->displayed_fields[ $tab_key ] = array( $field_key );
			} else {
				$this->displayed_fields[ $tab_key ][] = $field_key;
			}
		}


		/**
		 * @return bool
		 */
		public function is_secure_enabled() {
			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_account_secure_fields__enabled
			 * @description Active account secure fields
			 * @input_vars
			 * [{"var":"$enabled","type":"string","desc":"Enable secure account fields"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_account_secure_fields__enabled', 'function_name', 10, 1 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_account_secure_fields__enabled', 'my_account_secure_fields', 10, 1 );
			 * function my_account_secure_fields( $enabled ) {
			 *     // your code here
			 *     return $enabled;
			 * }
			 * ?>
			 */
			$secure = apply_filters( 'um_account_secure_fields__enabled', true );

			return $secure;
		}


		/**
		 * Get Tab fields args
		 *
		 * @param $id
		 * @param $shortcode_args
		 *
		 * @return mixed|string|null
		 * @throws \Exception
		 */
		public function get_tab_fields( $id, $shortcode_args ) {
			$args    = array();
			$user_id = get_current_user_id();

			switch ( $id ) {

				case 'privacy':
					/**
					 * Filters extend privacy options.
					 *
					 * @since 1.0
					 * @hook um_profile_privacy_options
					 *
					 * @param {array} $options privacy options
					 *
					 * @return {array} privacy options.
					 */
					$profile_privacy = apply_filters(
						'um_profile_privacy_options',
						array(
							'Everyone' => __( 'Everyone', 'ultimate-member' ),
							'Only me'  => __( 'Only me', 'ultimate-member' ),
						)
					);

					if ( get_user_meta( $user_id, 'hide_in_members', true ) ) {
						$hide_in_members_meta = get_user_meta( $user_id, 'hide_in_members', true );
						$hide_in_members      = $hide_in_members_meta[0];
					} else {
						$hide_in_members = 'No';
					}

					$args = array(
						'id'        => 'um-' . $id . '-tab',
						'class'     => 'um-top-label um-single-button',
						'prefix_id' => '',
						'fields'    => array(
							array(
								'type'    => 'select',
								'label'   => __( 'Profile Privacy', 'ultimate-member' ),
								'helptip' => __( 'Who can see your public profile?', 'ultimate-member' ),
								'id'      => 'profile_privacy',
								'value'   => get_user_meta( $user_id, 'profile_privacy', true ),
								'options' => $profile_privacy,
							),
							array(
								'type'    => 'select',
								'label'   => __( 'Avoid indexing my profile by search engines', 'ultimate-member' ),
								'helptip' => __( 'Hide my profile for robots?', 'ultimate-member' ),
								'id'      => 'profile_noindex',
								'value'   => get_user_meta( $user_id, 'profile_noindex', true ),
								'options' => array(
									'0' => __( 'No', 'ultimate-member' ),
									'1' => __( 'Yes', 'ultimate-member' ),
								),
							),
							array(
								'type'    => 'radio',
								'label'   => __( 'Hide my profile from directory', 'ultimate-member' ),
								'helptip' => __( 'Here you can hide yourself from appearing in public directory', 'ultimate-member' ),
								'id'      => 'hide_in_members',
								'value'   => $hide_in_members,
								'options' => array(
									'No'  => __( 'No', 'ultimate-member' ),
									'Yes' => __( 'Yes', 'ultimate-member' ),
								),
							),
						),
						'hiddens'   => array(
							'um-action-privacy-tab' => 'account-privacy-tab',
							'privacy-tab-nonce'     => wp_create_nonce( 'um-privacy-tab' ),
						),
						'buttons'   => array(
							'update-privacy' => array(
								'type'  => 'submit',
								'label' => __( 'Update Privacy', 'ultimate-member' ),
								'class' => array(
									'um-button-primary',
								),
							),
						),
					);

					/**
					 * Filters extend privacy tab form.
					 *
					 * @since 1.0
					 * @hook um_account_tab_privacy_fields
					 *
					 * @param {array} $args           form arguments
					 * @param {array} $shortcode_args shortcode arguments
					 *
					 * @return {array} form arguments.
					 */
					$args = apply_filters( 'um_account_tab_privacy_fields', $args, $shortcode_args );

					break;

				case 'delete':
					if ( UM()->account()->current_password_is_required( 'delete' ) ) {
						$delete_text = UM()->options()->get( 'delete_account_text' );
					} else {
						$delete_text = UM()->options()->get( 'delete_account_no_pass_required_text' );
					}

					$args = array(
						'id'        => 'um-' . $id . '-tab',
						'class'     => 'um-top-label um-single-button',
						'prefix_id' => '',
						'fields'    => array(
							'delete_text' => array(
								'type'    => 'block',
								'id'      => 'um-delete-text',
								'content' => $delete_text,
							),
							array(
								'type'     => 'password',
								'label'    => __( 'Password', 'ultimate-member' ),
								'id'       => 'single_user_password',
								'required' => true,
								'value'    => '',
							),
						),
						'hiddens'   => array(
							'um-action-delete-tab' => 'account-delete-tab',
							'delete-tab-nonce'     => wp_create_nonce( 'um-delete-tab' ),
						),
						'buttons'   => array(
							'delete-account' => array(
								'type'  => 'submit',
								'label' => __( 'Delete Account', 'ultimate-member' ),
								'class' => array(
									'um-button-primary',
								),
							),
						),
					);

					break;

				case 'general':
					$current_user = wp_get_current_user();

					$args = array(
						'id'        => 'um-' . $id . '-tab',
						'class'     => 'um-top-label um-single-button',
						'prefix_id' => '',
						'fields'    => array(
							'user_login'           => array(
								'type'     => 'text',
								'label'    => __( 'Username', 'ultimate-member' ),
								'id'       => 'user_login',
								'required' => true,
								'value'    => $current_user->user_login,
								'disabled' => true,
							),
							'first_name'           => array(
								'type'     => 'text',
								'label'    => __( 'First Name', 'ultimate-member' ),
								'id'       => 'first_name',
								'required' => true,
								'value'    => get_user_meta( $user_id, 'first_name', true ),
							),
							'last_name'            => array(
								'type'     => 'text',
								'label'    => __( 'Last Name', 'ultimate-member' ),
								'id'       => 'last_name',
								'required' => true,
								'value'    => get_user_meta( $user_id, 'last_name', true ),
							),
							'user_email'           => array(
								'type'     => 'text',
								'label'    => __( 'E-mail Address', 'ultimate-member' ),
								'id'       => 'user_email',
								'required' => true,
								'value'    => $current_user->user_email,
							),
							'single_user_password' => array(
								'type'     => 'password',
								'label'    => __( 'Password', 'ultimate-member' ),
								'id'       => 'single_user_password',
								'required' => true,
								'value'    => '',
							),
						),
						'hiddens'   => array(
							'um-action-general-tab' => 'account-general-tab',
							'general-tab-nonce'     => wp_create_nonce( 'um-general-tab' ),
						),
						'buttons'   => array(
							'update-account' => array(
								'type'  => 'submit',
								'label' => __( 'Update account', 'ultimate-member' ),
								'class' => array(
									'um-button-primary',
								),
							),
						),
					);

					if ( ! UM()->options()->get( 'account_name' ) ) {
						unset( $args['fields']['first_name'] );
						unset( $args['fields']['last_name'] );
					} else {
						if ( UM()->options()->get( 'account_name_disable' ) ) {
							$args['fields']['first_name']['disabled'] = true;
							$args['fields']['last_name']['disabled']  = true;
						}
						if ( ! UM()->options()->get( 'account_name_require' ) ) {
							$args['fields']['first_name']['required'] = false;
							$args['fields']['last_name']['required']  = false;
						}
					}
					if ( ! $this->current_password_is_required( $id ) ) {
						unset( $args['fields']['single_user_password'] );
					}
					if ( ! UM()->options()->get( 'account_email' ) && ! um_user( 'can_edit_everyone' ) ) {
						$args['fields']['user_email']['disabled'] = true;
					}

					/**
					 * Filters extend general tab form.
					 *
					 * @since 1.0
					 * @hook um_general_tab_form_args
					 *
					 * @param {array} $args           form arguments
					 *
					 * @return {array} form arguments.
					 */
					$args = apply_filters( 'um_general_tab_form_args', $args );

					break;

				case 'password':
					$args = array(
						'id'        => 'um-' . $id . '-tab',
						'class'     => 'um-top-label um-single-button',
						'prefix_id' => '',
						'fields'    => array(
							array(
								'type'     => 'password',
								'label'    => __( 'Current Password', 'ultimate-member' ),
								'id'       => 'current_user_password',
								'required' => true,
								'value'    => '',
							),
							array(
								'type'     => 'password',
								'label'    => __( 'New Password', 'ultimate-member' ),
								'id'       => 'user_password',
								'required' => true,
								'value'    => '',
							),
							array(
								'type'     => 'password',
								'label'    => __( 'Confirm Password', 'ultimate-member' ),
								'id'       => 'confirm_user_password',
								'required' => true,
								'value'    => '',
							),
						),
						'hiddens'   => array(
							'um-action-password-tab' => 'account-password-tab',
							'password-tab-nonce'     => wp_create_nonce( 'um-password-tab' ),
						),
						'buttons'   => array(
							'update-password' => array(
								'type'  => 'submit',
								'label' => __( 'Update Password', 'ultimate-member' ),
								'class' => array(
									'um-button-primary',
								),
							),
						),
					);

					break;

				case 'notifications':
					/**
					 * Filters extend notifications tab form.
					 *
					 * @since 1.0
					 * @hook um_notifications_tab_form_args
					 *
					 * @param {array} $args           form arguments
					 *
					 * @return {array} form arguments.
					 */
					$fields = apply_filters( 'um_notifications_tab_form_args', array() );

					if ( ! empty( $fields ) ) {
						$notifications_text = '<p>' . esc_html__( 'Select what email notifications you want to receive', 'ultimate-member' ) . '</p>';

						$args = array(
							'id'        => 'um-' . $id . '-tab',
							'class'     => 'um-top-label um-single-button',
							'prefix_id' => '',
							'fields'    => array(
								array(
									'type'    => 'block',
									'id'      => 'um-notifications-text',
									'content' => $notifications_text,
								),
							),
							'hiddens'   => array(
								'um-action-notifications-tab' => 'account-notifications-tab',
								'notifications-tab-nonce' => wp_create_nonce( 'um-notifications-tab' ),
							),
							'buttons'   => array(
								'update-notifications' => array(
									'type'  => 'submit',
									'label' => __( 'Update Notifications', 'ultimate-member' ),
									'class' => array(
										'um-button-primary',
									),
								),
							),
						);

						$args['fields'] = array_merge( $args['fields'], $fields );
					}

					break;

				default:
					/**
					 * Filters extend tab form.
					 *
					 * @since 1.0
					 * @hook um_account_content_hook_{$id}
					 *
					 * @param {array} $args           form arguments
					 * @param {array} $shortcode_args shortcode arguments
					 *
					 * @return {array} form arguments.
					 */
					$args = apply_filters( "um_account_args_hook_{$id}", $args, $shortcode_args );

					break;

			}

			return $args;
		}


		/**
		 * Get Tab fields args
		 *
		 * @param $id
		 * @param $shortcode_args
		 *
		 * @return mixed|string|null
		 * @throws \Exception
		 */
		public function get_tab_content( $id, $shortcode_args ) {
			$output = apply_filters( "um_account_content_hook_{$id}", '', $shortcode_args );

			return $output;
		}


		/**
		 * Render Account Tab HTML
		 *
		 * @param $tab_id
		 * @param $tab_data
		 * @param $args
		 *
		 * @throws \Exception
		 */
		public function render_account_tab( $tab_id, $tab_data, $args ) {

			$args   = $this->get_tab_fields( $tab_id, $args );
			$output = $this->get_tab_content( $tab_id, $args );

			if ( ! empty( $tab_data['with_header'] ) ) {
				?>

				<div class="um-account-heading uimob340-hide uimob500-hide"><i class="<?php echo esc_attr( $tab_data['icon'] ); ?>"></i><?php echo esc_html( $tab_data['title'] ); ?></div>

				<?php
			}

			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_before_account_{$tab_id}
			 * @description Make some action before show account tab
			 * @input_vars
			 * [{"var":"$args","type":"array","desc":"Account Page Arguments"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_before_account_{$tab_id}', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_action( 'um_before_account_{$tab_id}', 'my_before_account_tab', 10, 1 );
			 * function my_before_account_tab( $args ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( "um_before_account_{$tab_id}", $args );
			if ( ! empty( $args ) ) {
				$tab_form = UM()->frontend()->form(
					array(
						'id' => 'um-' . $tab_id . '-tab',
					)
				);

				$tab_form->set_data( $args );
				$tab_form->display();
			}

			if ( ! empty( $output ) ) {
				echo $output;
			}

			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_after_account_{$tab_id}
			 * @description Make some action after show account tab
			 * @input_vars
			 * [{"var":"$args","type":"array","desc":"Account Page Arguments"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_after_account_{$tab_id}', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_action( 'um_after_account_{$tab_id}', 'my_after_account_tab', 10, 1 );
			 * function my_after_account_tab( $args ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( "um_after_account_{$tab_id}", $args );
		}

		/**
		 * Checks account actions require current password
		 *
		 * @param $tab_key
		 *
		 * @return bool
		 */
		public function current_password_is_required( $tab_key ) {
			$is_required = true;

			switch ( $tab_key ) {
				case 'general':
					$is_required = UM()->options()->get( 'account_general_password' );
					break;
				case 'delete':
					$is_required = UM()->options()->get( 'delete_account_password_requires' );
					break;
				case 'password':
				case 'privacy_erase_data':
				case 'privacy_download_data':
					break;
			}

			$is_required = apply_filters( "um_account_{$tab_key}_require_current", $is_required );

			return $is_required;
		}


		public function deleted_user_redirect() {
			if ( um_user( 'after_delete' ) && um_user( 'after_delete' ) === 'redirect_home' ) {
				um_redirect_home();
			} elseif ( um_user( 'delete_redirect_url' ) ) {
				/**
				 * UM hook
				 *
				 * @type filter
				 * @title um_delete_account_redirect_url
				 * @description Change redirect URL after delete account
				 * @input_vars
				 * [{"var":"$url","type":"string","desc":"Redirect URL"},
				 * {"var":"$id","type":"int","desc":"User ID"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage
				 * <?php add_filter( 'um_delete_account_redirect_url', 'function_name', 10, 2 ); ?>
				 * @example
				 * <?php
				 * add_filter( 'um_delete_account_redirect_url', 'my_delete_account_redirect_url', 10, 2 );
				 * function my_delete_account_redirect_url( $url, $id ) {
				 *     // your code here
				 *     return $url;
				 * }
				 * ?>
				 */
				$redirect_url = apply_filters( 'um_delete_account_redirect_url', um_user( 'delete_redirect_url' ), $user_id );
				// phpcs:ignore WordPress.Security.SafeRedirect
				wp_redirect( $redirect_url );
				exit();
			} else {
				um_redirect_home();
			}
		}
	}
}
