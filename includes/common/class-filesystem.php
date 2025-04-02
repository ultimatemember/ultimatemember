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

	/**
	 * Files Age in the temp folder. By default, it's 24 hours.
	 *
	 * @return int Temp file age in seconds.
	 * @since 2.8.7
	 *
	 */
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

	/**
	 * Image MIME Types
	 *
	 * Retrieves a list of image MIME types based on the context.
	 *
	 * @param string $context The context in which the MIME types are needed ('list' or 'allowed').
	 *
	 * @return array List of image MIME types based on the context.
	 *
	 * @since 2.8.7
	 */
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
			$mimes     = array_key_exists( 'image', $all_mimes ) ? $all_mimes['image'] : array( 'jpg', 'jpeg', 'jpe', 'gif', 'png', 'bmp', 'tif', 'tiff', 'ico', 'heic', 'webp', 'avif' );

			$all_extensions = array();
			foreach ( $allowed_for_user as $extensions => $mime ) {
				$all_extensions[] = explode( '|', $extensions );
			}
			$all_extensions = array_merge( ...$all_extensions );
			$mimes          = array_intersect( $mimes, $all_extensions );

			/**
			 * Filters the MIME-types of the images that can be uploaded as Company Logo.
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

			$mimes = array_intersect( $mimes, $allowed_for_user );

			/**
			 * Filters the MIME-types of the images that can be uploaded via UM uploader
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
	 * Remove all files, which are older than 24 hours
	 *
	 * Can duplicate this function functionality `remove_old_files`
	 *
	 * @since 2.8.7
	 */
	public function clear_temp_dir() {
		global $wp_filesystem;

		self::maybe_init_wp_filesystem();

		if ( ! $wp_filesystem->is_dir( $this->temp_upload_dir ) ) {
			return;
		}

		// phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged
		$dir = @opendir( $this->temp_upload_dir );
		if ( ! $dir ) {
			return;
		}

		// phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition -- reading folder's content here
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

		self::maybe_init_wp_filesystem();

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

	/**
	 * Probably define global $wp_filesystem.
	 *
	 * @since 2.10.2
	 *
	 * @return void
	 */
	public static function maybe_init_wp_filesystem() {
		global $wp_filesystem;

		// If you need to fix this issue on the localhost
		// https://stackoverflow.com/questions/30688431/wordpress-needs-the-ftp-credentials-to-update-plugins
		// Please add define('FS_METHOD', 'direct'); to avoid question about FTP.
		if ( ! $wp_filesystem instanceof WP_Filesystem_Base ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';

			$credentials = request_filesystem_credentials( site_url() );
			WP_Filesystem( $credentials );
		}
	}

	/**
	 * Remove a directory using WP Filesystem.
	 *
	 * @param string $dir The directory path to be removed. Should end with DIRECTORY_SEPARATOR.
	 *
	 * @return bool True on success, false on failure or if directory doesn't exist.
	 *
	 * @since 2.10.2
	 */
	public static function remove_dir( $dir ) {
		global $wp_filesystem;

		self::maybe_init_wp_filesystem();

		if ( ! $wp_filesystem->is_dir( $dir ) ) {
			return false;
		}

		return $wp_filesystem->delete( $dir, true );
	}
}
