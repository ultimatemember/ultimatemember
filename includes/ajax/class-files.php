<?php
namespace um\ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Files
 *
 * @package um\ajax
 */
class Files {

	/**
	 * Files constructor.
	 */
	public function __construct() {
		if ( defined( 'UM_DEV_MODE' ) && UM_DEV_MODE ) {
			add_action( 'wp_ajax_um_delete_temp_file', array( $this, 'delete_temp_file' ) );
			add_action( 'wp_ajax_nopriv_um_delete_temp_file', array( $this, 'delete_temp_file' ) );

			add_action( 'wp_ajax_um_upload', array( $this, 'upload_file' ) );
			add_action( 'wp_ajax_nopriv_um_upload', array( $this, 'upload_file' ) );

			add_action( 'um_upload_file_validation', array( $this, 'upload_validation' ), 10, 5 );
			add_action( 'um_upload_file_temp_uploaded', array( $this, 'temp_uploaded' ), 10, 2 );
			add_filter( 'um_upload_file_fileinfo', array( $this, 'temp_fileinfo' ), 10, 2 );

			add_filter( 'um_upload_file_fileinfo', array( $this, 'uploader_hash' ), 10, 2 );
			add_filter( 'um_upload_file_fileinfo', array( $this, 'field_image_fileinfo' ), 10, 2 );

			add_action( 'wp_ajax_nopriv_um_crop_image', array( $this, 'crop_image' ) ); // Enabled image resize on registration form.
			add_action( 'wp_ajax_um_crop_image', array( $this, 'crop_image' ) );
		}
	}

	/**
	 * Common upload file handler. Default result file in temp directory with unique name.
	 * @todo make delete temp file function secure via $_COOKIE
	 */
	public function delete_temp_file() {
		if ( empty( $_REQUEST['name'] ) ) {
			wp_send_json_error( __( 'Unknown file.', 'ultimate-member' ) );
		}

		if ( empty( $_REQUEST['nonce'] ) || ! wp_verify_nonce( $_REQUEST['nonce'], 'um_delete_temp_file' . $_REQUEST['name'] ) ) {
			wp_send_json_error( __( 'Invalid nonce.', 'ultimate-member' ) );
		}

		$filename = sanitize_file_name( $_REQUEST['name'] );
		$path     = wp_normalize_path( UM()->common()->filesystem()->get_tempdir() . '/' . $filename );
		if ( ! file_exists( $path ) ) {
			wp_send_json_error( __( 'Invalid file.', 'ultimate-member' ) );
		}

		if ( ! unlink( $path ) ) {
			wp_send_json_error( __( 'Cannot remove file from server.', 'ultimate-member' ) );
		}

		wp_send_json_success();
	}

	/**
	 * Returns possible handlers for upload. Trigger invalid handler error base on this.
	 *
	 * @return array
	 */
	private static function get_possible_handlers() {
		$handlers = array(
			'common-upload',
			'field-file',
			'field-image',
//			'upload-image',
		);
		if ( is_user_logged_in() ) {
			if ( ! UM()->options()->get( 'disable_profile_photo_upload' ) ) {
				$handlers[] = 'upload-avatar';
			}
		} else {
//			$handlers[] = 'nopriv-upload';
		}

		return apply_filters( 'um_upload_handlers', $handlers );
	}

	private static function get_mimes( $handler ) {
		$mimes = wp_get_mime_types(); // for common use set the mimes from WP native array.
		if ( 'upload-avatar' === $handler ) {
			// Check the avatar file format.
			$mimes = UM()->common()->filesystem()::image_mimes( 'allowed' );
		} elseif ( 'field-file' === $handler || 'field-image' === $handler ) {

		}
		return apply_filters( 'um_upload_mimes', $mimes, $handler );
	}

