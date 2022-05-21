<?php
/**
 * Admin notices
 *
 * @package um\admin\core
 */

namespace um\admin\core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'um\admin\core\Admin_Notices' ) ) {


	/**
	 * Class Admin_Notices
	 */
	class Admin_Notices {


		/**
		 * Notices list
		 *
		 * @var array
		 */
		public $list = array();


		/**
		 * Class constructor
		 */
		public function __construct() {
			add_action( 'admin_init', array( &$this, 'create_languages_folder' ) );
			add_action( 'admin_init', array( &$this, 'create_list' ), 10 );

			add_action( 'admin_notices', array( &$this, 'render_notices' ), 1 );

			add_action( 'wp_ajax_um_dismiss_notice', array( &$this, 'dismiss_notice' ) );
			add_action( 'admin_init', array( &$this, 'force_dismiss_notice' ) );
		}


		/**
		 * Create plugins languages directory if not exists
		 *
		 * @hook  admin_init
		 */
		public function create_languages_folder() {
			$path = wp_normalize_path( WP_LANG_DIR . '/plugins/' );
			if ( ! file_exists( $path ) ) {
				$old = umask( 0 );
				wp_mkdir_p( $path, 0777, true );
				umask( $old );
			}
		}


		/**
		 * Add notices
		 *
		 * @hook  admin_init
		 */
		public function create_list() {
			$this->old_extensions_notice();
			$this->install_core_page_notice();
			$this->exif_extension_notice();
			$this->show_update_messages();
			$this->check_wrong_install_folder();
			$this->need_upgrade();
			$this->check_wrong_licenses();

			$this->lock_registration();

			/**
			 * Removed for now to avoid the bad reviews.
			 * $this->reviews_notice();
			 * $this->future_changed();
			 */

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


		/**
		 * Get notices list
		 *
		 * @return array
		 */
		public function get_admin_notices() {
			return $this->list;
		}


		/**
		 * Set notices list
		 *
		 * @param array $admin_notices  Notices list.
		 */
		public function set_admin_notices( $admin_notices ) {
			$this->list = $admin_notices;
		}


		/**
		 * Helper function that sorts notices
		 *
		 * @param  array $a  Notice.
		 * @param  array $b  Notice.
		 *
		 * @return int
		 */
		public function notice_priority_sort( $a, $b ) {
			if ( $a['priority'] === $b['priority'] ) {
				return 0;
			}
			return ( $a['priority'] < $b['priority'] ) ? -1 : 1;
		}


		/**
		 * Add notice to the notices list
		 *
		 * @param  string $key       Key.
		 * @param  array  $data      Notice.
		 * @param  int    $priority  Priority.
		 */
		public function add_notice( $key, $data, $priority = 10 ) {
			$admin_notices = $this->get_admin_notices();

			if ( empty( $admin_notices[ $key ] ) ) {
				$admin_notices[ $key ] = array_merge( $data, array( 'priority' => $priority ) );
				$this->set_admin_notices( $admin_notices );
			}
		}


		/**
		 * AJAX handler - dismiss notice
		 *
		 * @hook  wp_ajax_um_dismiss_notice
		 */
		public function dismiss_notice() {
			check_ajax_referer( 'um-admin-nonce', 'nonce' );

			if ( empty( $_POST['key'] ) ) {
				wp_send_json_error( __( 'Wrong Data', 'ultimate-member' ) );
			}

			$hidden_notices = get_option( 'um_hidden_admin_notices', array() );
			if ( ! is_array( $hidden_notices ) ) {
				$hidden_notices = array();
			}

			$hidden_notices[] = sanitize_key( $_POST['key'] );

			update_option( 'um_hidden_admin_notices', $hidden_notices );

			wp_send_json_success();
		}


		/**
		 * Dismiss notice
		 *
		 * @hook  admin_init
		 */
		public function force_dismiss_notice() {
			if ( empty( $_REQUEST['um_dismiss_notice'] ) || empty( $_REQUEST['um_admin_nonce'] ) ) {
				return;
			}

			if ( wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['um_admin_nonce'] ) ), 'um-admin-nonce' ) ) {

				$hidden_notices = get_option( 'um_hidden_admin_notices', array() );
				if ( ! is_array( $hidden_notices ) ) {
					$hidden_notices = array();
				}

				$hidden_notices[] = sanitize_key( wp_unslash( $_REQUEST['um_dismiss_notice'] ) );

				update_option( 'um_hidden_admin_notices', $hidden_notices );
			} else {
				wp_die( esc_html__( 'Security Check', 'ultimate-member' ) );
			}
		}


		/**
		 * Remove notice from the notices list
		 *
		 * @param  string $key  Key.
		 */
		public function remove_notice( $key ) {
			$admin_notices = $this->get_admin_notices();
			if ( ! empty( $admin_notices[ $key ] ) ) {
				unset( $admin_notices[ $key ] );
				$this->set_admin_notices( $admin_notices );
			}
		}


		/**
		 * Render all admin notices
		 *
		 * @hook  admin_notices
		 */
		public function render_notices() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$admin_notices = $this->get_admin_notices();

			$hidden = get_option( 'um_hidden_admin_notices', array() );

			uasort( $admin_notices, array( &$this, 'notice_priority_sort' ) );

			foreach ( $admin_notices as $key => $admin_notice ) {
				if ( empty( $hidden ) || ! in_array( $key, $hidden, true ) ) {
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
		 * @param  string $key   Key.
		 * @param  bool   $echo  Render a notice if true, return if false. Default true.
		 *
		 * @return void|string
		 */
		public function display_notice( $key, $echo = true ) {
			$admin_notices = $this->get_admin_notices();

			if ( empty( $admin_notices[ $key ] ) ) {
				return;
			}

			$notice_data = $admin_notices[ $key ];
			$message     = empty( $notice_data['message'] ) ? '' : $notice_data['message'];
			$class       = empty( $notice_data['class'] ) ? 'updated' : $notice_data['class'];
			$class      .= empty( $notice_data['dismissible'] ) ? '' : ' is-dismissible';

			ob_start();
			?>

			<div class="um-admin-notice notice <?php echo esc_attr( $class ); ?>" data-key="<?php echo esc_attr( $key ); ?>">
				<?php echo wp_kses_post( $message ); ?>
			</div>

			<?php
			$notice = ob_get_clean();

			if ( $echo ) {
				echo wp_kses_post( $notice );
			} else {
				return $notice;
			}
		}


		/**
		 * Notice: The "Membership - Anyone can register" WordPress general setting is active
		 */
		public function lock_registration() {
			$users_can_register = get_option( 'users_can_register' );
			if ( ! $users_can_register ) {
				return;
			}

			$this->add_notice(
				'lock_registration',
				array(
					// translators: 1: options URL.
					'message'     => '<p>' . wp_kses_post( sprintf( __( 'The <strong>"Membership - Anyone can register"</strong> option on the general settings <a href="%s">page</a> is enabled. This means users can register via the standard WordPress wp-login.php page. If you do not want users to be able to register via this page and only register via the Ultimate Member registration form, you should deactivate this option. You can dismiss this notice if you wish to keep the wp-login.php registration page open.', 'ultimate-member' ), admin_url( 'options-general.php' ) . '#users_can_register' ) ) . '</p>',
					'class'       => 'info',
					'dismissible' => true,
				),
				10
			);
		}


		/**
		 * Notice: Old extension's versions
		 */
		public function old_extensions_notice() {

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

			$show = false;
			foreach ( $slugs as $slug ) {
				if ( ! in_array( $slug, $active_plugins, true ) ) {
					continue;
				}
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

			if ( $show ) {
				$this->add_notice(
					'old_extensions',
					array(
						// translators: 1: Plugin name, 2: Plugin version, 3: Plugin name, 4: documentation URL.
						'message' => '<p>' . sprintf( __( '<strong>%1$s %2$s</strong> requires 2.0 extensions. You have pre 2.0 extensions installed on your site. <br /> Please update %3$s extensions to latest versions. For more info see this <a href="%4$s" target="_blank">doc</a>.', 'ultimate-member' ), ultimatemember_plugin_name, ultimatemember_version, ultimatemember_plugin_name, 'https://docs.ultimatemember.com/article/201-how-to-update-your-site' ) . '</p>',
						'class'   => 'error',
					),
					0
				);
			}
		}


		/**
		 * Notice: Regarding page setup
		 */
		public function install_core_page_notice() {
			$pages = UM()->config()->permalinks;

			if ( ! is_array( $pages ) ) {
				return;
			}

			foreach ( $pages as $slug => $page_id ) {
				$page = get_post( $page_id );

				if ( empty( $page ) && in_array( $slug, array_keys( UM()->config()->core_pages ), true ) ) {

					ob_start();
					?>

					<p>
						<?php
						// translators: 1: Plugin name.
						echo wp_kses_post( sprintf( __( '%s needs to create several pages (User Profiles, Account, Registration, Login, Password Reset, Logout, Member Directory) to function correctly.', 'ultimate-member' ), ultimatemember_plugin_name ) );
						?>
					</p>

					<p>
						<a href="<?php echo esc_url( add_query_arg( 'um_adm_action', 'install_core_pages' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Create Pages', 'ultimate-member' ); ?></a>&nbsp;
						<a href="javascript:void(0);" class="button-secondary um_secondary_dimiss"><?php esc_html_e( 'No thanks', 'ultimate-member' ); ?></a>
					</p>

					<?php
					$message = ob_get_clean();

					$this->add_notice(
						'wrong_pages',
						array(
							'message'     => $message,
							'class'       => 'updated',
							'dismissible' => true,
						),
						20
					);

					break;
				}
			}

			if ( isset( $pages['user'] ) ) {
				$test = get_post( $pages['user'] );

				if ( isset( $test->post_parent ) && $test->post_parent > 0 ) {
					$this->add_notice(
						'wrong_user_page',
						array(
							'message' => '<p>' . __( 'Ultimate Member Setup Error: User page can not be a child page.', 'ultimate-member' ) . '</p>',
							'class'   => 'updated',
						),
						25
					);
				}
			}

			if ( isset( $pages['account'] ) ) {
				$test = get_post( $pages['account'] );

				if ( isset( $test->post_parent ) && $test->post_parent > 0 ) {
					$this->add_notice(
						'wrong_account_page',
						array(
							'message' => '<p>' . __( 'Ultimate Member Setup Error: Account page can not be a child page.', 'ultimate-member' ) . '</p>',
							'class'   => 'updated',
						),
						30
					);
				}
			}
		}


		/**
		 * Notice: EXIF library is not enabled
		 */
		public function exif_extension_notice() {
			$hide_exif_notice = get_option( 'um_hide_exif_notice' );

			if ( ! extension_loaded( 'exif' ) && ! $hide_exif_notice ) {
				$this->add_notice(
					'exif_disabled',
					array(
						// translators: 1: Hide this notice URL.
						'message' => '<p>' . sprintf( __( 'Exif is not enabled on your server. Mobile photo uploads will not be rotated correctly until you enable the exif extension. <a href="%s">Hide this notice</a>', 'ultimate-member' ), add_query_arg( 'um_adm_action', 'um_hide_exif_notice' ) ) . '</p>',
						'class'   => 'updated',
					),
					10
				);
			}
		}


		/**
		 * Notice: Updating users
		 */
		public function show_update_messages() {

			if ( ! isset( $_REQUEST['update'] ) ) {
				return;
			}

			$messages = array();
			$update   = sanitize_key( wp_unslash( $_REQUEST['update'] ) );

			switch ( $update ) {

				case 'confirm_delete':
					if ( empty( $_REQUEST['user'] ) ) {
						break;
					}
					$request_users = array_map( 'absint', (array) wp_unslash( $_REQUEST['user'] ) );

					$confirm_uri = admin_url(
						'users.php?' . http_build_query(
							array(
								'um_adm_action' => 'delete_users',
								'user'          => $request_users,
								'confirm'       => 1,
							)
						)
					);

					$users = '';
					foreach ( $request_users as $user_id ) {
						$user   = get_userdata( $user_id );
						$users .= '#' . $user_id . ': ' . $user->user_login . '<br />';
					}

					$ignore = admin_url( 'users.php' );

					// translators: 1: Users list.
					$messages[0]['err_content'] = sprintf( __( 'Are you sure you want to delete the selected user(s)? The following users will be deleted: <p>%s</p> <strong>This cannot be undone!</strong>', 'ultimate-member' ), $users )
						. '<p>'
						. '<a href="' . esc_url( $confirm_uri ) . '" class="button-primary">' . esc_html__( 'Remove', 'ultimate-member' ) . '</a>'
						. '&nbsp;&nbsp;'
						. '<a href="' . esc_url( $ignore ) . '" class="button">' . esc_html__( 'Undo', 'ultimate-member' ) . '</a>'
						. '</p>';

					break;

				case 'language_updated':
					$messages[0]['content'] = __( 'Your translation files have been updated successfully.', 'ultimate-member' );
					break;

				case 'purged_temp':
					$messages[0]['content'] = __( 'Your temp uploads directory is now clean.', 'ultimate-member' );
					break;

				case 'cleared_cache':
					$messages[0]['content'] = __( 'Your user cache is now removed.', 'ultimate-member' );
					break;

				case 'got_updates':
					$messages[0]['content'] = __( 'You have the latest updates.', 'ultimate-member' );
					break;

				case 'often_updates':
					$messages[0]['err_content'] = __( 'Try again later. You can run this action once daily.', 'ultimate-member' );
					break;

				case 'form_duplicated':
					$messages[0]['content'] = __( 'The form has been duplicated successfully.', 'ultimate-member' );
					break;

				case 'settings_updated':
					$messages[0]['content'] = __( 'Settings have been saved successfully.', 'ultimate-member' );
					break;

				case 'user_updated':
					$messages[0]['content'] = __( 'User has been updated.', 'ultimate-member' );
					break;

				case 'users_updated':
					$messages[0]['content'] = __( 'Users have been updated.', 'ultimate-member' );
					break;

				case 'users_role_updated':
					$messages[0]['content'] = __( 'Changed roles.', 'ultimate-member' );
					break;

				case 'err_users_updated':
					$messages[0]['err_content'] = __( 'Super administrators cannot be modified.', 'ultimate-member' );
					$messages[1]['content']     = __( 'Other users have been updated.', 'ultimate-member' );

			}

			if ( ! empty( $messages ) ) {
				foreach ( $messages as $message ) {
					if ( isset( $message['err_content'] ) ) {
						$this->add_notice(
							'actions',
							array(
								'message' => '<p>' . $message['err_content'] . '</p>',
								'class'   => 'error',
							),
							50
						);
					} else {
						$this->add_notice(
							'actions',
							array(
								'message' => '<p>' . $message['content'] . '</p>',
								'class'   => 'updated',
							),
							50
						);
					}
				}
			}

		}


		/**
		 * Notice: Check if plugin is installed with correct folder
		 */
		public function check_wrong_install_folder() {

			$slug_array = explode( '/', um_plugin );
			if ( 'ultimate-member' === $slug_array[0] ) {
				return;
			}

			$this->add_notice(
				'invalid_dir',
				array(
					// translators: 1: Plugin name.
					'message' => '<p>' . sprintf( __( 'You have installed <strong>%s</strong> with wrong folder name. Correct folder name is <strong>"ultimate-member"</strong>.', 'ultimate-member' ), ultimatemember_plugin_name ) . '</p>',
					'class'   => 'error',
				),
				1
			);
		}


		/**
		 * Notice: Inactive license
		 */
		public function check_wrong_licenses() {
			$invalid_license           = 0;
			$arr_inactive_license_keys = array();

			if ( empty( UM()->admin_settings()->settings_structure['licenses']['fields'] ) ) {
				return;
			}

			foreach ( UM()->admin_settings()->settings_structure['licenses']['fields'] as $field_data ) {
				$license = get_option( $field_data['id'] . '_edd_answer' );

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
						// translators: 1: Number of inactive licenses, 2: Plugin name, 3: Store URL.
						'message' => '<p>' . sprintf( __( 'There are %1$d inactive %2$s license keys for this site. This site is not authorized to get plugin updates. You can active this site on <a href="%3$s">www.ultimatemember.com</a>.', 'ultimate-member' ), count( $arr_inactive_license_keys ), ultimatemember_plugin_name, UM()->store_url ) . '</p>',
						'class'   => 'error',
					),
					3
				);
			}

			if ( $invalid_license ) {
				$licenses_url = add_query_arg(
					array(
						'page' => 'um_options',
						'tab'  => 'licenses',
					),
					admin_url( 'admin.php' )
				);

				$this->add_notice(
					'license_key',
					array(
						// translators: 1: Number of invalid or expired licenses, 2: Plugin name, 3: Licenses URL.
						'message' => '<p>' . sprintf( __( 'You have %1$d invalid or expired license keys for %2$s. Please go to the <a href="%3$s">Licenses page</a> to correct this issue.', 'ultimate-member' ), $invalid_license, ultimatemember_plugin_name, $licenses_url ) . '</p>',
						'class'   => 'error',
					),
					3
				);
			}
		}


		/**
		 * Notice: Upgrade
		 */
		public function need_upgrade() {
			if ( ! empty( UM()->admin_upgrade()->necessary_packages ) ) {

				$url = add_query_arg(
					array( 'page' => 'um_upgrade' ),
					admin_url( 'admin.php' )
				);

				ob_start();
				?>

				<p>
					<?php
					// translators: 1: Plugin name, 2: Plugin version, 3: Plugin name, 4: Plugin version, 5: Upgrade URL.
					echo wp_kses_post( sprintf( __( '<strong>%1$s version %2$s</strong> needs to be updated to work correctly.<br />It is necessary to update the structure of the database and options that are associated with <strong>%3$s %4$s</strong>.<br />Please visit <a href="%5$s">"Upgrade"</a> page and run the upgrade process.', 'ultimate-member' ), ultimatemember_plugin_name, ultimatemember_version, ultimatemember_plugin_name, ultimatemember_version, $url ) );
					?>
				</p>

				<p>
					<a href="<?php echo esc_url( $url ); ?>" class="button button-primary"><?php esc_html_e( 'Visit Upgrade Page', 'ultimate-member' ); ?></a>&nbsp;
				</p>

				<?php
				$message = ob_get_clean();

				$this->add_notice(
					'upgrade',
					array(
						'message' => $message,
						'class'   => 'error',
					),
					4
				);

			} elseif ( isset( $_GET['msg'] ) && 'updated' === sanitize_key( $_GET['msg'] ) ) {

				if ( isset( $_GET['page'] ) && 'um_options' === sanitize_key( $_GET['page'] ) ) {
					$this->add_notice(
						'settings_upgrade',
						array(
							'message' => '<p>' . __( 'Settings successfully upgraded', 'ultimate-member' ) . '</p>',
							'class'   => 'updated',
						),
						4
					);
				} else {
					$this->add_notice(
						'upgrade',
						array(
							// translators: 1: Plugin name, 2: Plugin version.
							'message' => '<p>' . sprintf( __( '<strong>%1$s %2$s</strong> Successfully Upgraded', 'ultimate-member' ), ultimatemember_plugin_name, ultimatemember_version ) . '</p>',
							'class'   => 'updated',
						),
						4
					);
				}
			}
		}


		/**
		 * Notice: Review the plugin
		 */
		public function reviews_notice() {

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
					// translators: 1: Plugin name.
					echo wp_kses_post( sprintf( __( 'Hey there! It\'s been one month since you installed %s. How have you found the plugin so far?', 'ultimate-member' ), ultimatemember_plugin_name ) );
					?>
				</p>
				<p>
					<a href="javascript:void(0);" id="um_add_review_love"><?php esc_html_e( 'I love it!', 'ultimate-member' ); ?></a>&nbsp;|&nbsp;
					<a href="javascript:void(0);" id="um_add_review_good"><?php esc_html_e( 'It\'s good but could be better', 'ultimate-member' ); ?></a>&nbsp;|&nbsp;
					<a href="javascript:void(0);" id="um_add_review_bad"><?php esc_html_e( 'I don\'t like the plugin', 'ultimate-member' ); ?></a>
				</p>
			</div>
			<div class="um_hidden_notice" data-key="love">
				<p>
					<?php
					// translators: 1: Plugin name, 2: Plugin name.
					echo wp_kses_post( sprintf( __( 'Great! We\'re happy to hear that you love the plugin. It would be amazing if you could let others know why you like %1$s by leaving a review of the plugin. This will help %2$s to grow and become more popular and would be massively appreciated by us!' ), ultimatemember_plugin_name, ultimatemember_plugin_name ) );
					?>
				</p>

				<p>
					<a href="https://wordpress.org/support/plugin/ultimate-member/reviews/?rate=5#new-post" target="_blank" class="button button-primary um_review_link"><?php esc_html_e( 'Leave Review', 'ultimate-member' ); ?></a>
				</p>
			</div>
			<div class="um_hidden_notice" data-key="good">
				<p>
					<?php esc_html_e( 'We\'re glad to hear that you like the plugin but we would love to get your feedback so we can make the plugin better.' ); ?>
				</p>

				<p>
					<a href="https://ultimatemember.com/feedback/" target="_blank" class="button button-primary um_review_link"><?php esc_html_e( 'Provide Feedback', 'ultimate-member' ); ?></a>
				</p>
			</div>
			<div class="um_hidden_notice" data-key="bad">
				<p>
					<?php
					// translators: 1: Support forum URL.
					echo wp_kses_post( sprintf( __( 'We\'re sorry to hear that. If you\'re having the issue with the plugin you can create a topic on our <a href="%s" target="_blank">support forum</a> and we will try and help you out with the issue. Alternatively if you have an idea on how we can make the plugin better or want to tell us what you don\'t like about the plugin you can tell us know by giving us feedback.' ), 'https://wordpress.org/support/plugin/ultimate-member' ) );
					?>
				</p>

				<p>
					<a href="https://ultimatemember.com/feedback/" target="_blank" class="button button-primary um_review_link"><?php esc_html_e( 'Provide Feedback', 'ultimate-member' ); ?></a>
				</p>
			</div>

			<?php
			$message = ob_get_clean();

			$this->add_notice(
				'reviews_notice',
				array(
					'message'     => $message,
					'class'       => 'updated',
					'dismissible' => true,
				),
				1
			);
		}


		/**
		 * Notice: Check Future Changes
		 */
		public function future_changed() {
			ob_start();
			?>

			<p>
				<?php
				// translators: 1: Plugin name, 2: URL.
				echo wp_kses_post( sprintf( __( '<strong>%1$s</strong> future plans! Detailed future list is <a href="%2$s" target="_blank">here</a>', 'ultimate-member' ), ultimatemember_plugin_name, '#' ) );
				?>
			</p>

			<?php
			$message = ob_get_clean();

			$this->add_notice(
				'future_changes',
				array(
					'message' => $message,
					'class'   => 'updated',
				),
				2
			);
		}

	}
}
