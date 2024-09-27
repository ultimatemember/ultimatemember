<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.


//Make public functions without class creation


/**
 * Trim string by char length
 *
 *
 * @param $s
 * @param int $length
 *
 * @return string
 */
function um_trim_string( $s, $length = 20 ) {
	$s = mb_strlen( $s ) > $length ? substr( $s, 0, $length ) . "..." : $s;

	return $s;
}


/**
 * Get where user should be headed after logging
 *
 * @param string $redirect_to
 *
 * @return bool|false|mixed|string|void
 */
function um_dynamic_login_page_redirect( $redirect_to = '' ) {

	$uri = um_get_core_page( 'login' );

	if ( ! $redirect_to ) {
		$redirect_to = UM()->permalinks()->get_current_url();
	}

	$redirect_key = urlencode_deep( $redirect_to );

	$uri = add_query_arg( 'redirect_to', $redirect_key, $uri );

	return $uri;
}


/**
 * Checks if session has been started
 *
 * @return bool
 */
function um_is_session_started() {

	if ( php_sapi_name() !== 'cli' ) {
		if ( version_compare( phpversion(), '5.4.0', '>=' ) ) {
			return session_status() === PHP_SESSION_ACTIVE ? true : false;
		} else {
			return session_id() === '' ? false : true;
		}
	}

	return false;
}

/**
 * User clean basename
 *
 * @param $value
 *
 * @return mixed|void
 */
function um_clean_user_basename( $value ) {

	$raw_value = $value;
	$value = str_replace( '.', ' ', $value );
	$value = str_replace( '-', ' ', $value );
	$value = str_replace( '+', ' ', $value );

	/**
	 * UM hook
	 *
	 * @type filter
	 * @title um_clean_user_basename_filter
	 * @description Change clean user basename
	 * @input_vars
	 * [{"var":"$basename","type":"string","desc":"User basename"},
	 * {"var":"$raw_basename","type":"string","desc":"RAW user basename"}]
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage add_filter( 'um_clean_user_basename_filter', 'function_name', 10, 2 );
	 * @example
	 * <?php
	 * add_filter( 'um_clean_user_basename_filter', 'my_clean_user_basename', 10, 2 );
	 * function my_clean_user_basename( $basename, $raw_basename ) {
	 *     // your code here
	 *     return $basename;
	 * }
	 * ?>
	 */
	$value = apply_filters( 'um_clean_user_basename_filter', $value, $raw_value );

	return $value;
}


/**
 * Getting replace placeholders array
 *
 * @return array
 */
function um_replace_placeholders() {

	$search = array(
		'{display_name}',
		'{first_name}',
		'{last_name}',
		'{gender}',
		'{username}',
		'{email}',
		'{site_name}',
		'{user_account_link}',
	);

	/**
	 * UM hook
	 *
	 * @type filter
	 * @title um_template_tags_patterns_hook
	 * @description Extend UM placeholders
	 * @input_vars
	 * [{"var":"$placeholders","type":"array","desc":"UM Placeholders"}]
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage add_filter( 'um_template_tags_patterns_hook', 'function_name', 10, 1 );
	 * @example
	 * <?php
	 * add_filter( 'um_template_tags_patterns_hook', 'my_template_tags_patterns', 10, 1 );
	 * function my_template_tags_patterns( $placeholders ) {
	 *     // your code here
	 *     $placeholders[] = '{my_custom_placeholder}';
	 *     return $placeholders;
	 * }
	 * ?>
	 */
	$search = apply_filters( 'um_template_tags_patterns_hook', $search );

	$replace = array(
		um_user( 'display_name' ),
		um_user( 'first_name' ),
		um_user( 'last_name' ),
		um_user( 'gender' ),
		um_user( 'user_login' ),
		um_user( 'user_email' ),
		UM()->options()->get( 'site_name' ),
		um_get_core_page( 'account' ),
	);

	/**
	 * UM hook
	 *
	 * @type filter
	 * @title um_template_tags_replaces_hook
	 * @description Extend UM replace placeholders
	 * @input_vars
	 * [{"var":"$replace_placeholders","type":"array","desc":"UM Replace Placeholders"}]
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage add_filter( 'um_template_tags_replaces_hook', 'function_name', 10, 1 );
	 * @example
	 * <?php
	 * add_filter( 'um_template_tags_replaces_hook', 'my_template_tags_replaces', 10, 1 );
	 * function my_template_tags_replaces( $replace_placeholders ) {
	 *     // your code here
	 *     $replace_placeholders[] = 'my_replace_value';
	 *     return $replace_placeholders;
	 * }
	 * ?>
	 */
	$replace = apply_filters( 'um_template_tags_replaces_hook', $replace );

	return array_combine( $search, $replace );
}


/**
 * Convert template tags
 *
 * @param $content
 * @param array $args
 * @param bool $with_kses
 *
 * @return mixed|string
 */
function um_convert_tags( $content, $args = array(), $with_kses = true ) {
	$placeholders = um_replace_placeholders();

	$content = str_replace( array_keys( $placeholders ), array_values( $placeholders ), $content );
	if ( $with_kses ) {
		$content = wp_kses_decode_entities( $content );
	}

	if ( isset( $args['tags'] ) && isset( $args['tags_replace'] ) ) {
		$content = str_replace( $args['tags'], $args['tags_replace'], $content );
	}

	$regex = '~\{(usermeta:[^}]*)\}~';
	preg_match_all( $regex, $content, $matches );

	// Support for all usermeta keys
	if ( ! empty( $matches[1] ) && is_array( $matches[1] ) ) {
		foreach ( $matches[1] as $match ) {
			$key = str_replace( 'usermeta:', '', $match );
			$value = um_user( $key );
			if ( is_array( $value ) ) {
				$value = implode( ', ', $value );
			}
			$content = str_replace( '{' . $match . '}', apply_filters( 'um_convert_tags', $value, $key ), $content );
		}
	}
	return $content;
}


/**
 * UM Placeholders for activation link in email
 *
 * @param $placeholders
 *
 * @return array
 */
function account_activation_link_tags_patterns( $placeholders ) {
	$placeholders[] = '{account_activation_link}';
	return $placeholders;
}

/**
 * UM Replace Placeholders for activation link in email
 *
 * @param $replace_placeholders
 *
 * @return array
 */
function account_activation_link_tags_replaces( $replace_placeholders ) {
	$replace_placeholders[] = um_user( 'account_activation_link' );
	return $replace_placeholders;
}


/**
 * @function um_user_ip()
 *
 * @description This function returns the IP address of user.
 *
 * @usage <?php $user_ip = um_user_ip(); ?>
 *
 * @return string The user's IP address.
 *
 * @example The example below can retrieve the user's IP address
 *
 * <?php
 *
 * $user_ip = um_user_ip();
 * echo 'User IP address is: ' . $user_ip; // prints the user IP address e.g. 127.0.0.1
 *
 * ?>
 */
function um_user_ip() {
	$ip = '127.0.0.1';

	if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
		//check ip from share internet
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	} else if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		//to check ip is pass from proxy
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} else if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
		$ip = $_SERVER['REMOTE_ADDR'];
	}

	/**
	 * UM hook
	 *
	 * @type filter
	 * @title um_user_ip
	 * @description Change User IP
	 * @input_vars
	 * [{"var":"$ip","type":"string","desc":"User IP"}]
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage add_filter( 'um_user_ip', 'function_name', 10, 1 );
	 * @example
	 * <?php
	 * add_filter( 'um_user_ip', 'my_user_ip', 10, 1 );
	 * function my_user_ip( $ip ) {
	 *     // your code here
	 *     return $ip;
	 * }
	 * ?>
	 */
	return apply_filters( 'um_user_ip', $ip );
}


/**
 * If conditions are met return true;
 *
 * @param $data
 *
 * @return bool
 */
