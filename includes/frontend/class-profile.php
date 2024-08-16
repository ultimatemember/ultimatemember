<?php
namespace um\frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Profile
 *
 * @since 2.9.0
 *
 * @package um\frontend
 */
class Profile {

	/**
	 * Profile constructor.
	 */
	public function __construct() {
		add_action( 'um_profile_header', array( &$this, 'header' ), 9 );
		add_action( 'um_profile_menu', array( &$this, 'menu' ), 9 );
		add_action( 'um_profile_content_main', array( &$this, 'about' ) );
	}

	public function header( $args ) {
		$t_args = $args;

		$t_args['current_user_id'] = get_current_user_id();
		$t_args['user_profile_id'] = um_profile_id();

		$t_args['display_name']      = $args['show_name'] ? um_user( 'display_name' ) : '';
		$t_args['show_display_name'] = ! empty( $t_args['display_name'] );

		$t_args['profile_args']    = $args;
		$t_args['wrapper_classes'] = array(
			'um-profile-header',
			'um-profile-no-cover', // @todo add condition as soon as cover will be enabled in new UI.
		);

		$t_args['account_status'] = um_user( 'account_status' );

		$t_args['social_links'] = '';
		if ( ! empty( $args['show_social_links'] ) ) {
			ob_start();
			UM()->fields()->show_social_urls( $t_args['user_profile_id'] );
			$t_args['social_links'] = ob_get_clean();
		}

		$t_args['user_bio'] = '';
		$t_args['show_bio'] = false;
		if ( true === UM()->fields()->viewing ) {
			$bio_html       = false;
			$global_setting = UM()->options()->get( 'profile_show_html_bio' );
			if ( ! empty( $profile_args['use_custom_settings'] ) ) {
				if ( ! empty( $profile_args['show_bio'] ) ) {
					$t_args['show_bio'] = true;
					$bio_html           = ! empty( $global_setting );
				}
			} else {
				$global_show_bio = UM()->options()->get( 'profile_show_bio' );
				if ( ! empty( $global_show_bio ) ) {
					$t_args['show_bio'] = true;
					$bio_html           = ! empty( $global_setting );
				}
			}

			if ( $t_args['show_bio'] ) {
				$description_key = UM()->profile()->get_show_bio_key( $args );

				if ( um_user( $description_key ) ) {
					$description = get_user_meta( $t_args['user_profile_id'], $description_key, true );
					if ( $bio_html ) {
						$t_args['user_bio'] = wp_kses_post( nl2br( make_clickable( wpautop( $description ) ) ) );
					} else {
						$t_args['user_bio'] = nl2br( esc_html( $description ) );
					}
				}

				if ( empty( $t_args['user_bio'] ) ) {
					$t_args['show_bio'] = false;
				}
			}
		}

		UM()->get_template( 'v3/profile/header.php', '', $t_args, true );
	}

