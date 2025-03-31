<?php
namespace um\core;

use Exception;

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
		 * @var array
		 */
		private $account_exist = array();

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
			add_shortcode( 'ultimatemember_account', array( &$this, 'ultimatemember_account' ) );
			add_action( 'template_redirect', array( &$this, 'account_page_restrict' ), 10001 );
			add_action( 'template_redirect', array( &$this, 'account_submit' ), 10002 );
			add_filter( 'um_predefined_fields_hook', array( &$this, 'predefined_fields_hook' ), 1 );
		}

		/**
		 * Init AllTabs for user account.
		 *
		 * @param array $args
		 *
		 * @throws Exception
		 */
		public function init_tabs( $args ) {
			$this->tabs = $this->get_tabs();

			ksort( $this->tabs );

			$tabs_structured = array();
			foreach ( $this->tabs as $k => $arr ) {
				foreach ( $arr as $id => $info ) {
					if ( ! empty( $args['tab'] ) && $id !== $args['tab'] ) {
						continue;
					}

					if ( ! empty( $info['external_url'] ) ) {
						$tabs_structured[ $id ] = $info;
						continue;
					}

					$output = $this->get_tab_fields( $id, $args );
					if ( ! empty( $output ) ) {
						$tabs_structured[ $id ] = $info;
					}
				}
			}
			$this->tabs = $tabs_structured;
		}

		/**
		 * Get all Account tabs.
		 *
		 * @return array
		 */
		public function get_tabs() {
			$tabs                 = array();
			$tabs[100]['general'] = array(
				'icon'         => 'um-faicon-user',
				'title'        => __( 'My details', 'ultimate-member' ),
				'submit_title' => __( 'Update Account', 'ultimate-member' ),
			);

			$tabs[200]['password'] = array(
				'icon'         => 'um-faicon-asterisk',
				'title'        => __( 'Change Password', 'ultimate-member' ),
				'submit_title' => __( 'Update Password', 'ultimate-member' ),
			);

			$tabs[300]['privacy'] = array(
				'icon'         => 'um-faicon-lock',
				'title'        => __( 'Privacy', 'ultimate-member' ),
				'submit_title' => __( 'Update Privacy', 'ultimate-member' ),
			);

			// Init here, but default account tab content is empty, so it's hidden.
			// Init required here for the using inside the extensions where is possible to disable email notification.
			// Default Ultimate Member core notifications cannot be disabled on the user's side.
			$tabs[400]['notifications'] = array(
				'icon'         => 'um-faicon-envelope',
				'title'        => __( 'Notifications', 'ultimate-member' ),
				'submit_title' => __( 'Update Notifications', 'ultimate-member' ),
				'description'  => __( 'Select what email notifications you want to receive', 'ultimate-member' ),
			);

			// If user cannot delete profile hide delete tab.
			if ( um_user( 'can_delete_profile' ) || um_user( 'can_delete_everyone' ) ) {

				if ( UM()->is_new_ui() ) {
					$tabs[99998]['personal-data'] = array(
						'icon'        => 'um-faicon-lock',
						'title'       => __( 'Personal Data', 'ultimate-member' ),
						'description' => __( 'Helps to comply with local laws and regulations by exporting, deleting or anonymizing known data for a given user.', 'ultimate-member' ),
						'custom'      => true,
						'show_button' => false,
					);
				}

				if ( UM()->account()->current_password_is_required( 'delete' ) ) {
					$text = __( 'Are you sure you want to delete your account? This will erase all of your account data from the site. To delete your account enter your password below.', 'ultimate-member' );
				} else {
					$text = __( 'Are you sure you want to delete your account? This will erase all of your account data from the site. To delete your account, click on the button below.', 'ultimate-member' );
				}

				$tabs[99999]['delete'] = array(
					'icon'         => 'um-faicon-trash-o',
					'title'        => __( 'Delete Account', 'ultimate-member' ),
					'submit_title' => __( 'Delete Account', 'ultimate-member' ),
					'description'  => $text,
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
		 * Account Shortcode
		 *
		 * @param array $args
		 *
		 * @return false|string
		 * @throws Exception
		 */
		public function ultimatemember_account( $args = array() ) {
			if ( ! is_user_logged_in() ) {
				return '';
			}

			um_fetch_user( get_current_user_id() );

			/** There is possible to use 'shortcode_atts_ultimatemember_account' filter for getting customized $args. This filter is documented in wp-includes/shortcodes.php "shortcode_atts_{$shortcode}" */
			$args = shortcode_atts(
				array(
					'template' => 'account',
					'mode'     => 'account',
					'form_id'  => 'um_account_id',
					'tab'      => '',
				),
				$args,
				'ultimatemember_account'
			);

			/**
			 * Filters Account shortcode arguments.
			 *
			 * @since 1.3.x
			 * @hook  um_account_shortcode_args_filter
			 * @deprecated 2.6.9
			 *
			 * @param {array} $args Shortcode arguments.
			 *
			 * @return {array} Shortcode arguments.
			 *
			 * @example <caption>Change Account arguments.</caption>
			 * function my_account_shortcode_args( $args ) {
			 *     $args['tab'] = 'password';
			 *     return $args;
			 * }
			 * add_filter( 'um_account_shortcode_args_filter', 'my_account_shortcode_args' );
			 */
			$args = apply_filters_deprecated( 'um_account_shortcode_args_filter', array( $args ), '2.6.9', 'shortcode_atts_ultimatemember_account' );

			$account_hash = md5( wp_json_encode( $args ) );

			/**
			 * Filters variable for enable singleton shortcode loading on the same page.
			 * Note: Set it to `false` if you don't need to render the same form twice or more on the same page.
			 *
			 * @since 2.6.9
			 *
			 * @hook  um_ultimatemember_account_shortcode_disable_singleton
			 *
			 * @param {bool}  $disable Disabled singleton. By default, it's `true`.
			 * @param {array} $args    Shortcode arguments.
			 *
			 * @return {bool} Disabled singleton or not.
			 *
			 * @example <caption>Turn off ability to use ultimatemember_account shortcode twice.</caption>
			 * add_filter( 'um_ultimatemember_account_shortcode_disable_singleton', '__return_false' );
			 */
			$disable_singleton_shortcode = apply_filters( 'um_ultimatemember_account_shortcode_disable_singleton', true, $args );
			if ( false === $disable_singleton_shortcode && in_array( $account_hash, $this->account_exist, true ) ) {
				return '';
			}

			if ( UM()->is_new_ui() ) {
				//wp_enqueue_style( 'um_new_design' );
				wp_enqueue_script( 'um_new_design' );
				wp_enqueue_style( 'um_account' );
			}

			ob_start();

			if ( ! empty( $args['tab'] ) ) {

				if ( 'account' === $args['tab'] ) {
					$args['tab'] = 'general';
				}

				$this->init_tabs( $args );

				$this->current_tab = $args['tab'];

				if ( ! empty( $this->tabs[ $args['tab'] ] ) ) {
					if ( ! empty( $this->tabs[ $args['tab'] ]['custom'] ) &&  UM()->is_new_ui() ) {
						?>
						<div class="um um-account um-custom-account-tab um-tab-<?php echo esc_attr( $args['tab'] ); ?>-content um-custom-account-<?php echo esc_attr( $args['tab'] ); ?>-tab">
							<?php $this->render_account_tab( $args['tab'], $this->tabs[ $args['tab'] ], $args ); ?>
						</div>
						<?php
					} else {
						?>
						<div class="um um-custom-shortcode-tab">
							<div class="um-form">
								<form method="post" action="">
									<?php
									/**
									 * Fires for render account form hidden fields.
									 *
									 * @since 1.3.x
									 * @hook um_account_page_hidden_fields
									 *
									 * @param {array} $args Account shortcode arguments.
									 *
									 * @example <caption>Make some action before account tab loading.</caption>
									 * function my_account_page_hidden_fields( $args ) {
									 *     // your code here
									 * }
									 * add_action( 'um_account_page_hidden_fields', 'my_account_page_hidden_fields' );
									 */
									do_action( 'um_account_page_hidden_fields', $args, $args['tab'] );

									$this->render_account_tab( $args['tab'], $this->tabs[ $args['tab'] ], $args );
									?>
								</form>
							</div>
						</div>
						<?php
					}
				}
			} else {

				$this->init_tabs( $args );

				/**
				 * Filters Account shortcode default tab.
				 *
				 * @since 2.0
				 * @hook  um_change_default_tab
				 *
				 * @param {string} $tab  Current account tab.
				 * @param {array}  $args Shortcode arguments.
				 *
				 * @return {string} Current account tab.
				 *
				 * @example <caption>Change Account default tab to Password.</caption>
				 * function my_um_change_default_tab( $tab, $args ) {
				 *     $tab = 'password';
				 *     return $tab;
				 * }
				 * add_filter( 'um_change_default_tab', 'my_um_change_default_tab, 10, 2 );
				 */
				$this->current_tab = apply_filters( 'um_change_default_tab', $this->current_tab, $args );

				/** This action is documented in includes/common/class-shortcodes.php */
				do_action( "um_pre_{$args['mode']}_shortcode", $args );
				/** This action is documented in includes/common/class-shortcodes.php */
				do_action( 'um_before_form_is_loaded', $args );
				/** This action is documented in includes/common/class-shortcodes.php */
				do_action( "um_before_{$args['mode']}_form_is_loaded", $args );

				UM()->shortcodes()->template_load( $args['template'], $args );
			}

			if ( ! is_admin() && ! defined( 'DOING_AJAX' ) ) {
				UM()->shortcodes()->dynamic_css( $args );
			}

			$output = ob_get_clean();

			$this->account_fields_hash();

			$this->account_exist[] = $account_hash;

			return $output;
		}


		/**
		 *  Update account fields to secure the account submission
		 */
		function account_fields_hash() {
			update_user_meta( um_user( 'ID' ), 'um_account_secure_fields', UM()->account()->displayed_fields );
		}

		/**
		 * Restrict access to Account page
		 */
		public function account_page_restrict() {
			if ( um_is_core_page( 'account' ) ) {

				// Redirect to the login page for not logged-in users.
				if ( ! is_user_logged_in() ) {
					$redirect_to = add_query_arg(
						'redirect_to',
						urlencode_deep( um_get_core_page( 'account' ) ),
						um_get_core_page( 'login' )
					);

					wp_safe_redirect( $redirect_to );
					exit;
				}

				$account_tab = get_query_var( 'um_tab' );
				if ( ! empty( $account_tab ) ) {
					$exists = false;
					foreach ( $this->get_tabs() as $priority => $tabs ) {
						if ( array_key_exists( $account_tab, $tabs ) ) {
							ob_start();
							$this->render_account_tab( $account_tab, $tabs[ $account_tab ], array() );
							$content = ob_get_clean();

							if ( ! empty( $content ) ) {
								$exists = true;
							}
						}
					}
					if ( ! $exists ) {
						wp_safe_redirect( um_get_core_page( 'account' ) );
						exit;
					}
				}

				// Set data for fields.
				UM()->fields()->set_mode    = 'account';
				UM()->fields()->editing     = true;
				UM()->fields()->global_args = array(
					'mode' => 'account',
				);

				if ( get_query_var( 'um_tab' ) ) {
					$this->current_tab = get_query_var( 'um_tab' );
				}

				/**
				 * This action is fired after getting the current account tab.
				 *
				 * @hook um_account_after_getting_current_tab
				 * @param {string} $current_tab The current account tab.
				 *
				 * @since 3.0.0
				 */
				do_action( 'um_account_after_getting_current_tab', $this->current_tab );
			}
		}

		/**
		 * Submit Account handler
		 */
		function account_submit() {

			if ( um_submitting_account_page() ) {

				UM()->form()->post_form = wp_unslash( $_POST );

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

				if ( um_is_core_page( 'account' ) && get_query_var( 'um_tab' ) ) {
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
					if ( um_is_core_page( 'account' ) ) {

						$url = UM()->account()->tab_link( $this->current_tab );

						$url = add_query_arg( 'err', 'account', $url );

						if ( function_exists( 'icl_get_current_language' ) ) {
							if ( icl_get_current_language() != icl_get_default_language() ) {
								$url = UM()->permalinks()->get_current_url( true );
								$url = add_query_arg( 'err', 'account', $url );

								exit( wp_redirect( $url ) );
							}
						}
					}

					exit( wp_redirect( $url ) );
				}

			}

		}


		/**
		 * Filter account fields
		 * @param  array $predefined_fields
		 * @return array
		 */
		public function predefined_fields_hook( $predefined_fields ) {
			$account_hide_in_directory =  UM()->options()->get( 'account_hide_in_directory' );

			$account_hide_in_directory = apply_filters( 'um_account_hide_in_members_visibility', $account_hide_in_directory );

			if ( ! $account_hide_in_directory ) {
				unset( $predefined_fields['hide_in_members'] );
			}

			return $predefined_fields;
		}

		/**
		 * Get Tab Link
		 * @param  integer $id
		 * @return string
		 */
		public function tab_link( $id ) {
			if ( UM()->is_permalinks ) {
				$url  = trailingslashit( untrailingslashit( um_get_predefined_page_url( 'account' ) ) );
				$url .= $id . '/';
			} else {
				$url = add_query_arg( 'um_tab', $id, um_get_predefined_page_url( 'account' ) );
			}

			/**
			 * Filters the URL of the Ultimate Member > Account tab.
			 *
			 * @hook um_account_tab_link
			 * @since 3.0.0
			 *
			 * @param {string} $url The current url
			 * @param {string} $id  The tab ID.
			 *
			 * @return {string} The modified url of the account tab.
			 */
			return apply_filters( 'um_account_tab_link', $url, $id );
		}

		/**
		 * @param $fields
		 * @param $shortcode_args
		 * @return mixed
		 */
		function filter_fields_by_attrs( $fields, $shortcode_args ) {
			foreach ( $fields as $k => $field ) {
				if ( 'block' === $field['type'] ) {
					continue;
				}

				if ( isset( $shortcode_args[ $field['metakey'] ] ) && 0 == $shortcode_args[ $field['metakey'] ] ) {
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
		function init_displayed_fields( $fields, $tab_key ) {
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
		function add_displayed_field( $field_key, $tab_key ) {
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
		function is_secure_enabled() {
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
		 * Get Account Tab Output
		 *
		 * @param string $id
		 * @param array  $shortcode_args
		 *
		 * @return mixed|string|null
		 * @throws Exception
		 */
		public function get_tab_fields( $id, $shortcode_args ) {
			$output = null;

			UM()->fields()->set_id   = absint( $id );
			UM()->fields()->set_mode = 'account';
			UM()->fields()->editing  = true;

			if ( ! empty( $this->tab_output[ $id ]['content'] ) && ! empty( $this->tab_output[ $id ]['hash'] ) &&
			     $this->tab_output[ $id ]['hash'] == md5( json_encode( $shortcode_args ) ) ) {
				return $this->tab_output[ $id ]['content'];
			}

			switch ( $id ) {
				case 'privacy':
					$args = 'profile_privacy,profile_noindex,hide_in_members,um_show_last_login';
					/**
					 * Filters the account tab fields.
					 * Where $is is the account tab slug.
					 *
					 * @hook um_account_tab_{$id}_fields
					 * @param {array} $args           The existing account tab fields arguments.
					 * @param {array} $shortcode_args The shortcode arguments.
					 *
					 * @return {array} The modified fields that should be displayed in the account tab.
					 *
					 * @since 2.0
					 *
					 * @example <caption>Add `my_custom_email_notification` checkbox toggle option to the Notifications Account tab. Please note that `_enable_my_custom_email_notification` field has to be registered in builtin fields.</caption>
					 * ```php
					 * add_filter( 'um_account_tab_notifications_fields', 'my_account_tab_notifications_fields', 10, 2 );
					 * function my_account_tab_notifications_fields( $args, $shortcode_args ) {
					 *     $args .= ',_enable_my_custom_email_notification';
					 *     return $args;
					 * }
					 * ```
					 */
					$args = apply_filters( "um_account_tab_{$id}_fields", $args, $shortcode_args );

					$fields = UM()->builtin()->get_specific_fields( $args );
					$fields = $this->filter_fields_by_attrs( $fields, $shortcode_args );

					$this->init_displayed_fields( $fields, $id );

					foreach ( $fields as $key => $data ) {
						if ( ! empty( $shortcode_args['is_block'] ) ) {
							$data['is_block'] = true;
						}
						$output .= UM()->fields()->edit_field( $key, $data );
					}
					break;

				case 'personal-data':
					$output .= UM()->get_template( 'v3/account/personal-data.php' );
					break;

				case 'notifications':
					if ( UM()->is_new_ui() ) {
						$args = '';
						/** This filter is documented in includes/core/class-account.php */
						$args = apply_filters( "um_account_tab_{$id}_fields", $args, $shortcode_args );

						$fields = UM()->builtin()->get_specific_fields( $args );
						$fields = $this->filter_fields_by_attrs( $fields, $shortcode_args );

						$this->init_displayed_fields( $fields, $id );

						/**
						 * Filters the account tab content before the form fields added to content.
						 * Where $id is the account tab slug.
						 *
						 * @hook um_account_{$id}_content_before
						 * @param {string} $output         The existing content.
						 * @param {array}  $shortcode_args The shortcode arguments.
						 *
						 * @return {string} The modified account content.
						 *
						 * @since 3.0.0
						 */
						$output = apply_filters( "um_account_{$id}_content_before", $output, $shortcode_args );

						foreach ( $fields as $key => $data ) {
							if ( ! empty( $shortcode_args['is_block'] ) ) {
								$data['is_block'] = true;
							}
							$output .= UM()->fields()->edit_field( $key, $data );
						}
						/**
						 * Filters the account tab content after the form fields added to content.
						 * Where $id is the account tab slug.
						 *
						 * @hook um_account_{$id}_content_after
						 * @param {string} $output         The existing content.
						 * @param {array}  $shortcode_args The shortcode arguments.
						 *
						 * @return {string} The modified account content.
						 *
						 * @since 3.0.0
						 */
						$output = apply_filters( "um_account_{$id}_content_after", $output, $shortcode_args );
					}
					break;

				case 'delete':
					$args = '';
					if ( $this->current_password_is_required( $id ) ) {
						$args = 'single_user_password';
					}

					/** This filter is documented in includes/core/class-account.php */
					$args = apply_filters( "um_account_tab_{$id}_fields", $args, $shortcode_args );

					$fields = UM()->builtin()->get_specific_fields( $args );
					$fields = $this->filter_fields_by_attrs( $fields, $shortcode_args );

					$this->init_displayed_fields( $fields, $id );

					foreach ( $fields as $key => $data ) {
						if ( ! empty( $shortcode_args['is_block'] ) ) {
							$data['is_block'] = true;
						}
						$output .= UM()->fields()->edit_field( $key, $data );
					}

					if ( ! $output && ! $this->current_password_is_required( $id ) ) {
						$output = '<div></div>';
					}

					break;

				case 'general':
					$args = 'user_login,first_name,last_name,user_email';

					if ( ! UM()->options()->get( 'account_name' ) ) {
						$args = 'user_login,user_email';
					}

					if ( ! UM()->options()->get( 'account_email' ) && ! um_user( 'can_edit_everyone' ) ) {
						$args = str_replace(',user_email','', $args );
					}

					if ( $this->current_password_is_required( $id ) ) {
						$args .= ',single_user_password';
					}

					/** This filter is documented in includes/core/class-account.php */
					$args = apply_filters( "um_account_tab_{$id}_fields", $args, $shortcode_args );

					$fields = UM()->builtin()->get_specific_fields( $args );
					$fields = $this->filter_fields_by_attrs( $fields, $shortcode_args );

					$this->init_displayed_fields( $fields, $id );

					foreach ( $fields as $key => $data ) {
						if ( ! empty( $shortcode_args['is_block'] ) ) {
							$data['is_block'] = true;
						}
						$output .= UM()->fields()->edit_field( $key, $data );
					}
					break;

				case 'password':
					$args = 'user_password';
					/** This filter is documented in includes/core/class-account.php */
					$args = apply_filters( "um_account_tab_{$id}_fields", $args, $shortcode_args );

					$fields = UM()->builtin()->get_specific_fields( $args );
					$fields = $this->filter_fields_by_attrs( $fields, $shortcode_args );

					$this->init_displayed_fields( $fields, $id );

					foreach ( $fields as $key => $data ) {
						if ( ! empty( $shortcode_args['is_block'] ) ) {
							$data['is_block'] = true;
						}
						$output .= UM()->fields()->edit_field( $key, $data );
					}
					break;

			}

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_account_content_hook_{$id}
			 * @description Change not default Account tabs content
			 * @input_vars
			 * [{"var":"$output","type":"string","desc":"Account Tab Output"},
			 * {"var":"$shortcode_args","type":"array","desc":"Account Shortcode Arguments"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_account_content_hook_{$id}', 'function_name', 10, 2 );
			 * @example
			 * <?php
			 * add_filter( 'um_account_content_hook_{$id}', 'my_account_content', 10, 2 );
			 * function my_account_tab_password_fields( $args, $shortcode_args ) {
			 *     // your code here
			 *     return $args;
			 * }
			 * ?>
			 */
			$output = apply_filters( "um_account_content_hook_{$id}", $output, $shortcode_args );

			$this->tab_output[ $id ] = array(
				'content' => $output,
				'hash'    => md5( json_encode( $shortcode_args ) ),
			);
			return $output;
		}


		/**
		 * Render Account Tab HTML
		 *
		 * @param $tab_id
		 * @param $tab_data
		 * @param $args
		 *
		 * @throws Exception
		 */
		public function render_account_tab( $tab_id, $tab_data, $args ) {
			$output = $this->get_tab_fields( $tab_id, $args );
			if ( ! $output ) {
				return;
			}

			if ( UM()->is_new_ui() ) {
				?>
				<div class="um-section-header">
					<p class="um-section-title"><?php echo esc_html( $tab_data['title'] ); ?></p>
					<?php if ( ! empty( $tab_data['description'] ) ) { ?>
						<p class="um-supporting-text"><?php echo esc_html( $tab_data['description'] ); ?></p>
					<?php } ?>
				</div>
				<?php
			} else {
				if ( ! empty( $tab_data['with_header'] ) ) {
					?>
					<div class="um-account-heading uimob340-hide uimob500-hide"><i class="<?php echo esc_attr( $tab_data['icon'] ); ?>"></i><?php echo esc_html( $tab_data['title'] ); ?></div>
					<?php
				}
			}

			if ( UM()->is_new_ui() ) {
				/** This action is documented in includes/core/um-actions-profile.php */
				do_action( 'um_before_form', $args );
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

			if ( UM()->is_new_ui() ) {
				if ( ! empty( $tab_data['custom'] ) ) {
					echo $output;

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
				} else {
					?>
					<form method="post" action="" class="um-form-new">
						<div class="um-form-rows">
							<div class="um-form-row">
								<div class="um-form-cols um-form-cols-1">
									<div class="um-form-col um-form-col-1">
										<?php
										echo $output;

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
										?>
									</div>
								</div>
							</div>
						</div>
						<?php
						if ( ! isset( $tab_data['show_button'] ) || false !== $tab_data['show_button'] ) {
							?>
							<div class="um-form-submit">
								<input type="hidden" name="um_account_nonce_<?php echo esc_attr( $tab_id ) ?>" value="<?php echo esc_attr( wp_create_nonce( 'um_update_account_' . $tab_id ) ) ?>" />

								<?php
								/**
								 * UM hook
								 *
								 * @type action
								 * @title um_account_page_hidden_fields
								 * @description Show hidden fields on account form
								 * @input_vars
								 * [{"var":"$args","type":"array","desc":"Account shortcode arguments"}]
								 * @change_log
								 * ["Since: 2.0","Since: 3.0.0 - Added $tab_id"]
								 * @usage add_action( 'um_account_page_hidden_fields', 'function_name', 10, 1 );
								 * @example
								 * <?php
								 * add_action( 'um_account_page_hidden_fields', 'my_account_page_hidden_fields', 10, 1 );
								 * function my_account_page_hidden_fields( $args ) {
								 *     // your code here
								 * }
								 * ?>
								 */
								do_action( 'um_account_page_hidden_fields', $args, $tab_id );

								$submit_title = ! empty( $tab_data['submit_title'] ) ? $tab_data['submit_title'] : $tab_data['title'];
								echo UM()->frontend()::layouts()::button(
									$submit_title,
									array(
										'type'   => 'submit',
										'design' => 'primary',
										'width'  => 'full',
										'id'     => 'um_account_submit_' . $tab_id,
									)
								);

								/**
								 * UM hook
								 *
								 * @type action
								 * @title um_after_account_{$tab_id}_button
								 * @description Make some action after show account tab button
								 * @change_log
								 * ["Since: 2.0"]
								 * @usage add_action( 'um_after_account_{$tab_id}_button', 'function_name', 10 );
								 * @example
								 * <?php
								 * add_action( 'um_after_account_{$tab_id}_button', 'my_after_account_tab_button', 10 );
								 * function my_after_account_tab_button() {
								 *     // your code here
								 * }
								 * ?>
								 */
								do_action( "um_after_account_{$tab_id}_button" );
								?>
							</div>
							<?php
						}
						?>
					</form>
					<?php
				}
			} else {
				echo $output;

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

				if ( ! isset( $tab_data['show_button'] ) || false !== $tab_data['show_button'] ) {
					?>

					<div class="um-col-alt um-col-alt-b">
						<div class="um-left">
							<?php $submit_title = ! empty( $tab_data['submit_title'] ) ? $tab_data['submit_title'] : $tab_data['title']; ?>
							<input type="hidden" name="um_account_nonce_<?php echo esc_attr( $tab_id ) ?>" value="<?php echo esc_attr( wp_create_nonce( 'um_update_account_' . $tab_id ) ) ?>" />
							<input type="submit" name="um_account_submit" id="um_account_submit_<?php echo esc_attr( $tab_id ) ?>"  class="um-button" value="<?php echo esc_attr( $submit_title ) ?>" />
						</div>

						<?php
						/**
						 * UM hook
						 *
						 * @type action
						 * @title um_after_account_{$tab_id}_button
						 * @description Make some action after show account tab button
						 * @change_log
						 * ["Since: 2.0"]
						 * @usage add_action( 'um_after_account_{$tab_id}_button', 'function_name', 10 );
						 * @example
						 * <?php
						 * add_action( 'um_after_account_{$tab_id}_button', 'my_after_account_tab_button', 10 );
						 * function my_after_account_tab_button() {
						 *     // your code here
						 * }
						 * ?>
						 */
						do_action( "um_after_account_{$tab_id}_button" ); ?>

						<div class="um-clear"></div>
					</div>

					<?php
				}
			}
		}


		/**
		 * Add class based on shortcode
		 *
		 * @param  string $mode
		 * @return string
		 */
		function get_class( $mode ) {

			$classes = 'um-'.$mode;

			if ( is_admin() ) {
				$classes .= ' um-in-admin';
			}

			if ( true === UM()->fields()->editing ) {
				$classes .= ' um-editing';
			}

			if ( true === UM()->fields()->viewing ) {
				$classes .= ' um-viewing';
			}

			/** This filter is documented in includes/common/class-shortcodes.php */
			return apply_filters( 'um_form_official_classes__hook', $classes );
		}

		/**
		 * Checks account actions require current password.
		 *
		 * @param string $tab_key
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
				case 'password':
				case 'privacy_erase_data':
				case 'privacy_download_data':
					break;
			}

			return apply_filters( "um_account_{$tab_key}_require_current", $is_required );
		}

		/**
		 * Check the conditional hook for getting notifications tab data.
		 *
		 * @return bool
		 */
		public static function is_notifications_tab_visible() {
			return apply_filters( 'um_account_notifications_tab_enabled', false );
		}
	}
}
