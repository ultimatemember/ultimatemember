<?php
namespace um\common;

use WP_Filesystem_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Filesystem
 *
 * @package um\common
 */
class Filesystem {

	/**
	 * @var array
	 *
	 * @since 2.8.7
	 */
	public $upload_dir = array();

	/**
	 * @var array
	 *
	 * @since 2.8.7
	 */
	public $upload_url = array();

	/**
	 * @var string
	 *
	 * @since 2.8.7
	 */
	public $temp_upload_dir = '';

	/**
	 * @var string
	 *
	 * @since 2.8.7
	 */
	public $temp_upload_url = '';

	/**
	 * Filesystem constructor.
	 */
	public function __construct() {
		$this->init_paths();
	}

	/**
	 * Init uploading URL and directory
	 *
	 * @since 2.8.7
	 */
	public function init_paths() {
		$this->temp_upload_dir = $this->get_upload_dir( 'ultimatemember/temp' );
		$this->temp_upload_url = $this->get_upload_url( 'ultimatemember/temp' );
	}

	public function files_age() {
		/**
		 * Filters the maximum file age in the temp folder. By default, it's 24 hours.
		 *
		 * @since 2.8.7
		 * @hook um_filesystem_max_file_age
		 *
		 * @param {int} $file_age Temp file age in seconds.
		 *
		 * @return {int} Temp file age in seconds.
		 */
		return apply_filters( 'um_filesystem_max_file_age', 24 * HOUR_IN_SECONDS ); // Temp file age in seconds
	}

	public static function image_mimes( $context = 'list' ) {
		$mimes = array();

		static $allowed_for_user = null;
		if ( empty( $allowed_for_user ) ) {
			$allowed_for_user = get_allowed_mime_types();
		}

		if ( empty( $allowed_for_user ) ) {
			return $mimes;
		}

		if ( 'list' === $context ) {
			$all_mimes = wp_get_ext_types();

			$default_image_types = array( 'jpg', 'jpeg', 'jpe', 'gif', 'png', 'bmp', 'tif', 'tiff', 'ico', 'heic', 'webp', 'avif' );
			/**
			 * Filters the allowed image mimes.
			 *
			 * @param {array} $mimes Allowed image mimes.
			 *
			 * @since 2.9.0
			 * @hook  um_allowed_default_image_types
			 */
			$default_image_types = apply_filters( 'um_allowed_default_image_types', $default_image_types );

			$mimes = array_key_exists( 'image', $all_mimes ) ? $all_mimes['image'] : $default_image_types;

			$all_extensions = array();
			foreach ( $allowed_for_user as $extensions => $mime ) {
				$all_extensions[] = explode( '|', $extensions );
			}
			$all_extensions = array_merge( ...$all_extensions );
			$mimes          = array_intersect( $mimes, $all_extensions );

			/**
			 * Filters the MIME-types of the images that can be uploaded via UM image uploader.
			 *
			 * @since 2.8.7
			 * @hook um_upload_image_mimes_list
			 *
			 * @param {array} $mime_types MIME types.
			 *
			 * @return {array} MIME types.
			 */
			$mimes = apply_filters( 'um_upload_image_mimes_list', $mimes );
		} elseif ( 'allowed' === $context ) {
			$mimes = array(
				'jpg|jpeg|jpe' => 'image/jpeg',
				'gif'          => 'image/gif',
				'png'          => 'image/png',
				'bmp'          => 'image/bmp',
				'tiff|tif'     => 'image/tiff',
				'webp'         => 'image/webp',
				'avif'         => 'image/avif',
				'ico'          => 'image/x-icon',
				'heic'         => 'image/heic',
			);
			/**
			 * Filters the allowed image mimes.
			 *
			 * @param {array} $mimes Allowed image mimes.
			 *
			 * @since 2.9.0
			 * @hook  um_allowed_default_image_mimes
			 */
			$mimes = apply_filters( 'um_allowed_default_image_mimes', $mimes );

			$mimes = array_intersect( $mimes, $allowed_for_user );

			/**
			 * Filters the MIME-types of the images that can be uploaded via UM image uploader.
			 *
			 * @since 2.8.7
			 * @hook um_upload_allowed_image_mimes
			 *
			 * @param {array} $mime_types MIME types.
			 *
			 * @return {array} MIME types.
			 */
			$mimes = apply_filters( 'um_upload_allowed_image_mimes', $mimes );
		}

		return $mimes;
	}

