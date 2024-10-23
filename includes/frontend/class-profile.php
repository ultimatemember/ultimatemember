<?php
namespace um\frontend;

use WP_Comment_Query;
use WP_Query;

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

	public static $posts_per_page = 10;

	public static $comments_per_page = 10;

	/**
	 * Profile constructor.
	 */
	public function __construct() {
		add_action( 'um_profile_header', array( &$this, 'header' ), 9 );
		add_action( 'um_profile_navbar', array( &$this, 'navbar' ), 9 );
		add_action( 'um_profile_menu', array( &$this, 'menu' ), 9 );
		add_action( 'um_profile_content_main', array( &$this, 'about' ) );
		add_action( 'um_profile_content_posts', array( &$this, 'posts' ) );
		add_action( 'um_profile_content_comments', array( &$this, 'comments' ) );
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

	public function navbar( $args ) {
		$t_args = $args;

		$t_args['profile_args']    = $args;
		$t_args['current_user_id'] = get_current_user_id();
		$t_args['user_profile_id'] = um_profile_id();

		$index = 0;
		ob_start();
		/**
		 * Fires for displaying content in User Profile navigation bar.
		 *
		 * Internal Ultimate Member callbacks (Priority -> Callback name -> Excerpt):
		 * 4 - `um_followers_add_profile_bar()` displays Followers button.
		 * 5 - `add_profile_bar()` displays Messaging button.
		 *
		 * @param {array} $args    User Profile data.
		 * @param {int}   $user_id User Profile ID.
		 *
		 * @since 2.9.0
		 * @hook  um_profile_navbar_content
		 *
		 * @example <caption>Display some content in User Profile navigation bar.</caption>
		 * function my_um_profile_navbar( $args, $user_id ) {
		 *     // your code here
		 *     echo $content;
		 * }
		 * add_action( 'um_profile_navbar_content', 'my_um_profile_navbar_content', 10, 2 );
		 */
		do_action_ref_array( 'um_profile_navbar_content', array( $args, $t_args['user_profile_id'], &$index ) );

		$content = ob_get_clean();
		if ( empty( $content ) ) {
			return;
		}

		$classes = array( 'um-profile-navbar' );
		if ( $index > 1 ) {
			$classes[] = 'um-grid';
			$classes[] = 'um-grid-col-' . $index;
		}

		//var_dump( $index );

		/**
		 * Filters classes of the User Profile navigation bar.
		 *
		 * Internal Ultimate Member callbacks (Priority -> Callback name -> Excerpt):
		 * 10 - `um_followers_profile_navbar_classes()` from Followers extension class.
		 * 10 - `profile_navbar_classes()` from Messaging extension class.
		 *
		 * @param {array} $classes User Profile navigation bar classes.
		 *
		 * @since 2.0
		 * @since 2.9.0 $classes type is array.
		 * @hook  um_profile_navbar_classes
		 *
		 * @example <caption>Adds `my_custom_class` class to the navigation bar on the User Profile page.</caption>
		 * function my_um_profile_navbar_classes( $classes ) {
		 *     // your code here
		 *     $classes .= 'my_custom_class';
		 *     echo $classes;
		 * }
		 * add_filter( 'um_profile_navbar_classes', 'my_um_profile_navbar_classes' );
		 */
		$classes = apply_filters( 'um_profile_navbar_classes', $classes );

		$t_args['content']         = $content;
		$t_args['wrapper_classes'] = $classes;

		UM()->get_template( 'v3/profile/navbar.php', '', $t_args, true );
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

		$subnav = array();
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
			if ( array_key_exists( 'max_notifier', $tab ) ) {
				$new_tab['max_notifier'] = $tab['max_notifier'];
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
		if ( ! array_key_exists( 'mode', $args ) || 'profile' !== $args['mode'] ) {
			// It should be 'profile' only.
			return;
		}
		$mode = $args['mode']; // used for handling common form hooks but with `profile` mode.

		$all_tabs = UM()->profile()->tabs_active();
		// phpcs:ignore WordPress.Security.NonceVerification -- $_REQUEST is used for echo only
		if ( ! array_key_exists( 'main', $all_tabs ) && ! ( um_is_on_edit_profile() || UM()->user()->preview ) ) {
			// If not preview or edit and tab isn't available to view then return.
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

		// @todo make profile not fully visible for followers, friends and the similar Privacy settings of the user.
		// @todo It's related to the user profile, user card in member directory. See `um_profile_privacy_options` hook.
		if ( -1 === (int) $can_view ) {
			if ( um_is_on_edit_profile() || UM()->user()->preview ) {
				?>
				<form method="post" action="" class="um-form-new">
				<?php
			}

			/** This action is documented in includes/core/um-actions-profile.php */
			do_action( 'um_before_form', $args );
			/** This action is documented in includes/core/um-actions-profile.php */
			do_action( "um_before_{$mode}_fields", $args );
			/** This action is documented in includes/core/um-actions-profile.php */
			do_action( "um_main_{$mode}_fields", $args );
			/** This action is documented in includes/core/um-actions-profile.php */
			do_action( 'um_after_form_fields', $args );
			/** This action is documented in includes/core/um-actions-profile.php */
			do_action( "um_after_{$mode}_fields", $args );
			/** This action is documented in includes/core/um-actions-profile.php */
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

	/**
	 * @param $args
	 *
	 * @return void
	 */
	public function posts( $args ) {
		$user_profile_id = um_profile_id();
		$per_page        = self::$posts_per_page;

		$query_args = array(
			'post_type'        => 'post',
			'posts_per_page'   => $per_page,
			'offset'           => 0,
			'author'           => $user_profile_id,
			'post_status'      => array( 'publish' ),
			'um_main_query'    => true, // make this query pseudo-main.
			'suppress_filters' => false, // for WPML.
		);
		/**
		 * UM hook
		 *
		 * @type filter
		 * @title um_profile_query_make_posts
		 * @description Some changes of WP_Query Posts Tab
		 * @input_vars
		 * [{"var":"$query_posts","type":"WP_Query","desc":"UM Posts Tab query"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage
		 * <?php add_filter( 'um_profile_query_make_posts', 'function_name', 10, 1 ); ?>
		 * @example
		 * <?php
		 * add_filter( 'um_profile_query_make_posts', 'my_profile_query_make_posts', 10, 1 );
		 * function my_profile_query_make_posts( $query_posts ) {
		 *     // your code here
		 *     return $query_posts;
		 * }
		 * ?>
		 */
		$query_args = apply_filters( 'um_profile_query_make_posts', $query_args );

		$posts_query = new WP_Query( $query_args );

		$last_id = 0;
		if ( $posts_query->posts ) {
			$last_post = end( $posts_query->posts );
			$last_id = absint( $last_post->ID );
		}

		$t_args = array(
			'posts'           => $posts_query->posts,
			'per_page'        => $per_page,
			'count_posts'     => $posts_query->found_posts,
			'last_id'         => $last_id,
			'current_user_id' => get_current_user_id(),
			'user_profile_id' => absint( $user_profile_id ),
		);

		UM()->get_template( 'v3/profile/posts.php', '', $t_args, true );
	}

	/**
	 * @param $args
	 *
	 * @return void
	 */
	public function comments( $args ) {
		$user_profile_id = absint( um_profile_id() );
		$per_page        = self::$comments_per_page;

		$query_args = array(
			'number'        => $per_page,
			'offset'        => 0,
			'no_found_rows' => false,
			'user_id'       => $user_profile_id,
			'post_status'   => array( 'publish' ),
			'post_type'     => array( 'post' ),
		);

		if ( get_current_user_id() !== $user_profile_id && ! current_user_can( 'edit_posts' ) ) {
			$query_args['status'] = 'approve';
		}

		$comments_query = new WP_Comment_Query( $query_args );

		$last_id = 0;
		if ( $comments_query->comments ) {
			$last_comment = end( $comments_query->comments );
			$last_id      = absint( $last_comment->comment_ID );
		}

		$t_args = array(
			'comments'        => $comments_query->comments,
			'per_page'        => $per_page,
			'count_comments'  => $comments_query->found_comments,
			'last_id'         => $last_id,
			'current_user_id' => get_current_user_id(),
			'user_profile_id' => absint( $user_profile_id ),
		);

		UM()->get_template( 'v3/profile/comments.php', '', $t_args, true );
	}
}
