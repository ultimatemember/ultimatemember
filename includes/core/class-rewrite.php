<?php
namespace um\core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\core\Rewrite' ) ) {

	/**
	 * Class Rewrite
	 * @package um\core
	 */
	class Rewrite {

		/**
		 * Rewrite constructor.
		 */
		public function __construct() {
			if ( ! defined( 'DOING_AJAX' ) ) {
				add_action( 'wp_loaded', array( $this, 'maybe_flush_rewrite_rules' ) );
			}

			//add rewrite rules
			add_filter( 'query_vars', array( &$this, 'query_vars' ) );
			add_filter( 'rewrite_rules_array', array( &$this, 'add_rewrite_rules' ) );

			add_action( 'template_redirect', array( &$this, 'temp_files_routing' ), 1 );
			add_action( 'template_redirect', array( &$this, 'download_routing' ), 1 );

			add_action( 'template_redirect', array( &$this, 'redirect_author_page' ), 9999 );
			add_action( 'template_redirect', array( &$this, 'locate_user_profile' ), 9999 );
		}

		/**
		 * Update "flush" option for reset rules on wp_loaded hook.
		 */
		public function reset_rules() {
			update_option( 'um_flush_rewrite_rules', 1 );
		}

		/**
		 * Reset Rewrite rules if need it.
		 */
		public function maybe_flush_rewrite_rules() {
			if ( get_option( 'um_flush_rewrite_rules' ) ) {
				flush_rewrite_rules( false );
				delete_option( 'um_flush_rewrite_rules' );
			}
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
			$public_query_vars[] = 'um_filename';
			$public_query_vars[] = 'um_verify'; // todo remove where it's used and change to `um_nonce` when all extensions are ready with old UI in the new UI branch.
			$public_query_vars[] = 'um_nonce';

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

			$image_mimes   = UM()->common()->filesystem()::image_mimes();
			$files_mimes   = UM()->common()->filesystem()::file_mimes();
			$allowed_mimes = implode( '|', array_merge( $image_mimes, $files_mimes ) );

			// NGINX-config `rewrite ^/um-download/([^/]+)/([^/]+)/([^/]+)/([^/]+)/\d{1,10}\.(jpg|jpeg|jpe|gif|png|bmp|tif|tiff|ico|heic|heif|webp|avif|aac|flac|m4a|m4b|mka|mp3|ogg|oga|ram|wav|wma|3g2|3gp|3gpp|asf|avi|divx|flv|m4v|mkv|mov|mp4|mpeg|mpg|ogv|qt|wmv|doc|docx|docm|dotm|odt|pages|pdf|xps|oxps|rtf|wp|wpd|psd|xcf|numbers|ods|xls|xlsx|xlsm|xlsb|key|ppt|pptx|pptm|pps|ppsx|ppsm|sldx|sldm|odp|asc|csv|tsv|txt|gz|rar|tar|zip|7z|css|htm|html|js)$ /index.php?um_action=download&um_form=$1&um_field=$2&um_user=$3&um_verify=$4 last;`
			$newrules['um-download/([^/]+)/([^/]+)/([^/]+)/([^/]+)/\d{1,10}\.(' . $allowed_mimes . ')$'] = 'index.php?um_action=download&um_form=$matches[1]&um_field=$matches[2]&um_user=$matches[3]&um_verify=$matches[4]';

			// NGINX-config `rewrite ^/um-temp/([^/]+)/([^/]+)/\w{1,32}\.(jpg|jpeg|jpe|gif|png|bmp|tif|tiff|ico|heic|heif|webp|avif|aac|flac|m4a|m4b|mka|mp3|ogg|oga|ram|wav|wma|3g2|3gp|3gpp|asf|avi|divx|flv|m4v|mkv|mov|mp4|mpeg|mpg|ogv|qt|wmv|doc|docx|docm|dotm|odt|pages|pdf|xps|oxps|rtf|wp|wpd|psd|xcf|numbers|ods|xls|xlsx|xlsm|xlsb|key|ppt|pptx|pptm|pps|ppsx|ppsm|sldx|sldm|odp|asc|csv|tsv|txt|gz|rar|tar|zip|7z|css|htm|html|js)$ /index.php?um_action=temp-download&um_user=$1&um_verify=$2 last;`
			$newrules['um-temp/([^/]+)/([^/]+)/\w{1,32}\.(' . $allowed_mimes . ')$'] = 'index.php?um_action=temp-download&um_user=$matches[1]&um_verify=$matches[2]';

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
		 * @throws RandomException
		 */
		public function temp_files_routing() {
			global $wp_filesystem, $wp_query;

			if ( 'temp-download' !== get_query_var( 'um_action' ) ) {
				return;
			}

			$filename = get_query_var( 'um_filename' );
			if ( empty( $filename ) ) {
				$url = UM()->permalinks()->get_current_url();
				if ( UM()->common()->filesystem()::is_timestamp_addable() ) {
					$url = remove_query_arg( 'timestamp', $url );
				}
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

			$file_path = UM()->common()->filesystem()->get_file_by_hash( $filename );
			if ( false === $file_path ) {
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

			UM()->common()->filesystem()::maybe_init_wp_filesystem();

			$pathinfo     = pathinfo( $file_path );
			$size         = filesize( $file_path );
			$originalname = $pathinfo['basename'];
			$type         = $pathinfo['extension'];

			header( 'Content-Description: File Transfer' );
			header( 'Content-Type: ' . $type );
			header( 'Content-Disposition: inline; filename="' . esc_attr( $originalname ) . '"' );
			header( 'Content-Transfer-Encoding: binary' );
			header( 'Expires: 0' );
			header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
			header( 'Pragma: public' );
			header( 'Content-Length: ' . $size );

			$levels = ob_get_level();
			for ( $i = 0; $i < $levels; $i++ ) {
				@ob_end_clean();
			}

			$content = $wp_filesystem->get_contents( $file_path );
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped  -- temp file content.
			echo $content;
			exit;
		}

		/**
		 * Download uploaded by the user files routing handler.
		 *
		 * @return void
		 */
		public function download_routing() {
			global $wp_query;

			if ( 'download' !== get_query_var( 'um_action' ) ) {
				return;
			}

			$form_id = get_query_var( 'um_form' );
			if ( empty( $form_id ) ) {
				$wp_query->set_404();
				return;
			}

			$field_key = get_query_var( 'um_field' );
			if ( empty( $field_key ) ) {
				$wp_query->set_404();
				return;
			}
			$field_key = urldecode( $field_key );

			$user_id = get_query_var( 'um_user' );
			if ( empty( $user_id ) ) {
				$wp_query->set_404();
				return;
			}

			$query_verify = get_query_var( 'um_verify' );
			if ( empty( $query_verify ) || ! wp_verify_nonce( $query_verify, $user_id . $form_id . $field_key . 'um-download-nonce' ) ) {
				$wp_query->set_404();
				return;
			}

			if ( ! UM()->common()->users()->can_view_user_profile( $user_id ) ) {
				$wp_query->set_404();
				return;
			}

			$field_data = get_post_meta( $form_id, '_um_custom_fields', true );
			if ( empty( $field_data[ $field_key ] ) ) {
				$wp_query->set_404();
				return;
			}

			if ( ! um_can_view_field( $field_data[ $field_key ] ) ) {
				$wp_query->set_404();
				return;
			}

			um_fetch_user( $user_id );
			$field_value = UM()->fields()->field_value( $field_key );
			if ( empty( $field_value ) ) {
				$wp_query->set_404();
				return;
			}

			$download_type = $field_data[ $field_key ]['type'];
			if ( 'file' === $download_type ) {
				$this->file_download( $user_id, $field_key, $field_value );
			} else {
				$this->image_download( $user_id, $field_key, $field_value );
			}
		}

		/**
		 * @param $user_id
		 * @param $field_key
		 * @param $field_value
		 */
		private function image_download( $user_id, $field_key, $field_value ) {
			global $wp_filesystem;

			UM()->common()->filesystem()::maybe_init_wp_filesystem();

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
			header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
			header( 'Pragma: public' );
			header( 'Content-Length: ' . $size );

			$levels = ob_get_level();
			for ( $i = 0; $i < $levels; $i++ ) {
				@ob_end_clean();
			}

			$content = $wp_filesystem->get_contents( $file_path );
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped  -- temp file content.
			echo $content;
			exit;
		}

		/**
		 * @param $user_id
		 * @param $field_key
		 * @param $field_value
		 */
		private function file_download( $user_id, $field_key, $field_value ) {
			global $wp_filesystem;

			UM()->common()->filesystem()::maybe_init_wp_filesystem();

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
			header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
			header( 'Pragma: public' );
			header( 'Content-Length: ' . $size );

			$levels = ob_get_level();
			for ( $i = 0; $i < $levels; $i++ ) {
				@ob_end_clean();
			}

			$content = $wp_filesystem->get_contents( $file_path );
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped  -- temp file content.
			echo $content;
			exit;
		}

		/**
		 * Author page to user profile redirect.
		 */
		public function redirect_author_page() {
			if ( is_author() && UM()->options()->get( 'author_redirect' ) ) {
				$id = get_query_var( 'author' );
				um_fetch_user( $id );
				wp_safe_redirect( um_user_profile_url() );
				exit;
			}
		}

		/**
		 * Getting the user_id based on the User Profile slug like when Base Permalink setting equals 'user_login'.
		 *
		 * @since 2.7.0
		 *
		 * @return bool|int|mixed
		 */
		private function get_user_id_by_user_login_slug() {
			$permalink_base = UM()->options()->get( 'permalink_base' );
			if ( 'custom_meta' === $permalink_base ) {
				$custom_meta = UM()->options()->get( 'permalink_base_custom_meta' );
				if ( empty( $custom_meta ) ) {
					// Set default permalink base if custom meta is empty.
					$permalink_base = 'user_login';
				} else {
					// Ignore username slug if custom meta slug exists.
					$user_id          = username_exists( um_queried_user() );
					$custom_permalink = get_user_meta( $user_id, 'um_user_profile_url_slug_' . $permalink_base, true );
					if ( ! empty( $custom_permalink ) && um_queried_user() !== $custom_permalink ) {
						return false;
					}
				}
			}

			$user_id = username_exists( um_queried_user() );
			//Try
			if ( ! $user_id ) {
				// Search by Profile Slug
				$args = array(
					'fields'     => 'ids',
					'meta_query' => array(
						array(
							'key'     => 'um_user_profile_url_slug_' . $permalink_base,
							'value'   => strtolower( um_queried_user() ),
							'compare' => '=',
						),
					),
					'number'     => 1,
				);

				$ids = new \WP_User_Query( $args );
				if ( $ids->total_users > 0 ) {
					$user_id = current( $ids->get_results() );
				}
			}

			// Try nice name
			if ( ! $user_id ) {
				$slug     = um_queried_user();
				$slug     = str_replace( '.', '-', $slug );
				$the_user = get_user_by( 'slug', $slug );
				if ( isset( $the_user->ID ) ) {
					$user_id = $the_user->ID;
				}

				if ( ! $user_id ) {
					$user_id = UM()->user()->user_exists_by_email_as_username( um_queried_user() );
				}

				if ( ! $user_id ) {
					$user_id = UM()->user()->user_exists_by_email_as_username( $slug );
				}
			}

			return $user_id;
		}

		/**
		 * Locate/display a profile.
		 */
		public function locate_user_profile() {
			$permalink_base = UM()->options()->get( 'permalink_base' );
			if ( 'custom_meta' === $permalink_base ) {
				$custom_meta = UM()->options()->get( 'permalink_base_custom_meta' );
				if ( empty( $custom_meta ) ) {
					// Set default permalink base if custom meta is empty.
					$permalink_base = 'user_login';
				}
			}

			if ( um_queried_user() && um_is_core_page( 'user' ) ) {
				if ( 'user_login' === $permalink_base ) {
					$user_id = $this->get_user_id_by_user_login_slug();
				}

				if ( 'user_id' === $permalink_base && UM()->common()->users()::user_exists( um_queried_user() ) ) {
					$user_id = um_queried_user();
				}

				if ( 'hash' === $permalink_base ) {
					$user_id = UM()->user()->user_exists_by_hash( um_queried_user() );
				}

				if ( 'custom_meta' === $permalink_base ) {
					$user_id = UM()->user()->user_exists_by_custom_meta( um_queried_user() );
					if ( ! $user_id ) {
						// Try user_login by default.
						$user_id = $this->get_user_id_by_user_login_slug();
					}
				}

				if ( in_array( $permalink_base, array( 'name', 'name_dash', 'name_dot', 'name_plus' ), true ) ) {
					$user_id = UM()->user()->user_exists_by_name( um_queried_user() );
				}

				/** USER EXISTS SET USER AND CONTINUE **/

				if ( ! empty( $user_id ) ) {
					um_set_requested_user( $user_id );
					/**
					 * Fires after setting requested user.
					 *
					 * @param {int} $user_id Requested User ID.
					 *
					 * @since 1.3.x
					 * @hook um_access_profile
					 *
					 * @example <caption>Some action on user access profile and requested user isset.</caption>
					 * add_action( 'um_access_profile', 'my_access_profile', 10, 1 );
					 * function my_access_profile( $user_id ) {
					 *     // your code here
					 * }
					 */
					do_action( 'um_access_profile', $user_id );
				} else {
					wp_safe_redirect( um_get_core_page( 'user' ) );
					exit;
				}
			} elseif ( um_is_core_page( 'user' ) ) {
				if ( is_user_logged_in() ) { // just redirect to their profile
					$query = UM()->permalinks()->get_query_array();

					$url = um_user_profile_url( um_user( 'ID' ) );

					if ( $query ) {
						foreach ( $query as $key => $val ) {
							$url = add_query_arg( $key, $val, $url );
						}
					}
					wp_safe_redirect( $url );
					exit;
				}

				/**
				 * Filters the redirect URL from user profile for not logged-in user.
				 *
				 * @param {string} $url Redirect URL. By default, it's a home page.
				 *
				 * @return {string} Redirect URL.
				 *
				 * @since 1.3.x
				 * @hook um_locate_user_profile_not_loggedin__redirect
				 *
				 * @example <caption>Change redirect URL from user profile for not logged-in user to WordPress native login.</caption>
				 * function my_user_profile_not_loggedin__redirect( $url ) {
				 *     // your code here
				 *     $url = wp_login_url();
				 *     return $url;
				 * }
				 * add_filter( 'um_locate_user_profile_not_loggedin__redirect', 'my_user_profile_not_loggedin__redirect' );
				 */
				$redirect_to = apply_filters( 'um_locate_user_profile_not_loggedin__redirect', home_url() );
				if ( ! empty( $redirect_to ) ) {
					um_safe_redirect( $redirect_to );
				}
			}
		}
	}
}
