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
class Directory extends Directory_Config {

	/**
	 * Directory constructor.
	 */
	public function __construct() {
		parent::__construct();
		add_action( 'updated_user_meta', array( &$this, 'on_update_usermeta' ), 10, 4 );
		add_action( 'added_user_meta', array( &$this, 'on_update_usermeta' ), 10, 4 );
		add_action( 'deleted_user_meta', array( &$this, 'on_delete_usermeta' ), 10, 4 );

		add_action( 'um_add_new_field', array( &$this, 'on_new_field_added' ), 10, 2 );
		add_action( 'um_delete_custom_field', array( &$this, 'on_delete_custom_field' ), 10, 2 );
	}

	/**
	 * When you add/update usermeta - add/update row from um_metadata
	 *
	 * @param int $meta_id
	 * @param int $object_id
	 * @param string $meta_key
	 * @param mixed $_meta_value
	 */
	public function on_update_usermeta( $meta_id, $object_id, $meta_key, $_meta_value ) {
		$search_in_table = UM()->options()->get( 'member_directory_own_table' );
		if ( empty( $search_in_table ) ) {
			return;
		}

		$metakeys = get_option( 'um_usermeta_fields', array() );
		if ( ! in_array( $meta_key, $metakeys, true ) ) {
			return;
		}

		global $wpdb;

		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT umeta_id
					FROM {$wpdb->prefix}um_metadata
					WHERE user_id = %d AND
					      um_key = %s
					LIMIT 1",
				$object_id,
				$meta_key
			)
		);

		if ( empty( $result ) ) {
			$wpdb->insert(
				"{$wpdb->prefix}um_metadata",
				array(
					'user_id'  => $object_id,
					'um_key'   => $meta_key,
					'um_value' => maybe_serialize( $_meta_value ),
				),
				array(
					'%d',
					'%s',
					'%s',
				)
			);
		} else {
			$wpdb->update(
				"{$wpdb->prefix}um_metadata",
				array(
					'um_value' => maybe_serialize( $_meta_value ),
				),
				array(
					'umeta_id' => $result,
				),
				array(
					'%s',
				),
				array(
					'%d',
				)
			);
		}
	}

	/**
	 * When you delete usermeta - remove row from um_metadata
	 *
	 * @param int|array $meta_ids
	 * @param int $object_id
	 * @param string $meta_key
	 * @param mixed $_meta_value
	 */
	public function on_delete_usermeta( $meta_ids, $object_id, $meta_key, $_meta_value ) {
		$search_in_table = UM()->options()->get( 'member_directory_own_table' );
		if ( empty( $search_in_table ) ) {
			return;
		}

		$metakeys = get_option( 'um_usermeta_fields', array() );
		if ( ! in_array( $meta_key, $metakeys, true ) ) {
			return;
		}

		global $wpdb;

		$wpdb->delete(
			"{$wpdb->prefix}um_metadata",
			array(
				'user_id' => $object_id,
				'um_key'  => $meta_key,
			),
			array(
				'%d',
				'%s',
			)
		);
	}

	/**
	 * Add metakey to usermeta fields
	 *
	 * @param $metakey
	 * @param $args
	 */
	public function on_new_field_added( $metakey, $args ) {
		$search_in_table = UM()->options()->get( 'member_directory_own_table' );
		if ( empty( $search_in_table ) ) {
			return;
		}

		$metakeys = get_option( 'um_usermeta_fields', array() );

		if ( ! in_array( $metakey, $metakeys, true ) ) {
			$metakeys[] = $metakey;
			update_option( 'um_usermeta_fields', array_values( $metakeys ) );
		}

		do_action( 'um_metadata_on_new_field_added', $metakeys, $metakey, $args );
	}

	/**
	 * Delete custom field and metakey from UM usermeta table
	 *
	 * @param $metakey
	 * @param $args
	 */
	public function on_delete_custom_field( $metakey, $args ) {
		$search_in_table = UM()->options()->get( 'member_directory_own_table' );
		if ( empty( $search_in_table ) ) {
			return;
		}

		$metakeys = get_option( 'um_usermeta_fields', array() );

		if ( in_array( $metakey, $metakeys, true ) ) {
			unset( $metakeys[ array_search( $metakey, $metakeys, true ) ] );

			global $wpdb;

			$wpdb->delete(
				"{$wpdb->prefix}um_metadata",
				array(
					'um_key' => $metakey,
				),
				array(
					'%s',
				)
			);

			update_option( 'um_usermeta_fields', array_values( $metakeys ) );
		}

		do_action( 'um_metadata_on_delete_custom_field', $metakeys, $metakey, $args );
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

	/**
	 * @param $form_id
	 *
	 * @return bool|string
	 */
	public function get_directory_hash( $form_id ) {
		return substr( md5( $form_id ), 10, 5 );
	}

	/**
	 * @return bool
	 */
	public function get_hide_in_members_default() {
		$default = false;
		$option  = UM()->options()->get( 'account_hide_in_directory_default' );
		if ( 'Yes' === $option ) {
			$default = true;
		}

		return apply_filters( 'um_member_directory_hide_in_members_default', $default );
	}

	/**
	 * Get view Type template
	 * @param string $type
	 *
	 * @return string
	 */
	public function get_type_basename( $type ) {
		return apply_filters( "um_member_directory_{$type}_type_template_basename", '' );
	}

	/**
	 * @param int $user_id
	 * @param array $directory_data
	 *
	 * @return array
	 */
	public function build_user_card_data( $user_id, $directory_data ) {
		um_fetch_user( $user_id );

		$dropdown_actions = UM()->frontend()->users()->get_dropdown_items( $user_id, 'directory' );

		$can_edit = UM()->roles()->um_current_user_can( 'edit', $user_id );

		$this->init_image_sizing( $directory_data );

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
			'cover_photo'          => UM()->frontend()::layouts()::cover_photo( $user_id, array( 'size' => $this->cover_size ) ),
			'display_name'         => esc_html( um_user( 'display_name' ) ),
			'profile_url'          => esc_url( um_user_profile_url( $user_id ) ),
			'can_edit'             => (bool) $can_edit,
			'edit_profile_url'     => esc_url( um_edit_profile_url( $user_id ) ),
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
						if ( in_array( $key, array( 'role_select', 'role_radio' ), true ) ) {
							$label = strtr(
								$label,
								array(
									' (Dropdown)' => '',
									' (Radio)'    => '',
								)
							);
						}

						$data_array[ "label_{$key}" ] = esc_html( $label );
						$data_array[ $key ]           = wp_kses( $value, UM()->get_allowed_html( 'templates' ) );
					}
				}
			}

			if ( ! empty( $directory_data['show_social'] ) ) {
				ob_start();
				UM()->fields()->show_social_urls( $user_id );
				$data_array['social_urls'] = ob_get_clean();
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
			default:
				$meta = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT MIN( CONVERT( meta_value, DECIMAL ) ) as min_meta,
						MAX( CONVERT( meta_value, DECIMAL ) ) as max_meta,
						COUNT( DISTINCT meta_value ) as amount
						FROM {$wpdb->usermeta}
						WHERE meta_key = %s AND
							  meta_value != ''",
						$filter
					),
					ARRAY_A
				);

				if ( isset( $meta['min_meta'], $meta['max_meta'], $meta['amount'] ) && $meta['amount'] > 1 ) {
					$range = array( (float) $meta['min_meta'], (float) $meta['max_meta'] );
				}

				$range = apply_filters( 'um_member_directory_filter_slider_common', $range, $directory_data, $filter );
				$range = apply_filters( "um_member_directory_filter_{$filter}_slider", $range, $directory_data );
				break;

			case 'birth_date':
				$meta = $wpdb->get_row(
					"SELECT MIN( meta_value ) as min_meta,
					MAX( meta_value ) as max_meta,
					COUNT( DISTINCT meta_value ) as amount
					FROM {$wpdb->usermeta}
					WHERE meta_key = 'birth_date' AND
						  meta_value != ''",
					ARRAY_A
				);

				if ( isset( $meta['min_meta'], $meta['max_meta'], $meta['amount'] ) && $meta['amount'] > 1 ) {
					$range = array( $this->borndate( strtotime( $meta['max_meta'] ) ), $this->borndate( strtotime( $meta['min_meta'] ) ) );
				}
				break;

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
	 * @param string $filter
	 * @param array  $directory_data
	 *
	 * @return array
	 */
	public function datepicker_filters_range( $filter, $directory_data ) {
		global $wpdb;

		$directory_id = $directory_data['form_id'];

		$disable_filters_pre_query = (bool) get_post_meta( $directory_id, '_um_disable_filters_pre_query', true );
		if ( true === $disable_filters_pre_query ) {
			return array( gmdate( 'Y-m-d' ), gmdate( 'Y-m-d' ) );
		}

		$range = false;

		switch ( $filter ) {
			default:
				$meta = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT DISTINCT meta_value
						FROM {$wpdb->usermeta}
						WHERE meta_key = %s AND
							  meta_value != ''
						ORDER BY meta_value DESC",
						$filter
					)
				);

				if ( ! empty( $meta ) && count( $meta ) > 1 ) {
					$range = array( min( $meta ), max( $meta ) );
				}

				$range = apply_filters( "um_member_directory_filter_{$filter}_datepicker", $range );
				break;

			case 'last_login':
				$meta = $wpdb->get_row(
					"SELECT DISTINCT COUNT(*) AS total,
							MIN(meta_value) AS min,
							MAX(meta_value) AS max
					FROM {$wpdb->usermeta}
					WHERE meta_key = '_um_last_login' AND
						  meta_value != ''",
					ARRAY_A
				);

				if ( ! empty( $meta['total'] ) && absint( $meta['total'] ) > 1 && array_key_exists( 'min', $meta ) && array_key_exists( 'max', $meta ) ) {
					$range = array( strtotime( $meta['min'] ), strtotime( $meta['max'] ) );
				}
				break;

			case 'user_registered':
				$meta = $wpdb->get_col(
					"SELECT DISTINCT user_registered
					FROM {$wpdb->users}
					ORDER BY user_registered DESC"
				);

				if ( ! empty( $meta ) && count( $meta ) > 1 ) {
					$range = array( min( $meta ), max( $meta ) );
				}
				break;

		}

		return $range;
	}

	/**
	 * @param string $filter
	 * @param array  $directory_data
	 *
	 * @return array
	 */
	protected function timepicker_filters_range( $filter, $directory_data ) {
		global $wpdb;

		$directory_id = $directory_data['form_id'];

		$disable_filters_pre_query = (bool) get_post_meta( $directory_id, '_um_disable_filters_pre_query', true );
		if ( true === $disable_filters_pre_query ) {
			return array( '00:00', '23:59' );
		}

		$meta = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT meta_value
				FROM {$wpdb->usermeta}
				WHERE meta_key = %s AND
					  meta_value != ''
				ORDER BY meta_value DESC",
				$filter
			)
		);

		$meta = array_filter( $meta );

		$range = false;
		if ( ! empty( $meta ) && count( $meta ) > 1 ) {
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

	/**
	 * Render member's directory filters.
	 *
	 * @param string $filter
	 * @param array  $directory_data
	 * @param mixed  $default_value
	 * @param bool   $admin
	 *
	 * @return string $filter
	 */
	public function show_filter( $filter, $directory_data, $default_value = false, $admin = false ) {
		if ( empty( $this->filter_types[ $filter ] ) ) {
			return '';
		}

		// Don't show filter (not select type) in the case when the same default filter is set from wp-admin
		if ( ! $admin && 'select' !== $this->filter_types[ $filter ] ) {
			$default_filters = array();
			if ( ! empty( $directory_data['search_filters'] ) ) {
				$default_filters = maybe_unserialize( $directory_data['search_filters'] );
			}

			if ( ! empty( $default_filters[ $filter ] ) ) {
				return '';
			}
		}

		$field_key = $filter;
		if ( 'last_login' === $filter ) {
			$field_key = '_um_last_login';
		} elseif ( 'role' === $filter ) {
			$field_key = 'role_select';
		}

		$fields = UM()->builtin()->all_user_fields;

		$attrs = isset( $fields[ $field_key ] ) ? $fields[ $field_key ] : array();
		/**
		 * Filters the field's data used in member directory filter.
		 * Where $filter is the filter key.
		 *
		 * @param {array}  $attrs     Filter's field data.
		 * @param {string} $field_key Field key.
		 *
		 * @return {array} Filter's field data.
		 *
		 * @since 2.0
		 * @hook um_custom_search_field_{$filter}
		 *
		 * @example <caption>Set data for the filter with `my_filter` filter.</caption>
		 * function my_filter_search_field( $attrs, $field_key ) {
		 *     // your code here
		 *     if ( 'exam_date' === $field_key ) {
		 *         $attrs = array(
		 *             'title'    => 'Exam Date'
		 *             'metakey'  => 'exam_date',
		 *             'type'     => 'date',
		 *             'label'    => 'Exam Date',
		 *             'editable' => true,
		 *         );
		 *     }
		 *     return $attrs;
		 * }
		 * add_filter( 'um_custom_search_field_my_filter', 'my_filter_search_field', 10, 2 );
		 */
		$attrs = apply_filters( "um_custom_search_field_$filter", $attrs, $field_key );

		// Skip private invisible fields
		if ( ! $admin && ! um_can_view_field( $attrs ) ) {
			return '';
		}

		/**
		 * Filters the field's data used in member directory filter.
		 *
		 * @param {array}  $attrs        Filter's field data.
		 * @param {string} $field_key    Field key.
		 * @param {int}    $directory_id Member Directory ID.
		 *
		 * @return {array} Filter's field data.
		 *
		 * @since 2.0
		 * @hook um_search_fields
		 *
		 * @example <caption>Change `Birth Date` filter's label.</caption>
		 * function my_filter_search_field( $attrs, $field_key, $directory_id ) {
		 *     // your code here
		 *     if ( 'birth_date' === $field_key ) {
		 *         $attrs['label'] = 'Birthday';
		 *     }
		 *     return $attrs;
		 * }
		 * add_filter( 'um_search_fields', 'my_filter_search_field', 10, 3 );
		 */
		$attrs = apply_filters( 'um_search_fields', $attrs, $field_key, $directory_data['form_id'] );

		$unique_hash = $this->get_directory_hash( $directory_data['form_id'] );

		switch ( $this->filter_types[ $filter ] ) {
			default:
				ob_start();
				do_action( "um_member_directory_filter_type_{$this->filter_types[ $filter ]}", $filter, $directory_data, $unique_hash, $attrs, $default_value );
				return ob_get_clean();

			case 'text':
				$label = '';
				if ( isset( $attrs['label'] ) ) {
					$label = $attrs['label'];
				} elseif ( isset( $attrs['title'] ) ) {
					$label = $attrs['title'];
				}

				$label = stripslashes( $label );

				$filter_from_url = ! empty( $_GET[ 'filter_' . $filter . '_' . $unique_hash ] ) ? sanitize_text_field( $_GET[ 'filter_' . $filter . '_' . $unique_hash ] ) : $default_value;

				ob_start();
				if ( $admin ) {
					?>
					<input type="text" autocomplete="off" id="<?php echo esc_attr( $filter ); ?>" name="<?php echo esc_attr( $filter ); ?>"
						placeholder="<?php echo esc_attr( $label ); ?>"
						value="<?php echo esc_attr( $filter_from_url ); ?>"
						aria-label="<?php echo esc_attr( $label ); ?>" />
					<?php
				} else {
					?>
					<div class="um-field-wrapper">
						<label for="<?php echo esc_attr( $filter ); ?>"><?php echo esc_html( stripslashes( $label ) ); ?></label>
						<input type="text" autocomplete="off" id="<?php echo esc_attr( $filter ); ?>" name="<?php echo esc_attr( $filter ); ?>"
							placeholder="<?php echo esc_attr( $label ); ?>"
							value="<?php echo esc_attr( $filter_from_url ); ?>" class="um-search-filter-field"
							aria-label="<?php echo esc_attr( $label ); ?>" />
					</div>
					<?php
				}
				return ob_get_clean();

			case 'select':
				return $this->render_dropdown_filter( $attrs, $filter, $directory_data, $default_value, $admin );

			case 'slider':
				if ( $admin ) {
					return $this->render_slider_admin_filter( $filter, $default_value );
				}

				return $this->render_slider_filter( $attrs, $filter, $directory_data );

			case 'datepicker':
				if ( $admin ) {
					return $this->render_datepicker_admin_filter( $filter, $default_value );
				}
				return $this->render_datepicker_filter( $attrs, $filter, $directory_data );

			case 'timepicker':
				if ( $admin ) {
					return $this->render_timepicker_admin_filter( $filter, $default_value );
				}

				return $this->render_timepicker_filter( $attrs, $filter, $directory_data );

		}
	}

	/**
	 * @param array  $attrs
	 * @param string $filter
	 * @param array  $directory_data
	 * @param mixed  $default_value
	 * @param bool   $admin
	 *
	 * @return false|string
	 */
	private function render_dropdown_filter( $attrs, $filter, $directory_data, $default_value, $admin ) {
		global $wpdb;

		$directory_id = $directory_data['form_id'];
		$unique_hash  = $this->get_directory_hash( $directory_id );
		$values_array = isset( $attrs['options'] ) ? $attrs['options'] : array(); // Fallback

		// getting value from GET line
		$filter_from_url = array();
		if ( ! $admin ) {
			$filter_from_url = ! empty( $_GET[ 'filter_' . $filter . '_' . $unique_hash ] ) ? explode( '||', sanitize_text_field( $_GET[ 'filter_' . $filter . '_' . $unique_hash ] ) ) : $filter_from_url;
			$filter_from_url = apply_filters( 'um_member_directory_filter_value_from_url', $filter_from_url, $attrs, $filter, $directory_data );
		}

		$disable_filters_pre_query = (bool) get_post_meta( $directory_id, '_um_disable_filters_pre_query', true );

		/** This filter is documented in includes/core/class-fields.php */
		$option_pairs    = apply_filters( 'um_select_options_pair', null, $attrs );
		$custom_dropdown = '';

		// Workaround for the first member directory page loading.
		// Can be required for some cases in the callback functions. E.g. Billing/Shipping Country -> State dependencies.
		$workaround_unset = false;
		if ( ! isset( $_POST['member_directory'] ) ) {
			$_POST['member_directory'] = true;
			$_POST['child_name']       = $filter;
			$workaround_unset          = true;
		}

		$choices_callback = UM()->fields()->get_custom_dropdown_options_source( $filter, $attrs );
		if ( ! empty( $choices_callback ) ) {
			$option_pairs     = true;
			$custom_dropdown .= ' data-um-ajax-source="' . esc_attr( $choices_callback ) . '" data-nonce="' . wp_create_nonce( 'um_dropdown_parent_nonce' . $attrs['metakey'] ) . '" ';
			if ( ! empty( $attrs['parent_dropdown_relationship'] ) ) {
				/** This filter is documented in includes/core/class-fields.php */
				$parent_dropdown_relationship = apply_filters( "um_custom_dropdown_options_parent__$filter", $attrs['parent_dropdown_relationship'], $attrs );

				$custom_dropdown .= ' data-um-parent="' . esc_attr( $parent_dropdown_relationship ) . '"';

				$parent_option     = array();
				$um_search_filters = get_post_meta( $directory_id, '_um_search_filters', true );
				if ( $admin ) {
					if ( ! empty( $um_search_filters ) ) {
						$parent_option = isset( $um_search_filters[ $parent_dropdown_relationship ] ) ? $um_search_filters[ $parent_dropdown_relationship ] : $parent_option;
					}
				} else {
					$filter_value_key = 'filter_' . $parent_dropdown_relationship . '_' . $unique_hash;
					if ( isset( $_GET[ $filter_value_key ] ) ) {
						$parent_option_value = sanitize_text_field( $_GET[ $filter_value_key ] );
						$parent_option       = explode( '||', $parent_option_value );
					} elseif ( ! empty( $um_search_filters ) ) {
						// Case when display member directory filter in directory header, but the parent filter is set in Admin filtering.
						$parent_option = isset( $um_search_filters[ $parent_dropdown_relationship ] ) ? $um_search_filters[ $parent_dropdown_relationship ] : $parent_option;
					}
				}

				$attrs['options'] = $choices_callback( $parent_option, $parent_dropdown_relationship );
			} else {
				$attrs['options'] = $choices_callback();
			}

			if ( ! $admin && true !== $disable_filters_pre_query ) {
				$values_array = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT DISTINCT meta_value
						FROM $wpdb->usermeta
						WHERE meta_key = %s AND
							  meta_value != ''",
						$attrs['metakey']
					)
				);

				if ( ! empty( $values_array ) && in_array( $attrs['type'], array( 'select', 'multiselect', 'checkbox', 'radio' ), true ) ) {
					$values_array = array_map( 'maybe_unserialize', $values_array );
					$values_array = array_map(
						function ( $item ) {
							$item = maybe_unserialize( $item );
							return is_array( $item ) ? $item : (array) $item;
						},
						$values_array
					);
					$values_array = array_unique( array_merge( ...$values_array ) );
				}

				$attrs['options'] = array_intersect_key( array_map( 'trim', $attrs['options'] ), array_flip( $values_array ) );
			}
		} else {
			if ( $admin ) {
				$disable_filters_pre_query = true;

				if ( 'role' === $filter ) {
					$values_array = array();

					$editable_roles  = array_reverse( get_editable_roles() );
					$shortcode_roles = get_post_meta( $directory_id, '_um_roles', true );
					$shortcode_roles = maybe_unserialize( $shortcode_roles );

					if ( ! empty( $shortcode_roles ) && is_array( $shortcode_roles ) ) {
						foreach ( $editable_roles as $role => $details ) {
							if ( in_array( $role, $shortcode_roles, true ) ) {
								$values_array[ $role ] = translate_user_role( $details['name'] );
							}
						}
					} else {
						foreach ( $editable_roles as $role => $details ) {
							$values_array[ $role ] = translate_user_role( $details['name'] );
						}
					}

					$attrs['options'] = $values_array;
				}
			} else {
				if ( 'role' === $filter ) {
					$values_array = array();

					$shortcode_roles = get_post_meta( $directory_data['form_id'], '_um_roles', true );
					$shortcode_roles = maybe_unserialize( $shortcode_roles );

					$um_roles = UM()->roles()->get_roles();

					if ( true !== $disable_filters_pre_query ) {
						$users_roles = count_users();
						$roles_exist = ( ! empty( $users_roles['avail_roles'] ) && is_array( $users_roles['avail_roles'] ) ) ? array_keys( array_filter( $users_roles['avail_roles'] ) ) : array();

						foreach ( $um_roles as $key => $value ) {
							if ( ! empty( $shortcode_roles ) && is_array( $shortcode_roles ) ) {
								if ( in_array( $key, $roles_exist, true ) && in_array( $key, $shortcode_roles, true ) ) {
									$values_array[ $key ] = $value;
								}
							} elseif ( in_array( $key, $roles_exist, true ) ) {
								$values_array[ $key ] = $value;
							}
						}
					} else {
						foreach ( $um_roles as $key => $value ) {
							if ( ! empty( $shortcode_roles ) && is_array( $shortcode_roles ) ) {
								if ( in_array( $key, $shortcode_roles, true ) ) {
									$values_array[ $key ] = $value;
								}
							} else {
								$values_array[ $key ] = $value;
							}
						}
					}

					$attrs['options'] = $values_array;
				} else {
					if ( true !== $disable_filters_pre_query ) {
						// @todo find the way how to remove `online_status` from there
						if ( 'online_status' !== $attrs['metakey'] ) {
							$values_array = $wpdb->get_col(
								$wpdb->prepare(
									"SELECT DISTINCT meta_value
									FROM $wpdb->usermeta
									WHERE meta_key = %s AND
										  meta_value != ''",
									$attrs['metakey']
								)
							);

							if ( ! empty( $values_array ) && in_array( $attrs['type'], array( 'select', 'multiselect', 'checkbox', 'radio' ), true ) ) {
								$values_array = array_map(
									function ( $item ) {
										$item = maybe_unserialize( $item );
										return is_array( $item ) ? $item : (array) $item;
									},
									$values_array
								);
								$values_array = array_unique( array_merge( ...$values_array ) );
							}

							// @todo find the way how to remove `mycred_rank` from there
							if ( empty( $option_pairs ) && 'mycred_rank' !== $attrs['metakey'] ) {
								$attrs['options'] = array_intersect( array_map( 'stripslashes', array_map( 'trim', $attrs['options'] ) ), $values_array );
							} else {
								$attrs['options'] = array_intersect_key( array_map( 'trim', $attrs['options'] ), array_flip( $values_array ) );
							}
						}
					}
				}
			}
		}

		// Workaround for the first member directory page loading.
		// Can be required for some cases in the callback functions. E.g. Billing/Shipping Country -> State dependencies.
		if ( $workaround_unset ) {
			unset( $_POST['member_directory'], $_POST['child_name'] );
		}

		$options = array_key_exists( 'options', $attrs ) ? $attrs['options'] : array();
		$attrs['options'] = apply_filters( 'um_member_directory_filter_select_options', $options, $values_array, $attrs, $directory_data );
		if ( empty( $attrs['options'] ) || ! is_array( $attrs['options'] ) ) {
			return '';
		}

		// Intersect options array with the default filter options
		if ( ! $admin && ! empty( $directory_data['search_filters'] ) ) {
			$default_filters = maybe_unserialize( $directory_data['search_filters'] );

			if ( ! empty( $default_filters[ $filter ] ) ) {
				$attrs['options'] = array_intersect( $attrs['options'], $default_filters[ $filter ] );
			}
		}

		ksort( $attrs['options'] );

		$attrs['options'] = apply_filters( 'um_member_directory_filter_select_options_sorted', $attrs['options'], $attrs, $directory_data );

		if ( empty( $attrs['options'] ) ) {
			return '';
		}

		if ( isset( $attrs['label'] ) ) {
			$attrs['label'] = wp_strip_all_tags( $attrs['label'] );
		}

		$label = '';
		if ( isset( $attrs['label'] ) ) {
			$label = $attrs['label'];
		} elseif ( isset( $attrs['title'] ) ) {
			$label = $attrs['title'];
		}
		$label = stripslashes( $label );

		$dropdown_class = '';
		if ( true !== $admin ) {
			$dropdown_class = 'um-field-wrapper';
		}

		$orig_name = $filter;
		$name      = $filter;
		if ( $admin && count( $attrs['options'] ) > 1 ) {
			$name .= '[]';
		}
		ob_start();

		if ( ! $admin ) {
		?>
		<div class="<?php echo esc_attr( $dropdown_class ); ?>">
			<label for="<?php echo esc_attr( $filter ); ?>"><?php echo esc_html( $label ); ?></label>
		<?php } ?>
			<select multiple class="js-choice um-search-filter-field" id="<?php echo esc_attr( $filter ); ?>" name="<?php echo esc_attr( $name ); ?>" data-orig-name="<?php echo esc_attr( $orig_name ); ?>"
				aria-label="<?php echo esc_attr( $label ); ?>" <?php echo $custom_dropdown; ?>>
				<?php
				foreach ( $attrs['options'] as $k => $v ) {
					$opt = stripslashes( $v );
					if ( ! empty( $option_pairs ) || 'role' === $filter ) {
						$opt = $k;
					}

					if ( $admin ) {
						if ( ! is_array( $default_value ) ) {
							$default_value = array( $default_value );
						}

						// @todo find the way how to use strict comparison
						$selected = in_array( $opt, $default_value, false ); // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict -- $default_value values can be strings but $opt can be a number ( for example user tags extension)
					} else {
						$selected = ( ! empty( $filter_from_url ) && in_array( $opt, $filter_from_url, true ) );
					}
					?>
					<option value="<?php echo esc_attr( $opt ); ?>" data-value_label="<?php echo esc_attr( $v ); ?>" <?php selected( $selected ); ?>>
						<?php echo esc_html( $v ); ?>
					</option>
					<?php
				}
				?>
			</select>
		<?php if ( ! $admin ) { ?>
		</div>
		<?php
		}
		return ob_get_clean();
	}

	private function render_slider_admin_filter( $filter, $default_value ) {
		$value = array( 0, 0 );
		if ( $default_value ) {
			$value = $default_value;
		}
		ob_start();
		?>
		<div>
			<label for="<?php echo esc_attr( $filter . '_min' ); ?>"><?php esc_html_e( 'From', 'ultimate-member' ); ?></label>
			<input type="number" autocomplete="off" id="<?php echo esc_attr( $filter . '_min' ); ?>" name="<?php echo esc_attr( $filter . '_min' ); ?>"
				value="<?php echo esc_attr( min( $value ) ); ?>" class="um-search-filter-field" />
		</div>
		<div>
			<label for="<?php echo esc_attr( $filter . '_max' ); ?>"><?php esc_html_e( 'To', 'ultimate-member' ); ?></label>
			<input type="number" autocomplete="off" id="<?php echo esc_attr( $filter . '_max' ); ?>" name="<?php echo esc_attr( $filter . '_max' ); ?>"
				value="<?php echo esc_attr( max( $value ) ); ?>" class="um-search-filter-field" />
		</div>
		<?php
		return ob_get_clean();
	}

	private function render_slider_filter( $attrs, $filter, $directory_data ) {
		$unique_hash = $this->get_directory_hash( $directory_data['form_id'] );

		// Ignore '_um_disable_filters_pre_query' meta here because range can be different for different fields and need to know the real range or hide this filter.
		$range = $this->slider_filters_range( $filter, $directory_data );
		if ( ! $range ) {
			return '';
		}

		$label = '';
		if ( isset( $attrs['label'] ) ) {
			$label = $attrs['label'];
		} elseif ( ! isset( $attrs['label'] ) && isset( $attrs['title'] ) ) {
			$label = $attrs['title'];
		}

		$filter_from_url_from = ! empty( $_GET[ 'filter_' . $filter . '_from_' . $unique_hash ] ) ? sanitize_text_field( $_GET[ 'filter_' . $filter . '_from_' . $unique_hash ] ) : $range[0];
		$filter_from_url_to   = ! empty( $_GET[ 'filter_' . $filter . '_to_' . $unique_hash ] ) ? sanitize_text_field( $_GET[ 'filter_' . $filter . '_to_' . $unique_hash ] ) : $range[1];

		$value = array( $filter_from_url_from, $filter_from_url_to );

		list( $single_placeholder, $plural_placeholder ) = $this->slider_range_placeholder( $filter, $attrs );

		return UM()->frontend()::layouts()::range(
			array(
				'label'       => stripslashes( $label ),
				'name'        => $filter,
				'classes'     => array(
					'from' => array( 'um-search-filter-field' ),
					'to'   => array( 'um-search-filter-field' ),
				),
				'value'       => $value,
				'min'         => $range[0],
				'max'         => $range[1],
				'placeholder' => array(
					'single' => $single_placeholder,
					'plural' => $plural_placeholder,
				),
			)
		);
	}

	private function render_datepicker_admin_filter( $filter, $default_value ) {
		$value = array( gmdate( 'Y-m-d' ), gmdate( 'Y-m-d' ) );
		if ( $default_value ) {
			// Backward compatibility for dates in format Y/m/d.
			foreach ( $default_value as &$v ) {
				if ( strpos( $v, '/' ) ) {
					$v = gmdate( 'Y-m-d', strtotime( $v ) );
				}
			}
			unset( $v );

			$value = $default_value;
		}
		ob_start();
		?>
		<div class="um-date-range-row">
			<label for="<?php echo esc_attr( $filter . '_from' ); ?>"><?php esc_html_e( 'From', 'ultimate-member' ); ?></label>
			<input type="date" id="<?php echo esc_attr( $filter . '_from' ); ?>" name="<?php echo esc_attr( $filter . '_from' ); ?>" data-range="from" value="<?php echo esc_attr( min( $value ) ); ?>" />
		</div>
		<div class="um-date-range-row">
			<label for="<?php echo esc_attr( $filter . '_to' ); ?>"><?php esc_html_e( 'To', 'ultimate-member' ); ?></label>
			<input type="date" id="<?php echo esc_attr( $filter . '_to' ); ?>" name="<?php echo esc_attr( $filter . '_to' ); ?>" data-range="to" value="<?php echo esc_attr( max( $value ) ); ?>" />
		</div>
		<?php
		return ob_get_clean();
	}

	private function render_datepicker_filter( $attrs, $filter, $directory_data ) {
		$unique_hash = $this->get_directory_hash( $directory_data['form_id'] );

		$range = $this->datepicker_filters_range( $filter, $directory_data );
		if ( ! $range ) {
			return '';
		}

		$label = ! empty( $attrs['label'] ) ? $attrs['label'] : $attrs['title'];
		$label = stripslashes( $label );

		list( $min, $max ) = $range;

		$filter_from_url_from = ! empty( $_GET[ 'filter_' . $filter . '_from_' . $unique_hash ] ) ? sanitize_text_field( $_GET[ 'filter_' . $filter . '_from_' . $unique_hash ] ) : '';
		$filter_from_url_to   = ! empty( $_GET[ 'filter_' . $filter . '_to_' . $unique_hash ] ) ? sanitize_text_field( $_GET[ 'filter_' . $filter . '_to_' . $unique_hash ] ) : '';

		$value = array( $filter_from_url_from, $filter_from_url_to );

		return UM()->frontend()::layouts()::date_range(
			array(
				'id'      => $filter,
				'name'    => $filter,
				'label'   => $label,
				'value'   => $value,
				'min'     => $min,
				'max'     => $max,
				'classes' => array(
					'from' => array( 'um-search-filter-field' ),
					'to'   => array( 'um-search-filter-field' ),
				),
			)
		);
	}

	private function render_timepicker_admin_filter( $filter, $default_value ) {
		$value = array( '00:00', '23:59' );
		if ( $default_value ) {
			$value = $default_value;
		}
		ob_start();
		?>
		<div class="um-date-range-row">
			<label for="<?php echo esc_attr( $filter . '_from' ); ?>"><?php esc_html_e( 'From', 'ultimate-member' ); ?></label>
			<input type="time" id="<?php echo esc_attr( $filter . '_from' ); ?>" name="<?php echo esc_attr( $filter . '_from' ); ?>" data-range="from" value="<?php echo esc_attr( min( $value ) ); ?>" />
		</div>
		<div class="um-date-range-row">
			<label for="<?php echo esc_attr( $filter . '_to' ); ?>"><?php esc_html_e( 'To', 'ultimate-member' ); ?></label>
			<input type="time" id="<?php echo esc_attr( $filter . '_to' ); ?>" name="<?php echo esc_attr( $filter . '_to' ); ?>" data-range="to" value="<?php echo esc_attr( max( $value ) ); ?>" />
		</div>
		<?php
		return ob_get_clean();
	}

	private function render_timepicker_filter( $attrs, $filter, $directory_data ) {
		$unique_hash = $this->get_directory_hash( $directory_data['form_id'] );

		$range = $this->timepicker_filters_range( $filter, $directory_data );
		if ( ! $range ) {
			return '';
		}

		$label = ! empty( $attrs['label'] ) ? $attrs['label'] : $attrs['title'];
		$label = stripslashes( $label );

		list( $min, $max ) = $range;

		$filter_from_url_from = ! empty( $_GET[ 'filter_' . $filter . '_from_' . $unique_hash ] ) ? sanitize_text_field( $_GET[ 'filter_' . $filter . '_from_' . $unique_hash ] ) : '';
		$filter_from_url_to   = ! empty( $_GET[ 'filter_' . $filter . '_to_' . $unique_hash ] ) ? sanitize_text_field( $_GET[ 'filter_' . $filter . '_to_' . $unique_hash ] ) : '';

		$value = array( $filter_from_url_from, $filter_from_url_to );

		return UM()->frontend()::layouts()::time_range(
			array(
				'id'      => $filter,
				'name'    => $filter,
				'label'   => $label,
				'value'   => $value,
				'min'     => $min,
				'max'     => $max,
				'classes' => array(
					'from' => array( 'um-search-filter-field' ),
					'to'   => array( 'um-search-filter-field' ),
				),
			)
		);
	}
}