function um_field_conditions_are_met( $data ) {

	if ( ! isset( $data['conditions'] ) ) {
		return true;
	}

	$state = ( isset( $data['conditional_action'] ) && $data['conditional_action'] == 'show' ) ? 1 : 0;

	$first_group = 0;
	$state_array = array();
	$count = count( $state_array );
	foreach ( $data['conditions'] as $k => $arr ) {

		$val = $arr[3];
		$op = $arr[2];

		if ( strstr( $arr[1], 'role_' ) ) {
			$arr[1] = 'role';
		}

		$field = um_profile( $arr[1] );


		if ( ! isset( $arr[5] ) || $arr[5] != $first_group ) {


			if ( $arr[0] == 'show' ) {

				switch ($op) {
					case 'equals to':

						$field = maybe_unserialize( $field );

						if (is_array( $field ))
							$state = in_array( $val, $field ) ? 'show' : 'hide';
						else
							$state = ( $field == $val ) ? 'show' : 'hide';

						break;
					case 'not equals':

						$field = maybe_unserialize( $field );

						if (is_array( $field ))
							$state = !in_array( $val, $field ) ? 'show' : 'hide';
						else
							$state = ( $field != $val ) ? 'show' : 'hide';

						break;
					case 'empty':

						$state = ( !$field ) ? 'show' : 'hide';

						break;
					case 'not empty':

						$state = ( $field ) ? 'show' : 'hide';

						break;
					case 'greater than':
						if ($field > $val) {
							$state = 'show';
						} else {
							$state = 'hide';
						}
						break;
					case 'less than':
						if ($field < $val) {
							$state = 'show';
						} else {
							$state = 'hide';
						}
						break;
					case 'contains':
						if (strstr( $field, $val )) {
							$state = 'show';
						} else {
							$state = 'hide';
						}
						break;
				}
			} elseif ( $arr[0] == 'hide' ) {

				switch ( $op ) {
					case 'equals to':

						$field = maybe_unserialize( $field );

						if ( is_array( $field ) ) {
							$state = in_array( $val, $field ) ? 'hide' : 'show';
						} else {
							$state = ( $field == $val ) ? 'hide' : 'show';
						}

						break;
					case 'not equals':

						$field = maybe_unserialize( $field );

						if ( is_array( $field ) ) {
							$state = ! in_array( $val, $field ) ? 'hide' : 'show';
						} else {
							$state = ( $field != $val ) ? 'hide' : 'show';
						}

						break;
					case 'empty':

						$state = ( ! $field ) ? 'hide' : 'show';

						break;
					case 'not empty':

						$state = ( $field ) ? 'hide' : 'show';

						break;
					case 'greater than':
						if ( $field <= $val ) {
							$state = 'hide';
						} else {
							$state = 'show';
						}
						break;
					case 'less than':
						if ( $field >= $val ) {
							$state = 'hide';
						} else {
							$state = 'show';
						}
						break;
					case 'contains':
						if ( strstr( $field, $val ) ) {
							$state = 'hide';
						} else {
							$state = 'show';
						}
						break;
				}
			}
			$first_group++;
			array_push( $state_array, $state );
		} else {

			if ( $arr[0] == 'show' ) {

				switch ( $op ) {
					case 'equals to':
						$field = maybe_unserialize( $field );

						if ( is_array( $field ) ) {
							$state = in_array( $val, $field ) ? 'show' : 'not_show';
						} else {
							$state = ( $field == $val ) ? 'show' : 'not_show';
						}

						break;
					case 'not equals':
						$field = maybe_unserialize( $field );

						if ( is_array( $field ) ) {
							$state = ! in_array( $val, $field ) ? 'show' : 'not_show';
						} else {
							$state = ( $field != $val ) ? 'show' : 'not_show';
						}

						break;
					case 'empty':

						$state = ( ! $field ) ? 'show' : 'not_show';

						break;
					case 'not empty':

						$state = ( $field ) ? 'show': 'not_show';

						break;
					case 'greater than':
						if ( $field > $val ) {
							$state = 'show';
						} else {
							$state = 'not_show';
						}
						break;
					case 'less than':
						if ( $field < $val ) {
							$state = 'show';
						} else {
							$state = 'not_show';
						}
						break;
					case 'contains':
						if ( strstr( $field, $val ) ) {
							$state = 'show';
						} else {
							$state = 'not_show';
						}
						break;
				}
			} elseif ( $arr[0] == 'hide' ) {

				switch ( $op ) {
					case 'equals to':
						$field = maybe_unserialize( $field );

						if ( is_array( $field ) ) {
							$state = in_array( $val, $field ) ? 'hide' : 'not_hide';
						} else {
							$state = ( $field == $val ) ? 'hide' : 'not_hide';
						}

						break;
					case 'not equals':

						$field = maybe_unserialize( $field );

						if ( is_array( $field ) ) {
							$state = ! in_array( $val, $field ) ? 'hide' : 'not_hide';
						} else {
							$state = ( $field != $val ) ? 'hide' : 'not_hide';
						}

						break;
					case 'empty':

						$state = ( ! $field ) ? 'hide' : 'not_hide';

						break;
					case 'not empty':

						$state = ( $field ) ? 'hide' : 'not_hide';

						break;
					case 'greater than':
						if ( $field <= $val ) {
							$state = 'hide';
						} else {
							$state = 'not_hide';
						}
						break;
					case 'less than':
						if ( $field >= $val ) {
							$state = 'hide';
						} else {
							$state = 'not_hide';
						}
						break;
					case 'contains':
						if ( strstr( $field, $val ) ) {
							$state = 'hide';
						} else {
							$state = 'not_hide';
						}
						break;
				}
			}
			if ( isset( $state_array[ $count ] ) ) {
				if ( $state_array[ $count ] == 'show' || $state_array[ $count ] == 'not_hide' ) {
					if ( $state == 'show' || $state == 'not_hide' ) {
						$state_array[ $count ] = 'show';
					} else {
						$state_array[ $count ] = 'hide';
					}
				} else {
					if ( $state == 'hide' || $state == 'not_show' ) {
						$state_array[ $count ] = 'hide';
					} else {
						$state_array[ $count ] = 'hide';
					}
				}
			} else {
				if ( $state == 'show' || $state == 'not_hide' ) {
					$state_array[ $count ] = 'show';
				} else {
					$state_array[ $count ] = 'hide';
				}
			}
		}


	}
	$result = array_unique( $state_array );
	if ( ! in_array( 'show', $result ) ) {
		return $state = false;
	} else {
		return $state = true;
	}
}


/**
 * Exit and redirect to home
 *
 * @param string $requested_user_id
 * @param string $is_my_profile
 */
function um_redirect_home( $requested_user_id = '', $is_my_profile = '' ) {
	$url = apply_filters( 'um_redirect_home_custom_url', home_url(), $requested_user_id, $is_my_profile );
	exit( wp_redirect( $url ) );
}



/**
 * @param $url
 */
function um_js_redirect( $url ) {
	if ( headers_sent() || empty( $url ) ) {
		//for blank redirects
		if ( '' == $url ) {
			$url = set_url_scheme( '//' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] );
		}

		register_shutdown_function( function( $url ) {
			echo '<script data-cfasync="false" type="text/javascript">window.location = "' . esc_js( $url ) . '"</script>';
		}, $url );

		if ( 1 < ob_get_level() ) {
			while ( ob_get_level() > 1 ) {
				ob_end_clean();
			}
		} ?>
		<script data-cfasync='false' type="text/javascript">
			window.location = '<?php echo esc_js( $url ); ?>';
		</script>
		<?php exit;
	} else {
		wp_redirect( $url );
	}
	exit;
}


/**
 * Get limit of words from sentence
 *
 * @param $str
 * @param int $wordCount
 *
 * @return string
 */
function um_get_snippet( $str, $wordCount = 10 ) {
	if (str_word_count( $str, 0, "éèàôù" ) > $wordCount) {
		$str = implode(
			'',
			array_slice(
				preg_split(
					'/([\s,\.;\?\!]+)/',
					$str,
					$wordCount * 2 + 1,
					PREG_SPLIT_DELIM_CAPTURE
				),
				0,
				$wordCount * 2 - 1
			)
		);
	}

	return $str;
}


/**
 * Format submitted data for Info preview & Email template
 * @param  boolean $style
 * @return string
 *
 * @since  2.1.4
 */
function um_user_submitted_registration_formatted( $style = false ) {
	$output = null;

	$submitted_data = um_user( 'submitted' );

	if ( $style ) {
		$output .= '<div class="um-admin-infobox">';
	}

	// User registered date.
	$output .= um_user_submited_display( 'user_registered', __( 'User registered date', 'ultimate-member' ) );
	// Registration form.
	$output .= um_user_submited_display( 'form_id', __( 'Form', 'ultimate-member' ), $submitted_data );

	if ( isset( $submitted_data['use_gdpr_agreement'] ) ) {
		$output .= um_user_submited_display( 'use_gdpr_agreement', __( 'GDPR Applied', 'ultimate-member' ), $submitted_data );
	}

	if ( isset( $submitted_data ) && is_array( $submitted_data ) ) {

		if ( isset( $submitted_data['form_id'] ) ) {
			$fields = UM()->query()->get_attr( 'custom_fields', $submitted_data['form_id'] );
			$fields = maybe_unserialize( $fields );
		}

		if ( ! empty( $fields ) ) {

			$fields['form_id'] = array( 'title' => __( 'Form', 'ultimate-member' ) );

			$rows = array();

			UM()->fields()->get_fields = $fields;

			foreach ( $fields as $key => $array ) {
				if ( isset( $array['type'] ) && 'row' === $array['type'] ) {
					$rows[ $key ] = $array;
					unset( UM()->fields()->get_fields[ $key ] ); // not needed now
				}
			}

			if ( empty( $rows ) ) {
				$rows = array(
					'_um_row_1' => array(
						'type'     => 'row',
						'id'       => '_um_row_1',
						'sub_rows' => 1,
						'cols'     => 1,
					),
				);
			}

			foreach ( $rows as $row_id => $row_array ) {

				$row_fields = UM()->fields()->get_fields_by_row( $row_id );

				if ( $row_fields ) {

					$output .= UM()->fields()->new_row_output( $row_id, $row_array );

					$sub_rows = ( isset( $row_array['sub_rows'] ) ) ? $row_array['sub_rows'] : 1;
					for ( $c = 0; $c < $sub_rows; $c++ ) {

						// cols
						$cols = ( isset( $row_array['cols'] ) ) ? $row_array['cols'] : 1;
						if ( strstr( $cols, ':' ) ) {
							$col_split = explode( ':', $cols );
						} else {
							$col_split = array( $cols );
						}
						$cols_num = $col_split[ $c ];

						// sub row fields
						$subrow_fields = UM()->fields()->get_fields_in_subrow( $row_fields, $c );

						if ( is_array( $subrow_fields ) ) {

							if ( isset( $subrow_fields['form_id'] ) ) {
								unset( $subrow_fields['form_id'] );
							}

							$subrow_fields = UM()->fields()->array_sort_by_column( $subrow_fields, 'position' );

							if ( $cols_num == 1 ) {

								$col1_fields = UM()->fields()->get_fields_in_column( $subrow_fields, 1 );
								if ( $col1_fields ) {
									foreach ( $col1_fields as $key => $data ) {
										$output .= um_user_submited_display( $key, $data['title'] );
									}
								}
							} elseif ( $cols_num == 2 ) {

								$col1_fields = UM()->fields()->get_fields_in_column( $subrow_fields, 1 );
								if ( $col1_fields ) {
									foreach ( $col1_fields as $key => $data ) {
										$output .= um_user_submited_display( $key, $data['title'] );
									}
								}

								$col2_fields = UM()->fields()->get_fields_in_column( $subrow_fields, 2 );
								if ( $col2_fields ) {
									foreach ( $col2_fields as $key => $data ) {
										$output .= um_user_submited_display( $key, $data['title'] );
									}
								}
							} else {

								$col1_fields = UM()->fields()->get_fields_in_column( $subrow_fields, 1 );
								if ( $col1_fields ) {
									foreach ( $col1_fields as $key => $data ) {
										$output .= um_user_submited_display( $key, $data['title'] );
									}
								}

								$col2_fields = UM()->fields()->get_fields_in_column( $subrow_fields, 2 );
								if ( $col2_fields ) {
									foreach ( $col2_fields as $key => $data ) {
										$output .= um_user_submited_display( $key, $data['title'] );
									}
								}

								$col3_fields = UM()->fields()->get_fields_in_column( $subrow_fields, 3 );
								if ( $col3_fields ) {
									foreach ( $col3_fields as $key => $data ) {
										$output .= um_user_submited_display( $key, $data['title'] );
									}
								}
							}
						}
					}
				}
			} // endfor
		}
	}

	if ( $style ) {
		$output .= '</div>';
	}

	return $output;
}

/**
 * Prepare template
 *
 * @param  string  $k
 * @param  string  $title
 * @param  array   $data
 * @param  boolean $style
 * @return string
 *
 * @since  2.1.4
 */
