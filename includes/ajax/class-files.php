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
		$path     = wp_normalize_path( UM()->common()->filesystem()->temp_upload_dir . '/' . $filename );
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
				$error = __( 'Invalid nonce.', 'ultimate-member' );
			}

			if ( ! array_key_exists( 'user_id', $_REQUEST ) ) {
				$error = __( 'No user to set avatar.', 'ultimate-member' );
			}

			$user_id = absint( $_REQUEST['user_id'] );

			if ( ! UM()->roles()->um_current_user_can( 'edit', $user_id ) ) {
				$error = __( 'You can not edit this user.', 'ultimate-member' );
			}
		} elseif ( 'field-file' === $handler || 'field-image' === $handler ) {
			if ( ! wp_verify_nonce( sanitize_key( $_REQUEST['nonce'] ), 'um_upload_' . $handler ) ) {
				// This nonce is not valid.
				$error = __( 'Invalid nonce.', 'ultimate-member' );
			}

			if ( ! array_key_exists( 'user_id', $_REQUEST ) ) {
				$error = __( 'No user to set value.', 'ultimate-member' );
			}

			$user_id = absint( $_REQUEST['user_id'] );

			if ( ! UM()->roles()->um_current_user_can( 'edit', $user_id ) ) {
				$error = __( 'You can not edit this user.', 'ultimate-member' );
			}

			if ( ! array_key_exists( 'form_id', $_REQUEST ) ) {
				$error = __( 'No form to set value.', 'ultimate-member' );
			}

			if ( ! array_key_exists( 'field_id', $_REQUEST ) ) {
				$error = __( 'No field to set value.', 'ultimate-member' );
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
			<div class="um-profile-photo-crop-wrapper" data-crop="square" data-ratio="1" data-min_width="256" data-min_height="256">
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
	 * @todo make this works based on $_COOKIE
	 *
	 * @param string $file
	 * @param int    $user_id
	 *
	 * @return bool
	 */
	public function is_file_author( $file, $user_id = false ) {
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		if ( empty( $user_id ) ) {
			$user = 'guest';
		} else {
			$user = get_user_by( 'id', $user_id );
		}

		return true;
	}

	/**
	 * Common upload file handler. Default result file in temp directory with unique name.
	 */
	public function upload_file() {
		$error = null;

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
			$unique_name = wp_unique_filename( UM()->common()->filesystem()->temp_upload_dir, $filename, array( &$this, 'unique_filename' ) );
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

		$filepath = UM()->common()->filesystem()->temp_upload_dir . DIRECTORY_SEPARATOR . $unique_name;

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

			$fileinfo                 = $_FILES['file'];
			$fileinfo['file']         = $filepath;
			$fileinfo['name_loaded']  = $filename;
			$fileinfo['name_saved']   = $name_saved;
			$fileinfo['hash']         = md5( $fileinfo['name_saved'] . '_um_uploader_security_salt' );
			$fileinfo['path']         = UM()->common()->filesystem()->temp_upload_dir . DIRECTORY_SEPARATOR . $fileinfo['name_saved'];
			$fileinfo['url']          = UM()->common()->filesystem()->temp_upload_url . '/' . $fileinfo['name_saved'];
			$fileinfo['size']         = filesize( $fileinfo['file'] );
			$fileinfo['size_format']  = size_format( $fileinfo['size'] );
			$fileinfo['time']         = gmdate( 'Y-m-d H:i:s', filemtime( $fileinfo['file'] ) );
			$fileinfo['delete_nonce'] = wp_create_nonce( 'um_delete_temp_file' . $name_saved );

			$fileinfo = apply_filters( 'um_upload_file_fileinfo', $fileinfo, $handler );

			$files[] = $fileinfo;

			do_action( 'um_upload_file_temp_uploaded', $handler, $fileinfo );

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
}
