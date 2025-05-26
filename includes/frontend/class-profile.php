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
 * @since 3.0.0
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
		add_action( 'um_profile_header_cover_area', array( &$this, 'cover_photo' ), 9 );
		add_action( 'um_profile_navbar', array( &$this, 'navbar' ), 9 );
		add_action( 'um_profile_menu', array( &$this, 'menu' ), 9 );
		add_action( 'um_profile_content_main', array( &$this, 'about' ) );
		add_action( 'um_profile_content_posts', array( &$this, 'posts' ) );
		add_action( 'um_profile_content_comments', array( &$this, 'comments' ) );

		add_action( 'um_after_profile_fields', array( &$this, 'submit_button' ), 1000 );

		add_action( 'um_user_edit_profile', array( $this, 'handle_profile_submission' ), 10, 2 );
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
		);

		if ( ! UM()->options()->get( 'enable_user_cover' ) || UM()->options()->get( 'disable_cover_photo_upload' ) || ! UM()->common()->users()->has_photo( $t_args['user_profile_id'], 'cover_photo' ) ) {
			$t_args['wrapper_classes'][] = 'um-profile-no-cover';
		}

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

	/**
	 * Display cover photo for the user profile.
	 *
	 * @param array $args
	 */
	public function cover_photo( $args ) {
		if ( ! UM()->options()->get( 'enable_user_cover' ) || empty( $args['cover_enabled'] ) ) {
			return;
		}

		$user_profile_id = um_profile_id();

		$has_cover         = UM()->common()->users()->has_photo( $user_profile_id, 'cover_photo' );
		$default_cover_url = UM()->options()->get_default_cover_url();

		$wrapper_classes = array( 'um-cover-wrapper' );
		if ( $has_cover ) {
			$wrapper_classes[] = 'um-has-cover';
		} elseif ( ! empty( $default_cover_url ) ) {
			$wrapper_classes[] = 'um-default-cover';
		}
		?>
		<div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>">
			<?php
			if ( $has_cover || ! empty( $default_cover_url ) ) {
				$cover_args = array();
				if ( $has_cover ) {
					$cover_size = null; // Means original.
					if ( ! empty( $args['coversize'] ) ) {
						$cover_size = $args['coversize']; // Gets from form setting and if empty goes to global UM > Appearance settings.
					}
					// Set for mobile width = 300 by default but can be changed via filter below.
					$cover_size = wp_is_mobile() ? 300 : $cover_size;
					/**
					 * Filters the cover photo size.
					 *
					 * @param {string} $cover_size Default cover photo size.
					 * @param {array}  $args       Profile form data arguments.
					 *
					 * @since 3.0.0
					 * @hook  um_cover_photo_size
					 *
					 * @example <caption>Change the mobile cover size to 600 px.</caption>
					 * function my_default_cover_uri( $cover_size, $args ) {
					 *     if ( wp_is_mobile() ) {
					 *         $cover_size = 600;
					 *     }
					 *     return $cover_size;
					 * }
					 * add_filter( 'um_cover_photo_size', 'my_cover_photo_size', 10, 2 );
					 */
					$cover_args['size'] = apply_filters( 'um_cover_photo_size', $cover_size, $args );
				}

				/**
				 * UM hook
				 *
				 * @type action
				 * @title um_cover_area_content
				 * @description Cover area content change
				 * @input_vars
				 * [{"var":"$user_id","type":"int","desc":"User ID"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_action( 'um_cover_area_content', 'function_name', 10, 1 );
				 * @example
				 * <?php
				 * add_action( 'um_cover_area_content', 'my_cover_area_content', 10, 1 );
				 * function my_cover_area_content( $user_id ) {
				 *     // your code here
				 * }
				 * ?>
				 */
				do_action( 'um_cover_area_content', $args, $user_profile_id );

				echo wp_kses( UM()->frontend()::layouts()::cover_photo( $user_profile_id, $cover_args ), UM()->get_allowed_html( 'templates' ) );
			}
			?>
		</div>
		<?php
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
		 * @since 3.0.0
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
		 * @since 3.0.0 $classes type is array.
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
			if ( array_key_exists( 'notifier_type', $tab ) ) {
				$new_tab['notifier_type'] = $tab['notifier_type'];
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

		if ( get_current_user_id() !== $user_profile_id ) {
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

	/**
	 * Show the submit button (highest priority)
	 *
	 * @param $args
	 */
	public function submit_button( $args ) {
		// DO NOT add when reviewing user's details
		if ( is_admin() && UM()->user()->preview ) {
			return;
		}

		// only when editing
		if ( false === UM()->fields()->editing ) {
			return;
		}

		if ( ! isset( $args['primary_btn_word'] ) || '' === $args['primary_btn_word'] ) {
			$args['primary_btn_word'] = UM()->options()->get( 'profile_primary_btn_word' );
		}
		?>
		<div class="um-form-submit">
			<?php
			echo UM()->frontend()::layouts()::button(
				$args['primary_btn_word'],
				array(
					'type'   => 'submit',
					'design' => 'primary',
					'width'  => 'full',
					'id'     => 'um-submit-btn',
				)
			);
			?>
		</div>
		<?php
	}

	/**
	 * Update user's profile (frontend).
	 * It handles the profile form submission.
	 * Note: Legacy version of this function is `um_user_edit_profile()`.
	 *
	 * @param array $args
	 * @param array $form_data
	 */
	public function handle_profile_submission( $args, $form_data ) {
		global $wp_filesystem;

		$to_update = null;

		$user_id = null;
		if ( isset( $args['user_id'] ) ) {
			$user_id = $args['user_id'];
		} elseif ( isset( $args['_user_id'] ) ) {
			$user_id = $args['_user_id'];
		}

		if ( UM()->roles()->um_current_user_can( 'edit', $user_id ) ) {
			UM()->user()->set( $user_id );
		} else {
			wp_die( esc_html__( 'You are not allowed to edit this user.', 'ultimate-member' ) );
		}

		$userinfo = UM()->user()->profile;

		$user_basedir = UM()->common()->filesystem()->get_user_uploads_dir( $user_id );
		/**
		 * Fires before collecting data to update on profile form submit.
		 *
		 * @since 1.3.x
		 * @hook um_user_before_updating_profile
		 *
		 * @param {array} $userinfo Userdata.
		 *
		 * @example <caption>Make any custom action before collecting data to update on profile form submit.</caption>
		 * function my_user_before_updating_profile( $role_key, $role_meta ) {
		 *     // your code here
		 * }
		 * add_action( 'um_user_before_updating_profile', 'my_user_before_updating_profile', 10, 2 );
		 */
		do_action( 'um_user_before_updating_profile', $userinfo );

		$fields = maybe_unserialize( $form_data['custom_fields'] );
		$fields = apply_filters( 'um_user_edit_profile_fields', $fields, $args, $form_data );

		// loop through fields
		if ( ! empty( $fields ) ) {
			$arr_restricted_fields = UM()->fields()->get_restricted_fields_for_edit( $user_id );

			if ( ! function_exists( 'wp_get_image_editor' ) ) {
				require_once ABSPATH . 'wp-admin/includes/media.php';
			}

			foreach ( $fields as $key => $array ) {
				if ( ! isset( $array['type'] ) ) {
					continue;
				}

				if ( isset( $array['edit_forbidden'] ) ) {
					continue;
				}

				if ( is_array( $array ) ) {
					$origin_data = UM()->fields()->get_field( $key );
					if ( is_array( $origin_data ) ) {
						// Merge data passed with original field data.
						$array = array_merge( $origin_data, $array );
					}
				}

				// required option? 'required_opt' - it's field attribute predefined in the field data in code
				// @todo can be unnecessary. it's used in 1 place (user account).
				if ( isset( $array['required_opt'] ) ) {
					$opt = $array['required_opt'];
					if ( UM()->options()->get( $opt[0] ) !== $opt[1] ) {
						continue;
					}
				}

				// fields that need to be disabled in edit mode (profile) (email, username, etc.)
				if ( is_array( $arr_restricted_fields ) && in_array( $key, $arr_restricted_fields, true ) ) {
					continue;
				}

				if ( ! um_can_edit_field( $array ) || ! um_can_view_field( $array ) ) {
					continue;
				}

				// skip saving role here
				if ( in_array( $key, array( 'role', 'role_select', 'role_radio' ), true ) ) {
					continue;
				}

				//the same code in class-validation.php validate_fields_values for registration form
				//rating field validation
				if ( 'rating' === $array['type'] && isset( $args['submitted'][ $key ] ) ) {
					if ( ! is_numeric( $args['submitted'][ $key ] ) ) {
						continue;
					} else {
						if ( $array['number'] == 5 ) {
							if ( ! in_array( $args['submitted'][ $key ], range( 1, 5 ) ) ) {
								continue;
							}
						} elseif ( $array['number'] == 10 ) {
							if ( ! in_array( $args['submitted'][ $key ], range( 1, 10 ) ) ) {
								continue;
							}
						}
					}
				}

				// Returns dropdown/multi-select options keys from a callback function
				/** This filter is documented in includes/core/class-fields.php */
				$has_custom_source = apply_filters( "um_has_dropdown_options_source__$key", false );
				if ( isset( $array['options'] ) && in_array( $array['type'], array( 'select', 'multiselect' ), true ) ) {
					$options          = $array['options'];
					$choices_callback = UM()->fields()->get_custom_dropdown_options_source( $key, $array );
					if ( ! empty( $choices_callback ) && ! $has_custom_source ) {
						if ( ! empty( $array['parent_dropdown_relationship'] ) ) {
							$parent_dropdown_relationship = $array['parent_dropdown_relationship'];
							// Get parent values from the form's $_POST data or userdata
							$parent_options = array();
							if ( isset( UM()->form()->post_form[ $parent_dropdown_relationship ] ) ) {
								if ( ! is_array( UM()->form()->post_form[ $parent_dropdown_relationship ] ) ) {
									$parent_options = array( UM()->form()->post_form[ $parent_dropdown_relationship ] );
								} else {
									$parent_options = UM()->form()->post_form[ $parent_dropdown_relationship ];
								}
							} elseif ( um_user( $parent_dropdown_relationship ) ) {
								if ( ! is_array( um_user( $parent_dropdown_relationship ) ) ) {
									$parent_options = array( um_user( $parent_dropdown_relationship ) );
								} else {
									$parent_options = um_user( $parent_dropdown_relationship );
								}
							}

							$callback_result = $choices_callback( $parent_options, $parent_dropdown_relationship );
						} else {
							$callback_result = $choices_callback();
						}
						if ( is_array( $callback_result ) ) {
							$options = array_keys( $callback_result );
						}
					}
					$array['options'] = apply_filters( "um_custom_dropdown_options__{$key}", $options );
				}

				// Validation of correct values from options in wp-admin.
				$stripslashes = '';
				if ( isset( $args['submitted'][ $key ] ) && is_string( $args['submitted'][ $key ] ) ) {
					$stripslashes = wp_unslash( $args['submitted'][ $key ] );
				}

				if ( 'select' === $array['type'] ) {
					if ( ! empty( $array['options'] ) && ! empty( $stripslashes ) && ! in_array( $stripslashes, array_map( 'trim', $array['options'] ) ) && ! $has_custom_source ) {
						continue;
					}

					//update empty user meta
					if ( ! isset( $args['submitted'][ $key ] ) || '' === $args['submitted'][ $key ] ) {
						update_user_meta( $user_id, $key, '' );
					}
				}

				//validation of correct values from options in wp-admin
				//the user cannot set invalid value in the hidden input at the page
				if ( in_array( $array['type'], array( 'multiselect', 'checkbox', 'radio' ), true ) ) {
					if ( ! empty( $args['submitted'][ $key ] ) && ! empty( $array['options'] ) ) {
						if ( is_array( $args['submitted'][ $key ] ) ) {
							$args['submitted'][ $key ] = array_map( 'stripslashes', array_map( 'trim', $args['submitted'][ $key ] ) );
							if ( is_array( $array['options'] ) ) {
								$args['submitted'][ $key ] = array_intersect( $args['submitted'][ $key ], array_map( 'trim', $array['options'] ) );
							} else {
								$args['submitted'][ $key ] = array_intersect( $args['submitted'][ $key ], array( trim( $array['options'] ) ) );
							}
						} else {
							if ( is_array( $array['options'] ) ) {
								$args['submitted'][ $key ] = array_intersect( array( stripslashes( trim( $args['submitted'][ $key ] ) ) ), array_map( 'trim', $array['options'] ) );
							} else {
								$args['submitted'][ $key ] = array_intersect( array( stripslashes( trim( $args['submitted'][ $key ] ) ) ), array( trim( $array['options'] ) ) );
							}
						}
					}

					// update empty user meta
					if ( ! isset( $args['submitted'][ $key ] ) || '' === $args['submitted'][ $key ] ) {
						update_user_meta( $user_id, $key, array() );
					}
				}

				if ( isset( $args['submitted'][ $key ] ) ) {
					if ( in_array( $array['type'], array( 'image', 'file' ), true ) ) {

						if ( ! empty( $args['submitted'][ $key ]['temp_hash'] ) ) {
							$filepath = UM()->common()->filesystem()->get_file_by_hash( $args['submitted'][ $key ]['temp_hash'] );
							if ( ! empty( $filepath ) ) {
								// Delete old file if it's exists.
								if ( isset( $userinfo[ $key ] ) ) {
									$old_file_path = wp_normalize_path( $user_basedir . DIRECTORY_SEPARATOR . $userinfo[ $key ] );
									if ( file_exists( $old_file_path ) ) {
										wp_delete_file( $old_file_path );
									}
								}

								if ( 'profile_photo' === $key ) {
									// Flush user directory from original profile photo thumbnails.
									$files = scandir( wp_normalize_path( $user_basedir . DIRECTORY_SEPARATOR ) );
									if ( ! empty( $files ) ) {
										foreach ( $files as $file ) {
											if ( preg_match( '/^profile_photo-(.*?)/', $file ) ) {
												wp_delete_file( wp_normalize_path( $user_basedir . DIRECTORY_SEPARATOR . $file ) );
											}
										}
									}

									$file_type = wp_check_filetype( $filepath );
									$filename  = 'profile_photo.' . $file_type['ext'];
								} elseif ( 'cover_photo' === $key ) {
									// Flush user directory from original cover photo thumbnails.
									$files = scandir( $user_basedir . DIRECTORY_SEPARATOR );
									if ( ! empty( $files ) ) {
										foreach ( $files as $file ) {
											if ( preg_match( '/^cover_photo-(.*?)/', $file ) ) {
												wp_delete_file( wp_normalize_path( $user_basedir . DIRECTORY_SEPARATOR . $file ) );
											}
										}
									}

									$file_type = wp_check_filetype( $filepath );
									$filename  = 'cover_photo.' . $file_type['ext'];
								} else {
									$filename     = sanitize_file_name( $args['submitted'][ $key ]['filename'] );
									$new_filepath = wp_normalize_path( $user_basedir . DIRECTORY_SEPARATOR . $filename );
									if ( file_exists( $new_filepath ) ) {
										$filename = wp_unique_filename( $user_basedir . DIRECTORY_SEPARATOR, $filename );
									}
								}

								$new_filepath  = wp_normalize_path( $user_basedir . DIRECTORY_SEPARATOR . $filename );
								$moving_result = $wp_filesystem->move( $filepath, $new_filepath, true );

								if ( $moving_result ) {
									if ( 'profile_photo' === $key ) {
										$image = wp_get_image_editor( $new_filepath ); // Return an implementation that extends WP_Image_Editor
										if ( ! is_wp_error( $image ) ) {
											// Creates new file's thumbnails.
											$sizes_array = array();
											$all_sizes   = UM()->config()->get( 'avatar_thumbnail_sizes' );
											foreach ( $all_sizes as $size ) {
												$sizes_array[] = array( 'width' => $size );
											}
											$image->multi_resize( $sizes_array );
										}
									} elseif ( 'cover_photo' === $key ) {
										$image = wp_get_image_editor( $new_filepath ); // Return an implementation that extends WP_Image_Editor
										if ( ! is_wp_error( $image ) ) {
											// Creates new file's thumbnails.
											$sizes_array = array();
											$all_sizes   = UM()->options()->get( 'cover_thumb_sizes' );
											foreach ( $all_sizes as $size ) {
												$sizes_array[] = array( 'width' => $size );
											}
											$image->multi_resize( $sizes_array );
										}
									}

									$to_update[ $key ] = $filename;

									if ( 'file' === $array['type'] ) {
										$file_type = wp_check_filetype( $new_filepath );
										$size      = filesize( $new_filepath );

										$file_metadata = array(
											'ext'         => $file_type['ext'],
											'type'        => $file_type['type'],
											'size'        => $size,
											'size_format' => size_format( $size ),
										);

										$file_metadata = apply_filters( 'um_file_metadata', $file_metadata, $new_filepath, $key, $args['submitted'] );

										$to_update[ $key . '_metadata' ] = $file_metadata;
									}
								}
							}
						} elseif ( isset( $userinfo[ $key ] ) && $args['submitted'][ $key ]['filename'] !== $userinfo[ $key ] ) {
							// File was deleted on frontend
							// delete old file if it's exists
							if ( file_exists( $user_basedir . DIRECTORY_SEPARATOR . $userinfo[ $key ] ) ) {
								wp_delete_file( $user_basedir . DIRECTORY_SEPARATOR . $userinfo[ $key ] );

								if ( 'profile_photo' === $key ) {
									// Flush user directory from original profile photo thumbnails.
									$files = scandir( wp_normalize_path( $user_basedir . DIRECTORY_SEPARATOR ) );
									if ( ! empty( $files ) ) {
										foreach ( $files as $file ) {
											if ( preg_match( '/^profile_photo-(.*?)/', $file ) ) {
												wp_delete_file( wp_normalize_path( $user_basedir . DIRECTORY_SEPARATOR . $file ) );
											}
										}
									}
								} elseif ( 'cover_photo' === $key ) {
									// Flush user directory from original cover photo thumbnails.
									$files = scandir( $user_basedir . DIRECTORY_SEPARATOR );
									if ( ! empty( $files ) ) {
										foreach ( $files as $file ) {
											if ( preg_match( '/^cover_photo-(.*?)/', $file ) ) {
												wp_delete_file( wp_normalize_path( $user_basedir . DIRECTORY_SEPARATOR . $file ) );
											}
										}
									}
								}
							}

							$to_update[ $key ] = '';
							if ( 'file' === $array['type'] ) {
								$to_update[ $key . '_metadata' ] = '';
							}
						}
					} elseif ( 'password' === $array['type'] ) {
						$to_update[ $key ] = wp_hash_password( $args['submitted'][ $key ] );
						// translators: %s: title.
						$args['submitted'][ $key ] = sprintf( __( 'Your choosed %s', 'ultimate-member' ), $array['title'] );
					} elseif ( isset( $userinfo[ $key ] ) && $args['submitted'][ $key ] !== $userinfo[ $key ] ) {
						$to_update[ $key ] = $args['submitted'][ $key ];
					} elseif ( '' !== $args['submitted'][ $key ] ) {
						$to_update[ $key ] = $args['submitted'][ $key ];
					}

					// use this filter after all validations has been completed, and we can extend data based on key
					$to_update = apply_filters( 'um_change_usermeta_for_update', $to_update, $args, $fields, $key );
				}
			}
		}

		$description_key = UM()->profile()->get_show_bio_key( $args );
		if ( ! isset( $to_update[ $description_key ] ) && isset( $args['submitted'][ $description_key ] ) ) {
			if ( ! empty( $form_data['use_custom_settings'] ) && ! empty( $form_data['show_bio'] ) ) {
				$to_update[ $description_key ] = $args['submitted'][ $description_key ];
			} else {
				if ( UM()->options()->get( 'profile_show_bio' ) ) {
					$to_update[ $description_key ] = $args['submitted'][ $description_key ];
				}
			}
		}

		// Secure selected role.
		// It's for a legacy case `array_key_exists( 'editable', $fields['role'] )` and similar.
		if ( ( isset( $fields['role'] ) && ( ! array_key_exists( 'editable', $fields['role'] ) || ! empty( $fields['role']['editable'] ) ) && um_can_view_field( $fields['role'] ) ) ||
			 ( isset( $fields['role_select'] ) && ( ! array_key_exists( 'editable', $fields['role_select'] ) || ! empty( $fields['role_select']['editable'] ) ) && um_can_view_field( $fields['role_select'] ) ) ||
			 ( isset( $fields['role_radio'] ) && ( ! array_key_exists( 'editable', $fields['role_radio'] ) || ! empty( $fields['role_radio']['editable'] ) ) && um_can_view_field( $fields['role_radio'] ) ) ) {

			if ( ! empty( $args['submitted']['role'] ) ) {
				global $wp_roles;
				$exclude_roles = array_diff( array_keys( $wp_roles->roles ), UM()->roles()->get_editable_user_roles() );

				if ( ! in_array( $args['submitted']['role'], $exclude_roles, true ) ) {
					$to_update['role'] = $args['submitted']['role'];
				}

				$args['roles_before_upgrade'] = UM()->roles()->get_all_user_roles( $user_id );
			}
		}

		/**
		 * UM hook
		 *
		 * @type action
		 * @title um_user_pre_updating_profile
		 * @description Some actions before profile submit
		 * @input_vars
		 * [{"var":"$userinfo","type":"array","desc":"Submitted User Data"},
		 * {"var":"$user_id","type":"int","desc":"User ID"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage add_action( 'um_user_pre_updating_profile', 'function_name', 10, 2 );
		 * @example
		 * <?php
		 * add_action( 'um_user_pre_updating_profile', 'my_user_pre_updating_profile', 10, 2 );
		 * function my_user_pre_updating_profile( $userinfo, $user_id ) {
		 *     // your code here
		 * }
		 * ?>
		 */
		do_action( 'um_user_pre_updating_profile', $to_update, $user_id, $form_data );

		/**
		 * UM hook
		 *
		 * @type filter
		 * @title um_user_pre_updating_profile_array
		 * @description Change submitted data before update profile
		 * @input_vars
		 * [{"var":"$to_update","type":"array","desc":"Profile data upgrade"},
		 * {"var":"$user_id","type":"int","desc":"User ID"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage
		 * <?php add_filter( 'um_user_pre_updating_profile_array', 'function_name', 10, 2 ); ?>
		 * @example
		 * <?php
		 * add_filter( 'um_user_pre_updating_profile_array', 'my_user_pre_updating_profile', 10, 2 );
		 * function my_user_pre_updating_profile( $to_update, $user_id ) {
		 *     // your code here
		 *     return $to_update;
		 * }
		 * ?>
		 */
		$to_update = apply_filters( 'um_user_pre_updating_profile_array', $to_update, $user_id, $form_data );

		if ( is_array( $to_update ) ) {
			if ( isset( $to_update['first_name'] ) || isset( $to_update['last_name'] ) || isset( $to_update['nickname'] ) ) {
				$user = get_userdata( $user_id );
				if ( ! empty( $user ) && ! is_wp_error( $user ) ) {
					UM()->user()->previous_data['display_name'] = $user->display_name;

					if ( isset( $to_update['first_name'] ) ) {
						UM()->user()->previous_data['first_name'] = $user->first_name;
					}

					if ( isset( $to_update['last_name'] ) ) {
						UM()->user()->previous_data['last_name'] = $user->last_name;
					}

					if ( isset( $to_update['nickname'] ) ) {
						UM()->user()->previous_data['nickname'] = $user->nickname;
					}
				}
			}

			UM()->user()->update_profile( $to_update );
			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_after_user_updated
			 * @description Some actions after user profile updated
			 * @input_vars
			 * [{"var":"$user_id","type":"int","desc":"User ID"},
			 * {"var":"$args","type":"array","desc":"Form Data"},
			 * {"var":"$userinfo","type":"array","desc":"Submitted User Data"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_after_user_updated', 'function_name', 10, 33 );
			 * @example
			 * <?php
			 * add_action( 'um_after_user_updated', 'my_after_user_updated', 10, 3 );
			 * function my_after_user_updated( $user_id, $args, $userinfo ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_after_user_updated', $user_id, $args, $to_update );
		}

		/** This action is documented in ultimate-member/includes/core/um-actions-register.php */
		do_action( 'um_update_profile_full_name', $user_id, $to_update );

		/**
		 * UM hook
		 *
		 * @type action
		 * @title um_user_after_updating_profile
		 * @description After upgrade user's profile
		 * @input_vars
		 * [{"var":"$submitted","type":"array","desc":"Form data"},
		 * {"var":"$user_id","type":"int","desc":"User Id"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage add_action( 'um_user_after_updating_profile', 'function_name', 10, 1 );
		 * @example
		 * <?php
		 * add_action( 'um_user_after_updating_profile', 'my_user_after_updating_profile'', 10, 2 );
		 * function my_user_after_updating_profile( $submitted, $user_id ) {
		 *     // your code here
		 * }
		 * ?>
		 */
		do_action( 'um_user_after_updating_profile', $to_update, $user_id, $args );

		// Finally redirect to profile.
		$url = um_user_profile_url( $user_id );
		$url = apply_filters( 'um_update_profile_redirect_after', $url, $user_id, $args );
		// Not `um_safe_redirect()` because predefined user profile page is situated on the same host.
		wp_safe_redirect( um_edit_my_profile_cancel_uri( $url ) );
		exit;
	}
}
