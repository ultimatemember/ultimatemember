<?php
namespace umm\forumwp\includes;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class ForumWP_Profile
 *
 * @package umm\forumwp\includes
 */
class Profile {


	/**
	 * Profile constructor.
	 */
	function __construct() {
		add_filter( 'um_profile_tabs', array( $this, 'add_profile_tab' ), 802 );

		if ( UM()->is_request( 'frontend' ) ) {
			add_filter( 'um_user_profile_tabs', array( $this, 'check_profile_tab_privacy' ), 1000, 1 );

			add_filter( 'fmwp_user_display_name', array( $this, 'change_display_name' ), 10, 2 );

			add_filter( 'fmwp_user_profile_link', array( $this, 'user_profile_link' ), 10, 2 );
			add_filter( 'um_user_profile_subnav_link', array( $this, 'profile_subnav_link' ), 10, 3 );

			add_action( 'um_profile_content_forumwp_default', array( $this,'profile_content_forums_default' ) );
			add_action( 'um_profile_content_forumwp_topics', array( $this,'profile_content_forums_topics' ) );
			add_action( 'um_profile_content_forumwp_replies', array( $this,'profile_content_forums_replies' ) );
			add_action( 'um_profile_content_forumwp_subscriptions', array( $this,'profile_content_subscriptions' ) );
			add_action( 'um_profile_content_forumwp_bookmarks', array( $this,'profile_content_bookmarks' ) );
			add_action( 'um_profile_content_forumwp_likes', array( $this,'profile_content_likes' ) );
		}
	}


	/**
	 * @param string $display_name
	 * @param \WP_User $user
	 *
	 * @return string
	 */
	function change_display_name( $display_name, $user ) {
		um_fetch_user( $user->ID );
		$d_name = um_user( 'display_name' );
		um_reset_user();

		return ! empty( $d_name ) ? $d_name : $display_name;
	}


	/**
	 * Change FMWP profile link to UM profile
	 *
	 * @param string $link
	 * @param int $user_id
	 *
	 * @return string
	 */
	function user_profile_link( $link, $user_id ) {
		$link = um_user_profile_url( $user_id );
		return $link;
	}


	function profile_subnav_link( $subnav_link, $id_s, $subtab ) {
		$subnav_link = remove_query_arg( array( 'forumwp_likes', 'forumwp_bookmarks', 'forumwp_subscriptions' ), $subnav_link );

		return $subnav_link;
	}


	/**
	 * Add profile tab
	 *
	 * @param $tabs
	 *
	 * @return mixed
	 */
	function add_profile_tab( $tabs ) {
		$tabs['forumwp'] = array(
			'name' => __( 'Forums', 'ultimate-member' ),
			'icon' => 'fas fa-comments',
		);

		return $tabs;
	}

	/**
	 * Add tabs based on user
	 *
	 * @param $tabs
	 *
	 * @return mixed
	 */
	function check_profile_tab_privacy( $tabs ) {
		if ( empty( $tabs['forumwp'] ) ) {
			return $tabs;
		}

		$user_id = um_user( 'ID' );
		if ( ! $user_id ) {
			return $tabs;
		}

		if ( um_user( 'disable_forumwp_tab' ) ) {
			return $tabs;
		}

		$tabs['forumwp']['subnav']['topics'] = __( 'Topics', 'ultimate-member' ) . '<span>' . FMWP()->user()->get_topics_count( $user_id ) . '</span>';
		$tabs['forumwp']['subnav']['replies'] = __( 'Replies', 'ultimate-member' ) . '<span>' . FMWP()->user()->get_replies_count( $user_id ) . '</span>';
		$tabs['forumwp']['subnav_default'] = 'topics';

		if ( is_user_logged_in() && get_current_user_id() == $user_id ) {
			if ( FMWP()->modules()->is_active( 'likes' ) ) {
				$tabs['forumwp']['subnav']['likes'] = __( 'Likes', 'ultimate-member' ) . '<span>' . FMWP()->module( 'likes' )->get_user_likes_count( $user_id ) . '</span>';
			}
			if ( FMWP()->modules()->is_active( 'bookmarks' ) ) {
				$tabs['forumwp']['subnav']['bookmarks'] = __( 'Bookmarks', 'ultimate-member' ) . '<span>' . FMWP()->module( 'bookmarks' )->get_user_bookmarks_count( $user_id ) . '</span>';
			}
			if ( FMWP()->modules()->is_active( 'subscriptions' ) ) {
				$tabs['forumwp']['subnav']['subscriptions'] = __( 'Subscriptions', 'ultimate-member' ) . '<span>' . FMWP()->module( 'subscriptions' )->get_user_subscriptions_count( $user_id ) . '</span>';
			}
		}

		return $tabs;
	}


