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
					'add_new' => __( 'Add New Form' ),
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
					'name' => __( 'Roles' ),
					'singular_name' => __( 'Role' ),
					'add_new' => __( 'Add New Role' ),
					'add_new_item' => __('Add New Role' ),
					'edit_item' => __('Edit Role'),
					'not_found' => __('You did not create any roles yet'),
					'not_found_in_trash' => __('Nothing found in Trash'),
					'search_items' => __('Search Roles')
				),
				'show_ui' => true,
				'show_in_menu' => false,
				'public' => false,
				'supports' => array('title')
			)
		);

		register_post_type( 'um_directory', array(
				'labels' => array(
					'name' => __( 'Directories' ),
					'singular_name' => __( 'Directory' ),
					'add_new' => __( 'Add New Directory' ),
					'add_new_item' => __('Add New Directory' ),
					'edit_item' => __('Edit Directory'),
					'not_found' => __('You did not create any member directories yet'),
					'not_found_in_trash' => __('Nothing found in Trash'),
					'search_items' => __('Search Directories')
				),
				'show_ui' => true,
				'show_in_menu' => false,
				'public' => false,
				'supports' => array('title')
			)
		);
		
	}

}