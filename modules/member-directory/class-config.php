<?php
namespace umm\member_directory;


if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class Config
 *
 * @package umm\member_directory
 */
class Config {


	/**
	 * @var array
	 */
	var $core_search_fields = array();


	/**
	 * @var array
	 */
	var $filter_fields = array();


	/**
	 * @var array
	 */
	var $filter_types = array();


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
	var $sorting_supported_fields = array();


	/**
	 * @var array
	 */
	var $default_sorting = array();


	/**
	 * @var array
	 */
	var $default_member_directory_meta = array();


	/**
	 * Config constructor.
	 */
	function __construct() {
	}


	/**
	 * Get variable from config
	 *
	 * @param string $key
	 *
	 * @return mixed
	 *
	 * @since 3.0
	 */
	function get( $key ) {
		if ( empty( $this->$key ) ) {
			call_user_func( array( &$this, 'init_' . $key ) );
		}
		return apply_filters( 'um_member_directory_config_get', $this->$key, $key );
	}


	/**
	 *
	 */
	public function init_core_search_fields() {
		$this->core_search_fields = array(
			'user_login',
			'user_url',
			'display_name',
			'user_email',
			'user_nicename',
		);
	}


	/**
	 *
	 */
	public function init_view_types() {
		// Types
		$this->view_types = apply_filters(
			'um_member_directory_views',
			array(
				'grid' => __( 'Grid', 'ultimate-member' ),
				'list' => __( 'List', 'ultimate-member' ),
			)
		);
	}


	/**
	 *
	 */
	public function init_sorting_supported_fields() {
		$this->sorting_supported_fields = apply_filters(
			'um_members_directory_custom_field_types_supported_sorting',
			array( 'number' )
		);
	}


	/**
	 *
	 */
	public function init_sort_fields() {
		$this->sort_fields = apply_filters(
			'um_members_directory_sort_fields',
			array(
				'user_registered_desc' => __( 'New users first', 'ultimate-member' ),
				'user_registered_asc'  => __( 'Old users first', 'ultimate-member' ),
				'username'             => __( 'Username', 'ultimate-member' ),
				'nickname'             => __( 'Nickname', 'ultimate-member' ),
				'first_name'           => __( 'First name', 'ultimate-member' ),
				'last_name'            => __( 'Last name', 'ultimate-member' ),
				'display_name'         => __( 'Display name', 'ultimate-member' ),
				'last_first_name'      => __( 'Last & First name', 'ultimate-member' ),
				'last_login'           => __( 'Last login', 'ultimate-member' ),
			)
		);

		if ( ! empty( UM()->builtin()->saved_fields ) ) {
			foreach ( UM()->builtin()->saved_fields as $key => $data ) {
				if ( $key == '_um_last_login' ) {
					continue;
				}

				if ( isset( $data['type'] ) && in_array( $data['type'], $this->get( 'sorting_supported_fields' ) ) ) {
					if ( isset( $data['title'] ) && array_search( sprintf( __( '%s DESC', 'ultimate-member' ), $data['title'] ), $this->sort_fields ) !== false ) {
						$data['title'] = $data['title'] . ' (' . $key . ')';
					}

					$title = isset( $data['title'] ) ? $data['title'] : ( isset( $data['label'] ) ? $data['label'] : '' );
					if ( empty( $title ) ) {
						continue;
					}

					$this->sort_fields[ $key . '_desc' ] = sprintf( __( '%s DESC', 'ultimate-member' ), $title );
					$this->sort_fields[ $key . '_asc' ]  = sprintf( __( '%s ASC', 'ultimate-member' ), $title );
				}
			}
		}

		asort( $this->sort_fields );
	}


	/**
	 *
	 */
	public function init_default_sorting() {
		$this->default_sorting = array_merge(
			$this->get( 'sort_fields' ),
			array(
				'random' => __( 'Random', 'ultimate-member' ),
				'other'  => __( 'Other (Custom Field)', 'ultimate-member' ),
			)
		);

		$this->default_sorting = apply_filters( 'um_members_directory_default_sort', $this->default_sorting );

		asort( $this->default_sorting );
	}


	/**
	 *
	 */
	public function init_filter_fields() {
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
			'secondary_user_email' => __( 'Secondary E-mail Address', 'ultimate-member' ),
			'description'          => __( 'Biography', 'ultimate-member' ),
			'phone_number'         => __( 'Phone Number', 'ultimate-member' ),
			'mobile_number'        => __( 'Mobile Number', 'ultimate-member' ),
		);

		$filter_supported_fields = apply_filters(
			'um_members_directory_custom_field_types_supported_filter',
			array(
				'date',
				'time',
				'select',
				'multiselect',
				'radio',
				'checkbox',
				'rating',
				'text',
				'textarea',
				'number'
			)
		);

		if ( ! empty( UM()->builtin()->saved_fields ) ) {
			foreach ( UM()->builtin()->saved_fields as $key => $data ) {

				if ( $key == '_um_last_login' ) {
					continue;
				}

				if ( isset( $data['type'] ) && in_array( $data['type'], $filter_supported_fields ) ) {
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
	public function init_filter_types() {
		$this->filter_types = apply_filters(
			'um_members_directory_filter_types',
			array(
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
			)
		);

		$fields = UM()->builtin()->all_user_fields;

		$custom_fields_types = array_flip( array_keys( $this->get( 'filter_fields' ) ) );
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

		$this->filter_types = array_merge( $custom_fields_types, $this->filter_types );
	}


	/**
	 * Init default member directory meta
	 *
	 * @since 3.0
	 */
	public function init_default_member_directory_meta() {
		$this->default_member_directory_meta = array(
			'_um_core'                     => 'members',
			'_um_template'                 => 'members',
			'_um_mode'                     => 'directory',
			'_um_view_type'                => 'grid',
			'_um_grid_columns'             => 3,
			'_um_roles'                    => array(),
			'_um_has_profile_photo'        => 0,
			'_um_has_cover_photo'          => 0,
			'_um_show_these_users'         => '',
			'_um_exclude_these_users'      => '',
			'_um_sortby'                   => 'user_registered_desc',
			'_um_sortby_custom'            => '',
			'_um_sortby_custom_label'      => '',
			'_um_enable_sorting'           => 0,
			'_um_sorting_fields'           => array(),
			'_um_profile_photo'            => 1,
			'_um_cover_photos'             => 1,
			'_um_show_name'                => 1,
			'_um_show_tagline'             => 0,
			'_um_tagline_fields'           => array(),
			'_um_show_userinfo'            => 0,
			'_um_reveal_fields'            => array(),
			'_um_show_social'              => 0,
			'_um_search'                   => 0,
			'_um_roles_can_search'         => array(),
			'_um_filters'                  => 0,
			'_um_roles_can_filter'         => array(),
			'_um_search_fields'            => array(),
			'_um_filters_expanded'         => 0,
			'_um_filters_is_collapsible'   => 1,
			'_um_search_filters'           => array(),
			'_um_must_search'              => 0,
			'_um_max_users'                => '',
			'_um_profiles_per_page'        => 12,
			'_um_profiles_per_page_mobile' => 6,
			'_um_directory_header'         => __( '{total_users} Members', 'ultimate-member' ),
			'_um_directory_header_single'  => __( '{total_users} Member', 'ultimate-member' ),
			'_um_directory_no_users'       => __( 'We are sorry. We cannot find any users who match your search criteria.', 'ultimate-member' ),
		);

		$this->default_member_directory_meta = apply_filters( 'um_default_member_directory_meta', $this->default_member_directory_meta );
	}
}
