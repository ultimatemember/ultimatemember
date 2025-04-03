<?php
namespace um\core;

use um\common\Directory;
use Exception;
use WP_User_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\core\Member_Directory' ) ) {

	/**
	 * Class Member_Directory
	 * @package um\core
	 */
	class Member_Directory extends Directory {

		/**
		 * @var array
		 */
		public $custom_filters_in_query = array();

		/**
		 * @var
		 */
		public $query_args;

		/**
		 * @var bool Searching marker
		 */
		public $is_search = false;

		/**
		 * Member_Directory constructor.
		 */
		public function __construct() {
			parent::__construct();
			add_action( 'wp_ajax_nopriv_um_get_members', array( $this, 'ajax_get_members' ) );
			add_action( 'wp_ajax_um_get_members', array( $this, 'ajax_get_members' ) );
			add_action( 'wp_ajax_um_member_directory_default_filter_settings', array( $this, 'default_filter_settings' ) );
		}

		/**
		 * Tag conversion for member directory
		 *
		 * @param string $string
		 * @param array $array
		 *
		 * @return string
		 */
		protected function convert_tags( $string, $array ) {
			$search = array(
				'{total_users}',
			);

			$replace = array(
				$array['total_users'],
			);

			return str_replace( $search, $replace, $string );
		}

		/**
		 * Render member's directory filters. Deprecated in new UI.
		 *
		 * @todo deprecate since new UI is live
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
				/** This filter is documented in ultimate-member/includes/common/class-directory.php */
				$attrs = apply_filters( "um_custom_search_field_{$filter}", array(), $field_key );
			}

			// skip private invisible fields
			if ( ! um_can_view_field( $attrs ) ) {
				return '';
			}

			/** This filter is documented in ultimate-member/includes/common/class-directory.php */
			$attrs = apply_filters( 'um_search_fields', $attrs, $field_key, $directory_data['form_id'] );

			$unique_hash = $this->get_directory_hash( $directory_data['form_id'] );

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
					<input type="text" autocomplete="off" id="<?php echo esc_attr( $filter ); ?>" name="<?php echo esc_attr( $filter ); ?>"
					       placeholder="<?php echo esc_attr( $label ); ?>"
					       value="<?php echo esc_attr( $filter_from_url ); ?>" class="um-form-field"
					       aria-label="<?php echo esc_attr( $label ); ?>" />
					<?php
					break;

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
				case 'datepicker':
					$range = $this->datepicker_filters_range( $filter );

					$label = ! empty( $attrs['label'] ) ? $attrs['label'] : $attrs['title'];
					$label = stripslashes( $label );

					$default_value_min = '';
					$default_value_max = '';
					if ( ! empty( $default_value[0] ) ) {
						$default_value_min = $default_value[0];
					}
					if ( ! empty( $default_value[1] ) ) {
						$default_value_max = $default_value[1];
					}

					if ( $range ) {
						list( $min, $max ) = $range;
						?>
						<input type="text" id="<?php echo esc_attr( $filter ); ?>_from" name="<?php echo esc_attr( $filter ); ?>_from" class="um-datepicker-filter"
							<?php // translators: %s: Datetime filter label. ?>
							   placeholder="<?php echo esc_attr( sprintf( __( '%s From', 'ultimate-member' ), $label ) ); ?>"
							   data-filter-label="<?php echo esc_attr( $label ); ?>"
							   data-date_min="<?php echo esc_attr( $min ); ?>" data-date_max="<?php echo esc_attr( $max ); ?>"
							   data-filter_name="<?php echo esc_attr( $filter ); ?>" data-range="from" data-value="<?php echo ! empty( $default_value_min ) ? esc_attr( strtotime( $default_value_min ) ) : ''; ?>" />
						<input type="text" id="<?php echo esc_attr( $filter ); ?>_to" name="<?php echo esc_attr( $filter ); ?>_to" class="um-datepicker-filter"
							<?php // translators: %s: Datetime filter label. ?>
							   placeholder="<?php echo esc_attr( sprintf( __( '%s To', 'ultimate-member' ), $label ) ); ?>"
							   data-filter-label="<?php echo esc_attr( $label ); ?>"
							   data-date_min="<?php echo esc_attr( $min ); ?>" data-date_max="<?php echo esc_attr( $max ); ?>"
							   data-filter_name="<?php echo esc_attr( $filter ); ?>" data-range="to" data-value="<?php echo ! empty( $default_value_max ) ? esc_attr( strtotime( $default_value_max ) ) : ''; ?>" />
						<?php
					}
					break;
				case 'timepicker':
					$range = $this->timepicker_filters_range( $filter );

					$label = ! empty( $attrs['label'] ) ? $attrs['label'] : $attrs['title'];
					$label = stripslashes( $label );

					switch ( $attrs['format'] ) {
						case 'g:i a':
						default:
							$js_format = 'h:i a';
							break;
						case 'g:i A':
							$js_format = 'h:i A';
							break;
						case 'H:i':
							$js_format = 'HH:i';
							break;
					}

					$default_value_min = '';
					$default_value_max = '';
					if ( ! empty( $default_value[0] ) ) {
						$default_value_min = $default_value[0];
					}
					if ( ! empty( $default_value[1] ) ) {
						$default_value_max = $default_value[1];
					}

					if ( $range ) {
						?>
						<input type="text" id="<?php echo esc_attr( $filter ); ?>_from" name="<?php echo esc_attr( $filter ); ?>_from" class="um-timepicker-filter"
							<?php // translators: %s: Timepicker filter label. ?>
							   placeholder="<?php echo esc_attr( sprintf( __( '%s From', 'ultimate-member' ), $label ) ); ?>"
							   data-filter-label="<?php echo esc_attr( $label ); ?>"
							   data-min="<?php echo esc_attr( $range[0] ); ?>" data-max="<?php echo esc_attr( $range[1] ); ?>"
							   data-format="<?php echo esc_attr( $js_format ); ?>" data-intervals="<?php echo esc_attr( $attrs['intervals'] ); ?>"
							   data-filter_name="<?php echo esc_attr( $filter ); ?>" data-range="from" data-value="<?php echo ! empty( $default_value_min ) ? esc_attr( $default_value_min ) : ''; ?>" />
						<input type="text" id="<?php echo esc_attr( $filter ); ?>_to" name="<?php echo esc_attr( $filter ); ?>_to" class="um-timepicker-filter"
							<?php // translators: %s: Timepicker filter label. ?>
							   placeholder="<?php echo esc_attr( sprintf( __( '%s To', 'ultimate-member' ), $label ) ); ?>"
							   data-filter-label="<?php echo esc_attr( $label ); ?>"
							   data-min="<?php echo esc_attr( $range[0] ); ?>" data-max="<?php echo esc_attr( $range[1] ); ?>"
							   data-format="<?php echo esc_attr( $js_format ); ?>" data-intervals="<?php echo esc_attr( $attrs['intervals'] ); ?>"
							   data-filter_name="<?php echo esc_attr( $filter ); ?>" data-range="to" data-value="<?php echo ! empty( $default_value_max ) ? esc_attr( $default_value_max ) : ''; ?>" />
						<?php
					}

					break;
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

					$meta = $wpdb->get_row(
						$wpdb->prepare(
							"SELECT MIN( CONVERT( meta_value, DECIMAL ) ) as min_meta,
							MAX( CONVERT( meta_value, DECIMAL ) ) as max_meta,
							COUNT( DISTINCT meta_value ) as amount
							FROM {$wpdb->usermeta}
							WHERE meta_key = %s",
							$filter
						),
						ARRAY_A
					);

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
		public function datepicker_filters_range( $filter, $directory_data = null ) {
			global $wpdb;

			switch ( $filter ) {
				default:
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
		protected function timepicker_filters_range( $filter, $directory_data = null ) {
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
				'key'       => 'um_member_directory_data',
				'value'     => 's:14:"account_status";s:8:"approved";',
				'compare'   => 'LIKE'
			) ) );
		}


		/**
		 *
		 */
		function hide_by_role() {
			if ( ! is_user_logged_in() ) {
				return;
			}

			$roles = um_user( 'can_view_roles' );
			$roles = maybe_unserialize( $roles );

			if ( UM()->roles()->um_user_can( 'can_view_all' ) && empty( $roles ) ) {
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

			$this->query_args['meta_query'] = array_merge( $this->query_args['meta_query'], array( array(
				'key'       => 'um_member_directory_data',
				'value'     => 's:15:"hide_in_members";b:0;',
				'compare'   => 'LIKE'
			) ) );
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
			$this->exclude_these_users( $directory_data );

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
				if ( ! empty( $this->query_args['role__in'] ) ) {
					$this->query_args['role__in'] = is_array( $this->query_args['role__in'] ) ? $this->query_args['role__in'] : array( $this->query_args['role__in'] );
					$this->query_args['role__in'] = array_intersect( $this->query_args['role__in'], maybe_unserialize( $directory_data['roles'] ) );
				} else {
					$this->query_args['role__in'] = maybe_unserialize( $directory_data['roles'] );
				}
			}
		}


		/**
		 * Handle "Only show members who have uploaded a profile photo" option
		 *
		 * @param array $directory_data
		 */
		function show_only_with_avatar( $directory_data ) {
			if ( $directory_data['has_profile_photo'] == 1 ) {
				$this->query_args['meta_query'] = array_merge( $this->query_args['meta_query'], array( array(
					'key'       => 'um_member_directory_data',
					'value'     => 's:13:"profile_photo";b:1;',
					'compare'   => 'LIKE'
				) ) );
			}
		}


		/**
		 * Handle "Only show members who have uploaded a cover photo" option
		 *
		 * @param array $directory_data
		 */
		function show_only_with_cover( $directory_data ) {
			if ( ! UM()->is_new_ui() && ! empty( $directory_data['has_cover_photo'] ) ) { // @todo maybe remove if cover photos backs to user profile
				$this->query_args['meta_query'] = array_merge(
					$this->query_args['meta_query'],
					array(
						array(
							'key'     => 'um_member_directory_data',
							'value'   => 's:11:"cover_photo";b:1;',
							'compare' => 'LIKE',
						),
					)
				);
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
		 * Handle "Exclude specific users (Enter one username per line)" option
		 *
		 * @param array $directory_data
		 */
		function exclude_these_users( $directory_data ) {
			if ( ! empty( $directory_data['exclude_these_users'] ) ) {
				$exclude_these_users = maybe_unserialize( $directory_data['exclude_these_users'] );

				if ( is_array( $exclude_these_users ) && ! empty( $exclude_these_users ) ) {

					$users_array = array();

					foreach ( $exclude_these_users as $username ) {
						if ( false !== ( $exists_id = username_exists( $username ) ) ) {
							$users_array[] = $exists_id;
						}
					}

					if ( ! empty( $users_array ) ) {
						$this->query_args['exclude'] = $users_array;
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
			if ( wp_is_mobile() && isset( $directory_data['profiles_per_page_mobile'] ) ) {
				$profiles_per_page = $directory_data['profiles_per_page_mobile'];
			}

			$this->query_args['number'] = ( ! empty( $directory_data['max_users'] ) && $directory_data['max_users'] <= $profiles_per_page ) ? $directory_data['max_users'] : $profiles_per_page;
			$this->query_args['paged'] = ! empty( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
		}

		/**
		 * Add sorting attributes for \WP_Users_Query
		 *
		 * @param array $directory_data Member Directory options
		 */
		public function sorting_query( $directory_data ) {
			// sort members by
			$this->query_args['order'] = 'ASC';

			$sortby = ! empty( $_POST['sorting'] ) ? sanitize_text_field( $_POST['sorting'] ) : $directory_data['sortby'];
			$sortby = ( 'other' === $sortby ) ? $directory_data['sortby_custom'] : $sortby;

			$custom_sort = array();
			if ( ! empty( $directory_data['sorting_fields'] ) ) {
				$sorting_fields = maybe_unserialize( $directory_data['sorting_fields'] );
				foreach ( $sorting_fields as $field ) {
					if ( is_array( $field ) ) {
						$field_keys    = array_keys( $field );
						$custom_sort[] = $field_keys[0];
					}
				}
			}

			$numeric_sorting_keys = array();

			if ( ! empty( UM()->builtin()->saved_fields ) ) {
				foreach ( UM()->builtin()->saved_fields as $key => $data ) {
					if ( '_um_last_login' === $key ) {
						continue;
					}

					if ( isset( $data['type'] ) && 'number' === $data['type'] ) {
						if ( array_key_exists( $key . '_desc', $this->sort_fields ) ) {
							$numeric_sorting_keys[] = $key . '_desc';
						}
						if ( array_key_exists( $key . '_asc', $this->sort_fields ) ) {
							$numeric_sorting_keys[] = $key . '_asc';
						}
					}
				}
			}

			if ( 'username' == $sortby ) {

				$this->query_args['orderby'] = 'user_login';
				$this->query_args['order'] = 'ASC';

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

			} elseif ( in_array( $sortby, array( 'last_name', 'first_name', 'nickname' ), true ) ) {

				$this->query_args['meta_query'] = array_merge( $this->query_args['meta_query'], array( $sortby . '_c' => array(
					'key'       => $sortby,
					'compare'   => 'EXISTS',
				), ) );

				$this->query_args['orderby'] = array( $sortby . '_c' => 'ASC' );
				unset( $this->query_args['order'] );

			} elseif ( 'last_login' === $sortby ) {
				$this->query_args['orderby'] = array( 'um_last_login' => 'DESC' );
				// Please use custom meta table for better results and sorting. Here we only hide the users without visible last login date.
				$this->query_args['meta_query'][] = array(
					'relation'      => 'OR',
					array(
						'key'     => '_um_last_login',
						'compare' => 'EXISTS',
						'type'    => 'DATETIME',
					),
					'um_last_login' => array(
						'key'     => '_um_last_login',
						'compare' => 'NOT EXISTS',
						'type'    => 'DATETIME',
					),
				);
				unset( $this->query_args['order'] );

				add_filter( 'pre_user_query', array( &$this, 'sortby_last_login' ) );
			} elseif ( $sortby == 'last_first_name' ) {

				$this->query_args['meta_query'][] = array(
					'last_name_c'   => array(
						'key'       => 'last_name',
						'compare'   => 'EXISTS',
					),
					'first_name_c'  => array(
						'key'       => 'first_name',
						'compare'   => 'EXISTS',
					),
				);

				$this->query_args['orderby'] = array( 'last_name_c' => 'ASC', 'first_name_c' => 'ASC' );
				unset( $this->query_args['order'] );

			} elseif ( count( $numeric_sorting_keys ) && in_array( $sortby, $numeric_sorting_keys, true ) ) {

				$order = 'DESC';
				if ( strstr( $sortby, '_desc' ) ) {
					$sortby = str_replace( '_desc', '', $sortby );
					$order = 'DESC';
				}

				if ( strstr( $sortby, '_asc' ) ) {
					$sortby = str_replace( '_asc', '', $sortby );
					$order = 'ASC';
				}

				$this->query_args['meta_query'] = array_merge(
					$this->query_args['meta_query'],
					array(
						array(
							'relation'      => 'OR',
							array(
								'key'     => $sortby,
								'compare' => 'EXISTS',
								'type'    => 'NUMERIC',
							),
							$sortby . '_ns' => array(
								'key'     => $sortby,
								'compare' => 'NOT EXISTS',
								'type'    => 'NUMERIC',
							),
						),
					)
				);

				$this->query_args['orderby'] = array(
					$sortby . '_ns'   => $order,
					'user_registered' => 'DESC',
				);
				unset( $this->query_args['order'] );

			} elseif ( ( ! empty( $directory_data['sortby_custom'] ) && $sortby === $directory_data['sortby_custom'] ) || in_array( $sortby, $custom_sort, true ) ) {
				$custom_sort_order = ! empty( $directory_data['sortby_custom_order'] ) ? $directory_data['sortby_custom_order'] : 'ASC';

				$meta_query       = new \WP_Meta_Query();
				$custom_sort_type = ! empty( $directory_data['sortby_custom_type'] ) ? $meta_query->get_cast_for_type( $directory_data['sortby_custom_type'] ) : 'CHAR';

				if ( ! empty( $directory_data['sorting_fields'] ) ) {
					// phpcs:ignore WordPress.Security.NonceVerification -- already verified here
					$sorting        = sanitize_text_field( $_POST['sorting'] );
					$sorting_fields = maybe_unserialize( $directory_data['sorting_fields'] );

					if ( ! empty( $sorting_fields ) && is_array( $sorting_fields ) ) {
						foreach ( $sorting_fields as $field ) {
							if ( isset( $field[ $sorting ] ) ) {
								$custom_sort_type  = ! empty( $field['type'] ) ? $meta_query->get_cast_for_type( $field['type'] ) : 'CHAR';
								$custom_sort_order = $field['order'];
							}
						}
					}
				}
				/**
				 * Filters the sorting MySQL type in member directory custom sorting query.
				 *
				 * Note: Possible MySQL types are BINARY|CHAR|DATE|DATETIME|SIGNED|UNSIGNED|TIME|DECIMAL
				 *
				 * @since 2.1.3
				 * @hook um_member_directory_custom_sorting_type
				 *
				 * @param {string} $custom_sort_type MySQL type to cast meta_value. 'CHAR' is default.
				 * @param {string} $sortby           meta_key used for sorting.
				 * @param {array}  $directory_data   Member directory data.
				 *
				 * @return {string} MySQL type to cast meta_value.
				 * @example <caption>Change type to DATE by the directory ID and mete_key.</caption>
				 * function my_um_member_directory_custom_sorting_type( $custom_sort_type, $sortby, $directory_data ) {
				 *     if ( '{selected member directory ID}' == $directory_data['form_id'] && '{custom_date_key}' === $sortby ) {
				 *         $custom_sort_type = 'DATE';
				 *     }
				 *
				 *     return $custom_sort_type;
				 * }
				 * add_filter( 'um_member_directory_custom_sorting_type', 'my_um_member_directory_custom_sorting_type', 10, 3 );
				 */
				$custom_sort_type = apply_filters( 'um_member_directory_custom_sorting_type', $custom_sort_type, $sortby, $directory_data );

				$this->query_args['meta_query'][] = array(
					'relation'      => 'OR',
					$sortby . '_cs' => array(
						'key'     => $sortby,
						'compare' => 'EXISTS',
						'type'    => $custom_sort_type,
					),
					array(
						'key'     => $sortby,
						'compare' => 'NOT EXISTS',
					),
				);

				$this->query_args['orderby'] = array(
					$sortby . '_cs' => $custom_sort_order,
					'user_login'    => 'ASC',
				);

			} else {

				if ( strstr( $sortby, '_desc' ) ) {
					$sortby = str_replace( '_desc', '', $sortby );
					$order  = 'DESC';
				}

				if ( strstr( $sortby, '_asc' ) ) {
					$sortby = str_replace( '_asc', '', $sortby );
					$order  = 'ASC';
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
				if ( empty( $_REQUEST['directory_id'] ) && isset( $_SESSION['um_member_directory_seed'] ) ) {
					unset( $_SESSION['um_member_directory_seed'] );
				}

				// Get seed from session variable if it exists
				$seed = false;
				if ( isset( $_SESSION['um_member_directory_seed'] ) ) {
					$seed = $_SESSION['um_member_directory_seed'];
				}

				// Set new seed if none exists
				if ( ! $seed ) {
					$seed = rand();
					$_SESSION['um_member_directory_seed'] = $seed;
				}

				$query->query_orderby = 'ORDER by RAND(' . $seed . ')';
			}

			return $query;
		}


		/**
		 * Sorting by last login
		 *
		 * @param object $query
		 *
		 * @return mixed
		 */
		public function sortby_last_login( $query ) {
			if ( array_key_exists( 'um_last_login', $query->query_vars['orderby'] ) ) {
				global $wpdb;
				$query->query_from   .= " LEFT JOIN {$wpdb->prefix}usermeta AS umm_sort ON ( umm_sort.user_id = {$wpdb->prefix}users.ID AND umm_sort.meta_key = '_um_last_login' ) ";
				$query->query_from   .= " LEFT JOIN {$wpdb->prefix}usermeta AS umm_show_login ON ( umm_show_login.user_id = {$wpdb->prefix}users.ID AND umm_show_login.meta_key = 'um_show_last_login' ) ";
				$query->query_orderby = " ORDER BY CASE ISNULL(NULLIF(umm_show_login.meta_value,'a:1:{i:0;s:3:\"yes\";}')) WHEN 0 THEN '1970-01-01 00:00:00' ELSE CAST( umm_sort.meta_value AS DATETIME ) END DESC ";
			}
			return $query;
		}

		/**
		 * Prepare the search line. Avoid the using mySQL statement.
		 *
		 * @param string $search
		 *
		 * @return string
		 */
		protected function prepare_search( $search ) {
			// unslash, sanitize, trim - necessary prepare.
			$search = trim( sanitize_text_field( wp_unslash( $search ) ) );
			if ( empty( $search ) ) {
				return '';
			}
			return $search;
		}

		/**
		 * Handle general search line request
		 */
		public function general_search() {
			// General search
			if ( ! empty( $_POST['search'] ) ) {
				// complex using with change_meta_sql function
				$search = $this->prepare_search( $_POST['search'] );
				if ( ! empty( $search ) ) {
					$meta_query = array();
					$meta_query = apply_filters( 'um_member_directory_general_search_meta_query', $meta_query, $search );

					if ( ! empty( $meta_query ) ) {
						$this->query_args['meta_query'][] = $meta_query;
					}

					$this->is_search = true;
				}
			}
		}

		/**
		 * Handle filters request
		 */
		function filters( $directory_data ) {
			global $wpdb;
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

			$this->is_search = true;
			foreach ( $filter_query as $field => $value ) {
				$field = sanitize_text_field( $field );
				if ( is_array( $value ) ) {
					$value = array_map( 'sanitize_text_field', $value );
				} else {
					$value = sanitize_text_field( $value );
				}

				$attrs = UM()->fields()->get_field( $field );
				// skip private invisible fields
				if ( ! um_can_view_field( $attrs ) ) {
					continue;
				}

				/** This filter is documented in includes/core/class-member-directory-meta.php */
				$relation = apply_filters( 'um_members_directory_select_filter_relation', 'OR', $field );

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

						$field_query = apply_filters( 'um_query_args_filter_global', $field_query, $field, $value, $filter_type );

						if ( ! $field_query ) {

							switch ( $filter_type ) {
								default:

									$field_query = apply_filters( "um_query_args_{$field}_{$filter_type}__filter", false, $field, $value, $filter_type );

									break;
								case 'text':

									$value = stripslashes( $value );
									$field_query = array(
										'relation' => 'OR',
										array(
											'key'       => $field,
											'value'     => trim( $value ),
											'compare'   => apply_filters( 'um_members_directory_filter_text', 'LIKE', $field )
										),
									);

									$this->custom_filters_in_query[ $field ] = $value;

									break;

								case 'select':
									if ( is_array( $value ) ) {
										$field_query = array( 'relation' => esc_sql( $relation ) );

										foreach ( $value as $single_val ) {
											$single_val = trim( stripslashes( $single_val ) );

											$arr_meta_query = array(
												array(
													'key'     => $field,
													'value'   => $single_val,
													'compare' => '=',
												),
												array(
													'key'     => $field,
													'value'   => serialize( (string) $single_val ),
													'compare' => 'LIKE',
												),
												array(
													'key'     => $field,
													'value'   => '"' . $single_val . '"',
													'compare' => 'LIKE',
												),
											);

											if ( is_numeric( $single_val ) ) {

												$arr_meta_query[] = array(
													'key'     => $field,
													'value'   => serialize( absint( $single_val ) ),
													'compare' => 'LIKE',
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
										'type'		=> 'NUMERIC',
									);

									break;
								case 'datepicker':

									$offset = 0;
									if ( isset( $_POST['gmt_offset'] ) && is_numeric( $_POST['gmt_offset'] ) ) {
										$offset = (int) $_POST['gmt_offset'];
									}

									$from_date = (int) min( $value ) + ( $offset * HOUR_IN_SECONDS ); // client time zone offset
									$to_date   = (int) max( $value ) + ( $offset * HOUR_IN_SECONDS ) + DAY_IN_SECONDS - 1; // time 23:59
									$from_date = date( 'Y/m/d', $from_date );
									$to_date = date( 'Y/m/d', $to_date );

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

						if ( 'OR' !== $relation ) {
							$role__in_clauses = array( 'relation' => $relation );
							foreach ( $value as $role ) {
								$role__in_clauses[] = array(
									'key'     => $wpdb->get_blog_prefix() . 'capabilities',
									'value'   => '"' . $role . '"',
									'compare' => 'LIKE',
								);
							}

							$this->query_args['meta_query'] = array_merge( $this->query_args['meta_query'], array( $role__in_clauses ) );

							$this->custom_filters_in_query[ $field ] = $value;
						} else {
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
							}

							$this->custom_filters_in_query[ $field ] = $this->query_args['role__in'];
						}
						break;
					case 'birth_date':
						$from_date = date( 'Y/m/d', mktime( 0,0,0, date( 'm', time() ), date( 'd', time() ), date( 'Y', time() - min( $value ) * YEAR_IN_SECONDS ) ) );
						$to_date   = date( 'Y/m/d', mktime( 0,0,0, date( 'm', time() ), date( 'd', time() ) + 1, date( 'Y', time() - ( max( $value ) + 1 ) * YEAR_IN_SECONDS ) ) );

						$meta_query = array(
							array(
								'key'       => 'birth_date',
								'value'     => array( $to_date, $from_date ),
								'compare'   => 'BETWEEN',
								'type'      => 'DATE',
								'inclusive' => true,
							),
						);

						$this->query_args['meta_query'] = array_merge( $this->query_args['meta_query'], array( $meta_query ) );

						$this->custom_filters_in_query[ $field ] = array( $to_date, $from_date );

						break;
					case 'user_registered':
						$offset = 0;
						if ( isset( $_POST['gmt_offset'] ) && is_numeric( $_POST['gmt_offset'] ) ) {
							$offset = (int) $_POST['gmt_offset'];
						}

						$from_date = date( 'Y-m-d H:i:s', strtotime( date( 'Y-m-d H:i:s', min( $value ) ) . "+$offset hours" ) );
						$to_date = date( 'Y-m-d H:i:s', strtotime( date( 'Y-m-d H:i:s', max( $value ) ) . "+$offset hours" ) );

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
							'relation' => 'AND',
							array(
								'key'       => '_um_last_login',
								'value'     => array( gmdate( 'Y-m-d H:i:s', $from_date ), gmdate( 'Y-m-d H:i:s', $to_date ) ),
								'compare'   => 'BETWEEN',
								'inclusive' => true,
								'type'      => 'DATETIME',
							),
							array(
								'relation' => 'OR',
								array(
									'key'     => 'um_show_last_login',
									'compare' => 'NOT EXISTS',
								),
								array(
									'key'     => 'um_show_last_login',
									'value'   => 'a:1:{i:0;s:2:"no";}',
									'compare' => '!=',
								),
							),
						);

						$this->custom_filters_in_query[ $field ] = $value;

						$this->query_args['meta_query'] = array_merge( $this->query_args['meta_query'], array( $meta_query ) );
						break;
					case 'gender':
						if ( is_array( $value ) ) {
							$field_query = array( 'relation' => $relation );

							foreach ( $value as $single_val ) {
								$single_val = trim( stripslashes( $single_val ) );

								$arr_meta_query = array(
									array(
										'key'     => $field,
										'value'   => $single_val,
										'compare' => '=',
									),
									array(
										'key'     => $field,
										'value'   => '"' . $single_val . '"',
										'compare' => 'LIKE',
									),
								);

								$field_query = array_merge( $field_query, $arr_meta_query );
							}
						}

						if ( ! empty( $field_query ) ) {
							$this->query_args['meta_query'] = array_merge( $this->query_args['meta_query'], array( $field_query ) );

							$this->custom_filters_in_query[ $field ] = $value;
						}
						break;
				}
			}
		}


		/**
		 * Set default filters
		 *
		 * @param $directory_data
		 */
		public function default_filters( $directory_data ) {
			$default_filters = array();
			if ( ! empty( $directory_data['search_filters'] ) ) {
				$default_filters = maybe_unserialize( $directory_data['search_filters'] );
			}

			$gmt_offset = get_post_meta( $directory_data['form_id'], '_um_search_filters_gmt', true );

			if ( empty( $default_filters ) ) {
				return;
			}

			foreach ( $default_filters as $field => $value ) {

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

								case 'text':
									$field_query = array(
										'key'     => $field,
										'value'   => $value,
										'compare' => apply_filters( 'um_members_directory_filter_text', '=', $field ),
									);
									break;

								case 'select':
									if ( ! is_array( $value ) ) {
										$value = array( $value );
									}

									/** This filter is documented in includes/core/class-member-directory.php */
									$field_query = apply_filters( 'um_members_directory_filter_select', array( 'relation' => 'OR' ), $field );

									foreach ( $value as $single_val ) {
										$single_val = trim( $single_val );

										$arr_meta_query = array(
											array(
												'key'     => $field,
												'value'   => $single_val,
												'compare' => '=',
											),
											array(
												'key'     => $field,
												'value'   => serialize( (string) $single_val ),
												'compare' => 'LIKE',
											),
											array(
												'key'     => $field,
												'value'   => '"' . $single_val . '"',
												'compare' => 'LIKE',
											),
										);

										if ( is_numeric( $single_val ) ) {

											$arr_meta_query[] = array(
												'key'     => $field,
												'value'   => serialize( absint( $single_val ) ),
												'compare' => 'LIKE',
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

									if ( ! empty( $value[0] ) ) {
										$min = $value[0];
									} else {
										$range = $this->datepicker_filters_range( $field );
										$min   = strtotime( gmdate( 'Y/m/d', $range[0] ) );
									}
									if ( ! empty( $value[1] ) ) {
										$max = $value[1];
									} else {
										$max = strtotime( gmdate( 'Y/m/d' ) );
									}

									$from_date = (int) $min + ( $offset * HOUR_IN_SECONDS ); // client time zone offset
									$to_date   = (int) $max + ( $offset * HOUR_IN_SECONDS ) + DAY_IN_SECONDS - 1; // time 23:59

									$field_query = array(
										'key'       => $field,
										'value'     => array( $from_date, $to_date ),
										'compare'   => 'BETWEEN',
										'inclusive' => true,
									);

									break;
								case 'timepicker':
									if ( ! empty( $value[0] ) ) {
										$value[0] = $value[0] . ':00';
									} else {
										$range    = $this->timepicker_filters_range( $field );
										$value[0] = $range[0] . ':00';
									}
									if ( ! empty( $value[1] ) ) {
										$value[1] = $value[1] . ':00';
									} else {
										$range    = $this->timepicker_filters_range( $field );
										$value[1] = $range[1] . ':00';
									}

									if ( $value[0] === $value[1] ) {
										$field_query = array(
											'key'   => $field,
											'value' => $value[0],
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
						$value = is_array( $value ) ? $value : explode( '||', $value );
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
						}

						break;
					case 'birth_date':
						$from_date = date( 'Y/m/d', mktime( 0,0,0, date( 'm', time() ), date( 'd', time() ), date( 'Y', time() - min( $value ) * YEAR_IN_SECONDS ) ) );
						$to_date   = date( 'Y/m/d', mktime( 0,0,0, date( 'm', time() ), date( 'd', time() ) + 1, date( 'Y', time() - ( max( $value ) + 1 ) * YEAR_IN_SECONDS ) ) );

						$meta_query = array(
							array(
								'key'       => 'birth_date',
								'value'     => array( $to_date, $from_date ),
								'compare'   => 'BETWEEN',
								'type'      => 'DATE',
								'inclusive' => true,
							),
						);

						$this->query_args['meta_query'] = array_merge( $this->query_args['meta_query'], array( $meta_query ) );

						break;
					case 'user_registered':
						$offset = 0;
						if ( is_numeric( $gmt_offset ) ) {
							$offset = $gmt_offset;
						}

						$from_date = date( 'Y-m-d H:i:s', strtotime( date( 'Y-m-d H:i:s', min( $value ) ) . "+$offset hours" ) );
						$to_date   = date( 'Y-m-d H:i:s', strtotime( date( 'Y-m-d H:i:s', max( $value ) ) . "+$offset hours" ) );

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

						$value = array_map(
							function( $date ) {
								return is_numeric( $date ) ? $date : strtotime( $date );
							},
							$value
						);

						if ( ! empty( $value[0] ) ) {
							$min = $value[0];
						} else {
							$range = $this->datepicker_filters_range( 'last_login' );
							$min   = strtotime( gmdate( 'Y/m/d', $range[0] ) );
						}
						if ( ! empty( $value[1] ) ) {
							$max = $value[1];
						} else {
							$max = strtotime( gmdate( 'Y/m/d' ) );
						}

						$from_date = gmdate( 'Y-m-d H:i:s', (int) $min + ( $offset * HOUR_IN_SECONDS ) ); // client time zone offset
						$to_date   = gmdate( 'Y-m-d H:i:s', (int) $max + ( $offset * HOUR_IN_SECONDS ) + DAY_IN_SECONDS - 1 ); // time 23:59

						$meta_query = array(
							'relation' => 'AND',
							array(
								'key'       => '_um_last_login',
								'value'     => array( $from_date, $to_date ),
								'compare'   => 'BETWEEN',
								'inclusive' => true,
								'type'      => 'DATETIME',
							),
							array(
								'relation' => 'OR',
								array(
									'key'     => 'um_show_last_login',
									'compare' => 'NOT EXISTS',
								),
								array(
									'key'     => 'um_show_last_login',
									'value'   => 'a:1:{i:0;s:2:"no";}',
									'compare' => '!=',
								),
							),
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
		 * @param int $total_users
		 *
		 * @return array
		 */
		function calculate_pagination( $directory_data, $total_users ) {

			$current_page = ! empty( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;

			$total_users = ( ! empty( $directory_data['max_users'] ) && $directory_data['max_users'] <= $total_users ) ? $directory_data['max_users'] : $total_users;

			// number of profiles for mobile
			$profiles_per_page = $directory_data['profiles_per_page'];
			if ( wp_is_mobile() && isset( $directory_data['profiles_per_page_mobile'] ) ) {
				$profiles_per_page = $directory_data['profiles_per_page_mobile'];
			}

			$total_pages = 1;
			if ( ! empty( $profiles_per_page ) ) {
				$total_pages = ceil( $total_users / $profiles_per_page );
			}

			if ( ! empty( $total_pages ) ) {
				$index1 = 0 - ( $current_page - 2 ) + 1;
				$to     = $current_page + 2;
				if ( $index1 > 0 ) {
					$to += $index1;
				}

				$index2 = $total_pages - ( $current_page + 2 );
				$from   = $current_page - 2;
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

			$pagination_data['header']        = $this->convert_tags( $directory_data['header'], $pagination_data );
			$pagination_data['header_single'] = $this->convert_tags( $directory_data['header_single'], $pagination_data );

			return $pagination_data;
		}

		/**
		 * @param int $user_id
		 *
		 * @return array
		 */
		private function build_user_actions_list( $user_id ) {
			$actions = array();
			if ( ! is_user_logged_in() ) {
				return $actions;
			}

			if ( get_current_user_id() !== absint( $user_id ) ) {
				if ( UM()->roles()->um_current_user_can( 'edit', $user_id ) ) {
					$actions['um-editprofile'] = array(
						'title' => esc_html__( 'Edit Profile', 'ultimate-member' ),
						'url'   => um_edit_profile_url( $user_id ),
					);
				}

				$admin_actions = UM()->frontend()->users()->get_actions_list( $user_id );
				if ( ! empty( $admin_actions ) ) {
					foreach ( $admin_actions as $id => $arr ) {
						$url = add_query_arg(
							array(
								'um_action' => $id,
								'uid'       => $user_id,
								'nonce'     => wp_create_nonce( $id . $user_id ),
							),
							um_user_profile_url( $user_id )
						);

						$actions[ $id ] = array(
							'title' => esc_html( $arr['label'] ),
							'url'   => esc_url( $url ),
						);
					}
				}

				$actions = apply_filters( 'um_member_directory_users_card_actions', $actions, $user_id );
			} else {
				if ( um_user( 'can_edit_profile' ) ) {
					$actions['um-editprofile'] = array(
						'title' => esc_html__( 'Edit Profile', 'ultimate-member' ),
						'url'   => um_edit_profile_url( $user_id ),
					);
				}

				$actions['um-myaccount'] = array(
					'title' => esc_html__( 'My Account', 'ultimate-member' ),
					'url'   => um_get_predefined_page_url( 'account' ),
				);

				$actions['um-logout'] = array(
					'title' => esc_html__( 'Logout', 'ultimate-member' ),
					'url'   => um_get_predefined_page_url( 'logout' ),
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
		public function build_user_card_data( $user_id, $directory_data ) {
			um_fetch_user( $user_id );

			$dropdown_actions = $this->build_user_actions_list( $user_id );

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
				'account_status'       => esc_html( UM()->common()->users()->get_status( $user_id ) ),
				'account_status_name'  => esc_html( UM()->common()->users()->get_status( $user_id, 'formatted' ) ),
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
							if ( 'role_select' === $key || 'role_radio' === $key ) {
								$label = strtr(
									$label,
									array(
										' (Dropdown)' => '',
										' (Radio)'    => '',
									)
								);
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
		 * Update limit query
		 *
		 * @param $user_query
		 */
		public function pagination_changes( $user_query ) {
			global $wpdb;

			$directory_id   = $this->get_directory_by_hash( sanitize_key( $_POST['directory_id'] ) );
			$directory_data = UM()->query()->post_data( $directory_id );

			$qv = $user_query->query_vars;

			$number = $qv['number'];
			if ( ! empty( $directory_data['max_users'] ) && $qv['paged']*$qv['number'] > $directory_data['max_users'] ) {
				$number = ( $qv['paged']*$qv['number'] - ( $qv['paged']*$qv['number'] - $directory_data['max_users'] ) ) % $qv['number'];
			}

			// limit
			if ( isset( $qv['number'] ) && $qv['number'] > 0 ) {
				if ( $qv['offset'] ) {
					$user_query->query_limit = $wpdb->prepare( 'LIMIT %d, %d', $qv['offset'], $number );
				} else {
					$user_query->query_limit = $wpdb->prepare( 'LIMIT %d, %d', $qv['number'] * ( $qv['paged'] - 1 ), $number );
				}
			}
		}

		/**
		 * Update search query
		 *
		 * @param WP_User_Query $user_query
		 *
		 * @throws Exception
		 */
		public function search_changes( $user_query ) {
			global $wpdb;

			if ( ! empty( $_POST['search'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- already verified here
				$search = $this->prepare_search( $_POST['search'] ); // phpcs:ignore WordPress.Security.NonceVerification -- already verified here
				if ( empty( $search ) ) {
					return;
				}

				$directory_id = null;
				if ( ! empty( $_POST['directory_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- already verified here
					$directory_id = $this->get_directory_by_hash( sanitize_key( $_POST['directory_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification -- already verified here
				}

				$qv = $user_query->query_vars;

				$exclude_fields = array();
				$include_fields = array_keys( UM()->builtin()->all_user_fields );
				if ( ! empty( $directory_id ) ) {
					$exclude_fields = get_post_meta( $directory_id, '_um_search_exclude_fields', true );
					$include_fields = get_post_meta( $directory_id, '_um_search_include_fields', true );

					$exclude_fields = ! empty( $exclude_fields ) && is_array( $exclude_fields ) ? $exclude_fields : array();
					$include_fields = ! empty( $include_fields ) && is_array( $include_fields ) ? $include_fields : array_keys( UM()->builtin()->all_user_fields );
				}

				$custom_fields = array();
				foreach ( $include_fields as $field_key ) {
					if ( empty( $field_key ) ) {
						continue;
					}

					$data = UM()->fields()->get_field( $field_key );
					if ( isset( $data['type'] ) && 'password' === $data['type'] ) {
						continue;
					}
					if ( isset( $data['metakey'] ) && in_array( $data['metakey'], array( 'role_select', 'role_radio' ), true ) ) {
						continue;
					}
					if ( ! empty( $data['account_only'] ) ) {
						continue;
					}

					if ( ! um_can_view_field( $data ) ) {
						continue;
					}

					$custom_fields[] = $field_key;
				}

				if ( ! empty( $custom_fields ) && ! empty( $exclude_fields ) ) {
					$custom_fields = array_diff( $custom_fields, $exclude_fields );
				}

				$custom_fields = apply_filters( 'um_general_search_custom_fields', $custom_fields );
				$custom_fields = array_values( array_unique( $custom_fields ) );

				$search_columns = $this->get_core_search_fields( $qv, $search );

				if ( ! empty( $directory_id ) ) {
					$include_fields = get_post_meta( $directory_id, '_um_search_include_fields', true );
					if ( ! empty( $include_fields ) ) {
						$search_columns = array_intersect( $search_columns, $include_fields );
					}
				}
				if ( ! empty( $exclude_fields ) ) {
					$search_columns = array_diff( $search_columns, $exclude_fields );
				}

				if ( ! empty( $custom_fields ) ) {
					$search_columns[] = 'um_search.meta_value';

					$user_query->query_from .= " INNER JOIN {$wpdb->usermeta} AS um_search ON ( {$wpdb->users}.ID = um_search.user_id AND um_search.meta_key IN('" . implode( "','", $custom_fields ) . "'))";
				}

				if ( ! empty( $search_columns ) ) {
					$search_where = $user_query->get_search_sql( $search, $search_columns, 'both' );
					$search_where = apply_filters( 'um_general_search_custom_search_where', $search_where, $user_query, $search );

					$user_query->query_where .= $search_where;
				}

				// Required `DISTINCT` if not exists, because we add `OR` condition manually.
				if ( ( ! empty( $custom_fields ) || ! empty( $search_columns ) ) && false === strpos( $user_query->query_fields, 'DISTINCT' ) ) {
					$user_query->query_fields = 'DISTINCT ' . $user_query->query_fields;
				}
			}
		}


		function predefined_no_caps( $directory_data ) {
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
		}

		/**
		 * Main Query function for getting members via AJAX
		 */
		public function ajax_get_members() {
			UM()->check_ajax_nonce();

			global $wpdb;

			if ( empty( $_POST['directory_id'] ) ) {
				wp_send_json_error( __( 'Wrong member directory data', 'ultimate-member' ) );
			}

			$directory_id = $this->get_directory_by_hash( sanitize_key( $_POST['directory_id'] ) );

			if ( empty( $directory_id ) ) {
				wp_send_json_error( __( 'Wrong member directory data', 'ultimate-member' ) );
			}

			$directory_data = UM()->query()->post_data( $directory_id );

			//predefined result for user without capabilities to see other members
			$this->predefined_no_caps( $directory_data );

			do_action( 'um_member_directory_before_query' );

			// Prepare for BIG SELECT query
			$wpdb->query( 'SET SQL_BIG_SELECTS=1' );

			// Prepare default user query values
			$this->query_args = array(
				'fields'     => 'ids',
				'number'     => 0,
				'meta_query' => array(
					'relation' => 'AND',
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

			if ( isset( $this->query_args['role__in'] ) && empty( $this->query_args['role__in'] ) ) {
				$member_directory_response = apply_filters( 'um_ajax_get_members_response', array(
					'pagination'    => $this->calculate_pagination( $directory_data, 0 ),
					'users'         => array(),
					'is_search'     => $this->is_search,
				), $directory_data );

				wp_send_json_success( $member_directory_response );
			}
			/**
			 * Fires just before the users query for getting users in member directory.
			 *
			 * @since 1.3.x
			 * @since 2.1.0 Added `$member_directory_class` variable.
			 * @hook um_user_before_query
			 *
			 * @param {array}  $args                   Query arguments.
			 * @param {object} $member_directory_class Member Directory class. Since 2.1.0 version.
			 *
			 * @example <caption>Add custom arguments for query.</caption>
			 * function my_user_before_query( $query_args, $md_class ) {
			 *     $query_args['{custom_key}'] = 'custom_value';
			 * }
			 * add_action( 'um_user_before_query', 'my_user_before_query', 10, 2 );
			 */
			do_action( 'um_user_before_query', $this->query_args, $this );

			add_action( 'pre_user_query', array( &$this, 'search_changes' ) );
			add_action( 'pre_user_query', array( &$this, 'pagination_changes' ) );

			$user_query = new WP_User_Query( $this->query_args );

			remove_action( 'pre_user_query', array( &$this, 'search_changes' ) );
			remove_action( 'pre_user_query', array( &$this, 'pagination_changes' ) );

			/**
			 * Fires just after the users query for getting users in member directory.
			 *
			 * @since 1.3.x
			 * @hook um_user_after_query
			 *
			 * @param {array}  $query_args Query arguments.
			 * @param {object} $user_query Query results.
			 *
			 * @example <caption>Make some custom action after getting the users in member directory.</caption>
			 * function my_user_after_query( $query_args, $user_query ) {
			 *     // your code here
			 * }
			 * add_action( 'um_user_after_query', 'my_user_after_query', 10, 2 );
			 */
			do_action( 'um_user_after_query', $this->query_args, $user_query );

			$pagination_data = $this->calculate_pagination( $directory_data, $user_query->total_users );

			$user_ids = ! empty( $user_query->results ) ? array_unique( $user_query->results ) : array();

			/**
			 * Filters the member directory query result.
			 *
			 * @since 2.0
			 * @hook um_prepare_user_results_array
			 *
			 * @param {array} $user_ids   Members Query Result.
			 * @param {array} $query_args Query arguments.
			 *
			 * @return {array} Query result.
			 *
			 * @example <caption>Remove some users where ID equals 10 and 12 from query.</caption>
			 * function my_custom_um_prepare_user_results_array( $user_ids, $query_args ) {
			 *     $user_ids = array_diff( $user_ids, array( 10, 12 ) );
			 *     return $user_ids;
			 * }
			 * add_filter( 'um_prepare_user_results_array', 'my_custom_um_prepare_user_results_array', 10, 2 );
			 */
			$user_ids = apply_filters( 'um_prepare_user_results_array', $user_ids, $this->query_args );

			$this->init_image_sizing( $directory_data );

			$users = array();
			foreach ( $user_ids as $user_id ) {
				$users[] = $this->build_user_card_data( $user_id, $directory_data );
			}

			um_reset_user();
			// end of user card

			$member_directory_response = apply_filters( 'um_ajax_get_members_response', array(
				'pagination'    => $pagination_data,
				'users'         => $users,
				'is_search'     => $this->is_search,
			), $directory_data );

			wp_send_json_success( $member_directory_response );
		}


		/**
		 * New menu
		 *
		 * @param string $element
		 * @param string $trigger
		 * @param array $items
		 * @param string $parent
		 */
		function dropdown_menu( $element, $trigger, $items = array(), $parent = '' ) {
			// !!!!Important: all links in the dropdown items must have "class" attribute
			?>

			<div class="um-new-dropdown" data-element="<?php echo $element; ?>" data-trigger="<?php echo $trigger; ?>" data-parent="<?php echo $parent; ?>">
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
		 * @param string $additional_attributes
		 * @param string $parent
		 */
		function dropdown_menu_js( $element, $trigger, $item, $additional_attributes = '', $parent = '' ) {
			?>

			<div class="um-new-dropdown" data-element="<?php echo $element; ?>" data-trigger="<?php echo $trigger; ?>" data-parent="<?php echo $parent; ?>">
				<ul>
					<# _.each( <?php echo $item; ?>.dropdown_actions, function( action, key, list ) { #>
					<li><a href="<# if ( typeof action.url != 'undefined' ) { #>{{{action.url}}}<# } else { #>javascript:void(0);<# }#>" class="{{{key}}}"<?php echo $additional_attributes ? " $additional_attributes" : '' ?>>{{{action.title}}}</a></li>
					<# }); #>
				</ul>
			</div>

			<?php
		}

		/**
		 * AJAX handler - Get options for the member directory "Admin filtering"
		 * @version 2.1.12
		 *
		 * @todo deprecate since new UI is live
		 */
		public function default_filter_settings() {
			UM()->admin()->check_ajax_nonce();

			// we can't use function "sanitize_key" because it changes uppercase to lowercase
			$filter_key   = sanitize_text_field( $_REQUEST['key'] );
			$directory_id = absint( $_REQUEST['directory_id'] );

			$html = $this->show_filter( $filter_key, array( 'form_id' => $directory_id ), false, true );

			wp_send_json_success( array( 'field_html' => UM()->ajax()->esc_html_spaces( $html ) ) );
		}

		/**
		 * Get member directory id by page id.
		 *
		 * @param int $page_id Page ID.
		 *
		 * @return array Member directories ID.
		 */
		public function get_member_directory_id( $page_id ) {
			$members_page = get_post( $page_id );
			if ( ! empty( $members_page ) && ! is_wp_error( $members_page ) ) {
				if ( ! empty( $members_page->post_content ) ) {
					preg_match_all( '/\[ultimatemember[^\]]*?form_id\=[\'"]*?(\d+)[\'"]*?/i', $members_page->post_content, $matches );
					if ( ! empty( $matches[1] ) && is_array( $matches[1] ) ) {
						$member_directory_ids = array_map( 'absint', $matches[1] );
						return $member_directory_ids;
					}
				}
			}

			return array();
		}
	}
}
