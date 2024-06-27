<?php
namespace um\common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Directory
 *
 * @package um\common
 */
class Directory {

	/**
	 * Fields used for searching from wp_users table.
	 *
	 * @var string[]
	 */
	public $core_search_fields = array(
		'user_login',
		'user_url',
		'display_name',
		'user_email',
		'user_nicename',
	);

	/**
	 * @var array
	 */
	public $filter_types = array();

	/**
	 * @var array
	 */
	public $filter_fields = array();

	/**
	 * @var array
	 */
	public $searching_fields = array();

	/**
	 * @var array
	 */
	public $filter_supported_fields = array();

	public $cover_size;

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

		$this->filter_supported_fields = apply_filters( 'um_members_directory_custom_field_types_supported_filter', array( 'date', 'time', 'select', 'multiselect', 'radio', 'checkbox', 'rating', 'text', 'textarea', 'number' ) );

		$core_search_keys = $this->get_core_search_fields();

		$this->searching_fields = array();
		if ( ! empty( UM()->builtin()->all_user_fields() ) ) {
			foreach ( UM()->builtin()->all_user_fields() as $key => $data ) {
				if ( in_array( $key, $core_search_keys, true ) ) {
					if ( isset( $data['title'] ) && array_search( $data['title'], $this->searching_fields, true ) !== false ) {
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

		$this->searching_fields = array_merge( $this->searching_fields, $this->filter_fields );
		asort( $this->searching_fields );

		$this->filter_types = apply_filters( 'um_members_directory_filter_types', array(
			'country'               => 'select',
			'gender'                => 'select',
			'languages'             => 'select',
			'role'                  => 'select',
			'birth_date'            => 'slider',
			'last_login'            => 'datepicker',
			'user_registered'       => 'datepicker',
			'first_name'            => 'text',
			'last_name'             => 'text',
			'nickname'              => 'text',
			'secondary_user_email'  => 'text',
			'description'           => 'text',
			'phone_number'          => 'text',
			'mobile_number'         => 'text',
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

	/**
	 * Get the WordPress core searching fields in wp_users query.
	 * @return array
	 */
	protected function get_core_search_fields() {
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
		return apply_filters( 'um_member_directory_core_search_fields', $this->core_search_fields );
	}

	/**
	 * Getting member directory post ID via the hash.
	 * Hash is unique attr, which we use visible at frontend
	 *
	 * @param string $hash
	 *
	 * @return bool|int
	 */
	public function get_directory_by_hash( $hash ) {
		global $wpdb;

		$directory_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE SUBSTRING( MD5( ID ), 11, 5 ) = %s", $hash ) );

		if ( empty( $directory_id ) ) {
			return false;
		}

		return (int) $directory_id;
	}

	public function get_directory_hash( $form_id ) {
		return substr( md5( $form_id ), 10, 5 );
	}

	/**
	 * @param int $user_id
	 *
	 * @return array
	 */
	public function build_user_actions_list( $user_id ) {
		$actions = array();
		if ( ! is_user_logged_in() ) {
			return $actions;
		}

		$user_id = absint( $user_id );

//		$items = array(
//			array(
//				'<a href="javascript:void(0);" class="um-manual-trigger">' . __( 'Action 1', 'ultimate-member' ) . '</a>',
//				'<a href="javascript:void(0);" class="um-reset-profile-photo">' . __( 'Action 2', 'ultimate-member' ) . '</a>',
//			),
//			array(
//				'<a href="javascript:void(0);" class="um-manual-trigger">' . __( 'Action 3', 'ultimate-member' ) . '</a>',
//				'<a href="javascript:void(0);" class="um-reset-profile-photo">' . __( 'Action 4', 'ultimate-member' ) . '</a>',
//			),
//			array(
//				'<a href="javascript:void(0);" class="um-manual-trigger">' . __( 'Action 5', 'ultimate-member' ) . '</a>',
//				'<a href="javascript:void(0);" class="um-reset-profile-photo">' . __( 'Action 6', 'ultimate-member' ) . '</a>',
//			),
//		);

		if ( get_current_user_id() !== $user_id ) {

//			if ( UM()->roles()->um_current_user_can( 'edit', $user_id ) ) {
//				$actions['um-editprofile'] = array(
//					'title' => esc_html__( 'Edit Profile', 'ultimate-member' ),
//					'url'   => um_edit_profile_url(),
//				);
//			}

			$actions = array(
				array(
					'<a href="' . esc_url( um_edit_profile_url() ) . '" class="um-editprofile">' . esc_html__( 'Edit Profile', 'ultimate-member' ) . '</a>',
					//'<a href="' . esc_url( um_get_core_page( 'account' ) ) . '" class="um-myaccount">' . esc_html__( 'My Account', 'ultimate-member' ) . '</a>',
				),
//				array(
//					'<a href="' . esc_url( um_get_core_page( 'logout' ) ) . '" class="um-logout">' . esc_html__( 'Logout', 'ultimate-member' ) . '</a>',
//				),
			);

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
					$url = add_query_arg(
						array(
							'um_action' => $id,
							'uid'       => $user_id,
						),
						um_get_core_page( 'user' )
					);

					if ( 'um_switch_user' === $id ) {
						$actions[2][] = '<a href="' . esc_url( $url ) . '" class="' . esc_attr( $id ) . '">' . esc_html( $arr['label'] ) . '</a>';
					} else {
						$actions[1][] = '<a href="' . esc_url( $url ) . '" class="' . esc_attr( $id ) . '">' . esc_html( $arr['label'] ) . '</a>';
					}

//					$actions[ $id ] = array(
//						'title' => esc_html( $arr['label'] ),
//						'url'   => esc_url( $url ),
//					);
				}
			}

			if ( ! UM()->roles()->um_current_user_can( 'edit', $user_id ) ) {
				unset( $actions[0][0] );
			}

			$actions = apply_filters( 'um_member_directory_users_card_actions', $actions, $user_id );
		} else {
//			if ( empty( UM()->user()->cannot_edit ) ) {
//				$actions['um-editprofile'] = array(
//					'title' => esc_html__( 'Edit Profile', 'ultimate-member' ),
//					'url'   => um_edit_profile_url(),
//				);
//			}
//
//			$actions['um-myaccount'] = array(
//				'title' => esc_html__( 'My Account', 'ultimate-member' ),
//				'url'   => um_get_core_page( 'account' ),
//			);
//
//			$actions['um-logout'] = array(
//				'title' => esc_html__( 'Logout', 'ultimate-member' ),
//				'url'   => um_get_core_page( 'logout' ),
//			);

			$actions = array(
				array(
					'<a href="' . esc_url( um_edit_profile_url() ) . '" class="um-editprofile">' . esc_html__( 'Edit Profile', 'ultimate-member' ) . '</a>',
					'<a href="' . esc_url( um_get_core_page( 'account' ) ) . '" class="um-myaccount">' . esc_html__( 'My Account', 'ultimate-member' ) . '</a>',
				),
				array(
					'<a href="' . esc_url( um_get_core_page( 'logout' ) ) . '" class="um-logout">' . esc_html__( 'Logout', 'ultimate-member' ) . '</a>',
				),
			);

			if ( ! empty( UM()->user()->cannot_edit ) ) {
				unset( $actions[0][0] );
			}

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
	public function build_user_card_data( $user_id, $directory_data ) {

		um_fetch_user( $user_id );

		$dropdown_actions = $this->build_user_actions_list( $user_id );

		$actions  = array();
		$can_edit = UM()->roles()->um_current_user_can( 'edit', $user_id );

		// Replace hook 'um_members_just_after_name'
		ob_start();
		do_action( 'um_members_just_after_name', $user_id, $directory_data );
		$hook_just_after_name = ob_get_clean();

		// Replace hook 'um_members_after_user_name'
		ob_start();
		do_action( 'um_members_after_user_name', $user_id, $directory_data );
		$hook_after_user_name = ob_get_clean();

		$data_array = array(
			'card_anchor'          => esc_html( substr( md5( $user_id ), 10, 5 ) ),
			'id'                   => absint( $user_id ),
			'role'                 => esc_html( um_user( 'role' ) ),
			'account_status'       => esc_html( um_user( 'account_status' ) ),
			'account_status_name'  => esc_html( um_user( 'account_status_name' ) ),
			'cover_photo'          => wp_kses( um_user( 'cover_photo', $this->cover_size ), UM()->get_allowed_html( 'templates' ) ),
			'display_name'         => esc_html( um_user( 'display_name' ) ),
			'profile_url'          => esc_url( um_user_profile_url() ),
			'can_edit'             => (bool) $can_edit,
			'edit_profile_url'     => esc_url( um_edit_profile_url() ),
			'avatar'               => wp_kses( get_avatar( $user_id, $this->avatar_size ), UM()->get_allowed_html( 'templates' ) ),
			'display_name_html'    => wp_kses( um_user( 'display_name', 'html' ), UM()->get_allowed_html( 'templates' ) ),
			'dropdown_actions'     => $dropdown_actions,
			'hook_just_after_name' => wp_kses( preg_replace( '/^\s+/im', '', $hook_just_after_name ), UM()->get_allowed_html( 'templates' ) ),
			'hook_after_user_name' => wp_kses( preg_replace( '/^\s+/im', '', $hook_after_user_name ), UM()->get_allowed_html( 'templates' ) ),
		);

		if ( ! empty( $directory_data['show_tagline'] ) ) {

			if ( ! empty( $directory_data['tagline_fields'] ) ) {
				$directory_data['tagline_fields'] = maybe_unserialize( $directory_data['tagline_fields'] );

				if ( is_array( $directory_data['tagline_fields'] ) ) {
					foreach ( $directory_data['tagline_fields'] as $key ) {
						if ( ! $key ) {
							continue;
						}

						if ( '_um_last_login' === $key ) {
							$show_last_login = get_user_meta( $user_id, 'um_show_last_login', true );
							if ( ! empty( $show_last_login ) && 'no' === $show_last_login[0] ) {
								continue;
							}
						}

						$value = um_filtered_value( $key );

						if ( ! $value ) {
							continue;
						}

						$data_array[ $key ] = wp_kses( $value, UM()->get_allowed_html( 'templates' ) );
					}
				}
			}
		}

		if ( ! empty( $directory_data['show_userinfo'] ) ) {

			if ( ! empty( $directory_data['reveal_fields'] ) ) {

				$directory_data['reveal_fields'] = maybe_unserialize( $directory_data['reveal_fields'] );

				if ( is_array( $directory_data['reveal_fields'] ) ) {
					foreach ( $directory_data['reveal_fields'] as $key ) {
						if ( ! $key ) {
							continue;
						}

						if ( '_um_last_login' === $key ) {
							$show_last_login = get_user_meta( $user_id, 'um_show_last_login', true );
							if ( ! empty( $show_last_login ) && 'no' === $show_last_login[0] ) {
								continue;
							}
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

						$data_array[ "label_{$key}" ] = esc_html__( $label, 'ultimate-member' );
						$data_array[ $key ] = wp_kses( $value, UM()->get_allowed_html( 'templates' ) );
					}
				}
			}

			if ( ! empty( $directory_data['show_social'] ) ) {
				ob_start();
				UM()->fields()->show_social_urls();
				$social_urls = ob_get_clean();

				$data_array['social_urls'] = wp_kses( $social_urls, UM()->get_allowed_html( 'templates' ) );
			}
		}

		$data_array = apply_filters( 'um_ajax_get_members_data', $data_array, $user_id, $directory_data );

		um_reset_user_clean();

		return $data_array;
	}

	/**
	 * @param string $filter
	 * @param array $directory_data
	 *
	 * @return mixed
	 */
	protected function slider_filters_range( $filter, $directory_data ) {
		global $wpdb;

		$range = false;

		switch ( $filter ) {

			default: {

				$meta = $wpdb->get_row( $wpdb->prepare(
					"SELECT MIN( CONVERT( meta_value, DECIMAL ) ) as min_meta,
						MAX( CONVERT( meta_value, DECIMAL ) ) as max_meta,
						COUNT( DISTINCT meta_value ) as amount
						FROM {$wpdb->usermeta}
						WHERE meta_key = %s",
					$filter
				), ARRAY_A );

				if ( isset( $meta['min_meta'] ) && isset( $meta['max_meta'] ) && isset( $meta['amount'] ) && $meta['amount'] > 1 ) {
					$range = array( (float) $meta['min_meta'], (float) $meta['max_meta'] );
				}

				$range = apply_filters( 'um_member_directory_filter_slider_common', $range, $directory_data, $filter );
				$range = apply_filters( "um_member_directory_filter_{$filter}_slider", $range, $directory_data );

				break;
			}
			case 'birth_date': {

//					$meta = $wpdb->get_col(
//						"SELECT meta_value
//						FROM {$wpdb->usermeta}
//						WHERE meta_key = 'birth_date' AND
//						      meta_value != ''"
//					);
//
//					if ( empty( $meta ) || count( $meta ) < 2 ) {
//						$range = false;
//					} elseif ( is_array( $meta ) ) {
//						$birth_dates = array_filter( array_map( 'strtotime', $meta ), 'is_numeric' );
//						sort( $birth_dates );
//						$min_meta = array_shift( $birth_dates );
//						$max_meta = array_pop( $birth_dates );
//						$range = array( $this->borndate( $max_meta ), $this->borndate( $min_meta ) );
//					}

				$meta = $wpdb->get_row(
					"SELECT MIN( meta_value ) as min_meta,
						MAX( meta_value ) as max_meta,
						COUNT( DISTINCT meta_value ) as amount
						FROM {$wpdb->usermeta}
						WHERE meta_key = 'birth_date' AND
							  meta_value != ''",
					ARRAY_A );

				if ( isset( $meta['min_meta'] ) && isset( $meta['max_meta'] ) && isset( $meta['amount'] ) && $meta['amount'] > 1 ) {
					$range = array( $this->borndate( strtotime( $meta['max_meta'] ) ), $this->borndate( strtotime( $meta['min_meta'] ) ) );
				}

				break;
			}

		}

		return $range;
	}

	/**
	 * @param string $filter
	 * @param array  $attrs
	 *
	 * @return string[]
	 */
	protected function slider_range_placeholder( $filter, $attrs ) {
		if ( 'birth_date' === $filter ) {
			return array(
				__( '<strong>Age:</strong>&nbsp;{{{value}}} years old', 'ultimate-member' ),
				__( '<strong>Age:</strong>&nbsp;{{{value_from}}} - {{{value_to}}} years old', 'ultimate-member' ),
			);
		}

		$label        = ! empty( $attrs['label'] ) ? $attrs['label'] : $filter;
		$label        = ucwords( str_replace( array( 'um_', '_' ), array( '', ' ' ), $label ) );
		$placeholders = apply_filters( 'um_member_directory_filter_slider_range_placeholder', false, $filter );

		if ( false === $placeholders ) {
			if ( 'rating' === $attrs['type'] ) {
				return array(
					"<strong>$label:</strong>&nbsp;{{{value}}}" . __( ' stars', 'ultimate-member' ),
					"<strong>$label:</strong>&nbsp;{{{value_from}}} - {{{value_to}}}" . __( ' stars', 'ultimate-member' ),
				);
			}

			$placeholders = array(
				"<strong>$label:</strong>&nbsp;{{{value}}}",
				"<strong>$label:</strong>&nbsp;{{{value_from}}} - {{{value_to}}}",
			);
		}

		return $placeholders;
	}

	/**
	 * @param $filter
	 *
	 * @return mixed
	 */
	public function datepicker_filters_range( $filter ) {
		global $wpdb;

		switch ( $filter ) {
			default:
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
			case 'last_login':
				$meta = $wpdb->get_row(
					"SELECT DISTINCT COUNT(*) AS total,
							MIN(meta_value) AS min,
							MAX(meta_value) AS max
						FROM {$wpdb->usermeta}
						WHERE meta_key = '_um_last_login'",
					ARRAY_A
				);
				if ( empty( $meta['total'] ) || 1 === absint( $meta['total'] ) ) {
					$range = false;
				} elseif ( array_key_exists( 'min', $meta ) && array_key_exists( 'max', $meta ) ) {
					$range = array( strtotime( $meta['min'] ), strtotime( $meta['max'] ) );
				}
				break;
			case 'user_registered':
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

		return $range;
	}

	/**
	 * @param $filter
	 *
	 * @return mixed
	 */
	protected function timepicker_filters_range( $filter ) {
		global $wpdb;
		$meta = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT meta_value
					FROM {$wpdb->usermeta}
					WHERE meta_key = %s
					ORDER BY meta_value DESC",
				$filter
			)
		);

		$meta = array_filter( $meta );

		if ( empty( $meta ) || count( $meta ) === 1 ) {
			$range = false;
		} elseif ( ! empty( $meta ) ) {
			$range = array( min( $meta ), max( $meta ) );
		}

		return apply_filters( "um_member_directory_filter_{$filter}_timepicker", $range );
	}

	/**
	 * @param $borndate
	 *
	 * @return false|string
	 */
	private function borndate( $borndate ) {
		if ( date( 'm', $borndate ) > date( 'm' ) || ( date( 'm', $borndate ) === date( 'm' ) && date( 'd', $borndate ) > date( 'd' ) ) ) {
			return ( date( 'Y' ) - date( 'Y', $borndate ) - 1 );
		}
		return ( date( 'Y' ) - date( 'Y', $borndate ) );
	}
}