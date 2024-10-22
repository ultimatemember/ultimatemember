<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Error handling: blocked emails
 *
 * @param $submitted_data
 */
function um_submit_form_errors_hook__blockedemails( $submitted_data ) {
	$emails = UM()->options()->get( 'blocked_emails' );
	if ( ! $emails ) {
		return;
	}

	$emails = strtolower( $emails );
	$emails = array_map( 'rtrim', explode( "\n", $emails ) );

	if ( isset( $submitted_data['user_email'] ) && is_email( $submitted_data['user_email'] ) ) {
		if ( in_array( strtolower( $submitted_data['user_email'] ), $emails ) ) {
			exit( wp_redirect( esc_url( add_query_arg( 'err', 'blocked_email' ) ) ) );
		}

		$domain       = explode( '@', $submitted_data['user_email'] );
		$check_domain = str_replace( $domain[0], '*', $submitted_data['user_email'] );

		if ( in_array( strtolower( $check_domain ), $emails ) ) {
			exit( wp_redirect( esc_url( add_query_arg( 'err', 'blocked_domain' ) ) ) );
		}
	}

	if ( isset( $submitted_data['username'] ) && is_email( $submitted_data['username'] ) ) {
		if ( in_array( strtolower( $submitted_data['username'] ), $emails ) ) {
			exit( wp_redirect( esc_url( add_query_arg( 'err', 'blocked_email' ) ) ) );
		}

		$domain       = explode( '@', $submitted_data['username'] );
		$check_domain = str_replace( $domain[0], '*', $submitted_data['username'] );

		if ( in_array( strtolower( $check_domain ), $emails ) ) {
			exit( wp_redirect( esc_url( add_query_arg( 'err', 'blocked_domain' ) ) ) );
		}
	}
}
add_action( 'um_submit_form_errors_hook__blockedemails', 'um_submit_form_errors_hook__blockedemails' );


/**
 * Error handling: blocked IPs.
 */
function um_submit_form_errors_hook__blockedips() {
	$ips = UM()->options()->get( 'blocked_ips' );
	if ( ! $ips ) {
		return;
	}

	$ips = array_map( 'rtrim', explode( "\n", $ips ) );
	$user_ip = um_user_ip();

	foreach ( $ips as $ip ) {
		$ip = str_replace( '*', '', $ip );
		if ( ! empty( $ip ) && strpos( $user_ip, $ip ) === 0 ) {
			exit( wp_redirect( esc_url( add_query_arg( 'err', 'blocked_ip' ) ) ) );
		}
	}
}
add_action( 'um_submit_form_errors_hook__blockedips', 'um_submit_form_errors_hook__blockedips' );

/**
 * Error handling: blocked words during sign up
 *
 * @todo change the hook after checking that conditions inside this callback can be run only while registration.
 *
 * @param array $submitted_data
 * @param array $form_data
 */
function um_submit_form_errors_hook__blockedwords( $submitted_data, $form_data ) {
	$words = UM()->options()->get( 'blocked_words' );
	if ( empty( $words ) ) {
		return;
	}

	$fields = maybe_unserialize( $form_data['custom_fields'] );
	if ( empty( $fields ) || ! is_array( $fields ) ) {
		return;
	}

	$words = strtolower( $words );
	$words = array_map( 'rtrim', explode( "\n", $words ) );
	foreach ( $fields as $key => $array ) {
		if ( isset( $array['validate'] ) && in_array( $array['validate'], array( 'unique_username', 'unique_email', 'unique_username_or_email' ), true ) ) {
			if ( UM()->form()->has_error( $key ) ) {
				continue;
			}

			if ( array_key_exists( $key, $submitted_data ) && in_array( strtolower( $submitted_data[ $key ] ), $words, true ) ) {
				UM()->form()->add_error( $key, __( 'You are not allowed to use this word as your username.', 'ultimate-member' ) );
			}
		}
	}
}
add_action( 'um_submit_form_errors_hook__blockedwords', 'um_submit_form_errors_hook__blockedwords', 10, 2 );

/**
 * UM login|register|profile form error handling.
 *
 * @param array $submitted_data
 * @param array $form_data
 */