function um_user_submited_display( $k, $title, $data = array(), $style = true ) {
	$output = '';

	if ( 'form_id' === $k && ! empty( $data['form_id'] ) ) {
		// translators: %1$s is a form title; %2$s is a form ID.
		$v = sprintf( __( '%1$s - Form ID#: %2$s', 'ultimate-member' ), get_the_title( $data['form_id'] ), $data['form_id'] );
	} else {
		$v = um_user( $k );
	}

	if ( strstr( $k, 'user_pass' ) || in_array( $k, array( 'g-recaptcha-response', 'request', '_wpnonce', '_wp_http_referer' ), true ) ) {
		return '';
	}

	$fields_without_metakey = UM()->builtin()->get_fields_without_metakey();
	$type                   = UM()->fields()->get_field_type( $k );
	if ( in_array( $type, $fields_without_metakey, true ) ) {
		return '';
	}

	if ( ! $v ) {
		if ( $style ) {
			return "<p><label>$title: </label><span>" . esc_html__( '(empty)', 'ultimate-member' ) . '</span></p>';
		}
		return '';
	}

	if ( in_array( $type, array( 'image', 'file' ), true ) ) {
		$file = basename( $v );

		$filedata = get_user_meta( um_user( 'ID' ), $k . '_metadata', true );

		$baseurl = UM()->uploader()->get_upload_base_url();
		if ( ! file_exists( UM()->uploader()->get_upload_base_dir() . um_user( 'ID' ) . DIRECTORY_SEPARATOR . $file ) ) {
			if ( is_multisite() ) {
				//multisite fix for old customers
				$baseurl = str_replace( '/sites/' . get_current_blog_id() . '/', '/', $baseurl );
			}
		}

		if ( ! empty( $filedata['original_name'] ) ) {
			$v = '<a class="um-preview-upload" target="_blank" href="' . esc_url( $baseurl . um_user( 'ID' ) . '/' . $file ) . '">' . esc_html( $filedata['original_name'] ) . '</a>';
		} else {
			$v = $baseurl . um_user( 'ID' ) . '/' . $file;
		}
	}

	/**
	 * Filters submitted data info before displaying in modal window or submit to admin email.
	 *
	 * @param {string|array} $value Submitted value.
	 * @param {string}       $k     Submitted data metakey.
	 * @param {array}        $data  Submitted data
	 * @param {bool}         $style If styled echo
	 *
	 * @return {string|array} Is allowed verify.
	 *
	 * @since 2.6.8
	 * @hook um_submitted_data_value
	 *
	 * @example <caption>Change submitted data info before echo.</caption>
	 * function my_um_submitted_data_value ( $value, $metakey, $data, $style ) {
	 *     if ( 'some_metakey' === $metakey ) {
	 *         $value = 'new_value';
	 *     }
	 *     return $value;
	 * }
	 * add_filter( 'um_submitted_data_value', 'my_um_submitted_data_value', 10, 4 );
	 */
	$v = apply_filters( 'um_submitted_data_value', $v, $k, $data, $style );

	if ( is_array( $v ) ) {
		$v = implode( ',', $v );
	}

	if ( 'user_registered' === $k ) {
		$v = wp_date( get_option( 'date_format', 'F j, Y' ) . ' ' . get_option( 'time_format', 'g:i a' ), strtotime( $v ) );
	} elseif ( 'use_gdpr_agreement' === $k ) {
		$v = wp_date( get_option( 'date_format', 'F j, Y' ) . ' ' . get_option( 'time_format', 'g:i a' ), strtotime( $v ) );
	}

	if ( $style ) {
		if ( ! $v ) {
			$v = __( '(empty)', 'ultimate-member' );
		}
		$output .= "<p><label>$title: </label><span>$v</span></p>";
	} else {
		$output .= "$title: $v" . "<br />";
	}

	return $output;
}


/**
 * Show filtered social link
 *
 * @param string $key
 * @param null|string $match
 *
 * @return string
 */
function um_filtered_social_link( $key, $match = null ) {
	$value = um_profile( $key );
	if ( ! empty( $match ) ) {
		$submatch = str_replace( 'https://', '', $match );
		$submatch = str_replace( 'http://', '', $submatch );
		if ( strstr( $value, $submatch ) ) {
			$value = 'https://' . $value;
		} elseif ( strpos( $value, 'http' ) !== 0 ) {
			$value = $match . $value;
		}
	}
	$value = str_replace( 'https://https://', 'https://', $value );
	$value = str_replace( 'http://https://', 'https://', $value );
	$value = str_replace( 'https://http://', 'https://', $value );

	return $value;
}


/**
 * Get filtered meta value after applying hooks
 *
 * @param $key
 * @param bool $data
 * @return mixed|string|void
 */
function um_filtered_value( $key, $data = false ) {
	$value = um_user( $key );
	if ( is_array( $value ) ) {
		$value = add_magic_quotes( $value );
	}

	if ( ! $data ) {
		$data = UM()->builtin()->get_specific_field( $key );
	}

	$type = ( isset( $data['type'] ) ) ? $data['type'] : '';

	/**
	 * UM hook
	 *
	 * @type filter
	 * @title um_profile_field_filter_hook__
	 * @description Change or filter field value
	 * @input_vars
	 * [{"var":"$value","type":"string","desc":"Field Value"},
	 * {"var":"$data","type":"array","desc":"Field Data"},
	 * {"var":"$type","type":"string","desc":"Field Type"}]
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage add_filter( 'um_profile_field_filter_hook__', 'function_name', 10, 3 );
	 * @example
	 * <?php
	 * add_filter( 'um_profile_field_filter_hook__', 'my_profile_field', 10, 3 );
	 * function my_profile_field( $value, $data, $type ) {
	 *     // your code here
	 *     return $value;
	 * }
	 * ?>
	 */
	$value = apply_filters( 'um_profile_field_filter_hook__', $value, $data, $type );

	/**
	 * UM hook
	 *
	 * @type filter
	 * @title um_profile_field_filter_hook__{$key}
	 * @description Change or filter field value by field key ($key)
	 * @input_vars
	 * [{"var":"$value","type":"string","desc":"Field Value"},
	 * {"var":"$data","type":"array","desc":"Field Data"}]
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage add_filter( 'um_profile_field_filter_hook__{$key}', 'function_name', 10, 2 );
	 * @example
	 * <?php
	 * add_filter( 'um_profile_field_filter_hook__{$key}', 'my_profile_field', 10, 2 );
	 * function my_profile_field( $value, $data ) {
	 *     // your code here
	 *     return $value;
	 * }
	 * ?>
	 */
	$value = apply_filters( "um_profile_field_filter_hook__{$key}", $value, $data );

	/**
	 * UM hook
	 *
	 * @type filter
	 * @title um_profile_field_filter_hook__{$type}
	 * @description Change or filter field value by field type ($type)
	 * @input_vars
	 * [{"var":"$value","type":"string","desc":"Field Value"},
	 * {"var":"$data","type":"array","desc":"Field Data"}]
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage add_filter( 'um_profile_field_filter_hook__{$type}', 'function_name', 10, 2 );
	 * @example
	 * <?php
	 * add_filter( 'um_profile_field_filter_hook__{$type}', 'my_profile_field', 10, 2 );
	 * function my_profile_field( $value, $data ) {
	 *     // your code here
	 *     return $value;
	 * }
	 * ?>
	 */
	$value = apply_filters( "um_profile_field_filter_hook__{$type}", $value, $data );
	$value = UM()->shortcodes()->emotize( $value );
	return $value;
}


/**
 * Returns requested User ID or current User ID
 *
 * @return int
 */
function um_profile_id() {
	$requested_user = um_get_requested_user();

	if ( $requested_user ) {
		return um_get_requested_user();
	} elseif ( is_user_logged_in() && get_current_user_id() ) {
		return get_current_user_id();
	}

	return 0;
}


/**
 * Check that temp upload is valid
 *
 * @param string $url
 *
 * @return bool|string
 */
function um_is_temp_upload( $url ) {
	if ( is_string( $url ) ) {
		$url = trim( $url );
	}

	if ( filter_var( $url, FILTER_VALIDATE_URL ) === false ) {
		$url = realpath( $url );
	}

	if ( ! $url ) {
		return false;
	}

	$url = explode( '/ultimatemember/temp/', $url );
	if ( isset( $url[1] ) ) {

		if ( strstr( $url[1], '../' ) || strstr( $url[1], '%' ) ) {
			return false;
		}

		$src = UM()->files()->upload_temp . $url[1];
		if ( ! file_exists( $src ) ) {
			return false;
		}

		return $src;
	}

	return false;
}


/**
 * Check that temp image is valid
 *
 * @param $url
 *
 * @return bool|string
 */
function um_is_temp_image( $url ) {
	$url = explode( '/ultimatemember/temp/', $url );
	if (isset( $url[1] )) {
		$src = UM()->files()->upload_temp . $url[1];
		if (!file_exists( $src ))
			return false;
		list( $width, $height, $type, $attr ) = @getimagesize( $src );
		if (isset( $width ) && isset( $height ))
			return $src;
	}

	return false;
}


/**
 * Check user's file ownership
 * @param string $url
 * @param int|null $user_id
 * @param string|bool $image_path
 * @return bool
 */
function um_is_file_owner( $url, $user_id = null, $image_path = false ) {

	if ( strpos( $url, UM()->uploader()->get_upload_base_url() . $user_id . '/' ) !== false && is_user_logged_in() ) {
		$user_basedir = UM()->uploader()->get_upload_user_base_dir( $user_id );
	} else {
		$user_basedir = UM()->uploader()->get_upload_user_base_dir( 'temp' );
	}

	$filename = wp_basename( parse_url( $url, PHP_URL_PATH ) );

	$file = $user_basedir . DIRECTORY_SEPARATOR . $filename;
	if ( file_exists( $file ) ) {
		if ( $image_path ) {
			return $file;
		}

		return true;
	}

	return false;
}


/**
 * Check if file is temporary
 * @param  string $filename
 * @return bool
 */
function um_is_temp_file( $filename ) {
	$user_basedir = UM()->uploader()->get_upload_user_base_dir( 'temp' );

	$file = $user_basedir . '/' . $filename;

	if ( file_exists( $file ) ) {
		return true;
	}
	return false;
}

/**
 * Get user's last login timestamp
 *
 * @param int $user_id
 *
 * @return int|string
 */
function um_user_last_login_timestamp( $user_id ) {
	$value = get_user_meta( $user_id, '_um_last_login', true );
	if ( $value ) {
		return strtotime( $value );
	}

	return '';
}

/**
 * Get user's last login (time diff)
 *
 * @param int $user_id
 *
 * @return string
 */
function um_user_last_login( $user_id ) {
	$value = get_user_meta( $user_id, '_um_last_login', true ); // Datetime format in UTC.
	return ! empty( $value ) ? UM()->datetime()->time_diff( strtotime( $value ) ) : ''; // Compare with default time() in 2nd attribute.
}

/**
 * Get core page url
 *
 * @param $slug
 * @param bool $updated
 *
 * @return bool|false|mixed|string|void
 */
