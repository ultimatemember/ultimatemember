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
			/**
			 * Filters extend Ultimate Member status structure.
			 *
			 * @param {array} $structure status structure.
			 *
			 * @return {array} new status structure.
			 *
			 * @since 2.8.3
			 * @hook um_status_structure
			 *
			 * @example <caption>Extend UM status structure.</caption>
			 * function my_um_status_structure( $structure ) {
			 *     $structure = array();
			 *     return $structure;
			 * }
			 * add_filter( 'um_status_structure', 'my_um_status_structure', $structure );
			 */
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
			 * Show some content before section content at settings page
			 *
			 * @since 2.8.3
			 * @hook um_status_page_{$current_tab}_before_section
			 *
			 * @example <caption>Add description on the Notice center tab.</caption>
			 * function my_settings_page_before_section( $current_tab ) {
			 *     echo esc_html__( 'Tab description', 'ultimate-member' );
			 * }
			 * add_action( 'um_status_page_notices_center_before_section', 'my_settings_page_before_section' );
			 */
			do_action( "um_status_page_{$current_tab}_before_section" );

			/**
			 * Filters Ultimate Member status page content.
			 *
			 * @param {string} $current_tab Status page tab name.
			 *
			 * @return {string} Status page tab content.
			 *
			 * @since 2.8.3
			 * @hook um_status_section_{$current_tab}__content
			 *
			 * @example <caption>Extend UM core pages.</caption>
			 * function my_status_section_content( $pages ) {
			 *     $output = esc_html__( 'My custom page', 'ultimate-member' );
			 *     return $output;
			 * }
			 * add_filter( 'um_status_section_notices_center__content', 'my_status_section_content' );
			 */
			$custom_content = apply_filters( "um_status_section_{$current_tab}__content", $current_tab );

			echo $custom_content;

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

			ob_start();

			$notices = array();
			$notices = $this->install_core_page_notice( $notices );
			$notices = $this->exif_extension_notice( $notices );
			$notices = $this->lock_registration( $notices );
			$notices = $this->child_theme_required( $notices );
			$notices = $this->common_secure( $notices );

			usort(
				$notices,
				function( $a, $b ) {
					return $a['priority'] - $b['priority'];
				}
			);

			include_once UM()->admin()->templates_path . 'status/notices.php';
			return ob_get_clean();
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


		/**
		 * Checking if the "Membership - Anyone can register" WordPress general setting is active
		 */
		public function lock_registration( $notices ) {
			$users_can_register = get_option( 'users_can_register' );
			if ( ! $users_can_register ) {
				return $notices;
			}

			$allowed_html = array(
				'a'      => array(
					'href' => array(),
				),
				'strong' => array(),
			);

			$notices[] = array(
				'id'       => 'lock_registration',
				'priority' => 60,
				'class'    => 'info',
				// translators: %s: Setting link.
				'message'  => '<p>' . wp_kses( sprintf( __( 'The <strong>"Membership - Anyone can register"</strong> option on the general settings <a href="%s">page</a> is enabled. This means users can register via the standard WordPress wp-login.php page. If you do not want users to be able to register via this page and only register via the Ultimate Member registration form, you should deactivate this option. You can dismiss this notice if you wish to keep the wp-login.php registration page open.', 'ultimate-member' ), admin_url( 'options-general.php' ) . '#users_can_register' ), $allowed_html ) . '</p>',
			);

			return $notices;
		}


		/**
		 * Check if there isn't installed child-theme. Child theme is required for safely saved customizations.
		 */
		public function child_theme_required( $notices ) {
			if ( ! is_child_theme() ) {
				if ( ! is_dir( get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'ultimate-member' ) ) {
					return $notices;
				}

				$notices[] = array(
					'id'       => 'um_is_not_child_theme',
					'priority' => 70,
					'class'    => 'notice-warning',
					// translators: %s: Setting link.
					'message'  => '<p>' . wp_kses( sprintf( __( 'We highly recommend using a <a href="%s">child-theme</a> for Ultimate Member customization, which hasn\'t dependencies with the official themes repo, so your custom files cannot be rewritten after a theme upgrade.<br />Otherwise, the customization files may be deleted after every theme upgrade.', 'ultimate-member' ), 'https://developer.wordpress.org/themes/advanced-topics/child-themes/' ), UM()->get_allowed_html( 'admin_notice' ) ) . '</p>',
				);

			}

			return $notices;
		}


		public function common_secure( $notices ) {
			if ( UM()->options()->get( 'lock_register_forms' ) ) {
				$notices[] = array(
					'id'       => 'common_secure_register',
					'priority' => 80,
					'class'    => 'warning',
					'message'  => '<p>' . esc_html__( 'Your Register forms are now locked. You can unlock them in Ultimate Member > Settings > Secure > Lock All Register Forms.', 'ultimate-member' ) . '</p>',
				);
			}

			if ( UM()->options()->get( 'display_login_form_notice' ) ) {
				$notices[] = array(
					'id'       => 'common_secure_password_reset',
					'priority' => 90,
					'class'    => 'warning',
					'message'  => '<p>' . esc_html__( 'Mandatory password changes has been enabled. You can disable them in Ultimate Member > Settings > Secure > Display Login form notice to reset passwords.', 'ultimate-member' ) . '</p>',
				);
			}

			if ( UM()->options()->get( 'secure_ban_admins_accounts' ) ) {
				$notices[] = array(
					'id'       => 'common_secure_suspicious_activity',
					'priority' => 100,
					'class'    => 'warning',
					'message'  => '<p>' . esc_html__( 'Ban for administrative capabilities is enabled. You can disable them in Ultimate Member > Settings > Secure > Enable ban for administrative capabilities.', 'ultimate-member' ) . '</p>',
				);
			}

			$arr_banned_caps = UM()->options()->get( 'banned_capabilities' );
			if ( empty( $arr_banned_caps ) ) {
				return $notices;
			}

			$global_role = get_option( 'default_role' ); // WP Global settings
			$global_role = get_role( $global_role );
			$caps        = ( null !== $global_role && ! empty( $global_role->capabilities ) ) ? $global_role->capabilities : array();
			foreach ( array_keys( $caps ) as $cap ) {
				if ( in_array( $cap, $arr_banned_caps, true ) ) {
					$notices[] = array(
						'id'       => 'default_role_suspicious_activity',
						'priority' => 110,
						'class'    => 'notice-warning',
						'message'  => '<p>' . esc_html__( 'The role selected in WordPress native "Settings > New User Default Role" setting has Administrative capabilities.', 'ultimate-member' ) . '</p>',
					);
					break;
				}
			}

			$um_global_role = UM()->options()->get( 'register_role' ); // UM Settings Global settings
			if ( ! empty( $um_global_role ) ) {
				$um_global_role = get_role( $um_global_role );
				$caps           = ( null !== $um_global_role && ! empty( $um_global_role->capabilities ) ) ? $um_global_role->capabilities : array();
				foreach ( array_keys( $caps ) as $cap ) {
					if ( in_array( $cap, $arr_banned_caps, true ) ) {
						$notices[] = array(
							'id'       => 'register_role_suspicious_activity',
							'priority' => 120,
							'class'    => 'notice-warning',
							'message'  => '<p>' . esc_html__( 'The role selected in "Ultimate Member > Settings > Appearance > Registration Form > Registration Default Role" setting has Administrative capabilities.', 'ultimate-member' ) . '</p>',
						);
						break;
					}
				}
			}

			$arr_banned_caps = UM()->options()->get( 'banned_capabilities' );
			if ( empty( $arr_banned_caps ) ) {
				return $notices;
			}

			$um_forms = get_posts(
				array(
					'post_type'   => 'um_form',
					'meta_query'  => array(
						array(
							'key'   => '_um_mode',
							'value' => 'register',
						),
						array(
							'key'   => '_um_register_use_custom_settings',
							'value' => true,
						),
					),
					'numberposts' => -1,
					'fields'      => 'ids',
				)
			);

			$content = '';
			foreach ( $um_forms as $form_id ) {
				$role = get_post_meta( $form_id, '_um_register_role', true );
				if ( empty( $role ) ) {
					continue;
				}

				$role = get_role( $role );
				$caps = ( null !== $role && ! empty( $role->capabilities ) ) ? $role->capabilities : array();
				foreach ( array_keys( $caps ) as $cap ) {
					if ( in_array( $cap, $arr_banned_caps, true ) ) {
						$content .= '<br /><a target="_blank" href="' . get_edit_post_link( $form_id ) . '">' . get_the_title( $form_id ) . '</a> contains <strong>administrative role</strong>.';
						break;
					}
				}
			}

			if ( empty( $content ) ) {
				return $notices;
			}

			$notices[] = array(
				'id'       => 'forms_secure_suspicious_activity',
				'priority' => 140,
				'class'    => 'notice-warning',
				// translators: %s are link(s) to the forms.
				'message'  => '<p>' . wp_kses( sprintf( __( 'Register forms have Administrative roles, we recommend that you assign a non-admin roles to secure the forms. %s', 'ultimate-member' ), $content ), UM()->get_allowed_html( 'admin_notice' ) ) . '</p>',
			);

			return $notices;
		}
	}
}
