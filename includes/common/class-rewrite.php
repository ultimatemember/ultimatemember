<?php
namespace um\common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Rewrite
 * @package um\common
 */
class Rewrite {

	/**
	 * Rewrite constructor.
	 */
	public function __construct() {
		// Add rewrite rules
		add_filter( 'query_vars', array( &$this, 'query_vars' ) );
		add_filter( 'rewrite_rules_array', array( &$this, 'add_rewrite_rules' ) );

		add_action( 'template_redirect', array( &$this, 'temp_files_routing' ), 1 );
		add_action( 'template_redirect', array( &$this, 'download_routing' ), 1 );
	}

	/**
	 * Modify global query vars.
	 *
	 * @param array $public_query_vars
	 *
	 * @return array
	 */
	public function query_vars( $public_query_vars ) {
		$public_query_vars[] = 'um_user';
		$public_query_vars[] = 'um_tab';
		$public_query_vars[] = 'profiletab';
		$public_query_vars[] = 'subnav';

		$public_query_vars[] = 'um_page';
		$public_query_vars[] = 'um_action';
		$public_query_vars[] = 'um_field';
		$public_query_vars[] = 'um_form';
		$public_query_vars[] = 'um_verify';

		$public_query_vars[] = 'um_nonce';
		$public_query_vars[] = 'um_filename';

		return $public_query_vars;
	}

	/**
	 * Add UM rewrite rules.
	 *
	 * @param array $rules
	 *
	 * @return array
	 */
	public function add_rewrite_rules( $rules ) {
		$newrules = array();

		// NGINX-config `rewrite ^/um-download/([^/]+)/([^/]+)/([^/]+)/([^/]+)/?$ /index.php?um_action=download&um_form=$1&um_field=$2&um_user=$3&um_verify=$4 last;`
		$newrules['um-download/([^/]+)/([^/]+)/([^/]+)/([^/]+)/?$'] = 'index.php?um_action=download&um_form=$matches[1]&um_field=$matches[2]&um_user=$matches[3]&um_verify=$matches[4]';

		// NGINX-config `rewrite ^/um-temp/([^/]+)/([^/]+)/([^/]+)/([^/]+)/?$ /index.php?um_action=temp-access&um_nonce=$1 last;`
		$newrules['um-temp/([^/]+)/([^/]+)/([^/]+)/([^/]+)/?$'] = 'index.php?um_action=temp-access&um_nonce=$matches[1]';

		if ( isset( UM()->config()->permalinks['user'] ) ) {

			$user_page_id = UM()->config()->permalinks['user'];
			$user         = get_post( $user_page_id );

			if ( isset( $user->post_name ) ) {
				$user_slug                              = $user->post_name;
				$newrules[ $user_slug . '/([^/]+)/?$' ] = 'index.php?page_id=' . $user_page_id . '&um_user=$matches[1]';
			}

			if ( UM()->external_integrations()->is_wpml_active() ) {
				global $sitepress;

				$active_languages = $sitepress->get_active_languages();

				foreach ( $active_languages as $language_code => $language ) {
					$lang_post_id  = wpml_object_id_filter( $user_page_id, 'post', false, $language_code );
					$lang_post_obj = get_post( $lang_post_id );

					if ( isset( $lang_post_obj->post_name ) && $lang_post_obj->post_name !== $user->post_name ) {
						$user_slug                              = $lang_post_obj->post_name;
						$newrules[ $user_slug . '/([^/]+)/?$' ] = 'index.php?page_id=' . $lang_post_id . '&um_user=$matches[1]&lang=' . $language_code;
					}
				}
			}
		}

		if ( isset( UM()->config()->permalinks['account'] ) ) {
			$account_page_id = UM()->config()->permalinks['account'];
			$account         = get_post( $account_page_id );

			if ( isset( $account->post_name ) ) {
				$account_slug                             = $account->post_name;
				$newrules[ $account_slug . '/([^/]+)?$' ] = 'index.php?page_id=' . $account_page_id . '&um_tab=$matches[1]';
			}

			if ( UM()->external_integrations()->is_wpml_active() ) {
				global $sitepress;

				$active_languages = $sitepress->get_active_languages();

				foreach ( $active_languages as $language_code => $language ) {
					$lang_post_id  = wpml_object_id_filter( $account_page_id, 'post', false, $language_code );
					$lang_post_obj = get_post( $lang_post_id );

					if ( isset( $lang_post_obj->post_name ) && $lang_post_obj->post_name !== $account->post_name ) {
						$account_slug                              = $lang_post_obj->post_name;
						$newrules[ $account_slug . '/([^/]+)/?$' ] = 'index.php?page_id=' . $lang_post_id . '&um_user=$matches[1]&lang=' . $language_code;
					}
				}
			}
		}

		return $newrules + $rules;
	}

	/**
	 * @todo temp files routing.
	 * @return void
	 */
	public function temp_files_routing() {
		if ( 'um-temp' !== get_query_var( 'um_action' ) ) {
			return;
		}

		JB()->setcookie( 'jb-guest-job-posting', $uniqid, time() + HOUR_IN_SECONDS );

		$query_verify = get_query_var( 'um_nonce' );
		if ( empty( $query_verify ) || ! wp_verify_nonce( $query_verify, $user_id . 'um-temp-file-nonce' ) ) {
			return;
		}

		$filename = get_query_var( 'um_filename' );
		if ( empty( $filename ) ) {
			$url      = UM()->permalinks()->get_current_url();
			$filename = wp_basename( $url );
		}
		$temp_dir  = UM()->common()->filesystem()->get_tempdir();
		$file_path = wp_normalize_path( "$temp_dir/$filename" );
		if ( ! file_exists( $file_path ) ) {
			return;
		}

		$size = filesize( $file_path );

		$file_info    = get_post_meta( $post_id, '_photo_metadata', true );
		$originalname = $file_info['original_name'];
		$type         = $file_info['type'];

		$download_type = $field_data[ $field_key ]['type'];
		if ( $download_type === 'file' ) {
			$this->file_download( $user_id, $field_key, $field_value );
		} else {
			$this->image_download( $user_id, $field_key, $field_value );
		}
	}