	public function upload_validation( &$error, $handler, $chunks, $filename, $unique_name ) {
		if ( ! empty( $error ) ) {
			return;
		}

		if ( 'upload-avatar' === $handler ) {
			if ( ! wp_verify_nonce( sanitize_key( $_REQUEST['nonce'] ), 'um_upload_' . $handler ) ) {
				// This nonce is not valid.
				$error = esc_html__( 'Invalid nonce.', 'ultimate-member' );
			}

			if ( ! array_key_exists( 'user_id', $_REQUEST ) ) {
				$error = esc_html__( 'No user to set avatar.', 'ultimate-member' );
			}

			$user_id = absint( $_REQUEST['user_id'] );

			if ( ! UM()->roles()->um_current_user_can( 'edit', $user_id ) ) {
				$error = esc_html__( 'You can not edit this user.', 'ultimate-member' );
			}
		} elseif ( 'field-file' === $handler || 'field-image' === $handler ) {
			if ( ! wp_verify_nonce( sanitize_key( $_REQUEST['nonce'] ), 'um_upload_' . $handler ) ) {
				// This nonce is not valid.
				$error = esc_html__( 'Invalid nonce.', 'ultimate-member' );
			}

			if ( ! array_key_exists( 'user_id', $_REQUEST ) ) {
				$error = esc_html__( 'No user to set value.', 'ultimate-member' );
			}

			$user_id = empty( $_REQUEST['user_id'] ) ? null : absint( $_REQUEST['user_id'] );
			if ( $user_id && is_user_logged_in() && ! UM()->roles()->um_current_user_can( 'edit', $user_id ) ) {
				$error = esc_html__( 'You have no permission to edit this user', 'ultimate-member' );
			}

			if ( $user_id && ! is_user_logged_in() ) {
				$error = esc_html__( 'You have no permission to edit this user', 'ultimate-member' );
			}

			if ( ! array_key_exists( 'form_id', $_REQUEST ) ) {
				$error = esc_html__( 'No form to set value.', 'ultimate-member' );
			}

			$form_id   = absint( $_REQUEST['form_id'] );
			$form_post = get_post( $form_id );
			// Invalid post ID. Maybe post doesn't exist.
			if ( empty( $form_post ) ) {
				$error = esc_html__( 'Invalid form ID', 'ultimate-member' );
			}

			if ( 'um_form' !== $form_post->post_type ) {
				$error = esc_html__( 'Invalid form post type', 'ultimate-member' );
			}

			$form_status = get_post_status( $form_id );
			if ( 'publish' !== $form_status ) {
				$error = esc_html__( 'Invalid form status', 'ultimate-member' );
			}

			$post_data = UM()->query()->post_data( $form_id );
			if ( ! array_key_exists( 'mode', $post_data ) ) {
				$error = esc_html__( 'Invalid form type', 'ultimate-member' );
			}

			$mode = $post_data['mode'];
			if ( ! is_user_logged_in() && 'profile' === $mode ) {
				$error = esc_html__( 'You have no permission to edit this user', 'ultimate-member' );
			}

			if ( null !== $user_id && 'register' === $mode ) {
				$error = esc_html__( 'User has to be empty on registration', 'ultimate-member' );
			}

			UM()->fields()->set_id   = $form_id;
			UM()->fields()->set_mode = $mode;

			// For profiles only.
			if ( 'profile' === $mode && ! empty( $post_data['use_custom_settings'] ) && ! empty( $post_data['role'] ) ) {
				// Option "Apply custom settings to this form". Option "Make this profile form role-specific".
				// Show the first Profile Form with role selected, don't show profile forms below the page with other role-specific setting.
				$current_user_roles = UM()->roles()->get_all_user_roles( $user_id );
				if ( empty( $current_user_roles ) ) {
					$error = esc_html__( 'You have no permission to edit this user through this form', 'ultimate-member' );
				}

				$post_data['role'] = maybe_unserialize( $post_data['role'] );

				if ( is_array( $post_data['role'] ) ) {
					if ( ! count( array_intersect( $post_data['role'], $current_user_roles ) ) ) {
						$error = esc_html__( 'You have no permission to edit this user through this form', 'ultimate-member' );
					}
				} elseif ( ! in_array( $post_data['role'], $current_user_roles, true ) ) {
					$error = esc_html__( 'You have no permission to edit this user through this form', 'ultimate-member' );
				}
			}

			if ( ! array_key_exists( 'field_id', $_REQUEST ) ) {
				$error = esc_html__( 'No field to set value.', 'ultimate-member' );
			}

			$id = sanitize_text_field( $_REQUEST['field_id'] );

			if ( ! array_key_exists( 'custom_fields', $post_data ) || empty( $post_data['custom_fields'] ) ) {
				$error = esc_html__( 'Invalid form fields', 'ultimate-member' );
			}

			$custom_fields = maybe_unserialize( $post_data['custom_fields'] );
			if ( ! is_array( $custom_fields ) || ! array_key_exists( $id, $custom_fields ) ) {
				$error = esc_html__( 'Invalid field metakey', 'ultimate-member' );
			}

			if ( 'profile' === $mode && ! um_can_edit_field( $custom_fields[ $id ] ) ) {
				$error = esc_html__( 'You have no permission to edit this field', 'ultimate-member' );
			}
		}

		$mimes = self::get_mimes( $handler );
		if ( empty( $mimes ) ) {
			$error = __( 'No mime types for this uploader.', 'ultimate-member' );
		}

		$image_type = wp_check_filetype( $filename, $mimes );
		if ( ! $image_type['ext'] ) {
			$error = __( 'Wrong filetype.', 'ultimate-member' );
		}

		if ( isset( $_COOKIE['um-current-upload-filename'] ) && $chunks > 1 ) {
			// Double check filetype to avoid break from COOKIE while upload chunks of the big file.
			$image_type = wp_check_filetype( $unique_name, $mimes );
			if ( ! $image_type['ext'] ) {
				$error = __( 'Wrong filetype.', 'ultimate-member' );
			}
		}
	}

