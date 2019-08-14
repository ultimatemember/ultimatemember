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
	}
}