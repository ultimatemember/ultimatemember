<?php
namespace umm\forumwp\includes\frontend;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Profile
 *
 * @package umm\forumwp\includes\frontend
 */
class Profile {


	/**
	 * Profile constructor.
	 */
	public function __construct() {
		add_filter( 'um_user_profile_tabs', array( $this, 'check_profile_tab_privacy' ), 1000, 1 );
		add_filter( 'um_user_profile_subnav_link', array( $this, 'profile_subnav_link' ), 10, 1 );
		add_action( 'um_profile_content_forumwp_default', array( $this,'profile_content_forums_default' ) );
		add_action( 'um_profile_content_forumwp_topics', array( $this,'profile_content_forums_topics' ) );
		add_action( 'um_profile_content_forumwp_replies', array( $this,'profile_content_forums_replies' ) );
		add_action( 'um_profile_content_forumwp_subscriptions', array( $this,'profile_content_subscriptions' ) );
		add_action( 'um_profile_content_forumwp_bookmarks', array( $this,'profile_content_bookmarks' ) );
		add_action( 'um_profile_content_forumwp_likes', array( $this,'profile_content_likes' ) );
	}


	/**
	 * Add tabs based on user
	 *
	 * @param array $tabs
	 *
	 * @return array
	 */
	public function check_profile_tab_privacy( $tabs ) {
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
	 * @param string $subnav_link
	 *
	 * @return string
	 */
	public function profile_subnav_link( $subnav_link ) {
		$subnav_link = remove_query_arg( array( 'forumwp_likes', 'forumwp_bookmarks', 'forumwp_subscriptions' ), $subnav_link );

		return $subnav_link;
	}


	/**
	 *
	 */
	public function profile_content_forums_default() {
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
	public function profile_content_forums_topics() {
		if ( version_compare( get_bloginfo('version'),'5.4', '<' ) ) {
			echo do_shortcode( '[fmwp_user_topics user_id="' . um_user('ID') . '" /]' );
		} else {
			echo apply_shortcodes( '[fmwp_user_topics user_id="' . um_user('ID') . '" /]' );
		}
	}


	/**
	 *
	 */
	public function profile_content_forums_replies() {
		if ( version_compare( get_bloginfo('version'),'5.4', '<' ) ) {
			echo do_shortcode( '[fmwp_user_replies user_id="' . um_user('ID') . '" /]' );
		} else {
			echo apply_shortcodes( '[fmwp_user_replies user_id="' . um_user('ID') . '" /]' );
		}
	}


	/**
	 *
	 */
	public function profile_content_subscriptions() {
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
	public function profile_content_bookmarks() {
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


	/**
	 *
	 */
	public function profile_content_likes() {
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
}
