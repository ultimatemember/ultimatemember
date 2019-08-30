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



		var $filter_supported_fields = array();


		var $filter_types = array(
			'select',
			'slider',
			'datepicker',
			'timepicker',
		);


		/**
		 * @var
		 */
		var $query_args;


		/**
		 * Member_Directory constructor.
		 */
		function __construct() {
			add_filter( 'plugins_loaded', array( &$this, 'init_variables' ), 99999 );
			add_filter( 'um_member_directory_meta_value_before_save', array( &$this, 'before_save_data' ), 10, 3 );
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
				'user_registered_desc'  => __( 'New Users First', 'ultimate-member' ),
				'user_registered_asc'   => __( 'Old Users First', 'ultimate-member' ),
				'username'              => __( 'Username', 'ultimate-member' ),
				'first_name'            => __( 'First Name', 'ultimate-member' ),
				'last_name'             => __( 'Last Name', 'ultimate-member' ),
				'display_name'          => __( 'Display Name', 'ultimate-member' ),
				'last_login'            => __( 'Last Login', 'ultimate-member' ),
			) );

			asort( $this->sort_fields );

			$this->default_sorting = apply_filters( 'um_members_directory_default_sort', array_merge( $this->sort_fields, array(
				'random'    => __( 'Random', 'ultimate-member' ),
				'other'     => __( 'Other (Custom Field)', 'ultimate-member' ),
			) ) );

			asort( $this->default_sorting );



