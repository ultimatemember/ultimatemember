<?php
namespace umm\online\includes\cross_modules;


if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class Member_Directory
 *
 * @package umm\online\includes\cross_modules
 */
class Member_Directory {


	/**
	 * Member_Directory constructor.
	 */
	function __construct() {
		add_filter( 'um_admin_extend_directory_options_profile', array( &$this, 'member_directory_options_profile' ), 10, 1 );
		add_filter( 'um_member_directory_meta_map', array( &$this, 'add_member_directory_meta_sanitize' ), 10, 1 );

		add_filter( 'um_members_directory_filter_fields',  array( $this, 'directory_filter_dropdown_options' ), 10, 1 );
		add_filter( 'um_members_directory_filter_types',  array( $this, 'directory_filter_types' ), 10, 1 );
		add_filter( 'um_search_fields',  array( $this, 'online_dropdown' ), 10, 1 );

		add_filter( 'um_query_args_online_status__filter',  array( $this, 'online_status_filter' ), 10, 5 );

		//UM metadata
		add_filter( 'um_query_args_online_status__filter_meta',  array( $this, 'online_status_filter_meta' ), 10, 6 );

		add_filter( 'um_ajax_get_members_data', array( &$this, 'get_members_data' ), 50, 2 );

		add_action( 'um_members_in_profile_photo_tmpl', array( &$this, 'extend_js_template' ), 10, 1 );
		add_action( 'um_members_list_in_profile_photo_tmpl', array( &$this, 'extend_js_template' ), 10, 1 );

		add_filter( 'um_debug_member_directory_profile_extend', array( $this, 'extends_site_health' ), 10, 2 );
	}


	/**
	 * @param array $fields
	 *
	 * @return array
	 */
	function member_directory_options_profile( $fields ) {
		$fields = array_merge( array_slice( $fields, 0, 3 ), array(
			array(
				'id'    => '_um_online_hide_stats',
				'type'  => 'checkbox',
				'label' => __( 'Hide online stats', 'ultimate-member' ),
				'value' => UM()->query()->get_meta_value( '_um_online_hide_stats', null, 'na' ),
			),
		), array_slice( $fields, 3, count( $fields ) - 1 ) );

		return $fields;
	}


	/**
	 * @param array $meta_map
	 *
	 * @return array
	 */
	public function add_member_directory_meta_sanitize( $meta_map ) {
		$meta_map = array_merge(
			$meta_map,
			array(
				'_um_online_hide_stats' => array(
					'sanitize' => 'bool',
				),
			)
		);
		return $meta_map;
	}


	/**
	 * Add Member Directory filter
	 *
	 * @param array $options
	 *
	 * @return array
	 */
	function directory_filter_dropdown_options( $options ) {
		$options['online_status'] = __( 'Online Status', 'ultimate-member' );
		return $options;
	}


	/**
	 * Set online_status filter type
	 *
	 * @param array $types
	 *
	 * @return array
	 */
	function directory_filter_types( $types ) {
		$types['online_status'] = 'select';
		return $types;
	}


	/**
	 * Build Select box for Online Status filter
	 * @param array $attrs
	 *
	 * @return array
	 */
	function online_dropdown( $attrs ) {
		if ( isset( $attrs['metakey'] ) && 'online_status' === $attrs['metakey'] ) {
			$attrs['type'] = 'select';

			$attrs['options'] = array(
				0 => __( 'Offline', 'ultimate-member' ),
				1 => __( 'Online', 'ultimate-member' ),
			);
		}
		return $attrs;
	}


	/**
	 * Filter users by Online status
	 *
	 * @param $field_query
	 * @param $field
	 * @param $value
	 * @param $filter_type
	 * @param \umm\member_directory\includes\ajax\Directory() $directory_class
	 *
	 * @return bool
	 */
	function online_status_filter( $field_query, $field, $value, $filter_type, $directory_class ) {
		if ( ! is_array( $value ) ) {
			$value = array( $value );
		}

		if ( ! ( in_array( 1, $value ) && in_array( 0, $value ) ) ) {
			$online_users_array = UM()->module( 'online' )->get_users( 'ids' );

			foreach ( $value as $val ) {
				if ( $val == '0' ) {
					if ( ! empty( $online_users_array ) ) {
						$directory_class->query_args['exclude'] = $online_users_array;
					}
				} elseif ( $val == '1' ) {
					if ( ! empty( $online_users_array ) ) {
						$directory_class->query_args['include'] = $online_users_array;
					}
				}
			}
		}

		$directory_class->custom_filters_in_query[ $field ] = $value;

		return true;
	}


	/**
	 * Filter users by Online status
	 *
	 * @param $skip
	 * @param \umm\member_directory\includes\ajax\Directory_Meta $query
	 * @param $field
	 * @param $value
	 * @param $filter_type
	 * @param bool $is_default
	 *
	 * @return bool
	 */
	function online_status_filter_meta( $skip, $query, $field, $value, $filter_type, $is_default ) {
		$skip = true;

		if ( ! is_array( $value ) ) {
			$value = array( $value );
		}

		if ( ! ( in_array( 1, $value ) && in_array( 0, $value ) ) ) {
			$online_users_array = UM()->module( 'online' )->get_users( 'ids' );

			foreach ( $value as $val ) {
				if ( $val == '0' ) {
					if ( ! empty( $online_users_array ) ) {
						$query->where_clauses[] = "u.ID NOT IN ('" . implode( "','", $online_users_array ) . "')";
					}
				} elseif ( $val == '1' ) {
					if ( ! empty( $online_users_array ) ) {
						$query->where_clauses[] = "u.ID IN ('" . implode( "','", $online_users_array ) . "')";
					}
				}
			}
		}

		if ( ! $is_default ) {
			$query->custom_filters_in_query[ $field ] = $value;
		}

		return $skip;
	}



	/**
	 * Expand AJAX member directory data
	 *
	 * @param $data_array
	 * @param $user_id
	 *
	 * @return mixed
	 */
	function get_members_data( $data_array, $user_id ) {
		$data_array['is_online'] = false;

		if ( ! UM()->module( 'online' )->common()->user()->is_hidden_status( $user_id ) ) {
			$data_array['is_online'] = UM()->module( 'online' )->common()->user()->is_online( $user_id );
		}

		return $data_array;
	}


	/**
	 * @param $args
	 */
	function extend_js_template( $args ) {
		$hide_online_show_stats = ! empty( $args['online_hide_stats'] ) ? $args['online_hide_stats'] : ! UM()->options()->get( 'online_show_stats' );

		if ( empty( $hide_online_show_stats ) ) { ?>

			<# if ( user.is_online ) { #>
				<span class="um-online-status online um-tip-n"
				      title="<?php esc_attr_e( 'Online', 'ultimate-member' ); ?>">
					<i class="fas fa-circle"></i>
				</span>
			<# } #>

		<?php }
	}

	/**
	 * Extend profile card for member directory.
	 *
	 * @since 3.0
	 *
	 * @param array $info The Site Health information.
	 *
	 * @return array The updated Site Health information.
	 */
	public function extends_site_health( $info, $key ) {
		$info[ 'ultimate-member-directory-' . $key ]['fields'] = array_merge(
			$info[ 'ultimate-member-directory-' . $key ]['fields'],
			array(
				'um-directory-online_hide_stats' => array(
					'label' => __( 'Hide online stats', 'ultimate-member' ),
					'value' => get_post_meta( $key,'_um_online_hide_stats', true ) ? __( 'Yes', 'ultimate-member' ) : __( 'No', 'ultimate-member' ),
				),
			)
		);

		return $info;
	}
}
