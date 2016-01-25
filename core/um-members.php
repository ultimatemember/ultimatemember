<?php

class UM_Members {

	function __construct() {

		add_filter('user_search_columns', array(&$this, 'user_search_columns'), 99 );

		add_action('template_redirect', array(&$this, 'access_members'), 555);

		$this->core_search_fields = array(
			'user_login',
			'username',
			'display_name',
			'user_email',
		);

	}

	/***
	***	@user_search_columns
	***/
	function user_search_columns( $search_columns ){
		if ( is_admin() ) {
			$search_columns[] = 'display_name';
		} else {
			$search_columns = array('display_name','user_email');
		}
		return $search_columns;
	}

	/***
	***	@Members page allowed?
	***/
	function access_members() {

		if ( um_get_option('members_page') == 0 && um_is_core_page('members') ) {
			um_redirect_home();
		}

	}

	/***
	***	@tag conversion for member directory
	***/
	function convert_tags( $string, $array ) {

		$search = array(
			'{total_users}',
		);

		$replace = array(
			$array['total_users'],
		);

		$string = str_replace($search, $replace, $string);
		return $string;
	}

	/***
	***	@show filter
	***/
	function show_filter( $filter ) {
		global $ultimatemember;

		$fields = $ultimatemember->builtin->all_user_fields;

		if ( isset( $fields[$filter] ) ) {
			$attrs = $fields[$filter];
		} else {
			$attrs = apply_filters("um_custom_search_field_{$filter}", array() );
		}

		// additional filter for search field attributes
		$attrs = apply_filters("um_search_field_{$filter}", $attrs);

		if ( $ultimatemember->builtin->is_dropdown_field( $filter, $attrs ) ) {
			$type = 'select';
		} else if ( 'user_tags' == $attrs['type'] ) {
			$attrs['options'] = apply_filters('um_multiselect_options_user_tags', array(), $attrs);
			$type = 'select';
		} else {
			$type = 'text';
		}

		switch( $type ) {

			case 'select':

				?>

				<select name="<?php echo $filter; ?>" id="<?php echo $filter; ?>" class="um-s1" style="width: 100%" data-placeholder="<?php echo stripslashes( $attrs['label'] ); ?>">

					<option></option>

					<?php foreach( $attrs['options'] as $k => $v ) {

						$v = stripslashes($v);

						$opt = $v;

						if ( strstr($filter, 'role_') )
							$opt = $k;

						if ( isset( $attrs['custom'] ) )
							$opt = $k;

					?>

					<option value="<?php echo $opt; ?>" <?php um_select_if_in_query_params( $filter, $opt ); ?>><?php echo $v; ?></option>

					<?php } ?>

				</select>

				<?php

				break;

			case 'text':

				?>

				<input type="text" name="<?php echo $filter; ?>" id="<?php echo $filter; ?>" placeholder="<?php echo isset( $attrs['label'] ) ? $attrs['label'] : ''; ?>" value="<?php um_queried_search_value( $filter ); ?>" />

				<?php

				break;

		}

	}

	/***
	***	@Generate a loop of results
	***/
	function get_members($args){

		global $ultimatemember;

		extract($args);

		$query_args = array();
		$query_args = apply_filters( 'um_prepare_user_query_args', $query_args, $args );
		$users = new WP_User_Query( $query_args );

		// number of profiles for mobile
		if ( $ultimatemember->mobile->isMobile() && isset( $profiles_per_page_mobile ) )
			$profiles_per_page = $profiles_per_page_mobile;

		$array['users'] = array_unique( $users->results );

		$array['total_users'] = (isset( $max_users ) && $max_users && $max_users <= $users->total_users ) ? $max_users : $users->total_users;

		$array['page'] = isset($_REQUEST['members_page']) ? $_REQUEST['members_page'] : 1;

		$array['total_pages'] = ceil( $array['total_users'] / $profiles_per_page );

		$array['header'] = $this->convert_tags( $header, $array );
		$array['header_single'] = $this->convert_tags( $header_single, $array );

		$array['users_per_page'] = array_slice($array['users'], ( ( $profiles_per_page * $array['page'] ) - $profiles_per_page ), $profiles_per_page );

		for( $i = $array['page']; $i <= $array['page'] + 2; $i++ ) {
			if ( $i <= $array['total_pages'] ) {
				$pages_to_show[] = $i;
			}
		}

		if ( isset( $pages_to_show ) && count( $pages_to_show ) < 5 ) {
			$pages_needed = 5 - count( $pages_to_show );

			for ( $c = $array['page']; $c >= $array['page'] - 2; $c-- ) {
				if ( !in_array( $c, $pages_to_show ) && $c > 0 ) {
					$pages_to_add[] = $c;
				}
			}
		}

		if ( isset( $pages_to_add ) ) {

			asort( $pages_to_add );
			$pages_to_show = array_merge( (array)$pages_to_add, $pages_to_show );

			if ( count( $pages_to_show ) < 5 ) {
				if ( max($pages_to_show) - $array['page'] >= 2 ) {
					$pages_to_show[] = max($pages_to_show) + 1;
					if ( count( $pages_to_show ) < 5 ) {
						$pages_to_show[] = max($pages_to_show) + 1;
					}
				} else if ( $array['page'] - min($pages_to_show) >= 2 ) {
					$pages_to_show[] = min($pages_to_show) - 1;
					if ( count( $pages_to_show ) < 5 ) {
						$pages_to_show[] = min($pages_to_show) - 1;
					}
				}
			}

			asort( $pages_to_show );

			$array['pages_to_show'] = $pages_to_show;

		} else {

			if ( isset( $pages_to_show ) && count( $pages_to_show ) < 5 ) {
				if ( max($pages_to_show) - $array['page'] >= 2 ) {
					$pages_to_show[] = max($pages_to_show) + 1;
					if ( count( $pages_to_show ) < 5 ) {
						$pages_to_show[] = max($pages_to_show) + 1;
					}
				} else if ( $array['page'] - min($pages_to_show) >= 2 ) {
					$pages_to_show[] = min($pages_to_show) - 1;
					if ( count( $pages_to_show ) < 5 ) {
						$pages_to_show[] = min($pages_to_show) - 1;
					}
				}
			}

			if ( isset( $pages_to_show ) && is_array( $pages_to_show ) ) {

				asort( $pages_to_show );

				$array['pages_to_show'] = $pages_to_show;

			}

		}

		if ( isset( $array['pages_to_show'] ) ) {

			if ( $array['total_pages'] < count( $array['pages_to_show'] ) ) {
				foreach( $array['pages_to_show'] as $k => $v ) {
					if ( $v > $array['total_pages'] ) unset( $array['pages_to_show'][$k] );
				}
			}

			foreach( $array['pages_to_show'] as $k => $v ) {
				if ( (int)$v <= 0 ) {
					unset( $array['pages_to_show'][$k] );
				}
			}

		}

		return apply_filters('um_prepare_user_results_array', $array );
	}

}