	/**
	 * @since 2.9.0
	 * @param string $context
	 *
	 * @return array
	 */
	public static function file_mimes( $context = 'list' ) {
		$mimes = array();

		static $allowed_for_user = null;
		if ( empty( $allowed_for_user ) ) {
			$allowed_for_user = get_allowed_mime_types();
		}

		if ( empty( $allowed_for_user ) ) {
			return $mimes;
		}

		if ( 'list' === $context ) {
			$all_mimes = wp_get_ext_types();
			if ( array_key_exists( 'image', $all_mimes ) ) {
				unset( $all_mimes['image'] );
			}

			if ( ! empty( $all_mimes ) ) {
				$mimes = array_merge( ...array_values( $all_mimes ) );
			} else {
				$mimes = array( 'pdf', 'txt', 'csv', 'doc', 'docx', 'odt', 'ods', 'xls', 'xlsx', 'zip', 'rar', 'mp3', 'eps', 'psd' );
				/**
				 * Filters the allowed file mimes.
				 *
				 * @param {array} $mimes Allowed file mimes.
				 *
				 * @since 2.9.0
				 * @hook  um_allowed_default_file_types
				 */
				$mimes = apply_filters( 'um_allowed_default_file_types', $mimes );
			}

			$all_extensions = array();
			foreach ( $allowed_for_user as $extensions => $mime ) {
				$all_extensions[] = explode( '|', $extensions );
			}
			$all_extensions = array_merge( ...$all_extensions );
			$mimes          = array_intersect( $mimes, $all_extensions );

			/**
			 * Filters the MIME-types of the files that can be uploaded via UM file uploader.
			 *
			 * @since 2.9.0
			 * @hook um_upload_file_mimes_list
			 *
			 * @param {array} $mime_types MIME types.
			 *
			 * @return {array} MIME types.
			 */
			$mimes = apply_filters( 'um_upload_file_mimes_list', $mimes );
			$mimes = array_values( array_unique( $mimes ) );
		} elseif ( 'allowed' === $context ) {
			$mimes = array(
				'jpg|jpeg|jpe' => 'image/jpeg',
				'gif'          => 'image/gif',
				'png'          => 'image/png',
				'bmp'          => 'image/bmp',
				'tiff|tif'     => 'image/tiff',
				'webp'         => 'image/webp',
				'avif'         => 'image/avif',
				'ico'          => 'image/x-icon',
				'heic'         => 'image/heic',
			);

			$mimes = array_intersect( $mimes, $allowed_for_user );

			/**
			 * Filters the MIME-types of the files that can be uploaded via UM file uploader
			 *
			 * @since 2.9.0
			 * @hook um_upload_allowed_file_mimes
			 *
			 * @param {array} $mime_types MIME types.
			 *
			 * @return {array} MIME types.
			 */
			$mimes = apply_filters( 'um_upload_allowed_file_mimes', $mimes );
		}

		return $mimes;
	}

