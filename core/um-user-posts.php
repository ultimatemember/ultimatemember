<?php

class UM_User_posts {

	function __construct() {

		add_filter('um_profile_tabs', array(&$this, 'add_tab'), 100);
		
		add_action('um_profile_content_posts', array(&$this, 'add_posts') );
		add_action('um_profile_content_comments', array(&$this, 'add_comments') );
		
		add_action('um_ajax_load_posts__um_load_posts', array(&$this, 'load_posts') );
		add_action('um_ajax_load_posts__um_load_comments', array(&$this, 'load_comments') );
		
	}
	
	/***
	***	@dynamic load of posts
	***/
	function load_posts( $args ) {
		global $ultimatemember;
		
		$array = explode(',', $args );
		$post_type = $array[0];
		$posts_per_page = $array[1];
		$offset = $array[2];
		$author = $array[3];
		
		$offset_n = $posts_per_page + $offset;
		$modified_args = "$post_type,$posts_per_page,$offset_n,$author";
		
		$loop = $ultimatemember->query->make("post_type=$post_type&posts_per_page=$posts_per_page&offset=$offset&author=$author");

		include_once um_path . 'templates/profile/posts-single.php';
		
	}
	
	/***
	***	@dynamic load of comments
	***/
	function load_comments( $args ) {
		global $ultimatemember;
		
		$array = explode(',', $args );
		$post_type = $array[0];
		$posts_per_page = $array[1];
		$offset = $array[2];
		$author = $array[3];

		$offset_n = $posts_per_page + $offset;
		$modified_args = "$post_type,$posts_per_page,$offset_n,$author";
		
		$loop = $ultimatemember->query->make("post_type=$post_type&number=$posts_per_page&offset=$offset&author_email=$author");

		include_once um_path . 'templates/profile/comments-single.php';
		
	}
	
	/***
	***	@adds a tab
	***/
	function add_tab( $tabs ){
		
		$tabs['posts'] = array(
			'name' => __('Posts','ultimatemember'),
			'icon' => 'um-faicon-pencil',
			'count' => $this->count_user_posts_by_type(),
		);
		
		$tabs['comments'] = array(
			'name' => __('Comments','ultimatemember'),
			'icon' => 'um-faicon-comment',
			'count' => $this->count_user_comments(),
		);
		
		return $tabs;
	}
	
	/***
	***	@add posts
	***/
	function add_posts() {
		global $ultimatemember;
		
		include_once um_path . 'templates/profile/posts.php';
		
	}
	
	/***
	***	@add comments
	***/
	function add_comments() {
		global $ultimatemember;
		
		include_once um_path . 'templates/profile/comments.php';
		
	}
	
	/***
	***	@count posts
	***/
	function count_user_posts_by_type( $user_id= '', $post_type = 'post' ) {
		global $wpdb;
		if ( !$user_id )
			$user_id = um_user('ID');
		
		$where = get_posts_by_author_sql( $post_type, true, $user_id );
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts $where" );
		
		return apply_filters('um_pretty_number_formatting', $count);
	}
	
	/***
	***	@count comments
	***/
	function count_user_comments( $user_id = null ) {
		global $wpdb;
		if ( !$user_id )
			$user_id = um_user('ID');

		$count = $wpdb->get_var(
		'SELECT COUNT(comment_ID) FROM ' . $wpdb->comments. ' 
		WHERE user_id = ' . $user_id . ' 
		AND comment_approved = "1" 
		AND comment_type IN ("comment", "")'
		);
		
		return apply_filters('um_pretty_number_formatting', $count);
	}

}