function um_get_core_page( $slug, $updated = false ) {
	$url = '';

	if ( isset( UM()->config()->permalinks[ $slug ] ) ) {
		$url = get_permalink( UM()->config()->permalinks[ $slug ] );
		if ( $updated ) {
			$url = add_query_arg( 'updated', esc_attr( $updated ), $url );
		}
	}

	/**
	 * UM hook
	 *
	 * @type filter
	 * @title um_get_core_page_filter
	 * @description Change UM core page URL
	 * @input_vars
	 * [{"var":"$url","type":"string","desc":"UM Page URL"},
	 * {"var":"$slug","type":"string","desc":"UM Page slug"},
	 * {"var":"$updated","type":"bool","desc":"Additional parameter"}]
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage add_filter( 'um_get_core_page_filter', 'function_name', 10, 3 );
	 * @example
	 * <?php
	 * add_filter( 'um_get_core_page_filter', 'my_core_page_url', 10, 3 );
	 * function my_core_page_url( $url, $slug, $updated ) {
	 *     // your code here
	 *     return $url;
	 * }
	 * ?>
	 */
	return apply_filters( 'um_get_core_page_filter', $url, $slug, $updated );
}


/**
 * Check if we are on a UM Core Page or not
 *
 * Default um core pages slugs
 * 'user', 'login', 'register', 'members', 'logout', 'account', 'password-reset'
 *
 * @param string $page UM core page slug
 *
 * @return bool
 */
function um_is_core_page( $page ) {
	global $post;

	if ( empty( $post ) ) {
		return false;
	}

	if ( isset( $post->ID ) && isset( UM()->config()->permalinks[ $page ] ) && $post->ID == UM()->config()->permalinks[ $page ] ) {
		return true;
	}

	if ( isset( $post->ID ) && get_post_meta( $post->ID, '_um_wpml_' . $page, true ) == 1 ) {
		return true;
	}

	if ( UM()->external_integrations()->is_wpml_active() ) {
		global $sitepress;
		if ( isset( UM()->config()->permalinks[ $page ] ) && UM()->config()->permalinks[ $page ] == wpml_object_id_filter( $post->ID, 'page', true, $sitepress->get_default_language() ) ) {
			return true;
		}
	}

	if ( isset( $post->ID ) ) {
		$_icl_lang_duplicate_of = get_post_meta( $post->ID, '_icl_lang_duplicate_of', true );

		if ( isset( UM()->config()->permalinks[ $page ] ) && ( ( $_icl_lang_duplicate_of == UM()->config()->permalinks[ $page ] && !empty( $_icl_lang_duplicate_of ) ) || UM()->config()->permalinks[ $page ] == $post->ID ) ) {
			return true;
		}
	}

	return false;
}


/**
 * @param $post
 * @param $core_page
 *
 * @return bool
 */
function um_is_core_post( $post, $core_page ) {
	if ( isset( $post->ID ) && isset( UM()->config()->permalinks[ $core_page ] ) && $post->ID == UM()->config()->permalinks[ $core_page ] ) {
		return true;
	}
	if ( isset( $post->ID ) && get_post_meta( $post->ID, '_um_wpml_' . $core_page, true ) == 1 ) {
		return true;
	}

	if ( isset( $post->ID ) ) {
		$_icl_lang_duplicate_of = get_post_meta( $post->ID, '_icl_lang_duplicate_of', true );

		if ( isset( UM()->config()->permalinks[ $core_page ] ) && ( ( $_icl_lang_duplicate_of == UM()->config()->permalinks[ $core_page ] && ! empty( $_icl_lang_duplicate_of ) ) || UM()->config()->permalinks[ $core_page ] == $post->ID ) ) {
			return true;
		}
	}

	return false;
}


/**
 * Get styling defaults
 *
 * @param $mode
 *
 * @return array
 */
function um_styling_defaults( $mode ) {

	$new_arr = array();
	$core_form_meta_all = UM()->config()->core_form_meta_all;
	$core_global_meta_all = UM()->config()->core_global_meta_all;

	foreach ( $core_form_meta_all as $k => $v ) {
		$s = str_replace( $mode . '_', '', $k );
		if (strstr( $k, '_um_' . $mode . '_' ) && !in_array( $s, $core_global_meta_all )) {
			$a = str_replace( '_um_' . $mode . '_', '', $k );
			$b = str_replace( '_um_', '', $k );
			$new_arr[$a] = UM()->options()->get( $b );
		} else if (in_array( $k, $core_global_meta_all )) {
			$a = str_replace( '_um_', '', $k );
			$new_arr[$a] = UM()->options()->get( $a );
		}
	}

	return $new_arr;
}


/**
 * Get meta option default
 *
 * @param $id
 *
 * @return string
 */
function um_get_metadefault( $id ) {
	$core_form_meta_all = UM()->config()->core_form_meta_all;

	return isset( $core_form_meta_all[ '_um_' . $id ] ) ? $core_form_meta_all[ '_um_' . $id ] : '';
}


/**
 * boolean for account page editing
 *
 * @return bool
 */
function um_submitting_account_page() {
	if ( isset( $_POST['_um_account'] ) && $_POST['_um_account'] == 1 && is_user_logged_in() ) {
		return true;
	}

	return false;
}


/**
 * Get a user's display name
 *
 * @param $user_id
 *
 * @return string
 */
function um_get_display_name( $user_id ) {
	um_fetch_user( $user_id );
	$name = um_user( 'display_name' );
	um_reset_user();

	return $name;
}


/**
 * Clears the user data. You need to fetch a user manually after using this function.
 *
 * @function um_reset_user_clean()
 *
 * @description This function is similar to um_reset_user() with a difference that it will not use the logged-in
 *     user data after resetting. It is a hard-reset function for all user data.
 *
 * @usage <?php um_reset_user_clean(); ?>
 *
 * @example You can reset user data by using the following line in your code
 *
 * <?php um_reset_user_clean(); ?>
 */
function um_reset_user_clean() {
	UM()->user()->reset( true );
}


/**
 * Clears the user data. If a user is logged in, the user data will be reset to that user's data
 *
 * @function um_reset_user()
 *
 * @description This function resets the current user. You can use it to reset user data after
 * retrieving the details of a specific user.
 *
 * @usage <?php um_reset_user(); ?>
 *
 * @example You can reset user data by using the following line in your code
 *
 * <?php um_reset_user(); ?>
 */
function um_reset_user() {
	UM()->user()->reset();
}


/**
 * Gets the queried user
 *
 * @return mixed
 */
function um_queried_user() {
	return get_query_var( 'um_user' );
}


/**
 * Sets the requested user
 *
 * @param $user_id
 */
function um_set_requested_user( $user_id ) {
	UM()->user()->target_id = $user_id;
}


/**
 * Gets the requested user
 *
 * @return bool|null
 */
function um_get_requested_user() {
	if ( ! empty( UM()->user()->target_id ) ) {
		return absint( UM()->user()->target_id );
	}

	return false;
}


/**
 * Remove edit profile args from url
 *
 * @param string $url
 *
 * @return mixed|string|void
 */
function um_edit_my_profile_cancel_uri( $url = '' ) {

	if ( empty( $url ) ) {
		$url = remove_query_arg( 'um_action' );
		$url = remove_query_arg( 'profiletab', $url );
		$url = add_query_arg( 'profiletab', 'main', $url );
	}

	/**
	 * UM hook
	 *
	 * @type filter
	 * @title um_edit_profile_cancel_uri
	 * @description Change Edit Profile Cancel URL
	 * @input_vars
	 * [{"var":"$url","type":"string","desc":"Cancel URL"}]
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage add_filter( 'um_edit_profile_cancel_uri', 'function_name', 10, 1 );
	 * @example
	 * <?php
	 * add_filter( 'um_edit_profile_cancel_uri', 'my_edit_profile_cancel_uri', 10, 1 );
	 * function my_edit_profile_cancel_uri( $url ) {
	 *     // your code here
	 *     return $url;
	 * }
	 * ?>
	 */
	$url = apply_filters( 'um_edit_profile_cancel_uri', $url );

	return $url;
}


/**
 * boolean for profile edit page
 *
 * @return bool
 */
function um_is_on_edit_profile() {
	if ( isset( $_REQUEST['um_action'] ) && sanitize_key( $_REQUEST['um_action'] ) == 'edit' ) {
		return true;
	}

	return false;
}


/**
 * Can view field
 *
 * @param $data
 *
 * @return bool
 */
function um_can_view_field( $data ) {
	$can_view = true;

	if ( ! isset( UM()->fields()->set_mode ) ) {
		UM()->fields()->set_mode = '';
	}

	if ( isset( $data['public'] ) && 'register' !== UM()->fields()->set_mode ) {

		$can_edit           = false;
		$current_user_roles = array();
		if ( is_user_logged_in() ) {

			$can_edit = UM()->roles()->um_current_user_can( 'edit', um_user( 'ID' ) );

			$previous_user = um_user( 'ID' );
			um_fetch_user( get_current_user_id() );

			$current_user_roles = um_user( 'roles' );
			um_fetch_user( $previous_user );
		}

		switch ( $data['public'] ) {
			case '1': // Everyone
				break;
			case '2': // Members
				if ( ! is_user_logged_in() ) {
					$can_view = false;
				}
				break;
			case '-1': // Only visible to profile owner and users who can edit other member accounts
				if ( ! is_user_logged_in() ) {
					$can_view = false;
				} elseif ( ! um_is_user_himself() && ! $can_edit ) {
					$can_view = false;
				}
				break;
			case '-2': // Only specific member roles
				if ( ! is_user_logged_in() ) {
					$can_view = false;
				} elseif ( ! empty( $data['roles'] ) && count( array_intersect( $current_user_roles, $data['roles'] ) ) <= 0 ) {
					$can_view = false;
				}
				break;
			case '-3': // Only visible to profile owner and specific roles
				if ( ! is_user_logged_in() ) {
					$can_view = false;
				} elseif ( ! um_is_user_himself() && ! empty( $data['roles'] ) && count( array_intersect( $current_user_roles, $data['roles'] ) ) <= 0 ) {
					$can_view = false;
				}
				break;
			default:
				$can_view = apply_filters( 'um_can_view_field_custom', $can_view, $data );
				break;
		}
	}

	return apply_filters( 'um_can_view_field', $can_view, $data );
}

/**
 * Checks if user can view profile
 * @todo make the function review. Maybe rewrite it.
 * @param int $user_id
 *
 * @return bool
 */
