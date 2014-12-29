<?php
	
	/***
	***	@prepare the query args to show members
	***/
	add_filter('um_prepare_user_query_args', 'um_prepare_user_query_args', 10, 2);
	function um_prepare_user_query_args($query_args, $args){
		extract( $args );
		
		$query_args['fields'] = 'ID';
		
		$query_args['number'] = 0;
		
		$query_args['meta_query']['relation'] = 'AND';
		
		// must have a profile photo
		if ( $has_profile_photo == 1 ) {
			$query_args['meta_query'][] = array(
				'key' => 'profile_photo',
				'value' => '',
				'compare' => '!='
			);
		}
		
		// add roles to appear in directory 
		if ( !empty( $roles ) ) {
		
			$roles = unserialize( $roles );
			
			$query_args['meta_query'][] = array(
				'key' => 'role',
				'value' => $roles,
				'compare' => 'IN'
			);
		
		}
		
		// sort members by
		$query_args['order'] = 'ASC';
		
		if ( isset( $sortby ) ) {
			
			if ( $sortby == 'other' && $sortby_custom ) {
			
				$query_args['meta_key'] = $sortby_custom;
				$query_args['orderby'] = 'meta_value';
				
			} else if ( in_array( $sortby, array( 'last_name', 'first_name' ) ) ) {
			
				$query_args['meta_key'] = $sortby;
				$query_args['orderby'] = 'meta_value';
				
			} else {
			
				if ( strstr( $sortby, '_desc' ) ) {$sortby = str_replace('_desc','',$sortby);$order = 'DESC';}
				if ( strstr( $sortby, '_asc' ) ) {$sortby = str_replace('_asc','',$sortby);$order = 'ASC';}
				$query_args['orderby'] = $sortby;
			
			}
			
			if ( isset( $order ) ) {
				$query_args['order'] = $order;
			}

		}
		
		return $query_args;
	}
	
	/***
	***	@print out result array
	***/
	add_filter('um_prepare_user_results_array', 'um_prepare_user_results_array', 50, 2);
	function um_prepare_user_results_array($result){
		
		if ( empty( $result['users_per_page'] ) ) {
			$result['no_users'] = 1;
		} else {
			$result['no_users'] = 0;
		}
		
		return $result;
	}
	
	/***
	***	@adding search filters
	***/
	add_filter('um_prepare_user_query_args', 'um_add_search_to_query', 50, 2);
	function um_add_search_to_query($query_args, $args){
		global $ultimatemember;
		extract( $args );
		
		if ( isset( $_REQUEST['um_search'] ) ) {
			
			$query = $ultimatemember->permalinks->get_query_array();

			foreach( $query as $field => $value ) {

				if ( $value && $field != 'um_search' ) {
				
				$query_args['meta_query'][] = array(
					'key' => $field,
					'value' => $value,
					'compare' => '='
				);
				
				}
				
			}

		}
		
		if ( count ($query_args['meta_query']) == 1 ) {
			unset( $query_args['meta_query'] );
		}

		return $query_args;
		
	}