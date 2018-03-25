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
			$this->regex_phone_number = '/\A[\d\-\.\+\(\)\ ]+\z/';
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
		 * Password test
		 *
		 * @param $candidate
		 *
		 * @return bool
		 */
		function strong_pass( $candidate ) {
			$r1='/[A-Z]/';
			$r2='/[a-z]/';
			$r3='/[0-9]/';
			if(preg_match_all($r1,$candidate, $o)<1) return false;
			if(preg_match_all($r2,$candidate, $o)<1) return false;
			if(preg_match_all($r3,$candidate, $o)<1) return false;
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
			$regex_safe_username = apply_filters('um_validation_safe_username_regex',$this->regex_safe );

			if ( is_email( $string ) )
				return true;
			if ( !is_email( $string) && !preg_match( $regex_safe_username, $string ) )
				return false;
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
			$regex_safe_string = apply_filters('um_validation_safe_string_regex',$this->regex_safe );

			if ( !preg_match( $regex_safe_string, $string) ){
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
			if ( !$string )
				return true;
			if ( !preg_match( $this->regex_phone_number, $string) )
				return false;
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
		function is_url( $url, $social = false ){
			if ( !$url ) return true;

			if ( $social ) {

				if ( !filter_var($url, FILTER_VALIDATE_URL) && strstr( $url, $social )  ) { // starts with social requested
					return true;
				} else {

					if ( filter_var($url, FILTER_VALIDATE_URL) && strstr( $url, $social ) ) {
						return true;
					} elseif ( preg_match( $this->regex_safe, $url) ) {

						if ( strstr( $url, '.com' ) ){
							return false;
						} else {
							return true;
						}

					}

				}

			} else {

				if ( strstr( $url, 'http://') || strstr( $url, 'https://') )
					return true;

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
			for ($i = 0; $i < $length; $i++) {
				$result .= $characters[rand(0, strlen($characters) - 1)];
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
			$len = $len ? $len : rand(2,9);
			if($len > 9)
			{
				trigger_error('Maximum length should not exceed 9');
				return 0;
			}
			while(true)
			{
				$current = rand(0,9);
				if(!in_array($current,$ints))
				{
					$ints[] = $current;
				}
				if(count($ints) == $len)
				{
					return implode($ints);
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
		function validate_date( $date, $format='YYYY/MM/D' ) {
			if ( strlen( $date ) < strlen($format) ) return false;
			if ( $date[4] != '/' ) return false;
			if ( $date[7] != '/' ) return false;
			if ( false === strtotime($date) ) return false;
			return true;
		}

	}
}