<?php

class UM_Query {

	function __construct() {
	
	}
	
	/***
	***	@get user's role
	***/
	function get_role_by_userid( $user_id ) {
		$role = get_user_meta( $user_id, 'role', true );
		return $role;
	}
	
	/***
	***	@Count all users
	***/
	function count_users(){
		$result = count_users();
		return $result['total_users'];
	}
	
	/***
	***	@Count users of specific role
	***/
	function count_users_by_role($role){
		global $wpdb;
		
		$args['fields'] = 'ID';
		$args['meta_query'] = array(
			array(
				'key'     => 'role',
				'value'   => $role,
				'compare' => '='
			)
		);
		
		$users = new WP_User_Query( $args );
		
		return count($users->results);
	}
	
	/***
	***	@Using wpdb instead of update_post_meta
	***/
	function update_attr( $key, $post_id, $new_value ){
		update_post_meta( $post_id, '_um_' . $key, $new_value );
	}
	
	/***
	***	@get data
	***/
	function get_attr( $key, $post_id ){
		$meta = get_post_meta( $post_id, '_um_' . $key, true );
		return $meta;
	}
	
	/***
	***	@delete data
	***/
	function delete_attr( $key, $post_id ){
		$meta = delete_post_meta( $post_id, '_um_' . $key );
		return $meta;
	}
	
	/***
	***	@Checks if post has a specific meta key
	***/
	function has_post_meta($key, $value=null, $post_id=null ){
		if (!$post_id){
			global $post;
			$post_id = $post->ID;
		}
		if ($value ){
			if ( get_post_meta($post_id, $key, true) == $value )
				return true;
		} else {
			if ( get_post_meta($post_id, $key, true) )
				return true;
		}
		return false;
	}
	
	/***
	***	@Get posts with specific meta key/value
	***/
	function find_post_id($post_type, $key, $value){
		$posts = get_posts( array( 'post_type' => $post_type, 'meta_key' => $key, 'meta_value' => $value ) );
		if ( isset($posts[0]) && !empty($posts) )
			return $posts[0]->ID;
		return false;
	}
	
	/***
	***	@Get role data
	***/
	function role_data( $role_slug ) {
		global $wpdb, $ultimatemember;

		if ($role_slug == 'admin' || $role_slug == 'member'){
			$try = $this->find_post_id('um_role','_um_core',$role_slug);
			if ( isset( $try ) ){
				$post_id = $try;
				$real_role_slug =  $role_slug;
			}
		} else {
			$post_id = $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_status = 'publish' AND post_name = '$role_slug'");
			$real_role_slug = $role_slug;
		}
		
		if ( isset($post_id) && $post_id != '' ){
			$meta = get_post_custom( $post_id );
			$array['role'] = $real_role_slug;
			$array['role_name'] = get_the_title( $post_id );
			foreach ($meta as $k => $v){
				if ( strstr($k, '_um_') ) {
					$k = str_replace('_um_', '', $k);
					$array[$k] = $v[0];
				}
				
			}
		} else {
			
			// no permissions, something wrong
			$array = $ultimatemember->setup->get_initial_permissions( $role_slug );

		}
		return $array;
	}
	
	/***
	***	@Get post data
	***/
	function post_data( $post_id ) {
		$array['form_id'] = $post_id;
		$mode = $this->get_attr('mode', $post_id);
		$meta = get_post_custom( $post_id );
		foreach ($meta as $k => $v){
			if ( strstr($k, '_um_'.$mode.'_' ) ) {
				$k = str_replace('_um_'.$mode.'_', '', $k);
				$array[$k] = $v[0];
			} elseif ($k == '_um_mode'){
				$k = str_replace('_um_', '', $k);
				$array[$k] = $v[0];
			} elseif ( strstr($k, '_um_') ) {
				$k = str_replace('_um_', '', $k);
				$array[$k] = $v[0];
			}
			
		}
		
		foreach( $array as $k => $v ) {
			if ( strstr( $k, 'login_') || strstr( $k, 'register_' ) || strstr( $k, 'profile_' ) ){
				if ( $mode != 'directory' ) {
					unset($array[$k]);
				}
			}
		}
		return $array;
	}
	
	/***
	***	@Counts all user posts
	***/
	function count_posts($user_id){
		$args = array(
			'author'      	=> $user_id,
			'post_status'	=> array('publish'),
			'post_type' 	=> 'any'
		);
		$posts = new WP_Query( $args );
		$post_count = $posts->found_posts;
		return $post_count;
	}
	
	/***
	***	@Count comments by user
	***/
	function count_comments( $user_id ) {
		$args = array(
			'user_id' => $user_id
		);
		$comments = get_comments( $args );
		return count($comments);
	}
	
	/***
	***	@Capture selected value
	***/
	function get_meta_value($key, $array_key=null, $fallback = null){
		global $post;
		$post_id = get_the_ID();
		$try = get_post_meta( $post_id, $key, true);
		
		if (isset($try) && !empty($try))
			if (is_array($try) && in_array($array_key, $try) ){
			return $array_key;
			} else if ( is_array( $try ) ) {
			return '';
			} else {
			return $try;
			}
			
		if ($fallback == 'na') {
			$fallback = 0;
			$none = '';
		} else {
			$none = 0;
		}
		return (!empty($fallback)) ? $fallback : $none;
	}
	
	/***
	***	@Is a core post/role
	***/
	function is_core( $post_id ){
		$is_core = get_post_meta($post_id, '_um_core', true);
		if ( $is_core != '' ) {
			return $is_core;
		} else {
			return false;
		}
	}
	
	/***
	***	@Query for UM roles
	***/
	function get_roles( $add_default = false, $exclude = null ){
	
		$roles = array();
		
		if ($add_default) $roles[0] = $add_default;
		
		$args = array(
			'post_type' => 'um_role',
			'posts_per_page' => -1,
			'post_status' => array('publish')
		);
		$results = new WP_Query($args);
		if ($results->posts){
			foreach($results->posts as $post) { setup_postdata($post);
			
				if ( $this->is_core( $post->ID ) ){
					$roles[ $this->is_core( $post->ID ) ] = $post->post_title;
				} else {
					$roles[ $post->post_name ] = $post->post_title;
				}
				
			}
		} else {
		
			$roles['member'] = 'Member';
			$roles['admin'] = 'Admin';
			
		}
		
		if ( $exclude ) {
			foreach( $exclude as $role ) {
				unset($roles[$role]);
			}
		}
	
		return $roles;
		
	}
	
	/***
	***	@Number of users registered on specific day
	***/
	function count_users_registered_on( $date='' ){
		global $wpdb;

		// defaults to today
		if( empty($date) )
			$date = date('Y-m-d');

		$morning = new DateTime($date. ' 00:00:00');
		$night = new DateTime($date.' 23:59:59'); 
		$m = $morning->format('Y-m-d H:i:s');
		$n = $night->format('Y-m-d H:i:s');

		$sql = $wpdb->prepare("SELECT wp_users.* FROM wp_users WHERE 1=1 AND CAST(user_registered AS DATE) BETWEEN %s AND %s ORDER BY user_login ASC",$m,$n);

		$users = $wpdb->get_results($sql);

		return $wpdb->num_rows;
	}
	
	/***
	***	@Get deleted users count for last x days
	***/
	function deleted_users_per_day( $limit = 30 ){
		$option = get_option('um_deleted_users');
		if (is_array($option)){
			$slice = array_slice($option, 0, $limit);
			$reverse = array_reverse($slice);
			return $reverse;
		} else {
			return null;
		}
	}

}