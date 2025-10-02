<?php
namespace um\common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// @todo disable_filters_pre_query for displaying all filter fields and options
// @todo admin filtering (getting filters like `disable_filters_pre_query` is active)

// @todo directory enhancements
// var sort_fields
// var filter_fields
// var filter_types
// var view_types
// var sort_data_types
// var core_search_fields
// var searching_fields
// var default_sorting
// var custom_filters_in_query - common marker about filtration
// var query_args - common user query args


// method ajax_get_members    - ajax only
// method default_filter_settings    - ajax only
// method show_filter    - ajax/admin and different for frontend
// method before_save_data - admin save data handler

// method get_type_basename - maybe can be deprecated

// method get_hide_in_members_default - can be static and common
// method get_member_directory_id - can be static and common
// method get_directory_hash - can be static and common

// method dropdown_menu_js - can be static and common only for v2
// method dropdown_menu - can be static and common only for v2
// method calculate_pagination - can be static and common only for v2
// method convert_tags - can be static and common only for v2

/**
 * Class Directory_Config
 *
 * @package um\common
 */
class Directory_Config {

	/**
	 * Member Directory Views
	 *
	 * @var array
	 */
	public $view_types = array();

	/**
	 * @var array
	 */
	public $sort_fields = array();

	/**
	 * @var array
	 */
	public $sorting_supported_fields = array();

	/**
	 * @var array
	 */
	public $sort_data_types = array();

	/**
	 * @var array
	 */
	public $default_sorting = array();

	/**
	 * @var array
	 */
	public $filter_supported_fields = array();

	/**
	 * @var array
	 */
	public $filter_fields = array();

	/**
	 * @var array
	 */
	public $filter_types = array();

	/**
	 * Fields used for searching from wp_users table.
	 *
	 * @var string[]
	 */
	public static $core_search_fields = array(
		'user_login',
		'user_url',
		'display_name',
		'user_email',
		'user_nicename',
	);

	/**
	 * @var array
	 */
	public $searching_fields = array();

	/**
	 * @var string User Card cover size
	 */
	public $cover_size;


	/**
	 * @var string User Avatar size
	 */
	public $avatar_size;

	/**
	 * Directory constructor.
	 */
	public function __construct() {
		add_filter( 'init', array( &$this, 'init_variables' ) );
	}

	/**
	 *
	 */
	public function init_variables() {
		$this->init_view_types();
		$this->init_sorting();
		$this->init_filters(); // Filters init should be before searching init.
		$this->init_searching();
	}

	private function init_view_types() {
		// Types
		if ( UM()->is_new_ui() ) {
			$this->view_types = array(
				'grid' => __( 'Grid', 'ultimate-member' ),
				'list' => __( 'List', 'ultimate-member' ),
			);
		} else {
			$this->view_types = array(
				'grid' => array(
					'title' => __( 'Grid', 'ultimate-member' ),
					'icon'  => 'um-faicon-th',
				),
				'list' => array(
					'title' => __( 'List', 'ultimate-member' ),
					'icon'  => 'um-faicon-list',
				),
			);
		}
		$this->view_types = apply_filters( 'um_member_directory_views', $this->view_types );
	}

