<?php
namespace um\core;


if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'um\core\Account' ) ) {


	/**
	 * Class Account
	 * @package um\core
	 */
	class Account {


		/**
		 * @var
		 */
		var $tabs;


		/**
		 * @var string
		 */
		var $current_tab = 'general';


		/**
		 * @var array
		 */
		var $displayed_fields = array();


		/**
		 * @var array
		 */
		var $tab_output = array();


		/**
		 * Account constructor.
		 */
		function __construct() {
			add_shortcode( 'ultimatemember_account', array( &$this, 'ultimatemember_account' ) );
			add_action( 'template_redirect', array( &$this, 'account_page_restrict' ), 10001 );
			add_action( 'template_redirect', array( &$this, 'account_submit' ), 10002 );
			add_filter( 'um_predefined_fields_hook', array( &$this, 'predefined_fields_hook' ), 1 );
		}


		/**
		 * Init AllTabs for user account
		 *
		 * @param $args
		 *
		 * @throws \Exception
		 */
		function init_tabs( $args ) {

			$this->tabs = $this->get_tabs();

			ksort( $this->tabs );

			$tabs_structed = array();
			foreach ( $this->tabs as $k => $arr ) {

				foreach ( $arr as $id => $info ) {

					if ( ! empty( $args['tab'] ) && $id != $args['tab'] ) {
						continue;
					}

					$output = $this->get_tab_fields( $id, $args );

					if ( ! empty( $output ) ) {
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
		function get_tabs() {
			$tabs = array();
			$tabs[100]['general'] = array(
				'icon'          => 'um-faicon-user',
				'title'         => __( 'Account', 'ultimate-member' ),
				'submit_title'  => __( 'Update Account', 'ultimate-member' ),
			);

			$tabs[200]['password'] = array(
				'icon'          => 'um-faicon-asterisk',
				'title'         => __( 'Change Password', 'ultimate-member' ),
				'submit_title'  => __( 'Update Password', 'ultimate-member' ),
			);

			$tabs[300]['privacy'] = array(
				'icon'          => 'um-faicon-lock',
				'title'         => __( 'Privacy', 'ultimate-member' ),
				'submit_title'  => __( 'Update Privacy', 'ultimate-member' ),
			);

			$tabs[400]['notifications'] = array(
				'icon'          => 'um-faicon-envelope',
				'title'         => __( 'Notifications', 'ultimate-member' ),
				'submit_title'  => __( 'Update Notifications', 'ultimate-member' ),
			);

			//if user cannot delete profile hide delete tab
			if ( um_user( 'can_delete_profile' ) || um_user( 'can_delete_everyone' ) ) {

				$tabs[99999]['delete'] = array(
					'icon'          => 'um-faicon-trash-o',
					'title'         => __( 'Delete Account', 'ultimate-member' ),
					'submit_title'  => __( 'Delete Account', 'ultimate-member' ),
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
		 * @throws \Exception
		 */
		function ultimatemember_account( $args = array() ) {
			um_fetch_user( get_current_user_id() );

			ob_start();

			$defaults = array(
				'template'  => 'account',
				'mode'      => 'account',
				'form_id'   => 'um_account_id',
			);
			$args = wp_parse_args( $args, $defaults );

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_account_shortcode_args_filter
			 * @description Account Shortcode Arguments
			 * @input_vars
			 * [{"var":"$args","type":"array","desc":"Shortcode Arguments"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_account_shortcode_args_filter', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_filter( 'um_account_shortcode_args_filter', 'my_account_shortcode_args', 10, 1 );
			 * function my_account_shortcode_args( $args ) {
			 *     // your code here
			 *     return $args;
			 * }
			 * ?>
			 */
			$args = apply_filters( 'um_account_shortcode_args_filter', $args );

			if ( ! empty( $args['tab'] ) ) {

				if ( $args['tab'] == 'account' ) {
					$args['tab'] = 'general';
				}

				$this->init_tabs( $args );

				$this->current_tab = $args['tab'];

				if ( ! empty( $this->tabs[ $args['tab'] ] ) ) { ?>
					<div class="um um-custom-shortcode-tab">
						<div class="um-form">
							<form method="post" action="">
								<?php
								/**
								 * UM hook
								 *
								 * @type action
								 * @title um_account_page_hidden_fields
								 * @description Make some action before account tab loading
								 * @input_vars
								 * [{"var":"$args","type":"array","desc":"Account Page Arguments"}]
								 * @change_log
								 * ["Since: 2.0"]
								 * @usage add_action( 'um_before_template_part', 'function_name', 10, 1 );
								 * @example
								 * <?php
								 * add_action( 'um_account_page_hidden_fields', 'my_account_page_hidden_fields', 10, 1 );
								 * function my_account_page_hidden_fields( $args ) {
								 *     // your code here
								 * }
								 * ?>
								 */
								do_action( 'um_account_page_hidden_fields', $args );

								$this->render_account_tab( $args['tab'], $this->tabs[ $args['tab'] ], $args );  ?>
							</form>
						</div>
					</div>
				<?php }

			} else {

				$this->init_tabs( $args );

				$this->current_tab = apply_filters( 'um_change_default_tab', $this->current_tab, $args );

				/**
				 * UM hook
				 *
				 * @type action
				 * @title um_pre_{$mode}_shortcode
				 * @description Make some action before account tabs loading
				 * @input_vars
				 * [{"var":"$args","type":"array","desc":"Account Page Arguments"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_action( 'um_pre_{$mode}_shortcode', 'function_name', 10, 1 );
				 * @example
				 * <?php
				 * add_action( 'um_pre_{$mode}_shortcode', 'my_pre_account_shortcode', 10, 1 );
				 * function my_pre_account_shortcode( $args ) {
				 *     // your code here
				 * }
				 * ?>
				 */
				do_action( "um_pre_{$args['mode']}_shortcode", $args );
				/**
				 * UM hook
				 *
				 * @type action
				 * @title um_before_form_is_loaded
				 * @description Make some action before account tabs loading
				 * @input_vars
				 * [{"var":"$args","type":"array","desc":"Account Page Arguments"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_action( 'um_before_form_is_loaded', 'function_name', 10, 1 );
				 * @example
				 * <?php
				 * add_action( 'um_before_form_is_loaded', 'my_before_form_is_loaded', 10, 1 );
				 * function my_before_form_is_loaded( $args ) {
				 *     // your code here
				 * }
				 * ?>
				 */
				do_action( 'um_before_form_is_loaded', $args );
				/**
				 * UM hook
				 *
				 * @type action
				 * @title um_before_{$mode}_form_is_loaded
				 * @description Make some action before account tabs loading
				 * @input_vars
				 * [{"var":"$args","type":"array","desc":"Account Page Arguments"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_action( 'um_before_{$mode}_form_is_loaded', 'function_name', 10, 1 );
				 * @example
				 * <?php
				 * add_action( 'um_before_{$mode}_form_is_loaded', 'my_before_account_form_is_loaded', 10, 1 );
				 * function my_before_account_form_is_loaded( $args ) {
				 *     // your code here
				 * }
				 * ?>
				 */
				do_action( "um_before_{$args['mode']}_form_is_loaded", $args );

				UM()->shortcodes()->template_load( $args['template'], $args );

			}

			if ( ! is_admin() && ! defined( 'DOING_AJAX' ) ) {
				UM()->shortcodes()->dynamic_css( $args );
			}

			$output = ob_get_clean();

			$this->account_fields_hash();

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
		function account_page_restrict() {

			if ( um_is_core_page( 'account' ) ) {

				//redirect to login for not logged in users
				if ( ! is_user_logged_in() ) {
					$redirect_to = add_query_arg(
						'redirect_to',
						urlencode_deep( um_get_core_page( 'account' ) ) ,
						um_get_core_page( 'login' )
					);

					exit( wp_redirect( $redirect_to ) );
				}


				//set data for fields
				UM()->fields()->set_mode = 'account';
				UM()->fields()->editing = true;

				if ( get_query_var( 'um_tab' ) ) {
					$this->current_tab = get_query_var( 'um_tab' );
				}

			}
		}


		/**
		 * Submit Account handler
		 */
		function account_submit() {

			if ( um_submitting_account_page() ) {

				UM()->form()->post_form = $_POST;

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
		function predefined_fields_hook( $predefined_fields ) {
			$account_hide_in_directory =  UM()->options()->get( 'account_hide_in_directory' );

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
		function tab_link( $id ) {

			if ( get_option( 'permalink_structure' ) ) {

				$url = trailingslashit( untrailingslashit( um_get_core_page( 'account' ) ) );
				$url = $url . $id . '/';

			} else {

				$url = add_query_arg( 'um_tab', $id, um_get_core_page( 'account' ) );

			}

			return $url;
		}


		/**
		 * @param $fields
		 * @param $shortcode_args
		 * @return mixed
		 */
		function filter_fields_by_attrs( $fields, $shortcode_args ) {
			foreach ( $fields as $k => $field ) {
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
		 * Get Tab Output
		 *
		 * @param $id
		 * @param $shortcode_args
		 *
		 * @return mixed|string|null
		 * @throws \Exception
		 */
		function get_tab_fields( $id, $shortcode_args ) {
			$output = null;

			UM()->fields()->set_mode = 'account';
			UM()->fields()->editing = true;

			if ( ! empty( $this->tab_output[ $id ]['content'] ) && ! empty( $this->tab_output[ $id ]['hash'] ) &&
			     $this->tab_output[ $id ]['hash'] == md5( json_encode( $shortcode_args ) ) ) {
				return $this->tab_output[ $id ]['content'];
			}

			switch ( $id ) {

				case 'privacy':

					$args = 'profile_privacy,hide_in_members';
					/**
					 * UM hook
					 *
					 * @type filter
					 * @title um_account_tab_privacy_fields
					 * @description Extend Account Tab Privacy
					 * @input_vars
					 * [{"var":"$args","type":"array","desc":"Account Arguments"},
					 * {"var":"$shortcode_args","type":"array","desc":"Account Shortcode Arguments"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage add_filter( 'um_account_tab_privacy_fields', 'function_name', 10, 2 );
					 * @example
					 * <?php
					 * add_filter( 'um_account_tab_privacy_fields', 'my_account_tab_privacy_fields', 10, 2 );
					 * function my_account_tab_privacy_fields( $args, $shortcode_args ) {
					 *     // your code here
					 *     return $args;
					 * }
					 * ?>
					 */
					$args = apply_filters( 'um_account_tab_privacy_fields', $args, $shortcode_args );

					$fields = UM()->builtin()->get_specific_fields( $args );
					$fields = $this->filter_fields_by_attrs( $fields, $shortcode_args );

					$this->init_displayed_fields( $fields, $id );

					foreach ( $fields as $key => $data ) {
						$output .= UM()->fields()->edit_field( $key, $data );
					}
					break;

				case 'delete':

					$args = 'single_user_password';

					/**
					 * UM hook
					 *
					 * @type filter
					 * @title um_account_tab_delete_fields
					 * @description Extend Account Tab Delete
					 * @input_vars
					 * [{"var":"$args","type":"array","desc":"Account Arguments"},
					 * {"var":"$shortcode_args","type":"array","desc":"Account Shortcode Arguments"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage add_filter( 'um_account_tab_delete_fields', 'function_name', 10, 2 );
					 * @example
					 * <?php
					 * add_filter( 'um_account_tab_delete_fields', 'my_account_tab_delete_fields', 10, 2 );
					 * function my_account_tab_delete_fields( $args, $shortcode_args ) {
					 *     // your code here
					 *     return $args;
					 * }
					 * ?>
					 */
					$args = apply_filters( 'um_account_tab_delete_fields', $args, $shortcode_args );

					$fields = UM()->builtin()->get_specific_fields( $args );
					$fields = $this->filter_fields_by_attrs( $fields, $shortcode_args );

					$this->init_displayed_fields( $fields, $id );

					foreach ( $fields as $key => $data ) {
						$output .= UM()->fields()->edit_field( $key, $data );
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

					if ( UM()->options()->get( 'account_general_password' ) ) {
						$args .= ',single_user_password';
					}

					/**
					 * UM hook
					 *
					 * @type filter
					 * @title um_account_tab_general_fields
					 * @description Extend Account Tab General
					 * @input_vars
					 * [{"var":"$args","type":"array","desc":"Account Arguments"},
					 * {"var":"$shortcode_args","type":"array","desc":"Account Shortcode Arguments"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage add_filter( 'um_account_tab_general_fields', 'function_name', 10, 2 );
					 * @example
					 * <?php
					 * add_filter( 'um_account_tab_general_fields', 'my_account_tab_general_fields', 10, 2 );
					 * function my_account_tab_general_fields( $args, $shortcode_args ) {
					 *     // your code here
					 *     return $args;
					 * }
					 * ?>
					 */
					$args = apply_filters( 'um_account_tab_general_fields', $args, $shortcode_args );

					$fields = UM()->builtin()->get_specific_fields( $args );
					$fields = $this->filter_fields_by_attrs( $fields, $shortcode_args );

					$this->init_displayed_fields( $fields, $id );

					foreach ( $fields as $key => $data ) {
						$output .= UM()->fields()->edit_field( $key, $data );
					}

					break;

				case 'password':

					$args = 'user_password';

					/**
					 * UM hook
					 *
					 * @type filter
					 * @title um_account_tab_password_fields
					 * @description Extend Account Tab Password
					 * @input_vars
					 * [{"var":"$args","type":"array","desc":"Account Arguments"},
					 * {"var":"$shortcode_args","type":"array","desc":"Account Shortcode Arguments"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage add_filter( 'um_account_tab_password_fields', 'function_name', 10, 2 );
					 * @example
					 * <?php
					 * add_filter( 'um_account_tab_password_fields', 'my_account_tab_password_fields', 10, 2 );
					 * function my_account_tab_password_fields( $args, $shortcode_args ) {
					 *     // your code here
					 *     return $args;
					 * }
					 * ?>
					 */
					$args = apply_filters( 'um_account_tab_password_fields', $args, $shortcode_args );

					$fields = UM()->builtin()->get_specific_fields( $args );
					$fields = $this->filter_fields_by_attrs( $fields, $shortcode_args );

					$this->init_displayed_fields( $fields, $id );

					foreach ( $fields as $key => $data ) {
						$output .= UM()->fields()->edit_field( $key, $data );
					}

					break;

				default :

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
					break;

			}

			$this->tab_output[ $id ] = array( 'content' => $output, 'hash' => md5( json_encode( $shortcode_args ) ) );
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
		function render_account_tab( $tab_id, $tab_data, $args ) {

			$output = $this->get_tab_fields( $tab_id, $args );

			if ( $output ) {

				if ( ! empty ( $tab_data['with_header'] ) ) { ?>

					<div class="um-account-heading uimob340-hide uimob500-hide"><i class="<?php echo esc_attr( $tab_data['icon'] ) ?>"></i><?php echo esc_html( $tab_data['title'] ); ?></div>

				<?php }

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

				if ( ! isset( $tab_data['show_button'] ) || false !== $tab_data['show_button'] ) { ?>

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

				<?php }
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

			if ( UM()->fields()->editing == true ) {
				$classes .= ' um-editing';
			}

			if ( UM()->fields()->viewing == true ) {
				$classes .= ' um-viewing';
			}

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_form_official_classes__hook
			 * @description Change not default Account tabs content
			 * @input_vars
			 * [{"var":"$classes","type":"string","desc":"Form Classes"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_form_official_classes__hook', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_filter( 'um_form_official_classes__hook', 'my_form_official_classes', 10, 1 );
			 * function my_form_official_classes( $classes ) {
			 *     // your code here
			 *     return $classes;
			 * }
			 * ?>
			 */
			$classes = apply_filters( 'um_form_official_classes__hook', $classes );
			return $classes;
		}
	}
}