function um_submit_form_errors_hook( $submitted_data, $form_data ) {
	$mode = $form_data['mode'];

	/**
	 * Fires for validation blocked IPs when UM login, registration or profile form has been submitted.
	 *
	 * Internal Ultimate Member callbacks (Priority -> Callback name -> Excerpt):
	 * 10 - `um_submit_form_errors_hook__blockedips()` Native validation handlers.
	 *
	 * @since 1.3.x
	 * @hook um_submit_form_errors_hook__blockedips
	 *
	 * @param {array} $submitted_data $_POST Submission array.
	 * @param {array} $form_data      UM form data. Since 2.6.7
	 *
	 * @example <caption>Make any common validation action when login, registation or profile form is submitted.</caption>
	 * function my_submit_form_errors_hook__blockedips( $post, $form_data ) {
	 *     // your code here
	 * }
	 * add_action( 'um_submit_form_errors_hook__blockedips', 'my_submit_form_errors_hook__blockedips', 10, 2 );
	 */
	do_action( 'um_submit_form_errors_hook__blockedips', $submitted_data, $form_data );
	/**
	 * Fires for validation blocked email addresses when UM login, registration or profile form has been submitted.
	 *
	 * Internal Ultimate Member callbacks (Priority -> Callback name -> Excerpt):
	 * 10 - `um_submit_form_errors_hook__blockedemails()` Native validation handlers.
	 *
	 * @since 1.3.x
	 * @hook um_submit_form_errors_hook__blockedemails
	 *
	 * @param {array} $submitted_data $_POST Submission array.
	 * @param {array} $form_data      UM form data. Since 2.6.7
	 *
	 * @example <caption>Make any common validation action when login, registation or profile form is submitted.</caption>
	 * function my_submit_form_errors_hook__blockedemails( $post, $form_data ) {
	 *     // your code here
	 * }
	 * add_action( 'um_submit_form_errors_hook__blockedemails', 'my_submit_form_errors_hook__blockedemails', 10, 2 );
	 */
	do_action( 'um_submit_form_errors_hook__blockedemails', $submitted_data, $form_data );

	if ( 'login' === $mode ) {
		/**
		 * Fires for login form validation when it has been submitted.
		 *
		 * Internal Ultimate Member callbacks (Priority -> Callback name -> Excerpt):
		 * 10 - `um_submit_form_errors_hook_login()` Native login validation handlers.
		 *
		 * @since 1.3.x
		 * @hook um_submit_form_errors_hook_login
		 *
		 * @param {array} $submitted_data $_POST Submission array.
		 * @param {array} $form_data      UM form data. Since 2.6.7
		 *
		 * @example <caption>Make any validation action when login form is submitted.</caption>
		 * function my_submit_form_errors_hook_login( $post, $form_data ) {
		 *     // your code here
		 * }
		 * add_action( 'um_submit_form_errors_hook_login', 'my_submit_form_errors_hook_login', 10, 2 );
		 */
		do_action( 'um_submit_form_errors_hook_login', $submitted_data, $form_data );
		/**
		 * Fires for login form validation when it has been submitted.
		 *
		 * Internal Ultimate Member callbacks (Priority -> Callback name -> Excerpt):
		 * 9999 - `um_submit_form_errors_hook_logincheck()` Native login validation handlers.
		 *
		 * @since 1.3.x
		 * @hook um_submit_form_errors_hook_logincheck
		 *
		 * @param {array} $submitted_data $_POST Submission array.
		 * @param {array} $form_data      UM form data. Since 2.6.7
		 *
		 * @example <caption>Make any validation action when login form is submitted.</caption>
		 * function my_submit_form_errors_hook_logincheck( $post, $form_data ) {
		 *     // your code here
		 * }
		 * add_action( 'um_submit_form_errors_hook_logincheck', 'my_submit_form_errors_hook_logincheck', 10, 2 );
		 */
		do_action( 'um_submit_form_errors_hook_logincheck', $submitted_data, $form_data );
	} else {
		if ( 'register' === $mode ) {
			/**
			 * Fires for registration form validation when it has been submitted.
			 *
			 * Internal Ultimate Member callbacks (Priority -> Callback name -> Excerpt):
			 * 10 - `um_submit_form_errors_hook__registration()` Native registration validation handlers.
			 *
			 * @since 1.3.x
			 * @hook um_submit_form_errors_hook__registration
			 *
			 * @param {array} $submitted_data $_POST Submission array.
			 * @param {array} $form_data      UM form data. Since 2.6.7
			 *
			 * @example <caption>Make any validation action when registration form submitted.</caption>
			 * function my_submit_form_errors_hook__registration( $submitted_data, $form_data ) {
			 *     // your code here
			 * }
			 * add_action( 'um_submit_form_errors_hook__registration', 'my_submit_form_errors_hook__registration', 10, 2 );
			 */
			do_action( 'um_submit_form_errors_hook__registration', $submitted_data, $form_data );
		} elseif ( 'profile' === $mode ) {
			/**
			 * Fires for profile form validation when it has been submitted.
			 *
			 * Internal Ultimate Member callbacks (Priority -> Callback name -> Excerpt):
			 * 1 - `um_profile_validate_nonce()` Validate nonce.
			 *
			 * @since 2.0
			 * @hook um_submit_form_errors_hook__profile
			 *
			 * @param {array} $submitted_data $_POST Submission array.
			 * @param {array} $form_data      UM form data. Since 2.6.7
			 *
			 * @example <caption>Make any validation action when profile form submitted.</caption>
			 * function my_submit_form_errors_hook__profile( $submitted_data, $form_data ) {
			 *     // your code here
			 * }
			 * add_action( 'um_submit_form_errors_hook__profile', 'my_submit_form_errors_hook__profile', 10, 2 );
			 */
			do_action( 'um_submit_form_errors_hook__profile', $submitted_data, $form_data );
		}

		/**
		 * Fires for registration and profile forms validation when it has been submitted.
		 *
		 * Internal Ultimate Member callbacks (Priority -> Callback name -> Excerpt):
		 * 10 - `um_submit_form_errors_hook__blockedwords()` Validate username and email from blocked words.
		 *
		 * @since 1.3.x
		 * @hook um_submit_form_errors_hook__blockedwords
		 *
		 * @param {array} $submitted_data $_POST Submission array.
		 * @param {array} $form_data      UM form data. Since 2.6.7
		 *
		 * @example <caption>Make any validation action when registration or profile form submitted.</caption>
		 * function my_submit_form_errors_hook__blockedwords( $submitted_data, $form_data ) {
		 *     // your code here
		 * }
		 * add_action( 'um_submit_form_errors_hook__blockedwords', 'my_submit_form_errors_hook__blockedwords', 10, 2 );
		 */
		do_action( 'um_submit_form_errors_hook__blockedwords', $submitted_data, $form_data );
		/**
		 * Fires for registration and profile forms validation when it has been submitted.
		 *
		 * Internal Ultimate Member callbacks (Priority -> Callback name -> Excerpt):
		 * 10 - `um_submit_form_errors_hook_()` Run field validations.
		 *
		 * @since 1.3.x
		 * @hook um_submit_form_errors_hook_
		 *
		 * @param {array} $submitted_data $_POST Submission array.
		 * @param {array} $form_data      UM form data. Since 2.6.7
		 *
		 * @example <caption>Make any validation action when registration or profile form submitted.</caption>
		 * function my_submit_form_errors_hook( $submitted_data, $form_data ) {
		 *     // your code here
		 * }
		 * add_action( 'um_submit_form_errors_hook_', 'my_submit_form_errors_hook', 10, 2 );
		 */
		do_action( 'um_submit_form_errors_hook_', $submitted_data, $form_data );
	}
}
add_action( 'um_submit_form_errors_hook', 'um_submit_form_errors_hook', 10, 2 );