	/**
	 * @return bool
	 */
	public function download_routing() {
		if ( 'download' !== get_query_var( 'um_action' ) ) {
			return false;
		}

		$query_form = get_query_var( 'um_form' );
		if ( empty( $query_form ) ) {
			return false;
		}

		$form_id     = get_query_var( 'um_form' );
		$query_field = get_query_var( 'um_field' );
		if ( empty( $query_field ) ) {
			return false;
		}
		$field_key  = urldecode( get_query_var( 'um_field' ) );
		$query_user = get_query_var( 'um_user' );
		if ( empty( $query_user ) ) {
			return false;
		}

		$user_id = get_query_var( 'um_user' );
		$user    = get_userdata( $user_id );

		if ( empty( $user ) || is_wp_error( $user ) ) {
			return false;
		}
		$query_verify = get_query_var( 'um_verify' );
		if ( empty( $query_verify ) ||
		     ! wp_verify_nonce( $query_verify, $user_id . $form_id . 'um-download-nonce' ) ) {
			return false;
		}

		um_fetch_user( $user_id );
		$field_data = get_post_meta( $form_id, '_um_custom_fields', true );
		if ( empty( $field_data[ $field_key ] ) ) {
			return false;
		}

		if ( ! um_can_view_field( $field_data[ $field_key ] ) ) {
			return false;
		}

		$field_value = UM()->fields()->field_value( $field_key );
		if ( empty( $field_value ) ) {
			return false;
		}

		$download_type = $field_data[ $field_key ]['type'];
		if ( $download_type === 'file' ) {
			$this->file_download( $user_id, $field_key, $field_value );
		} else {
			$this->image_download( $user_id, $field_key, $field_value );
		}

		return false;
	}

	/**
	 * @param $user_id
	 * @param $field_key
	 * @param $field_value
	 */
	private function image_download( $user_id, $field_key, $field_value ) {
		$file_path = UM()->uploader()->get_upload_base_dir() . $user_id . DIRECTORY_SEPARATOR . $field_value;
		if ( ! file_exists( $file_path ) ) {
			if ( is_multisite() ) {
				//multisite fix for old customers
				$file_path = str_replace( DIRECTORY_SEPARATOR . 'sites' . DIRECTORY_SEPARATOR . get_current_blog_id() . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $file_path );
			}
		}

		//validate traversal file
		if ( validate_file( $file_path ) === 1 ) {
			return;
		}

		$file_info = get_user_meta( $user_id, $field_key . '_metadata', true );

		$pathinfo     = pathinfo( $file_path );
		$size         = filesize( $file_path );
		$originalname = ! empty( $file_info['original_name'] ) ? $file_info['original_name'] : $pathinfo['basename'];
		$type         = ! empty( $file_info['type'] ) ? $file_info['type'] : $pathinfo['extension'];

		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: ' . $type );
		header( 'Content-Disposition: inline; filename="' . $originalname . '"' );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Pragma: public' );
		header( 'Content-Length: ' . $size );

		$levels = ob_get_level();
		for ( $i = 0; $i < $levels; $i++ ) {
			@ob_end_clean();
		}

		readfile( $file_path );
		exit;
	}

	/**
	 * @param $user_id
	 * @param $field_key
	 * @param $field_value
	 */
	private function file_download( $user_id, $field_key, $field_value ) {
		$file_path = UM()->uploader()->get_upload_base_dir() . $user_id . DIRECTORY_SEPARATOR . $field_value;
		if ( ! file_exists( $file_path ) ) {
			if ( is_multisite() ) {
				//multisite fix for old customers
				$file_path = str_replace( DIRECTORY_SEPARATOR . 'sites' . DIRECTORY_SEPARATOR . get_current_blog_id() . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $file_path );
			}
		}

		//validate traversal file
		if ( validate_file( $file_path ) === 1 ) {
			return;
		}

		$file_info = get_user_meta( $user_id, $field_key . '_metadata', true );

		$pathinfo     = pathinfo( $file_path );
		$size         = filesize( $file_path );
		$originalname = ! empty( $file_info['original_name'] ) ? $file_info['original_name'] : $pathinfo['basename'];
		$type         = ! empty( $file_info['type'] ) ? $file_info['type'] : $pathinfo['extension'];

		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: ' . $type );
		header( 'Content-Disposition: attachment; filename="' . $originalname . '"' );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Pragma: public' );
		header( 'Content-Length: ' . $size );

		$levels = ob_get_level();
		for ( $i = 0; $i < $levels; $i++ ) {
			@ob_end_clean();
		}

		readfile( $file_path );
		exit;
	}

	/**
	 * Get temp file link.
	 *
	 * @param string $filename
	 *
	 * @return string
	 */
	public function get_temp_link( $filename ) {
		$url   = get_home_url( get_current_blog_id() );
		$nonce = wp_create_nonce( $user_id . $form_id . 'um-download-nonce' );

		if ( UM()->is_permalinks ) {
			$url = $url . "/um-temp/{$nonce}/$filename";
		} else {
			$url = add_query_arg(
				array(
					'um_action'   => 'temp-access',
					'um_nonce'    => $nonce,
					'um_filename' => $filename,
				),
				$url
			);
		}

		return $url;
	}
}
