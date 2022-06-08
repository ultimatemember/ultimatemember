<?php
namespace um\core;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'um\core\Validation' ) ) {


	/**
	 * Class Validation
	 * @package um\core
	 */
	class Validation {


		/**
		 * Validation constructor.
		 */
		function __construct() {
			$this->regex_safe = '/\A[\w\-\.]+\z/';
			$this->regex_username_safe = '|[^a-z0-9 _.\-@]|i';
			$this->regex_phone_number = '/\A[\d\-\.\+\(\)\ ]+\z/';


			add_filter( 'um_user_pre_updating_files_array', array( $this, 'validate_files' ), 10, 1 );
			add_filter( 'um_before_save_filter_submitted', array( $this, 'validate_fields_values' ), 10, 2 );
		}


		/**
		 * Validate files before upload
		 *
		 * @param $files
		 *
		 * @return mixed
		 */
		function validate_files( $files ) {
			if ( ! empty( $files ) ) {
				foreach ( $files as $key => $filename ) {
					if ( validate_file( $filename ) !== 0 ) {
						unset( $files[ $key ] );
					}
				}
			}

			return $files;
		}



		function validate_fields_values( $changes, $args ) {
			$fields = array();
			if ( ! empty( $args['custom_fields'] ) ) {
				$fields = unserialize( $args['custom_fields'] );
			}

			foreach ( $changes as $key => $value ) {
				if ( ! isset( $fields[ $key ] ) ) {
					continue;
				}

				//rating field validation
				if ( isset( $fields[ $key ]['type'] ) && $fields[ $key ]['type'] == 'rating' ) {
					if ( ! is_numeric( $value ) ) {
						unset( $changes[ $key ] );
					} else {
						if ( $fields[ $key ]['number'] == 5 ) {
							if ( ! in_array( $value, range( 1, 5 ) ) ) {
								unset( $changes[ $key ] );
							}
						} elseif ( $fields[ $key ]['number'] == 10 ) {
							if ( ! in_array( $value, range( 1, 10 ) ) ) {
								unset( $changes[ $key ] );
							}
						}
					}
				}

				//validation of correct values from options in wp-admin
				$stripslashes = $value;
				if ( is_string( $value ) ) {
					$stripslashes = stripslashes( $value );
				}

				// Dynamic dropdown options population
				$has_custom_source = apply_filters("um_has_dropdown_options_source__{$key}", false );
				if ( in_array( $fields[ $key ]['type'], array( 'select','multiselect' ) ) && $has_custom_source ){
					$arr_options = apply_filters("um_get_field__{$key}", $fields[ $key ]['options'] );
					$fields[ $key ]['options'] = array_keys( $arr_options['options'] );
				}

				// Dropdown options source from callback function
				if ( in_array( $fields[ $key ]['type'], array( 'select','multiselect' ) ) && 
					isset( $fields[ $key ]['custom_dropdown_options_source'] ) &&
					! empty( $fields[ $key ]['custom_dropdown_options_source'] ) &&
					function_exists( $fields[ $key ]['custom_dropdown_options_source'] ) ) {
					$arr_options = call_user_func( $fields[ $key ]['custom_dropdown_options_source'] );
					$fields[ $key ]['options'] = array_keys( $arr_options );
				}
				
				// Unset changed value that doesn't match the option list
				if ( in_array( $fields[ $key ]['type'], array( 'select' ) ) &&
				     ! empty( $stripslashes ) && ! empty( $fields[ $key ]['options'] ) &&
				     ! in_array( $stripslashes, array_map( 'trim', $fields[ $key ]['options'] ) ) ) {
					unset( $changes[ $key ] );
				}

				//validation of correct values from options in wp-admin
				//the user cannot set invalid value in the hidden input at the page
				if ( in_array( $fields[ $key ]['type'], array( 'multiselect', 'checkbox', 'radio' ) ) &&
				     ! empty( $value ) && ! empty( $fields[ $key ]['options'] ) ) {
					$value = array_map( 'stripslashes', array_map( 'trim', $value ) );
					$changes[ $key ] = array_intersect( $value, array_map( 'trim', $fields[ $key ]['options'] ) );
				}

			}

			return $changes;
		}


		/**
		 * Removes html from any string
		 *
		 * @param $string
		 *
		 * @return string
		 */
		function remove_html( $string ) {
			return wp_strip_all_tags( $string );
		}


		/**
		 * Normalize a string
		 *
		 * @param $string
		 *
		 * @return mixed
		 */
		function normalize( $string ) {
			$string = preg_replace('~&([a-z]{1,2})(acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i', '$1', htmlentities($string, ENT_QUOTES, 'UTF-8'));
			return $string;
		}


		/**
		 * Safe name usage ( for url purposes )
		 *
		 * @param $name
		 *
		 * @return mixed|string
		 */
		function safe_name_in_url( $name ) {
			$name = strtolower( $name );
			$name = preg_replace("/'/","", $name );
			$name = stripslashes( $name );
			$name = $this->normalize($name);
			$name = rawurldecode( $name );
			return $name;
		}