	private function init_sorting() {
		$this->sorting_supported_fields = apply_filters( 'um_members_directory_custom_field_types_supported_sorting', array( 'number' ) );

		// Sort
		$this->sort_fields = array(
			'user_registered_desc' => __( 'New users first', 'ultimate-member' ),
			'user_registered_asc'  => __( 'Old users first', 'ultimate-member' ),
			'username'             => __( 'Username', 'ultimate-member' ),
			'nickname'             => __( 'Nickname', 'ultimate-member' ),
			'first_name'           => __( 'First name', 'ultimate-member' ),
			'last_name'            => __( 'Last name', 'ultimate-member' ),
			'display_name'         => __( 'Display name', 'ultimate-member' ),
			'last_first_name'      => __( 'Last & First name', 'ultimate-member' ),
			'last_login'           => __( 'Last login', 'ultimate-member' ),
		);

		if ( ! empty( UM()->builtin()->saved_fields ) ) {
			foreach ( UM()->builtin()->saved_fields as $key => $data ) {
				if ( '_um_last_login' === $key ) {
					continue;
				}

				if ( isset( $data['type'] ) && in_array( $data['type'], $this->sorting_supported_fields ) ) {
					// translators: %s: title.
					if ( isset( $data['title'] ) && array_search( sprintf( __( '%s DESC', 'ultimate-member' ), $data['title'] ), $this->sort_fields ) !== false ) {
						$data['title'] = $data['title'] . ' (' . $key . ')';
					}

					$title = isset( $data['title'] ) ? $data['title'] : ( isset( $data['label'] ) ? $data['label'] : '' );
					if ( empty( $title ) ) {
						continue;
					}

					// translators: %s: title.
					$this->sort_fields[ $key . '_desc' ] = sprintf( __( '%s DESC', 'ultimate-member' ), $title );
					// translators: %s: title.
					$this->sort_fields[ $key . '_asc' ] = sprintf( __( '%s ASC', 'ultimate-member' ), $title );
				}
			}
		}

		$this->sort_fields = apply_filters( 'um_members_directory_sort_fields', $this->sort_fields );
		asort( $this->sort_fields );

		$this->default_sorting = array_merge(
			$this->sort_fields,
			array(
				'random' => __( 'Random', 'ultimate-member' ),
				'other'  => __( 'Other (Custom Field)', 'ultimate-member' ),
			)
		);
		$this->default_sorting = apply_filters( 'um_members_directory_default_sort', $this->default_sorting );
		asort( $this->default_sorting );

		$this->sort_data_types = array(
			'CHAR'     => __( 'CHAR', 'ultimate-member' ),
			'NUMERIC'  => __( 'NUMERIC', 'ultimate-member' ),
			'BINARY'   => __( 'BINARY', 'ultimate-member' ),
			'DATE'     => __( 'DATE', 'ultimate-member' ),
			'DATETIME' => __( 'DATETIME', 'ultimate-member' ),
			'DECIMAL'  => __( 'DECIMAL', 'ultimate-member' ),
			'SIGNED'   => __( 'SIGNED', 'ultimate-member' ),
			'TIME'     => __( 'TIME', 'ultimate-member' ),
			'UNSIGNED' => __( 'UNSIGNED', 'ultimate-member' ),
		);

		$this->sort_data_types = apply_filters( 'um_members_directory_sort_data_types', $this->sort_data_types );
	}

	private function init_filters() {
		$this->filter_supported_fields = array(
			'date',
			'time',
			'select',
			'multiselect',
			'radio',
			'checkbox',
			'rating',
			'text',
			'textarea',
			'number',
		);
		$this->filter_supported_fields = apply_filters( 'um_members_directory_custom_field_types_supported_filter', $this->filter_supported_fields );

		// Filters
		$this->filter_fields = array(
			'country'              => __( 'Country', 'ultimate-member' ),
			'gender'               => __( 'Gender', 'ultimate-member' ),
			'languages'            => __( 'Languages', 'ultimate-member' ),
			'role'                 => __( 'Roles', 'ultimate-member' ),
			'birth_date'           => __( 'Age', 'ultimate-member' ),
			'last_login'           => __( 'Last Login', 'ultimate-member' ),
			'user_registered'      => __( 'User Registered', 'ultimate-member' ),
			'first_name'           => __( 'First Name', 'ultimate-member' ),
			'last_name'            => __( 'Last Name', 'ultimate-member' ),
			'nickname'             => __( 'Nickname', 'ultimate-member' ),
			'secondary_user_email' => __( 'Secondary Email Address', 'ultimate-member' ),
			'description'          => __( 'Biography', 'ultimate-member' ),
			'phone_number'         => __( 'Phone Number', 'ultimate-member' ),
			'mobile_number'        => __( 'Mobile Number', 'ultimate-member' ),
		);

		if ( ! empty( UM()->builtin()->saved_fields ) ) {
			foreach ( UM()->builtin()->saved_fields as $key => $data ) {
				if ( '_um_last_login' === $key ) {
					continue;
				}

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

		$this->filter_types = array(
			'country'              => 'select',
			'gender'               => 'select',
			'languages'            => 'select',
			'role'                 => 'select',
			'birth_date'           => 'slider',
			'last_login'           => 'datepicker',
			'user_registered'      => 'datepicker',
			'first_name'           => 'text',
			'last_name'            => 'text',
			'nickname'             => 'text',
			'secondary_user_email' => 'text',
			'description'          => 'text',
			'phone_number'         => 'text',
			'mobile_number'        => 'text',
		);
		$this->filter_types = apply_filters( 'um_members_directory_filter_types', $this->filter_types );

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
					case 'text':
					case 'textarea':
						$value = 'text';
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
					case 'number':
					case 'rating':
						$value = 'slider';
						break;
				}
			}
		}
		unset( $value );

