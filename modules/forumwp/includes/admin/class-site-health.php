<?php
namespace umm\forumwp\includes\admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'umm\forumwp\includes\admin\Site_Health' ) ) {

	/**
	 * Class Site_Health
	 *
	 * @package um\admin
	 */
	class Site_Health {

		/**
		 * Site_Health constructor.
		 */
		public function __construct() {
			add_filter( 'um_debug_information_user_role', array( $this, 'um_debug_information_user_role' ), 20, 2 );
		}

		/**
		 * Extend user role info.
		 *
		 * @since 3.0
		 *
		 * @param array $info The Site Health information.
		 *
		 * @return array The updated Site Health information.
		 */
		public function um_debug_information_user_role( $info, $key ) {
			$rolemeta = get_option( "um_role_{$key}_meta", false );

			$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
				$info[ 'ultimate-member-' . $key ]['fields'],
				array(
					'um-disable_forumwp_tab' => array(
						'label' => __( 'ForumWP - Disable forums tab?', 'ultimate-member' ),
						'value' => ! empty( $rolemeta['_um_disable_forumwp_tab'] ) ? __( 'Yes', 'ultimate-member' ) : __( 'No', 'ultimate-member' ),
					),
					'um-disable_create_forumwp_topics' => array(
						'label' => __( 'ForumWP - Disable create new topics?', 'ultimate-member' ),
						'value' => ! empty( $rolemeta['_um_disable_create_forumwp_topics'] ) ? __( 'Yes', 'ultimate-member' ) : __( 'No', 'ultimate-member' ),
					),
				)
			);

			if ( isset( $rolemeta['_um_disable_create_forumwp_topics'] ) && 1 == $rolemeta['_um_disable_create_forumwp_topics'] ) {
				$lock_create_forumwp_topics_notice = '';
				if ( isset( $rolemeta['_um_lock_create_forumwp_topics_notice'] ) ) {
					$lock_create_forumwp_topics_notice = stripslashes( $rolemeta['_um_lock_create_forumwp_topics_notice'] );
				}
				$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
					$info[ 'ultimate-member-' . $key ]['fields'],
					array(
						'um-lock_create_forumwp_topics_notice' => array(
							'label' => __( 'ForumWP - Custom message to show if you force locking new topic', 'ultimate-member' ),
							'value' => $lock_create_forumwp_topics_notice,
						),
					)
				);
			}

			$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
				$info[ 'ultimate-member-' . $key ]['fields'],
				array(
					'um-disable_create_forumwp_replies' => array(
						'label' => __( 'ForumWP - Disable create new replies?', 'ultimate-member' ),
						'value' => ! empty( $rolemeta['_um_disable_create_forumwp_replies'] ) ? __( 'Yes', 'ultimate-member' ) : __( 'No', 'ultimate-member' ),
					),
				)
			);

			if ( isset( $rolemeta['_um_disable_create_forumwp_replies'] ) && 1 == $rolemeta['_um_disable_create_forumwp_replies'] ) {
				$lock_create_forumwp_replies_notice = '';
				if ( isset( $rolemeta['_um_lock_create_forumwp_topics_notice'] ) ) {
					$lock_create_forumwp_replies_notice = stripslashes( $rolemeta['_um_lock_create_forumwp_replies_notice'] );
				}
				$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
					$info[ 'ultimate-member-' . $key ]['fields'],
					array(
						'um-lock_create_forumwp_replies_notice' => array(
							'label' => __( 'ForumWP - Custom message to show if you force locking new reply', 'ultimate-member' ),
							'value' => $lock_create_forumwp_replies_notice,
						),
					)
				);
			}

			return $info;
		}
	}
}
