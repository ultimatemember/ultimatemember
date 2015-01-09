<?php
	
	/***
	***	@Members Filter Hooks
	***/
	add_filter('um_prepare_user_query_args', 'um_prepare_user_query_args', 10, 2);
	add_filter('um_prepare_user_query_args', 'um_add_search_to_query', 50, 2);
	add_filter('um_prepare_user_query_args', 'um_remove_unwanted_users_from_list', 99, 2);
	
	/***
	***	@Remove users we do not need to show in directory
	***/
	function um_remove_unwanted_users_from_list( $query_args, $args ) {
		global $ultimatemember;
		extract( $args );
		
		if ( !current_user_can('manage_options') )
		
			$query_args['meta_query'][] = array(
				'key' => 'account_status',
				'value' => 'approved',
				'compare' => '='
			);

		return $query_args;
	}
	
	/***
	***	@adds search parameters
	***/
	function um_add_search_to_query( $query_args, $args ){
		global $ultimatemember;
		extract( $args );
		
		if ( isset( $_REQUEST['um_search'] ) ) {
			
			$query = $ultimatemember->permalinks->get_query_array();

			foreach( $query as $field => $value ) {

				$operator = '=';
				if ( in_array( $ultimatemember->fields->get_field_type( $field ), array('checkbox','multiselect') ) ) {
					$operator = 'LIKE';
				}
				
				if ( $value && $field != 'um_search' ) {
				
					$query_args['meta_query'][] = array(
						'key' => $field,
						'value' => $value,
						'compare' => $operator,
					);
				
				}
				
			}

		}
		
		if ( count ($query_args['meta_query']) == 1 ) {
			unset( $query_args['meta_query'] );
		}

		return $query_args;
		
	}
	
	/***
	***	@adds main parameters
	***/
	function um_prepare_user_query_args($query_args, $args){
		global $ultimatemember;
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
	***	@hook in the member results array
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