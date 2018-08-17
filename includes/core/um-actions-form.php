<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Error handling: blocked IPs
 *
 * @param $args
 */
function um_prevent_submit_form_blockedips( $args ) {
	if( $args['mode'] == 'login' ) return true; //check blocked values for login on 'authenticate' filter

	if( !UM()->form()->validate_blocked_ips() ) {
		UM()->form()->add_error( 'blocked_ip', UM()->form()->get_notice_by_code( 'blocked_ip' ) );
	}
}
add_action( 'um_submit_form_errors_hook', 'um_prevent_submit_form_blockedips', 10 );


/**
 * Error handling: blocked emails
 *
 * @param $args
 */
function um_prevent_submit_form_blockedemails( $args ) {
	if( $args['mode'] == 'login' ) return true; //check blocked values for login on 'authenticate' filter

	if ( $emails = UM()->options()->get( 'blocked_emails' ) ) {
		$emails = array_map( "rtrim", explode( "\n", $emails ) );

		$check_values = array();

		if ( isset( $args['username'] ) ) {
			if ( is_email( $args['username'] ) ) {
				$check_values[] = $args['username'];
			} else {
				$user = get_user_by('login', $args['username'] );
				if( isset( $user->user_email ) ) {
					$check_values[] = $user->user_email;
				}
			}
		} else if ( isset( $args['user_email'] ) ) {
			$check_values[] = $args['user_email'];
		} else if ( isset( $args['user_login'] ) ) {
			$user = get_user_by('login', $args['user_login'] );
			if( isset( $user->user_email ) ) {
				$check_values[] = $user->user_email;
			}
		} else {
			return true;
		}

		foreach( $check_values as $check_email ) {
			$domain       = explode( '@', $check_email );
			$check_domain = str_replace( $domain[0], '*', $check_email );

			if ( in_array( $check_email, $emails ) ) {
				UM()->form()->add_error( 'blocked_email', UM()->form()->get_notice_by_code( 'blocked_email' ) );
			}

			if ( in_array( $check_domain, $emails ) ) {
				UM()->form()->add_error( 'blocked_domain', UM()->form()->get_notice_by_code( 'blocked_domain' ) );
			}
		}
	}
	return true;
}
add_action( 'um_submit_form_errors_hook', 'um_prevent_submit_form_blockedemails', 20 );

/**
 * Error handling: blocked words during sign up
 *
 * @param $args
 */
function um_prevent_submit_form_blockedwords( $args ) {
	if( $args['mode'] == 'login' ) return true;

	$fields = unserialize( $args['custom_fields'] );

	$words = UM()->options()->get('blocked_words');
	if ( $words == '' ) return true;

	$words = array_map("rtrim", explode("\n", $words));
	if ( ! empty( $fields ) && is_array( $fields ) ) {
		foreach ( $fields as $key => $array ) {
			if ( isset($array['validate']) && in_array( $array['validate'], array('unique_username','unique_email','unique_username_or_email') ) ) {
				if ( ! UM()->form()->has_error( $key ) && isset( $args[$key] ) && in_array( $args[$key], $words ) ) {
					UM()->form()->add_error( $key,  __('You are not allowed to use this word as your username.','ultimate-member') );
				}
			}
		}
	}
	return true;
}
add_action( 'um_submit_form_errors_hook', 'um_prevent_submit_form_blockedwords', 30 );


/**
 * Error processing hook : standard
 *
 * @param $args
 */