/**
 * Error processing: Conditions
 * @staticvar int     $counter
 * @param     array   $condition
 * @param     array   $fields
 * @param     array   $submitted_data
 * @param     boolean $reset
 * @return    boolean
 * @throws    Exception
 */
function um_check_conditions_on_submit( $condition, $fields, $submitted_data, $reset = false ) {
	static $counter = 0;
	if ( $reset ) {
		$counter = 0;
	}
	$continue = false;

	list( $visibility, $parent_key, $op, $parent_value ) = $condition;

	if ( ! isset( $submitted_data[ $parent_key ] ) ) {
		$continue = true;
		return $continue;
	}

	if ( ! empty( $fields[ $parent_key ]['conditions'] ) ) {
		foreach ( $fields[ $parent_key ]['conditions'] as $parent_condition ) {
			if ( 64 > $counter++ ) {
				$continue = um_check_conditions_on_submit( $parent_condition, $fields, $submitted_data );
			} else {
				throw new Exception( 'Endless recursion in the function ' . __FUNCTION__, 512 );
			}
			if ( ! empty( $continue ) ) {
				return $continue;
			}
		}
	}

	$cond_value = ( $fields[ $parent_key ]['type'] == 'radio' ) ? $submitted_data[ $parent_key ][0] : $submitted_data[ $parent_key ];

	if ( $visibility == 'hide' ) {
		if ( $op == 'empty' ) {
			if ( empty( $cond_value ) ) {
				$continue = true;
			}
		} elseif ( $op == 'not empty' ) {
			if ( ! empty( $cond_value ) ) {
				$continue = true;
			}
		} elseif ( $op == 'equals to' ) {
			if ( $cond_value == $parent_value ) {
				$continue = true;
			}
		} elseif ( $op == 'not equals' ) {
			if ( $cond_value != $parent_value ) {
				$continue = true;
			}
		} elseif ( $op == 'greater than' ) {
			if ( $cond_value > $parent_value ) {
				$continue = true;
			}
		} elseif ( $op == 'less than' ) {
			if ( $cond_value < $parent_value ) {
				$continue = true;
			}
		} elseif ( $op == 'contains' ) {
			if ( is_string( $cond_value ) && strstr( $cond_value, $parent_value ) ) {
				$continue = true;
			}
			if( is_array( $cond_value ) && in_array( $parent_value, $cond_value ) ) {
				$continue = true;
			}
		}
	} elseif ( $visibility == 'show' ) {
		if ( $op == 'empty' ) {
			if ( ! empty( $cond_value ) ) {
				$continue = true;
			}
		} elseif ( $op == 'not empty' ) {
			if ( empty( $cond_value ) ) {
				$continue = true;
			}
		} elseif ( $op == 'equals to' ) {
			if ( $cond_value != $parent_value ) {
				$continue = true;
			}
		} elseif ( $op == 'not equals' ) {
			if ( $cond_value == $parent_value ) {
				$continue = true;
			}
		} elseif ( $op == 'greater than' ) {
			if ( $cond_value <= $parent_value ) {
				$continue = true;
			}
		} elseif ( $op == 'less than' ) {
			if ( $cond_value >= $parent_value ) {
				$continue = true;
			}
		} elseif ( $op == 'contains' ) {
			if ( is_string( $cond_value ) && ! strstr( $cond_value, $parent_value ) ) {
				$continue = true;
			}
			if( is_array( $cond_value ) && !in_array( $parent_value, $cond_value ) ) {
				$continue = true;
			}
		}
	}

	return $continue;
}

/**
 * Error processing hook : standard
 *
 * @param array $submitted_data
 * @param array $form_data
 */
