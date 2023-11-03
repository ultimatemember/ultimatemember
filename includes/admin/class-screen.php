<?php
namespace um\admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Screen
 *
 * @since 2.7.1
 *
 * @package um\admin
 */
class Screen {

	/**
	 * Boolean check if we're viewing UM backend
	 *
	 * @since 2.7.1
	 *
	 * @return bool
	 */
	public function is_own_screen() {
		global $current_screen;

		$is_um_screen = false;
		if ( ! empty( $current_screen ) && isset( $current_screen->id ) ) {
			$screen_id = $current_screen->id;
			if ( strstr( $screen_id, 'ultimatemember' ) ||
				strstr( $screen_id, 'um_' ) ||
				strstr( $screen_id, 'user' ) ||
				strstr( $screen_id, 'profile' ) ||
				'nav-menus' === $screen_id ) {
				$is_um_screen = true;
			}
		}

		if ( $this->is_own_post_type() ) {
			$is_um_screen = true;
		}

		if ( $this->is_restricted_entity() ) {
			$is_um_screen = true;
		}

		return apply_filters( 'um_is_ultimatememeber_admin_screen', $is_um_screen );
	}

	/**
	 * Check if current page load UM post type.
	 *
	 * @since 2.7.1
	 *
	 * @return bool
	 */
	public function is_own_post_type() {
		$cpt = UM()->common()->cpt()->get_list();

		if ( isset( $_REQUEST['post_type'] ) ) {
			$post_type = sanitize_key( $_REQUEST['post_type'] );
			if ( in_array( $post_type, $cpt, true ) ) {
				return true;
			}
		} elseif ( isset( $_REQUEST['action'] ) && 'edit' === sanitize_key( $_REQUEST['action'] ) ) {
			$post_type = get_post_type();
			if ( in_array( $post_type, $cpt, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * If page now show content with restricted post/taxonomy.
	 *
	 * @since 2.7.1
	 *
	 * @return bool
	 */
	public function is_restricted_entity() {
		$restricted_posts      = UM()->options()->get( 'restricted_access_post_metabox' );
		$restricted_taxonomies = UM()->options()->get( 'restricted_access_taxonomy_metabox' );

		global $typenow, $taxnow;
		if ( ! empty( $typenow ) && ! empty( $restricted_posts[ $typenow ] ) ) {
			return true;
		}

		if ( ! empty( $taxnow ) && ! empty( $restricted_taxonomies[ $taxnow ] ) ) {
			return true;
		}

		return false;
	}
}