function um_can_view_profile( $user_id ) {
	$can_view = true;
	$user_id  = absint( $user_id );
	if ( ! is_user_logged_in() ) {
		$can_view = ! UM()->user()->is_private_profile( $user_id );
	} else {
		$temp_id = um_user( 'ID' );
		um_fetch_user( get_current_user_id() );

		if ( get_current_user_id() !== $user_id ) {
			if ( ! um_user( 'can_view_all' ) ) {
				um_fetch_user( $temp_id );
				$can_view = false;
			} elseif ( ! um_user( 'can_access_private_profile' ) && UM()->user()->is_private_profile( $user_id ) ) {
				um_fetch_user( $temp_id );
				$can_view = false;
			} elseif ( um_user( 'can_view_roles' ) ) {
				$can_view_roles = um_user( 'can_view_roles' );

				if ( ! is_array( $can_view_roles ) ) {
					$can_view_roles = array();
				}

				$all_roles = UM()->roles()->get_all_user_roles( $user_id );
				if ( empty( $all_roles ) ) {
					um_fetch_user( $temp_id );
					$can_view = false;
				} else {
					if ( count( $can_view_roles ) && count( array_intersect( $all_roles, $can_view_roles ) ) <= 0 ) {
						um_fetch_user( $temp_id );
						$can_view = false;
					}
				}
			}
		}

		um_fetch_user( $temp_id );
	}

	/**
	 * Filters the marker for user capabilities to view other profile
	 *
	 * @param {bool} $can_view Can view profile marker.
	 * @param {int}  $user_id  User ID requested from profile page.
	 *
	 * @return {bool} Can view profile marker.
	 *
	 * @since 2.6.10
	 * @hook um_can_view_profile
	 *
	 * @example <caption>Set that only user with ID=5 can be viewed on Profile page.</caption>
	 * function my_um_can_view_profile( $can_view, $user_id ) {
	 *     $can_view = 5 === $user_id;
	 *     return $can_view;
	 * }
	 * add_filter( 'um_can_view_profile', 'my_um_can_view_profile', 10, 2 );
	 */
	return apply_filters( 'um_can_view_profile', $can_view, $user_id );
}

/**
 * boolean check for not same user
 *
 * @return bool
 */
function um_is_user_himself() {
	if (um_get_requested_user() && um_get_requested_user() != get_current_user_id())
		return false;

	return true;
}

/**
 * Can edit field
 *
 * @param $data
 *
 * @return bool
 */
function um_can_edit_field( $data ) {
	$can_edit = true;

	if ( true === UM()->fields()->editing && isset( UM()->fields()->set_mode ) && UM()->fields()->set_mode == 'profile' ) {
		if ( ! is_user_logged_in() ) {
			$can_edit = false;
		} else {
			if ( ! UM()->roles()->um_user_can( 'can_edit_everyone' ) ) {
				// It's for a legacy case `array_key_exists( 'editable', $data )`.
				if ( array_key_exists( 'editable', $data ) && empty( $data['editable'] ) ) {
					$can_edit = false;
				} else {
					if ( ! um_is_user_himself() ) {
						$can_edit = false;
					}
				}
			}
		}
	}

	return apply_filters( 'um_can_edit_field', $can_edit, $data );
}


/**
 * Check if user is in his profile
 *
 * @return bool
 */
function um_is_myprofile() {
	if (get_current_user_id() && get_current_user_id() == um_get_requested_user()) return true;
	if (!um_get_requested_user() && um_is_core_page( 'user' ) && get_current_user_id()) return true;

	return false;
}


/**
 * Returns the edit profile link
 *
 * @param int $user_id
 *
 * @return string
 */
function um_edit_profile_url( $user_id = null ) {
	if ( um_is_core_page( 'user' ) ) {
		$url = UM()->permalinks()->get_current_url();
	} else {
		$url = isset( $user_id ) ? um_user_profile_url( $user_id ) : um_user_profile_url();
	}

	$url = remove_query_arg( 'profiletab', $url );
	$url = remove_query_arg( 'subnav', $url );
	$url = add_query_arg( 'um_action', 'edit', $url );

	/**
	 * Filters change edit profile URL.
	 *
	 * @param {string} $url      Edit profile URL.
	 * @param {int}    $user_id  User ID.
	 *
	 * @return {string} Edit profile URL.
	 *
	 * @since 2.6.8
	 * @hook um_edit_profile_url
	 *
	 * @example <caption>Add/remove your custom $_GET attribute to all links.</caption>
	 * function my_um_edit_profile_url( $url, $user_id ) {
	 *     $url = add_query_arg( '{attr_value}', '{attr_key}', $url ); // replace to your custom value and key.
	 *     return $url;
	 * }
	 * add_filter( 'um_edit_profile_url', 'my_um_edit_profile_url', 10, 2 );
	 */
	$url = apply_filters( 'um_edit_profile_url', $url, $user_id );

	return $url;
}


/**
 * Checks if user can edit his profile
 *
 * @return bool
 */
function um_can_edit_my_profile() {
	if ( ! is_user_logged_in() || ! um_user( 'can_edit_profile' ) ) {
		return false;
	}

	return true;
}


/**
 * Short for admin email
 *
 * @return mixed|string|void
 */
function um_admin_email() {
	return UM()->options()->get( 'admin_email' );
}


/**
 * Get admin emails
 *
 * @return array
 */
function um_multi_admin_email() {
	$emails = UM()->options()->get( 'admin_email' );

	$emails_array = explode( ',', $emails );
	if ( ! empty( $emails_array ) ) {
		$emails_array = array_map( 'trim', $emails_array );
	}

	$emails_array = array_unique( $emails_array );
	return $emails_array;
}


/**
 * Display a link to profile page
 *
 * @param int|bool $user_id
 *
 * @return bool|string
 */
function um_user_profile_url( $user_id = false ) {
	if ( ! $user_id ) {
		$user_id = um_user( 'ID' );
	}

	$url = UM()->user()->get_profile_link( $user_id );
	if ( empty( $url ) ) {
		//if empty profile slug - generate it and re-get profile URL
		UM()->user()->generate_profile_slug( $user_id );
		$url = UM()->user()->get_profile_link( $user_id );
	}

	return $url;
}


/**
 * Get all UM roles in array
 *
 * @return array
 */
function um_get_roles() {
	return UM()->roles()->get_roles();
}


/**
 * Sets a specific user and prepares profile data and user permissions and makes them accessible.
 *
 * @function um_fetch_user()
 *
 * @description This function sets a user and allow you to retrieve any information for the retrieved user
 *
 * @usage <?php um_fetch_user( $user_id ); ?>
 *
 * @param $user_id (numeric) (required) A user ID is required. This is the user's ID that you wish to set/retrieve
 *
 *
 * @example The example below will set user ID 5 prior to retrieving his profile information.
 *
 * <?php
 *
 * um_fetch_user(5);
 * echo um_user('display_name'); // returns the display name of user ID 5
 *
 * ?>
 *
 * @example In the following example you can fetch the profile of a logged-in user dynamically.
 *
 * <?php
 *
 * um_fetch_user( get_current_user_id() );
 * echo um_user('display_name'); // returns the display name of logged-in user
 *
 * ?>
 *
 */
function um_fetch_user( $user_id ) {
	UM()->user()->set( $user_id );
}


/**
 * Load profile key
 *
 * @param $key
 *
 * @return bool|string
 */
function um_profile( $key ) {
	if ( ! empty( UM()->user()->profile[ $key ] ) ) {
		/**
		 * UM hook
		 *
		 * @type filter
		 * @title um_profile_{$key}__filter
		 * @description Change not empty profile field value
		 * @input_vars
		 * [{"var":"$value","type":"mixed","desc":"Profile Value"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage add_filter( 'um_profile_{$key}__filter', 'function_name', 10, 1 );
		 * @example
		 * <?php
		 * add_filter( 'um_profile_{$key}__filter', 'my_profile_value', 10, 1 );
		 * function my_profile_value( $value ) {
		 *     // your code here
		 *     return $value;
		 * }
		 * ?>
		 */
		$value = apply_filters( "um_profile_{$key}__filter", UM()->user()->profile[ $key ] );
	} else {
		/**
		 * UM hook
		 *
		 * @type filter
		 * @title um_profile_{$key}_empty__filter
		 * @description Change Profile field value if it's empty
		 * @input_vars
		 * [{"var":"$value","type":"mixed","desc":"Profile Value"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage add_filter( 'um_profile_{$key}_empty__filter', 'function_name', 10, 1 );
		 * @example
		 * <?php
		 * add_filter( 'um_profile_{$key}_empty__filter', 'my_profile_value', 10, 1 );
		 * function my_profile_value( $value ) {
		 *     // your code here
		 *     return $value;
		 * }
		 * ?>
		 */
		$value = apply_filters( "um_profile_{$key}_empty__filter", false );
	}

	return $value;
}

/**
 * Get YouTube video ID from URL.
 *
 * @param string $url
 *
 * @return bool|string
 */
