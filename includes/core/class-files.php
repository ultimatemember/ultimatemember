<?php
namespace um\core;

use Exception;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\core\Files' ) ) {

	/**
	 * Class Files
	 * @package um\core
	 */
	class Files {

		/**
		 * @var
		 */
		public $upload_temp;

		/**
		 * @deprecated 3.0.0
		 *
		 * @var
		 */
		public $upload_baseurl;

		/**
		 * @var
		 */
		public $upload_basedir;

		/**
		 * @deprecated 3.0.0
		 *
		 * @var
		 */
		public $upload_temp_url;

		/**
		 * @deprecated 3.0.0
		 *
		 * @var string
		 */
		public $upload_dir;

		/**
		 * @deprecated 3.0.0
		 *
		 * @var array|array[]
		 */
		public $fonticon = array();

		/**
		 * @deprecated 3.0.0
		 *
		 * @var string
		 */
		public $default_file_fonticon = 'um-faicon-file-o';

		/**
		 * Files constructor.
		 */
		public function __construct() {
			$this->setup_paths();

			add_filter( 'um_upload_basedir_filter', array( &$this, 'multisite_urls_support' ), 99 );
			add_filter( 'um_upload_baseurl_filter', array( &$this, 'multisite_urls_support' ), 99 );
		}

		/**
		 * Support multisite
		 *
		 * @param $dir
		 *
		 * @return string
		 */
		public function multisite_urls_support( $dir ) {
			if ( UM()->is_new_ui() ) {
				return $dir;
			}

			if ( is_multisite() ) { // Need to the work

				if ( get_current_blog_id() == '1' ) {
					return $dir;
				}

				/** This filter is documented in ultimate-member/includes/common/class-filesystem.php */
				$sites_dir = apply_filters( 'um_multisite_upload_sites_directory', 'sites/' );
				$split     = explode( $sites_dir, $dir );
				/**
				 * UM hook
				 *
				 * @type filter
				 * @title um_multisite_upload_directory
				 * @description Change multisite UM uploads directory
				 * @input_vars
				 * [{"var":"$um_dir","type":"string","desc":"Upload UM directory"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage
				 * <?php add_filter( 'um_multisite_upload_directory', 'function_name', 10, 1 ); ?>
				 * @example
				 * <?php
				 * add_filter( 'um_multisite_upload_directory', 'my_multisite_upload_directory', 10, 1 );
				 * function my_multisite_upload_directory( $um_dir ) {
				 *     // your code here
				 *     return $um_dir;
				 * }
				 * ?>
				 */
				$um_dir = apply_filters( 'um_multisite_upload_directory', 'ultimatemember/' );
				$dir    = $split[0] . $um_dir;

			}

			return $dir;
		}

		/**
		 * Remove file by AJAX
		 */
		public function ajax_remove_file() {
			UM()->check_ajax_nonce();

			if ( empty( $_POST['src'] ) ) {
				wp_send_json_error( __( 'Wrong path', 'ultimate-member' ) );
			}

			if ( empty( $_POST['mode'] ) ) {
				wp_send_json_error( __( 'Wrong mode', 'ultimate-member' ) );
			}

			$src = esc_url_raw( $_POST['src'] );
			if ( strstr( $src, '?' ) ) {
				$splitted = explode( '?', $src );
				$src      = $splitted[0];
			}

			$mode = sanitize_key( $_POST['mode'] );

			if ( $mode == 'register' || empty( $_POST['user_id'] ) ) {
				$is_temp = $this->is_temp_upload( $src );
				if ( ! $is_temp ) {
					wp_send_json_success();
				}
			} else {
				$user_id = absint( $_POST['user_id'] );

				if ( ! UM()->roles()->um_current_user_can( 'edit', $user_id ) ) {
					wp_send_json_error( __( 'You have no permission to edit this user', 'ultimate-member' ) );
				}

				$is_temp = $this->is_temp_upload( $src );
				if ( ! $is_temp ) {
					if ( ! empty( $_POST['filename'] ) && file_exists( UM()->common()->filesystem()->get_user_uploads_dir( $user_id ) . DIRECTORY_SEPARATOR . sanitize_file_name( $_POST['filename'] ) ) ) {
						wp_send_json_success();
					}
				}
			}

			if ( $this->delete_file( $src ) ) {
				wp_send_json_success();
			} else {
				wp_send_json_error( __( 'You have no permission to delete this file', 'ultimate-member' ) );
			}
		}

		/**
		 * Resize image AJAX handler
		 */
		public function ajax_resize_image() {
			UM()->check_ajax_nonce();
			// phpcs:disable WordPress.Security.NonceVerification -- verified by the `check_ajax_nonce()`
			if ( ! isset( $_REQUEST['src'], $_REQUEST['coord'], $_REQUEST['key'] ) ) {
				wp_send_json_error( esc_js( __( 'Invalid parameters', 'ultimate-member' ) ) );
			}

			$coord_n = substr_count( $_REQUEST['coord'], ',' );
			if ( 3 !== $coord_n ) {
				wp_send_json_error( esc_js( __( 'Invalid coordinates', 'ultimate-member' ) ) );
			}

			$user_id = empty( $_REQUEST['user_id'] ) ? null : absint( $_REQUEST['user_id'] );
			if ( $user_id && is_user_logged_in() && ! UM()->roles()->um_current_user_can( 'edit', $user_id ) ) {
				wp_send_json_error( esc_js( __( 'You have no permission to edit this user', 'ultimate-member' ) ) );
			}

			if ( $user_id && ! is_user_logged_in() ) {
				wp_send_json_error( esc_js( __( 'Please login to edit this user', 'ultimate-member' ) ) );
			}

			$form_id = isset( $_POST['set_id'] ) ? absint( $_POST['set_id'] ) : null;
			$mode    = isset( $_POST['set_mode'] ) ? sanitize_text_field( $_POST['set_mode'] ) : null;

			UM()->fields()->set_id   = $form_id;
			UM()->fields()->set_mode = $mode;

			if ( ! is_user_logged_in() && 'profile' === $mode ) {
				wp_send_json_error( esc_js( __( 'You have no permission to edit user profile', 'ultimate-member' ) ) );
			}

			if ( null !== $user_id && 'register' === $mode ) {
				wp_send_json_error( esc_js( __( 'User has to be empty on registration', 'ultimate-member' ) ) );
			}

			$form_post = get_post( $form_id );
			// Invalid post ID. Maybe post doesn't exist.
			if ( empty( $form_post ) ) {
				wp_send_json_error( esc_js( __( 'Invalid form ID', 'ultimate-member' ) ) );
			}

			if ( 'um_form' !== $form_post->post_type ) {
				wp_send_json_error( esc_js( __( 'Invalid form post type', 'ultimate-member' ) ) );
			}

			$form_status = get_post_status( $form_id );
			if ( 'publish' !== $form_status ) {
				wp_send_json_error( esc_js( __( 'Invalid form status', 'ultimate-member' ) ) );
			}

			$post_data = UM()->query()->post_data( $form_id );
			if ( ! array_key_exists( 'mode', $post_data ) || $mode !== $post_data['mode'] ) {
				wp_send_json_error( esc_js( __( 'Invalid form type', 'ultimate-member' ) ) );
			}

			// For profiles only.
			if ( 'profile' === $mode && ! empty( $post_data['use_custom_settings'] ) && ! empty( $post_data['role'] ) ) {
				// Option "Apply custom settings to this form". Option "Make this profile form role-specific".
				// Show the first Profile Form with role selected, don't show profile forms below the page with other role-specific setting.
				$current_user_roles = UM()->roles()->get_all_user_roles( $user_id );
				if ( empty( $current_user_roles ) ) {
					wp_send_json_error( esc_js( __( 'You have no permission to edit this user through this form', 'ultimate-member' ) ) );
				}

				$post_data['role'] = maybe_unserialize( $post_data['role'] );

				if ( is_array( $post_data['role'] ) ) {
					if ( ! count( array_intersect( $post_data['role'], $current_user_roles ) ) ) {
						wp_send_json_error( esc_js( __( 'You have no permission to edit this user through this form', 'ultimate-member' ) ) );
					}
				} elseif ( ! in_array( $post_data['role'], $current_user_roles, true ) ) {
					wp_send_json_error( esc_js( __( 'You have no permission to edit this user through this form', 'ultimate-member' ) ) );
				}
			}

			$key = sanitize_text_field( $_REQUEST['key'] );

			if ( ! array_key_exists( 'custom_fields', $post_data ) || empty( $post_data['custom_fields'] ) ) {
				wp_send_json_error( esc_js( __( 'Invalid form fields', 'ultimate-member' ) ) );
			}

			$custom_fields = maybe_unserialize( $post_data['custom_fields'] );
			if ( ! is_array( $custom_fields ) || ! array_key_exists( $key, $custom_fields ) ) {
				if ( ! ( 'profile' === $mode && in_array( $key, array( 'cover_photo', 'profile_photo' ), true ) ) ) {
					wp_send_json_error( esc_js( __( 'Invalid field metakey', 'ultimate-member' ) ) );
				}
			}

			if ( empty( $custom_fields[ $key ]['crop'] ) && ! in_array( $key, array( 'cover_photo', 'profile_photo' ), true ) ) {
				wp_send_json_error( esc_js( __( 'This field doesn\'t support image crop', 'ultimate-member' ) ) );
			}

			if ( 'profile' === $mode ) {
				if ( in_array( $key, array( 'cover_photo', 'profile_photo' ), true ) ) {
					if ( 'profile_photo' === $key ) {
						$disable_photo_uploader = empty( $post_data['use_custom_settings'] ) ? UM()->options()->get( 'disable_profile_photo_upload' ) : $post_data['disable_photo_upload'];
						if ( $disable_photo_uploader ) {
							wp_send_json_error( esc_js( __( 'You have no permission to edit this field', 'ultimate-member' ) ) );
						}
					} else {
						$cover_enabled_uploader = empty( $post_data['use_custom_settings'] ) ? UM()->options()->get( 'profile_cover_enabled' ) : $post_data['cover_enabled'];
						if ( ! $cover_enabled_uploader ) {
							wp_send_json_error( esc_js( __( 'You have no permission to edit this field', 'ultimate-member' ) ) );
						}
					}
				} elseif ( ! um_can_edit_field( $custom_fields[ $key ] ) ) {
					wp_send_json_error( esc_js( __( 'You have no permission to edit this field', 'ultimate-member' ) ) );
				}
			}

			$src        = esc_url_raw( $_REQUEST['src'] );
			$image_path = um_is_file_owner( $src, $user_id, true );
			if ( ! $image_path ) {
				wp_send_json_error( esc_js( __( 'Invalid file ownership', 'ultimate-member' ) ) );
			}

			$coord = sanitize_text_field( $_REQUEST['coord'] );

			UM()->uploader()->replace_upload_dir = true;

			$output = UM()->uploader()->resize_image( $image_path, $src, $key, $user_id, $coord );

			UM()->uploader()->replace_upload_dir = false;

			delete_option( "um_cache_userdata_{$user_id}" );
			// phpcs:enable WordPress.Security.NonceVerification -- verified by the `check_ajax_nonce()`
			wp_send_json_success( $output );
		}

		/**
		 * Image upload by AJAX
		 *
		 * @throws Exception
		 */
		public function ajax_image_upload() {
			$ret['error'] = null;
			$ret          = array();

			if ( empty( $_POST['key'] ) ) {
				$ret['error'] = esc_html__( 'Invalid image key', 'ultimate-member' );
				wp_send_json_error( $ret );
			}

			$id      = sanitize_text_field( $_POST['key'] );
			$user_id = empty( $_POST['user_id'] ) ? null : absint( $_POST['user_id'] );

			/**
			 * Filters the custom validation marker for 3rd-party uploader.
			 *
			 * @param {bool}   $custom_validation Custom validation marker. Is null by default. Keep null for UM core validation.
			 * @param {string} $id                Uploader field key.
			 * @param {int}    $user_id           User ID.
			 *
			 * @return {bool} Custom validation marker.
			 *
			 * @since 2.9.1
			 * @hook um_image_upload_validation
			 *
			 * @example <caption>Custom validation.</caption>
			 * function my_um_image_upload_validation( $custom_validation, $id, $user_id ) {
			 *     // your code here
			 *     $ret['error'] = esc_html__( 'Error code', 'ultimate-member' );
			 *     wp_send_json_error( $ret );
			 *     return true;
			 * }
			 * add_filter( 'um_image_upload_validation', 'my_um_image_upload_validation', 10, 3 );
			 */
			$custom_validation = apply_filters( 'um_image_upload_validation', null, $id, $user_id );
			if ( is_null( $custom_validation ) ) {
				/**
				 * Filters image upload checking nonce.
				 *
				 * @param {bool} $verify_nonce Verify nonce marker. Default true.
				 *
				 * @return {bool} Verify nonce marker.
				 *
				 * @since 1.3.x
				 * @hook um_image_upload_nonce
				 *
				 * @example <caption>Disable checking nonce on image upload.</caption>
				 * function my_image_upload_nonce( $verify_nonce ) {
				 *     // your code here
				 *     $verify_nonce = false;
				 *     return $verify_nonce;
				 * }
				 * add_filter( 'um_image_upload_nonce', 'my_image_upload_nonce' );
				 */
				$um_image_upload_nonce = apply_filters( 'um_image_upload_nonce', true );
				if ( $um_image_upload_nonce ) {
					$timestamp = absint( $_POST['timestamp'] );
					$nonce     = sanitize_text_field( $_POST['_wpnonce'] );
					if ( ! wp_verify_nonce( $nonce, "um_upload_nonce-{$timestamp}" ) && is_user_logged_in() ) {
						// This nonce is not valid.
						$ret['error'] = esc_html__( 'Invalid nonce', 'ultimate-member' );
						wp_send_json_error( $ret );
					}
				}

				if ( $user_id && is_user_logged_in() && ! UM()->roles()->um_current_user_can( 'edit', $user_id ) ) {
					$ret['error'] = esc_html__( 'You have no permission to edit this user', 'ultimate-member' );
					wp_send_json_error( $ret );
				}

				if ( $user_id && ! is_user_logged_in() ) {
					$ret['error'] = esc_html__( 'Please login to edit this user', 'ultimate-member' );
					wp_send_json_error( $ret );
				}

				$form_id = absint( $_POST['set_id'] );
				$mode    = sanitize_key( $_POST['set_mode'] );

				UM()->fields()->set_id   = $form_id;
				UM()->fields()->set_mode = $mode;

				if ( ! is_user_logged_in() && 'profile' === $mode ) {
					$ret['error'] = esc_html__( 'You have no permission to edit user profile', 'ultimate-member' );
					wp_send_json_error( $ret );
				}

				if ( null !== $user_id && 'register' === $mode ) {
					$ret['error'] = esc_html__( 'User has to be empty on registration', 'ultimate-member' );
					wp_send_json_error( $ret );
				}

				$form_post = get_post( $form_id );
				// Invalid post ID. Maybe post doesn't exist.
				if ( empty( $form_post ) ) {
					$ret['error'] = esc_html__( 'Invalid form ID', 'ultimate-member' );
					wp_send_json_error( $ret );
				}

				if ( 'um_form' !== $form_post->post_type ) {
					$ret['error'] = esc_html__( 'Invalid form post type', 'ultimate-member' );
					wp_send_json_error( $ret );
				}

				$form_status = get_post_status( $form_id );
				if ( 'publish' !== $form_status ) {
					$ret['error'] = esc_html__( 'Invalid form status', 'ultimate-member' );
					wp_send_json_error( $ret );
				}

				$post_data = UM()->query()->post_data( $form_id );
				if ( ! array_key_exists( 'mode', $post_data ) || $mode !== $post_data['mode'] ) {
					$ret['error'] = esc_html__( 'Invalid form type', 'ultimate-member' );
					wp_send_json_error( $ret );
				}

				// For profiles only.
				if ( 'profile' === $mode && ! empty( $post_data['use_custom_settings'] ) && ! empty( $post_data['role'] ) ) {
					// Option "Apply custom settings to this form". Option "Make this profile form role-specific".
					// Show the first Profile Form with role selected, don't show profile forms below the page with other role-specific setting.
					$current_user_roles = UM()->roles()->get_all_user_roles( $user_id );
					if ( empty( $current_user_roles ) ) {
						$ret['error'] = esc_html__( 'You have no permission to edit this user through this form', 'ultimate-member' );
						wp_send_json_error( $ret );
					}

					$post_data['role'] = maybe_unserialize( $post_data['role'] );

					if ( is_array( $post_data['role'] ) ) {
						if ( ! count( array_intersect( $post_data['role'], $current_user_roles ) ) ) {
							$ret['error'] = esc_html__( 'You have no permission to edit this user through this form', 'ultimate-member' );
							wp_send_json_error( $ret );
						}
					} elseif ( ! in_array( $post_data['role'], $current_user_roles, true ) ) {
						$ret['error'] = esc_html__( 'You have no permission to edit this user through this form', 'ultimate-member' );
						wp_send_json_error( $ret );
					}
				}

				if ( ! array_key_exists( 'custom_fields', $post_data ) || empty( $post_data['custom_fields'] ) ) {
					$ret['error'] = esc_html__( 'Invalid form fields', 'ultimate-member' );
					wp_send_json_error( $ret );
				}

				$custom_fields = maybe_unserialize( $post_data['custom_fields'] );
				if ( ! is_array( $custom_fields ) || ! array_key_exists( $id, $custom_fields ) ) {
					if ( ! ( 'profile' === $mode && in_array( $id, array( 'cover_photo', 'profile_photo' ), true ) ) ) {
						$ret['error'] = esc_html__( 'Invalid field metakey', 'ultimate-member' );
						wp_send_json_error( $ret );
					}
				}

				if ( 'profile' === $mode ) {
					if ( in_array( $id, array( 'cover_photo', 'profile_photo' ), true ) ) {
						if ( 'profile_photo' === $id ) {
							$disable_photo_uploader = empty( $post_data['use_custom_settings'] ) ? UM()->options()->get( 'disable_profile_photo_upload' ) : $post_data['disable_photo_upload'];
							if ( $disable_photo_uploader ) {
								$ret['error'] = esc_html__( 'You have no permission to edit this field', 'ultimate-member' );
								wp_send_json_error( $ret );
							}
						} else {
							$cover_enabled_uploader = empty( $post_data['use_custom_settings'] ) ? UM()->options()->get( 'profile_cover_enabled' ) : $post_data['cover_enabled'];
							if ( ! $cover_enabled_uploader ) {
								$ret['error'] = esc_html__( 'You have no permission to edit this field', 'ultimate-member' );
								wp_send_json_error( $ret );
							}
						}
					} elseif ( ! um_can_edit_field( $custom_fields[ $id ] ) ) {
						$ret['error'] = esc_html__( 'You have no permission to edit this field', 'ultimate-member' );
						wp_send_json_error( $ret );
					}
				}
			}

			if ( isset( $_FILES[ $id ]['name'] ) ) {
				if ( ! is_array( $_FILES[ $id ]['name'] ) ) {
					UM()->uploader()->replace_upload_dir = true;

					$uploaded = UM()->uploader()->upload_image( $_FILES[ $id ], $user_id, $id );

					UM()->uploader()->replace_upload_dir = false;

					if ( isset( $uploaded['error'] ) ) {
						$ret['error'] = $uploaded['error'];
					} else {
						$ret[] = $uploaded['handle_upload'];
					}
				}
			} else {
				$ret['error'] = esc_html__( 'A theme or plugin compatibility issue', 'ultimate-member' );
			}

			wp_send_json_success( $ret );
		}

		/**
		 * File upload by AJAX
		 *
		 * @throws Exception
		 */
		public function ajax_file_upload() {
			$ret['error'] = null;
			$ret          = array();

			/**
			 * Filters file upload checking nonce.
			 *
			 * @param {bool} $verify_nonce Verify nonce marker. Default true.
			 *
			 * @return {bool} Verify nonce marker.
			 *
			 * @since 1.3.x
			 * @hook um_file_upload_nonce
			 *
			 * @example <caption>Disable checking nonce on file upload.</caption>
			 * function my_file_upload_nonce( $verify_nonce ) {
			 *     // your code here
			 *     $verify_nonce = false;
			 *     return $verify_nonce;
			 * }
			 * add_filter( 'um_file_upload_nonce', 'my_file_upload_nonce' );
			 */
			$um_file_upload_nonce = apply_filters( 'um_file_upload_nonce', true );
			if ( $um_file_upload_nonce ) {
				$nonce     = sanitize_text_field( $_POST['_wpnonce'] );
				$timestamp = absint( $_POST['timestamp'] );

				if ( ! wp_verify_nonce( $nonce, 'um_upload_nonce-' . $timestamp ) && is_user_logged_in() ) {
					// This nonce is not valid.
					$ret['error'] = esc_html__( 'Invalid nonce', 'ultimate-member' );
					wp_send_json_error( $ret );
				}
			}

			$user_id = empty( $_POST['user_id'] ) ? null : absint( $_POST['user_id'] );
			if ( $user_id && is_user_logged_in() && ! UM()->roles()->um_current_user_can( 'edit', $user_id ) ) {
				$ret['error'] = esc_html__( 'You have no permission to edit this user', 'ultimate-member' );
				wp_send_json_error( $ret );
			}

			if ( $user_id && ! is_user_logged_in() ) {
				$ret['error'] = esc_html__( 'You have no permission to edit this user', 'ultimate-member' );
				wp_send_json_error( $ret );
			}

			$form_id = absint( $_POST['set_id'] );
			$mode    = sanitize_key( $_POST['set_mode'] );

			UM()->fields()->set_id   = $form_id;
			UM()->fields()->set_mode = $mode;

			if ( ! is_user_logged_in() && 'profile' === $mode ) {
				$ret['error'] = esc_html__( 'You have no permission to edit this user', 'ultimate-member' );
				wp_send_json_error( $ret );
			}

			if ( null !== $user_id && 'register' === $mode ) {
				$ret['error'] = esc_html__( 'User has to be empty on registration', 'ultimate-member' );
				wp_send_json_error( $ret );
			}

			$form_post = get_post( $form_id );
			// Invalid post ID. Maybe post doesn't exist.
			if ( empty( $form_post ) ) {
				$ret['error'] = esc_html__( 'Invalid form ID', 'ultimate-member' );
				wp_send_json_error( $ret );
			}

			if ( 'um_form' !== $form_post->post_type ) {
				$ret['error'] = esc_html__( 'Invalid form post type', 'ultimate-member' );
				wp_send_json_error( $ret );
			}

			$form_status = get_post_status( $form_id );
			if ( 'publish' !== $form_status ) {
				$ret['error'] = esc_html__( 'Invalid form status', 'ultimate-member' );
				wp_send_json_error( $ret );
			}

			$post_data = UM()->query()->post_data( $form_id );
			if ( ! array_key_exists( 'mode', $post_data ) || $mode !== $post_data['mode'] ) {
				$ret['error'] = esc_html__( 'Invalid form type', 'ultimate-member' );
				wp_send_json_error( $ret );
			}

			// For profiles only.
			if ( 'profile' === $mode && ! empty( $post_data['use_custom_settings'] ) && ! empty( $post_data['role'] ) ) {
				// Option "Apply custom settings to this form". Option "Make this profile form role-specific".
				// Show the first Profile Form with role selected, don't show profile forms below the page with other role-specific setting.
				$current_user_roles = UM()->roles()->get_all_user_roles( $user_id );
				if ( empty( $current_user_roles ) ) {
					$ret['error'] = esc_html__( 'You have no permission to edit this user through this form', 'ultimate-member' );
					wp_send_json_error( $ret );
				}

				$post_data['role'] = maybe_unserialize( $post_data['role'] );

				if ( is_array( $post_data['role'] ) ) {
					if ( ! count( array_intersect( $post_data['role'], $current_user_roles ) ) ) {
						$ret['error'] = esc_html__( 'You have no permission to edit this user through this form', 'ultimate-member' );
						wp_send_json_error( $ret );
					}
				} elseif ( ! in_array( $post_data['role'], $current_user_roles, true ) ) {
					$ret['error'] = esc_html__( 'You have no permission to edit this user through this form', 'ultimate-member' );
					wp_send_json_error( $ret );
				}
			}

			$id = sanitize_text_field( $_POST['key'] );

			if ( ! array_key_exists( 'custom_fields', $post_data ) || empty( $post_data['custom_fields'] ) ) {
				$ret['error'] = esc_html__( 'Invalid form fields', 'ultimate-member' );
				wp_send_json_error( $ret );
			}

			$custom_fields = maybe_unserialize( $post_data['custom_fields'] );
			if ( ! is_array( $custom_fields ) || ! array_key_exists( $id, $custom_fields ) ) {
				$ret['error'] = esc_html__( 'Invalid field metakey', 'ultimate-member' );
				wp_send_json_error( $ret );
			}

			if ( 'profile' === $mode && ! um_can_edit_field( $custom_fields[ $id ] ) ) {
				$ret['error'] = esc_html__( 'You have no permission to edit this field', 'ultimate-member' );
				wp_send_json_error( $ret );
			}

			if ( isset( $_FILES[ $id ]['name'] ) ) {
				if ( ! is_array( $_FILES[ $id ]['name'] ) ) {
					UM()->uploader()->replace_upload_dir = true;

					$uploaded = UM()->uploader()->upload_file( $_FILES[ $id ], $user_id, $id );

					UM()->uploader()->replace_upload_dir = false;

					if ( isset( $uploaded['error'] ) ) {
						$ret['error'] = $uploaded['error'];
					} else {
						$uploaded_file        = $uploaded['handle_upload'];
						$ret['url']           = $uploaded_file['file_info']['name'];
						$ret['icon']          = UM()->fonticons()->get_file_fonticon( $uploaded_file['file_info']['ext'] );
						$ret['icon_bg']       = UM()->fonticons()->get_file_fonticon_bg( $uploaded_file['file_info']['ext'] );
						$ret['filename']      = $uploaded_file['file_info']['basename'];
						$ret['original_name'] = $uploaded_file['file_info']['original_name'];
					}
				}
			} else {
				$ret['error'] = esc_html__( 'A theme or plugin compatibility issue', 'ultimate-member' );
			}

			wp_send_json_success( $ret );
		}

		/**
		 * Setup upload directory
		 */
		private function setup_paths() {

			$upload_dir = wp_upload_dir();

			$this->upload_basedir = $upload_dir['basedir'] . '/ultimatemember/';
			// $this->upload_baseurl = $upload_dir['baseurl'] . '/ultimatemember/';

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_upload_basedir_filter
			 * @description Change Uploads Basedir
			 * @input_vars
			 * [{"var":"$basedir","type":"string","desc":"Uploads basedir"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_upload_basedir_filter', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_filter( 'um_upload_basedir_filter', 'my_upload_basedir', 10, 1 );
			 * function my_upload_basedir( $basedir ) {
			 *     // your code here
			 *     return $basedir;
			 * }
			 * ?>
			 */
			$this->upload_basedir = apply_filters( 'um_upload_basedir_filter', $this->upload_basedir );
			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_upload_baseurl_filter
			 * @description Change Uploads Base URL
			 * @input_vars
			 * [{"var":"$baseurl","type":"string","desc":"Uploads base URL"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_upload_baseurl_filter', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_filter( 'um_upload_baseurl_filter', 'my_upload_baseurl', 10, 1 );
			 * function my_upload_baseurl( $baseurl ) {
			 *     // your code here
			 *     return $baseurl;
			 * }
			 * ?>
			 */
			// $this->upload_baseurl = apply_filters( 'um_upload_baseurl_filter', $this->upload_baseurl );

			// @note : is_ssl() doesn't work properly for some sites running with load balancers
			// Check the links for more info about this bug
			// https://codex.wordpress.org/Function_Reference/is_ssl
			// http://snippets.webaware.com.au/snippets/wordpress-is_ssl-doesnt-work-behind-some-load-balancers/
//			if ( is_ssl() || stripos( get_option( 'siteurl' ), 'https://' ) !== false
//				|| ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' ) ) {
//				$this->upload_baseurl = str_replace( 'http://', 'https://', $this->upload_baseurl );
//			}

			$this->upload_temp     = $this->upload_basedir . 'temp/';
			// $this->upload_temp_url = $this->upload_baseurl . 'temp/';

			if ( ! file_exists( $this->upload_basedir ) ) {
				$old = umask( 0 );
				@mkdir( $this->upload_basedir, 0755, true );
				umask( $old );
			}

			if ( ! file_exists( $this->upload_temp ) ) {
				$old = umask( 0 );
				@mkdir( $this->upload_temp, 0755, true );
				umask( $old );
			}
		}

		/**
		 * Get path only without file name
		 *
		 * @param $file
		 *
		 * @return string
		 */
		function path_only( $file ) {

			return trailingslashit( dirname( $file ) );
		}

		/**
		 * Process a file
		 *
		 * @param $source
		 * @param $destination
		 */
		function upload_temp_file( $source, $destination ) {

			move_uploaded_file( $source, $destination );
		}

		/**
		 * This function will delete file upload from server
		 *
		 * @param string $src
		 *
		 * @return bool
		 */
		private function delete_file( $src ) {
			if ( false !== strpos( $src, '?' ) ) {
				$splitted = explode( '?', $src );
				$src      = $splitted[0];
			}

			$is_temp = $this->is_temp_upload( $src );
			if ( $is_temp ) {
				wp_delete_file( $is_temp );
				return true;
			}

			return false;
		}

		/**
		 * Check that temp upload is valid
		 *
		 * @param string $url
		 *
		 * @return bool|string
		 */
		private function is_temp_upload( $url ) {
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

				$src = $this->upload_temp . $url[1];
				if ( ! file_exists( $src ) ) {
					return false;
				}

				return $src;
			}

			return false;
		}

		/**
		 * Remove old files
		 *
		 * @deprecated 3.0.0
		 *
		 * @param string $dir           Path to directoty.
		 * @param int|string $timestamp Unix timestamp or PHP relative time. All older files will be removed.
		 */
		public function remove_old_files( $dir, $timestamp = null ) {
			_deprecated_function( __METHOD__, '3.0.0' );
			$removed_files = array();

			if ( empty( $timestamp ) ) {
				$timestamp = strtotime( '-1 day' );
			} elseif ( is_string( $timestamp ) && ! is_numeric( $timestamp ) ) {
				$timestamp = strtotime( $timestamp );
			}

			if ( $timestamp && is_dir( $dir ) ) {

				$files = glob( $dir . '/*' );

				foreach ( (array) $files as $file ) {
					if ( in_array( wp_basename( $file ), array( '.', '..' ), true ) ) {
						continue;
					}

					if ( is_dir( $file ) ) {
						$this->remove_old_files( $file, $timestamp );
					} elseif ( is_file( $file ) ) {
						$fileatime = fileatime( $file );
						if ( $fileatime && $fileatime < (int) $timestamp ) {
							unlink( $file );
							$removed_files[] = $file;
						}
					}
				}
			}

			return $removed_files;
		}

		/**
		 * Get the list of profile/cover sizes
		 *
		 * @deprecated 3.0.0
		 * @param string $type
		 *
		 * @return array
		 */
		public function get_profile_photo_size( $type ) {
			_deprecated_function( __METHOD__, '3.0.0', 'UM()->options()->get_profile_photo_size()' );
			return UM()->options()->get_profile_photo_size( $type );
		}

		/**
		 * New user upload
		 *
		 * @param $user_id
		 * @param $source
		 * @param $key
		 *
		 * @deprecated 2.1.0
		 *
		 * @return string
		 */
		public function new_user_upload( $user_id, $source, $key ) {
			_deprecated_function( __METHOD__, '2.1.0' );
			return '';
		}

		/**
		 * Format Bytes
		 *
		 * @deprecated 2.8.7
		 * @param $size
		 * @param int $precision
		 *
		 * @return string
		 */
		public function format_bytes( $size, $precision = 1 ) {
			_deprecated_function( __METHOD__, '2.8.7', 'UM()->common()->filesystem()->format_bytes()' );
			return UM()->common()->filesystem()::format_bytes( $size, $precision );
		}

		/**
		 * Allowed image types
		 *
		 * @deprecated 3.0.0
		 *
		 * @return array
		 */
		public function allowed_image_types() {
			_deprecated_function( __METHOD__, '3.0.0' );
			return apply_filters(
				'um_allowed_image_types',
				array(
					'png'  => 'PNG',
					'jpeg' => 'JPEG',
					'jpg'  => 'JPG',
					'gif'  => 'GIF',
				)
			);
		}

		/**
		 * Allowed file types
		 * @deprecated 3.0.0
		 * @return array
		 */
		public function allowed_file_types() {
			_deprecated_function( __METHOD__, '3.0.0' );
			return apply_filters(
				'um_allowed_file_types',
				array(
					'pdf'  => 'PDF',
					'txt'  => 'Text',
					'csv'  => 'CSV',
					'doc'  => 'DOC',
					'docx' => 'DOCX',
					'odt'  => 'ODT',
					'ods'  => 'ODS',
					'xls'  => 'XLS',
					'xlsx' => 'XLSX',
					'zip'  => 'ZIP',
					'rar'  => 'RAR',
					'mp3'  => 'MP3',
					'jpg'  => 'JPG',
					'jpeg' => 'JPEG',
					'png'  => 'PNG',
					'gif'  => 'GIF',
					'eps'  => 'EPS',
					'psd'  => 'PSD',
					'tif'  => 'TIF',
					'tiff' => 'TIFF',
				)
			);
		}

		/**
		 * Remove a directory
		 * @deprecated 3.0.0
		 * @param $dir
		 */
		public function remove_dir( $dir ) {
			_deprecated_function( __METHOD__, '3.0.0', 'UM()->common()->filesystem()::remove_dir()' );
			UM()->common()->filesystem()::remove_dir( $dir );
		}

		/**
		 * Delete a main user photo.
		 *
		 * @deprecated 3.0.0
		 *
		 * @param int    $user_id
		 * @param string $type
		 */
		public function delete_core_user_photo( $user_id, $type ) {
			_deprecated_function( __METHOD__, '3.0.0', 'UM()->common()->users()->delete_photo()' );
			UM()->common()->users()->delete_photo( $user_id, $type );
		}

		/**
		 * Make a user folder for uploads
		 * @deprecated 3.0.0
		 * @param $user_id
		 */
		public function new_user( $user_id ) {
			_deprecated_function( __METHOD__, '3.0.0' );
			if ( ! file_exists( $this->upload_basedir . $user_id . '/' ) ) {
				$old = umask( 0 );
				@mkdir( $this->upload_basedir . $user_id . '/', 0755, true );
				umask( $old );
			}
		}

		/**
		 * Resize a local image
		 *
		 * @deprecated 3.0.0
		 *
		 * @param $file
		 * @param $crop
		 *
		 * @return string
		 */
		public function resize_image( $file, $crop ) {
			_deprecated_function( __METHOD__, '3.0.0' );

			$targ_x1 = $crop[0];
			$targ_y1 = $crop[1];
			$targ_x2 = $crop[2];
			$targ_y2 = $crop[3];

			$info = @getimagesize( $file );

			if ( $info['mime'] == 'image/gif' ) {

				$img_r = imagecreatefromgif( $file );
				$dst_r = imagecreatetruecolor( $targ_x2, $targ_y2 );
				imagecopy( $dst_r, $img_r, 0, 0, $targ_x1, $targ_y1, $targ_x2, $targ_y2 );
				imagegif( $dst_r, $this->path_only( $file ) . basename( $file ) );

			} elseif ( $info['mime'] == 'image/png' ) {

				$img_r = imagecreatefrompng( $file );
				$dst_r = imagecreatetruecolor( $targ_x2, $targ_y2 );
				imagealphablending( $dst_r, false );
				imagesavealpha( $dst_r, true );
				imagecopy( $dst_r, $img_r, 0, 0, $targ_x1, $targ_y1, $targ_x2, $targ_y2 );
				imagepng( $dst_r, $this->path_only( $file ) . basename( $file ) );

			} else {

				$img_r = imagecreatefromjpeg( $file );
				$dst_r = imagecreatetruecolor( $targ_x2, $targ_y2 );
				imagecopy( $dst_r, $img_r, 0, 0, $targ_x1, $targ_y1, $targ_x2, $targ_y2 );
				imagejpeg( $dst_r, $this->path_only( $file ) . basename( $file ), 100 );

			}

			$split = explode( '/ultimatemember/temp/', $file );
			return $this->upload_temp_url . $split[1];
		}

		/**
		 * Process a temp upload
		 * @deprecated 3.0.0
		 * @param $source
		 * @param $destination
		 * @param int $quality
		 *
		 * @return string
		 */
		public function new_image_upload_temp( $source, $destination, $quality = 100 ) {
			_deprecated_function( __METHOD__, '3.0.0' );
			$unique_dir = $this->unique_dir();

			$this->make_dir( $unique_dir['dir'] );

			$info = $this->create_and_copy_image( $source, $unique_dir['dir'] . $destination, $quality );

			$url = $unique_dir['url'] . $destination;

			return $url;
		}

		/**
		 * Process an image
		 * @deprecated 3.0.0
		 *
		 * @param $source
		 * @param $destination
		 * @param int $quality
		 *
		 * @return array
		 */
		private function create_and_copy_image( $source, $destination, $quality = 100 ) {
			_deprecated_function( __METHOD__, '3.0.0' );
			$info = @getimagesize( $source );

			if ( $info['mime'] == 'image/jpeg' ) {

				$image = imagecreatefromjpeg( $source );

			} elseif ( $info['mime'] == 'image/gif' ) {

				$image = imagecreatefromgif( $source );

			} elseif ( $info['mime'] == 'image/png' ) {

				$image = imagecreatefrompng( $source );
				imagealphablending( $image, false );
				imagesavealpha( $image, true );

			}

			list($w, $h) = @getimagesize( $source );
			if ( $w > UM()->options()->get( 'image_max_width' ) ) {

				$ratio = round( $w / $h, 2 );
				$new_w = UM()->options()->get( 'image_max_width' );
				$new_h = round( $new_w / $ratio, 2 );

				if ( $info['mime'] == 'image/jpeg' || $info['mime'] == 'image/gif' ) {

					$image_p = imagecreatetruecolor( $new_w, $new_h );
					imagecopyresampled( $image_p, $image, 0, 0, 0, 0, $new_w, $new_h, $w, $h );
					$image_p = $this->fix_image_orientation( $image_p, $source );

				} elseif ( $info['mime'] == 'image/png' ) {

					$srcImage    = $image;
					$targetImage = imagecreatetruecolor( $new_w, $new_h );
					imagealphablending( $targetImage, false );
					imagesavealpha( $targetImage, true );
					imagecopyresampled( $targetImage, $srcImage, 0, 0, 0, 0, $new_w, $new_h, $w, $h );

				}

				if ( $info['mime'] == 'image/jpeg' ) {
					$has_copied = imagejpeg( $image_p, $destination, $quality );
				} elseif ( $info['mime'] == 'image/gif' ) {
					$has_copied = imagegif( $image_p, $destination );
				} elseif ( $info['mime'] == 'image/png' ) {
					$has_copied = imagepng( $targetImage, $destination, 0, PNG_ALL_FILTERS );
				}

				$info['um_has_max_width'] = 'custom';
				$info['um_has_copied']    = $has_copied ? 'yes' : 'no';

			} else {

				$image = $this->fix_image_orientation( $image, $source );

				if ( $info['mime'] == 'image/jpeg' ) {
					$has_copied = imagejpeg( $image, $destination, $quality );
				} elseif ( $info['mime'] == 'image/gif' ) {
					$has_copied = imagegif( $image, $destination );
				} elseif ( $info['mime'] == 'image/png' ) {
					$has_copied = imagepng( $image, $destination, 0, PNG_ALL_FILTERS );
				}

				$info['um_has_max_width'] = 'default';
				$info['um_has_copied']    = $has_copied ? 'yes' : 'no';
			}

			return $info;
		}

		/**
		 * Fix image orientation
		 * @deprecated 3.0.0
		 * @param $rotate
		 * @param $source
		 *
		 * @return resource
		 */
		public function fix_image_orientation( $rotate, $source ) {
			_deprecated_function( __METHOD__, '3.0.0', 'UM()->uploader()->fix_image_orientation()' );
			return UM()->uploader()->fix_image_orientation( $rotate, $source );
		}

		/**
		 * Get extension icon
		 *
		 * @deprecated 3.0.0
		 * @param string $extension
		 *
		 * @return string
		 */
		public function get_fonticon_by_ext( $extension ) {
			_deprecated_function( __METHOD__, '3.0.0', 'UM()->fonticons()->get_file_fonticon()' );
			return UM()->fonticons()->get_file_fonticon( $extension );
		}

		/**
		 * Get extension icon background
		 *
		 * @deprecated 3.0.0
		 * @param string $extension
		 *
		 * @return string
		 */
		public function get_fonticon_bg_by_ext( $extension ) {
			_deprecated_function( __METHOD__, '3.0.0', 'UM()->fonticons()->get_file_fonticon_bg()' );
			return UM()->fonticons()->get_file_fonticon_bg( $extension );
		}

		/**
		 * If a value exists in comma seperated list
		 *
		 * @deprecated 3.0.0
		 *
		 * @param $value
		 * @param $array
		 *
		 * @return bool
		 */
		public function in_array( $value, $array ) {
			_deprecated_function( __METHOD__, '3.0.0' );
			if ( in_array( $value, explode( ',', $array ) ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Get file data
		 * @deprecated 3.0.0
		 * @param string $file
		 *
		 * @return array
		 */
		public function get_file_data( $file ) {
			_deprecated_function( __METHOD__, '3.0.0' );

			$array['size'] = filesize( $file );
			return $array;
		}

		/**
		 * Get image data
		 * @deprecated 3.0.0
		 * @param string $file
		 *
		 * @return array
		 */
		public function get_image_data( $file ) {
			_deprecated_function( __METHOD__, '3.0.0' );

			$finfo = finfo_open( FILEINFO_MIME_TYPE );

			$mime_type = finfo_file( $finfo, $file );

			if ( function_exists( 'exif_imagetype' ) ) {

				$array_exif_image_mimes = array( IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG );

				$allowed_types = apply_filters( 'um_image_upload_allowed_exif_mimes', $array_exif_image_mimes );

				if ( ! in_array( @exif_imagetype( $file ), $allowed_types ) ) {

					$array['invalid_image'] = true;

					return $array;
				}
			} else {

				$array_image_mimes = array( 'image/jpeg', 'image/png', 'image/gif' );

				$allowed_types = apply_filters( 'um_image_upload_allowed_mimes', $array_image_mimes );

				if ( ! in_array( $mime_type, $allowed_types ) ) {

					$array['invalid_image'] = true;

					return $array;
				}
			}

			$array['size'] = filesize( $file );

			$image_data = @getimagesize( $file );

			$array['image'] = $image_data;

			$array['invalid_image'] = false;

			list($width, $height, $type, $attr) = $image_data;

			$array['width'] = $width;

			$array['height'] = $height;

			$array['ratio'] = $width / $height;

			$array['extension'] = $this->get_extension_by_mime_type( $mime_type );

			return $array;
		}

		/**
		 * Process a temp upload for files
		 *
		 * @param $source
		 * @param $destination
		 * @deprecated 3.0.0
		 *
		 * @return string
		 */
		public function new_file_upload_temp( $source, $destination ) {
			_deprecated_function( __METHOD__, '3.0.0' );

			$unique_dir = $this->unique_dir();

			$this->make_dir( $unique_dir['dir'] );

			$this->upload_temp_file( $source, $unique_dir['dir'] . $destination );

			$url = $unique_dir['url'] . $destination;

			return $url;
		}

		/**
		 * Make a Folder
		 *
		 * @param $dir
		 *
		 * @deprecated 3.0.0
		 */
		public function make_dir( $dir ) {
			_deprecated_function( __METHOD__, '3.0.0' );

			$old = umask( 0 );
			@mkdir( $dir, 0755, true );
			umask( $old );
		}

		/**
		 * Get extension by mime type
		 * @deprecated 3.0.0
		 * @param $mime
		 *
		 * @return mixed
		 */
		public function get_extension_by_mime_type( $mime ) {
			_deprecated_function( __METHOD__, '3.0.0' );

			$split = explode( '/', $mime );
			return $split[1];
		}

		/**
		 * Generate unique temp directory
		 * @deprecated 3.0.0
		 *
		 * @return mixed
		 */
		public function unique_dir() {
			_deprecated_function( __METHOD__, '3.0.0' );

			$unique_number = UM()->validation()->generate();
			$array['dir']  = $this->upload_temp . $unique_number . '/';
			$array['url']  = $this->upload_temp_url . $unique_number . '/';
			return $array;
		}

		/**
		 * File download link generate
		 * @deprecated 3.0.0
		 * @param int $form_id
		 * @param string $field_key
		 * @param int $user_id
		 *
		 * @return string
		 */
		public function get_download_link( $form_id, $field_key, $user_id ) {
			_deprecated_function( __METHOD__, '3.0.0', 'UM()->fields()->get_download_link()' );
			return UM()->fields()->get_download_link( $form_id, $field_key, $user_id );
		}
	}
}