	/**
	 *
	 */
	function profile_content_forums_default() {
		$tabs = UM()->profile()->tabs_active();
		$file = ( get_query_var('subnav') ) ? get_query_var('subnav') : $tabs['forumwp']['subnav_default'];

		if ( ! in_array( $file, array_keys( $tabs['forumwp']['subnav'] ) ) ) {
			$file = 'topics';
		}

		call_user_func( array( &$this, 'profile_content_forums_' . $file ) );
	}


	/**
	 *
	 */
	function profile_content_forums_topics() {
		if ( version_compare( get_bloginfo('version'),'5.4', '<' ) ) {
			echo do_shortcode( '[fmwp_user_topics user_id="' . um_user('ID') . '" /]' );
		} else {
			echo apply_shortcodes( '[fmwp_user_topics user_id="' . um_user('ID') . '" /]' );
		}
	}


	/**
	 *
	 */
	function profile_content_forums_replies() {
		if ( version_compare( get_bloginfo('version'),'5.4', '<' ) ) {
			echo do_shortcode( '[fmwp_user_replies user_id="' . um_user('ID') . '" /]' );
		} else {
			echo apply_shortcodes( '[fmwp_user_replies user_id="' . um_user('ID') . '" /]' );
		}
	}


	/**
	 *
	 */
	function profile_content_subscriptions() {
		ob_start(); ?>

		<div class="um-profile-subnav">
			<a href="<?php echo add_query_arg( 'forumwp_subscriptions', 'forums' ) ?>" class="<?php if ( ! isset( $_GET['forumwp_subscriptions'] ) || 'forums' == sanitize_key( $_GET['forumwp_subscriptions'] ) ) { ?>active<?php } ?>"><?php _e( 'Forums', 'ultimate-member' ) ?></a>
			<a href="<?php echo add_query_arg( 'forumwp_subscriptions', 'topics' ) ?>" class="<?php if ( isset( $_GET['forumwp_subscriptions'] ) && 'topics' == sanitize_key( $_GET['forumwp_subscriptions'] ) ) { ?>active<?php } ?>"><?php _e( 'Topics', 'ultimate-member' ) ?></a>
		</div>

		<?php if ( ! isset( $_GET['forumwp_subscriptions'] ) || 'forums' == sanitize_key( $_GET['forumwp_subscriptions'] ) ) {
			if ( version_compare( get_bloginfo('version'),'5.4', '<' ) ) {
				echo do_shortcode( '[fmwp_user_subscriptions user_id="' . um_user('ID') . '" tab="forums" /]' );
			} else {
				echo apply_shortcodes( '[fmwp_user_subscriptions user_id="' . um_user('ID') . '" tab="forums" /]' );
			}
		} else if ( isset( $_GET['forumwp_subscriptions'] ) && 'topics' == sanitize_key( $_GET['forumwp_subscriptions'] ) ) {
			if ( version_compare( get_bloginfo('version'),'5.4', '<' ) ) {
				echo do_shortcode( '[fmwp_user_subscriptions user_id="' . um_user('ID') . '" tab="topics" /]' );
			} else {
				echo apply_shortcodes( '[fmwp_user_subscriptions user_id="' . um_user('ID') . '" tab="topics" /]' );
			}
		}

		ob_get_flush();
	}


