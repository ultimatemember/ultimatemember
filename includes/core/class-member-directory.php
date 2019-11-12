<?php
namespace um\core;


if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'um\core\Member_Directory' ) ) {


	/**
	 * Class Member_Directory
	 * @package um\core
	 */
	class Member_Directory {


		/**
		 * Member Directory Views
		 *
		 * @var array
		 */
		var $view_types = array();


		/**
		 * @var array
		 */
		var $sort_fields = array();


		/**
		 * @var array
		 */
		var $default_sorting = array();


		/**
		 * @var array
		 */
		var $filter_fields = array();


		/**
		 * @var array
		 */
		var $custom_filters_in_query = array();



		var $filter_supported_fields = array();


		var $filter_types = array(
//			'select',
//			'slider',
//			'datepicker',
//			'timepicker',
		);


		var $core_search_fields = array(
			'user_login',
			'user_url',
			'display_name',
			'user_email',
			'user_nicename',
		);


		/**
		 * @var
		 */
		var $query_args;


		/**
		 * @var User Card cover size
		 */
		var $cover_size;


		/**
		 * @var User Avatar size
		 */
		var $avatar_size;


		/**
		 * Member_Directory constructor.
		 */
		function __construct() {
			add_filter( 'plugins_loaded', array( &$this, 'init_variables' ), 99999 );
			add_filter( 'init', array( &$this, 'init_filter_types' ), 2 );

			add_action( 'template_redirect', array( &$this, 'access_members' ), 555 );
		}


		/**
		 * Getting member directory post ID via hash
		 * Hash is unique attr, which we use visible at frontend
		 *
		 * @param string $hash
		 *
		 * @return bool|int
		 */
		function get_directory_by_hash( $hash ) {
			global $wpdb;

			$directory_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE SUBSTRING( MD5( ID ), 11, 5 ) = %s", $hash ) );

			if ( empty( $directory_id ) ) {
				return false;
			}

			return (int) $directory_id;
		}


		/**
		 * @param $id
		 *
		 * @return bool|string
		 */
		function get_directory_hash( $id ) {
			$hash = substr( md5( $id ), 10, 5 );
			return $hash;
		}


		/**
		 * Get view Type template
		 * @param string $type
		 *
		 * @return string
		 */
		function get_type_basename( $type ) {
			return apply_filters( "um_member_directory_{$type}_type_template_basename", '' );
		}


		/**
		 * Tag conversion for member directory
		 *
		 * @param string $string
		 * @param array $array
		 *
		 * @return string
		 */
		function convert_tags( $string, $array ) {

			$search = array(
				'{total_users}',
			);

			$replace = array(
				$array['total_users'],
			);

			$string = str_replace( $search, $replace, $string );
			return $string;
		}


		/**
		 * Members page allowed?
		 *
		 * can be disabled by "Enable Members Directory" option
		 *
		 */
		function access_members() {
			if ( UM()->options()->get( 'members_page' ) == 0 && um_is_core_page( 'members' ) ) {
				um_redirect_home();
			}
		}


		/**
		 * @param $value
		 * @param $key
		 * @param $post_id
		 *
		 * @return array
		 */
		function before_save_data( $value, $key, $post_id ) {

			$post = get_post( $post_id );

			if ( $post->post_type == 'um_directory' ) {

				if ( ! empty( $value ) && in_array( $key, array( '_um_view_types', '_um_roles', '_um_roles_can_search', '_um_roles_can_filter' ) ) ) {
					$value = array_keys( $value );
				} elseif ( $key == '_um_search_filters' ) {

					$temp_value = array();

					if ( ! empty( $value ) ) {
						foreach ( $value as $k ) {
							$filter_type = $this->filter_types[ $k ];
							if ( ! empty( $filter_type  ) ) {
								if ( $filter_type == 'select' ) {
									if ( ! empty( $_POST[ $k ] ) ) {
										$temp_value[ $k ] = trim( $_POST[ $k ] );
									}
								} elseif ( $filter_type == 'slider' ) {
									if ( ! empty( $_POST[ $k ] ) ) {
										$temp_value[ $k ] = $_POST[ $k ];
									}
								} elseif ( $filter_type == 'timepicker' || $filter_type == 'datepicker' ) {
									if ( ! empty( $_POST[ $k . '_from' ] ) && ! empty( $_POST[ $k . '_to' ] ) ) {
										$temp_value[ $k ] = array( $_POST[ $k . '_from' ], $_POST[ $k . '_to' ] );
									}
								}
							}
						}
					}

					$value = $temp_value;
				}
			}

			return $value;
		}


		/**
		 *
		 */
		function init_variables() {

			// Types
			$this->view_types = apply_filters( 'um_member_directory_views', array(
				'grid'  => array( 'title' => __( 'Grid', 'ultimate-member' ), 'icon' => 'um-faicon-th' ),
				'list'  => array( 'title' => __( 'List', 'ultimate-member' ), 'icon' => 'um-faicon-list' ),
			) );

			// Sort
			$this->sort_fields = apply_filters( 'um_members_directory_sort_fields', array(
				'user_registered_desc'  => __( 'New users first', 'ultimate-member' ),
				'user_registered_asc'   => __( 'Old users first', 'ultimate-member' ),
				'username'              => __( 'Username', 'ultimate-member' ),
				'first_name'            => __( 'First name', 'ultimate-member' ),
				'last_name'             => __( 'Last name', 'ultimate-member' ),
				'display_name'          => __( 'Display name', 'ultimate-member' ),
				'last_login'            => __( 'Last login', 'ultimate-member' ),
			) );

			asort( $this->sort_fields );

			$this->default_sorting = apply_filters( 'um_members_directory_default_sort', array_merge( $this->sort_fields, array(
				'random'    => __( 'Random', 'ultimate-member' ),
				'other'     => __( 'Other (Custom Field)', 'ultimate-member' ),
			) ) );

			asort( $this->default_sorting );

			// Filters
			$this->filter_fields = array(
				'country'           => __( 'Country', 'ultimate-member' ),
				'gender'            => __( 'Gender', 'ultimate-member' ),
				'languages'         => __( 'Languages', 'ultimate-member' ),
				'role'              => __( 'Roles', 'ultimate-member' ),
				'birth_date'        => __( 'Age', 'ultimate-member' ),
				'last_login'        => __( 'Last Login', 'ultimate-member' ),
				'user_registered'   => __( 'User Registered', 'ultimate-member' ),
			);

			$this->filter_supported_fields = apply_filters( 'um_members_directory_custom_field_types_supported_filter', array( 'date', 'time', 'select', 'multiselect', 'radio', 'checkbox', 'rating' ) );

			if ( ! empty( UM()->builtin()->saved_fields ) ) {
				foreach ( UM()->builtin()->saved_fields as $key => $data ) {
					if ( isset( $data['type'] ) && in_array( $data['type'], $this->filter_supported_fields ) ) {
						if ( isset( $data['title'] ) && array_search( $data['title'], $this->filter_fields ) !== false ) {
							$data['title'] = $data['title'] . ' (' . $key . ')';
						}

						$title = isset( $data['title'] ) ? $data['title'] : ( isset( $data['label'] ) ? $data['label'] : '' );
						if ( empty( $title ) ) {
							continue;
						}

						$this->filter_fields[ $key ] = $title;
					}
				}
			}

			$this->filter_fields = apply_filters( 'um_members_directory_filter_fields', $this->filter_fields );

			ksort( $this->filter_fields );
		}


		/**
		 *
		 */
		function init_filter_types() {
			$this->filter_types = apply_filters( 'um_members_directory_filter_types', array(
				'country'           => 'select',
				'gender'            => 'select',
				'languages'         => 'select',
				'role'              => 'select',
				'birth_date'        => 'slider',
				'last_login'        => 'datepicker',
				'user_registered'   => 'datepicker',
			) );

			$fields = UM()->builtin()->all_user_fields;

			$custom_fields_types = array_flip( array_keys( $this->filter_fields ) );
			foreach ( $custom_fields_types as $key => &$value ) {
				if ( ! isset( $fields[ $key ] ) ) {
					unset( $custom_fields_types[ $key ] );
				} else {
					switch ( $fields[ $key ]['type'] ) {
						default:
							$value = apply_filters( 'um_custom_field_filter_type', 'select', $fields[ $key ] );
							break;
						case 'date':
							$value = 'datepicker';
							break;
						case 'time':
							$value = 'timepicker';
							break;
						case 'select':
						case 'multiselect':
						case 'radio':
						case 'checkbox':
							$value = 'select';
							break;
						case 'rating':
							$value = 'slider';
							break;
					}
				}
			}

			$this->filter_types = array_merge( $custom_fields_types, $this->filter_types );
		}


		/**
		 * Render member's directory
		 * filters selectboxes
		 *
		 * @param string $filter
		 * @param array $directory_data
		 * @param mixed $default_value
		 *
		 * @return string $filter
		 */
		function show_filter( $filter, $directory_data, $default_value = false ) {

			if ( empty( $this->filter_types[ $filter ] ) ) {
				return '';
			}

			$field_key = $filter;
			if ( $filter == 'last_login' ) {
				$field_key = '_um_last_login';
			}
			if ( $filter == 'role' ) {
				$field_key = 'role_select';
			}

			$fields = UM()->builtin()->all_user_fields;

			if ( isset( $fields[ $field_key ] ) ) {
				$attrs = $fields[ $field_key ];
			} else {
				/**
				 * UM hook
				 *
				 * @type filter
				 * @title um_custom_search_field_{$filter}
				 * @description Custom search settings by $filter
				 * @input_vars
				 * [{"var":"$settings","type":"array","desc":"Search Settings"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage
				 * <?php add_filter( 'um_custom_search_field_{$filter}', 'function_name', 10, 1 ); ?>
				 * @example
				 * <?php
				 * add_filter( 'um_custom_search_field_{$filter}', 'my_custom_search_field', 10, 1 );
				 * function my_change_email_template_file( $settings ) {
				 *     // your code here
				 *     return $settings;
				 * }
				 * ?>
				 */
				$attrs = apply_filters( "um_custom_search_field_{$filter}", array(), $field_key );
			}

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_search_fields
			 * @description Filter all search fields
			 * @input_vars
			 * [{"var":"$settings","type":"array","desc":"Search Fields"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_search_fields', 'function_name', 10, 1 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_search_fields', 'my_search_fields', 10, 1 );
			 * function my_search_fields( $settings ) {
			 *     // your code here
			 *     return $settings;
			 * }
			 * ?>
			 */
			$attrs = apply_filters( 'um_search_fields', $attrs, $field_key );

			$unique_hash = substr( md5( $directory_data['form_id'] ), 10, 5 );

			ob_start();

			switch ( $this->filter_types[ $filter ] ) {
				default: {

					do_action( "um_member_directory_filter_type_{$this->filter_types[ $filter ]}", $filter, $this->filter_types );

					break;
				}
				case 'select': {

					// getting value from GET line
					$filter_from_url = ! empty( $_GET[ 'filter_' . $filter . '_' . $unique_hash ] ) ? explode( '||', sanitize_text_field( $_GET[ 'filter_' . $filter . '_' . $unique_hash ] ) ) : array();

					if ( isset( $attrs['metakey'] ) && strstr( $attrs['metakey'], 'role_' ) ) {
						$shortcode_roles = get_post_meta( $directory_data['form_id'], '_um_roles', true );
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

					if ( ! empty( $attrs['custom_dropdown_options_source'] ) ) {
						$attrs['custom'] = true;
						$attrs['options'] = UM()->fields()->get_options_from_callback( $attrs, $attrs['type'] );
					}

					if ( isset( $attrs['label'] ) ) {
						$attrs['label'] = strip_tags( $attrs['label'] );
					}

					if ( isset( $attrs['options'] ) && is_array( $attrs['options'] ) ) {
						asort( $attrs['options'] );
					}

					$custom_dropdown = ! empty( $attrs['custom_dropdown_options_source'] ) ? ' data-um-ajax-source="' . $attrs['custom_dropdown_options_source'] . '"' : '';

					if ( ! empty( $attrs['options'] ) || ! empty( $custom_dropdown ) ) { ?>

						<select class="um-s1" id="<?php echo $filter; ?>" name="<?php echo $filter; ?>"
						        data-placeholder="<?php esc_attr_e( stripslashes( $attrs['label'] ), 'ultimate-member' ); ?>"
							<?php echo $custom_dropdown; ?>>

							<option></option>

							<?php if ( ! empty( $attrs['options'] ) ) {
								foreach ( $attrs['options'] as $k => $v ) {

									$v = stripslashes( $v );

									$opt = $v;

									if ( strstr( $filter, 'role_' ) ) {
										$opt = $k;
									}

									if ( isset( $attrs['custom'] ) ) {
										$opt = $k;
									} ?>

									<option value="<?php echo esc_attr( $opt ); ?>" data-value_label="<?php esc_attr_e( $v, 'ultimate-member' ); ?>"
										<?php disabled( ! empty( $filter_from_url ) && in_array( $opt, $filter_from_url ) ) ?>
										<?php selected( $opt == $default_value ) ?>>
										<?php _e( $v, 'ultimate-member' ); ?>
									</option>

								<?php }
							} ?>

						</select>

					<?php }

					break;
				}
				case 'slider': {
					$range = $this->slider_filters_range( $filter, $directory_data );
					$placeholder = $this->slider_range_placeholder( $filter, $attrs );

					if ( $range ) { ?>
						<input type="hidden" id="<?php echo $filter; ?>_min" name="<?php echo $filter; ?>[]" class="um_range_min" value="<?php echo ! empty( $default_value ) ? esc_attr( min( $default_value ) ) : '' ?>" />
						<input type="hidden" id="<?php echo $filter; ?>_max" name="<?php echo $filter; ?>[]" class="um_range_max" value="<?php echo ! empty( $default_value ) ? esc_attr( max( $default_value ) ) : '' ?>" />
						<div class="um-slider" data-field_name="<?php echo $filter; ?>" data-min="<?php echo $range[0] ?>" data-max="<?php echo $range[1] ?>"></div>
						<div class="um-slider-range" data-placeholder="<?php echo esc_attr( $placeholder ); ?>" data-label="<?php echo ( ! empty( $attrs['label'] ) ) ? esc_attr__( stripslashes( $attrs['label'] ), 'ultimate-member' ) : ''; ?>"></div>
					<?php }

					break;
				}
				case 'datepicker': {

					$range = $this->datepicker_filters_range( $filter );

					$label = ! empty( $attrs['label'] ) ? $attrs['label'] : $attrs['title'];

					if ( $range ) { ?>

						<input type="text" id="<?php echo $filter; ?>_from" name="<?php echo $filter; ?>_from" class="um-datepicker-filter"
						       placeholder="<?php esc_attr_e( sprintf( '%s From', stripslashes( $label ) ), 'ultimate-member' ); ?>"
						       data-filter-label="<?php echo esc_attr( stripslashes( $label ) ); ?>"
						       data-date_min="<?php echo $range[0] ?>" data-date_max="<?php echo $range[1] ?>"
						       data-filter_name="<?php echo $filter; ?>" data-range="from" data-value="<?php echo ! empty( $default_value ) ? esc_attr( strtotime( min( $default_value ) ) ) : '' ?>" />
						<input type="text" id="<?php echo $filter; ?>_to" name="<?php echo $filter; ?>_to" class="um-datepicker-filter"
						       placeholder="<?php esc_attr_e( sprintf( '%s To', stripslashes( $label ) ), 'ultimate-member' ); ?>"
						       data-filter-label="<?php echo esc_attr( stripslashes( $label ) ); ?>"
						       data-date_min="<?php echo $range[0] ?>" data-date_max="<?php echo $range[1] ?>"
						       data-filter_name="<?php echo $filter; ?>" data-range="to" data-value="<?php echo ! empty( $default_value ) ? esc_attr( strtotime( max( $default_value ) ) ) : '' ?>" />

					<?php }

					break;
				}
				case 'timepicker': {

					$range = $this->timepicker_filters_range( $filter );

					$label = ! empty( $attrs['label'] ) ? $attrs['label'] : $attrs['title'];

					switch ( $attrs['format'] ) {
						case 'g:i a':
							$js_format = 'h:i a';
							break;
						case 'g:i A':
							$js_format = 'h:i A';
							break;
						case 'H:i':
							$js_format = 'HH:i';
							break;
					}

					if ( $range ) { ?>

						<input type="text" id="<?php echo $filter; ?>_from" name="<?php echo $filter; ?>_from" class="um-timepicker-filter"
						       placeholder="<?php esc_attr_e( sprintf( '%s From', stripslashes( $label ) ), 'ultimate-member' ); ?>"
						       data-filter-label="<?php echo esc_attr( stripslashes( $label ) ); ?>"
						       data-min="<?php echo $range[0] ?>" data-max="<?php echo $range[1] ?>"
						       data-format="<?php echo esc_attr( $js_format ) ?>" data-intervals="<?php echo esc_attr( $attrs['intervals'] ) ?>"
						       data-filter_name="<?php echo $filter; ?>" data-range="from" />
						<input type="text" id="<?php echo $filter; ?>_to" name="<?php echo $filter; ?>_to" class="um-timepicker-filter"
						       placeholder="<?php esc_attr_e( sprintf( '%s To', stripslashes( $label ) ), 'ultimate-member' ); ?>"
						       data-filter-label="<?php echo esc_attr( stripslashes( $label ) ); ?>"
						       data-min="<?php echo $range[0] ?>" data-max="<?php echo $range[1] ?>"
						       data-format="<?php echo esc_attr( $js_format ) ?>" data-intervals="<?php echo esc_attr( $attrs['intervals'] ) ?>"
						       data-filter_name="<?php echo $filter; ?>" data-range="to" />

					<?php }

					break;
				}
			}

			$filter = ob_get_clean();
			return $filter;
		}


		/**
		 * @param string $filter
		 * @param array $directory_data
		 *
		 * @return mixed
		 */
		function slider_filters_range( $filter, $directory_data ) {

			switch ( $filter ) {

				default: {

					global $wpdb;
					$meta = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT meta_value
						FROM {$wpdb->usermeta}
						WHERE meta_key = %s
						ORDER BY meta_value DESC", $filter ) );

					if ( empty( $meta ) || count( $meta ) === 1 ) {
						$range = false;
					} elseif ( ! empty( $meta ) ) {
						$range = array( min( $meta ), max( $meta ) );
					}

					$range = apply_filters( "um_member_directory_filter_{$filter}_slider", $range, $directory_data );

					break;
				}
				case 'birth_date': {
					global $wpdb;
					$meta = $wpdb->get_col( "SELECT DISTINCT meta_value FROM {$wpdb->usermeta} WHERE meta_key='birth_date' ORDER BY meta_value DESC" );

					if ( empty( $meta ) || count( $meta ) === 1 ) {
						$range = false;
					} elseif ( ! empty( $meta ) ) {
						$range = array( $this->borndate( strtotime( $meta[0] ) ), $this->borndate( strtotime( $meta[ count( $meta ) - 1 ] ) ) );
					}

					break;
				}

			}

			return $range;
		}


		/**
		 * @param $filter
		 *
		 * @return mixed
		 */
		function slider_range_placeholder( $filter, $attrs ) {
			switch ( $filter ) {
				default: {
					$label = ucwords( str_replace( array( 'um_', '_' ), array( '', ' ' ), $filter ) );
					$placeholder = apply_filters( 'um_member_directory_filter_slider_range_placeholder', false, $filter );

					if ( ! $placeholder ) {
						switch ( $attrs['type'] ) {
							default:
								$placeholder = "<strong>$label:</strong>&nbsp;{min_range} - {max_range}";
								break;
							case 'rating':
								$placeholder = "<strong>$label:</strong>&nbsp;{min_range} - {max_range}" . __( ' stars', 'ultimate-member' );
								break;
						}
					}

					break;
				}
				case 'birth_date': {
					$placeholder = __( '<strong>Age:</strong>&nbsp;{min_range} - {max_range} years old', 'ultimate-member' );
					break;
				}
			}

			return $placeholder;
		}


		/**
		 * @param $filter
		 *
		 * @return mixed
		 */
		function datepicker_filters_range( $filter ) {
			global $wpdb;

			switch ( $filter ) {

				default: {

					global $wpdb;
					$meta = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT meta_value
						FROM {$wpdb->usermeta}
						WHERE meta_key = %s
						ORDER BY meta_value DESC", $filter ) );

					if ( empty( $meta ) || count( $meta ) === 1 ) {
						$range = false;
					} elseif ( ! empty( $meta ) ) {
						$range = array( strtotime( min( $meta ) ), strtotime( max( $meta ) ) );
					}

					$range = apply_filters( "um_member_directory_filter_{$filter}_datepicker", $range );

					break;
				}
				case 'last_login': {
					$meta = $wpdb->get_col( "SELECT DISTINCT meta_value
						FROM {$wpdb->usermeta}
						WHERE meta_key='_um_last_login'
						ORDER BY meta_value DESC" );

					if ( empty( $meta ) || count( $meta ) === 1 ) {
						$range = false;
					} elseif ( ! empty( $meta ) ) {
						$range = array( min( $meta ), max( $meta ) );
					}

					break;
				}
				case 'user_registered': {
					$meta = $wpdb->get_col(
						"SELECT DISTINCT user_registered
						FROM {$wpdb->users}
						ORDER BY user_registered DESC"
					);

					if ( empty( $meta ) || count( $meta ) === 1 ) {
						$range = false;
					} elseif ( ! empty( $meta ) ) {
						$range = array( strtotime( min( $meta ) ), strtotime( max( $meta ) ) );
					}

					break;
				}

			}

			return $range;
		}


		/**
		 * @param $filter
		 *
		 * @return mixed
		 */
		function timepicker_filters_range( $filter ) {

			switch ( $filter ) {

				default: {

					global $wpdb;
					$meta = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT meta_value
						FROM {$wpdb->usermeta}
						WHERE meta_key = %s
						ORDER BY meta_value DESC", $filter ) );

					$meta = array_filter( $meta );

					if ( empty( $meta ) || count( $meta ) === 1 ) {
						$range = false;
					} elseif ( ! empty( $meta ) ) {
						$range = array( min( $meta ), max( $meta ) );
					}


					$range = apply_filters( "um_member_directory_filter_{$filter}_timepicker", $range );

					break;
				}

			}

			return $range;
		}


		/**
		 * @param $borndate
		 *
		 * @return false|string
		 */
		function borndate( $borndate ) {
			if ( date('m', $borndate) > date('m') || date('m', $borndate) == date('m') && date('d', $borndate ) > date('d')) {
				return (date('Y') - date('Y', $borndate ) - 1);
			}
			return (date('Y') - date('Y', $borndate));
		}


		/**
		 * Handle members can view restrictions
		 */
		function restriction_options() {
			$this->hide_not_approved();
			$this->hide_by_role();
			$this->hide_by_account_settings();

			do_action( 'um_member_directory_restrictions_handle_extend' );
		}


		/**
		 *
		 */
		function hide_not_approved() {
			if ( UM()->roles()->um_user_can( 'can_edit_everyone' )  ) {
				return;
			}

			$this->query_args['meta_query'] = array_merge( $this->query_args['meta_query'], array( array(
				'key'       => 'account_status',
				'value'     => 'approved',
				'compare'   => '='
			) ) );
		}


		/**
		 *
		 */
		function hide_by_role() {
			$roles = um_user( 'can_view_roles' );
			$roles = maybe_unserialize( $roles );

			if ( empty( $roles ) || ! UM()->roles()->um_user_can( 'can_view_all' ) ) {
				return;
			}

			if ( ! empty( $this->query_args['role__in'] ) ) {
				$this->query_args['role__in'] = is_array( $this->query_args['role__in'] ) ? $this->query_args['role__in'] : array( $this->query_args['role__in'] );
				$this->query_args['role__in'] = array_intersect( $this->query_args['role__in'], $roles );
			} else {
				$this->query_args['role__in'] = $roles;
			}
		}


		/**
		 *
		 */
		function hide_by_account_settings() {
			if ( ! UM()->options()->get( 'account_hide_in_directory' ) ) {
				return;
			}

			if ( UM()->roles()->um_user_can( 'can_edit_everyone' ) ) {
				return;
			}

			$meta_query = array(
				"relation"  => "OR",
				array(
					'key'       => 'hide_in_members',
					'value'     => '',
					'compare'   => 'NOT EXISTS'
				),
				array(
					"relation"  => "AND",
					array(
						'key'       => 'hide_in_members',
						'value'     => __( 'Yes', 'ultimate-member' ),
						'compare'   => 'NOT LIKE'
					),
					array(
						'key'       => 'hide_in_members',
						'value'     => 'Yes',
						'compare'   => 'NOT LIKE'
					),
				),
			);

			$this->query_args['meta_query'] = array_merge( $this->query_args['meta_query'], array( $meta_query ) );
		}


		/**
		 * Handle "General Options" metabox settings
		 *
		 * @param array $directory_data
		 */
		function general_options( $directory_data ) {
			$this->show_selected_roles( $directory_data );
			$this->show_only_with_avatar( $directory_data );
			$this->show_only_with_cover( $directory_data );
			$this->show_only_these_users( $directory_data );

			do_action( 'um_member_directory_general_options_handle_extend', $directory_data );
		}


		/**
		 * Handle "User Roles to Display" option
		 *
		 * @param array $directory_data
		 */
		function show_selected_roles( $directory_data ) {
			// add roles to appear in directory
			if ( ! empty( $directory_data['roles'] ) ) {
				//since WP4.4 use 'role__in' argument
				$this->query_args['role__in'] = maybe_unserialize( $directory_data['roles'] );
			}
		}


		/**
		 * Handle "Only show members who have uploaded a profile photo" option
		 *
		 * @param array $directory_data
		 */
		function show_only_with_avatar( $directory_data ) {
			if ( $directory_data['has_profile_photo'] == 1 ) {
				$meta_query = array(
					'relation'  => 'OR',
					array(
						'key'       => 'synced_profile_photo', // addons
						'value'     => '',
						'compare'   => '!='
					),
					array(
						'key'       => 'profile_photo', // from upload form
						'value'     => '',
						'compare'   => '!='
					)
				);

				if ( UM()->options()->get( 'use_gravatars' ) ) {
					$meta_query[] = array(
						'key'       => 'synced_gravatar_hashed_id', // gravatar
						'value'     => '',
						'compare'   => '!='
					);
				}

				$this->query_args['meta_query'] = array_merge( $this->query_args['meta_query'], array( $meta_query ) );
			}
		}


		/**
		 * Handle "Only show members who have uploaded a cover photo" option
		 *
		 * @param array $directory_data
		 */
		function show_only_with_cover( $directory_data ) {
			if ( $directory_data['has_cover_photo'] == 1 ) {
				$this->query_args['meta_query'] = array_merge( $this->query_args['meta_query'], array( array(
					'key'       => 'cover_photo',
					'value'     => '',
					'compare'   => '!='
				) ) );
			}
		}


		/**
		 * Handle "Only show specific users (Enter one username per line)" option
		 *
		 * @param array $directory_data
		 */
		function show_only_these_users( $directory_data ) {
			if ( ! empty( $directory_data['show_these_users'] ) ) {
				$show_these_users = maybe_unserialize( $directory_data['show_these_users'] );

				if ( is_array( $show_these_users ) && ! empty( $show_these_users ) ) {

					$users_array = array();

					foreach ( $show_these_users as $username ) {
						if ( false !== ( $exists_id = username_exists( $username ) ) ) {
							$users_array[] = $exists_id;
						}
					}

					if ( ! empty( $users_array ) ) {
						$this->query_args['include'] = $users_array;
					}

				}
			}
		}


		/**
		 * Handle "Pagination Options" metabox settings
		 *
		 * @param array $directory_data
		 */
		function pagination_options( $directory_data ) {
			// number of profiles for mobile
			$profiles_per_page = $directory_data['profiles_per_page'];
			if ( UM()->mobile()->isMobile() && isset( $directory_data['profiles_per_page_mobile'] ) ) {
				$profiles_per_page = $directory_data['profiles_per_page_mobile'];
			}

			$this->query_args['number'] = ( ! empty( $directory_data['max_users'] ) && $directory_data['max_users'] <= $profiles_per_page ) ? $directory_data['max_users'] : $profiles_per_page;
			$this->query_args['paged'] = ! empty( $_POST['page'] ) ? $_POST['page'] : 1;
		}


		/**
		 * Add sorting attributes for \WP_Users_Query
		 *
		 * @param array $directory_data Member Directory options
		 */
		function sorting_query( $directory_data ) {
			// sort members by
			$this->query_args['order'] = 'ASC';
			$sortby = ! empty( $_POST['sorting'] ) ? $_POST['sorting'] : $directory_data['sortby'];

			if ( $sortby == 'other' && $directory_data['sortby_custom'] ) {

				$this->query_args['meta_key'] = $directory_data['sortby_custom'];
				$this->query_args['orderby'] = 'meta_value, display_name';

			} elseif ( 'display_name' == $sortby ) {

				$display_name = UM()->options()->get( 'display_name' );
				if ( $display_name == 'username' ) {
					$this->query_args['orderby'] = 'user_login';
					$this->query_args['order'] = 'ASC';
				} else {
					$this->query_args['meta_query'][] = array(
						'relation' => 'OR',
						'full_name' => array(
							'key'       => 'full_name',
							'compare'   => 'EXISTS'
						),
						array(
							'key'       => 'full_name',
							'compare'   => 'NOT EXISTS'
						)
					);

					$this->query_args['orderby'] = 'full_name, display_name';
					$this->query_args['order'] = 'ASC';
				}

			} elseif ( in_array( $sortby, array( 'last_name', 'first_name' ) ) ) {

				$this->query_args['meta_query'] = array_merge( $this->query_args['meta_query'], array( $sortby . '_c' => array(
					'key'       => $sortby,
					'compare'   => 'EXISTS',
				), ) );

				$this->query_args['orderby'] = array( $sortby . '_c' => 'ASC' );
				unset( $this->query_args['order'] );

			} elseif ( $sortby == 'last_login' ) {

				$this->query_args['orderby'] = array( 'um_last_login' => 'DESC' );
				$this->query_args['meta_query'][] = array(
					'relation' => 'OR',
					array(
						'key'   => '_um_last_login',
						'compare'   => 'EXISTS',
					),
					'um_last_login' => array(
						'key'   => '_um_last_login',
						'compare'   => 'NOT EXISTS',
					),
				);
				unset( $this->query_args['order'] );

			} else {

				if ( strstr( $sortby, '_desc' ) ) {
					$sortby = str_replace( '_desc', '', $sortby );
					$order = 'DESC';
				}

				if ( strstr( $sortby, '_asc' ) ) {
					$sortby = str_replace( '_asc', '', $sortby );
					$order = 'ASC';
				}

				$this->query_args['orderby'] = $sortby;
				if ( isset( $order ) ) {
					$this->query_args['order'] = $order;
				}

				add_filter( 'pre_user_query', array( &$this, 'sortby_randomly' ), 10, 1 );
			}


			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_modify_sortby_parameter
			 * @description Change query sort by attributes for search at Members Directory
			 * @input_vars
			 * [{"var":"$query_args","type":"array","desc":"Query Arguments"},
			 * {"var":"$sortby","type":"string","desc":"Sort by"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_modify_sortby_parameter', 'function_name', 10, 2 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_modify_sortby_parameter', 'my_modify_sortby_parameter', 10, 2 );
			 * function my_modify_sortby_parameter( $query_args, $sortby ) {
			 *     // your code here
			 *     return $query_args;
			 * }
			 * ?>
			 */
			$this->query_args = apply_filters( 'um_modify_sortby_parameter', $this->query_args, $sortby );
		}


		/**
		 * Sorting random
		 *
		 * @param object $query
		 *
		 * @return mixed
		 */
		function sortby_randomly( $query ) {
			if ( 'random' == $query->query_vars['orderby'] ) {

				if ( um_is_session_started() === false ) {
					@session_start();
				}

				// Reset seed on load of initial
				if ( ! isset( $_REQUEST['members_page'] ) || $_REQUEST['members_page'] == 0 ||  $_REQUEST['members_page'] == 1 ) {
					if ( isset( $_SESSION['seed'] ) ) {
						unset( $_SESSION['seed'] );
					}
				}

				// Get seed from session variable if it exists
				$seed = false;
				if ( isset( $_SESSION['seed'] ) ) {
					$seed = $_SESSION['seed'];
				}

				// Set new seed if none exists
				if ( ! $seed ) {
					$seed = rand();
					$_SESSION['seed'] = $seed;
				}


				$query->query_orderby = 'ORDER by RAND(' . $seed . ')';
			}

			return $query;
		}



		/**
		 * Handle general search line request
		 */
		function general_search() {
			//general search
			if ( ! empty( $_POST['search'] ) ) {
				// complex using with change_meta_sql function

				$meta_query = array(
					'relation' => 'OR',
					array(
						'value'     => trim( $_POST['search'] ),
						'compare'   => '=',
					),
					array(
						'value'     => trim( $_POST['search'] ),
						'compare'   => 'LIKE',
					),
					array(
						'value'     => trim( serialize( strval( $_POST['search'] ) ) ),
						'compare'   => 'LIKE',
					),
				);

				$meta_query = apply_filters( 'um_member_directory_general_search_meta_query', $meta_query, $_POST['search'] );

				$this->query_args['meta_query'][] = $meta_query;
			}
		}


		/**
		 * Change mySQL meta query join attribute
		 * for search only by UM user meta fields and WP core fields in WP Users table
		 *
		 * @param array $sql Array containing the query's JOIN and WHERE clauses.
		 * @param $queries
		 * @param $type
		 * @param $primary_table
		 * @param $primary_id_column
		 * @param \WP_User_Query $context
		 *
		 * @return mixed
		 */
		function change_meta_sql( $sql, $queries, $type, $primary_table, $primary_id_column, $context ) {
			if ( ! empty( $_POST['search'] ) ) {
				global $wpdb;
				$search = trim( $_POST['search'] );
				if ( ! empty( $search ) ) {

					$meta_value = '%' . $wpdb->esc_like( $search ) . '%';
					$search_meta      = $wpdb->prepare( '%s', $meta_value );

					preg_match(
						'/^(.*).meta_value LIKE ' . addslashes( $search_meta ) . '[^\)]/im',
						$sql['where'],
						$join_matches
					);

					if ( isset( $join_matches[1] ) ) {
						$meta_join_for_search = trim( $join_matches[1] );

						$sql['join'] = preg_replace(
							'/(' . $meta_join_for_search . ' ON \( ' . $wpdb->users . '\.ID = ' . $meta_join_for_search . '\.user_id )(\))/im',
							"$1 AND " . $meta_join_for_search . ".meta_key IN( '" . implode( "','", array_keys( UM()->builtin()->all_user_fields ) ) . "' ) $2",
							$sql['join']
						);
					}

					// Add OR instead AND to search in WP core fields user_email, user_login, user_display_name
					$search_where = $context->get_search_sql( $search, $this->core_search_fields, 'both' );

					$search_where = preg_replace( '/ AND \((.*?)\)/im', "$1 OR", $search_where );

					$sql['where'] = preg_replace(
						'/(' . $meta_join_for_search . '.meta_value = \'' . esc_attr( $search ) . '\')/im',
						trim( $search_where ) . " $1",
						$sql['where'],
						1
					);
				}
			}

			return $sql;
		}


		/**
		 * Handle filters request
		 */
		function filters( $directory_data ) {
			//filters
			$filter_query = array();
			if ( ! empty( $directory_data['search_fields'] ) ) {
				$search_filters = maybe_unserialize( $directory_data['search_fields'] );
				if ( ! empty( $search_filters ) && is_array( $search_filters ) ) {
					$filter_query = array_intersect_key( $_POST, array_flip( $search_filters ) );
				}
			}

			// added for user tags extension integration on individual tag page
			$ignore_empty_filters = apply_filters( 'um_member_directory_ignore_empty_filters', false );

			if ( empty( $filter_query ) && ! $ignore_empty_filters ) {
				return;
			}

			foreach ( $filter_query as $field => $value ) {

				switch ( $field ) {
					default:

						$filter_type = $this->filter_types[ $field ];

						/**
						 * UM hook
						 *
						 * @type filter
						 * @title um_query_args_{$field}__filter
						 * @description Change field's query for search at Members Directory
						 * @input_vars
						 * [{"var":"$field_query","type":"array","desc":"Field query"}]
						 * @change_log
						 * ["Since: 2.0"]
						 * @usage
						 * <?php add_filter( 'um_query_args_{$field}__filter', 'function_name', 10, 1 ); ?>
						 * @example
						 * <?php
						 * add_filter( 'um_query_args_{$field}__filter', 'my_query_args_filter', 10, 1 );
						 * function my_query_args_filter( $field_query ) {
						 *     // your code here
						 *     return $field_query;
						 * }
						 * ?>
						 */
						$field_query = apply_filters( "um_query_args_{$field}__filter", false, $field, $value, $filter_type );

						if ( ! $field_query ) {

							switch ( $filter_type ) {
								default:

									$field_query = apply_filters( "um_query_args_{$field}_{$filter_type}__filter", false, $field, $value, $filter_type );

									break;
								case 'select':
									if ( is_array( $value ) ) {
										$field_query = array( 'relation' => 'OR' );

										foreach ( $value as $single_val ) {
											$arr_meta_query = array(
												array(
													'key'       => $field,
													'value'     => trim( $single_val ),
													'compare'   => '=',
												),
												array(
													'key'       => $field,
													'value'     => serialize( strval( trim( $single_val ) ) ),
													'compare'   => 'LIKE',
												),
												array(
													'key'       => $field,
													'value'     => '"' . trim( $single_val ) . '"',
													'compare'   => 'LIKE',
												)
											);

											if ( is_numeric( $single_val ) ) {

												$arr_meta_query[] = array(
													'key'       => $field,
													'value'     => serialize( intval( trim( $single_val ) ) ),
													'compare'   => 'LIKE',
												);

											}

											$field_query = array_merge( $field_query, $arr_meta_query );
										}
									}

									$this->custom_filters_in_query[ $field ] = $value;

									break;
								case 'slider':

									$this->custom_filters_in_query[ $field ] = $value;

									$field_query = array(
										'key'       => $field,
										'value'     => $value,
										'compare'   => 'BETWEEN',
										'inclusive' => true,
									);

									break;
								case 'datepicker':

									$offset = 0;
									if ( isset( $_POST['gmt_offset'] ) && is_numeric( $_POST['gmt_offset'] ) ) {
										$offset = (int) $_POST['gmt_offset'];
									}

									$from_date = (int) min( $value ) + ( $offset * HOUR_IN_SECONDS ); // client time zone offset
									$to_date   = (int) max( $value ) + ( $offset * HOUR_IN_SECONDS ) + DAY_IN_SECONDS - 1; // time 23:59
									$field_query = array(
										'key'       => $field,
										'value'     =>  array( $from_date, $to_date ),
										'compare'   => 'BETWEEN',
										'inclusive' => true,
									);

									$this->custom_filters_in_query[ $field ] = array( $from_date, $to_date );

									break;
								case 'timepicker':

									if ( $value[0] == $value[1] ) {
										$field_query = array(
											'key'       => $field,
											'value'     => $value[0],
										);
									} else {
										$field_query = array(
											'key'       => $field,
											'value'     => $value,
											'compare'   => 'BETWEEN',
											'type'      => 'TIME',
											'inclusive' => true,
										);
									}

									$this->custom_filters_in_query[ $field ] = $value;

									break;
							}

						}

						if ( ! empty( $field_query ) && $field_query !== true ) {
							$this->query_args['meta_query'] = array_merge( $this->query_args['meta_query'], array( $field_query ) );
						}

						break;
					case 'role':
						$value = array_map( 'strtolower', $value );

						if ( ! empty( $this->query_args['role__in'] ) ) {
							$this->query_args['role__in'] = is_array( $this->query_args['role__in'] ) ? $this->query_args['role__in'] : array( $this->query_args['role__in'] );
							$default_role = array_intersect( $this->query_args['role__in'], $value );
							$um_role = array_diff( $value, $default_role );

							foreach ( $um_role as $key => &$val ) {
								$val = 'um_' . str_replace( ' ', '-', $val );
							}
							$this->query_args['role__in'] = array_merge( $default_role, $um_role );
						} else {
							$this->query_args['role__in'] = $value;
						};

						$this->custom_filters_in_query[ $field ] = $this->query_args['role__in'];

						break;
					case 'birth_date':

						$from_date = date( 'Y/m/d', mktime( 0,0,0, date( 'm', time() ), date( 'd', time() ), date( 'Y', time() - min( $value ) * YEAR_IN_SECONDS ) ) );
						$to_date = date( 'Y/m/d', mktime( 0,0,0, date( 'm', time() ), date( 'd', time() ) + 1, date( 'Y', time() - ( max( $value ) + 1 ) * YEAR_IN_SECONDS ) ) );

						$meta_query = array(
							array(
								'key'       => 'birth_date',
								'value'     => array( $to_date, $from_date ),
								'compare'   => 'BETWEEN',
								'type'      => 'DATE',
								'inclusive' => true,
							)
						);

						$this->query_args['meta_query'] = array_merge( $this->query_args['meta_query'], array( $meta_query ) );

						$this->custom_filters_in_query[ $field ] = array( $to_date, $from_date );

						break;
					case 'user_registered':

						$offset = 0;
						if ( isset( $_POST['gmt_offset'] ) && is_numeric( $_POST['gmt_offset'] ) ) {
							$offset = (int) $_POST['gmt_offset'];
						}

						$from_date = date( 'Y-m-d H:s:i', strtotime( date( 'Y-m-d H:s:i', min( $value ) ) . "+$offset hours" ) );
						$to_date = date( 'Y-m-d H:s:i', strtotime( date( 'Y-m-d H:s:i', max( $value ) ) . "+$offset hours" ) );

						$date_query = array(
							array(
								'column'    => 'user_registered',
								'before'    => $to_date,
								'after'     => $from_date,
								'inclusive' => true,
							),
						);

						if ( empty( $this->query_args['date_query'] ) ) {
							$this->query_args['date_query'] = $date_query;
						} else {
							$this->query_args['date_query'] = array_merge( $this->query_args['date_query'], array( $date_query ) );
						}

						$this->custom_filters_in_query[ $field ] = $value;

						break;
					case 'last_login':

						$offset = 0;
						if ( isset( $_POST['gmt_offset'] ) && is_numeric( $_POST['gmt_offset'] ) ) {
							$offset = (int) $_POST['gmt_offset'];
						}

						$from_date = (int) min( $value ) + ( $offset * HOUR_IN_SECONDS ); // client time zone offset
						$to_date   = (int) max( $value ) + ( $offset * HOUR_IN_SECONDS ) + DAY_IN_SECONDS - 1; // time 23:59
						$meta_query = array(
							array(
								'key'       => '_um_last_login',
								'value'     =>  array( $from_date, $to_date ),
								'compare'   => 'BETWEEN',
								'inclusive' => true,
							)
						);

						$this->custom_filters_in_query[ $field ] = $value;

						$this->query_args['meta_query'] = array_merge( $this->query_args['meta_query'], array( $meta_query ) );
						break;
				}

			}
		}


		/**
		 * Set default filters
		 *
		 * @param $directory_data
		 */
		function default_filters( $directory_data ) {
			//unable default filter in case if we select other filters in frontend filters
			if ( ! empty( $this->custom_filters_in_query ) ) {
				return;
			}

			$default_filters = array();
			if ( ! empty( $directory_data['search_filters'] ) ) {
				$default_filters = maybe_unserialize( $directory_data['search_filters'] );
			}

			$gmt_offset = get_post_meta( $directory_data['form_id'], '_um_search_filters_gmt', true );

			if ( empty( $default_filters ) ) {
				return;
			}

			foreach ( $default_filters as $field => $value ) {
				//unable default filter in case if we select other value in frontend filters
//				if ( in_array( $field, array_keys( $this->custom_filters_in_query ) ) ) {
//					continue;
//				}

				switch ( $field ) {
					default:

						$filter_type = $this->filter_types[ $field ];

						/**
						 * UM hook
						 *
						 * @type filter
						 * @title um_query_args_{$field}__filter
						 * @description Change field's query for search at Members Directory
						 * @input_vars
						 * [{"var":"$field_query","type":"array","desc":"Field query"}]
						 * @change_log
						 * ["Since: 2.0"]
						 * @usage
						 * <?php add_filter( 'um_query_args_{$field}__filter', 'function_name', 10, 1 ); ?>
						 * @example
						 * <?php
						 * add_filter( 'um_query_args_{$field}__filter', 'my_query_args_filter', 10, 1 );
						 * function my_query_args_filter( $field_query ) {
						 *     // your code here
						 *     return $field_query;
						 * }
						 * ?>
						 */
						$field_query = apply_filters( "um_query_args_{$field}__filter", false, $field, $value, $filter_type );

						if ( ! $field_query ) {

							switch ( $filter_type ) {
								default:

									$field_query = apply_filters( "um_query_args_{$field}_{$filter_type}__filter", false, $field, $value, $filter_type );

									break;
								case 'select':
									if ( ! is_array( $value ) ) {
										$value = array( $value );
									}

									$field_query = array( 'relation' => 'OR' );

									foreach ( $value as $single_val ) {
										$arr_meta_query = array(
											array(
												'key'       => $field,
												'value'     => trim( $single_val ),
												'compare'   => '=',
											),
											array(
												'key'       => $field,
												'value'     => serialize( strval( trim( $single_val ) ) ),
												'compare'   => 'LIKE',
											),
											array(
												'key'       => $field,
												'value'     => '"' . trim( $single_val ) . '"',
												'compare'   => 'LIKE',
											)
										);

										if ( is_numeric( $single_val ) ) {

											$arr_meta_query[] = array(
												'key'       => $field,
												'value'     => serialize( intval( trim( $single_val ) ) ),
												'compare'   => 'LIKE',
											);

										}

										$field_query = array_merge( $field_query, $arr_meta_query );
									}

									break;
								case 'slider':

									$field_query = array(
										'key'       => $field,
										'value'     => $value,
										'compare'   => 'BETWEEN',
										'inclusive' => true,
									);

									break;
								case 'datepicker':

									$offset = 0;
									if ( is_numeric( $gmt_offset ) ) {
										$offset = $gmt_offset;
									}

									$from_date = (int) min( $value ) + ( $offset * HOUR_IN_SECONDS ); // client time zone offset
									$to_date   = (int) max( $value ) + ( $offset * HOUR_IN_SECONDS ) + DAY_IN_SECONDS - 1; // time 23:59
									$field_query = array(
										'key'       => $field,
										'value'     =>  array( $from_date, $to_date ),
										'compare'   => 'BETWEEN',
										'inclusive' => true,
									);

									break;
								case 'timepicker':

									if ( $value[0] == $value[1] ) {
										$field_query = array(
											'key'       => $field,
											'value'     => $value[0],
										);
									} else {
										$field_query = array(
											'key'       => $field,
											'value'     => $value,
											'compare'   => 'BETWEEN',
											'type'      => 'TIME',
											'inclusive' => true,
										);
									}

									break;
							}

						}

						if ( ! empty( $field_query ) && $field_query !== true ) {
							$this->query_args['meta_query'] = array_merge( $this->query_args['meta_query'], array( $field_query ) );
						}

						break;
					case 'role':
						$value = explode( '||', $value );
						$value = array_map( 'strtolower', $value );

						if ( ! empty( $this->query_args['role__in'] ) ) {
							$this->query_args['role__in'] = is_array( $this->query_args['role__in'] ) ? $this->query_args['role__in'] : array( $this->query_args['role__in'] );
							$default_role = array_intersect( $this->query_args['role__in'], $value );
							$um_role = array_diff( $value, $default_role );

							foreach ( $um_role as $key => &$val ) {
								$val = 'um_' . str_replace( ' ', '-', $val );
							}
							$this->query_args['role__in'] = array_merge( $default_role, $um_role );
						} else {
							$this->query_args['role__in'] = $value;
						};

						break;
					case 'birth_date':
						$from_date = date( 'Y/m/d', mktime( 0,0,0, date( 'm', time() ), date( 'd', time() ), date( 'Y', time() - min( $value ) * YEAR_IN_SECONDS ) ) );
						$to_date = date( 'Y/m/d', mktime( 0,0,0, date( 'm', time() ), date( 'd', time() ) + 1, date( 'Y', time() - ( max( $value ) + 1 ) * YEAR_IN_SECONDS ) ) );

						$meta_query = array(
							array(
								'key'       => 'birth_date',
								'value'     => array( $to_date, $from_date ),
								'compare'   => 'BETWEEN',
								'type'      => 'DATE',
								'inclusive' => true,
							)
						);

						$this->query_args['meta_query'] = array_merge( $this->query_args['meta_query'], array( $meta_query ) );

						break;
					case 'user_registered':
						$offset = 0;
						if ( is_numeric( $gmt_offset ) ) {
							$offset = $gmt_offset;
						}

						$from_date = date( 'Y-m-d H:s:i', strtotime( date( 'Y-m-d H:s:i', min( $value ) ) . "+$offset hours" ) );
						$to_date = date( 'Y-m-d H:s:i', strtotime( date( 'Y-m-d H:s:i', max( $value ) ) . "+$offset hours" ) );

						$date_query = array(
							array(
								'column'    => 'user_registered',
								'before'    => $to_date,
								'after'     => $from_date,
								'inclusive' => true,
							),
						);

						if ( empty( $this->query_args['date_query'] ) ) {
							$this->query_args['date_query'] = $date_query;
						} else {
							$this->query_args['date_query'] = array_merge( $this->query_args['date_query'], array( $date_query ) );
						}

						break;
					case 'last_login':
						$offset = 0;
						if ( is_numeric( $gmt_offset ) ) {
							$offset = $gmt_offset;
						}

						$from_date = (int) min( $value ) + ( $offset * HOUR_IN_SECONDS ); // client time zone offset
						$to_date   = (int) max( $value ) + ( $offset * HOUR_IN_SECONDS ) + DAY_IN_SECONDS - 1; // time 23:59
						$meta_query = array(
							array(
								'key'       => '_um_last_login',
								'value'     =>  array( $from_date, $to_date ),
								'compare'   => 'BETWEEN',
								'inclusive' => true,
							)
						);

						$this->query_args['meta_query'] = array_merge( $this->query_args['meta_query'], array( $meta_query ) );
						break;
				}
			}
		}


		/**
		 * Get data array for pagination
		 *
		 *
		 * @param array $directory_data
		 * @param \WP_User_Query $result
		 *
		 * @return array
		 */
		function calculate_pagination( $directory_data, $result ) {

			$current_page = ! empty( $_POST['page'] ) ? $_POST['page'] : 1;

			$total_users = ( ! empty( $directory_data['max_users'] ) && $directory_data['max_users'] <= $result->total_users ) ? $directory_data['max_users'] : $result->total_users;
			$total_pages = ceil( $total_users / $directory_data['profiles_per_page'] );

			if ( ! empty( $total_pages ) ) {
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
			}


			$pagination_data = array(
				'pages_to_show' => ( ! empty( $pages_to_show ) && count( $pages_to_show ) > 1 ) ? array_values( $pages_to_show ) : array(),
				'current_page'  => $current_page,
				'total_pages'   => $total_pages,
				'total_users'   => $total_users,
			);

			$pagination_data['header'] = $this->convert_tags( $directory_data['header'], $pagination_data );
			$pagination_data['header_single'] = $this->convert_tags( $directory_data['header_single'], $pagination_data );

			return $pagination_data;
		}


		/**
		 * @param int $user_id
		 *
		 * @return array
		 */
		function build_user_actions_list( $user_id ) {

			$actions = array();
			if ( ! is_user_logged_in() ) {
				return $actions;
			}

			if ( get_current_user_id() != $user_id ) {

				if ( UM()->roles()->um_current_user_can( 'edit', $user_id ) ) {
					$actions['um-editprofile'] = array(
						'title' => __( 'Edit Profile', 'ultimate-member' ),
						'url' => um_edit_profile_url(),
					);
				}

				/**
				 * UM hook
				 *
				 * @type filter
				 * @title um_admin_user_actions_hook
				 * @description Extend admin actions for each user
				 * @input_vars
				 * [{"var":"$actions","type":"array","desc":"Actions for user"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage
				 * <?php add_filter( 'um_admin_user_actions_hook', 'function_name', 10, 1 ); ?>
				 * @example
				 * <?php
				 * add_filter( 'um_admin_user_actions_hook', 'my_admin_user_actions', 10, 1 );
				 * function my_admin_user_actions( $actions ) {
				 *     // your code here
				 *     return $actions;
				 * }
				 * ?>
				 */
				$admin_actions = apply_filters( 'um_admin_user_actions_hook', array(), $user_id );
				if ( ! empty( $admin_actions ) ) {
					foreach ( $admin_actions as $id => $arr ) {
						$url = add_query_arg( array( 'um_action' => $id, 'uid' => $user_id ), um_get_core_page( 'user' ) );

						$actions[ $id ] = array(
							'title' => $arr['label'],
							'url'   => $url,
						);
					}
				}

				$actions = apply_filters( 'um_member_directory_users_card_actions', $actions, $user_id );

			} else {

				if ( empty( UM()->user()->cannot_edit ) ) {
					$actions['um-editprofile'] = array(
						'title' => __( 'Edit Profile', 'ultimate-member' ),
						'url'   => um_edit_profile_url(),
					);
				}

				$actions['um-myaccount'] = array(
					'title' => __( 'My Account', 'ultimate-member' ),
					'url'   => um_get_core_page( 'account' ),
				);

				$actions['um-logout'] = array(
					'title' => __( 'Logout', 'ultimate-member' ),
					'url'   => um_get_core_page( 'logout' ),
				);

				$actions = apply_filters( 'um_member_directory_my_user_card_actions', $actions, $user_id );
			}


			return $actions;
		}


		/**
		 * @param int $user_id
		 * @param array $directory_data
		 *
		 * @return array
		 */
		function build_user_card_data( $user_id, $directory_data ) {
			um_fetch_user( $user_id );

			$dropdown_actions = $this->build_user_actions_list( $user_id );

			$actions = array();
			$can_edit = UM()->roles()->um_current_user_can( 'edit', $user_id ) || UM()->roles()->um_user_can( 'can_edit_everyone' );

			// Replace hook 'um_members_just_after_name'
			ob_start();
			do_action( 'um_members_just_after_name', $user_id, $directory_data );
			$hook_just_after_name = ob_get_clean();

			// Replace hook 'um_members_after_user_name'
			ob_start();
			do_action( 'um_members_after_user_name', $user_id, $directory_data );
			$hook_after_user_name = ob_get_clean();

			$data_array = array(
				'id'                    => $user_id,
				'role'                  => um_user( 'role' ),
				'account_status'        => um_user( 'account_status' ),
				'account_status_name'   => um_user( 'account_status_name' ),
				'cover_photo'           => um_user( 'cover_photo', $this->cover_size ),
				'display_name'          => um_user( 'display_name' ),
				'profile_url'           => um_user_profile_url(),
				'can_edit'              => $can_edit,
				'edit_profile_url'      => um_edit_profile_url(),
				'avatar'                => get_avatar( $user_id, $this->avatar_size ),
				'display_name_html'     => um_user( 'display_name', 'html' ),
				'dropdown_actions'      => $dropdown_actions,
				'hook_just_after_name'  => preg_replace( '/^\s+/im', '', $hook_just_after_name ),
				'hook_after_user_name'  => preg_replace( '/^\s+/im', '', $hook_after_user_name ),
			);

			$directory_data['tagline_fields'] = maybe_unserialize( $directory_data['tagline_fields'] );

			if ( $directory_data['show_tagline'] && is_array( $directory_data['tagline_fields'] ) ) {
				foreach ( $directory_data['tagline_fields'] as $key ) {
					if ( ! $key ) {
						continue;
					}

					$value = um_filtered_value( $key );

					if ( ! $value ) {
						continue;
					}

					$data_array[ $key ] = $value;
				}
			}

			if ( $directory_data['show_userinfo'] ) {
				$directory_data['reveal_fields'] = maybe_unserialize( $directory_data['reveal_fields'] );

				if ( is_array( $directory_data['reveal_fields'] ) ) {
					foreach ( $directory_data['reveal_fields'] as $key ) {
						if ( ! $key ) {
							continue;
						}

						$value = um_filtered_value( $key );
						if ( ! $value ) {
							continue;
						}

						$label = UM()->fields()->get_label( $key );
						if ( $key == 'role_select' || $key == 'role_radio' ) {
							$label = strtr( $label, array(
								' (Dropdown)'   => '',
								' (Radio)'      => ''
							) );
						}

						$data_array[ "label_{$key}" ] = $label;
						$data_array[ $key ] = $value;
					}
				}

				if ( ! empty( $directory_data['show_social'] ) ) {
					ob_start();
					UM()->fields()->show_social_urls();
					$social_urls = ob_get_clean();

					$data_array['social_urls'] = $social_urls;
				}
			}

			$data_array = apply_filters( 'um_ajax_get_members_data', $data_array, $user_id, $directory_data );
			um_reset_user_clean();

			return $data_array;
		}


		/**
		 * Main Query function for getting members via AJAX
		 */
		function ajax_get_members() {
			UM()->check_ajax_nonce();

			global $wpdb;

			$directory_id = $this->get_directory_by_hash( $_POST['directory_id'] );
			$directory_data = UM()->query()->post_data( $directory_id );

			//predefined result for user without capabilities to see other members
			if ( is_user_logged_in() && ! UM()->roles()->um_user_can( 'can_view_all' ) ) {
				$pagination_data = array(
					'pages_to_show' => array(),
					'current_page'  => 1,
					'total_pages'   => 0,
					'total_users'   => 0,
				);

				$pagination_data['header'] = $this->convert_tags( $directory_data['header'], $pagination_data );
				$pagination_data['header_single'] = $this->convert_tags( $directory_data['header_single'], $pagination_data );

				wp_send_json_success( array( 'users' => array(), 'pagination' => $pagination_data ) );
			}

			do_action( 'um_member_directory_before_query' );

			// Prepare for BIG SELECT query
			$wpdb->query( 'SET SQL_BIG_SELECTS=1' );

			// Prepare default user query values
			$this->query_args = array(
				'fields'        => 'ids',
				'number'        => 0,
				'meta_query'    => array(
					'relation' => 'AND'
				),
			);


			// handle different restrictions
			$this->restriction_options();

			// handle general options
			$this->general_options( $directory_data );

			// handle pagination options
			$this->pagination_options( $directory_data );

			// handle sorting options
			$this->sorting_query( $directory_data );

			// handle general search line
			$this->general_search();

			// handle filters
			$this->filters( $directory_data );

			$this->default_filters( $directory_data );

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_prepare_user_query_args
			 * @description Extend member directory query arguments
			 * @input_vars
			 * [{"var":"$query_args","type":"array","desc":"Members Query Arguments"},
			 * {"var":"$directory_settings","type":"array","desc":"Member Directory Settings"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_prepare_user_query_args', 'function_name', 10, 2 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_prepare_user_query_args', 'my_prepare_user_query_args', 10, 2 );
			 * function my_prepare_user_query_args( $query_args, $directory_settings ) {
			 *     // your code here
			 *     return $query_args;
			 * }
			 * ?>
			 */
			$this->query_args = apply_filters( 'um_prepare_user_query_args', $this->query_args, $directory_data );

			//unset empty meta_query attribute
			if ( isset( $this->query_args['meta_query']['relation'] ) && count( $this->query_args['meta_query'] ) == 1 ) {
				unset( $this->query_args['meta_query'] );
			}

			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_user_before_query
			 * @description Action before users query on member directory
			 * @input_vars
			 * [{"var":"$query_args","type":"array","desc":"Query arguments"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_user_before_query', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_action( 'um_user_before_query', 'my_user_before_query', 10, 1 );
			 * function my_user_before_query( $query_args ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_user_before_query', $this->query_args );

			add_filter( 'get_meta_sql', array( &$this, 'change_meta_sql' ), 10, 6 );

			$user_query = new \WP_User_Query( $this->query_args );

			remove_filter( 'get_meta_sql', array( &$this, 'change_meta_sql' ), 10 );

			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_user_after_query
			 * @description Action before users query on member directory
			 * @input_vars
			 * [{"var":"$query_args","type":"array","desc":"Query arguments"},
			 * {"var":"$user_query","type":"array","desc":"User Query"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_user_after_query', 'function_name', 10, 2 );
			 * @example
			 * <?php
			 * add_action( 'um_user_after_query', 'my_user_after_query', 10, 2 );
			 * function my_user_after_query( $query_args, $user_query ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_user_after_query', $this->query_args, $user_query );

			$pagination_data = $this->calculate_pagination( $directory_data, $user_query );

			$user_ids = ! empty( $user_query->results ) ? array_unique( $user_query->results ) : array();

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_prepare_user_results_array
			 * @description Extend member directory query result
			 * @input_vars
			 * [{"var":"$result","type":"array","desc":"Members Query Result"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_prepare_user_results_array', 'function_name', 10, 1 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_prepare_user_results_array', 'my_prepare_user_results', 10, 1 );
			 * function my_prepare_user_results( $user_ids ) {
			 *     // your code here
			 *     return $user_ids;
			 * }
			 * ?>
			 */
			$user_ids = apply_filters( 'um_prepare_user_results_array', $user_ids );


			$sizes = UM()->options()->get( 'cover_thumb_sizes' );
			$this->cover_size = UM()->mobile()->isTablet() ? $sizes[1] : $sizes[0];

			$avatar_size = UM()->options()->get( 'profile_photosize' );
			$this->avatar_size = str_replace( 'px', '', $avatar_size );

			$users = array();
			foreach ( $user_ids as $user_id ) {
				$users[] = $this->build_user_card_data( $user_id, $directory_data );
			}

			um_reset_user();
			// end of user card

			wp_send_json_success( array( 'pagination' => $pagination_data, 'users' => $users ) );
		}


		/**
		 * New menu
		 *
		 * @param string $element
		 * @param string $trigger
		 * @param array $items
		 */
		function dropdown_menu( $element, $trigger, $items = array() ) {
			?>

			<div class="um-new-dropdown" data-element="<?php echo $element; ?>" data-trigger="<?php echo $trigger; ?>">
				<ul>
					<?php foreach ( $items as $k => $v ) { ?>
						<li><?php echo $v; ?></li>
					<?php } ?>
				</ul>
			</div>

			<?php
		}


		/**
		 * New menu JS
		 *
		 * @param string $element
		 * @param string $trigger
		 * @param string $item
		 */
		function dropdown_menu_js( $element, $trigger, $item ) {
			?>

			<div class="um-new-dropdown" data-element="<?php echo $element; ?>" data-trigger="<?php echo $trigger; ?>">
				<ul>
					<# _.each( <?php echo $item; ?>.dropdown_actions, function( action, key, list ) { #>
						<li><a href="<# if ( typeof action.url != 'undefined' ) { #>{{{action.url}}}<# } else { #>javascript:void(0);<# }#>" class="{{{key}}}">{{{action.title}}}</a></li>
					<# }); #>
				</ul>
			</div>

			<?php
		}



		function default_filter_settings() {
			UM()->admin()->check_ajax_nonce();

			$filter_key = sanitize_key( $_REQUEST['key'] );
			$directory_id = absint( $_REQUEST['directory_id'] );

			$html = $this->show_filter( $filter_key, array( 'form_id' => $directory_id ) );

			wp_send_json_success( array( 'field_html' => $html ) );
		}
	}
}