	public function menu( $args ) {
		if ( ! UM()->options()->get( 'profile_menu' ) ) {
			return;
		}

		// Get active tabs.
		$tabs = UM()->profile()->tabs_active();

		$all_tabs = $tabs;

		// @todo check maybe we can avoid hidden tabs in new UI. Currently they are used for getting followers list.
		$tabs = array_filter(
			$tabs,
			function ( $item ) {
				if ( ! empty( $item['hidden'] ) ) {
					return false;
				}
				return true;
			}
		);

		$active_tab = UM()->profile()->active_tab();
		// Check here tabs with hidden also, to make correct check of active tab
		if ( ! isset( $all_tabs[ $active_tab ] ) || um_is_on_edit_profile() ) {
			$active_tab                    = 'main';
			UM()->profile()->active_tab    = $active_tab;
			UM()->profile()->active_subnav = null;
		}

		// Need enough tabs to continue.
		if ( count( $tabs ) <= 1 && count( $all_tabs ) === count( $tabs ) ) {
			$has_subnav = false;
			if ( 1 === count( $tabs ) ) {
				foreach ( $tabs as $tab ) {
					if ( isset( $tab['subnav'] ) ) {
						$has_subnav = true;
					}
				}
			}

			if ( ! $has_subnav ) {
				return;
			}
		}

		if ( count( $tabs ) > 1 || count( $all_tabs ) > count( $tabs ) ) {
			// Move default tab priority.
			$default_tab = UM()->options()->get( 'profile_menu_default_tab' );
			$dtab        = array_key_exists( $default_tab, $tabs ) ? $tabs[ $default_tab ] : 'main';
			if ( array_key_exists( $default_tab, $tabs ) ) {
				unset( $tabs[ $default_tab ] );
				$dtabs[ $default_tab ] = $dtab;
				$tabs                  = $dtabs + $tabs;
			}
		}

		$subnav        = array();
		if ( array_key_exists( 'subnav', $tabs[ $active_tab ] ) ) {
			$default_subnav = array_key_exists( 'subnav_default', $tabs[ $active_tab ] ) ? $tabs[ $active_tab ]['subnav_default'] : array_keys( $tabs[ $active_tab ]['subnav'] )[0];
			$active_subnav  = UM()->profile()->active_subnav() ? UM()->profile()->active_subnav() : $default_subnav;
			$subnav         = $tabs[ $active_tab ]['subnav'];
			foreach ( $subnav as $id_s => &$subtab ) {
				$subnav_link = add_query_arg( 'subnav', $id_s );
				$subnav_link = apply_filters( 'um_user_profile_subnav_link', $subnav_link, $id_s, $subtab );

				$subtab['url'] = $subnav_link;

				if ( $active_subnav === $id_s ) {
					$subtab['current'] = true;
				}
			}
			unset( $subtab );
		}

		$t_args = $args;

		$t_args['current_user_id'] = get_current_user_id();
		$t_args['user_profile_id'] = um_profile_id();

		foreach ( $tabs as $id => &$tab ) {
			$nav_link = UM()->permalinks()->get_current_url( UM()->is_permalinks );
			$nav_link = remove_query_arg( array( 'um_action', 'subnav' ), $nav_link );
			$nav_link = add_query_arg( 'profiletab', $id, $nav_link );
			/**
			 * Filters a profile menu navigation link.
			 *
			 * @since 1.3.x
			 * @hook um_profile_menu_link_{$id}
			 *
			 * @param {string} $nav_link Profile tab URL.
			 *
			 * @return {string} Profile tab URL.
			 *
			 * @example <caption>Change user profile menu item `about` link.</caption>
			 * function my_um_profile_menu_link( $link ) {
			 *     // your code here
			 *     $link = 'some_url';
			 *     return $link;
			 * }
			 * add_filter( 'um_profile_menu_link_about', 'my_um_profile_menu_link', 10, 1 );
			 */
			$nav_link = apply_filters( "um_profile_menu_link_{$id}", $nav_link );
			/**
			 * Filters a profile menu navigation links' tag attributes.
			 *
			 * @since 2.6.3
			 * @hook um_profile_menu_link_{$id}_attrs
			 *
			 * @param {string} $profile_nav_attrs Link's tag attributes.
			 * @param {array}  $args              Profile form arguments.
			 *
			 * @return {string} Link's tag attributes.
			 *
			 * @example <caption>Add a link's tag attributes.</caption>
			 * function um_profile_menu_link_attrs( $profile_nav_attrs ) {
			 *     // your code here
			 *     return $profile_nav_attrs;
			 * }
			 * add_filter( 'um_profile_menu_link_{$id}_attrs', 'um_profile_menu_link_attrs', 10, 1 );
			 */
			$profile_nav_attrs = apply_filters( "um_profile_menu_link_{$id}_attrs", '', $args );
			// @todo apply link attributes to the tabs layout.
			$new_tab = array(
				'title' => $tab['name'],
				'url'   => $nav_link,
			);

			if ( $id === $active_tab ) {
				$new_tab['current'] = true;
			}

			if ( array_key_exists( 'notifier', $tab ) ) {
				$new_tab['notifier'] = $tab['notifier'];
			}
			$tab = $new_tab;
		}
		unset( $tab );

		$t_args['tabs']            = $tabs;
		$t_args['subnav']          = $subnav;
		$t_args['profile_args']    = $args;
		$t_args['wrapper_classes'] = array(
			'um-profile-header',
		);

		UM()->get_template( 'v3/profile/menu.php', '', $t_args, true );
	}

