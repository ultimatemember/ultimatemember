<?php
namespace umm\member_directory\includes\frontend;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Helpers
 *
 * @package umm\member_directory\includes\frontend
 */
class Helpers {


	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Render member's directory
	 * filters selectboxes
	 *
	 * @param string $filter
	 * @param array $directory_data
	 * @param mixed $default_value
	 * @param bool $admin
	 *
	 * @return string $filter
	 */
	public function show_filter( $filter, $directory_data, $default_value = false, $admin = false ) {
		$filter_types = UM()->module( 'member-directory' )->config()->get( 'filter_types' );
		if ( empty( $filter_types[ $filter ] ) ) {
			return '';
		}

		if ( $default_value === false ) {
			$default_filters = array();
			if ( ! empty( $directory_data['search_filters'] ) ) {
				$default_filters = maybe_unserialize( $directory_data['search_filters'] );
			}

			if ( ! empty( $default_filters[ $filter ] ) && $filter_types[ $filter ] != 'select' ) {
				return '';
			}
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
			$attrs = apply_filters( "um_custom_search_field_{$filter}", array(), $field_key );
		}

		// skip private invisible fields
		if ( ! um_can_view_field( $attrs ) ) {
			return '';
		}

		$attrs = apply_filters( 'um_search_fields', $attrs, $field_key, $directory_data['form_id'] );

		$unique_hash = substr( md5( $directory_data['form_id'] ), 10, 5 );

		ob_start();

		switch ( $filter_types[ $filter ] ) {
			default: {

				do_action( 'um_member_directory_filter_type_' . $filter_types[ $filter ], $filter, $directory_data, $unique_hash, $attrs, $default_value );
				break;
			}
			case 'text': {
				$label = '';
				if ( isset( $attrs['label'] ) ) {
					$label = $attrs['label'];
				} elseif ( ! isset( $attrs['label'] ) && isset( $attrs['title'] ) ) {
					$label = $attrs['title'];
				}

				$filter_from_url = ! empty( $_GET[ 'filter_' . $filter . '_' . $unique_hash ] ) ? sanitize_text_field( $_GET[ 'filter_' . $filter . '_' . $unique_hash ] ) : $default_value; ?>
				<label for="<?php echo esc_attr( $filter ); ?>">
					<span><?php esc_html_e( stripslashes( $label ), 'ultimate-member' ); ?></span>
				<input type="text" autocomplete="off" id="<?php echo $filter; ?>" name="<?php echo $filter; ?>"
				       placeholder="<?php esc_attr_e( stripslashes( $label ), 'ultimate-member' ); ?>"
				       value="<?php echo esc_attr( $filter_from_url ) ?>" class="um-form-field"
				       aria-label="<?php esc_attr_e( stripslashes( $label ), 'ultimate-member' ); ?>" />
				</label>
				<?php
				break;
			}
			case 'select': {

				// getting value from GET line
				$filter_from_url = ! empty( $_GET[ 'filter_' . $filter . '_' . $unique_hash ] ) ? explode( '||', sanitize_text_field( $_GET[ 'filter_' . $filter . '_' . $unique_hash ] ) ) : array();

				// new
				global $wpdb;

				if ( $attrs['metakey'] != 'role_select' ) {
					$values_array = $wpdb->get_col(
						$wpdb->prepare(
							"SELECT DISTINCT meta_value
							FROM $wpdb->usermeta
							WHERE meta_key = %s AND
								  meta_value != ''",
							$attrs['metakey']
						)
					);
				} else {
					$users_roles = count_users();
					$values_array = ( ! empty( $users_roles['avail_roles'] ) && is_array( $users_roles['avail_roles'] ) ) ? array_keys( array_filter( $users_roles['avail_roles'] ) ) : array();
				}

				if ( ! empty( $values_array ) && in_array( $attrs['type'], array( 'select', 'multiselect', 'checkbox', 'radio' ) ) ) {
					$values_array = array_map( 'maybe_unserialize', $values_array );
					$temp_values = array();
					foreach ( $values_array as $values ) {
						if ( is_array( $values ) ) {
							$temp_values = array_merge( $temp_values, $values );
						} else {
							$temp_values[] = $values;
						}
					}
					$values_array = array_unique( $temp_values );
				}

				if ( $attrs['metakey'] != 'online_status' && empty( $values_array ) ) {
					ob_get_clean();
					return '';
				}

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
					} else {
						$attrs['options'] = array();

						foreach ( $um_roles as $key => $value ) {
							$attrs['options'][ $key ] = $value;
						}
					}
				}

				$custom_dropdown = '';
				if ( ! empty( $attrs['custom_dropdown_options_source'] ) ) {
					$attrs['custom'] = true;

					if ( ! empty( $attrs['parent_dropdown_relationship'] ) ) {

						$custom_dropdown .= ' data-member-directory="yes"';
						$custom_dropdown .= ' data-um-parent="' . esc_attr( $attrs['parent_dropdown_relationship'] ) . '"';

						if ( isset( $_GET[ 'filter_' . $attrs['parent_dropdown_relationship'] . '_' . $unique_hash ] ) ) {
							$_POST['parent_option_name'] = $attrs['parent_dropdown_relationship'];
							$_POST['parent_option'] = explode( '||', filter_input( INPUT_GET, 'filter_' . $attrs['parent_dropdown_relationship'] . '_' . $unique_hash ) );
						}
					}

					$attrs['custom_dropdown_options_source'] = wp_unslash( $attrs['custom_dropdown_options_source'] );

					$ajax_source = apply_filters( "um_custom_dropdown_options_source__{$filter}", $attrs['custom_dropdown_options_source'], $attrs );
					$custom_dropdown .= ' data-um-ajax-source="' . esc_attr( $ajax_source ) . '" ';

					$attrs['options'] = UM()->fields()->get_options_from_callback( $attrs, $attrs['type'] );
				} else {
					$option_pairs = apply_filters( 'um_select_options_pair', null, $attrs );
				}

				if ( $attrs['metakey'] != 'online_status' ) {
					if ( $attrs['metakey'] != 'role_select' && $attrs['metakey'] != 'mycred_rank' && empty( $custom_dropdown ) && empty( $option_pairs ) ) {
						$attrs['options'] = array_intersect( array_map( 'stripslashes', array_map( 'trim', $attrs['options'] ) ), $values_array );
					} elseif ( ! empty( $custom_dropdown ) ) {
						$attrs['options'] = array_intersect_key( array_map( 'trim', $attrs['options'] ), array_flip( $values_array ) );
					} else {
						$attrs['options'] = array_intersect_key( array_map( 'trim', $attrs['options'] ), array_flip( $values_array ) );
					}
				}

				$attrs['options'] = apply_filters( 'um_member_directory_filter_select_options', $attrs['options'], $values_array, $attrs );

				if ( empty( $attrs['options'] ) || ! is_array( $attrs['options'] ) ) {
					ob_get_clean();
					return '';
				}

				if ( ! empty( $attrs['custom_dropdown_options_source'] ) && ! empty( $attrs['parent_dropdown_relationship'] ) ) {
					$attrs['options'] = array();
				}

				if ( isset( $attrs['label'] ) ) {
					$attrs['label'] = strip_tags( $attrs['label'] );
				}

				if ( ! empty( $default_filters[ $filter ] ) ) {
					$attrs['options'] = array_intersect( $attrs['options'], $default_filters[ $filter ] );
				}

				ksort( $attrs['options'] );

				$attrs['options'] = apply_filters( 'um_member_directory_filter_select_options_sorted', $attrs['options'], $attrs );

				$label = '';
				if ( isset( $attrs['label'] ) ) {
					$label = $attrs['label'];
				} elseif ( ! isset( $attrs['label'] ) && isset( $attrs['title'] ) ) {
					$label = $attrs['title'];
				}
				?>

				<label for="<?php echo esc_attr( $filter ); ?>"><span><?php esc_html_e( stripslashes( $label ), 'ultimate-member' ); ?></span>
				<select class="um-s1" id="<?php echo esc_attr( $filter ); ?>" name="<?php echo esc_attr( $filter ); ?><?php if ( $admin && count( $attrs['options'] ) > 1 ) { ?>[]<?php } ?>"
				        data-placeholder="<?php esc_attr_e( stripslashes( $label ), 'ultimate-member' ); ?>"
				        aria-label="<?php esc_attr_e( stripslashes( $label ), 'ultimate-member' ); ?>"
				        <?php if ( $admin && count( $attrs['options'] ) > 1 ) { ?>multiple<?php } ?>
					<?php echo $custom_dropdown; ?>>

					<option></option>

					<?php if ( ! empty( $attrs['options'] ) ) {
						foreach ( $attrs['options'] as $k => $v ) {

							$v = stripslashes( $v );

							$opt = $v;

							if ( strstr( $filter, 'role_' ) || $filter == 'role' ) {
								$opt = $k;
							}

							if ( isset( $attrs['custom'] ) ) {
								$opt = $k;
							}

							if ( ! empty( $option_pairs ) ) {
								$opt = $k;
							} ?>

							<option value="<?php echo esc_attr( $opt ); ?>" data-value_label="<?php esc_attr_e( $v, 'ultimate-member' ); ?>"
								<?php disabled( ! empty( $filter_from_url ) && in_array( $opt, $filter_from_url ) );

								if ( $admin ) {
									if ( ! is_array( $default_value ) ) {
										$default_value = array( $default_value );
									}

									selected( in_array( $opt, $default_value ) );
								} else {
									selected( $opt === $default_value );
								} ?>>
								<?php _e( $v, 'ultimate-member' ); ?>
							</option>

						<?php }
					} ?>

				</select>
				</label>

				<?php break;
			}
			case 'slider': {
				$range = $this->slider_filters_range( $filter, $directory_data );
				if ( $range ) {
					list( $single_placeholder, $plural_placeholder ) = $this->slider_range_placeholder( $filter, $attrs ); ?>

					<input type="hidden" id="<?php echo $filter; ?>_min" name="<?php echo $filter; ?>[]" class="um_range_min" value="<?php echo ! empty( $default_value ) ? esc_attr( min( $default_value ) ) : '' ?>" />
					<input type="hidden" id="<?php echo $filter; ?>_max" name="<?php echo $filter; ?>[]" class="um_range_max" value="<?php echo ! empty( $default_value ) ? esc_attr( max( $default_value ) ) : '' ?>" />
					<div class="um-slider" data-field_name="<?php echo $filter; ?>" data-min="<?php echo esc_attr( $range[0] ); ?>" data-max="<?php echo esc_attr( $range[1] ); ?>"></div>
					<div class="um-slider-range" data-placeholder-s="<?php echo esc_attr( $single_placeholder ); ?>" data-placeholder-p="<?php echo esc_attr( $plural_placeholder ); ?>" data-label="<?php echo ( ! empty( $attrs['label'] ) ) ? esc_attr__( stripslashes( $attrs['label'] ), 'ultimate-member' ) : ''; ?>"></div>
				<?php }

				break;
			}
			case 'datepicker': {

				$range = $this->datepicker_filters_range( $filter );

				$label = ! empty( $attrs['label'] ) ? $attrs['label'] : $attrs['title'];

				if ( $range ) { ?>
					<label for="<?php echo esc_attr( $filter ); ?>_from"><span><?php esc_html_e( sprintf( '%s From', stripslashes( $label ) ), 'ultimate-member' ); ?></span>
						<input type="date" id="<?php echo $filter; ?>_from" name="<?php echo $filter; ?>_from" class="um-datepicker-filter"
						       data-filter-label="<?php echo esc_attr( stripslashes( $label ) ); ?>"
						       min="<?php echo esc_attr( date( 'Y-m-d', $range[0] ) ); ?>" max="<?php echo esc_attr( date( 'Y-m-d', $range[1] ) ); ?>"
						       data-filter_name="<?php echo $filter; ?>" data-range="from" data-value="<?php echo ! empty( $default_value ) ? esc_attr( strtotime( min( $default_value ) ) ) : '' ?>" />
					</label>

					<label for="<?php echo esc_attr( $filter ); ?>_to"><span><?php esc_html_e( sprintf( '%s To', stripslashes( $label ) ), 'ultimate-member' ); ?></span>
						<input type="date" id="<?php echo $filter; ?>_to" name="<?php echo $filter; ?>_to" class="um-datepicker-filter"
						       data-filter-label="<?php echo esc_attr( stripslashes( $label ) ); ?>"
						       min="<?php echo esc_attr( date( 'Y-m-d', $range[0] ) ); ?>" max="<?php echo esc_attr( date( 'Y-m-d', $range[1] ) ); ?>"
						       data-filter_name="<?php echo $filter; ?>" data-range="to" data-value="<?php echo ! empty( $default_value ) ? esc_attr( strtotime( max( $default_value ) ) ) : '' ?>" />
					</label>


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
					<label for="<?php echo esc_attr( $filter ); ?>_from"><span><?php esc_html_e( sprintf( '%s From', stripslashes( $label ) ), 'ultimate-member' ); ?></span>
						<input type="time" id="<?php echo $filter; ?>_from" name="<?php echo $filter; ?>_from" class="um-timepicker-filter"
						       data-filter-label="<?php echo esc_attr( stripslashes( $label ) ); ?>"
						       min="<?php echo esc_attr( date( 'H:i:s', $range[0] ) ); ?>" max="<?php echo esc_attr( date( 'H:i:s', $range[1] ) ); ?>"
						       step="<?php echo esc_attr( $attrs['intervals'] ) ?>"
						       data-filter_name="<?php echo $filter; ?>" data-range="from" /></label>

					<label for="<?php echo esc_attr( $filter ); ?>_to"><span><?php esc_html_e( sprintf( '%s To', stripslashes( $label ) ), 'ultimate-member' ); ?></span>
						<input type="time" id="<?php echo $filter; ?>_to" name="<?php echo $filter; ?>_to" class="um-timepicker-filter"
						       data-filter-label="<?php echo esc_attr( stripslashes( $label ) ); ?>"
						       min="<?php echo esc_attr( date( 'H:i:s', $range[0] ) ); ?>" max="<?php echo esc_attr( date( 'H:i:s', $range[1] ) ); ?>"
						       step="<?php echo esc_attr( $attrs['intervals'] ) ?>"
						       data-filter_name="<?php echo $filter; ?>" data-range="to" />
					</label>


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

				$meta = $wpdb->get_row(
					"SELECT MIN( meta_value ) as min_meta,
					MAX( meta_value ) as max_meta,
					COUNT( DISTINCT meta_value ) as amount
					FROM {$wpdb->usermeta}
					WHERE meta_key = 'birth_date' AND
						  meta_value != ''",
					ARRAY_A
				);

				if ( isset( $meta['min_meta'] ) && isset( $meta['max_meta'] ) && isset( $meta['amount'] ) && $meta['amount'] > 1 ) {
					$range = array( $this->borndate( strtotime( $meta['max_meta'] ) ), $this->borndate( strtotime( $meta['min_meta'] ) ) );
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
				$label = ! empty( $attrs['label'] ) ? $attrs['label'] : $filter;
				$label = ucwords( str_replace( array( 'um_', '_' ), array( '', ' ' ), $label ) );
				$placeholders = apply_filters( 'um_member_directory_filter_slider_range_placeholder', false, $filter );

				if ( ! $placeholders ) {
					switch ( $attrs['type'] ) {
						default:
							$placeholders = array(
								"<strong>$label:</strong>&nbsp;{value}",
								"<strong>$label:</strong>&nbsp;{min_range} - {max_range}",
							);
							break;
						case 'rating':
							$placeholders = array(
								"<strong>$label:</strong>&nbsp;{value}" . __( ' stars', 'ultimate-member' ),
								"<strong>$label:</strong>&nbsp;{min_range} - {max_range}" . __( ' stars', 'ultimate-member' )
							);
							break;
					}
				}

				break;
			}
			case 'birth_date': {
				$placeholders = array(
					__( '<strong>Age:</strong>&nbsp;{value} years old', 'ultimate-member' ),
					__( '<strong>Age:</strong>&nbsp;{min_range} - {max_range} years old', 'ultimate-member' )
				);
				break;
			}
		}

		return $placeholders;
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
		if ( date( 'm', $borndate ) > date( 'm' ) || date( 'm', $borndate ) == date( 'm' ) && date( 'd', $borndate ) > date( 'd' ) ) {
			return date( 'Y' ) - date( 'Y', $borndate ) - 1;
		}

		return date( 'Y' ) - date( 'Y', $borndate );
	}
}
