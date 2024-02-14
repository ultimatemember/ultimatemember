<?php
namespace um\admin\core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\admin\core\Admin_Notices' ) ) {

	/**
	 * Class Admin_Notices
	 * @package um\admin\core
	 */
	class Admin_Notices {

		/**
		 * Notices list
		 *
		 * @var array
		 */
		private $list = array();

		/**
		 * Admin_Notices constructor.
		 */
		public function __construct() {
			add_action( 'admin_init', array( &$this, 'create_languages_folder' ) );

			add_action( 'admin_init', array( &$this, 'create_list' ) );
			add_action( 'admin_notices', array( &$this, 'render_notices' ), 1 );

			add_action( 'wp_ajax_um_dismiss_notice', array( &$this, 'dismiss_notice' ) );
			add_action( 'admin_init', array( &$this, 'force_dismiss_notice' ) );

			add_action( 'current_screen', array( &$this, 'create_list_for_screen' ) );
		}

		/**
		 *
		 */
		public function create_list() {
			$this->old_extensions_notice();
			$this->show_update_messages();
			$this->check_wrong_install_folder();
			$this->need_upgrade();
			$this->check_wrong_licenses();

			$this->extensions_page();

			$this->template_version();

			// Removed for now to avoid the bad reviews.
			//$this->reviews_notice();

			//$this->future_changed();

			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_admin_create_notices
			 * @description Add notices to wp-admin
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_admin_create_notices', 'function_name', 10 );
			 * @example
			 * <?php
			 * add_action( 'um_admin_create_notices', 'my_admin_create_notices', 10 );
			 * function my_admin_create_notices() {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_admin_create_notices' );
		}

		public function create_list_for_screen() {
			if ( UM()->admin()->screen()->is_own_screen() ) {
				$this->secure_settings();
			}
		}


		/**
		 * @return array
		 */
		public function get_admin_notices() {
			return $this->list;
		}


		/**
		 * @param $admin_notices
		 */
		function set_admin_notices( $admin_notices ) {
			$this->list = $admin_notices;
		}


		/**
		 * @param $a
		 * @param $b
		 *
		 * @return mixed
		 */
		function notice_priority_sort( $a, $b ) {
			if ( $a['priority'] == $b['priority'] ) {
				return 0;
			}
			return ( $a['priority'] < $b['priority'] ) ? -1 : 1;
		}


		/**
		 * Add notice to UM notices array
		 *
		 * @param string $key
		 * @param array $data
		 * @param int $priority
		 */
		function add_notice( $key, $data, $priority = 10 ) {
			$admin_notices = $this->get_admin_notices();

			if ( empty( $admin_notices[ $key ] ) ) {
				$admin_notices[ $key ] = array_merge( $data, array( 'priority' => $priority ) );
				$this->set_admin_notices( $admin_notices );
			}
		}


		/**
		 * Remove notice from UM notices array
		 *
		 * @param string $key
		 */
		function remove_notice( $key ) {
			$admin_notices = $this->get_admin_notices();

			if ( ! empty( $admin_notices[ $key ] ) ) {
				unset( $admin_notices[ $key ] );
				$this->set_admin_notices( $admin_notices );
			}
		}


		/**
		 * Render all admin notices
		 */
		function render_notices() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$admin_notices = $this->get_admin_notices();

			$hidden = get_option( 'um_hidden_admin_notices', array() );

			uasort( $admin_notices, array( &$this, 'notice_priority_sort' ) );

			foreach ( $admin_notices as $key => $admin_notice ) {
				if ( empty( $hidden ) || ! in_array( $key, $hidden ) ) {
					$this->display_notice( $key );
				}
			}

			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_admin_after_main_notices
			 * @description Insert some content after main admin notices
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_admin_after_main_notices', 'function_name', 10 );
			 * @example
			 * <?php
			 * add_action( 'um_admin_after_main_notices', 'my_admin_after_main_notices', 10 );
			 * function my_admin_after_main_notices() {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_admin_after_main_notices' );
		}


		/**
		 * Display single admin notice
		 *
		 * @param string $key
		 * @param bool $echo
		 *
		 * @return void|string
		 */
		function display_notice( $key, $echo = true ) {
			$admin_notices = $this->get_admin_notices();

			if ( empty( $admin_notices[ $key ] ) ) {
				return;
			}

			$notice_data = $admin_notices[ $key ];

			$class = ! empty( $notice_data['class'] ) ? $notice_data['class'] : 'updated';

			$dismissible = ! empty( $admin_notices[ $key ]['dismissible'] );

			ob_start(); ?>

			<div class="<?php echo esc_attr( $class ) ?> um-admin-notice notice <?php echo $dismissible ? 'is-dismissible' : '' ?>" data-key="<?php echo esc_attr( $key ) ?>">
				<?php echo ! empty( $notice_data['message'] ) ? $notice_data['message'] : '' ?>
			</div>

			<?php $notice = ob_get_clean();
			if ( $echo ) {
				echo $notice;
				return;
			} else {
				return $notice;
			}
		}

		/**
		 * Checking if the "Membership - Anyone can register" WordPress general setting is active
		 */
		public function extensions_page() {
			global $pagenow;
			if ( isset( $pagenow ) && 'admin.php' === $pagenow && isset( $_GET['page'] ) && 'ultimatemember-extensions' === $_GET['page'] ) {
				ob_start();
				?>

				<p>
					<?php _e( '<strong>All Access Pass</strong> – Get access to all Ultimate Member extensions at a significant discount with our All Access Pass.', 'ultimate-member' ) ?>
				</p>
				<p>
					<a href="https://ultimatemember.com/pricing/" class="button button-primary" target="_blank">
						<?php _e( 'View Pricing', 'ultimate-member' ) ?>
					</a>
				</p>

				<?php
				$message = ob_get_clean();

				$this->add_notice(
					'extensions_all_access',
					array(
						'class'       => 'info',
						'message'     => $message,
						'dismissible' => false,
					),
					10
				);
			}
		}


		/**
		 * To store plugin languages
		 */
		function create_languages_folder() {
			$path = UM()->files()->upload_basedir;
			$path = str_replace( '/uploads/ultimatemember', '', $path );
			$path = $path . '/languages/plugins/';
			$path = str_replace( '//', '/', $path );

			if ( ! file_exists( $path ) ) {
				$old = umask(0);
				@mkdir( $path, 0777, true );
				umask( $old );
			}
		}


		/**
		 * Show notice for customers with old extension's versions
		 */
		public function old_extensions_notice() {
			$show = false;

			$old_extensions = array(
				'bbpress',
				'followers',
				'friends',
				'instagram',
				'mailchimp',
				'messaging',
				'mycred',
				'notices',
				'notifications',
				'online',
				'private-content',
				'profile-completeness',
				'recaptcha',
				'reviews',
				'social-activity',
				'social-login',
				'terms-conditions',
				'user-tags',
				'verified-users',
				'woocommerce',
			);

			$slugs = array_map(
				function( $item ) {
					return 'um-' . $item . '/um-' . $item . '.php';
				},
				$old_extensions
			);

			$active_plugins = UM()->dependencies()->get_active_plugins();
			foreach ( $slugs as $slug ) {
				if ( in_array( $slug, $active_plugins ) ) {
					$path = wp_normalize_path( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $slug );
					if ( ! file_exists( $path ) ) {
						continue;
					}
					$plugin_data = get_plugin_data( $path );
					if ( version_compare( '2.0', $plugin_data['Version'], '>' ) ) {
						$show = true;
						break;
					}
				}
			}

			if ( ! $show ) {
				return;
			}

			$this->add_notice(
				'old_extensions',
				array(
					'class'   => 'error',
					// translators: %1$s is a plugin name; %2$s is a plugin version; %3$s is a plugin name; %4$s is a doc link.
					'message' => '<p>' . sprintf( __( '<strong>%1$s %2$s</strong> requires 2.0 extensions. You have pre 2.0 extensions installed on your site. <br /> Please update %3$s extensions to latest versions. For more info see this <a href="%4$s" target="_blank">doc</a>.', 'ultimate-member' ), UM_PLUGIN_NAME, UM_VERSION, UM_PLUGIN_NAME, 'https://docs.ultimatemember.com/article/201-how-to-update-your-site' ) . '</p>',
				),
				0
			);
		}


		/**
		 * Updating users
		 */
		public function show_update_messages() {
			if ( ! isset( $_REQUEST['update'] ) ) {
				return;
			}

			$update = sanitize_key( $_REQUEST['update'] );
			switch ( $update ) {
				case 'um_purged_temp':
					$messages[0]['content'] = __( 'Your temp uploads directory is now clean.', 'ultimate-member' );
					break;
				case 'um_cleared_cache':
					$messages[0]['content'] = __( 'Your user cache is now removed.', 'ultimate-member' );
					break;
				case 'um_cleared_status_cache':
					$messages[0]['content'] = __( 'Your user statuses cache is now removed.', 'ultimate-member' );
					break;
				case 'um_got_updates':
					$messages[0]['content'] = __( 'You have the latest updates.', 'ultimate-member' );
					break;
				case 'um_often_updates':
					$messages[0]['err_content'] = __( 'Try again later. You can run this action once daily.', 'ultimate-member' );
					break;
				case 'um_form_duplicated':
					$messages[0]['content'] = __( 'The form has been duplicated successfully.', 'ultimate-member' );
					break;
				case 'um_settings_updated':
					$messages[0]['content'] = __( 'Settings have been saved successfully.', 'ultimate-member' );
					break;
				case 'um_user_updated':
					$messages[0]['content'] = __( 'User has been updated.', 'ultimate-member' );
					break;
				case 'um_users_updated':
					$messages[0]['content'] = __( 'Users have been updated.', 'ultimate-member' );
					break;
				case 'um_secure_expire_sessions':
					$messages[0]['content'] = __( 'All users sessions have been successfully destroyed.', 'ultimate-member' );
					break;
				case 'um_secure_restore':
					$messages[0]['content'] = __( 'Account has been successfully restored.', 'ultimate-member' );
					break;
				default:
					/**
					 * Filters the custom admin notice after um_adm_action.
					 *
					 * @param {array}  $messages Admin notice messages.
					 * @param {string} $update   Update action key.
					 *
					 * @return {array} Admin notice messages.
					 *
					 * @since 2.6.8
					 * @hook um_adm_action_custom_update_notice
					 *
					 * @example <caption>Add custom admin notice after {custom_update_key} action.</caption>
					 * function my_um_adm_action_custom_update_notice( $messages, $update ) {
					 *     if ( 'custom_update_key' === $update ) {
					 *         $messages[0]['content'] = 'custom notice text';
					 *     }
					 *     return $messages;
					 * }
					 * add_filter( 'um_adm_action_custom_update_notice', 'my_um_adm_action_custom_update_notice', 10, 2 );
					 */
					$messages = apply_filters( 'um_adm_action_custom_update_notice', array(), $update );
					break;
			}

			if ( ! empty( $messages ) ) {
				foreach ( $messages as $message ) {
					if ( isset( $message['err_content'] ) ) {
						$this->add_notice(
							'actions',
							array(
								'class'   => 'error',
								'message' => '<p>' . $message['err_content'] . '</p>',
							),
							50
						);
					} else {
						$this->add_notice(
							'actions',
							array(
								'class'   => 'updated',
								'message' => '<p>' . $message['content'] . '</p>',
							),
							50
						);
					}
				}
			}
		}

		/**
		 * Check if plugin is installed with correct folder
		 */
		public function check_wrong_install_folder() {
			$invalid_folder = false;

			$slug_array = explode( '/', UM_PLUGIN );
			if ( 'ultimate-member' !== $slug_array[0] ) {
				$invalid_folder = true;
			}

			if ( $invalid_folder ) {
				$this->add_notice(
					'invalid_dir',
					array(
						'class'   => 'error',
						// translators: %s: Plugin name.
						'message' => '<p>' . sprintf( __( 'You have installed <strong>%s</strong> with wrong folder name. Correct folder name is <strong>"ultimate-member"</strong>.', 'ultimate-member' ), UM_PLUGIN_NAME ) . '</p>',
					),
					1
				);
			}
		}


		public function check_wrong_licenses() {
			$invalid_license           = 0;
			$arr_inactive_license_keys = array();

			if ( empty( UM()->admin_settings()->settings_structure['licenses']['fields'] ) ) {
				return;
			}

			foreach ( UM()->admin_settings()->settings_structure['licenses']['fields'] as $field_data ) {
				$license = get_option( "{$field_data['id']}_edd_answer" );

				if ( ( is_object( $license ) && 'valid' === $license->license ) || 'valid' === $license ) {
					continue;
				}

				if ( ( is_object( $license ) && 'inactive' === $license->license ) || 'inactive' === $license ) {
					$arr_inactive_license_keys[] = $license->item_name;
				}

				$invalid_license++;
			}

			if ( ! empty( $arr_inactive_license_keys ) ) {
				$this->add_notice(
					'license_key',
					array(
						'class'   => 'error',
						// translators: %1$s is a inactive license number; %2$s is a plugin name; %3$s is a store link.
						'message' => '<p>' . sprintf( __( 'There are %1$s inactive %2$s license keys for this site. This site is not authorized to get plugin updates. You can active this site on <a href="%3$s">www.ultimatemember.com</a>.', 'ultimate-member' ), count( $arr_inactive_license_keys ), UM_PLUGIN_NAME, UM()->store_url ) . '</p>',
					),
					3
				);
			}

			if ( $invalid_license ) {
				$this->add_notice(
					'license_key',
					array(
						'class'   => 'error',
						// translators: %1$s is a invalid license; %2$s is a plugin name; %3$s is a license link.
						'message' => '<p>' . sprintf( __( 'You have %1$s invalid or expired license keys for %2$s. Please go to the <a href="%3$s">Licenses page</a> to correct this issue.', 'ultimate-member' ), $invalid_license, UM_PLUGIN_NAME, add_query_arg( array( 'page' => 'um_options', 'tab' => 'licenses' ), admin_url( 'admin.php' ) ) ) . '</p>',
					),
					3
				);
			}
		}


		public function need_upgrade() {
			if ( ! empty( UM()->admin_upgrade()->necessary_packages ) ) {

				$url = add_query_arg( array( 'page' => 'um_upgrade' ), admin_url( 'admin.php' ) );

				ob_start();
				?>

				<p>
					<strong><?php echo esc_html( UM_PLUGIN_NAME . ' ' . UM_VERSION . ' ' . __( 'version needs to be updated to work correctly.', 'ultimate-member' ) ); ?></strong>
					<br />
					<?php esc_html_e( 'It is necessary to update the structure of the database and options that are associated with current version', 'ultimate-member' ); ?>
					<br />
					<?php esc_html_e( 'Please run the upgrade process on this ', 'ultimate-member' ); ?><a href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'page', 'ultimate-member' ); ?></a>
				</p>

				<p>
					<a href="<?php echo esc_url( $url ); ?>" class="button button-primary"><?php esc_html_e( 'Visit Upgrade Page', 'ultimate-member' ); ?></a>
					&nbsp;
				</p>

				<?php $message = ob_get_clean();

				$this->add_notice(
					'upgrade',
					array(
						'class'     => 'error',
						'message'   => $message,
					),
					4
				);
			} else {
				if ( isset( $_GET['msg'] ) && 'updated' === sanitize_key( $_GET['msg'] ) ) {
					if ( isset( $_GET['page'] ) && 'um_options' === sanitize_key( $_GET['page'] ) ) {
						$this->add_notice(
							'settings_upgrade',
							array(
								'class'   => 'updated',
								'message' => '<p>' . esc_html__( 'Settings successfully upgraded', 'ultimate-member' ) . '</p>',
							),
							4
						);
					} else {
						$this->add_notice(
							'upgrade',
							array(
								'class'   => 'updated',
								// translators: %1$s is a plugin name title; %2$s is a plugin version.
								'message' => '<p>' . sprintf( __( '<strong>%1$s %2$s</strong> Successfully Upgraded', 'ultimate-member' ), UM_PLUGIN_NAME, UM_VERSION ) . '</p>',
							),
							4
						);
					}
				}
			}
		}

		/**
		 * @note Removed for now to avoid the bad reviews.
		 * @deprecated 2.8.0
		 */
		private function reviews_notice() {
			$first_activation_date = get_option( 'um_first_activation_date', false );

			if ( empty( $first_activation_date ) ) {
				return;
			}

			if ( $first_activation_date + 2 * WEEK_IN_SECONDS > time() ) {
				return;
			}

			ob_start();
			?>

			<div id="um_start_review_notice">
				<p>
					<?php
					// translators: %s: plugin name.
					echo wp_kses( sprintf( __( 'Hey there! It\'s been one month since you installed %s. How have you found the plugin so far?', 'ultimate-member' ), UM_PLUGIN_NAME ), UM()->get_allowed_html( 'admin_notice' ) );
					?>
				</p>
				<p>
					<a href="javascript:void(0);" id="um_add_review_love"><?php _e( 'I love it!', 'ultimate-member' ) ?></a>&nbsp;|&nbsp;
					<a href="javascript:void(0);" id="um_add_review_good"><?php _e('It\'s good but could be better', 'ultimate-member' ) ?></a>&nbsp;|&nbsp;
					<a href="javascript:void(0);" id="um_add_review_bad"><?php _e('I don\'t like the plugin', 'ultimate-member' ) ?></a>
				</p>
			</div>
			<div class="um-hidden-notice" data-key="love">
				<p>
					<?php printf( __( 'Great! We\'re happy to hear that you love the plugin. It would be amazing if you could let others know why you like %s by leaving a review of the plugin. This will help %s to grow and become more popular and would be massively appreciated by us!' ), UM_PLUGIN_NAME, UM_PLUGIN_NAME ); ?>
				</p>

				<p>
					<a href="https://wordpress.org/support/plugin/ultimate-member/reviews/?rate=5#new-post" target="_blank" class="button button-primary um_review_link"><?php _e( 'Leave Review', 'ultimate-member' ) ?></a>
				</p>
			</div>
			<div class="um-hidden-notice" data-key="good">
				<p>
					<?php _e( 'We\'re glad to hear that you like the plugin but we would love to get your feedback so we can make the plugin better.' ); ?>
				</p>

				<p>
					<a href="https://ultimatemember.com/feedback/" target="_blank" class="button button-primary um_review_link"><?php _e( 'Provide Feedback', 'ultimate-member' ) ?></a>
				</p>
			</div>
			<div class="um-hidden-notice" data-key="bad">
				<p>
					<?php printf( __( 'We\'re sorry to hear that. If you\'re having the issue with the plugin you can create a topic on our <a href="%s" target="_blank">support forum</a> and we will try and help you out with the issue. Alternatively if you have an idea on how we can make the plugin better or want to tell us what you don\'t like about the plugin you can tell us know by giving us feedback.' ), 'https://wordpress.org/support/plugin/ultimate-member' ); ?>
				</p>

				<p>
					<a href="https://ultimatemember.com/feedback/" target="_blank" class="button button-primary um_review_link"><?php _e( 'Provide Feedback', 'ultimate-member' ) ?></a>
				</p>
			</div>

			<?php $message = ob_get_clean();

			$this->add_notice( 'reviews_notice', array(
				'class'         => 'updated',
				'message'       => $message,
				'dismissible'   => true
			), 1 );
		}


		/**
		 * Check Future Changes notice
		 */
		function future_changed() {

			ob_start(); ?>

			<p>
				<?php
				// translators: %1$s is a plugin name; %2$s is a #.
				echo wp_kses( sprintf( __( '<strong>%1$s</strong> future plans! Detailed future list is <a href="%2$s" target="_blank">here</a>', 'ultimate-member' ), UM_PLUGIN_NAME, '#' ), UM()->get_allowed_html( 'admin_notice' ) );
				?>
			</p>

			<?php $message = ob_get_clean();

			$this->add_notice( 'future_changes', array(
				'class'         => 'updated',
				'message'       => $message,
			), 2 );
		}

		/**
		 * Check Templates Versions notice
		 */
		public function template_version() {
			if ( true === (bool) get_option( 'um_override_templates_outdated' ) ) {
				$link = admin_url( 'admin.php?page=um_options&tab=advanced&section=override_templates' );
				ob_start();
				?>

				<p>
					<?php
					// translators: %s override templates page link.
					echo wp_kses( sprintf( __( 'Your templates are out of date. Please visit <a href="%s">override templates status page</a> and update templates.', 'ultimate-member' ), $link ), UM()->get_allowed_html( 'admin_notice' ) );
					?>
				</p>

				<?php
				$message = ob_get_clean();
				UM()->admin()->notices()->add_notice(
					'um_override_templates_notice',
					array(
						'class'       => 'error',
						'message'     => $message,
						'dismissible' => false,
					),
					10
				);
			}
		}

		/**
		 * First time installed Secure settings.
		 */
		public function secure_settings() {
			ob_start();
			?>
			<p>
				<strong><?php esc_html_e( 'Important Update', 'ultimate-member' ); ?></strong><br/>
				<?php esc_html_e( 'Ultimate Member has a new additional feature to secure your Ultimate Member forms to prevent attacks from injecting accounts with administrative roles &amp; capabilities.', 'ultimate-member' ); ?>
			</p>
			<p>
				<a class="button button-primary" href="<?php echo esc_attr( admin_url( 'admin.php?page=um_options&tab=advanced&section=secure&um_dismiss_notice=secure_settings&um_admin_nonce=' . wp_create_nonce( 'um-admin-nonce' ) ) ); ?>"><?php esc_html_e( 'Manage Security Settings', 'ultimate-member' ); ?></a>
				<a class="button" target="_blank" href="https://docs.ultimatemember.com/article/1869-security-feature"><?php esc_html_e( 'Read the documentation', 'ultimate-member' ); ?></a>
			</p>
			<?php
			$message = ob_get_clean();
			$this->add_notice(
				'secure_settings',
				array(
					'class'       => 'warning',
					'message'     => $message,
					'dismissible' => true,
				),
				1
			);
		}

		public function dismiss_notice() {
			UM()->admin()->check_ajax_nonce();

			if ( empty( $_POST['key'] ) ) {
				wp_send_json_error( __( 'Wrong Data', 'ultimate-member' ) );
			}

			$this->dismiss( sanitize_key( $_POST['key'] ) );

			wp_send_json_success();
		}

		/**
		 * Dismiss notice by key.
		 *
		 * @param string $key
		 *
		 * @return void
		 */
		public function dismiss( $key ) {
			$hidden_notices = get_option( 'um_hidden_admin_notices', array() );
			if ( ! is_array( $hidden_notices ) ) {
				$hidden_notices = array();
			}
			$hidden_notices[] = $key;
			update_option( 'um_hidden_admin_notices', $hidden_notices );
		}

		function force_dismiss_notice() {
			if ( ! empty( $_REQUEST['um_dismiss_notice'] ) && ! empty( $_REQUEST['um_admin_nonce'] ) ) {
				if ( wp_verify_nonce( $_REQUEST['um_admin_nonce'], 'um-admin-nonce' ) ) {
					$hidden_notices = get_option( 'um_hidden_admin_notices', array() );
					if ( ! is_array( $hidden_notices ) ) {
						$hidden_notices = array();
					}

					$hidden_notices[] = sanitize_key( $_REQUEST['um_dismiss_notice'] );

					update_option( 'um_hidden_admin_notices', $hidden_notices );
				} else {
					wp_die( __( 'Security Check', 'ultimate-member' ) );
				}
			}
		}
	}
}