		$this->filter_types = array_merge( $custom_fields_types, $this->filter_types );
	}

	private function init_searching() {
		$core_search_keys = $this->get_core_search_fields();
		if ( ! empty( UM()->builtin()->all_user_fields() ) ) {
			foreach ( UM()->builtin()->all_user_fields() as $key => $data ) {
				if ( in_array( $key, $core_search_keys, true ) ) {
					if ( isset( $data['title'] ) && in_array( $data['title'], $this->searching_fields, true ) ) {
						$data['title'] = $data['title'] . ' (' . $key . ')';
					}

					$title = isset( $data['title'] ) ? $data['title'] : ( isset( $data['label'] ) ? $data['label'] : '' );
					if ( empty( $title ) ) {
						continue;
					}

					$this->searching_fields[ $key ] = $title;
				}
			}
		}

		$this->searching_fields = array_merge( $this->searching_fields, $this->filter_fields );
		asort( $this->searching_fields );
	}

	protected function init_image_sizing( $directory_data ) {
		$sizes = UM()->options()->get( 'cover_thumb_sizes' );
		// Ensure we have valid sizes array and handle case when only one size is defined
		if ( ! is_array( $sizes ) || empty( $sizes ) ) {
			$sizes = array( 300 ); // fallback to default
		}

		// For mobile, use second size if available, otherwise use first size
		$available_mobile = isset( $sizes[1] ) ? $sizes[1] : $sizes[0];
		$this->cover_size = wp_is_mobile() ? $available_mobile : end( $sizes );
		$this->cover_size = apply_filters( 'um_member_directory_cover_image_size', $this->cover_size, $directory_data );

		$avatar_size       = UM()->options()->get( 'profile_photosize' );
		$this->avatar_size = str_replace( 'px', '', $avatar_size );
		$this->avatar_size = apply_filters( 'um_member_directory_avatar_image_size', $this->avatar_size, $directory_data );
	}

	/**
	 * Get the WordPress core searching fields in wp_users query.
	 * @since 2.6.10
	 * @since 2.10.2 Added $qv and $search params
	 * @version 3.0.0
	 *
	 * @param array|null  $qv     WP_User_Query variables.
	 * @param string|null $search Search line value.
	 * @return array
	 */
	protected function get_core_search_fields( $qv = null, $search = null ) {
		$search_columns = array();
		if ( ! is_null( $qv ) && ! empty( $search ) ) {
			// WordPress native code from wp-includes/class-wp-user-query.php is below in this condition.
			if ( $qv['search_columns'] ) {
				$search_columns = array_intersect( $qv['search_columns'], array( 'ID', 'user_login', 'user_email', 'user_url', 'user_nicename', 'display_name' ) );
			}
			if ( ! $search_columns ) {
				if ( str_contains( $search, '@' ) ) {
					$search_columns = array( 'user_email' );
				} elseif ( is_numeric( $search ) ) {
					$search_columns = array( 'user_login', 'ID' );
				} elseif ( preg_match( '|^https?://|', $search ) && ! ( is_multisite() && wp_is_large_network( 'users' ) ) ) {
					$search_columns = array( 'user_url' );
				} else {
					$search_columns = array( 'user_login', 'user_url', 'user_email', 'user_nicename', 'display_name' );
				}
			}
			/** This filter is documented in wp-includes/class-wp-user-query.php */
			$search_columns = apply_filters( 'user_search_columns', $search_columns, $search, $this );
		} else {
			$search_columns = self::$core_search_fields;
		}
		/**
		 * Filters the WordPress core searching fields in wp_users query for UM Member directory query.
		 *
		 * @param {array} $core_search_fields Core search fields in wp_users query.
		 *
		 * @return {array} Core search fields in wp_users query.
		 *
		 * @since 2.6.10
		 * @hook um_member_directory_core_search_fields
		 *
		 * @example <caption>Extends or remove wp_users core search fields.</caption>
		 * function my_um_member_directory_core_search_fields( $core_search_fields ) {
		 *     $core_search_fields = array_flip( $core_search_fields );
		 *     unset( $core_search_fields['user_email'] );
		 *     $core_search_fields = array_flip( $core_search_fields );
		 *     return $core_search_fields;
		 * }
		 * add_filter( 'um_member_directory_core_search_fields', 'my_um_member_directory_core_search_fields' );
		 */
		return apply_filters( 'um_member_directory_core_search_fields', $search_columns );
	}
}
