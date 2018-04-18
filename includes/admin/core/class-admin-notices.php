<?php
namespace um\admin\core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

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
		var $list = array();


		/**
		 * Admin_Notices constructor.
		 */
		function __construct() {
			add_action( 'admin_init', array( &$this, 'create_languages_folder' ) );

			add_action( 'admin_init', array( &$this, 'create_list' ), 10 );
			add_action( 'admin_notices', array( &$this, 'render_notices' ), 1 );
		}


		function create_list() {
			$this->old_extensions_notice();
			$this->main_notices();
			$this->localize_note();
			$this->show_update_messages();
			$this->check_wrong_install_folder();
			$this->admin_notice_tracking();
			$this->need_upgrade();
			$this->check_wrong_licenses();

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
		 * @return array
		 */
		function get_admin_notices() {
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

			$hidden = get_user_meta( get_current_user_id(), 'um_hidden_admin_notices' );
			$hidden = empty( $hidden ) ? array() : $hidden;

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

			ob_start(); ?>

			<div class="<?php echo esc_attr( $class ) ?> um-admin-notice">
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
		function old_extensions_notice() {
			$show = false;

			$slugs = array_map( function( $item ) {
				return 'um-' . $item . '/um-' . $item . '.php';
			}, array_keys( UM()->dependencies()->ext_required_version ) );

			$active_plugins = UM()->dependencies()->get_active_plugins();
			foreach ( $slugs as $slug ) {
				if ( in_array( $slug, $active_plugins ) ) {
					$plugin_data = get_plugin_data( um_path . '..' . DIRECTORY_SEPARATOR . $slug );
					if ( version_compare( '2.0', $plugin_data['Version'], '>' ) ) {
						$show = true;
						break;
					}
				}
			}

			if ( ! $show ) {
				return;
			}

			$this->add_notice( 'old_extensions', array(
				'class' => 'error',
				'message' => '<p>' . sprintf( __( '<strong>%s %s</strong> requires 2.0 extensions. You have pre 2.0 extensions installed on your site. <br /> Please update %s extensions to latest versions. For more info see this <a href="%s" target="_blank">doc</a>.', 'ultimate-member' ), ultimatemember_plugin_name, ultimatemember_version, ultimatemember_plugin_name, 'http://docs.ultimatemember.com/article/266-updating-to-2-0-versions-of-extensions' ) . '</p>',
			), 0 );
		}


		/**
		* Show main notices
		*/
		function main_notices() {

			$hide_exif_notice = get_option( 'um_hide_exif_notice' );

			if ( ! extension_loaded( 'exif' ) && ! $hide_exif_notice ) {
				$this->add_notice( 'exif_disabled', array(
					'class'     => 'updated',
					'message'   => '<p>' . sprintf(__( 'Exif is not enabled on your server. Mobile photo uploads will not be rotated correctly until you enable the exif extension. <a href="%s">Hide this notice</a>', 'ultimate-member' ), add_query_arg('um_adm_action', 'um_hide_exif_notice') ) . '</p>',
				), 10 );
			}

			// Regarding page setup
			$pages = UM()->config()->permalinks;
			if ( $pages && is_array( $pages ) ) {

				$err = false;

				foreach ( $pages as $slug => $page_id ) {

					$page = get_post( $page_id );

					if ( ! isset( $page->ID ) && in_array( $slug, array( 'user', 'account', 'members', 'register', 'login', 'logout', 'password-reset' ) ) ) {
						$err = true;
					}

				}

				if ( $err ) {
					$this->add_notice( 'wrong_pages', array(
						'class'     => 'updated',
						'message'   => '<p>' . __( 'One or more of your Ultimate Member pages are not correctly setup. Please visit <strong>Ultimate Member > Settings</strong> to re-assign your missing pages.', 'ultimate-member' ) . '</p>',
					), 20 );
				}

				if ( isset( $pages['user'] ) ) {
					$test = get_post( $pages['user'] );
					if ( isset( $test->post_parent ) && $test->post_parent > 0 ) {
						$this->add_notice( 'wrong_user_page', array(
							'class'     => 'updated',
							'message'   => '<p>' . __( 'Ultimate Member Setup Error: User page can not be a child page.', 'ultimate-member' ) . '</p>',
						), 25 );
					}
				}

				if ( isset( $pages['account'] ) ) {
					$test = get_post( $pages['account'] );
					if ( isset( $test->post_parent ) && $test->post_parent > 0 ) {
						$this->add_notice( 'wrong_account_page', array(
							'class'     => 'updated',
							'message'   => '<p>' . __( 'Ultimate Member Setup Error: Account page can not be a child page.', 'ultimate-member' ) . '</p>',
						), 30 );
					}
				}

			}
		}


		/**
		 * Localization notice
		 */
		function localize_note() {
			$locale = get_option( 'WPLANG' );
			if ( ! $locale || strstr( $locale, 'en_' ) ) {
				return;
			}

			if ( file_exists( WP_LANG_DIR . '/plugins/ultimatemember-' . $locale . '.mo' ) ) {
				return;
			}

			$hide_locale_notice = get_option( 'um_hide_locale_notice' );
			if ( $hide_locale_notice ) {
				return;
			}

			if ( isset( UM()->available_languages[ $locale ] ) ) {

				$download_uri = add_query_arg( 'um_adm_action', 'um_language_downloader' );

				$this->add_notice( 'locale', array(
					'class'     => 'updated',
					'message'   => '<p>' . sprintf( __( 'Your site language is <strong>%1$s</strong>. Good news! Ultimate Member is already available in <strong>%2$s language</strong>. <a href="%3$s">Download the translation</a> files and start using the plugin in your language now. <a href="%4$s">Hide this notice</a>','ultimate-member'), $locale, UM()->available_languages[ $locale ], $download_uri, add_query_arg( 'um_adm_action', 'um_hide_locale_notice' ) ) . '</p>',
				), 40 );

			} else {

				$this->add_notice( 'locale', array(
					'class'     => 'updated',
					'message'   => '<p>' . sprintf( __( 'Ultimate Member has not yet been translated to your language: <strong>%1$s</strong>. If you have translated the plugin you need put these files <code>ultimatemember-%1$s.po and ultimatemember-%1$s.mo</code> in <strong>/wp-content/languages/plugins/</strong> for the plugin to be translated in your language. <a href="%2$s">Hide this notice</a>', 'ultimate-member' ), $locale, add_query_arg( 'um_adm_action', 'um_hide_locale_notice' ) ) . '</p>',
				), 40 );

			}

		}


		/**
		 * Updating users
		 */
		function show_update_messages() {

			if ( ! isset( $_REQUEST['update'] ) ) {
				return;
			}

			$update = $_REQUEST['update'];
			switch( $update ) {

				case 'confirm_delete':
					$confirm_uri = admin_url( 'users.php?' . http_build_query( array(
						'um_adm_action' => 'delete_users',
						'user'          => array_map( 'intval', (array) $_REQUEST['user'] ),
						'confirm'       => 1
					) ) );
					$users = '';

					if ( isset( $_REQUEST['user'] ) ){
						foreach ( $_REQUEST['user'] as $user_id ) {
							$user = get_userdata( $user_id );
							$users .= '#' . $user_id . ': ' . $user->user_login . '<br />';
						}
					}

					$ignore = admin_url('users.php');

					$messages[0]['err_content'] = sprintf( __( 'Are you sure you want to delete the selected user(s)? The following users will be deleted: <p>%s</p> <strong>This cannot be undone!</strong>','ultimate-member'), $users);
					$messages[0]['err_content'] .= '<p><a href="'. esc_html( $confirm_uri ) .'" class="button-primary">' . __( 'Remove', 'ultimate-member' ) . '</a>&nbsp;&nbsp;<a href="'.$ignore.'" class="button">' . __('Undo','ultimate-member') . '</a></p>';

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

				case 'form_duplicated':
					$messages[0]['content'] = __( 'The form has been duplicated successfully.', 'ultimate-member' );
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
					$messages[1]['content'] = __( 'Other users have been updated.', 'ultimate-member' );

			}

			if ( ! empty( $messages ) ) {
				foreach ( $messages as $message ) {
					if ( isset( $message['err_content'] ) ) {
						$this->add_notice( 'actions', array(
							'class'     => 'error',
							'message'   => '<p>' . $message['err_content'] . '</p>',
						), 50 );
					} else {
						$this->add_notice( 'actions', array(
							'class'     => 'updated',
							'message'   => '<p>' . $message['content'] . '</p>',
						), 50 );
					}
				}
			}

		}


		/**
		 * Check if plugin is installed with correct folder
		 */
		function check_wrong_install_folder() {
			$invalid_folder = false;

			$slug_array = explode( '/', um_plugin );
			if ( $slug_array[0] != 'ultimate-member' ) {
				$invalid_folder = true;
			}

			if ( $invalid_folder ) {
				$this->add_notice( 'invalid_dir', array(
					'class'     => 'error',
					'message'   => '<p>' . sprintf( __( 'You have installed <strong>%s</strong> with wrong folder name. Correct folder name is <strong>"ultimate-member"</strong>.', 'ultimate-member' ), ultimatemember_plugin_name ) . '</p>',
				), 1 );
			}
		}


		/**
		 * Show admin notices
		 */
		public function admin_notice_tracking() {

			if ( ! current_user_can( 'manage_options' ) )
				return;

			$hide_notice = get_option( 'um_tracking_notice' );

			if ( $hide_notice )
				return;

			$optin_url  =  esc_url( add_query_arg( 'um_adm_action', 'opt_into_tracking' ) );
			$optout_url =  esc_url( add_query_arg( 'um_adm_action', 'opt_out_of_tracking' ) );

			ob_start(); ?>

			<p>
				<?php printf( __( 'Thanks for installing <strong>%s</strong>! The core plugin is free but we also sell extensions which allow us to continue developing and supporting the plugin full time. If you subscribe to our mailing list (no spam) we will email you a 20%% discount code which you can use to purchase the <a href="%s" target="_blank">extensions bundle</a>.', 'ultimate-member' ), ultimatemember_plugin_name, 'https://ultimatemember.com/core-extensions-bundle/' ); ?>
			</p>

			<p>
				<a href="<?php echo esc_url( $optin_url ) ?>" class="button button-primary"><?php _e( 'Subscribe to mailing list', 'ultimate-member' ) ?></a>
				&nbsp;
				<a href="<?php echo esc_url( $optout_url ) ?>" class="button-secondary"><?php _e( 'No thanks', 'ultimate-member' ) ?></a>
			</p>

			<?php $message = ob_get_clean();

			$this->add_notice( 'invalid_dir', array(
				'class'     => 'updated',
				'message'   => $message,
			), 2 );
		}


		function check_wrong_licenses() {
			$invalid_license = 0;
			$arr_inactive_license_keys = array();

			if ( empty( UM()->admin_settings()->settings_structure['licenses']['fields'] ) ) {
				return;
			}

			foreach ( UM()->admin_settings()->settings_structure['licenses']['fields'] as $field_data ) {
				$license = get_option( "{$field_data['id']}_edd_answer" );

				if ( ( is_object( $license ) && 'valid' == $license->license ) || 'valid' == $license )
					continue;

				if ( ( is_object( $license ) && 'inactive' == $license->license ) || 'inactive' == $license ) {
					$arr_inactive_license_keys[ ] = $license->item_name;
				}

				$invalid_license++;
			}

			if ( ! empty(  $arr_inactive_license_keys ) ) {
				$this->add_notice( 'license_key', array(
					'class'     => 'error',
					'message'   => '<p>' . sprintf( __( 'There are %d inactive %s license keys for this site. This site is not authorized to get plugin updates. You can active this site on <a href="%s">www.UltimateMember.com</a>.', 'ultimate-member' ), count( $arr_inactive_license_keys ) , ultimatemember_plugin_name, 'https://ultimatemember.com' ) . '</p>',
				), 3 );
			}

			if ( $invalid_license ) {
				$this->add_notice( 'license_key', array(
					'class'     => 'error',
					'message'   => '<p>' . sprintf( __( 'You have %d invalid or expired license keys for %s. Please go to the <a href="%s">Licenses page</a> to correct this issue.', 'ultimate-member' ), $invalid_license, ultimatemember_plugin_name, add_query_arg( array('page'=>'um_options', 'tab' => 'licenses'), admin_url( 'admin.php' ) ) ) . '</p>',
				), 3 );
			}
		}


		function need_upgrade() {
			if ( ! empty( UM()->admin_upgrade()->necessary_packages ) ) {

				$url = add_query_arg( array( 'page' => 'um_upgrade' ), admin_url( 'admin.php' ) );

				ob_start(); ?>

				<p>
					<?php printf( __( '<strong>%s version %s</strong> needs to be updated to work correctly.<br />It is necessary to update the structure of the database and options that are associated with <strong>%s %s</strong>.<br />Please visit <a href="%s">"Upgrade"</a> page and run the upgrade process.', 'ultimate-member' ), ultimatemember_plugin_name, ultimatemember_version, ultimatemember_plugin_name, ultimatemember_version, $url ); ?>
				</p>

				<p>
					<a href="<?php echo esc_url( $url ) ?>" class="button button-primary"><?php _e( 'Upgrade Now', 'ultimate-member' ) ?></a>
					&nbsp;
				</p>

				<?php $message = ob_get_clean();

				$this->add_notice( 'upgrade', array(
					'class'     => 'error',
					'message'   => $message,
				), 4 );
			} else {
				if ( isset( $_GET['msg'] ) && 'updated' == $_GET['msg'] ) {
					$this->add_notice( 'upgrade', array(
						'class'     => 'updated',
						'message'   => '<p>' . sprintf( __( '<strong>%s %s</strong> Successfully Upgraded', 'ultimate-member' ), ultimatemember_plugin_name, ultimatemember_version ) . '</p>',
					), 4 );
				}
			}
		}

	}
}