//			<!--			<option value="description">Biography</option>                          to Search-->
//<!--			<option value="user_email">E-mail Address</option>                      to Search-->
//<!--			<option value="first_name">First Name</option>                          to Search-->
//<!--			<option value="last_name">Last Name</option>                            to Search-->
//<!--			<option value="mobile_number">Mobile Number</option>                    to Search-->
//<!--			<option value="nickname">Nickname</option>                              to Search-->
//<!--			<option value="phone_number">Phone Number</option>                      to Search-->
//<!--			<option value="secondary_user_email">Secondary E-mail Address</option>  to Search-->
//<!--			<option value="user_login">Username</option>                            to Search-->
//<!--			<option value="username">Username or E-mail</option> - username         to Search-->
//<!--			<option value="gm">gm</option> - google maps field                      to Search-->
//<!--			<option value="numberr">number</option> - number field                  to Search-->
//<!--			<option value="scm">scm</option> - Soundcloud field                     to Search-->
//<!--			<option value="test">test</option> - text box field                     to Search-->
//<!--			<option value="textareaa">textareaa</option> - textarea field           to Search-->
//<!--			<option value="vimeov">vimeov</option> - Vimeo field                    to Search-->
//<!--			<option value="youtubev">youtubev</option> - Youtube field              to Search-->
//<!--			URL fields                                                              to Search-->
//<!--			Password                                                                skip-->
//<!--			File, Image Upload                                                      maybe search by file,image name-->
//<!---->
//<!---->
//<!--			DatePicker, TimePicker                                                  to Filter-->
//<!--			Rating field                                                            to Filter-->
//<!--			needs to be added 'birth_date' - Age                                    to Filter-->
//<!--			<option value="checkboxx">checkbox</option> - checkbox field            to Filter-->
//<!--			<option value="drop">drop</option> - select field                       to Filter-->
//<!--			<option value="radi">radi</option> - radio field                        to Filter-->
//<!--			<option value="multidrop">multidrop</option> - multiselect field        to Filter-->
//<!--			<option value="role_radio">Roles (Radio)</option> - roles merge         to Filter-->
//<!--			<option value="user_registered">Registration Date</option> -            to Filter-->
//<!--			<option value="gender">Gender</option>                                  to Filter-->
//<!--			<option value="languages">Languages</option>                            to Filter-->
//<!--			<option value="_um_last_login">Last Login</option>                      to Filter-->
//<!--			<option value="country">Country</option>                                to Filter-->
//<!---->
//<!--			So there are next filters:-->
//<!---->
//<!--			Predefined Fields:-->
//<!--			Country, Gender, Age(Birth Date field), Last Login, User Registered-->
//<!--			Languages, Roles (merge dropdown+radio)-->
//<!---->
//<!--			Custom Fields:-->
//<!--			all TimePicker, Datepicker,-->
//<!--			Rating field(by stars), Checkbox, Radio, Select, Multi-select custom fields-->


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

			foreach ( UM()->builtin()->saved_fields as $key => $data ) {
				if ( isset( $data['type'] ) && in_array( $data['type'], $this->filter_supported_fields ) ) {
					if ( array_search( $data['title'], $this->filter_fields ) !== false ) {
						$data['title'] = $data['title'] . ' (' . $key . ')';
					}
					$this->filter_fields[ $key ] = $data['title'];
				}
			}

			$this->filter_fields = apply_filters( 'um_members_directory_filter_fields', $this->filter_fields );

			ksort( $this->filter_fields );
		}


		/**
		 * Render member's directory
		 * filters selectboxes
		 *
		 * @param string $filter
		 * @return string $filter
		 */
		function show_filter( $filter ) {

			$filter_types = apply_filters( 'um_members_directory_filter_types', array(
				'country'           => 'select',
				'gender'            => 'select',
				'languages'         => 'select',
				'role'              => 'select',
				'birth_date'        => 'slider',
				'last_login'        => 'datepicker',
				'user_registered'   => 'datepicker',
			) );

			$field_key = $filter;
			if ( $filter == 'last_login' ) {
				$field_key = '_um_last_login';
			}
			if ( $filter == 'role' ) {
				$field_key = 'role_select';
			}

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

			$filter_types = array_merge( $custom_fields_types, $filter_types );

			if ( empty( $filter_types[ $filter ] ) ) {
				return '';
			}

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
				$attrs = apply_filters( "um_custom_search_field_{$filter}", array() );
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
			$attrs = apply_filters( 'um_search_fields', $attrs );
			ob_start();

			switch ( $filter_types[ $filter ] ) {
				default: {

					do_action( "um_member_directory_filter_type_{$filter_types[ $filter ]}", $filter, $filter_types );

					break;
				}
				case 'select': {

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

									<option value="<?php echo $opt; ?>" data-value_label="<?php esc_attr_e( $v, 'ultimate-member' ); ?>">
										<?php _e( $v, 'ultimate-member' ); ?>
									</option>

								<?php }
							} ?>

						</select>

					<?php }

					break;
				}
				case 'slider': {
					$range = $this->slider_filters_range( $filter );
					$placeholder = $this->slider_range_placeholder( $filter );

					if ( $range ) { ?>
						<input type="hidden" id="<?php echo $filter; ?>_min" name="<?php echo $filter; ?>[]" class="um_range_min" />
						<input type="hidden" id="<?php echo $filter; ?>_max" name="<?php echo $filter; ?>[]" class="um_range_max" />
						<div class="um-slider" data-field_name="<?php echo $filter; ?>" data-min="<?php echo $range[0] ?>" data-max="<?php echo $range[1] ?>"></div>
						<div class="um-slider-range" data-placeholder="<?php echo esc_attr( $placeholder ); ?>" data-label="<?php esc_attr_e( stripslashes( $attrs['label'] ), 'ultimate-member' ); ?>"></div>
					<?php }

					break;
				}
				case 'datepicker': {

					$range = $this->datepicker_filters_range( $filter );

					$label = ! empty( $attrs['label'] ) ? $attrs['label'] : $attrs['title'];

					if ( $range ) { ?>

						<input type="text" id="<?php echo $filter; ?>_from" name="<?php echo $filter; ?>_from" class="um-half-filter um-datepicker-filter"
						       placeholder="<?php esc_attr_e( sprintf( '%s From', stripslashes( $label ) ), 'ultimate-member' ); ?>"
						       data-filter-label="<?php echo esc_attr( stripslashes( $label ) ); ?>"
						       data-date_min="<?php echo $range[0] ?>" data-date_max="<?php echo $range[1] ?>"
						       data-filter_name="<?php echo $filter; ?>" data-range="from" />
						<input type="text" id="<?php echo $filter; ?>_to" name="<?php echo $filter; ?>_to" class="um-half-filter um-datepicker-filter"
						       placeholder="<?php esc_attr_e( sprintf( '%s To', stripslashes( $label ) ), 'ultimate-member' ); ?>"
						       data-filter-label="<?php echo esc_attr( stripslashes( $label ) ); ?>"
						       data-date_min="<?php echo $range[0] ?>" data-date_max="<?php echo $range[1] ?>"
						       data-filter_name="<?php echo $filter; ?>" data-range="to" />

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

						<input type="text" id="<?php echo $filter; ?>_from" name="<?php echo $filter; ?>_from" class="um-half-filter um-timepicker-filter"
						       placeholder="<?php esc_attr_e( sprintf( '%s From', stripslashes( $label ) ), 'ultimate-member' ); ?>"
						       data-filter-label="<?php echo esc_attr( stripslashes( $label ) ); ?>"
						       data-min="<?php echo $range[0] ?>" data-max="<?php echo $range[1] ?>"
						       data-format="<?php echo esc_attr( $js_format ) ?>" data-intervals="<?php echo esc_attr( $attrs['intervals'] ) ?>"
						       data-filter_name="<?php echo $filter; ?>" data-range="from" />
						<input type="text" id="<?php echo $filter; ?>_to" name="<?php echo $filter; ?>_to" class="um-half-filter um-timepicker-filter"
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
		 * @param $filter
		 *
		 * @return mixed
		 */
		function slider_filters_range( $filter ) {


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

					$range = apply_filters( "um_member_directory_filter_{$filter}_slider", $range );

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
		function slider_range_placeholder( $filter ) {
			switch ( $filter ) {
				default: {
					$label = ucwords( str_replace(array('um_', '_'), array('', ' '), $filter) );
					$placeholder = apply_filters( "um_member_directory_filter_{$filter}_slider_range_placeholder", "<strong>$label:</strong> {min_range} - {max_range}" );
					break;
				}
				case 'birth_date': {
					$placeholder = __( '<strong>Age:</strong> {min_range} - {max_range} years old', 'ultimate-member' );
					break;
				}
				case 'user_rating': {
					$placeholder = __( '<strong>User Rating:</strong> {min_range} - {max_range} points', 'ultimate-member' );
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
		 * Must have a profile photo
		 *
		 * @param $args
		 */
		function profile_photo_query( $args ) {
			if ( $args['has_profile_photo'] == 1 ) {
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
		 * Must have a cover photo
		 *
		 * @param $args
		 */
		function cover_photo_query( $args ) {
			if ( $args['has_cover_photo'] == 1 ) {
				$this->query_args['meta_query'] = array_merge( $this->query_args['meta_query'], array( array(
					'key'       => 'cover_photo',
					'value'     => '',
					'compare'   => '!='
				) ) );
			}
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
		 * @param $args
		 */
		function sorting_query( $args ) {
			// sort members by
			$this->query_args['order'] = 'ASC';
			$sortby = ! empty( $_POST['sorting'] ) ? $_POST['sorting'] : $args['sortby'];

			if ( $sortby == 'other' && $args['sortby_custom'] ) {

				$this->query_args['meta_key'] = $args['sortby_custom'];
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


		function general_search() {
			//general search
			if ( ! empty( $_POST['search'] ) ) {
				$this->query_args['meta_query'][] = array(
					array(
						'key'       => 'first_name',
						'value'     => trim( $_POST['search'] ),
						'compare'   => '=',
					),
					array(
						'key'       => 'first_name',
						'value'     => trim( $_POST['search'] ),
						'compare'   => 'LIKE',
					),
					array(
						'key'     => 'first_name',
						'value'     => trim( serialize( strval( $_POST['search'] ) ) ),
						'compare'   => 'LIKE',
					),
					'relation' => 'OR',
				);
			}
		}


		/**
		 * Change mySQL meta query join attribute
		 * for search only by UM user meta fields
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

					preg_match(
						'/^(.*).meta_value LIKE \'%' . esc_attr( $search ) . '%\' [^\)]/im',
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
					$search_where = $context->get_search_sql( $search, UM()->members()->core_search_fields, 'both' );
					$search_where = preg_replace( '/ AND \((.*?)\)/im', " OR $1", $search_where );

					$sql['where'] = $sql['where'] . $search_where;
				}
			}

			return $sql;
		}


		/**
		 *
		 */
		function filters( $args ) {
			//filters
			$filter_query = array();
			if ( ! empty( $args['search_fields'] ) ) {
				$search_filters = maybe_unserialize( $args['search_fields'] );
				if ( ! empty( $search_filters ) && is_array( $search_filters ) ) {
					$filter_query = array_intersect_key( $_POST, array_flip( $search_filters ) );
				}
			}

			if ( empty( $filter_query ) ) {
				return;
			}

			$query = $_POST;

			foreach ( $filter_query as $field => $value ) {

				switch ( $field ) {
					default:
						$value = explode( '||', $value );

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
						$field_query = apply_filters( "um_query_args_{$field}__filter", $field_query );
						$this->query_args['meta_query'] = array_merge( $this->query_args['meta_query'], array( $field_query ) );

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
				}



//				if ( 'role' == $field ) {
//
//					if ( ! empty( $this->query_args['role__in'] ) ) {
//						$value = array_map('strtolower', $value);
//
//						$this->query_args['role__in'] = is_array( $this->query_args['role__in'] ) ? $this->query_args['role__in'] : array( $this->query_args['role__in'] );
//						$default_role = array_intersect( $this->query_args['role__in'], $value );
//						$um_role = array_diff( $value, $default_role );
//
//						foreach ($um_role as $key => &$val) {
//							$val = 'um_' . str_replace(' ', '-', $val);
//						}
//						$this->query_args['role__in'] = array_merge( $default_role, $um_role );
//					} else {
//						$this->query_args['role__in'] = $value;
//					};
//
//				} elseif ( 'birth_date' == $field ) {
//					$from_date = date( 'Y-m-d', mktime( 0,0,0, 1, 1, date('Y', time() - ($query['birth_date'][0] -1)*YEAR_IN_SECONDS ) ) );
//					$to_date = date( 'Y-m-d', mktime( 0,0,0, 1, 1, date('Y', time() - ($query['birth_date'][1] +1)*YEAR_IN_SECONDS ) ) );
//
//					$meta_query = array(
//						array(
//							'key'       => 'birth_date',
//							'value'     => array( $to_date, $from_date ),
//							'compare'   => 'BETWEEN',
//							'type'      => 'DATE',
//							'inclusive'	=> true,
//						)
//					);
//
//					$this->query_args['meta_query'] = array_merge( $this->query_args['meta_query'], array( $meta_query ) );
//
//				} elseif ( 'user_registered' == $field ) {
//
//					$offset = 0;
//
//					if( isset( $query['gmt_offset'] ) ) {
//						$offset = (int)$query['gmt_offset'];
//					}
//
//					if( isset( $query['user_registered']['from'] ) ) {
//						$from_date = date( 'Y-m-d', strtotime( date( 'Y-m-d H:s:i', $query['user_registered']['from'] ) . "+$offset hours" ) );
//					}
//
//					if( isset( $query['user_registered']['to'] ) ) {
//						$to_date = date( 'Y-m-d', strtotime( date( 'Y-m-d H:s:i', $query['user_registered']['to'] ) . "+$offset hours" ) );
//					}
//
//					$date_query = array(
//						array(
//							'column'	=> 'user_registered',
//							'before'	=> $to_date,
//							'after'		=> $from_date,
//							'inclusive'	=> true,
//						),
//					);
//
//					$this->query_args['date_query'] = array( $date_query );
//
//				} elseif ( 'last_login' == $field ) {
//
//					$meta_query = array();
//					$offset		= 0;
//
//					if( isset( $query['gmt_offset'] ) ) {
//						$offset = (int)$query['gmt_offset'];
//					}
//
//					if( isset( $query['last_login']['from'] ) and isset( $query['last_login']['to'] ) ) {
//						$from_date = (int)$query['last_login']['from'] + ( $offset * 60 * 60 ); // client time zone offset
//						$to_date   = (int)$query['last_login']['to'] + ( $offset * 60 * 60 ) + (24 * 60 * 60 - 1); // time 23:59
//
//						$meta_query[] = array(
//							'key'       => '_um_last_login',
//							'value'     =>  array( $from_date, $to_date ),
//							'compare'   => 'BETWEEN',
//						);
//
//					} else {
//
//						if( isset( $query['last_login']['from'] ) ) {
//							$from_date = (int)$query['last_login']['from'] + ( $offset * 60 * 60 );
//
//							$meta_query[] = array(
//								'key'       => '_um_last_login',
//								'value'     =>  $from_date,
//								'compare'   => '>',
//							);
//						}
//
//						if( isset( $query['last_login']['to'] ) ) {
//							$to_date = (int)$query['last_login']['to'] + ( $offset * 60 * 60 ) + (24 * 60 * 60 - 1);
//
//							$meta_query[] = array(
//								'key'       => '_um_last_login',
//								'value'     =>  $to_date,
//								'compare'   => '<',
//							);
//						}
//					}
//
//					$this->query_args['meta_query'] = array_merge( $this->query_args['meta_query'], array( $meta_query ) );
//
//				} elseif( 'gmt_offset' == $field ) {
//					continue;
//				} else {
//
//					if ( is_array( $value ) ) {
//						$field_query = array( 'relation' => 'OR' );
//
//						foreach ( $value as $single_val ) {
//							$field_query = array_merge( $field_query, array(
//								array(
//									'key'       => $field,
//									'value'     => trim( $single_val ),
//									'compare'   => '=',
//								)
//							) );
//
//							$types = apply_filters( 'um_search_field_types', array(
//								'multiselect',
//								'radio',
//								'checkbox'
//							) );
//
//							//if ( in_array( $filter_data['attrs']['type'], $types ) ) {
//
//							$arr_meta_query = array(
//								array(
//									'key' => $field,
//									'value' => serialize( strval( trim( $single_val ) ) ),
//									'compare' => 'LIKE',
//								),
//								array(
//									'key' => $field,
//									'value' => '"' . trim( $single_val ) . '"',
//									'compare' => 'LIKE',
//								)
//							);
//
//							if ( is_numeric( $single_val ) ) {
//
//								$arr_meta_query[ ] = array(
//									'key' => $field,
//									'value' => serialize( intval( trim( $single_val ) ) ),
//									'compare' => 'LIKE',
//								);
//
//							}
//
//							$field_query = array_merge( $field_query, $arr_meta_query );
//							//}
//						}
//					} else {
//						$field_query = array(
//							array(
//								'key' => $field,
//								'value' => trim( $value ),
//								'compare' => '=',
//							),
//							'relation' => 'OR',
//						);
//
//						$types = apply_filters( 'um_search_field_types', array(
//							'multiselect',
//							'radio',
//							'checkbox'
//						) );
//
//						//if ( in_array( $filter_data['attrs']['type'], $types ) ) {
//
//						$arr_meta_query = array(
//							array(
//								'key' => $field,
//								'value' => serialize( strval( trim( $value ) ) ),
//								'compare' => 'LIKE',
//							),
//							array(
//								'key' => $field,
//								'value' => '"' . trim( $value ) . '"',
//								'compare' => 'LIKE',
//							)
//						);
//
//						if ( is_numeric( $value ) ) {
//
//							$arr_meta_query[ ] = array(
//								'key' => $field,
//								'value' => serialize( intval( trim( $value ) ) ),
//								'compare' => 'LIKE',
//							);
//
//						}
//
//						$field_query = array_merge( $field_query, $arr_meta_query );
//						//}
//					}
//
//					$field_query = apply_filters( "um_query_args_{$field}__filter", $field_query );
//					$this->query_args['meta_query'] = array_merge( $this->query_args['meta_query'], array( $field_query ) );
//				}

			}
		}


		function get_directory_by_hash( $hash ) {
			global $wpdb;

			$directory_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE SUBSTRING( MD5( ID ), 11, 5 ) = %s", $hash ) );

			if ( empty( $directory_id ) ) {
				return false;
			}

			return (int) $directory_id;
		}


		function get_type_basename( $type ) {
			return apply_filters( "um_member_directory_{$type}_type_template_basename", '' );
		}


		/**
		 * get AJAX results members
		 */
		function ajax_get_members() {
			UM()->check_ajax_nonce();

			global $wpdb;

			$args = ! empty( $_POST['args'] ) ? $_POST['args'] : array();
			$args['page'] = ! empty( $_POST['page'] ) ? $_POST['page'] : ( isset( $args['page'] ) ? $args['page'] : 1 );

			$directory_data = array();
			$directory_id = $this->get_directory_by_hash( $_POST['directory_id'] );

			$post_data = UM()->query()->post_data( $directory_id );

			$args = array_merge( $post_data, $args );

			/**
			 * @var $profiles_per_page
			 * @var $profiles_per_page_mobile
			 * @var $header
			 * @var $header_single
			 * @var $has_profile_photo
			 * @var $has_cover_photo
			 */
			extract( $args );

			$data_args = array(
				'show_count' => false
			);
			if ( ! empty( $_POST['search'] ) || ! empty( $_POST['is_filters'] ) || ! empty( $args['search_filters'] ) ) {
				$data_args['show_count'] = true;
			}

			$this->query_args = array(
				'fields' => 'ids',
				'number' => 0,
				'meta_query' => array(
					'relation' => 'AND'
				),
			);

			// add roles to appear in directory
			if ( ! empty( $args['roles'] ) ) {
				//since WP4.4 use 'role__in' argument
				$this->query_args['role__in'] = $args['roles'];
			}

			$this->profile_photo_query( $args );

			$this->cover_photo_query( $args );

			// show specific usernames
			if ( ! empty( $args['show_these_users'] ) && is_array( $args['show_these_users'] ) ) {
				foreach ( $args['show_these_users'] as $username ) {
					if ( false !== ( $exists_id = username_exists( $username ) ) ) {
						$users_array[] = $exists_id;
					}
				}

				$this->query_args['include'] = $users_array;
			}

			$this->hide_not_approved();

			$this->hide_by_role();

			$this->hide_by_account_settings();

			$this->sorting_query( $args );

			$this->general_search();

			$this->filters( $args );

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
			$this->query_args = apply_filters( 'um_prepare_user_query_args', $this->query_args, $args );

			//unset empty meta_query attribute
			if ( isset( $this->query_args['meta_query']['relation'] ) && count( $this->query_args['meta_query'] ) == 1 ) {
				unset( $this->query_args['meta_query'] );
			}

			// Prepare for BIG SELECT query
			$wpdb->query( 'SET SQL_BIG_SELECTS=1' );

			// number of profiles for mobile
			$profiles_per_page = $args['profiles_per_page'];
			if ( UM()->mobile()->isMobile() && isset( $args['profiles_per_page_mobile'] ) ) {
				$profiles_per_page = $args['profiles_per_page_mobile'];
			}

			$this->query_args['number'] = isset( $args['number'] ) ? $args['number'] : $profiles_per_page;
			$this->query_args['number'] = ( ! empty( $args['max_users'] ) && $args['max_users'] <= $profiles_per_page ) ? $args['max_users'] : $this->query_args['number'];

			$current_page = isset( $args['page'] ) ? $args['page'] : 1;
			$this->query_args['paged'] = $current_page;

			if ( ! UM()->roles()->um_user_can( 'can_view_all' ) && is_user_logged_in() ) {
				$this->query_args = array();
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

			$users = new \WP_User_Query( $this->query_args );

			//var_dump($users->request);

			remove_filter( 'get_meta_sql', array( &$this, 'change_meta_sql' ), 10 );

			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_user_after_query
			 * @description Action before users query on member directory
			 * @input_vars
			 * [{"var":"$query_args","type":"array","desc":"Query arguments"},
			 * {"var":"$users","type":"array","desc":"Users"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_user_after_query', 'function_name', 10, 2 );
			 * @example
			 * <?php
			 * add_action( 'um_user_after_query', 'my_user_after_query', 10, 2 );
			 * function my_user_after_query( $query_args, $users ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_user_after_query', $this->query_args, $users );

			$user_ids = ! empty( $users->results ) ? array_unique( $users->results ) : array();
			$total_users = ( ! empty( $max_users ) && $max_users <= $users->total_users ) ? $max_users : $users->total_users;
			$total_pages = ceil( $total_users / $profiles_per_page );

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
			$users = apply_filters( 'um_prepare_user_results_array', $user_ids );

			$sizes = UM()->options()->get( 'cover_thumb_sizes' );
			$cover_size = UM()->mobile()->isTablet() ? $sizes[1] : $sizes[0];

			$users_data = array();
			foreach ( $users as $user_id ) {
				um_fetch_user( $user_id );

				$actions = array();
				if ( UM()->roles()->um_current_user_can( 'edit', $user_id ) || UM()->roles()->um_user_can( 'can_edit_everyone' ) ) {
					$actions[] = array(
						'title'         => __( 'Edit profile', 'ultimate-member' ),
						'url'           => um_edit_profile_url(),
						'wrapper_class' => 'um-members-edit-btn',
						'class'         => 'um-edit-profile-btn um-button um-alt',
					);
				}

				// Replace hook 'um_members_just_after_name'
				ob_start();
				do_action( 'um_members_just_after_name', $user_id, $args );
				$hook_just_after_name = ob_get_clean();

				// Replace hook 'um_members_after_user_name'
				ob_start();
				do_action( 'um_members_after_user_name', $user_id, $args );
				$hook_after_user_name = ob_get_clean();

				ob_start();
				UM()->fields()->show_social_urls();
				$social_urls = ob_get_clean();

				$data_array = array(
					'id'                    => $user_id,
					'role'                  => um_user( 'role' ),
					'account_status'        => um_user( 'account_status' ),
					'account_status_name'   => um_user( 'account_status_name' ),
					'cover_photo'           => um_user( 'cover_photo', $cover_size ),
					'display_name'          => um_user( 'display_name' ),
					'profile_url'           => um_user_profile_url(),
					'can_edit'              => ( UM()->roles()->um_current_user_can( 'edit', $user_id ) || UM()->roles()->um_user_can( 'can_edit_everyone' ) ) ? true : false,
					'edit_profile_url'      => um_edit_profile_url(),
					'avatar'                => get_avatar( $user_id, str_replace( 'px', '', UM()->options()->get( 'profile_photosize' ) ) ),
					'display_name_html'     => um_user( 'display_name', 'html' ),
					'social_urls'           => $social_urls,
					'actions'               => $actions,
					'hook_just_after_name'  => preg_replace('/^\s+/im', '', $hook_just_after_name),
					'hook_after_user_name'  => preg_replace('/^\s+/im', '', $hook_after_user_name),
				);

				if ( $args['show_tagline'] && is_array( $args['tagline_fields'] ) ) {
					foreach ( $args['tagline_fields'] as $key ) {
						if ( $key && um_filtered_value( $key ) ) {
							$data_array[ $key ] = um_filtered_value( $key );
						}
					}
				}

				if ( $args['show_userinfo'] ) {
					foreach ( $args['reveal_fields'] as $key ) {
						if ( $key && um_filtered_value( $key ) ) {
							$label = strtr( UM()->fields()->get_label( $key ), array(
								' (Dropdown)' => '',
								' (Radio)' => ''
							) );
							$data_array[ "label_{$key}" ] = $label;
							$data_array[ $key ] = um_filtered_value( $key );
						}
					}
				}

				$users_data[] = apply_filters( 'um_ajax_get_members_data', $data_array, $user_id );
				um_reset_user_clean();
			}

			um_reset_user();

			$pagination_data = array(
				'pages_to_show' => ( ! empty( $pages_to_show ) && count( $pages_to_show ) > 1 ) ? array_values( $pages_to_show ) : array(),
				'current_page'  => $args['page'],
				'total_pages'   => $total_pages,
				'total_users'   => $total_users,
			);

			$pagination_data['header'] = $this->convert_tags( $args['header'], $pagination_data );
			$pagination_data['header_single'] = $this->convert_tags( $args['header_single'], $pagination_data );

			wp_send_json_success( array( 'users' => $users_data, 'pagination' => $pagination_data, 'args' => $data_args ) );
		}


		/**
		 * Tag conversion for member directory
		 *
		 * @param $string
		 * @param $array
		 *
		 * @return mixed
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
	}
}