<?php
namespace um\core;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Members' ) ) {
	class Members {

		var $results;

		function __construct() {

			$this->core_search_fields = array(
				'user_login',
				'username',
				'display_name',
				'user_email',
			);

			add_action( 'template_redirect', array( &$this, 'access_members' ), 555 );
			add_action( 'um_pre_directory_shortcode', array( &$this, 'pre_directory_shortcode' ) );

			add_filter( 'um_search_select_fields', array( &$this, 'search_select_fields' ), 10, 1 );
		}


		/**
		 * Get prepared search text for sql request
		 *
		 * @global object $wpdb
		 * @param string $text
		 * @param array $sql_fields
		 * @return string
		 */
		function prepare_search( $text, $sql_fields ) {
			$text = strtolower( trim( $text ) );

			$string = '';
			foreach ( $sql_fields as $field ) {
				if ( ! is_array( $field ) ) {
					$string .= 'LOWER(' . $field . ') LIKE %s OR ';
				} else {
					if ( strpos( $field['meta_key'], '%' ) !== false ) {
						$field['meta_key'] = str_replace( '%', '%%', $field['meta_key'] );
						$string .= '( ' . $field['table'] . '.meta_key LIKE \'' . $field['meta_key'] . '\' AND LOWER(' . $field['meta_value'] . ') LIKE %s ) OR ';
					} else {
						$string .= '( ' . $field['table'] . '.meta_key = \'' . $field['meta_key'] . '\' AND LOWER(' . $field['meta_value'] . ') LIKE %s ) OR ';
					}
				}
			}

			$string = substr( $string, 0, -4 );

            if ( UM()->options()->get('members_page') == 0 && um_is_core_page( 'members' ) ) {
                um_redirect_home();
            }
			if ( empty( $string ) )
				return '';

			global $wpdb;
			return $wpdb->prepare( ' AND ( ' . $string . ' )', array_fill( 0, count( $sql_fields ), '%' . $text . '%' ) );
		}


		/**
		 * Check Members page allowed
		 */
		function access_members() {

			if ( um_get_option( 'members_page' ) == 0 && um_is_core_page( 'members' ) ) {
				um_redirect_home();
			}

		}


		/**
		 * Pre-display Member Directory
		 *
		 * @param $args
		 */
		function pre_directory_shortcode( $args ) {
			wp_localize_script( 'um_members', 'um_members_args', $args );
		}


		/**
		 * Display assigned roles in search filter 'role' field
		 * @param  	array $attrs
		 * @return 	array
		 * @uses  	add_filter 'um_search_select_fields'
		 * @since 	1.3.83
		 */
		function search_select_fields( $attrs ) {
			if ( isset( $attrs['metakey'] ) && strstr( $attrs['metakey'], 'role_' ) ) {

				$shortcode_roles = get_post_meta( UM()->shortcodes()->form_id, '_um_roles', true );
				$um_roles = UM()->roles()->get_roles( false );

				if ( ! empty( $shortcode_roles ) && is_array( $shortcode_roles ) ) {

					$attrs['options'] = array();

					foreach ( $um_roles as $key => $value ) {
						if ( in_array( $key, $shortcode_roles ) ) {
							$attrs['options'][ $key ] = $value;
						}
					}

				}

			}

			return $attrs;
		}


		/**
		 * tag conversion for member directory
		 *
		 * @param $string
		 * @param $array
		 * @return mixed
		 */
		function convert_tags( $string, $array ) {

			$search = array(
				'{total_users}',
			);

			$replace = array(
				$array['total_users'],
			);

			return str_replace( $search, $replace, $string );
		}


		/**
		 * Render member's directory
		 * filters selectboxes
		 *
		 * @param $filter
		 */
		function show_filter( $filter ) {
			$fields = UM()->builtin()->all_user_fields;

			if ( isset( $fields[ $filter ] ) ) {
				$attrs = $fields[ $filter ];
			} else {
				$attrs = apply_filters( "um_custom_search_field_{$filter}", array() );
			}

			// additional filter for search field attributes
			$attrs = apply_filters( "um_search_field_{$filter}", $attrs );

			//$type = UM()->builtin()->is_dropdown_field( $filter, $attrs ) ? 'select' : 'text';
			//$type = UM()->builtin()->is_dropdown_field( $filter, $attrs ) ? 'radio' : 'text';
			//$type = apply_filters( 'um_search_field_type', $type, $attrs );

			// filter all search fields
			$attrs = apply_filters( 'um_search_fields', $attrs );

			//if ( $type == 'select' )
			$attrs = apply_filters( 'um_search_select_fields', $attrs );

			if ( $filter == 'age' ) {

				$this->show_slider( $filter );

			} else { ?>

				<select name="<?php echo $filter; ?>" id="<?php echo $filter; ?>" class="um-s1" style="width: 100%" data-placeholder="<?php echo __( stripslashes( $attrs['label'] ), 'ultimate-member' ); ?>" <?php if ( ! empty( $attrs['custom_dropdown_options_source'] ) ) { ?> data-um-ajax-source="<?php echo $attrs['custom_dropdown_options_source'] ?>"<?php } ?>>

					<option></option>

					<?php foreach ( $attrs['options'] as $k => $v ) {

						$v = stripslashes( $v );

						$opt = $v;

						if ( strstr( $filter, 'role_' ) )
							$opt = $k;

						if ( isset( $attrs['custom'] ) )
							$opt = $k; ?>

						<option value="<?php echo $opt; ?>" data-value_label="<?php echo __( $v, 'ultimate-member'); ?>"><?php echo __( $v, 'ultimate-member'); ?></option>

					<?php } ?>

				</select>

			<?php }
		}


		function borndate( $borndate ) {
			if ( date('m', $borndate) > date('m') || date('m', $borndate) == date('m') && date('d', $borndate ) > date('d')) {
				return (date('Y') - date('Y', $borndate ) - 1);
			}
			return (date('Y') - date('Y', $borndate));
		}

		function show_slider( $filter ) {

			global $wpdb;
			$meta = $wpdb->get_col( "SELECT meta_value FROM {$wpdb->usermeta} WHERE meta_key='birth_date' ORDER BY meta_value DESC" );

			if ( ! empty( $meta ) ) {
				$range = array( $this->borndate( strtotime( $meta[0] ) ), $this->borndate( strtotime( $meta[ count( $meta ) - 1 ] ) ) );
			} else {
				$range = array( 0, 100 );
			}

			$range = apply_filters( 'um_member_directory_filter_slider', $range ); ?>

			<div class="um-slider" data-field_name="birth_date" data-min="<?php echo $range[0] ?>" data-max="<?php echo $range[1] ?>" style="float: left;width:100%;"></div>
			<div class="um-slider-range" style="float:left;width:100%;text-align: left;padding-top: 5px;box-sizing: border-box;"></div>
			<input type="hidden" name="birth_date[]" class="um_range_min" />
			<input type="hidden" name="birth_date[]" class="um_range_max" />

		<?php
		}


		/**
		 * Generate a loop of results
		 *
		 * @param $args
		 * @return mixed|void
		 */
		function get_members( $args ) {
			global $wpdb;

			extract( $args );

			$query_args = apply_filters( 'um_prepare_user_query_args', array(), $args );

			// Prepare for BIG SELECT query
			$wpdb->query( 'SET SQL_BIG_SELECTS=1' );

			// number of profiles for mobile
			$profiles_per_page = $args['profiles_per_page'];
			if ( UM()->mobile()->isMobile() && isset( $args['profiles_per_page_mobile'] ) ) {
				$profiles_per_page = $args['profiles_per_page_mobile'];
			}

			$query_args['number'] = isset( $args['number'] ) ? $args['number'] : $profiles_per_page;

			$current_page = isset( $args['page'] ) ? $args['page'] : 1;

			$query_args['paged'] = $current_page;

			if ( ! um_user( 'can_view_all' ) && is_user_logged_in() ) {
				$query_args = array();
			}

			do_action( 'um_user_before_query', $query_args );

			add_filter( 'get_meta_sql', array( &$this, 'change_meta_sql' ), 10 );

			$users = new \WP_User_Query( $query_args );

			remove_filter( 'get_meta_sql', array( &$this, 'change_meta_sql' ), 10 );

			do_action( 'um_user_after_query', $query_args, $users );

			$user_ids = ! empty( $users->results ) ? array_unique( $users->results ) : array();
			$total_users = ( ! empty( $max_users ) && $max_users <= $users->total_users ) ? $max_users : $users->total_users;
			$total_pages = ceil( $total_users / $profiles_per_page );

			$index1 = 0 - ( $current_page - 2 ) + 1;
			$to = $current_page + 2;
			if ( $index1 > 0 ) {
				$to += $index1;
			}

			$index2 = $total_pages - ( $current_page + 2 );
			$from = $current_page - 2;
			if ( $index2 < 0 ) {
				$from += $index2;
			}

			$pages_to_show = range(
				( $from > 0 ) ? $from : 1,
				( $to <= $total_pages ) ? $to : $total_pages
			);

			$response = array(
				'users'         => $user_ids,
				'total_users'   => $total_users,
				'total_pages'   => $total_pages,
				'page'          => $current_page,
				'no_users'      => empty( $user_ids ) ? 1 : 0,
				'pages_to_show' => ! empty( $pages_to_show ) ? array_values( $pages_to_show ) : array()
			);

			$response['header'] = $this->convert_tags( $header, $response );
			$response['header_single'] = $this->convert_tags( $header_single, $response );

			return apply_filters( 'um_prepare_user_results_array', $response );
		}


		/**
		 * Change mySQL meta query join attribute
		 * for search only by UM user meta fields
		 *
		 * @param array $sql Array containing the query's JOIN and WHERE clauses.
		 * @return mixed
		 */
		function change_meta_sql( $sql ) {

			if ( ! empty( $_POST['general_search'] ) ) {
				global $wpdb;

				preg_match(
					'/^(.*).meta_value LIKE \'%' . esc_attr( $_POST['general_search'] ) . '%\' [^\)]/im',
					$sql['where'],
					$join_matches
				);

				$meta_join_for_search = trim( $join_matches[1] );

				$sql['join'] = preg_replace(
					'/(' . $meta_join_for_search . ' ON \( ' . $wpdb->users . '\.ID = ' . $meta_join_for_search . '\.user_id )(\))/im',
					"$1 AND " . $meta_join_for_search . ".meta_key IN( '" . implode( "','", array_keys( UM()->builtin()->all_user_fields ) ) . "' ) $2",
					$sql['join']
				);
			}

			return $sql;
		}


		/**
		 * maybe deprecated
		 *
		 *
		 * Optimizes Member directory with multiple LEFT JOINs
		 * @param  object $vars
		 * @return object $var
		 */
		/*public function um_optimize_member_query( $vars ) {

            global $wpdb;

            $arr_where = explode("\n", $vars->query_where );
            $arr_left_join = explode("LEFT JOIN", $vars->query_from );
            $arr_user_photo_key = array('synced_profile_photo','profile_photo','synced_gravatar_hashed_id');

            foreach ( $arr_where as $where ) {

                foreach( $arr_user_photo_key as $key ){

                    if( strpos( $where  , "'".$key."'" ) > -1 ){

                        // find usermeta key
                        preg_match("#mt[0-9]+.#",  $where, $meta_key );

                        // remove period from found meta_key
                        $meta_key = str_replace(".","", current( $meta_key ) );

                        // remove matched LEFT JOIN clause
                        $vars->query_from = str_replace('LEFT JOIN wp_usermeta AS '.$meta_key.' ON ( wp_users.ID = '.$meta_key.'.user_id )', '',  $vars->query_from );

                        // prepare EXISTS replacement for LEFT JOIN clauses
                        $where_exists = 'um_exist EXISTS( SELECT '.$wpdb->usermeta.'.umeta_id FROM '.$wpdb->usermeta.' WHERE '.$wpdb->usermeta.'.user_id = '.$wpdb->users.'.ID AND '.$wpdb->usermeta.'.meta_key IN("'.implode('","',  $arr_user_photo_key ).'") AND '.$wpdb->usermeta.'.meta_value != "" )';

                        // Replace LEFT JOIN clauses with EXISTS and remove duplicates
                        if( strpos( $vars->query_where, 'um_exist' ) === FALSE ){
                            $vars->query_where = str_replace( $where , $where_exists,  $vars->query_where );
                        }else{
                            $vars->query_where = str_replace( $where , '1=0',  $vars->query_where );
                        }
                    }

                }

            }

            $vars->query_where = str_replace("\n", "", $vars->query_where );
            $vars->query_where = str_replace("um_exist", "", $vars->query_where );

            return $vars;

        }*/


		/**
		 * get AJAX results members
		 */
		function ajax_get_members() {
			$args = ! empty( $_POST['args'] ) ? $_POST['args'] : array();
			$args['page'] = ! empty( $_POST['page'] ) ? $_POST['page'] : ( isset( $args['page'] ) ? $args['page'] : 1 );

			$sizes = um_get_option( 'cover_thumb_sizes' );
			$cover_size = UM()->mobile()->isTablet() ? $sizes[1] : $sizes[0];

			$users = $this->get_members( $args );

			$users_data = array();
			foreach ( $users['users'] as $user_id ) {
				um_fetch_user( $user_id );

				$data_array = array(
					'id'                    => $user_id,
					'role'                  => um_user('role'),
					'account_status'        => um_user('account_status'),
					'account_status_name'   => um_user('account_status_name'),
					'cover_photo'           => um_user('cover_photo', $cover_size),
					'display_name'          => um_user('display_name'),
					'profile_url'           => um_user_profile_url(),
					'can_edit'              => ( UM()->roles()->um_current_user_can( 'edit', $user_id ) || UM()->roles()->um_user_can( 'can_edit_everyone' ) ) ? true : false,
					'edit_profile_url'      => um_edit_profile_url(),
					'avatar'                => get_avatar( $user_id, str_replace( 'px', '', um_get_option( 'profile_photosize' ) ) ),
					'display_name_html'     => um_user('display_name', 'html'),
					'social_urls'           => UM()->fields()->show_social_urls( false ),
				);

				if ( $args['show_tagline'] && is_array( $args['tagline_fields'] ) ) {
					foreach ( $args['tagline_fields'] as $key ) {
						if ( $key && um_filtered_value( $key ) ) {
							$data_array[$key] = um_filtered_value( $key );
						}
					}
				}

				if ( $args['show_userinfo'] ) {
					foreach ( $args['reveal_fields'] as $key ) {
						if ( $key && um_filtered_value( $key ) ) {
							$data_array["label_{$key}"] = UM()->fields()->get_label( $key );
							$data_array[$key] = um_filtered_value( $key );
						}
					}
				}

				$data_array = apply_filters( 'um_ajax_get_members_data', $data_array, $user_id );

				$users_data[] = $data_array;

				um_reset_user_clean();
			}

			um_reset_user();


			$pagination_data = array(
				'pages_to_show' => $users['pages_to_show'],
				'current_page'  => $args['page'],
				'total_pages'   => $users['total_pages'],
				'header_single' => $users['header_single'],
				'header'        => $users['header'],
			);

			wp_send_json_success( array( 'users' => $users_data, 'pagi' => $pagination_data ) );
		}


		function get_sorting_fields() {

			return apply_filters( 'um_members_directory_sort_dropdown_options', array(
				'user_registered_desc'	=> __( 'Newest Members', 'ultimate-member' ),
				'user_registered_asc'	=> __( 'Oldest Members', 'ultimate-member' ),
				'username_asc'			=> __( 'Username', 'ultimate-member' ),
				'first_name'			=> __( 'First Name', 'ultimate-member' ),
				'last_name'				=> __( 'Last Name', 'ultimate-member' ),
			) );

		}


		function get_filters_fields() {

			return apply_filters( 'um_members_directory_filter_dropdown_options', array(
				'country'       => __( 'Country', 'ultimate-member' ),
				'gender'        => __( 'Gender', 'ultimate-member' ),
				'languages'     => __( 'Languages', 'ultimate-member' ),
				'role'          => __( 'Roles', 'ultimate-member' ),
				'age'           => __( 'Age', 'ultimate-member' ),
				'mycred_rank'   => __( 'myCRED Rank', 'ultimate-member' ),
			) );

		}

	}
}