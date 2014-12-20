<?php

class UM_Taxonomies {

	function __construct() {
	
		add_action('init',  array(&$this, 'create_taxonomies'), 1);
	
	}
	
	/***
	***	@Create taxonomies for use for UM
	***/
	function create_taxonomies( $user_id = null ) {
	
		register_post_type( 'um_form', array(
				'labels' => array(
					'name' => __( 'Forms' ),
					'singular_name' => __( 'Form' ),
					'add_new' => __( 'Add New' ),
					'add_new_item' => __('Add New Form' ),
					'edit_item' => __('Edit Form'),
					'not_found' => __('You did not create any forms yet'),
					'not_found_in_trash' => __('Nothing found in Trash'),
					'search_items' => __('Search Forms')
				),
				'show_ui' => true,
				'show_in_menu' => false,
				'public' => false,
				'supports' => array('title')
			)
		);
		
		register_post_type( 'um_role', array(
				'labels' => array(
					'name' => __( 'Member Levels' ),
					'singular_name' => __( 'Member Level' ),
					'add_new' => __( 'Add New' ),
					'add_new_item' => __('Add New Member Level' ),
					'edit_item' => __('Edit Member Level'),
					'not_found' => __('You did not create any member levels yet'),
					'not_found_in_trash' => __('Nothing found in Trash'),
					'search_items' => __('Search Member Levels')
				),
				'show_ui' => true,
				'show_in_menu' => false,
				'public' => false,
				'supports' => array('title')
			)
		);

		register_post_type( 'um_directory', array(
				'labels' => array(
					'name' => __( 'Member Directories' ),
					'singular_name' => __( 'Member Directory' ),
					'add_new' => __( 'Add New' ),
					'add_new_item' => __('Add New Member Directory' ),
					'edit_item' => __('Edit Member Directory'),
					'not_found' => __('You did not create any member directories yet'),
					'not_found_in_trash' => __('Nothing found in Trash'),
					'search_items' => __('Search Member Directories')
				),
				'show_ui' => true,
				'show_in_menu' => false,
				'public' => false,
				'supports' => array('title')
			)
		);
		
	}

}