	public function about( $args ) {
		if ( ! array_key_exists( 'mode', $args ) ) {
			return;
		}
		$mode = $args['mode'];

		// phpcs:ignore WordPress.Security.NonceVerification -- $_REQUEST is used for echo only
		if ( ! isset( $_REQUEST['um_action'] ) && ! UM()->options()->get( 'profile_tab_main' ) ) {
			return;
		}

		/**
		 * Filters user's ability to view a profile
		 *
		 * @since 1.3.x
		 * @hook  um_profile_can_view_main
		 *
		 * @param {int} $can_view   Can view profile. It's -1 by default.
		 * @param {int} $profile_id User Profile ID.
		 *
		 * @return {int} Can view profile. Set it to -1 for displaying and vice versa to hide.
		 *
		 * @example <caption>Make profile hidden.</caption>
		 * function my_profile_can_view_main( $can_view, $profile_id ) {
		 *     $can_view = 1; // make profile hidden.
		 *     return $can_view;
		 * }
		 * add_filter( 'um_profile_can_view_main', 'my_profile_can_view_main', 10, 2 );
		 */
		$can_view = apply_filters( 'um_profile_can_view_main', -1, um_profile_id() );

		if ( -1 === (int) $can_view ) {
			if ( um_is_on_edit_profile() || UM()->user()->preview ) {
				?>
				<form method="post" action="" class="um-form-new">
				<?php
			}
			/**
			 * Fires before UM Form content.
			 *
			 * @since 1.3.x
			 * @hook  um_before_form
			 *
			 * @param {array} $args UM Form shortcode arguments.
			 *
			 * @example <caption>Make any custom action before UM form.</caption>
			 * function my_before_form( $args ) {
			 *     // your code here
			 * }
			 * add_action( 'um_before_form', 'my_before_form' );
			 */
			do_action( 'um_before_form', $args );
			/**
			 * Fires before UM Form fields.
			 *
			 * Note: $mode can be equals to 'login', 'profile', 'register'.
			 *
			 * @since 1.3.x
			 * @hook  um_before_{$mode}_fields
			 *
			 * @param {array} $args UM Form shortcode arguments.
			 *
			 * @example <caption>Make any custom action before UM Profile form fields.</caption>
			 * function my_before_profile_fields( $args ) {
			 *     // your code here
			 * }
			 * add_action( 'um_before_profile_fields', 'my_before_profile_fields' );
			 * @example <caption>Make any custom action before UM Login form fields.</caption>
			 * function my_before_login_fields( $args ) {
			 *     // your code here
			 * }
			 * add_action( 'um_before_login_fields', 'my_before_login_fields' );
			 * @example <caption>Make any custom action before UM Register form fields.</caption>
			 * function my_before_register_fields( $args ) {
			 *     // your code here
			 * }
			 * add_action( 'um_before_register_fields', 'my_before_register_fields' );
			 */
			do_action( "um_before_{$mode}_fields", $args );
			/**
			 * Fires for rendering UM Form fields.
			 *
			 * Note: $mode can be equals to 'login', 'profile', 'register'.
			 *
			 * @since 1.3.x
			 * @hook  um_main_{$mode}_fields
			 *
			 * @param {array} $args UM Form shortcode arguments.
			 *
			 * @example <caption>Make any custom action when profile form fields are rendered.</caption>
			 * function my_main_profile_fields( $args ) {
			 *     // your code here
			 * }
			 * add_action( 'um_main_profile_fields', 'my_main_profile_fields' );
			 * @example <caption>Make any custom action when login form fields are rendered.</caption>
			 * function my_main_login_fields( $args ) {
			 *     // your code here
			 * }
			 * add_action( 'um_main_login_fields', 'my_main_login_fields' );
			 * @example <caption>Make any custom action when register form fields are rendered.</caption>
			 * function my_main_register_fields( $args ) {
			 *     // your code here
			 * }
			 * add_action( 'um_main_register_fields', 'my_main_register_fields' );
			 */
			do_action( "um_main_{$mode}_fields", $args );
			/**
			 * Fires after UM Form fields.
			 *
			 * @since 1.3.x
			 * @hook  um_after_form_fields
			 *
			 * @param {array} $args UM Form shortcode arguments.
			 *
			 * @example <caption>Make any custom action after UM Form fields.</caption>
			 * function my_after_form_fields( $args ) {
			 *     // your code here
			 * }
			 * add_action( 'um_after_form_fields', 'my_after_form_fields' );
			 */
			do_action( 'um_after_form_fields', $args );
			/**
			 * Fires after UM Form fields.
			 *
			 * Note: $mode can be equals to 'login', 'profile', 'register'.
			 *
			 * @since 1.3.x
			 * @hook  um_after_{$mode}_fields
			 *
			 * @param {array} $args UM Form shortcode arguments.
			 *
			 * @example <caption>Make any custom action after profile form fields.</caption>
			 * function my_after_profile_fields( $args ) {
			 *     // your code here
			 * }
			 * add_action( 'um_after_profile_fields', 'my_after_profile_fields' );
			 * @example <caption>Make any custom action after login form fields.</caption>
			 * function my_after_login_fields( $args ) {
			 *     // your code here
			 * }
			 * add_action( 'um_after_login_fields', 'my_after_login_fields' );
			 * @example <caption>Make any custom action after register form fields.</caption>
			 * function my_after_register_fields( $args ) {
			 *     // your code here
			 * }
			 * add_action( 'um_after_register_fields', 'my_after_register_fields' );
			 */
			do_action( "um_after_{$mode}_fields", $args );
			/**
			 * Fires after UM Form content.
			 *
			 * @since 1.3.x
			 * @hook  um_after_form
			 *
			 * @param {array} $args UM Form shortcode arguments.
			 *
			 * @example <caption>Make any custom action after UM Form content.</caption>
			 * function my_after_form( $args ) {
			 *     // your code here
			 * }
			 * add_action( 'um_after_form', 'my_after_form' );
			 */
			do_action( 'um_after_form', $args );

			if ( um_is_on_edit_profile() || UM()->user()->preview ) {
				?>
				</form>
				<?php
			}
		} else {
			?>
			<div class="um-profile-note">
			<span>
				<i class="um-faicon-lock"></i>
				<?php echo esc_html( $can_view ); ?>
			</span>
			</div>
			<?php
		}
	}
}
