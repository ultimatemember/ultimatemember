<?php

	/***
	***	@prevent moving core posts to trash
	***/
	add_action('wp_trash_post','um_core_posts_delete');
	function um_core_posts_delete($post_id){
		global $ultimatemember;
		if ( $ultimatemember->query->is_core($post_id) ) {
			wp_die('This is a core functionality of Ultimate Member and cannot be deleted!');
		}
		
	}