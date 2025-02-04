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

		if ( UM()->is_new_ui() ) {
			if ( UM()->options()->get( 'files_secure_links' ) ) {
				$public_query_vars[] = 'um_field';
				$public_query_vars[] = 'um_form';
			}

			$public_query_vars[] = 'um_nonce';
			$public_query_vars[] = 'um_filename';
		} else {
			$public_query_vars[] = 'um_field';
			$public_query_vars[] = 'um_form';
			$public_query_vars[] = 'um_verify';
		}

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

		if ( UM()->is_new_ui() ) {
			$image_mimes   = UM()->common()->filesystem()::image_mimes();
			$files_mimes   = UM()->common()->filesystem()::file_mimes();
			$allowed_mimes = implode( '|', array_merge( $image_mimes, $files_mimes ) );

			if ( UM()->options()->get( 'files_secure_links' ) ) {
				// NGINX-config `rewrite ^/um-download/([^/]+)/([^/]+)/([^/]+)/([^/]+)/\d{1,10}\.(jpg|jpeg|jpe|gif|png|bmp|tif|tiff|ico|heic|heif|webp|avif|aac|flac|m4a|m4b|mka|mp3|ogg|oga|ram|wav|wma|3g2|3gp|3gpp|asf|avi|divx|flv|m4v|mkv|mov|mp4|mpeg|mpg|ogv|qt|wmv|doc|docx|docm|dotm|odt|pages|pdf|xps|oxps|rtf|wp|wpd|psd|xcf|numbers|ods|xls|xlsx|xlsm|xlsb|key|ppt|pptx|pptm|pps|ppsx|ppsm|sldx|sldm|odp|asc|csv|tsv|txt|gz|rar|tar|zip|7z|css|htm|html|js)$ /index.php?um_action=download&um_form=$1&um_field=$2&um_user=$3&um_nonce=$4 last;`
				$newrules['um-download/([^/]+)/([^/]+)/([^/]+)/([^/]+)/\d{1,10}\.(' . $allowed_mimes . ')$'] = 'index.php?um_action=download&um_form=$matches[1]&um_field=$matches[2]&um_user=$matches[3]&um_nonce=$matches[4]';
			}

			// NGINX-config `rewrite ^/um-temp/([^/]+)/([^/]+)/\w{1,32}\.(jpg|jpeg|jpe|gif|png|bmp|tif|tiff|ico|heic|heif|webp|avif|aac|flac|m4a|m4b|mka|mp3|ogg|oga|ram|wav|wma|3g2|3gp|3gpp|asf|avi|divx|flv|m4v|mkv|mov|mp4|mpeg|mpg|ogv|qt|wmv|doc|docx|docm|dotm|odt|pages|pdf|xps|oxps|rtf|wp|wpd|psd|xcf|numbers|ods|xls|xlsx|xlsm|xlsb|key|ppt|pptx|pptm|pps|ppsx|ppsm|sldx|sldm|odp|asc|csv|tsv|txt|gz|rar|tar|zip|7z|css|htm|html|js)$ /index.php?um_action=temp-download&um_user=$1&um_nonce=$2 last;`
			$newrules['um-temp/([^/]+)/([^/]+)/\w{1,32}\.(' . $allowed_mimes . ')$'] = 'index.php?um_action=temp-download&um_user=$matches[1]&um_nonce=$matches[2]';
		} else {
			// NGINX-config `rewrite ^/um-download/([^/]+)/([^/]+)/([^/]+)/([^/]+)/?$ /index.php?um_action=download&um_form=$1&um_field=$2&um_user=$3&um_verify=$4 last;`
			$newrules['um-download/([^/]+)/([^/]+)/([^/]+)/([^/]+)/?$'] = 'index.php?um_action=download&um_form=$matches[1]&um_field=$matches[2]&um_user=$matches[3]&um_verify=$matches[4]';
		}

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
	 * Handle a secure link of the temp file.
	 * @return void
	 */
	public function temp_files_routing() {
		global $wp_filesystem, $wp_query;

		if ( 'temp-download' !== get_query_var( 'um_action' ) ) {
			return;
		}

		$filename = get_query_var( 'um_filename' );
		if ( empty( $filename ) ) {
			$url      = UM()->permalinks()->get_current_url();
			$filename = wp_basename( $url );
		}

		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
		} else {
			$user_id = UM()->common()->guest()->get_guest_token();
		}

		$queried_user = get_query_var( 'um_user' );
		if ( empty( $queried_user ) || (string) $queried_user !== (string) $user_id ) {
			$wp_query->set_404();
			return;
		}

		$query_verify = get_query_var( 'um_nonce' );

		if ( empty( $query_verify ) || ! wp_verify_nonce( $query_verify, $user_id . $filename . 'um-temp-download-nonce' ) ) {
			$wp_query->set_404();
			return;
		}

		$temp_dir = UM()->common()->filesystem()->get_user_temp_dir();
		if ( empty( $temp_dir ) ) {
			// Possible hijacking.
			$wp_query->set_404();
			return;
		}
		$temp_dir .= DIRECTORY_SEPARATOR;

		UM()->common()->filesystem()::maybe_init_wp_filesystem();

		$dirlist = $wp_filesystem->dirlist( $temp_dir );
		$dirlist = $dirlist ? $dirlist : array();
		if ( empty( $dirlist ) ) {
			$wp_query->set_404();
			return;
		}

		foreach ( array_keys( $dirlist ) as $file ) {
			if ( '.' === $file || '..' === $file ) {
				continue;
			}

			$hash = md5( $file . '_um_uploader_security_salt' );

			if ( 0 === strpos( $filename, $hash ) ) {
				$file_path = wp_normalize_path( "$temp_dir/$file" );
				break;
			}
		}

		if ( ! file_exists( $file_path ) ) {
			$wp_query->set_404();
			return;
		}

		// Validate traversal file
		if ( validate_file( $file_path ) === 1 ) {
			$wp_query->set_404();
			return;
		}

		if ( ! is_user_logged_in() ) {
			// Check for excessive downloads (e.g., max 5 downloads per 5 minutes)
			$break_due_downloads_limit = UM()->common()->guest()::check_excessive_downloads();
			if ( $break_due_downloads_limit ) {
				return;
			}

			UM()->common()->guest()::set_download_attempts();
		}

		$pathinfo     = pathinfo( $file_path );
		$size         = filesize( $file_path );
		$originalname = $pathinfo['basename'];
		$type         = $pathinfo['extension'];

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
	 * @return bool
	 */
	public function download_routing() {
		if ( 'download' !== get_query_var( 'um_action' ) ) {
			return false;
		}

		$form_id = get_query_var( 'um_form' );
		if ( empty( $form_id ) ) {
			return false;
		}

		$field_key = get_query_var( 'um_field' );
		if ( empty( $field_key ) ) {
			return false;
		}
		$field_key = urldecode( $field_key );

		$user_id = get_query_var( 'um_user' );
		if ( empty( $user_id ) ) {
			return false;
		}

		$user = get_userdata( $user_id );
		if ( empty( $user ) || is_wp_error( $user ) ) {
			return false;
		}

		if ( UM()->is_new_ui() ) {
			if ( UM()->options()->get( 'files_secure_links' ) ) {
				$query_verify = get_query_var( 'um_nonce' );
			}
		} else {
			$query_verify = get_query_var( 'um_verify' );
		}
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
		$file_path = UM()->common()->filesystem()->get_user_uploads_dir( $user_id ) . DIRECTORY_SEPARATOR . $field_value;

		// Validate traversal file
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
		$file_path = UM()->common()->filesystem()->get_user_uploads_dir( $user_id ) . DIRECTORY_SEPARATOR . $field_value;
		// Validate traversal file
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
}
