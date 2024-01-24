<?php
namespace um\admin\core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\admin\core\Admin_Status' ) ) {

	/**
	 * Class Admin_Notices
	 * @package um\admin\core
	 */
	class Admin_Status {


		public $status_structure = array();


		/**
		 * Admin_Status constructor.
		 */
		public function __construct() {
			add_action( 'um_extend_admin_menu', array( &$this, 'um_extend_admin_menu' ), 5 );

			add_filter( 'um_status_section_notices_center__content', array( $this, 'settings_notices_center_tab' ), 10 );
		}


		public function um_extend_admin_menu() {
			add_submenu_page( UM()->admin_menu()->slug, __( 'Status', 'ultimate-member' ), __( 'Status', 'ultimate-member' ), 'manage_options', 'um_status_page', array( &$this, 'um_status_page' ) );
		}


		/**
		 * Status page menu callback
		 */
		public function um_status_page() {
			$this->status_structure = apply_filters(
				'um_status_structure',
				array(
					'notices_center' => array(
						'title'  => __( 'Notices Center', 'ultimate-member' ),
						'fields' => array(
							array(
								'type' => 'notices_center',
							),
						),
					),
				)
			);

			$current_tab = empty( $_GET['tab'] ) ? 'notices_center' : sanitize_key( $_GET['tab'] ); // phpcs:ignore WordPress.Security.NonceVerification

			echo '<div id="um-settings-wrap" class="wrap">';
			echo '<h2>' . esc_html__( 'Ultimate Member - Status', 'ultimate-member' ) . '</h2>';
			echo wp_kses( $this->generate_tabs_menu(), UM()->get_allowed_html( 'admin' ) );
			echo '<div class="clear"></div>';
			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_settings_page_{$current_tab}_{$current_subtab}_before_section
			 * @description Show some content before section content at settings page
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_settings_page_{$current_tab}_{$current_subtab}_before_section', 'function_name', 10 );
			 * @example
			 * <?php
			 * add_action( 'um_settings_page_{$current_tab}_{$current_subtab}_before_section', 'my_settings_page_before_section', 10 );
			 * function my_settings_page_before_section() {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( "um_status_page_{$current_tab}_before_section" );

			apply_filters( 'um_status_section_' . $current_tab . '__content', $current_tab );

			echo '</div>';
		}


		/**
		 * Generate pages tabs.
		 *
		 * @param string $page
		 * @return string
		 */
		private function generate_tabs_menu( $page = 'settings' ) {
			$tabs = '<nav class="nav-tab-wrapper um-nav-tab-wrapper">';

			$menu_tabs = array();
			foreach ( $this->status_structure as $slug => $tab ) {
				if ( ! empty( $tab['fields'] ) ) {
					foreach ( $tab['fields'] as $field_key => $field_options ) {
						if ( isset( $field_options['is_option'] ) && false === $field_options['is_option'] ) {
							unset( $tab['fields'][ $field_key ] );
						}
					}
				}

				if ( ! empty( $tab['fields'] ) || ! empty( $tab['sections'] ) || ! empty( $tab['form_sections'] ) ) {
					$menu_tabs[ $slug ] = $tab['title'];
				}
			}

			$current_tab = empty( $_GET['tab'] ) ? 'notices_center' : sanitize_key( $_GET['tab'] ); // phpcs:ignore WordPress.Security.NonceVerification
			foreach ( $menu_tabs as $name => $label ) {
				$active = $current_tab === $name ? 'nav-tab-active' : '';

				$args = array( 'page' => 'um_status_page' );
				if ( ! empty( $name ) ) {
					$args['tab'] = $name;
				}
				$tab_url = add_query_arg( $args, admin_url( 'admin.php' ) );
				$tabs   .= '<a href="' . esc_url( $tab_url ) . '" class="nav-tab ' . esc_attr( $active ) . '">' . esc_html( $label ) . '</a>';
			}

			return $tabs . '</nav>';
		}


		/**
		 * @param $html
		 * @param $section_fields
		 */
		public function settings_notices_center_tab() {
			$notices = array();
			$notices = $this->old_extensions_notice( $notices );
			$notices = $this->install_core_page_notice( $notices );
			$notices = $this->exif_extension_notice( $notices );

			usort(
				$notices,
				function( $a, $b ) {
					return $a['priority'] - $b['priority'];
				}
			);

			include_once UM()->admin()->templates_path . 'status/notices.php';
		}


		/**
		 * Show notice for customers with old extension's versions
		 */
		public function old_extensions_notice( $notices ) {
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
				if ( in_array( $slug, $active_plugins, true ) ) {
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

			// @todo need this check after
//			if ( ! $show ) {
//				return;
//			}

			$notices[] = array(
				'id'       => 'old_extensions',
				'priority' => 10,
				'class'    => 'error',
				// translators: %1$s is a plugin name; %2$s is a plugin version; %3$s is a plugin name; %4$s is a doc link.
				'message'  => '<p>' . sprintf( __( '<strong>%1$s %2$s</strong> requires 2.0 extensions. You have pre 2.0 extensions installed on your site. <br /> Please update %3$s extensions to latest versions. For more info see this <a href="%4$s" target="_blank">doc</a>.', 'ultimate-member' ), UM_PLUGIN_NAME, UM_VERSION, UM_PLUGIN_NAME, 'https://docs.ultimatemember.com/article/201-how-to-update-your-site' ) . '</p>',
			);

			return $notices;
		}


		/**
		 * Regarding page setup
		 */
		public function install_core_page_notice( $notices ) {
			$pages = UM()->config()->permalinks;

			if ( $pages && is_array( $pages ) ) {

				foreach ( $pages as $slug => $page_id ) {
					$page = get_post( $page_id );

					if ( ! isset( $page->ID ) && array_key_exists( $slug, UM()->config()->core_pages ) ) {
						$url = add_query_arg(
							array(
								'um_adm_action' => 'install_core_pages',
								'_wpnonce'      => wp_create_nonce( 'install_core_pages' ),
							)
						);

						ob_start();
						?>

						<p>
							<?php
							// translators: %s: Plugin name.
							echo wp_kses( sprintf( __( '%s needs to create several pages (User Profiles, Account, Registration, Login, Password Reset, Logout, Member Directory) to function correctly.', 'ultimate-member' ), UM_PLUGIN_NAME ), UM()->get_allowed_html( 'admin_notice' ) );
							?>
						</p>

						<p>
							<a href="<?php echo esc_url( $url ); ?>" class="button button-primary"><?php esc_html_e( 'Create Pages', 'ultimate-member' ); ?></a>
							&nbsp;
							<a href="javascript:void(0);" class="button-secondary um_secondary_dismiss"><?php esc_html_e( 'No thanks', 'ultimate-member' ); ?></a>
						</p>

						<?php
						$message = ob_get_clean();

						$notices[] = array(
							'id'       => 'wrong_pages',
							'priority' => 20,
							'class'    => 'updated',
							'message'  => $message,
						);

						break;
					}
				}

				if ( isset( $pages['user'] ) ) {
					$test = get_post( $pages['user'] );
					if ( isset( $test->post_parent ) && $test->post_parent > 0 ) {
						$notices[] = array(
							'id'       => 'wrong_user_page',
							'priority' => 30,
							'class'    => 'updated',
							'message'  => '<p>' . esc_html__( 'Ultimate Member Setup Error: User page can not be a child page.', 'ultimate-member' ) . '</p>',
						);
					}
				}

				if ( isset( $pages['account'] ) ) {
					$test = get_post( $pages['account'] );
					if ( isset( $test->post_parent ) && $test->post_parent > 0 ) {
						$notices[] = array(
							'id'       => 'wrong_account_page',
							'priority' => 40,
							'class'    => 'updated',
							'message'  => '<p>' . esc_html__( 'Ultimate Member Setup Error: Account page can not be a child page.', 'ultimate-member' ) . '</p>',
						);
					}
				}

				// @todo DELETE THIS
				$notices[] = array(
					'id'       => 'wrong_account_page',
					'priority' => 1,
					'class'    => 'updated',
					'message'  => '<p>' . esc_html__( 'Ultimate Member Setup Error: Account page can not be a child page.', 'ultimate-member' ) . '</p>',
				);
			}

			return $notices;
		}


		/**
		 * EXIF library notice
		 */
		public function exif_extension_notice( $notices ) {
			if ( ! extension_loaded( 'exif' ) ) {
				$notices[] = array(
					'id'       => 'exif_disabled',
					'priority' => 50,
					'class'    => 'updated',
					'message'  => '<p>' . esc_html__( 'Exif is not enabled on your server. Mobile photo uploads will not be rotated correctly until you enable the exif extension.', 'ultimate-member' ) . '</p>',
				);
			}

			return $notices;
		}
	}
}