	public function temp_uploaded( $handler, $fileinfo ) {
		if ( 'upload-avatar' === $handler ) {
			$user_id = absint( $_REQUEST['user_id'] );
			update_user_meta( $user_id, 'um_temp_profile_photo', $fileinfo );
		}
	}

	public function temp_fileinfo( $fileinfo, $handler ) {
		if ( 'upload-avatar' === $handler ) {
			$user_id = absint( $_REQUEST['user_id'] );
			ob_start();
			?>
			<div class="um-modal-crop-wrapper" data-crop="square" data-ratio="1" data-min_width="256" data-min_height="256">
				<img src="<?php echo esc_url( $fileinfo['url'] ); ?>" class="um-profile-photo-crop fusion-lazyload-ignore" alt="" />
			</div>
			<div class="um-modal-buttons-wrapper">
				<?php
				echo wp_kses(
					UM()->frontend()::layouts()::button(
						__( 'Apply', 'ultimate-member' ),
						array(
							'type'    => 'button',
							'design'  => 'primary',
							'size'    => 'm',
							'classes' => array( 'um-apply-avatar-crop' ),
							'data'    => array(
								'user_id' => $user_id,
								'nonce'   => wp_create_nonce( 'um_upload_profile_photo_apply' ),
							),
						)
					),
					UM()->get_allowed_html( 'templates' )
				);
				echo wp_kses(
					UM()->frontend()::layouts()::button(
						__( 'Cancel', 'ultimate-member' ),
						array(
							'type'    => 'button',
							'design'  => 'secondary-gray',
							'size'    => 'm',
							'classes' => array( 'um-modal-avatar-decline' ),
							'data'    => array(
								'user_id' => $user_id,
								'nonce'   => wp_create_nonce( 'um_upload_profile_photo_decline' ),
							),
						)
					),
					UM()->get_allowed_html( 'templates' )
				);
				echo wp_kses( UM()->frontend()::layouts()::ajax_loader( 's' ), UM()->get_allowed_html( 'templates' ) );
				?>
			</div>
			<?php
			$fileinfo['modal_content'] = ob_get_clean();
			$fileinfo['modal_content'] = UM()->ajax()->esc_html_spaces( $fileinfo['modal_content'] );
		}

		return $fileinfo;
	}

	/**
	 * Generate unique filename.
	 *
	 * @param string $dir
	 * @param string $name
	 * @param string $ext
	 *
	 * @return string
	 *
	 * @since 3.0.0
	 */
	public function unique_filename( $dir, $name, $ext ) {
		$hashed = hash( 'ripemd160', time() . wp_rand( 10, 1000 ) . $name );
		return "temp_{$hashed}{$ext}";
	}

