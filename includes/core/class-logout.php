<?php
namespace um\core;


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'um\core\Logout' ) ) {


	/**
	 * Class Logout
	 * @package um\core
	 */
	class Logout {


		/**
		 * Logout constructor.
		 */
		function __construct() {
			add_action( 'template_redirect', array( &$this, 'logout_page' ), 10000 );
		}


		/**
		 * @param $redirect_url
		 * @param $status
		 *
		 * @return false|string
		 */
		function safe_redirect_default( $redirect_url, $status ) {
			$login_page_id = UM()->config()->permalinks['login'];
			return get_permalink( $login_page_id );
		}


		/**
		 * Logout via logout page
		 */
		function logout_page() {
			if ( is_home() ) {
				return;
			}

			$trid = 0;
			//$language_code = '';
			if ( UM()->external_integrations()->is_wpml_active() ) {
				global $sitepress;
				$default_lang = $sitepress->get_default_language();

				/*$language_code = $sitepress->get_current_language();
				if ( $language_code == $default_lang ) {
					$language_code = '';
				}*/

				$current_page_ID = get_the_ID();
				if ( function_exists( 'icl_object_id' ) ) {
					$trid = icl_object_id( $current_page_ID, 'page', true, $default_lang );
				} else {
					$trid = wpml_object_id_filter( $current_page_ID, 'page', true, $default_lang );
				}
			}

			$logout_page_id = UM()->config()->permalinks['logout'];
			if ( um_is_core_page( 'logout' ) || ( $trid > 0 && $trid == $logout_page_id ) ) {

				if ( is_user_logged_in() ) {

					add_filter( 'wp_safe_redirect_fallback', array( &$this, 'safe_redirect_default' ), 10, 2 );

					if ( isset( $_REQUEST['redirect_to'] ) && $_REQUEST['redirect_to'] !== '' ) {
						wp_destroy_current_session();
						wp_logout();
						session_unset();
						exit( wp_safe_redirect( $_REQUEST['redirect_to'] ) );
					} else if ( um_user('after_logout') == 'redirect_home' ) {
						wp_destroy_current_session();
						wp_logout();
						session_unset();
						exit( wp_safe_redirect( home_url() ) );
					} else {
						/**
						 * UM hook
						 *
						 * @type filter
						 * @title um_logout_redirect_url
						 * @description Change redirect URL after logout
						 * @input_vars
						 * [{"var":"$url","type":"string","desc":"Redirect URL"},
						 * {"var":"$id","type":"int","desc":"User ID"}]
						 * @change_log
						 * ["Since: 2.0"]
						 * @usage
						 * <?php add_filter( 'um_logout_redirect_url', 'function_name', 10, 2 ); ?>
						 * @example
						 * <?php
						 * add_filter( 'um_logout_redirect_url', 'my_logout_redirect_url', 10, 2 );
						 * function my_logout_redirect_url( $url, $id ) {
						 *     // your code here
						 *     return $url;
						 * }
						 * ?>
						 */
						$redirect_url = apply_filters( 'um_logout_redirect_url', um_user( 'logout_redirect_url' ), um_user( 'ID' ) );
						wp_destroy_current_session();
						wp_logout();
						session_unset();
						exit( wp_safe_redirect( $redirect_url ) );
					}

				} else {
					add_filter( 'wp_safe_redirect_fallback', array( &$this, 'safe_redirect_default' ), 10, 2 );
					exit( wp_safe_redirect( home_url() ) );
				}

			}

		}

	}
}