		/**
		 * Password strength test
		 *
		 * @param string $candidate
		 *
		 * @return bool
		 */
		function strong_pass( $candidate ) {
			// are used Unicode Regular Expressions
			$regexps = [
				'/[\p{Lu}]/u', // any Letter Uppercase symbol
				'/[\p{Ll}]/u', // any Letter Lowercase symbol
				'/[\p{N}]/u', // any Number symbol
			];
			foreach ( $regexps as $regexp ) {
				if ( preg_match_all( $regexp, $candidate, $o ) < 1 ) {
					return false;
				}
			}
			return true;
		}


		/**
		 * Space, dash, underscore
		 *
		 * @param $string
		 *
		 * @return bool
		 */
		function safe_username( $string ) {

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_validation_safe_username_regex
			 * @description Change validation regex for username
			 * @input_vars
			 * [{"var":"$regex_safe","type":"string","desc":"Regex"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_validation_safe_username_regex', 'function_name', 10, 1 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_validation_safe_username_regex', 'my_validation_safe_username', 10, 1 );
			 * function my_validation_safe_username( $regex_safe ) {
			 *     // your code here
			 *     return $regex_safe;
			 * }
			 * ?>
			 */
			$regex_safe_username = apply_filters( 'um_validation_safe_username_regex', $this->regex_username_safe );

			if ( is_email( $string ) ) {
				return true;
			}
			if ( ! is_email( $string ) && preg_match( $regex_safe_username, $string ) ) {
				return false;
			}
			return true;
		}


		/**
		 * Dash and underscore (metakey)
		 *
		 * @param $string
		 *
		 * @return bool
		 */
		function safe_string( $string ) {

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_validation_safe_string_regex
			 * @description Change validation regex for each string
			 * @input_vars
			 * [{"var":"$regex_safe","type":"string","desc":"Regex"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_validation_safe_string_regex', 'function_name', 10, 1 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_validation_safe_string_regex', 'my_validation_safe_string', 10, 1 );
			 * function my_validation_safe_string( $regex_safe ) {
			 *     // your code here
			 *     return $regex_safe;
			 * }
			 * ?>
			 */
			$regex_safe_string = apply_filters( 'um_validation_safe_string_regex', $this->regex_safe );

			if ( ! preg_match( $regex_safe_string, $string ) ) {
				return false;
			}
			return true;
		}


		/**
		 * Ss phone number
		 *
		 * @param $string
		 *
		 * @return bool
		 */
		function is_phone_number( $string ) {
			if ( ! $string ) {
				return true;
			}
			if ( ! preg_match( $this->regex_phone_number, $string ) ) {
				return false;
			}
			return true;
		}


		/**
		 * Is Discord ID?
		 *
		 * @param $string
		 *
		 * @return bool
		 */
		public function is_discord_id( $string ) {
			if ( ! $string ) {
				return true;
			}
			if ( substr_count( $string, '#' ) > 1 ) {
				return false;
			}
			if ( ! preg_match( '/^(.+)#(\d+)$/', trim( $string ) ) ) {
				return false;
			}
			return true;
		}


		/**
		 * Is url
		 *
		 * @param $url
		 * @param bool $social
		 *
		 * @return bool
		 */
		function is_url( $url, $social = false ) {
			if ( ! $url ) {
				return true;
			}

			if ( $social ) {

				if ( strstr( $url, $social ) && '' != str_replace( $social, '', $url ) ) {
					return true;
				}

			} else {

				if ( strstr( $url, 'http://' ) || strstr( $url, 'https://' ) ) {
					return true;
				}

			}

			return false;
		}


		/**
		 * Get a random string
		 *
		 * @param int $length
		 *
		 * @return string
		 */
		function randomize( $length = 10 ) {
			$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$result = '';
			for ( $i = 0; $i < $length; $i++ ) {
				$result .= $characters[ rand( 0, strlen( $characters ) - 1 ) ];
			}
			return $result;
		}


		/**
		 * Generate a password, hash, or similar
		 *
		 * @param int $length
		 *
		 * @return string
		 */
		function generate( $length = 40 ) {
			return wp_generate_password( $length, false );
		}


		/**
		 * Random numbers only
		 *
		 * @param bool $len
		 *
		 * @return int|string
		 */
		function random_number( $len = false ) {
			$ints = array();
			$len = $len ? $len : rand( 2, 9 );
			if ( $len > 9 ) {
				trigger_error( 'Maximum length should not exceed 9' );
				return 0;
			}

			while( true ) {
				$current = rand(0,9);
				if ( ! in_array( $current, $ints ) ) {
					$ints[] = $current;
				}
				if ( count( $ints ) == $len ) {
					return implode( $ints );
				}
			}
		}


		/**
		 * To validate given date input
		 *
		 * @param $date
		 * @param string $format
		 *
		 * @return bool
		 */
		function validate_date( $date, $format = 'YYYY/MM/D' ) {
			if ( strlen( $date ) < strlen( $format ) ) {
				return false;
			}
			if ( $date[4] != '/' ) {
				return false;
			}
			if ( $date[7] != '/' ) {
				return false;
			}
			if ( false === strtotime( $date ) ) {
				return false;
			}
			return true;
		}

	}
}