	/**
	 *
	 */
	function profile_content_likes() {
		ob_start(); ?>

		<div class="um-profile-subnav">
			<a href="<?php echo add_query_arg( 'forumwp_likes', 'topics' ) ?>" class="<?php if ( ! isset( $_GET['forumwp_likes'] ) || 'topics' == sanitize_key( $_GET['forumwp_likes'] ) ) { ?>active<?php } ?>"><?php _e( 'Topics', 'ultimate-member' ) ?></a>
			<a href="<?php echo add_query_arg( 'forumwp_likes', 'replies' ) ?>" class="<?php if ( isset( $_GET['forumwp_likes'] ) && 'replies' == sanitize_key( $_GET['forumwp_likes'] ) ) { ?>active<?php } ?>"><?php _e( 'Replies', 'ultimate-member' ) ?></a>
		</div>

		<?php if ( ! isset( $_GET['forumwp_likes'] ) || 'topics' == sanitize_key( $_GET['forumwp_likes'] ) ) {
			if ( version_compare( get_bloginfo('version'),'5.4', '<' ) ) {
				echo do_shortcode( '[fmwp_user_likes user_id="' . um_user('ID') . '" tab="topics" /]' );
			} else {
				echo apply_shortcodes( '[fmwp_user_likes user_id="' . um_user('ID') . '" tab="topics" /]' );
			}
		} else if ( isset( $_GET['forumwp_likes'] ) && 'replies' == sanitize_key( $_GET['forumwp_likes'] ) ) {
			if ( version_compare( get_bloginfo('version'),'5.4', '<' ) ) {
				echo do_shortcode( '[fmwp_user_likes user_id="' . um_user('ID') . '" tab="replies" /]' );
			} else {
				echo apply_shortcodes( '[fmwp_user_likes user_id="' . um_user('ID') . '" tab="replies" /]' );
			}
		}

		ob_get_flush();
	}


	/**
	 *
	 */
	function profile_content_bookmarks() {
		ob_start(); ?>

		<div class="um-profile-subnav">
			<a href="<?php echo add_query_arg( 'forumwp_bookmarks', 'topics' ) ?>" class="<?php if ( ! isset( $_GET['forumwp_bookmarks'] ) || 'topics' == sanitize_key( $_GET['forumwp_bookmarks'] ) ) { ?>active<?php } ?>"><?php _e( 'Topics', 'ultimate-member' ) ?></a>
			<a href="<?php echo add_query_arg( 'forumwp_bookmarks', 'replies' ) ?>" class="<?php if ( isset( $_GET['forumwp_bookmarks'] ) && 'replies' == sanitize_key( $_GET['forumwp_bookmarks'] ) ) { ?>active<?php } ?>"><?php _e( 'Replies', 'ultimate-member' ) ?></a>
		</div>

		<?php if ( ! isset( $_GET['forumwp_bookmarks'] ) || 'topics' == sanitize_key( $_GET['forumwp_bookmarks'] ) ) {
			if ( version_compare( get_bloginfo('version'),'5.4', '<' ) ) {
				echo do_shortcode( '[fmwp_user_bookmarks user_id="' . um_user('ID') . '" tab="topics" /]' );
			} else {
				echo apply_shortcodes( '[fmwp_user_bookmarks user_id="' . um_user('ID') . '" tab="topics" /]' );
			}
		} else if ( isset( $_GET['forumwp_bookmarks'] ) && 'replies' == sanitize_key( $_GET['forumwp_bookmarks'] ) ) {
			if ( version_compare( get_bloginfo('version'),'5.4', '<' ) ) {
				echo do_shortcode( '[fmwp_user_bookmarks user_id="' . um_user('ID') . '" tab="replies" /]' );
			} else {
				echo apply_shortcodes( '[fmwp_user_bookmarks user_id="' . um_user('ID') . '" tab="replies" /]' );
			}
		}

		ob_get_flush();
	}
}