	/**
	 * Remove all files, which are older than 24 hours
	 *
	 * Can duplicate this function functionality `remove_old_files`
	 *
	 * @since 2.8.7
	 */
	public function clear_temp_dir() {
		global $wp_filesystem;

		if ( ! $wp_filesystem instanceof WP_Filesystem_Base ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';

			$credentials = request_filesystem_credentials( site_url() );
			WP_Filesystem( $credentials );
		}

		if ( ! $wp_filesystem->is_dir( $this->temp_upload_dir ) ) {
			return;
		}

		// phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged
		$dir = @opendir( $this->temp_upload_dir );
		if ( ! $dir ) {
			return;
		}

		// phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition, Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition -- reading folder's content here
		while ( false !== ( $file = readdir( $dir ) ) ) {
			if ( '.' === $file || '..' === $file ) {
				continue;
			}

			$filepath = wp_normalize_path( $this->temp_upload_dir . DIRECTORY_SEPARATOR . $file );

			// Remove temp file if it is older than the max age and is not the current file
			if ( $wp_filesystem->mtime( $filepath ) < time() - $this->files_age() ) {
				$wp_filesystem->delete( $filepath );
			}
		}

		@closedir( $dir );
		// phpcs:enable WordPress.PHP.NoSilencedErrors.Discouraged
	}

	/**
	 * Get upload dir of plugin
	 *
	 * @param string $dir
	 * @param int|null $blog_id
	 *
	 * @return string
	 *
	 * @since 2.8.7
	 */
	public function get_upload_dir( $dir = '', $blog_id = null ) {
		// Please add define('FS_METHOD', 'direct'); to avoid question about FTP.
		global $wp_filesystem;

		if ( ! $wp_filesystem instanceof WP_Filesystem_Base ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';

			$credentials = request_filesystem_credentials( site_url() );
			WP_Filesystem( $credentials );
		}

		if ( ! $blog_id ) {
			$blog_id = get_current_blog_id();
		} elseif ( is_multisite() ) {
			switch_to_blog( $blog_id );
		}

		if ( empty( $this->upload_dir[ $blog_id ] ) ) {
			$uploads = wp_upload_dir();
			if ( ! empty( $uploads['error'] ) ) {
				return '';
			}
			$this->upload_dir[ $blog_id ] = $uploads['basedir'];
		}

		$upload_dir = wp_normalize_path( trailingslashit( $this->upload_dir[ $blog_id ] ) . untrailingslashit( $dir ) );

		if ( ! $wp_filesystem->is_dir( $upload_dir ) ) {
			wp_mkdir_p( $upload_dir );
		}

		if ( is_multisite() ) {
			restore_current_blog();
		}

		//return dir path
		return $upload_dir;
	}

	/**
	 * Get upload url of plugin
	 *
	 * @param string $url
	 * @param int|null $blog_id
	 *
	 * @return string
	 *
	 * @since 2.8.7
	 */
	public function get_upload_url( $url = '', $blog_id = null ) {
		if ( ! $blog_id ) {
			$blog_id = get_current_blog_id();
		} elseif ( is_multisite() ) {
			switch_to_blog( $blog_id );
		}

		if ( empty( $this->upload_url[ $blog_id ] ) ) {
			$uploads = wp_upload_dir();
			if ( ! empty( $uploads['error'] ) ) {
				return '';
			}
			$this->upload_url[ $blog_id ] = $uploads['baseurl'];
		}

		$upload_url = trailingslashit( $this->upload_url[ $blog_id ] ) . untrailingslashit( $url );

		if ( is_multisite() ) {
			restore_current_blog();
		}

		//return dir path
		return $upload_url;
	}

	/**
	 * Format Bytes
	 *
	 * @param int $size
	 * @param int $precision
	 *
	 * @return string
	 *
	 * @since 2.8.7
	 */
	public static function format_bytes( $size, $precision = 1 ) {
		if ( is_numeric( $size ) ) {
			$base     = log( $size, 1024 );
			$suffixes = array( '', 'kb', 'MB', 'GB', 'TB' );

			$computed_size = round( 1024 ** ( $base - floor( $base ) ), $precision );
			$unit          = $suffixes[ absint( floor( $base ) ) ];

			return $computed_size . ' ' . $unit;
		}

		return '';
	}
}
