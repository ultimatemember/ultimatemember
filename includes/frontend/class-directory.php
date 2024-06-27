<?php
namespace um\frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Directory
 *
 * @package um\frontend
 */
class Directory extends \um\common\Directory {

	public function get_filter_data( $filter, $directory_data, $default_value = false ) {
		$filter_content = $this->show_filter( $filter, $directory_data );
		$type           = $this->filter_types[ $filter ];
		$unique_hash    = $this->get_directory_hash( $directory_data['form_id'] );

		$filter_from_url = ! empty( $_GET[ 'filter_' . $filter . '_' . $unique_hash ] ) ? sanitize_text_field( $_GET[ 'filter_' . $filter . '_' . $unique_hash ] ) : $default_value;
	}

	/**
	 * @deprecated
	 * @param $search_filters
	 * @param $args
	 *
	 * @return false|string
	 */
	public function get_filters_hash( $search_filters, $args ) {
		$hash_entities = array();
		foreach ( $search_filters as $filter => $filter_data ) {
			switch ( $filter_data['type'] ) {
				case 'text':
					$filter_from_url = ! empty( $_GET[ 'filter_' . $filter . '_' . $args['unique_hash'] ] ) ? sanitize_text_field( $_GET[ 'filter_' . $filter . '_' . $args['unique_hash'] ] ) : '';
					if ( empty( $filter_from_url ) ) {
						continue 2;
					}
					$hash_entities[ $filter ] = $filter_from_url;
					break;

				case 'select':
					// getting value from GET line
					$filter_from_url = ! empty( $_GET[ 'filter_' . $filter . '_' . $args['unique_hash'] ] ) ? explode( '||', sanitize_text_field( $_GET[ 'filter_' . $filter . '_' . $args['unique_hash'] ] ) ) : array();
					if ( empty( $filter_from_url ) ) {
						continue 2;
					}
					$hash_entities[ $filter ] = $filter_from_url;
					break;

				case 'slider':
					$range                = $this->slider_filters_range( $filter, $args );
					$filter_from_url_from = ! empty( $_GET[ 'filter_' . $filter . '_from_' . $args['unique_hash'] ] ) ? sanitize_text_field( $_GET[ 'filter_' . $filter . '_from_' . $args['unique_hash'] ] ) : '';
					$filter_from_url_to   = ! empty( $_GET[ 'filter_' . $filter . '_to_' . $args['unique_hash'] ] ) ? sanitize_text_field( $_GET[ 'filter_' . $filter . '_to_' . $args['unique_hash'] ] ) : '';

					$filter_from_url_from = $filter_from_url_from !== $range[0] ? $filter_from_url_from : '';
					$filter_from_url_to   = $filter_from_url_to !== $range[1] ? $filter_from_url_to : '';

					if ( empty( $filter_from_url_from ) && empty( $filter_from_url_to ) ) {
						continue 2;
					}
					$hash_entities[ $filter ] = array( $filter_from_url_from, $filter_from_url_to );
					break;
				case 'datepicker':
				case 'timepicker':
					$filter_from_url_from = ! empty( $_GET[ 'filter_' . $filter . '_from_' . $args['unique_hash'] ] ) ? sanitize_text_field( $_GET[ 'filter_' . $filter . '_from_' . $args['unique_hash'] ] ) : '';
					$filter_from_url_to   = ! empty( $_GET[ 'filter_' . $filter . '_to_' . $args['unique_hash'] ] ) ? sanitize_text_field( $_GET[ 'filter_' . $filter . '_to_' . $args['unique_hash'] ] ) : '';
					if ( empty( $filter_from_url_from ) && empty( $filter_from_url_to ) ) {
						continue 2;
					}
					$hash_entities[ $filter ] = array( $filter_from_url_from, $filter_from_url_to );
					break;
			}
		}

		if ( empty( $hash_entities ) ) {
			return '';
		}

		$json_hash = wp_json_encode( $hash_entities );
		/*$json_hash_length = mb_strlen( $json_hash );
		for ( $i = 0; $i < $json_hash_length; $i++ ) {
			$utf8Character = 'Ą';
			list(, $ord) = unpack('N', mb_convert_encoding($utf8Character, 'UCS-4BE', 'UTF-8'));
			echo $ord; # 260
		}

		for (let i = 0; i < $json_hash.length; i++) {
			const char = str.charCodeAt(i);
			hash = ((hash << 5) - hash) + char;
			hash |= 0; // Convert to 32-bit integer
		}*/

		return $json_hash;
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
		if ( empty( $this->filter_types[ $filter ] ) ) {
			return '';
		}

		if ( false === $default_value ) {
			$default_filters = array();
			if ( ! empty( $directory_data['search_filters'] ) ) {
				$default_filters = maybe_unserialize( $directory_data['search_filters'] );
			}

			if ( ! empty( $default_filters[ $filter ] ) && 'select' !== $this->filter_types[ $filter ] ) {
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

		// skip private invisible fields
		if ( ! um_can_view_field( $attrs ) ) {
			return '';
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
		$attrs = apply_filters( 'um_search_fields', $attrs, $field_key, $directory_data['form_id'] );

		$unique_hash = substr( md5( $directory_data['form_id'] ), 10, 5 );

		ob_start();

		switch ( $this->filter_types[ $filter ] ) {
			default:
				do_action( "um_member_directory_filter_type_{$this->filter_types[ $filter ]}", $filter, $directory_data, $unique_hash, $attrs, $default_value );
				break;

			case 'text':
				$label = '';
				if ( isset( $attrs['label'] ) ) {
					$label = $attrs['label'];
				} elseif ( isset( $attrs['title'] ) ) {
					$label = $attrs['title'];
				}

				$label = stripslashes( $label );

				$filter_from_url = ! empty( $_GET[ 'filter_' . $filter . '_' . $unique_hash ] ) ? sanitize_text_field( $_GET[ 'filter_' . $filter . '_' . $unique_hash ] ) : $default_value;
				?>
				<div class="um-field-wrapper">
					<label for="<?php echo esc_attr( $filter ); ?>"><?php echo esc_html( stripslashes( $label ) ); ?></label>
					<input type="text" autocomplete="off" id="<?php echo esc_attr( $filter ); ?>" name="<?php echo esc_attr( $filter ); ?>"
						placeholder="<?php echo esc_attr( $label ); ?>"
						value="<?php echo esc_attr( $filter_from_url ); ?>" class="um-search-filter-field"
						aria-label="<?php echo esc_attr( $label ); ?>" />
				</div>
				<?php
				break;

			case 'select':
				// getting value from GET line
				$filter_from_url = ! empty( $_GET[ 'filter_' . $filter . '_' . $unique_hash ] ) ? explode( '||', sanitize_text_field( $_GET[ 'filter_' . $filter . '_' . $unique_hash ] ) ) : array();

				// new
				global $wpdb;

				if ( $attrs['metakey'] !== 'role_select' ) {
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

				if ( ! empty( $values_array ) && in_array( $attrs['type'], array( 'select', 'multiselect', 'checkbox', 'radio' ), true ) ) {
					$values_array = array_map( 'maybe_unserialize', $values_array );
					$temp_values  = array();
					foreach ( $values_array as $values ) {
						if ( is_array( $values ) ) {
							$temp_values = array_merge( $temp_values, $values );
						} else {
							$temp_values[] = $values;
						}
					}
					$values_array = array_unique( $temp_values );
				}

				if ( 'online_status' !== $attrs['metakey'] && empty( $values_array ) ) {
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

							$parent_option_value    = sanitize_text_field( $_GET[ 'filter_' . $attrs['parent_dropdown_relationship'] . '_' . $unique_hash ] );
							$_POST['parent_option'] = explode( '||', $parent_option_value );
						}
					}

					$attrs['custom_dropdown_options_source'] = wp_unslash( $attrs['custom_dropdown_options_source'] );

					$ajax_source = apply_filters( "um_custom_dropdown_options_source__{$filter}", $attrs['custom_dropdown_options_source'], $attrs );

					$custom_dropdown .= ' data-um-ajax-source="' . esc_attr( $ajax_source ) . '" ';

					$attrs['options'] = UM()->fields()->get_options_from_callback( $attrs, $attrs['type'] );
				} else {
					/** This filter is documented in includes/core/class-fields.php */
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

				if ( ( empty( $attrs['options'] ) || ! is_array( $attrs['options'] ) ) && ! ( ! empty( $attrs['custom_dropdown_options_source'] ) && ! empty( $attrs['parent_dropdown_relationship'] ) ) ) {
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
				<div class="um-field-wrapper">
					<label for="<?php echo esc_attr( $filter ); ?>"><?php echo esc_html( stripslashes( $label ) ); ?></label>
					<select class="js-choice um-search-filter-field" id="<?php echo esc_attr( $filter ); ?>" name="<?php echo esc_attr( $filter ); ?><?php if ( $admin && count( $attrs['options'] ) > 1 ) { ?>[]<?php } ?>"
							data-placeholder="<?php esc_attr_e( stripslashes( $label ), 'ultimate-member' ); ?>"
							aria-label="<?php esc_attr_e( stripslashes( $label ), 'ultimate-member' ); ?>"
							<?php if ( count( $attrs['options'] ) > 1 ) { ?>multiple<?php } ?>
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
								}
								?>
								<option value="<?php echo esc_attr( $opt ); ?>" data-value_label="<?php esc_attr_e( $v, 'ultimate-member' ); ?>"
									<?php
									if ( $admin ) {
										if ( ! is_array( $default_value ) ) {
											$default_value = array( $default_value );
										}

										selected( in_array( $opt, $default_value ) );
									} else {
										selected( $opt === $default_value || ( ! empty( $filter_from_url ) && in_array( $opt, $filter_from_url, true ) ) );
									} ?>>
									<?php _e( $v, 'ultimate-member' ); ?>
								</option>

							<?php }
						} ?>

					</select>
				</div>
				<?php
				break;

			case 'slider':
				$range = $this->slider_filters_range( $filter, $directory_data );
				if ( $range ) {
					$label = '';
					if ( isset( $attrs['label'] ) ) {
						$label = $attrs['label'];
					} elseif ( ! isset( $attrs['label'] ) && isset( $attrs['title'] ) ) {
						$label = $attrs['title'];
					}

					if ( $default_value ) {
						$value = $default_value;
					} else {
						$value = $range;
					}

					list( $single_placeholder, $plural_placeholder ) = $this->slider_range_placeholder( $filter, $attrs );

					echo wp_kses(
						UM()->frontend()::layouts()::range(
							array(
								'label'       => stripslashes( $label ),
								'name'        => $filter,
								'classes' => array(
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
						),
						UM()->get_allowed_html( 'templates' )
					);
				}
				break;

			case 'datepicker':
				$range = $this->datepicker_filters_range( $filter );

				$label = ! empty( $attrs['label'] ) ? $attrs['label'] : $attrs['title'];
				$label = stripslashes( $label );

				if ( $range ) {
					list( $min, $max ) = $range;

					if ( $default_value ) {
						$value = $default_value;
					} else {
						$value = 0;
					}

					echo wp_kses(
						UM()->frontend()::layouts()::date_range(
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
						),
						UM()->get_allowed_html( 'templates' )
					);
				}
				break;
			case 'timepicker':
				$range = $this->timepicker_filters_range( $filter );

				$label = ! empty( $attrs['label'] ) ? $attrs['label'] : $attrs['title'];
				$label = stripslashes( $label );

				if ( $range ) {
					list( $min, $max ) = $range;

					if ( $default_value ) {
						$value = $default_value;
					} else {
						$value = 0;
					}

					echo wp_kses(
						UM()->frontend()::layouts()::time_range(
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
						),
						UM()->get_allowed_html( 'templates' )
					);
				}

				break;
		}

		return ob_get_clean();
	}
}