function um_submit_form_validate_fields( $args ) {
	$form_id = $args['form_id'];
	$mode = $args['mode'];
	if( $mode == 'login' ) return true;

	$fields = unserialize( $args['custom_fields'] );
	$um_profile_photo = um_profile('profile_photo');

	if ( get_post_meta( $form_id, '_um_profile_photo_required', true ) && ( empty( $args['profile_photo'] ) && empty( $um_profile_photo ) ) ) {
		UM()->form()->add_error('profile_photo', sprintf(__('%s is required.','ultimate-member'), 'Profile Photo' ) );
	}

	if ( ! empty( $fields ) ) {
		foreach ( $fields as $key => $array ) {

			if ( isset( $array['public']  ) && -2 == $array['public'] && ! empty( $array['roles'] ) && is_user_logged_in() ) {
				$current_user_roles = um_user( 'roles' );
				if ( empty( $current_user_roles ) || count( array_intersect( $current_user_roles, $array['roles'] ) ) <= 0 ) {
					continue;
				}
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
				foreach ( $array['conditions'] as $condition ) {
					list( $visibility, $parent_key, $op, $parent_value ) = $condition;

					if ( ! isset( $args[ $parent_key ] ) ) {
						continue;
					}

					$cond_value = ( $fields[ $parent_key ]['type'] == 'radio' ) ? $args[ $parent_key ][0] : $args[ $parent_key ];

					if ( $visibility == 'hide' ) {
						if ( $op == 'empty' ) {
							if ( empty( $cond_value ) ) {
								continue 2;
							}
						} elseif ( $op == 'not empty' ) {
							if ( ! empty( $cond_value ) ) {
								continue 2;
							}
						} elseif ( $op == 'equals to' ) {
							if ( $cond_value == $parent_value ) {
								continue 2;
							}
						} elseif ( $op == 'not equals' ) {
							if ( $cond_value != $parent_value ) {
								continue 2;
							}
						} elseif ( $op == 'greater than' ) {
							if ( $cond_value > $parent_value ) {
								continue 2;
							}
						} elseif ( $op == 'less than' ) {
							if ( $cond_value < $parent_value ) {
								continue 2;
							}
						} elseif ( $op == 'contains' ) {
							if ( strstr( $cond_value, $parent_value ) ) {
								continue 2;
							}
						}
					} elseif ( $visibility == 'show' ) {
						if ( $op == 'empty' ) {
							if ( ! empty( $cond_value ) ) {
								continue 2;
							}
						} elseif ( $op == 'not empty' ) {
							if ( empty( $cond_value ) ) {
								continue 2;
							}
						} elseif ( $op == 'equals to' ) {
							if ( $cond_value != $parent_value ) {
								continue 2;
							}
						} elseif ( $op == 'not equals' ) {
							if ( $cond_value == $parent_value ) {
								continue 2;
							}
						} elseif ( $op == 'greater than' ) {
							if ( $cond_value <= $parent_value ) {
								continue 2;
							}
						} elseif ( $op == 'less than' ) {
							if ( $cond_value >= $parent_value ) {
								continue 2;
							}
						} elseif ( $op == 'contains' ) {
							if ( ! strstr( $cond_value, $parent_value ) ) {
								continue 2;
							}
						}
					}
				}
			}

			if ( isset( $array['type'] ) && $array['type'] == 'checkbox' && isset( $array['required'] ) && $array['required'] == 1 && !isset( $args[$key] ) ) {
				UM()->form()->add_error($key, sprintf(__('%s is required.','ultimate-member'), $array['title'] ) );
			}

			if ( isset( $array['type'] ) && $array['type'] == 'radio' && isset( $array['required'] ) && $array['required'] == 1 && !isset( $args[$key] ) && !in_array($key, array('role_radio','role_select') ) ) {
				UM()->form()->add_error($key, sprintf(__('%s is required.','ultimate-member'), $array['title'] ) );
			}

			if ( isset( $array['type'] ) && $array['type'] == 'multiselect' && isset( $array['required'] ) && $array['required'] == 1 && !isset( $args[$key] ) && !in_array($key, array('role_radio','role_select') ) ) {
				UM()->form()->add_error($key, sprintf(__('%s is required.','ultimate-member'), $array['title'] ) );
			}

			if ( $key == 'role_select' || $key == 'role_radio' ) {
				if ( isset( $array['required'] ) && $array['required'] == 1 && ( !isset( $args['role'] ) || empty( $args['role'] ) ) ) {
					UM()->form()->add_error('role', __('Please specify account type.','ultimate-member') );
				}
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
			do_action( 'um_add_error_on_form_submit_validation', $array, $key, $args );

			if ( isset( $args[$key] ) ) {

				if ( isset( $array['required'] ) && $array['required'] == 1 ) {
					if ( ! isset( $args[$key] ) || $args[$key] == '' || $args[$key] == 'empty_file') {
						if( empty( $array['label'] ) ) {
							UM()->form()->add_error($key, __('This field is required','ultimate-member') );
						} else {
							UM()->form()->add_error($key, sprintf( __('%s is required','ultimate-member'), $array['label'] ) );
						}
					}
				}

				if ( isset( $array['max_words'] ) && $array['max_words'] > 0 ) {
					if ( str_word_count( $args[$key] ) > $array['max_words'] ) {
						UM()->form()->add_error($key, sprintf(__('You are only allowed to enter a maximum of %s words','ultimate-member'), $array['max_words']) );
					}
				}

				if ( isset( $array['min_chars'] ) && $array['min_chars'] > 0 ) {
					if ( $args[$key] && strlen( utf8_decode( $args[$key] ) ) < $array['min_chars'] ) {
						UM()->form()->add_error($key, sprintf(__('Your %s must contain at least %s characters','ultimate-member'), $array['label'], $array['min_chars']) );
					}
				}

				if ( isset( $array['max_chars'] ) && $array['max_chars'] > 0 ) {
					if ( $args[$key] && strlen( utf8_decode( $args[$key] ) ) > $array['max_chars'] ) {
						UM()->form()->add_error($key, sprintf(__('Your %s must contain less than %s characters','ultimate-member'), $array['label'], $array['max_chars']) );
					}
				}
                     
				$profile_show_html_bio = UM()->options()->get('profile_show_html_bio');
					
				if(  $profile_show_html_bio == 1 && $key !== "description" ){
					if ( isset( $array['html'] ) && $array['html'] == 0 ) {
						if ( wp_strip_all_tags( $args[$key] ) != trim( $args[$key] ) ) {
							UM()->form()->add_error($key, __('You can not use HTML tags here','ultimate-member') );
						}
					}
				}

				if ( isset( $array['force_good_pass'] ) && $array['force_good_pass'] == 1 ) {
					if ( ! UM()->validation()->strong_pass( $args[$key] ) ) {
						UM()->form()->add_error($key, __('Your password must contain at least one lowercase letter, one capital letter and one number','ultimate-member') );
					}
				}

				if ( isset( $array['force_confirm_pass'] ) && $array['force_confirm_pass'] == 1 ) {
					if ( $args[ 'confirm_' . $key] == '' && ! UM()->form()->has_error($key) ) {
						UM()->form()->add_error( 'confirm_' . $key , __('Please confirm your password','ultimate-member') );
					}
					if ( $args[ 'confirm_' . $key] != $args[$key] && !UM()->form()->has_error($key) ) {
						UM()->form()->add_error( 'confirm_' . $key , __('Your passwords do not match','ultimate-member') );
					}
				}

				if ( isset( $array['min_selections'] ) && $array['min_selections'] > 0 ) {
					if ( ( !isset($args[$key]) ) || ( isset( $args[$key] ) && is_array($args[$key]) && count( $args[$key] ) < $array['min_selections'] ) ) {
						UM()->form()->add_error($key, sprintf(__('Please select at least %s choices','ultimate-member'), $array['min_selections'] ) );
					}
				}

				if ( isset( $array['max_selections'] ) && $array['max_selections'] > 0 ) {
					if ( isset( $args[$key] ) && is_array($args[$key]) && count( $args[$key] ) > $array['max_selections'] ) {
						UM()->form()->add_error($key, sprintf(__('You can only select up to %s choices','ultimate-member'), $array['max_selections'] ) );
					}
				}

				if ( isset( $array['min'] ) && is_numeric( $args[ $key ] ) ) {
					if ( isset( $args[ $key ] )  && $args[ $key ] < $array['min'] ) {
						UM()->form()->add_error( $key, sprintf(__('Minimum number limit is %s','ultimate-member'), $array['min'] ) );
					}
				}

				if ( isset( $array['max'] ) && is_numeric( $args[ $key ] )  ) {
					if ( isset( $args[ $key ] ) && $args[ $key ] > $array['max'] ) {
						UM()->form()->add_error( $key, sprintf(__('Maximum number limit is %s','ultimate-member'), $array['max'] ) );
					}
				}

				if ( isset( $array['validate'] ) && !empty( $array['validate'] ) ) {

					switch( $array['validate'] ) {

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
							do_action( "um_custom_field_validation_{$custom}", $key, $array, $args );
							break;

						case 'numeric':
							if ( $args[$key] && !is_numeric( $args[$key] ) ) {
								UM()->form()->add_error($key, __('Please enter numbers only in this field','ultimate-member') );
							}
							break;

						case 'phone_number':
							if ( ! UM()->validation()->is_phone_number( $args[$key] ) ) {
								UM()->form()->add_error($key, __('Please enter a valid phone number','ultimate-member') );
							}
							break;

						case 'youtube_url':
							if ( ! UM()->validation()->is_url( $args[$key], 'youtube.com' ) ) {
								UM()->form()->add_error($key, sprintf(__('Please enter a valid %s username or profile URL','ultimate-member'), $array['label'] ) );
							}
							break;

						case 'soundcloud_url':
							if ( ! UM()->validation()->is_url( $args[$key], 'soundcloud.com' ) ) {
								UM()->form()->add_error($key, sprintf(__('Please enter a valid %s username or profile URL','ultimate-member'), $array['label'] ) );
							}
							break;

						case 'facebook_url':
							if ( ! UM()->validation()->is_url( $args[$key], 'facebook.com' ) ) {
								UM()->form()->add_error($key, sprintf(__('Please enter a valid %s username or profile URL','ultimate-member'), $array['label'] ) );
							}
							break;

						case 'twitter_url':
							if ( ! UM()->validation()->is_url( $args[$key], 'twitter.com' ) ) {
								UM()->form()->add_error($key, sprintf(__('Please enter a valid %s username or profile URL','ultimate-member'), $array['label'] ) );
							}
							break;

						case 'instagram_url':
							if ( ! UM()->validation()->is_url( $args[$key], 'instagram.com' ) ) {
								UM()->form()->add_error($key, sprintf(__('Please enter a valid %s username or profile URL','ultimate-member'), $array['label'] ) );
							}
							break;

						case 'google_url':
							if ( ! UM()->validation()->is_url( $args[$key], 'plus.google.com' ) ) {
								UM()->form()->add_error($key, sprintf(__('Please enter a valid %s username or profile URL','ultimate-member'), $array['label'] ) );
							}
							break;

						case 'linkedin_url':
							if ( ! UM()->validation()->is_url( $args[$key], 'linkedin.com' ) ) {
								UM()->form()->add_error($key, sprintf(__('Please enter a valid %s username or profile URL','ultimate-member'), $array['label'] ) );
							}
							break;

						case 'vk_url':
							if ( ! UM()->validation()->is_url( $args[$key], 'vk.com' ) ) {
								UM()->form()->add_error($key, sprintf(__('Please enter a valid %s username or profile URL','ultimate-member'), $array['label'] ) );
							}
							break;

						case 'url':
							if ( ! UM()->validation()->is_url( $args[$key] ) ) {
								UM()->form()->add_error($key, __('Please enter a valid URL','ultimate-member') );
							}
							break;

						case 'skype':
							if ( ! UM()->validation()->is_url( $args[$key], 'skype.com' ) ) {
								UM()->form()->add_error($key, sprintf(__('Please enter a valid %s username or profile URL','ultimate-member'), $array['label'] ) );
							}
							break;

						case 'unique_username':

							if ( $args[$key] == '' ) {
								UM()->form()->add_error($key, __('You must provide a username','ultimate-member') );
							} else if ( is_email( $args[$key] ) ) {
								UM()->form()->add_error($key, __('Username cannot be an email','ultimate-member') );
							} else if ( ! UM()->validation()->safe_username( $args[$key] ) ) {
								UM()->form()->add_error($key, __('Your username contains invalid characters','ultimate-member') );
							} else if ( $mode == 'register' && $exist_user_id =  username_exists( sanitize_user( $args[$key] ) ) ) {
								if( is_user_member_of_blog( $exist_user_id ) )
									UM()->form()->add_error($key, __('Your username is already taken','ultimate-member') );
							}

							break;

						case 'unique_username_or_email':

							if ( $args[$key] == '' ) {
								UM()->form()->add_error($key,  __('You must provide a username','ultimate-member') );
							} else if ( ! UM()->validation()->safe_username( $args[$key] ) ) {
								UM()->form()->add_error($key,  __('Your username contains invalid characters','ultimate-member') );
							} else if ( $mode == 'register' && $exist_user_id = username_exists( sanitize_user( $args[$key] ) ) ) {
								if( is_user_member_of_blog( $exist_user_id ) )
									UM()->form()->add_error($key, __('Your username is already taken','ultimate-member') );
							} else if ( $mode == 'register' && $exist_user_id = email_exists( $args[$key] ) ) {
								if( is_user_member_of_blog( $exist_user_id ) )
									UM()->form()->add_error($key,  __('This email is already linked to an existing account','ultimate-member') );
							}

							break;

						case 'unique_email':

							$args[ $key ] = trim( $args[ $key ] );

							if ( in_array( $key, array('user_email') ) ) {

								if( ! isset( $args['user_id'] ) ){
									$args['user_id'] = um_get_requested_user();
								}

								$email_exists =  email_exists( $args[ $key ] );
								if( !is_user_member_of_blog( $email_exists ) ) {
									$email_exists = false;
								}

								if ( $args[ $key ] == '' && in_array( $key, array('user_email') ) ) {
									UM()->form()->add_error( $key, __('You must provide your email','ultimate-member') );
								} else if ( in_array( $mode, array('profile') )  && $email_exists && $email_exists != $args['user_id']  ) {
									UM()->form()->add_error( $key, __('This email is already linked to an existing account','ultimate-member') );
								} else if ( !is_email( $args[ $key ] ) ) {
									UM()->form()->add_error( $key, __('This is not a valid email','ultimate-member') );
								} else if ( ! UM()->validation()->safe_username( $args[ $key ] ) ) {
									UM()->form()->add_error( $key,  __('Your email contains invalid characters','ultimate-member') );
								} else if ( in_array( $mode, array('register') )  && $email_exists  ) {
									UM()->form()->add_error($key, __('This email is already linked to an existing account','ultimate-member') );
								}

							} else {

								if ( $args[ $key ] != '' && !is_email( $args[ $key ] ) ) {
									UM()->form()->add_error( $key, __('This is not a valid email','ultimate-member') );
								} else if ( $args[ $key ] != '' && email_exists( $args[ $key ] ) ) {
									UM()->form()->add_error($key, __('This email is already linked to an existing account','ultimate-member') );
								} else if ( $args[ $key ] != '' ) {
										
									$users = get_users('meta_value='.$args[ $key ]);

									foreach ( $users as $user ) {
										if( $user->ID != $args['user_id'] ){
											UM()->form()->add_error( $key, __('This email is already linked to an existing account','ultimate-member') );
										}
									}

										
								}

							}

							break;

						case 'unique_value':

							if ( $args[$key] != '' ) {

								$args_unique_meta = array(
									'meta_key' => $key,
									'meta_value' => $args[ $key ],
									'compare' => '=',
									'exclude' => array( $args['user_id'] ),
								);

								$meta_key_exists = get_users( $args_unique_meta );

								if ( $meta_key_exists ) {
									UM()->form()->add_error( $key , __('You must provide a unique value','ultimate-member') );
								}
							}
							break;
							
						case 'alphabetic':

							if ( $args[$key] != '' ) {

								if( ! ctype_alpha( str_replace(' ', '', $args[$key] ) ) ){
									UM()->form()->add_error( $key , __('You must provide alphabetic letters','ultimate-member') );
								}
							}
							break;

						case 'lowercase':

							if ( $args[$key] != '' ) {

								if( ! ctype_lower( str_replace(' ', '',$args[$key] ) ) ){
									UM()->form()->add_error( $key , __('You must provide lowercase letters.','ultimate-member') );
								}
							}

							break;

					}

				}

			}

			if ( isset( $args['description'] ) ) {
					
				$max_chars = UM()->options()->get('profile_bio_maxchars');
				$profile_show_bio = UM()->options()->get('profile_show_bio');

				if( $profile_show_bio ){
					if ( strlen( utf8_decode( $args['description'] ) ) > $max_chars && $max_chars  ) {
						UM()->form()->add_error('description', sprintf(__('Your user description must contain less than %s characters','ultimate-member'), $max_chars ) );
					}
				}

			}

		} // end if ( isset in args array )
	}
}
add_action( 'um_submit_form_errors_hook', 'um_submit_form_validate_fields', 40 );