<?php
namespace umm\member_directory\includes\common;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class CPT
 *
 * @package umm\member_directory\includes\common
 */
class CPT {


	/**
	 * CPT constructor.
	 */
	public function __construct() {
	}


	/**
	 *
	 */
	public function hooks() {
		add_action( 'init', array( &$this, 'create_post_types' ), 1 );
		add_filter( 'um_cpt_list', array( &$this, 'add_um_cpt' ), 10, 1 );
	}


	/**
	 * Create UM's CPT
	 */
	public function create_post_types() {
		register_post_type(
			'um_directory',
			array(
				'labels'       => array(
					'name'                     => __( 'Member Directories', 'ultimate-member' ),
					'singular_name'            => __( 'Member Directory', 'ultimate-member' ),
					'add_new'                  => __( 'Add New', 'ultimate-member' ),
					'add_new_item'             => __( 'Add New Member Directory', 'ultimate-member' ),
					'edit_item'                => __( 'Edit Member Directory', 'ultimate-member' ),
					'not_found'                => __( 'You did not create any member directories yet', 'ultimate-member' ),
					'not_found_in_trash'       => __( 'Nothing member directories found in Trash', 'ultimate-member' ),
					'search_items'             => __( 'Search Member Directories', 'ultimate-member' ),
					'new_item'                 => __( 'New Member Directory', 'ultimate-member' ),
					'view_item'                => __( 'View Member Directory', 'ultimate-member' ),
					'insert_into_item'         => __( 'Insert into Member Directory', 'ultimate-member' ),
					'uploaded_to_this_item'    => __( 'Uploaded to this Member Directory', 'ultimate-member' ),
					'filter_items_list'        => __( 'Filter Member Directories List', 'ultimate-member' ),
					'items_list_navigation'    => __( 'Member Directories List Navigation', 'ultimate-member' ),
					'items_list'               => __( 'Member Directories List', 'ultimate-member' ),
					'view_items'               => __( 'View Member Directories', 'ultimate-member' ),
					'attributes'               => __( 'Member Directory Attributes', 'ultimate-member' ),
					'item_updated'             => __( 'Member Directory updated.', 'ultimate-member' ),
					'item_published'           => __( 'Member Directory created.', 'ultimate-member' ),
					'item_published_privately' => __( 'Member Directory created privately.', 'ultimate-member' ),
					'item_reverted_to_draft'   => __( 'Member Directory reverted to draft.', 'ultimate-member' ),
					'item_scheduled'           => __( 'Member Directory scheduled.', 'ultimate-member' ),
				),
				'capabilities' => array(
					'edit_post'          => 'manage_options',
					'read_post'          => 'manage_options',
					'delete_post'        => 'manage_options',
					'edit_posts'         => 'manage_options',
					'edit_others_posts'  => 'manage_options',
					'delete_posts'       => 'manage_options',
					'publish_posts'      => 'manage_options',
					'read_private_posts' => 'manage_options',
				),
				'show_ui'      => true,
				'show_in_menu' => false,
				'public'       => false,
				'show_in_rest' => true,
				'supports'     => array( 'title' ),
			)
		);
	}


	/**
	 * @param array $cpt
	 *
	 * @return array
	 */
	public function add_um_cpt( $cpt ) {
		$cpt[] = 'um_directory';
		return $cpt;
	}
}
