<?php
namespace umm\forumwp\includes\admin;


if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class Init
 *
 * @package umm\forumwp\includes\admin
 */
class Init {


	/**
	 * Init constructor.
	 */
	function __construct() {
		add_filter( 'um_is_ultimatememeber_admin_screen', array( &$this, 'is_um_screen' ), 10, 1 );

		add_filter( 'um_admin_role_metaboxes', array( &$this, 'add_role_metabox' ), 10, 1 );
		add_filter( 'um_role_meta_map', array( &$this, 'add_role_meta_sanitize' ), 10, 1 );
		add_action( 'add_meta_boxes',  array( &$this, 'add_forum_access_metabox' ), 10 );

		add_action( 'um_admin_custom_restrict_content_metaboxes',  array( &$this, 'save_forum_access_metabox' ), 10, 2 );
	}


	/**
	 * Extends UM admin pages for enqueue scripts
	 *
	 * @param $is_um
	 *
	 * @return bool
	 */
	function is_um_screen( $is_um ) {
		global $current_screen;
		if ( strstr( $current_screen->id, 'fmwp_forum' ) ) {
			$is_um = true;
		}

		return $is_um;
	}


	/**
	 * Creates options in Role page
	 *
	 * @param array $roles_metaboxes
	 *
	 * @return array
	 */
	function add_role_metabox( $roles_metaboxes ) {
		$module_data = UM()->modules()->get_data( 'forumwp' );
		if ( ! $module_data ) {
			return $roles_metaboxes;
		}

		$roles_metaboxes[] = array(
			'id'        => "um-admin-form-forumwp{" . $module_data['path'] . "}",
			'title'     => __( 'ForumWP', 'ultimate-member' ),
			'callback'  => array( UM()->admin()->metabox(), 'load_metabox_role' ),
			'screen'    => 'um_role_meta',
			'context'   => 'normal',
			'priority'  => 'default'
		);

		return $roles_metaboxes;
	}


	/**
	 * @param array $meta_map
	 *
	 * @return array
	 */
	public function add_role_meta_sanitize( $meta_map ) {
		$meta_map = array_merge(
			$meta_map,
			array(
				'_um_disable_forumwp_tab'                => array(
					'sanitize' => 'bool',
				),
				'_um_disable_create_forumwp_topics'      => array(
					'sanitize' => 'bool',
				),
				'_um_lock_create_forumwp_topics_notice'  => array(
					'sanitize' => 'textarea',
				),
				'_um_disable_create_forumwp_replies'     => array(
					'sanitize' => 'bool',
				),
				'_um_lock_create_forumwp_replies_notice' => array(
					'sanitize' => 'textarea',
				),
			)
		);
		return $meta_map;
	}


	/**
	 * Creates UM Permissions metabox for Forum CPT
	 *
	 */
	function add_forum_access_metabox() {
		$module_data = UM()->modules()->get_data( 'forumwp' );
		if ( ! $module_data ) {
			return;
		}

		add_meta_box(
			"um-admin-custom-access/forumwp{" . $module_data['path'] . "}",
			__( 'Ultimate Member: Permissions', 'ultimate-member' ),
			array( UM()->admin()->metabox(), 'load_metabox_custom' ),
			'fmwp_forum',
			'side',
			'low'
		);
	}



	/**
	 * Save postmeta on Forum CPT
	 *
	 * @param bool $post_id
	 * @param bool|\WP_Post $post
	 */
	function save_forum_access_metabox( $post_id = false, $post = false ) {
		if ( empty( $post->post_type ) || 'fmwp_forum' !== $post->post_type ) {
			return;
		}

		$um_fmwp_can_topic = ! empty( $_POST['_um_forumwp_can_topic'] ) ? $_POST['_um_forumwp_can_topic'] : array();
		$um_fmwp_can_reply = ! empty( $_POST['_um_forumwp_can_reply'] ) ? $_POST['_um_forumwp_can_reply'] : array();

		update_post_meta( $post_id, '_um_forumwp_can_topic', array_map( 'sanitize_key', $um_fmwp_can_topic ) );
		update_post_meta( $post_id, '_um_forumwp_can_reply', array_map( 'sanitize_key', $um_fmwp_can_reply ) );
	}
}