function um_youtube_id_from_url( $url ) {
	if ( ! $url ) {
		return true;
	}
	$url = preg_replace( '/&ab_channel=.*/', '', $url ); // ADBlock argument.
	$url = preg_replace( '/\?si=.*/', '', $url ); // referral attribute.

	$pattern =
		'%^            # Match any youtube URL
		(?:https?://)? # Optional scheme. Either http or https
		(?:                 # Optional subdomain, for example m or www.
			[a-z0-9]          # Subdomain begins with alpha-num.
			(?:               # Optionally more than one char.
				[a-z0-9-]{0,61} # Middle part may have dashes.
				[a-z0-9]        # Starts and ends with alpha-num.
			)?                # Subdomain length from 1 to 63.
			\.                # Required dot separates subdomains.
		)?                  # Subdomain is optional.
		(?:            # Group host alternatives
		  youtu\.be/   # Either youtu.be,
		| youtube\.com # or youtube.com
		  (?:          # Group path alternatives
			/embed/      # Either /embed/
		  | /v/        # or /v/
		  | /watch\?v= # or /watch\?v=
		  | /shorts/   # or /shorts/ for short videos
		  )            # End path alternatives.
		)              # End host alternatives.
		([\w-]{10,12}) # Allow 10-12 for 11 char youtube id.
		(?:            # Additional parameters
		  (?:\?|\&)
		  \w+=[^&$]+
		)*
		$%x';

	$result = preg_match( $pattern, $url, $matches );
	if ( false !== $result && isset( $matches[1] ) ) {
		return $matches[1];
	}

	return false;
}

/**
 * Find closest number in an array
 *
 * @param $array
 * @param $number
 *
 * @return mixed
 */
function um_closest_num( $array, $number ) {
	sort( $array );
	foreach ( $array as $a ) {
		if ( $a >= $number ) return $a;
	}

	return end( $array );
}


/**
 * get cover uri
 *
 * @param $image
 * @param $attrs
 *
 * @return bool|string
 */
function um_get_cover_uri( $image, $attrs ) {
	$uri        = false;
	$uri_common = false;
	$ext        = '.' . pathinfo( $image, PATHINFO_EXTENSION );

	$ratio  = str_replace( ':1', '', UM()->options()->get( 'profile_cover_ratio' ) );
	$height = round( $attrs / $ratio );

	$timestamp = time();

	if ( is_multisite() ) {
		//multisite fix for old customers
		$multisite_fix_dir = UM()->uploader()->get_upload_base_dir();
		$multisite_fix_url = UM()->uploader()->get_upload_base_url();
		$multisite_fix_dir = str_replace( DIRECTORY_SEPARATOR . 'sites' . DIRECTORY_SEPARATOR . get_current_blog_id() . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $multisite_fix_dir );
		$multisite_fix_url = str_replace( '/sites/' . get_current_blog_id() . '/', '/', $multisite_fix_url );

		if ( file_exists( $multisite_fix_dir . um_user( 'ID' ) . DIRECTORY_SEPARATOR . "cover_photo{$ext}" ) ) {
			$uri_common = $multisite_fix_url . um_user( 'ID' ) . "/cover_photo{$ext}?" . $timestamp;
		}

		if ( file_exists( $multisite_fix_dir . um_user( 'ID' ) . DIRECTORY_SEPARATOR . "cover_photo-{$attrs}{$ext}" ) ) {
			$uri_common = $multisite_fix_url . um_user( 'ID' ) . "/cover_photo-{$attrs}{$ext}?" . $timestamp;
		}elseif ( file_exists( $multisite_fix_dir . um_user( 'ID' ) . DIRECTORY_SEPARATOR . "cover_photo-{$attrs}x{$height}{$ext}" ) ) {
			$uri_common = $multisite_fix_url . um_user( 'ID' ) . "/cover_photo-{$attrs}x{$height}{$ext}?". $timestamp;
		}
	}

	if ( file_exists( UM()->uploader()->get_upload_base_dir() . um_user( 'ID' ) . DIRECTORY_SEPARATOR . "cover_photo{$ext}" ) ) {
		$uri = UM()->uploader()->get_upload_base_url() . um_user( 'ID' ) . "/cover_photo{$ext}?" . $timestamp;
	}

	if ( file_exists( UM()->uploader()->get_upload_base_dir() . um_user( 'ID' ) . DIRECTORY_SEPARATOR . "cover_photo-{$attrs}{$ext}" ) ) {
		$uri = UM()->uploader()->get_upload_base_url() . um_user( 'ID' ) . "/cover_photo-{$attrs}{$ext}?" . $timestamp;
	}elseif ( file_exists( UM()->uploader()->get_upload_base_dir() . um_user( 'ID' ) . DIRECTORY_SEPARATOR . "cover_photo-{$attrs}x{$height}{$ext}" ) ) {
		$uri = UM()->uploader()->get_upload_base_url() . um_user( 'ID' ) . "/cover_photo-{$attrs}x{$height}{$ext}?". $timestamp;
	}

	if ( ! empty( $uri_common ) && empty( $uri ) ) {
		$uri = $uri_common;
	}

	return $uri;
}



/**
 * get avatar URL instead of image
 *
 * @param $get_avatar
 *
 * @return mixed
 */
function um_get_avatar_url( $get_avatar ) {
	preg_match( '/src="(.*?)"/i', $get_avatar, $matches );

	return isset( $matches[1] ) ? $matches[1] : '';
}


/**
 * get avatar uri
 *
 * @param $image
 * @param string|array $attrs
 *
 * @return bool|string
 */
function um_get_avatar_uri( $image, $attrs ) {
	$uri = false;
	$uri_common = false;
	$find = false;
	$ext = '.' . pathinfo( $image, PATHINFO_EXTENSION );

	if ( is_multisite() ) {
		//multisite fix for old customers
		$multisite_fix_dir = UM()->uploader()->get_upload_base_dir();
		$multisite_fix_url = UM()->uploader()->get_upload_base_url();
		$multisite_fix_dir = str_replace( DIRECTORY_SEPARATOR . 'sites' . DIRECTORY_SEPARATOR . get_current_blog_id() . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $multisite_fix_dir );
		$multisite_fix_url = str_replace( '/sites/' . get_current_blog_id() . '/', '/', $multisite_fix_url );

		if ( $attrs == 'original' && file_exists( $multisite_fix_dir . um_user( 'ID' ) . DIRECTORY_SEPARATOR . "profile_photo{$ext}" ) ) {
			$uri_common = $multisite_fix_url . um_user( 'ID' ) . "/profile_photo{$ext}";
		} elseif ( file_exists( $multisite_fix_dir . um_user( 'ID' ) . DIRECTORY_SEPARATOR . "profile_photo-{$attrs}x{$attrs}{$ext}" ) ) {
			$uri_common = $multisite_fix_url . um_user( 'ID' ) . "/profile_photo-{$attrs}x{$attrs}{$ext}";
		} elseif ( file_exists( $multisite_fix_dir . um_user( 'ID' ) . DIRECTORY_SEPARATOR . "profile_photo-{$attrs}{$ext}" ) ) {
			$uri_common = $multisite_fix_url . um_user( 'ID' ) . "/profile_photo-{$attrs}{$ext}";
		} else {
			$sizes = UM()->options()->get( 'photo_thumb_sizes' );
			if ( is_array( $sizes ) ) {
				$find = um_closest_num( $sizes, $attrs );
			}

			if ( file_exists( $multisite_fix_dir . um_user( 'ID' ) . DIRECTORY_SEPARATOR . "profile_photo-{$find}x{$find}{$ext}" ) ) {
				$uri_common = $multisite_fix_url . um_user( 'ID' ) . "/profile_photo-{$find}x{$find}{$ext}";
			} elseif ( file_exists( $multisite_fix_dir . um_user( 'ID' ) . DIRECTORY_SEPARATOR . "profile_photo-{$find}{$ext}" ) ) {
				$uri_common = $multisite_fix_url . um_user( 'ID' ) . "/profile_photo-{$find}{$ext}";
			} elseif ( file_exists( $multisite_fix_dir . um_user( 'ID' ) . DIRECTORY_SEPARATOR . "profile_photo{$ext}" ) ) {
				$uri_common = $multisite_fix_url . um_user( 'ID' ) . "/profile_photo{$ext}";
			}
		}
	}

	if ( $attrs == 'original' && file_exists( UM()->uploader()->get_upload_base_dir() . um_user( 'ID' ) . DIRECTORY_SEPARATOR . "profile_photo{$ext}" ) ) {
		$uri = UM()->uploader()->get_upload_base_url() . um_user( 'ID' ) . "/profile_photo{$ext}";
	} elseif ( file_exists( UM()->uploader()->get_upload_base_dir() . um_user( 'ID' ) . DIRECTORY_SEPARATOR . "profile_photo-{$attrs}x{$attrs}{$ext}" ) ) {
		$uri = UM()->uploader()->get_upload_base_url() . um_user( 'ID' ) . "/profile_photo-{$attrs}x{$attrs}{$ext}";
	} elseif ( file_exists( UM()->uploader()->get_upload_base_dir() . um_user( 'ID' ) . DIRECTORY_SEPARATOR . "profile_photo-{$attrs}{$ext}" ) ) {
		$uri = UM()->uploader()->get_upload_base_url() . um_user( 'ID' ) . "/profile_photo-{$attrs}{$ext}";
	} else {
		$sizes = UM()->options()->get( 'photo_thumb_sizes' );
		if ( is_array( $sizes ) ) {
			$find = um_closest_num( $sizes, $attrs );
		}

		if ( file_exists( UM()->uploader()->get_upload_base_dir() . um_user( 'ID' ) . DIRECTORY_SEPARATOR . "profile_photo-{$find}x{$find}{$ext}" ) ) {
			$uri = UM()->uploader()->get_upload_base_url() . um_user( 'ID' ) . "/profile_photo-{$find}x{$find}{$ext}";
		} elseif ( file_exists( UM()->uploader()->get_upload_base_dir() . um_user( 'ID' ) . DIRECTORY_SEPARATOR . "profile_photo-{$find}{$ext}" ) ) {
			$uri = UM()->uploader()->get_upload_base_url() . um_user( 'ID' ) . "/profile_photo-{$find}{$ext}";
		} elseif ( file_exists( UM()->uploader()->get_upload_base_dir() . um_user( 'ID' ) . DIRECTORY_SEPARATOR . "profile_photo{$ext}" ) ) {
			$uri = UM()->uploader()->get_upload_base_url() . um_user( 'ID' ) . "/profile_photo{$ext}";
		}
	}

	if ( ! empty( $uri_common ) && empty( $uri ) ) {
		$uri = $uri_common;
	}

	/**
	 * UM hook
	 *
	 * @type filter
	 * @title um_filter_avatar_cache_time
	 * @description Change Profile field value if it's empty
	 * @input_vars
	 * [{"var":"$timestamp","type":"timestamp","desc":"Avatar cache time"},
	 * {"var":"$user_id","type":"int","desc":"User ID"}]
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage add_filter( 'um_filter_avatar_cache_time', 'function_name', 10, 2 );
	 * @example
	 * <?php
	 * add_filter( 'um_filter_avatar_cache_time', 'my_avatar_cache_time', 10, 2 );
	 * function my_avatar_cache_time( $timestamp, $user_id ) {
	 *     // your code here
	 *     return $timestamp;
	 * }
	 * ?>
	 */
	$cache_time = apply_filters( 'um_filter_avatar_cache_time', time(), um_user( 'ID' ) );
	if ( ! empty( $cache_time ) ) {
		$uri .= "?{$cache_time}";
	}

	return $uri;
}

/**
 * Default avatar URL
 *
 * @return string
 */
function um_get_default_avatar_uri() {
	$uri = UM()->options()->get( 'default_avatar' );
	$uri = ! empty( $uri['url'] ) ? $uri['url'] : '';
	if ( ! $uri ) {
		$uri = UM_URL . 'assets/img/default_avatar.jpg';
	}

	return set_url_scheme( $uri );
}

/**
 * get user avatar url
 *
 * @param $user_id
 * @param $size
 *
 * @return bool|string
 */
function um_get_user_avatar_data( $user_id = '', $size = '96' ) {
	if ( empty( $user_id ) ) {
		$user_id = um_user( 'ID' );
	} else {
		um_fetch_user( $user_id );
	}

	$data = array(
		'user_id' => $user_id,
		'default' => um_get_default_avatar_uri(),
		'class'   => 'gravatar avatar avatar-' . $size . ' um-avatar',
		'size'    => $size,
	);

	if ( $profile_photo = um_profile( 'profile_photo' ) ) {
		$data['url'] = um_get_avatar_uri( $profile_photo, $size );
		$data['type'] = 'upload';
		$data['class'] .= ' um-avatar-uploaded';
	} elseif ( $synced_profile_photo = um_user( 'synced_profile_photo' ) ) {
		$data['url'] = $synced_profile_photo;
		$data['type'] = 'sync';
		$data['class'] .= ' um-avatar-default';
	} elseif ( UM()->options()->get( 'use_gravatars' ) ) {
		$avatar_hash_id = md5( um_user( 'user_email' ) );
		$data['url'] = set_url_scheme( '//gravatar.com/avatar/' . $avatar_hash_id );
		$data['url'] = add_query_arg( 's', 400, $data['url'] );
		$rating = get_option( 'avatar_rating' );
		if ( ! empty( $rating ) ) {
			$data['url'] = add_query_arg( 'r', $rating, $data['url'] );
		}

		$gravatar_type = UM()->options()->get( 'use_um_gravatar_default_builtin_image' );
		if ( $gravatar_type == 'default' ) {
			if ( UM()->options()->get( 'use_um_gravatar_default_image' ) ) {
				$data['url'] = add_query_arg( 'd', $data['default'], $data['url'] );
			} else {
				$default = get_option( 'avatar_default', 'mystery' );
				if ( $default == 'gravatar_default' ) {
					$default = '';
				}
				$data['url'] = add_query_arg( 'd', $default, $data['url'] );
			}
		} else {
			$data['url'] = add_query_arg( 'd', $gravatar_type, $data['url'] );
		}

		$data['type'] = 'gravatar';
		$data['class'] .= ' um-avatar-gravatar';
	} else {
		$data['url'] = $data['default'];
		$data['type'] = 'default';
		$data['class'] .= ' um-avatar-default';
	}


	/**
	 * UM hook
	 *
	 * @type filter
	 * @title um_user_avatar_url_filter
	 * @description Change user avatar URL
	 * @input_vars
	 * [{"var":"$avatar_uri","type":"string","desc":"Avatar URL"},
	 * {"var":"$user_id","type":"int","desc":"User ID"}]
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage add_filter( 'um_user_avatar_url_filter', 'function_name', 10, 2 );
	 * @example
	 * <?php
	 * add_filter( 'um_user_avatar_url_filter', 'my_user_avatar_url', 10, 2 );
	 * function my_user_avatar_url( $avatar_uri ) {
	 *     // your code here
	 *     return $avatar_uri;
	 * }
	 * ?>
	 */
	$data['url'] = apply_filters( 'um_user_avatar_url_filter', $data['url'], $user_id, $data );
	/**
	 * UM hook
	 *
	 * @type filter
	 * @title um_avatar_image_alternate_text
	 * @description Change user display name on um_user function profile photo
	 * @input_vars
	 * [{"var":"$display_name","type":"string","desc":"User Display Name"}]
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage add_filter( 'um_avatar_image_alternate_text', 'function_name', 10, 1 );
	 * @example
	 * <?php
	 * add_filter( 'um_avatar_image_alternate_text', 'my_avatar_image_alternate_text', 10, 1 );
	 * function my_avatar_image_alternate_text( $display_name ) {
	 *     // your code here
	 *     return $display_name;
	 * }
	 * ?>
	 */
	$data['alt'] = apply_filters( "um_avatar_image_alternate_text", um_user( "display_name" ), $data );

	return $data;
}


/**
 * get user avatar url
 *
 * @param $user_id
 * @param $size
 *
 * @return bool|string
 */
function um_get_user_avatar_url( $user_id = '', $size = '96' ) {
	$data = um_get_user_avatar_data( $user_id, $size );
	return $data['url'];
}


/**
 * default cover
 *
 * @return mixed|string|void
 */
function um_get_default_cover_uri() {
	$uri = UM()->options()->get( 'default_cover' );
	$uri = ! empty( $uri['url'] ) ? $uri['url'] : '';
	if ( $uri ) {

		/**
		 * UM hook
		 *
		 * @type filter
		 * @title um_get_default_cover_uri_filter
		 * @description Change Default Cover URL
		 * @input_vars
		 * [{"var":"$uri","type":"string","desc":"Default Cover URL"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage add_filter( 'um_get_default_cover_uri_filter', 'function_name', 10, 1 );
		 * @example
		 * <?php
		 * add_filter( 'um_get_default_cover_uri_filter', 'my_default_cover_uri', 10, 1 );
		 * function my_default_cover_uri( $uri ) {
		 *     // your code here
		 *     return $uri;
		 * }
		 * ?>
		 */
		return apply_filters( 'um_get_default_cover_uri_filter', $uri );
	}

	return '';
}


/**
 * @param $data
 * @param null $attrs
 *
 * @return int|string|array
 */
function um_user( $data, $attrs = null ) {

	switch ($data) {

		default:

			$value = um_profile( $data );

			$value = maybe_unserialize( $value );

			if ( in_array( $data, array( 'role', 'gender' ) ) ) {
				if ( is_array( $value ) ) {
					$value = implode( ",", $value );
				}

				return $value;
			}

			return $value;
			break;

		case 'user_email':

			$user_email_in_meta = get_user_meta( um_user( 'ID' ), 'user_email', true );
			if ( $user_email_in_meta ) {
				delete_user_meta( um_user( 'ID' ), 'user_email' );
			}

			$value = um_profile( $data );

			return $value;
			break;

		case 'user_login':

			$user_login_in_meta = get_user_meta( um_user( 'ID' ), 'user_login', true );
			if ( $user_login_in_meta ) {
				delete_user_meta( um_user( 'ID' ), 'user_login' );
			}

			$value = um_profile( $data );

			return $value;
			break;

		case 'first_name':
		case 'last_name':

			$name = um_profile( $data );

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_user_{$data}_case
			 * @description Change user name on um_user function
			 * @input_vars
			 * [{"var":"$name","type":"string","desc":"User Name"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_user_{$data}_case', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_filter( 'um_user_{$data}_case', 'my_user_case', 10, 1 );
			 * function my_user_case( $name ) {
			 *     // your code here
			 *     return $name;
			 * }
			 * ?>
			 */
			$name = apply_filters( "um_user_{$data}_case", $name );

			return $name;

			break;

		case 'full_name':

			if ( um_user( 'first_name' ) && um_user( 'last_name' ) ) {
				$full_name = um_user( 'first_name' ) . ' ' . um_user( 'last_name' );
			} else {
				$full_name = um_user( 'display_name' );
			}

			$full_name = UM()->validation()->safe_name_in_url( $full_name );

			// update full_name changed
			if ( um_profile( $data ) !== $full_name ) {
				update_user_meta( um_user( 'ID' ), 'full_name', $full_name );
			}

			return $full_name;

			break;

		case 'first_and_last_name_initial':

			$f_and_l_initial = '';

			if ( um_user( 'first_name' ) && um_user( 'last_name' ) ) {
				$initial = um_user( 'last_name' );
				$f_and_l_initial = um_user( 'first_name' ) . ' ' . $initial[0];
			} else {
				$f_and_l_initial = um_profile( $data );
			}

			$name = UM()->validation()->safe_name_in_url( $f_and_l_initial );
			return $name;

			break;

		case 'display_name':

			$op = UM()->options()->get( 'display_name' );

			$name = '';

			if ( $op == 'default' ) {
				$name = um_profile( 'display_name' );
			}

			if ( $op == 'nickname' ) {
				$name = um_profile( 'nickname' );
			}

			if ( $op == 'full_name' ) {
				if ( um_user( 'first_name' ) && um_user( 'last_name' ) ) {
					$name = um_user( 'first_name' ) . ' ' . um_user( 'last_name' );
				} else {
					$name = um_profile( $data );
				}
				if ( ! $name ) {
					$name = um_user( 'user_login' );
				}
			}

			if ( $op == 'sur_name' ) {
				if ( um_user( 'first_name' ) && um_user( 'last_name' ) ) {
					$name = um_user( 'last_name' ) . ' ' . um_user( 'first_name' );
				} else {
					$name = um_profile( $data );
				}
			}

			if ( $op == 'first_name' ) {
				if ( um_user( 'first_name' ) ) {
					$name = um_user( 'first_name' );
				} else {
					$name = um_profile( $data );
				}
			}

			if ( $op == 'username' ) {
				$name = um_user( 'user_login' );
			}

			if ( $op == 'initial_name' ) {
				if (um_user( 'first_name' ) && um_user( 'last_name' )) {
					$initial = um_user( 'last_name' );
					$name = um_user( 'first_name' ) . ' ' . $initial[0];
				} else {
					$name = um_profile( $data );
				}
			}

			if ( $op == 'initial_name_f' ) {
				if ( um_user( 'first_name' ) && um_user( 'last_name' ) ) {
					$initial = um_user( 'first_name' );
					$name = $initial[0] . ' ' . um_user( 'last_name' );
				} else {
					$name = um_profile( $data );
				}
			}


			if ( $op == 'field' && UM()->options()->get( 'display_name_field' ) != '' ) {
				$fields = array_filter( preg_split( '/[,\s]+/', UM()->options()->get( 'display_name_field' ) ) );
				$name = '';

				foreach ( $fields as $field ) {
					if ( um_profile( $field ) ) {

						$field_value = maybe_unserialize( um_profile( $field ) );
						$field_value = is_array( $field_value ) ? implode( ',', $field_value ) : $field_value;

						$name .= $field_value . ' ';
					} elseif ( um_user( $field ) && $field != 'display_name' && $field != 'full_name' ) {
						$name .= um_user( $field ) . ' ';
					}
				}
			}

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_user_display_name_filter
			 * @description Change user display name on um_user function
			 * @input_vars
			 * [{"var":"$name","type":"string","desc":"User Name"},
			 * {"var":"$user_id","type":"int","desc":"User ID"},
			 * {"var":"$html","type":"bool","desc":"Is HTML"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_user_display_name_filter', 'function_name', 10, 3 );
			 * @example
			 * <?php
			 * add_filter( 'um_user_display_name_filter', 'my_user_display_name', 10, 3 );
			 * function my_user_display_name( $name, $user_id, $html ) {
			 *     // your code here
			 *     return $name;
			 * }
			 * ?>
			 */
			return apply_filters( 'um_user_display_name_filter', $name, um_user( 'ID' ), ( $attrs == 'html' ) ? 1 : 0 );

			break;

		case 'role_select':
		case 'role_radio':

			return UM()->roles()->get_role_name( UM()->roles()->get_editable_priority_user_role( um_user( 'ID' ) ) );
			break;

		case 'submitted':
			$array = um_profile( $data );
			if ( empty( $array ) ) {
				return '';
			}
			$array = maybe_unserialize( $array );

			return $array;
			break;

		case 'password_reset_link':
			return UM()->password()->reset_url();
			break;

		case 'account_activation_link':
			return UM()->permalinks()->activate_url();
			break;

		case 'profile_photo':
			$data = um_get_user_avatar_data( um_user( 'ID' ), $attrs );

			return sprintf( '<img src="%s" class="%s" width="%s" height="%s" alt="%s" data-default="%s" onerror="%s" loading="lazy" />',
				esc_attr( $data['url'] ),
				esc_attr( $data['class'] ),
				esc_attr( $data['size'] ),
				esc_attr( $data['size'] ),
				esc_attr( $data['alt'] ),
				esc_attr( $data['default'] ),
				'if ( ! this.getAttribute(\'data-load-error\') ){ this.setAttribute(\'data-load-error\', \'1\');this.setAttribute(\'src\', this.getAttribute(\'data-default\'));}'
			);
			break;

		case 'cover_photo':

			$is_default = false;

			if ( um_profile( 'cover_photo' ) ) {
				$cover_uri = um_get_cover_uri( um_profile( 'cover_photo' ), $attrs );
			} elseif ( um_profile( 'synced_cover_photo' ) ) {
				$cover_uri = um_profile( 'synced_cover_photo' );
			} else {
				$cover_uri = um_get_default_cover_uri();
				$is_default = true;
			}

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_user_cover_photo_uri__filter
			 * @description Change user avatar URL
			 * @input_vars
			 * [{"var":"$cover_uri","type":"string","desc":"Cover URL"},
			 * {"var":"$is_default","type":"bool","desc":"Default or not"},
			 * {"var":"$attrs","type":"array","desc":"Attributes"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_user_cover_photo_uri__filter', 'function_name', 10, 3 );
			 * @example
			 * <?php
			 * add_filter( 'um_user_cover_photo_uri__filter', 'my_user_cover_photo_uri', 10, 3 );
			 * function my_user_cover_photo_uri( $cover_uri, $is_default, $attrs ) {
			 *     // your code here
			 *     return $cover_uri;
			 * }
			 * ?>
			 */
			$cover_uri = apply_filters( 'um_user_cover_photo_uri__filter', $cover_uri, $is_default, $attrs );

			$alt = um_profile( 'nickname' );

			$cover_html = $cover_uri ? '<img src="' . esc_attr( $cover_uri ) . '" alt="' . esc_attr( $alt ) . '" loading="lazy" />' : '';

			$cover_html = apply_filters( 'um_user_cover_photo_html__filter', $cover_html, $cover_uri, $alt, $is_default, $attrs );
			return $cover_html;

			break;

		case 'user_url':

			$value = um_profile( $data );

			return $value;

			break;


	}

}


/**
 * Get server protocol
 *
 * @return  string
 */
function um_get_domain_protocol() {

	if (is_ssl()) {
		$protocol = 'https://';
	} else {
		$protocol = 'http://';
	}

	return $protocol;
}


/**
 * Set SSL to media URI
 *
 * @param  string $url
 *
 * @return string
 */
function um_secure_media_uri( $url ) {

	if (is_ssl()) {
		$url = str_replace( 'http:', 'https:', $url );
	}

	return $url;
}


/**
 * Force strings to UTF-8 encoded
 *
 * @param  mixed $value
 *
 * @return mixed
 */
function um_force_utf8_string( $value ) {

	if ( is_array( $value ) ) {
		$arr_value = array();
		foreach ( $value as $key => $v ) {
			if ( ! function_exists( 'utf8_decode' ) ) {
				continue;
			}

			$utf8_decoded_value = utf8_decode( $v );

			if ( function_exists( 'mb_check_encoding' ) && mb_check_encoding( $utf8_decoded_value, 'UTF-8' ) ) {
				array_push( $arr_value, $utf8_decoded_value );
			} else {
				array_push( $arr_value, $v );
			}

		}

		return $arr_value;
	} else {
		if ( function_exists( 'utf8_decode' ) ) {
			$utf8_decoded_value = utf8_decode( $value );

			if ( function_exists( 'mb_check_encoding' ) && mb_check_encoding( $utf8_decoded_value, 'UTF-8' ) ) {
				return $utf8_decoded_value;
			}
		}
	}

	return $value;
}


/**
 * Get user host
 *
 * Returns the webhost this site is using if possible
 *
 * @since 1.3.68
 * @return mixed string $host if detected, false otherwise
 */
function um_get_host() {
	$host = false;

	if (defined( 'WPE_APIKEY' )) {
		$host = 'WP Engine';
	} else if (defined( 'PAGELYBIN' )) {
		$host = 'Pagely';
	} else if (DB_HOST == 'localhost:/tmp/mysql5.sock') {
		$host = 'ICDSoft';
	} else if (DB_HOST == 'mysqlv5') {
		$host = 'NetworkSolutions';
	} else if (strpos( DB_HOST, 'ipagemysql.com' ) !== false) {
		$host = 'iPage';
	} else if (strpos( DB_HOST, 'ipowermysql.com' ) !== false) {
		$host = 'IPower';
	} else if (strpos( DB_HOST, '.gridserver.com' ) !== false) {
		$host = 'MediaTemple Grid';
	} else if (strpos( DB_HOST, '.pair.com' ) !== false) {
		$host = 'pair Networks';
	} else if (strpos( DB_HOST, '.stabletransit.com' ) !== false) {
		$host = 'Rackspace Cloud';
	} else if (strpos( DB_HOST, '.sysfix.eu' ) !== false) {
		$host = 'SysFix.eu Power Hosting';
	} else if (strpos( $_SERVER['SERVER_NAME'], 'Flywheel' ) !== false) {
		$host = 'Flywheel';
	} else {
		// Adding a general fallback for data gathering
		$host = 'DBH: ' . DB_HOST . ', SRV: ' . $_SERVER['SERVER_NAME'];
	}

	return $host;
}


/**
 * Let To Num
 *
 * Does Size Conversions
 *
 * @since 1.3.68
 * @author Chris Christoff
 *
 * @param string $v
 *
 * @return int|string
 */
function um_let_to_num( $v ) {
	$l = substr( $v, -1 );
	$ret = substr( $v, 0, -1 );

	switch (strtoupper( $l )) {
		case 'P': // fall-through
		case 'T': // fall-through
		case 'G': // fall-through
		case 'M': // fall-through
		case 'K': // fall-through
			$ret *= 1024;
			break;
		default:
			break;
	}

	return $ret;
}


/**
 * Check if we are on UM page
 *
 * @return bool
 */
function is_ultimatemember() {
	global $post;

	if ( isset( $post->ID ) && in_array( $post->ID, UM()->config()->permalinks ) )
		return true;

	return false;
}


/**
 * Maybe set empty time limit
 */
function um_maybe_unset_time_limit() {
	@set_time_limit( 0 );
}


/*
 * Check if current user is owner of requested profile
 * @Returns Boolean
*/
if ( ! function_exists( 'um_is_profile_owner' ) ) {
	/**
	 * @param $user_id
	 *
	 * @return bool
	 */
	function um_is_profile_owner( $user_id = false ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		return ( $user_id == um_profile_id() );
	}
}


/**
 * Check whether the current page is in AMP mode or not.
 * We need to check for specific functions, as there is no special AMP header.
 *
 * @since 2.1.11
 *
 * @param bool $check_theme_support Whether theme support should be checked. Defaults to true.
 *
 * @uses is_amp_endpoint() AMP by Automattic
 * @uses is_better_amp() Better AMP
 *
 * @return bool
 */
function um_is_amp( $check_theme_support = true ) {

	$is_amp = false;

	if ( ( function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() ) ||
	     ( function_exists( 'is_better_amp' ) && is_better_amp() ) ) {
		$is_amp = true;
	}

	if ( $is_amp && $check_theme_support ) {
		$is_amp = current_theme_supports( 'amp' );
	}

	return apply_filters( 'um_is_amp', $is_amp );
}

/**
 * UM safe redirect. By default, you can be redirected only to WordPress installation Home URL. Fallback URL is wp-admin URL.
 * But it can be changed through filters and extended by UM Setting "Allowed hosts for safe redirect (one host per line)" and filter `um_wp_safe_redirect_fallback`.
 *
 * @since 2.6.8
 *
 * @param string $url redirect URL.
 */
function um_safe_redirect( $url ) {
	add_filter( 'allowed_redirect_hosts', 'um_allowed_redirect_hosts' );
	add_filter( 'wp_safe_redirect_fallback', 'um_wp_safe_redirect_fallback', 10, 2 );

	wp_safe_redirect( $url );
	exit;
}

/**
 * UM allowed hosts
 *
 * @since 2.6.8
 *
 * @param array $hosts Allowed hosts.
 *
 * @return array
 */
function um_allowed_redirect_hosts( $hosts ) {
	$secure_hosts = UM()->options()->get( 'secure_allowed_redirect_hosts' );
	$secure_hosts = explode( "\n", $secure_hosts );
	$secure_hosts = array_unique( $secure_hosts );

	$additional_hosts = array();
	foreach ( $secure_hosts as $host ) {
		if ( '' !== trim( $host ) ) {
			$host = trim( $host );
			$host = str_replace( array( 'http://', 'https://' ), '', $host );
			$host = trim( $host, '/' );
			$host = strtolower( $host );

			if ( ! in_array( $host, $additional_hosts, true ) ) {
				$additional_hosts[] = $host;
			}

			if ( strpos( $host, 'www.' ) !== false ) {
				$strip_www = str_replace( 'www.', '', $host );
				if ( ! in_array( $strip_www, $additional_hosts, true ) ) {
					$additional_hosts[] = $strip_www;
				}
			} else {
				$added_www = 'www.' . $host;
				if ( ! in_array( $added_www, $additional_hosts, true ) ) {
					$additional_hosts[] = $added_www;
				}
			}
		}
	}
	/**
	 * Filters change allowed hosts. When `wp_safe_redirect()` function is used for the Ultimate Member frontend redirects.
	 *
	 * @since 2.6.8
	 * @hook  um_allowed_redirect_hosts
	 *
	 * @param {array} $additional_hosts Allowed hosts.
	 * @param {array} $hosts            Default hosts.
	 *
	 * @return {array} Allowed hosts.
	 *
	 * @example <caption>Change allowed hosts.</caption>
	 * function my_um_allowed_redirect_hosts( $additional_hosts, $hosts ) {
	 *     // your code here
	 *     return $allowed_hosts;
	 * }
	 * add_filter( 'um_allowed_redirect_hosts', 'my_um_allowed_redirect_hosts', 10, 2 );
	 */
	$additional_hosts = apply_filters( 'um_allowed_redirect_hosts', $additional_hosts, $hosts );
	return array_merge( $hosts, $additional_hosts );
}

/**
 * UM fallback redirect URL
 *
 * @since 2.6.8
 *
 * @param string $url    Fallback URL.
 * @param string $status Redirect status.
 *
 * @return string
 */
function um_wp_safe_redirect_fallback( $url, $status ) {
	/**
	 * Filters change fallback URL. When `wp_safe_redirect()` function is used for the Ultimate Member frontend redirects.
	 * It's `home_url()` by default.
	 *
	 * @since 2.6.8
	 * @hook  um_wp_safe_redirect_fallback
	 *
	 * @param {string} $url              UM Fallback URL.
	 * @param {string} $default_fallback Default fallback URL.
	 * @param {string} $status           Redirect status.
	 *
	 * @return {string} Fallback URL.
	 *
	 * @example <caption>Change fallback URL.</caption>
	 * function my_um_wp_safe_redirect_fallback( $url, $status ) {
	 *     // your code here
	 *     return $url;
	 * }
	 * add_filter( 'um_wp_safe_redirect_fallback', 'my_um_wp_safe_redirect_fallback', 10, 2 );
	 */
	return apply_filters( 'um_wp_safe_redirect_fallback', home_url( '/' ), $url, $status );
}