	/**
	 * Common upload file handler. Default result file in temp directory with unique name.
	 */
	public function upload_file() {
		$error = null;
// For error debug
//		wp_send_json(
//			array(
//				'OK'   => 0,
//				'info' => '797979',
//			)
//		);

		if ( empty( $_REQUEST['nonce'] ) ) {
			$error = __( 'Nonce is required.', 'ultimate-member' );
		}

		if ( empty( $_REQUEST['handler'] ) ) {
			$error = __( 'Unknown handler.', 'ultimate-member' );
		}

		$handler = sanitize_key( $_REQUEST['handler'] );
		if ( ! in_array( $handler, self::get_possible_handlers(), true ) ) {
			$error = __( 'Invalid handler.', 'ultimate-member' );
		}

		if ( empty( $_FILES ) || $_FILES['file']['error'] ) {
			$error = __( 'Failed to move uploaded file.', 'ultimate-member' );
		}

		$files  = array();
		$chunk  = ! empty( $_REQUEST['chunk'] ) ? absint( $_REQUEST['chunk'] ) : 0;
		$chunks = ! empty( $_REQUEST['chunks'] ) ? absint( $_REQUEST['chunks'] ) : 0;

		// Stop uploader if no chunks
		if ( empty( $chunks ) ) {
			$error = __( 'Empty chunks.', 'ultimate-member' );
		}

		// Get a file name
		if ( isset( $_REQUEST['name'] ) ) {
			$filename = sanitize_file_name( $_REQUEST['name'] );
		} elseif ( ! empty( $_FILES ) ) {
			$filename = sanitize_file_name( $_FILES['file']['name'] );
		} else {
			$filename = uniqid( 'file_' );
		}

		if ( isset( $_COOKIE['um-current-upload-filename'] ) && $chunks > 1 ) {
			$unique_name = sanitize_file_name( $_COOKIE['um-current-upload-filename'] );
		} else {
			$unique_name = wp_unique_filename( UM()->common()->filesystem()->get_tempdir(), $filename, array( &$this, 'unique_filename' ) );
		}

		do_action_ref_array( 'um_upload_file_validation', array( &$error, $handler, $chunks, $filename, $unique_name ) );

		if ( $error ) {
			wp_send_json(
				array(
					'OK'   => 0,
					'info' => $error,
				)
			);
		}

		UM()->common()->filesystem()->clear_temp_dir();
		// Save unique name to the cookies.
		if ( ! isset( $_COOKIE['um-current-upload-filename'] ) && $chunks > 1 ) {
			UM()->setcookie( 'um-current-upload-filename', $unique_name );
		}

		$filepath = wp_normalize_path( UM()->common()->filesystem()->get_tempdir() . '/' . $unique_name );

		// phpcs:disable WordPress.WP.AlternativeFunctions -- for directly fopen, fwrite, fread, fclose functions using
		// phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged -- for silenced fopen, fwrite, fread, fclose functions running

		// Open temp file
		$out = @fopen( "{$filepath}.part", 0 === $chunk ? 'wb' : 'ab' );

		if ( ! $out ) {
			wp_send_json(
				array(
					'OK'   => 0,
					'info' => esc_html__( 'Failed to open output stream.', 'ultimate-member' ),
				)
			);
		}

		// Read binary input stream and append it to temp file
		$in = @fopen( $_FILES['file']['tmp_name'], 'rb' );

		if ( ! $in ) {
			wp_send_json(
				array(
					'OK'   => 0,
					'info' => esc_html__( 'Failed to open input stream.', 'ultimate-member' ),
				)
			);
		}

		// phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition -- reading buffer here
		while ( $buff = fread( $in, 4096 ) ) {
			fwrite( $out, $buff );
		}

		fclose( $in );
		fclose( $out );
		unlink( $_FILES['file']['tmp_name'] );

		// phpcs:enable WordPress.WP.AlternativeFunctions
		// phpcs:enable WordPress.PHP.NoSilencedErrors.Discouraged

		// Check if file has been uploaded
		if ( $chunk === $chunks - 1 ) {
			// Strip the temp .part suffix off
			rename( "{$filepath}.part", $filepath ); // Strip the temp .part suffix off

			$name_saved = wp_basename( $filepath );

			$fileinfo                 = ! empty( $_FILES['file'] ) ? wp_unslash( $_FILES['file'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- don't need to sanitize
			$fileinfo['file']         = $filepath;
			$fileinfo['name_loaded']  = $filename;
			$fileinfo['name_saved']   = $name_saved;
			$fileinfo['hash']         = md5( $fileinfo['name_saved'] . '_um_uploader_security_salt' );
			$fileinfo['path']         = wp_normalize_path( UM()->common()->filesystem()->get_tempdir() . '/' . $fileinfo['name_saved'] );
			$fileinfo['url']          = UM()->common()->filesystem()->get_tempurl() . '/' . $fileinfo['name_saved'];
			$fileinfo['size']         = filesize( $fileinfo['file'] );
			$fileinfo['size_format']  = size_format( $fileinfo['size'] );
			$fileinfo['time']         = gmdate( 'Y-m-d H:i:s', filemtime( $fileinfo['file'] ) );
			$fileinfo['delete_nonce'] = wp_create_nonce( 'um_delete_temp_file' . $name_saved );

			$fileinfo = apply_filters( 'um_upload_file_fileinfo', $fileinfo, $handler );

			$files[] = $fileinfo;

			do_action( 'um_upload_file_temp_uploaded', $handler, $fileinfo );

			// @todo using $_COOKIE['um-temp-uploads'] for security links.
			// Set temp file to cookies for access via secure link.
			$temp_uploads = isset( $_COOKIE['um-temp-uploads'] ) ? maybe_unserialize( $_COOKIE['um-temp-uploads'] ) : array();
			if ( is_array( $temp_uploads ) ) {
				$temp_uploads[] = $fileinfo['hash'];
			}
			UM()->setcookie( 'um-temp-uploads', maybe_serialize( $temp_uploads ) );

			// Flush this cookie because temp file is uploaded successfully.
			UM()->setcookie( 'um-current-upload-filename', false );

			wp_send_json_success( $files );
		} else {
			// Internal response for uploader while uploading chunks.
			wp_send_json(
				array(
					'OK'   => 1,
					'info' => esc_html__( 'Chunk has been uploaded successfully.', 'ultimate-member' ),
				)
			);
		}
	}

	public function uploader_hash( $fileinfo, $handler ) {
		if ( 'field-image' !== $handler && 'field-file' !== $handler ) {
			return $fileinfo;
		}

		$user_id = empty( $_REQUEST['user_id'] ) ? null : absint( $_REQUEST['user_id'] );
		$form_id = absint( $_REQUEST['form_id'] );

		$fileinfo['hash'] = md5( $fileinfo['name_saved'] . $user_id . $form_id . '_um_uploader_security_salt' . NONCE_KEY );
		return $fileinfo;
	}

	public function field_image_fileinfo( $fileinfo, $handler ) {
		if ( 'field-image' !== $handler ) {
			return $fileinfo;
		}

		$crop = ! empty( $_REQUEST['crop'] ) ? sanitize_key( wp_unslash( $_REQUEST['crop'] ) ) : 0;
		if ( empty( $crop ) ) {
			// @todo with preview
//			$fileinfo['lazy_image'] = wp_kses(
//				'<a href="#" class="um-photo-modal" data-src="' . esc_url( $fileinfo['url'] ) . '" title="Preview Image Upload">' .
//				UM()->frontend()::layouts()::lazy_image(
//					$fileinfo['url'],
//					array(
//						'width' => '100%',
//						'alt'   => __( 'Image Upload', 'ultimate-member' ),
//					)
//				) . '</a>',
//				UM()->get_allowed_html( 'templates' )
//			);

			$fileinfo['lazy_image'] = wp_kses(
				UM()->frontend()::layouts()::lazy_image(
					$fileinfo['url'],
					array(
						'width' => '100%',
						'alt'   => __( 'Image Upload', 'ultimate-member' ), // @todo field label here
					)
				),
				UM()->get_allowed_html( 'templates' )
			);
		} else {
			$field_id = ! empty( $_REQUEST['field_id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['field_id'] ) ) : '';
			$form_id  = ! empty( $_REQUEST['form_id'] ) ? absint( $_REQUEST['form_id'] ) : '';
			$real_id  = 'um_field_' . $form_id . '_' . $field_id;
			$user_id  = ! empty( $_REQUEST['user_id'] ) ? absint( $_REQUEST['user_id'] ) : false;
			ob_start();
			?>
			<div class="um-modal-crop-wrapper" data-crop="<?php echo esc_attr( $crop ); ?>" data-field="<?php echo esc_attr( $real_id ); ?>" data-min_width="256" data-min_height="256">
				<img src="<?php echo esc_url( $fileinfo['url'] ); ?>" class="um-field-image-crop fusion-lazyload-ignore" alt="" />
			</div>
			<div class="um-modal-buttons-wrapper">
				<?php
				echo wp_kses(
					UM()->frontend()::layouts()::button(
						__( 'Apply', 'ultimate-member' ),
						array(
							'type'    => 'button',
							'design'  => 'primary',
							'size'    => 'm',
							'classes' => array( 'um-apply-field-image-crop' ),
							'data'    => array(
								'user_id'    => $user_id,
								'form_id'    => $form_id,
								'form_field' => $real_id,
								'field_id'   => $field_id,
								'nonce'      => wp_create_nonce( 'um_field_image_crop_apply' . $field_id ),
							),
						)
					),
					UM()->get_allowed_html( 'templates' )
				);
				echo wp_kses(
					UM()->frontend()::layouts()::button(
						__( 'Cancel', 'ultimate-member' ),
						array(
							'type'    => 'button',
							'design'  => 'secondary-gray',
							'size'    => 'm',
							'classes' => array( 'um-modal-field-image-decline' ),
							'data'    => array(
								'form_field' => $real_id,
							),
						)
					),
					UM()->get_allowed_html( 'templates' )
				);
				echo wp_kses( UM()->frontend()::layouts()::ajax_loader( 's' ), UM()->get_allowed_html( 'templates' ) );
				?>
			</div>
			<?php
			$fileinfo['modal_content'] = ob_get_clean();
			$fileinfo['modal_content'] = UM()->ajax()->esc_html_spaces( $fileinfo['modal_content'] );
		}

		return $fileinfo;
	}

	/**
	 * Resize image AJAX handler
	 */
	public function crop_image() {
		if ( empty( $_REQUEST['field_id'] ) ) {
			wp_send_json_error( esc_js( __( 'Invalid field ID', 'ultimate-member' ) ) );
		}

		$field_id = ! empty( $_REQUEST['field_id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['field_id'] ) ) : '';

		check_ajax_referer( 'um_field_image_crop_apply' . $field_id, 'nonce' );

		if ( ! isset( $_REQUEST['src'], $_REQUEST['coord'] ) ) {
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

		$form_id   = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : null;
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
		if ( ! array_key_exists( 'mode', $post_data ) ) {
			wp_send_json_error( esc_js( __( 'Invalid form type', 'ultimate-member' ) ) );
		}
		$mode = $post_data['mode'];

		UM()->fields()->set_id   = $form_id;
		UM()->fields()->set_mode = $mode;

		if ( ! is_user_logged_in() && 'profile' === $mode ) {
			wp_send_json_error( esc_js( __( 'You have no permission to edit user profile', 'ultimate-member' ) ) );
		}

		if ( null !== $user_id && 'register' === $mode ) {
			wp_send_json_error( esc_js( __( 'User has to be empty on registration', 'ultimate-member' ) ) );
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

		if ( ! array_key_exists( 'custom_fields', $post_data ) || empty( $post_data['custom_fields'] ) ) {
			wp_send_json_error( esc_js( __( 'Invalid form fields', 'ultimate-member' ) ) );
		}

		$custom_fields = maybe_unserialize( $post_data['custom_fields'] );
		if ( ! is_array( $custom_fields ) || ! array_key_exists( $field_id, $custom_fields ) ) {
			if ( ! ( 'profile' === $mode && in_array( $field_id, array( 'cover_photo', 'profile_photo' ), true ) ) ) {
				wp_send_json_error( esc_js( __( 'Invalid field metakey', 'ultimate-member' ) ) );
			}
		}

		if ( empty( $custom_fields[ $field_id ]['crop'] ) && ! in_array( $field_id, array( 'cover_photo', 'profile_photo' ), true ) ) {
			wp_send_json_error( esc_js( __( 'This field doesn\'t support image crop', 'ultimate-member' ) ) );
		}

		if ( 'profile' === $mode && ! um_can_edit_field( $custom_fields[ $field_id ] ) ) {
			wp_send_json_error( esc_js( __( 'You have no permission to edit this field', 'ultimate-member' ) ) );
		}

		$src        = esc_url_raw( $_REQUEST['src'] );
		$image_path = um_is_file_owner( $src, $user_id, true );
		if ( ! $image_path ) {
			wp_send_json_error( esc_js( __( 'Invalid file ownership', 'ultimate-member' ) ) );
		}

		$coord_n = substr_count( $_REQUEST['coord'], ',' );
		if ( 3 !== $coord_n ) {
			wp_send_json_error( esc_js( __( 'Invalid coordinates', 'ultimate-member' ) ) );
		}
		$coord = sanitize_text_field( $_REQUEST['coord'] );

		$crop = explode( ',', $coord );
		$crop = array_map( 'intval', $crop );

		$quality = UM()->options()->get( 'image_compression' );

		// @todo continue with image crop in new UI.
		UM()->uploader()->replace_upload_dir = true;

		$output = UM()->uploader()->resize_image( $image_path, $src, $field_id, $user_id, $coord );

		UM()->uploader()->replace_upload_dir = false;

		delete_option( "um_cache_userdata_{$user_id}" );
		// phpcs:enable WordPress.Security.NonceVerification -- verified by the `check_ajax_nonce()`
		wp_send_json_success( $output );
	}
}
