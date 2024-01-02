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

		/**
		 * Admin_Status constructor.
		 */
		public function __construct() {
			add_action( 'um_extend_admin_menu', array( &$this, 'um_extend_admin_menu' ), 5 );

			add_filter( 'um_status_section_notices_center__content', array( $this, 'settings_notices_center_tab' ), 10, 2 );
		}


		public function um_extend_admin_menu() {
			add_submenu_page( UM()->admin_menu()->slug, __( 'Status', 'ultimate-member' ), __( 'Status', 'ultimate-member' ), 'manage_options', 'um_status_page', array( &$this, 'um_status_page' ) );
		}


		/**
		 * Status page menu callback
		 */
		public function um_status_page() {
			$status_structure = apply_filters(
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

			echo '<div id="um-settings-wrap" class="wrap"><h2>' . esc_html__( 'Ultimate Member - Status', 'ultimate-member' ) . '</h2>';
			echo '<h2 class="nav-tab-wrapper um-nav-tab-wrapper">';
			$menu_tabs = array();
			foreach ( $status_structure as $slug => $tab ) {
				if ( ! empty( $tab['fields'] ) ) {
					foreach ( $tab['fields'] as $field_key => $field_options ) {
						if ( isset( $field_options['is_option'] ) && false === $field_options['is_option'] ) {
							unset( $tab['fields'][ $field_key ] );
						}
					}
				}

				if ( ! empty( $tab['fields'] ) || ! empty( $tab['sections'] ) ) {
					$menu_tabs[ $slug ] = $tab['title'];
				}
			}

			// phpcs:disable WordPress.Security.NonceVerification
			$current_tab = empty( $_GET['tab'] ) ? 'notices_center' : sanitize_key( $_GET['tab'] );

			foreach ( $menu_tabs as $name => $label ) {
				$active = ( $current_tab === $name ) ? 'nav-tab-active' : '';
				echo '<a href="' . esc_url( admin_url( 'admin.php?page=um_status_page' . ( empty( $name ) ? '' : '&tab=' . esc_attr( $name ) ) ) ) . '" class="nav-tab ' . esc_attr( $active ) . '">' . esc_html( $label ) . '</a>';
			}

			$menu_subtabs = array();
			foreach ( $status_structure as $slug => $subtab ) {
				$menu_subtabs[ $slug ] = $subtab['title'];
			}
			echo '</h2>';

			echo '<div><ul class="subsubsub">';

			$current_tab    = empty( $_GET['tab'] ) ? 'notices_center' : sanitize_key( $_GET['tab'] );
			$current_subtab = empty( $_GET['section'] ) ? '' : sanitize_key( $_GET['section'] );
			// phpcs:enable WordPress.Security.NonceVerification

			foreach ( $menu_subtabs as $name => $label ) {
				$active = ( $current_subtab === $name ) ? 'current' : '';
				echo '<a href="' . esc_url( admin_url( 'admin.php?page=um_status_page' . ( empty( $current_tab ) ? '' : '&tab=' . esc_attr( $current_tab ) ) . ( empty( $name ) ? '' : '&section=' . esc_attr( $name ) ) ) ) . '" class="' . esc_attr( $active ) . '">'
							. esc_html( $label ) . '</a> | ';
			}

			echo '</ul></div>';
			echo '</div>';

			$section_fields   = $this->get_section_fields( $current_tab, $current_subtab );
			$settings_section = $this->render_status_section( $section_fields, $current_tab, $current_subtab );

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_settings_section_{$current_tab}_{$current_subtab}_content
			 *
			 * @description Render settings section
			 * @input_vars
			 * [{"var":"$content","type":"string","desc":"Section content"},
			 * {"var":"$section_fields","type":"array","desc":"Section Fields"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_settings_section_{$current_tab}_{$current_subtab}_content', 'function_name', 10, 2 );
			 * @example
			 * <?php
			 * add_filter( 'um_settings_section_{$current_tab}_{$current_subtab}_content', 'my_settings_section', 10, 2 );
			 * function my_settings_section( $content ) {
			 *     // your code here
			 *     return $content;
			 * }
			 * ?>
			 */
			echo apply_filters( 'um_status_section_' . $current_tab . '_' . $current_subtab . '_content',
				$settings_section,
				$section_fields
			);
		}


		/**
		 * @param $tab
		 * @param $section
		 *
		 * @return array
		 */
		public function get_section_fields( $tab, $section ) {
			$status_structure = apply_filters(
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

			if ( '' === $tab ) {
				$tab = 'notices_center';
			}

			if ( empty( $status_structure[ $tab ] ) ) {
				return array();
			}

			if ( ! empty( $status_structure[ $tab ]['sections'][ $section ]['fields'] ) ) {
				return $status_structure[ $tab ]['sections'][ $section ]['fields'];
			} elseif ( ! empty( $status_structure[ $tab ]['fields'] ) ) {
				return $status_structure[ $tab ]['fields'];
			}

			return array();
		}


		/**
		 * Render settings section
		 *
		 * @param array $section_fields
		 * @param string $current_tab
		 * @param string $current_subtab
		 *
		 * @return string
		 */
		public function render_status_section( $section_fields, $current_tab, $current_subtab ) {
			ob_start();

			UM()->admin_forms_settings(
				array(
					'class'     => 'um_status-' . $current_tab . '-' . $current_subtab . ' um-third-column',
					'prefix_id' => 'um_status',
					'fields'    => $section_fields,
				)
			)->render_form(); ?>

			<?php
			$section = ob_get_clean();

			return $section;
		}


		/**
		 * @param $html
		 * @param $section_fields
		 */
		public function settings_notices_center_tab( $html, $section_fields ) {
			$notices = array();
			$notices = $this->old_extensions_notice( $notices );
			$notices = $this->install_core_page_notice( $notices );

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

//			if ( ! $show ) {
//				return;
//			}

			$notices[] = array(
				'id'       => 'old_extensions',
				'priority' => 0,
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
							'priority' => 25,
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
							'priority' => 30,
							'class'    => 'updated',
							'message'  => '<p>' . esc_html__( 'Ultimate Member Setup Error: Account page can not be a child page.', 'ultimate-member' ) . '</p>',
						);
					}
				}

				// DELETE THIS
				$notices[] = array(
					'id'       => 'wrong_account_page',
					'priority' => 30,
					'class'    => 'updated',
					'message'  => '<p>' . esc_html__( 'Ultimate Member Setup Error: Account page can not be a child page.', 'ultimate-member' ) . '</p>',
				);
			}

			return $notices;
		}
	}
}