function um_submit_form_errors_hook_( $submitted_data, $form_data ) {
	$form_id = $form_data['form_id'];
	$mode    = $form_data['mode'];

	$fields = maybe_unserialize( $form_data['custom_fields'] );
	if ( empty( $fields ) || ! is_array( $fields ) ) {
		return;
	}

	$um_profile_photo = um_profile( 'profile_photo' );
	if ( empty( $submitted_data['profile_photo'] ) && empty( $um_profile_photo ) && get_post_meta( $form_id, '_um_profile_photo_required', true ) ) {
		UM()->form()->add_error( 'profile_photo', __( 'Profile Photo is required.', 'ultimate-member' ) );
	}

	$can_edit           = false;
	$current_user_roles = array();
	if ( is_user_logged_in() ) {
		if ( array_key_exists( 'user_id', $submitted_data ) ) {
			$can_edit = UM()->roles()->um_current_user_can( 'edit', $submitted_data['user_id'] );
		}

		um_fetch_user( get_current_user_id() );
		$current_user_roles = um_user( 'roles' );
		um_reset_user();
	}

	$restricted_fields = array();
	if ( 'profile' === $mode ) {
		$restricted_fields = UM()->fields()->get_restricted_fields_for_edit();
	}

	foreach ( $fields as $key => $array ) {

		if ( 'profile' === $mode && is_array( $restricted_fields ) && in_array( $key, $restricted_fields, true ) ) {
			continue;
		}

		$can_view = true;
		if ( isset( $array['public'] ) && 'register' !== $mode ) {

			switch ( $array['public'] ) {
				case '1': // Everyone
					break;
				case '2': // Members
					if ( ! is_user_logged_in() ) {
						$can_view = false;
					}
					break;
				case '-1': // Only visible to profile owner and admins
					if ( ! is_user_logged_in() ) {
						$can_view = false;
					} elseif ( $submitted_data['user_id'] != get_current_user_id() && ! $can_edit ) {
						$can_view = false;
					}
					break;
				case '-2': // Only specific member roles
					if ( ! is_user_logged_in() ) {
						$can_view = false;
					} elseif ( ! empty( $array['roles'] ) && count( array_intersect( $current_user_roles, $array['roles'] ) ) <= 0 ) {
						$can_view = false;
					}
					break;
				case '-3': // Only visible to profile owner and specific roles
					if ( ! is_user_logged_in() ) {
						$can_view = false;
					} elseif ( $submitted_data['user_id'] != get_current_user_id() && ! empty( $array['roles'] ) && count( array_intersect( $current_user_roles, $array['roles'] ) ) <= 0 ) {
						$can_view = false;
					}
					break;
				default:
					$can_view = apply_filters( 'um_can_view_field_custom', $can_view, $array );
					break;
			}
		}

		$can_view = apply_filters( 'um_can_view_field', $can_view, $array );

		if ( ! $can_view ) {
			continue;
		}

		/**
		 * UM hook
		 *
		 * @type filter
		 * @title um_get_custom_field_array
		 * @description Extend custom field data on submit form error
		 * @input_vars
		 * [{"var":"$array","type":"array","desc":"Field data"},
		 * {"var":"$fields","type":"array","desc":"All fields"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage
		 * <?php add_filter( 'um_get_custom_field_array', 'function_name', 10, 2 ); ?>
		 * @example
		 * <?php
		 * add_filter( 'um_get_custom_field_array', 'my_get_custom_field_array', 10, 2 );
		 * function my_get_custom_field_array( $array, $fields ) {
		 *     // your code here
		 *     return $array;
		 * }
		 * ?>
		 */
		$array = apply_filters( 'um_get_custom_field_array', $array, $fields );

		if ( ! empty( $array['conditions'] ) ) {
			try {
				foreach ( $array['conditions'] as $condition ) {
					$continue = um_check_conditions_on_submit( $condition, $fields, $submitted_data, true );
					if ( $continue === true ) {
						continue 2;
					}
				}
			} catch ( Exception $e ) {
				// translators: %s: title.
				UM()->form()->add_error( $key, sprintf( __( '%s - wrong conditions.', 'ultimate-member' ), $array['title'] ) );
				// translators: %s: title.
				$notice = '<div class="um-field-error">' . sprintf( __( '%s - wrong conditions.', 'ultimate-member' ), $array['title'] ) . '</div><!-- ' . $e->getMessage() . ' -->';
				add_action( 'um_after_profile_fields', function() use ( $notice ) {
					echo $notice;
				}, 900 );
			}
		}

		// Validate the Required condition for the Number field. Set the Minimum Number option to allow 0 value.
		if ( isset( $array['type'] ) && 'number' === $array['type'] && ! empty( $array['required'] ) && '' === $submitted_data[ $key ] ) {
			// translators: %s: title.
			UM()->form()->add_error( $key, sprintf( __( '%s is required.', 'ultimate-member' ), $array['title'] ) );
		}

		if ( isset( $array['type'] ) && $array['type'] == 'checkbox' && isset( $array['required'] ) && $array['required'] == 1 && ! isset( $submitted_data[ $key ] ) ) {
			// translators: %s: title.
			UM()->form()->add_error( $key, sprintf( __( '%s is required.', 'ultimate-member' ), $array['title'] ) );
		}

		if ( isset( $array['type'] ) && $array['type'] == 'radio' && isset( $array['required'] ) && $array['required'] == 1 && ! isset( $submitted_data[ $key ] ) && ! in_array( $key, array( 'role_radio', 'role_select' ) ) ) {
			// translators: %s: title.
			UM()->form()->add_error( $key, sprintf( __( '%s is required.', 'ultimate-member'), $array['title'] ) );
		}

		if ( isset( $array['type'] ) && $array['type'] == 'multiselect' && isset( $array['required'] ) && $array['required'] == 1 && ! isset( $submitted_data[ $key ] ) && ! in_array( $key, array( 'role_radio', 'role_select' ) ) ) {
			// translators: %s: title.
			UM()->form()->add_error( $key, sprintf( __( '%s is required.', 'ultimate-member' ), $array['title'] ) );
		}

		/* WordPress uses the default user role if the role wasn't chosen in the registration form. That is why we should use submitted data to validate fields Roles (Radio) and Roles (Dropdown). */
		if ( in_array( $key, array( 'role_radio', 'role_select' ) ) && isset( $array['required'] ) && $array['required'] == 1 && empty( UM()->form()->post_form['submitted']['role'] ) ) {
			UM()->form()->add_error( 'role', __( 'Please specify account type.', 'ultimate-member' ) );
			UM()->form()->post_form[ $key ] = '';
		}

		/**
		 * UM hook
		 *
		 * @type action
		 * @title um_add_error_on_form_submit_validation
		 * @description Submit form validation
		 * @input_vars
		 * [{"var":"$field","type":"array","desc":"Field Data"},
		 * {"var":"$key","type":"string","desc":"Field Key"},
		 * {"var":"$args","type":"array","desc":"Form Arguments"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage add_action( 'um_add_error_on_form_submit_validation', 'function_name', 10, 3 );
		 * @example
		 * <?php
		 * add_action( 'um_add_error_on_form_submit_validation', 'my_add_error_on_form_submit_validation', 10, 3 );
		 * function my_add_error_on_form_submit_validation( $field, $key, $args ) {
		 *     // your code here
		 * }
		 * ?>
		 */
		do_action( 'um_add_error_on_form_submit_validation', $array, $key, $submitted_data );

		if ( ! empty( $array['required'] ) ) {
			if ( ! isset( $submitted_data[ $key ] ) || $submitted_data[ $key ] == '' || $submitted_data[ $key ] == 'empty_file' ) {
				if ( empty( $array['label'] ) ) {
					UM()->form()->add_error( $key, __( 'This field is required', 'ultimate-member' ) );
				} else {
					// translators: %s: title.
					UM()->form()->add_error( $key, sprintf( __( '%s is required', 'ultimate-member' ), $array['label'] ) );
				}
			}
		}

		if ( ! isset( $submitted_data[ $key ] ) ) {
			continue;
		}

		if ( isset( $array['max_words'] ) && $array['max_words'] > 0 ) {
			if ( ! empty( $array['html'] ) ) {
				// Count words without html tags when HTML is enabled.
				$text_value = wp_strip_all_tags( $submitted_data[ $key ] );
			} else {
				$text_value = $submitted_data[ $key ];
			}

			if ( str_word_count( $text_value, 0, '0123456789éèàôù' ) > $array['max_words'] ) {
				// translators: %s: max words.
				UM()->form()->add_error( $key, sprintf( __( 'You are only allowed to enter a maximum of %s words', 'ultimate-member' ), $array['max_words'] ) );
			}
		}

		if ( isset( $array['min_chars'] ) && $array['min_chars'] > 0 ) {
			if ( $submitted_data[ $key ] && mb_strlen( $submitted_data[ $key ] ) < $array['min_chars'] ) {
				if ( empty( $array['label'] ) ) {
					// translators: %s: min chars.
					UM()->form()->add_error( $key, sprintf( __( 'This field must contain at least %s characters', 'ultimate-member' ), $array['min_chars'] ) );
				} else {
					// translators: %1$s is a label; %2$s is a min chars.
					UM()->form()->add_error( $key, sprintf( __( 'Your %1$s must contain at least %2$s characters', 'ultimate-member' ), $array['label'], $array['min_chars'] ) );
				}
			}
		}

		if ( ! empty( $array['max_chars'] ) && UM()->profile()->get_show_bio_key( $submitted_data ) !== $key ) {
			if ( ! empty( $array['html'] ) ) {
				// Count words without html tags when HTML is enabled.
				$text_value = wp_strip_all_tags( $submitted_data[ $key ] );
			} else {
				$text_value = $submitted_data[ $key ];
			}

			if ( ! empty( $text_value ) && mb_strlen( $text_value ) > $array['max_chars'] ) {
				if ( empty( $array['label'] ) ) {
					// translators: %s: max chars.
					UM()->form()->add_error( $key, sprintf( __( 'This field must contain less than %s characters', 'ultimate-member' ), $array['max_chars'] ) );
				} else {
					// translators: %1$s is a label; %2$s is a max chars.
					UM()->form()->add_error( $key, sprintf( __( 'Your %1$s must contain less than %2$s characters', 'ultimate-member' ), $array['label'], $array['max_chars'] ) );
				}
			}
		}

		if ( isset( $array['type'] ) && 'textarea' === $array['type'] && UM()->profile()->get_show_bio_key( $submitted_data ) !== $key ) {
			if ( empty( $array['html'] ) ) {
				if ( wp_strip_all_tags( $submitted_data[ $key ] ) !== trim( $submitted_data[ $key ] ) ) {
					UM()->form()->add_error( $key, __( 'You can not use HTML tags here', 'ultimate-member' ) );
				}
			}
		}

		if ( isset( $array['force_good_pass'] ) && $array['force_good_pass'] && ! empty( $submitted_data['user_password'] ) ) {
			if ( isset( $submitted_data['user_login'] ) && strpos( strtolower( $submitted_data['user_login'] ), strtolower( $submitted_data['user_password'] )  ) > -1 ) {
				UM()->form()->add_error( 'user_password', __( 'Your password cannot contain the part of your username', 'ultimate-member' ));
			}

			if ( isset( $submitted_data['user_email'] ) && strpos( strtolower( $submitted_data['user_email'] ), strtolower( $submitted_data['user_password'] )  ) > -1 ) {
				UM()->form()->add_error( 'user_password', __( 'Your password cannot contain the part of your email address', 'ultimate-member' ));
			}

			if ( ! UM()->validation()->strong_pass( $submitted_data[ $key ] ) ) {
				UM()->form()->add_error( $key, __( 'Your password must contain at least one lowercase letter, one capital letter and one number', 'ultimate-member' ) );
			}
		}

		if ( ! empty( $array['force_confirm_pass'] ) ) {
			if ( ! array_key_exists( 'confirm_' . $key, $submitted_data ) && ! UM()->form()->has_error( $key ) ) {
				UM()->form()->add_error( 'confirm_' . $key, __( 'Please confirm your password', 'ultimate-member' ) );
			} else {
				if ( '' === $submitted_data[ 'confirm_' . $key ] && ! UM()->form()->has_error( $key ) ) {
					UM()->form()->add_error( 'confirm_' . $key, __( 'Please confirm your password', 'ultimate-member' ) );
				}
				if ( $submitted_data[ 'confirm_' . $key ] !== $submitted_data[ $key ] && ! UM()->form()->has_error( $key ) ) {
					UM()->form()->add_error( 'confirm_' . $key, __( 'Your passwords do not match', 'ultimate-member' ) );
				}
			}
		}

		if ( isset( $array['min_selections'] ) && $array['min_selections'] > 0 ) {
			if ( ( ! isset( $submitted_data[ $key ] ) ) || ( isset( $submitted_data[ $key ] ) && is_array( $submitted_data[ $key ] ) && count( $submitted_data[ $key ] ) < $array['min_selections'] ) ) {
				// translators: %s: min selections.
				UM()->form()->add_error( $key, sprintf( __( 'Please select at least %s choices', 'ultimate-member' ), $array['min_selections'] ) );
			}
		}

		if ( isset( $array['max_selections'] ) && $array['max_selections'] > 0 ) {
			if ( isset( $submitted_data[ $key ] ) && is_array( $submitted_data[ $key ] ) && count( $submitted_data[ $key ] ) > $array['max_selections'] ) {
				// translators: %s: max selections.
				UM()->form()->add_error( $key, sprintf( __( 'You can only select up to %s choices', 'ultimate-member' ), $array['max_selections'] ) );
			}
		}

		if ( isset( $array['min'] ) && is_numeric( $submitted_data[ $key ] ) ) {
			if ( isset( $submitted_data[ $key ] )  && $submitted_data[ $key ] < $array['min'] ) {
				// translators: %s: min limit.
				UM()->form()->add_error( $key, sprintf( __( 'Minimum number limit is %s', 'ultimate-member' ), $array['min'] ) );
			}
		}

		if ( isset( $array['max'] ) && is_numeric( $submitted_data[ $key ] )  ) {
			if ( isset( $submitted_data[ $key ] ) && $submitted_data[ $key ] > $array['max'] ) {
				// translators: %s: max limit.
				UM()->form()->add_error( $key, sprintf( __( 'Maximum number limit is %s', 'ultimate-member' ), $array['max'] ) );
			}
		}

		$description_key = UM()->profile()->get_show_bio_key( $form_data );
		if ( isset( $form_data['mode'] ) && 'profile' === $form_data['mode'] && $description_key === $key ) {
			$show_bio       = false;
			$bio_html       = false;
			$global_setting = UM()->options()->get( 'profile_show_html_bio' );
			if ( ! empty( $form_data['use_custom_settings'] ) ) {
				if ( ! empty( $form_data['show_bio'] ) ) {
					$show_bio = true;
					$bio_html = ! empty( $global_setting );
				}
			} else {
				$global_show_bio = UM()->options()->get( 'profile_show_bio' );
				if ( ! empty( $global_show_bio ) ) {
					$show_bio = true;
					$bio_html = ! empty( $global_setting );
				}
			}

			if ( $show_bio ) {
				$max_chars = UM()->options()->get( 'profile_bio_maxchars' );
			}
			$field_exists = false;
			if ( ! empty( $form_data['custom_fields'] ) ) {
				$custom_fields = maybe_unserialize( $form_data['custom_fields'] );
				if ( array_key_exists( $description_key, $custom_fields ) ) {
					$field_exists = true;
					if ( ! empty( $array['max_chars'] ) ) {
						$max_chars = $array['max_chars'];
					}

					if ( $show_bio ) {
						if ( ! empty( $array['html'] ) && $bio_html ) {
							$description_value = wp_strip_all_tags( $submitted_data[ $description_key ] );
						} else {
							$description_value = $submitted_data[ $description_key ];
						}
					} else {
						if ( ! empty( $array['html'] ) ) {
							$description_value = wp_strip_all_tags( $submitted_data[ $description_key ] );
						} else {
							$description_value = $submitted_data[ $description_key ];
						}
					}
				}
			}

			if ( ! $field_exists && $show_bio ) {
				if ( $bio_html ) {
					$description_value = wp_strip_all_tags( $submitted_data[ $description_key ] );
				} else {
					$description_value = $submitted_data[ $description_key ];
				}
			}

			if ( ! empty( $description_value ) && ! empty( $max_chars ) && mb_strlen( str_replace( array( "\r\n", "\n", "\r\t", "\t" ), ' ', $description_value ) ) > $max_chars ) {
				// translators: %s: max chars.
				UM()->form()->add_error( $description_key, sprintf( __( 'Your user description must contain less than %s characters', 'ultimate-member' ), $max_chars ) );
			}
		}

		if ( empty( $array['validate'] ) ) {
			continue;
		}

		switch ( $array['validate'] ) {

			case 'custom':
				$custom = $array['custom_validate'];
				/**
				 * UM hook
				 *
				 * @type action
				 * @title um_custom_field_validation_{$custom}
				 * @description Submit form validation for custom field
				 * @input_vars
				 * [{"var":"$key","type":"string","desc":"Field Key"},
				 * {"var":"$field","type":"array","desc":"Field Data"},
				 * {"var":"$args","type":"array","desc":"Form Arguments"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_action( 'um_custom_field_validation_{$custom}', 'function_name', 10, 3 );
				 * @example
				 * <?php
				 * add_action( 'um_custom_field_validation_{$custom}', 'my_custom_field_validation', 10, 3 );
				 * function my_custom_field_validation( $key, $field, $args ) {
				 *     // your code here
				 * }
				 * ?>
				 */
				do_action( "um_custom_field_validation_{$custom}", $key, $array, $submitted_data );
				break;

			case 'numeric':
				if ( $submitted_data[ $key ] && ! is_numeric( $submitted_data[ $key ] ) ) {
					UM()->form()->add_error( $key, __( 'Please enter numbers only in this field', 'ultimate-member' ) );
				}
				break;

			case 'phone_number':
				if ( ! UM()->validation()->is_phone_number( $submitted_data[ $key ] ) ) {
					UM()->form()->add_error( $key, __( 'Please enter a valid phone number', 'ultimate-member' ) );
				}
				break;

			case 'youtube_url':
				if ( ! UM()->validation()->is_url( $submitted_data[ $key ], 'youtube.com' ) && ! UM()->validation()->is_url( $submitted_data[ $key ], 'youtu.be' ) ) {
					// translators: %s: label.
					UM()->form()->add_error( $key, sprintf( __( 'Please enter a valid %s username or profile URL', 'ultimate-member' ), $array['label'] ) );
				}
				break;

			case 'youtube_video':
				if ( ! UM()->validation()->is_url( $submitted_data[ $key ] ) || false === um_youtube_id_from_url( $submitted_data[ $key ] ) ) {
					// translators: %s: label.
					UM()->form()->add_error( $key, sprintf( __( 'Please enter a valid %s URL', 'ultimate-member' ), $array['label'] ) );
				}
				break;

			case 'spotify_url':
				if ( ! UM()->validation()->is_url( $submitted_data[ $key ], 'open.spotify.com' ) ) {
					// translators: %s: label.
					UM()->form()->add_error( $key, sprintf( __( 'Please enter a valid %s URL', 'ultimate-member' ), $array['label'] ) );
				}
				break;

			case 'telegram_url':
				if ( ! UM()->validation()->is_url( $submitted_data[ $key ], 't.me' ) ) {
					// translators: %s: label.
					UM()->form()->add_error( $key, sprintf( __( 'Please enter a valid %s username or profile URL', 'ultimate-member' ), $array['label'] ) );
				}
				break;

			case 'soundcloud_url':
				if ( ! UM()->validation()->is_url( $submitted_data[ $key ], 'soundcloud.com' ) ) {
					// translators: %s: label.
					UM()->form()->add_error( $key, sprintf( __( 'Please enter a valid %s username or profile URL', 'ultimate-member' ), $array['label'] ) );
				}
				break;

			case 'facebook_url':
				if ( ! UM()->validation()->is_url( $submitted_data[ $key ], 'facebook.com' ) ) {
					// translators: %s: label.
					UM()->form()->add_error( $key, sprintf( __( 'Please enter a valid %s username or profile URL', 'ultimate-member' ), $array['label'] ) );
				}
				break;

			case 'twitter_url':
				if ( ! UM()->validation()->is_url( $submitted_data[ $key ], 'twitter.com' ) ) {
					// translators: %s: label.
					UM()->form()->add_error( $key, sprintf( __( 'Please enter a valid %s username or profile URL', 'ultimate-member' ), $array['label'] ) );
				}
				break;

			case 'instagram_url':
				if ( ! UM()->validation()->is_url( $submitted_data[ $key ], 'instagram.com' ) ) {
					// translators: %s: label.
					UM()->form()->add_error( $key, sprintf( __( 'Please enter a valid %s profile URL', 'ultimate-member' ), $array['label'] ) );
				}
				break;

			case 'linkedin_url':
				if ( ! UM()->validation()->is_url( $submitted_data[ $key ], 'linkedin.com' ) ) {
					// translators: %s: label.
					UM()->form()->add_error( $key, sprintf( __( 'Please enter a valid %s username or profile URL', 'ultimate-member' ), $array['label'] ) );
				}
				break;

			case 'discord':
				if ( ! UM()->validation()->is_discord_id( $submitted_data[ $key ] ) ) {
					UM()->form()->add_error( $key, __( 'Please enter a valid Discord ID', 'ultimate-member' ) );
				}
				break;

			case 'tiktok_url':
				if ( ! UM()->validation()->is_url( $submitted_data[ $key ], 'tiktok.com' ) ) {
					// translators: %s: label.
					UM()->form()->add_error( $key, sprintf( __( 'Please enter a valid %s profile URL', 'ultimate-member' ), $array['label'] ) );
				}
				break;

			case 'twitch_url':
				if ( ! UM()->validation()->is_url( $submitted_data[ $key ], 'twitch.tv' ) ) {
					// translators: %s: label.
					UM()->form()->add_error( $key, sprintf( __( 'Please enter a valid %s profile URL', 'ultimate-member' ), $array['label'] ) );
				}
				break;

			case 'reddit_url':
				if ( ! UM()->validation()->is_url( $submitted_data[ $key ], 'reddit.com' ) ) {
					// translators: %s: label.
					UM()->form()->add_error( $key, sprintf( __( 'Please enter a valid %s profile URL', 'ultimate-member' ), $array['label'] ) );
				}
				break;

			case 'url':
				if ( ! UM()->validation()->is_url( $submitted_data[ $key ] ) ) {
					UM()->form()->add_error( $key, __( 'Please enter a valid URL', 'ultimate-member' ) );
				}
				break;

			case 'unique_username':
				if ( '' === $submitted_data[ $key ] ) {
					UM()->form()->add_error( $key, __( 'You must provide a username', 'ultimate-member' ) );
				} elseif ( 'register' === $mode && username_exists( sanitize_user( $submitted_data[ $key ] ) ) ) {
					UM()->form()->add_error( $key, __( 'The username you entered is incorrect', 'ultimate-member' ) );
				} elseif ( is_email( $submitted_data[ $key ] ) ) {
					UM()->form()->add_error( $key, __( 'Username cannot be an email', 'ultimate-member' ) );
				} elseif ( ! UM()->validation()->safe_username( $submitted_data[ $key ] ) ) {
					UM()->form()->add_error( $key, __( 'Your username contains invalid characters', 'ultimate-member' ) );
				}
				break;

			case 'unique_username_or_email':
				if ( '' === $submitted_data[ $key ] ) {
					UM()->form()->add_error( $key, __( 'You must provide a username or email', 'ultimate-member' ) );
				} elseif ( 'register' === $mode && username_exists( sanitize_user( $submitted_data[ $key ] ) ) ) {
					UM()->form()->add_error( $key, __( 'The username you entered is incorrect', 'ultimate-member' ) );
				} elseif ( 'register' === $mode && email_exists( $submitted_data[ $key ] ) ) {
					UM()->form()->add_error( $key, __( 'The email you entered is incorrect', 'ultimate-member' ) );
				} elseif ( ! UM()->validation()->safe_username( $submitted_data[ $key ] ) ) {
					UM()->form()->add_error( $key, __( 'Your username contains invalid characters', 'ultimate-member' ) );
				}
				break;

			case 'unique_email':
				$submitted_data[ $key ] = trim( $submitted_data[ $key ] );

				if ( 'user_email' === $key ) {
					if ( ! isset( $submitted_data['user_id'] ) ) {
						$submitted_data['user_id'] = um_get_requested_user();
					}

					$email_exists = email_exists( $submitted_data[ $key ] );

					if ( '' === $submitted_data[ $key ] ) {
						UM()->form()->add_error( $key, __( 'You must provide your email', 'ultimate-member' ) );
					} elseif ( 'register' === $mode && $email_exists ) {
						UM()->form()->add_error( $key, __( 'The email you entered is incorrect', 'ultimate-member' ) );
					} elseif ( 'profile' === $mode && $email_exists && absint( $email_exists ) !== absint( $submitted_data['user_id'] ) ) {
						UM()->form()->add_error( $key, __( 'The email you entered is incorrect', 'ultimate-member' ) );
					} elseif ( ! is_email( $submitted_data[ $key ] ) ) {
						UM()->form()->add_error( $key, __( 'The email you entered is incorrect', 'ultimate-member' ) );
					} elseif ( ! UM()->validation()->safe_username( $submitted_data[ $key ] ) ) {
						UM()->form()->add_error( $key, __( 'Your email contains invalid characters', 'ultimate-member' ) );
					}
				} else {

					if ( '' !== $submitted_data[ $key ] && ! is_email( $submitted_data[ $key ] ) ) {
						UM()->form()->add_error( $key, __( 'The email you entered is incorrect', 'ultimate-member' ) );
					} elseif ( '' !== $submitted_data[ $key ] && email_exists( $submitted_data[ $key ] ) ) {
						UM()->form()->add_error( $key, __( 'The email you entered is incorrect', 'ultimate-member' ) );
					} elseif ( '' !== $submitted_data[ $key ] ) {

						$users = get_users( 'meta_value=' . $submitted_data[ $key ] );

						foreach ( $users as $user ) {
							if ( $user->ID !== $submitted_data['user_id'] ) {
								UM()->form()->add_error( $key, __( 'The email you entered is incorrect', 'ultimate-member' ) );
							}
						}
					}
				}
				break;

			case 'is_email':
				$submitted_data[ $key ] = trim( $submitted_data[ $key ] );
				if ( '' !== $submitted_data[ $key ] && ! is_email( $submitted_data[ $key ] ) ) {
					UM()->form()->add_error( $key, __( 'This is not a valid email', 'ultimate-member' ) );
				}
				break;

			case 'unique_value':
				if ( '' !== $submitted_data[ $key ] ) {

					if ( ! isset( $submitted_data['user_id'] ) ) {
						$submitted_data['user_id'] = um_get_requested_user();
					}

					$args_unique_meta = array(
						'meta_key'   => $key,
						'meta_value' => $submitted_data[ $key ],
						'compare'    => '=',
						'exclude'    => array( $submitted_data['user_id'] ),
					);

					$meta_key_exists = get_users( $args_unique_meta );

					if ( $meta_key_exists ) {
						UM()->form()->add_error( $key, __( 'You must provide a unique value', 'ultimate-member' ) );
					}
				}
				break;

			case 'alphabetic':
				if ( '' !== $submitted_data[ $key ] ) {
					if ( ! preg_match( '/^\p{L}+$/u', str_replace( ' ', '', $submitted_data[ $key ] ) ) ) {
						UM()->form()->add_error( $key, __( 'You must provide alphabetic letters', 'ultimate-member' ) );
					}
				}
				break;

			case 'alpha_numeric':
				if ( '' !== $submitted_data[ $key ] ) {
					if ( ! preg_match( '/^[\p{L}0-9\s]+$/u', str_replace( ' ', '', $submitted_data[ $key ] ) ) ) {
						UM()->form()->add_error( $key, __( 'You must provide alphabetic letters or numbers', 'ultimate-member' ) );
					}
				}
				break;

			case 'lowercase':
				if ( '' !== $submitted_data[ $key ] ) {
					if ( ! ctype_lower( str_replace( ' ', '', $submitted_data[ $key ] ) ) ) {
						UM()->form()->add_error( $key, __( 'You must provide lowercase letters.', 'ultimate-member' ) );
					}
				}
				break;

			case 'english':
				if ( '' !== $submitted_data[ $key ] ) {
					if ( ! preg_match( '/^[a-zA-Z]*$/u', str_replace( ' ', '', $submitted_data[ $key ] ) ) ) {
						UM()->form()->add_error( $key, __( 'You must provide English letters.', 'ultimate-member' ) );
					}
				}

				break;

		}
	} // end if ( isset in args array )

	// Description in header
	if ( isset( $form_data['mode'] ) && 'profile' === $form_data['mode'] ) {
		$description_key = UM()->profile()->get_show_bio_key( $form_data );
		if ( ! UM()->form()->has_error( $description_key ) ) {
			if ( ! empty( $submitted_data[ $description_key ] ) ) {
				$field_exists = false;
				if ( ! empty( $form_data['custom_fields'] ) ) {
					$custom_fields = maybe_unserialize( $form_data['custom_fields'] );
					if ( array_key_exists( $description_key, $custom_fields ) ) {
						$field_exists = true;
					}
				}

				if ( ! $field_exists ) {
					$show_bio       = false;
					$bio_html       = false;
					$global_setting = UM()->options()->get( 'profile_show_html_bio' );
					if ( ! empty( $form_data['use_custom_settings'] ) ) {
						if ( ! empty( $form_data['show_bio'] ) ) {
							$show_bio = true;
							$bio_html = ! empty( $global_setting );
						}
					} else {
						$global_show_bio = UM()->options()->get( 'profile_show_bio' );
						if ( ! empty( $global_show_bio ) ) {
							$show_bio = true;
							$bio_html = ! empty( $global_setting );
						}
					}

					if ( $show_bio ) {
						$max_chars = UM()->options()->get( 'profile_bio_maxchars' );
						if ( $bio_html ) {
							$description_value = wp_strip_all_tags( $submitted_data[ $description_key ] );
						} else {
							$description_value = $submitted_data[ $description_key ];
						}
					}

					if ( ! empty( $description_value ) && ! empty( $max_chars ) && mb_strlen( str_replace( array( "\r\n", "\n", "\r\t", "\t" ), ' ', $description_value ) ) > $max_chars ) {
						// translators: %s: max chars.
						UM()->form()->add_error( $description_key, sprintf( __( 'Your user description must contain less than %s characters', 'ultimate-member' ), $max_chars ) );
					}
				}
			}
		}
	}
}
add_action( 'um_submit_form_errors_hook_', 'um_submit_form_errors_hook_', 10, 2 );


/**
 * @param string $url
 *
 * @return string
 */
function um_invalid_nonce_redirect_url( $url ) {
	$url = add_query_arg(
		array(
			'um-hash' => substr( md5( rand() ), 0, 6 ),
		),
		remove_query_arg( 'um-hash', $url )
	);

	return $url;
}
add_filter( 'um_login_invalid_nonce_redirect_url', 'um_invalid_nonce_redirect_url', 10, 1 );
add_filter( 'um_register_invalid_nonce_redirect_url', 'um_invalid_nonce_redirect_url', 10, 1 );
