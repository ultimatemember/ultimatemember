<?php

	/***
	***	@Search results
	***/
	add_action('pre_get_posts','um_pre_get_posts');
	function um_pre_get_posts($query) {

		if ( !is_admin() && $query->is_main_query() ) {

			if ( $query->is_search || $query->is_archive() || $query->is_home ) {
				
				if ( $query->is_home && !um_get_option('exclude_from_main_loop' ) ) return;
				if ( $query->is_archive && !um_get_option('exclude_from_archive_loop' ) ) return;
				if ( $query->is_search && !um_get_option('exclude_from_search_loop' ) ) return;
				
				if ( is_user_logged_in() ) {

					$meta_query['relation'] = 'OR';
					$meta_query[] = array(
									'key'=>'_um_accessible',
									'value'=>'1',
									'compare'=>'!=',
					);
					$meta_query[] = array(
									'key'=>'_um_accessible',
									'compare'=>'NOT EXISTS',
					);
					$query->set('meta_query',$meta_query);

				}
				
				if ( !is_user_logged_in() ) {

					$meta_query['relation'] = 'OR';
					$meta_query[] = array(
									'key'=>'_um_accessible',
									'value'=>'2',
									'compare'=>'!=',
					);
					$meta_query[] = array(
									'key'=>'_um_accessible',
									'compare'=>'NOT EXISTS',
					);
					$query->set('meta_query',$meta_query);

				}
				
			}

		}

	}