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
	public $temp_upload_dir = array();

	/**
	 * @var string
	 *
	 * @since 2.8.7
	 */
	public $temp_upload_url = array();

	/**
	 * @return void
	 */
	public function hooks() {
		add_action( 'init', array( $this, 'init_paths' ), 0 );
	}

	/**
	 * Init uploading URL and directory
	 *
	 * @since 2.8.7
	 */
	public function init_paths() {
		// Built-in prepare here. But after getting we can check if it isn't empty.
		$basedir = $this->get_basedir();
		if ( empty( $basedir ) ) {
			// Doesn't make the sense to init other variables because basedir isn't init properly.
			return;
		}

		$this->prepare_tempdir();

		$this->prepare_baseurl();
		$this->prepare_tempurl();
	}

	/**
	 * @since 3.0.0
	 *
	 * @param $blog_id
	 *
	 * @return void
	 */
	public function prepare_basedir( $blog_id = null ) {
		if ( ! $blog_id ) {
			$blog_id = get_current_blog_id();
		} elseif ( is_multisite() ) {
			switch_to_blog( $blog_id );
		}

		$upload_dir = wp_upload_dir();
		if ( ! empty( $uploads['error'] ) ) {
			return;
		}

		/**
		 * Filters the base UM uploads directory.
		 *
		 * @param {string} $dir     Base UM uploads directory name.
		 * @param {int}    $blog_id Current blog ID. For no-multisite installation equals 1.
		 *
		 * @return {string} Base UM uploads directory name.
		 *
		 * @since 3.0.0
		 * @hook um_base_upload_directory
		 *
		 * @example <caption>Change base UM uploads directory to 'ultimate-member'.</caption>
		 * function my_um_base_upload_directory( $um_dir, $blog_id ) {
		 *      $um_dir = 'ultimate-member/';
		 *      return $um_dir;
		 * }
		 * add_filter( 'um_base_upload_directory', 'my_um_base_upload_directory', 10, 2 );
		 */
		$um_dir = apply_filters( 'um_base_upload_directory', 'ultimatemember', $blog_id );

		$basedir = $upload_dir['basedir'] . '/' . $um_dir;

		if ( is_multisite() ) {
			/**
			 * Filters the multisite suffix for base UM uploads directory.
			 *
			 * @param {string} $prefix  The multisite suffix for base UM uploads directory.
			 * @param {int}    $blog_id Current blog ID. For no-multisite installation equals 1.
			 *
			 * @return {string} The multisite suffix for base UM uploads directory.
			 *
			 * @since 1.3.84
			 * @hook um_multisite_upload_sites_directory
			 *
			 * @example <caption>Change the multisite suffix for base UM uploads directory.</caption>
			 * function my_um_multisite_upload_sites_directory( $sites_dir, $blog_id ) {
			 *      $sites_dir = 'sub-site/';
			 *      return $sites_dir;
			 * }
			 * add_filter( 'um_multisite_upload_sites_directory', 'my_um_multisite_upload_sites_directory', 10, 2 );
			 */
			$sites_dir = apply_filters( 'um_multisite_upload_sites_directory', 'sites', $blog_id );

			$basedir .= '/' . $sites_dir . '/' . $blog_id;
		}

		if ( is_multisite() ) {
			restore_current_blog();
		}

		$basedir = wp_normalize_path( $basedir );

		/**
		 * Filters Ultimate Member uploads basedir.
		 *
		 * @param {string} $basedir The base UM uploads directory.
		 * @param {int}    $blog_id Current blog ID. For no-multisite installation equals 1.
		 *
		 * @return {string} The base UM uploads directory.
		 *
		 * @since 3.0.0
		 * @hook um_upload_basedir
		 *
		 * @example <caption>Change the Ultimate Member uploads basedir.</caption>
		 * function my_um_upload_basedir( $basedir, $blog_id ) {
		 *      $basedir = 'your custom basedir';
		 *      return $basedir;
		 * }
		 * add_filter( 'um_upload_basedir', 'my_um_upload_basedir', 10, 2 );
		 */
		$this->upload_dir[ $blog_id ] = apply_filters( 'um_upload_basedir', $basedir, $blog_id );

		if ( ! self::maybe_create_dir( $this->upload_dir[ $blog_id ] ) ) {
			// Flush data on false directory exists or creation. Then directory doesn't exist.
			unset( $this->upload_dir[ $blog_id ] );
		}
	}

	/**
	 * @since 3.0.0
	 *
	 * @param $blog_id
	 *
	 * @return void
	 */
	public function prepare_tempdir( $blog_id = null ) {
		$basedir = $this->get_basedir( $blog_id );
		if ( empty( $basedir ) ) {
			return;
		}

		$temp_dir = $basedir . '/temp';
		$temp_dir = wp_normalize_path( $temp_dir );

		/**
		 * Filters Ultimate Member uploads temp dir.
		 *
		 * @param {string} $temp_dir Temp UM uploads directory.
		 * @param {int}    $blog_id  Current blog ID. For no-multisite installation equals 1.
		 *
		 * @return {string} Temp UM uploads directory.
		 *
		 * @since 3.0.0
		 * @hook um_upload_temp_dir
		 *
		 * @example <caption>Change the Ultimate Member uploads temp dir.</caption>
		 * function my_um_upload_temp_dir( $temp_dir, $blog_id ) {
		 *      $basedir = 'your custom temp dir';
		 *      return $basedir;
		 * }
		 * add_filter( 'um_upload_temp_dir', 'my_um_upload_temp_dir', 10, 2 );
		 */
		$this->temp_upload_dir[ $blog_id ] = apply_filters( 'um_upload_temp_dir', $temp_dir, $blog_id );

		if ( ! self::maybe_create_dir( $this->temp_upload_dir[ $blog_id ] ) ) {
			// Flush data on false directory exists or creation. Then directory doesn't exist.
			unset( $this->temp_upload_dir[ $blog_id ] );
		}
	}

	/**
	 * @since 3.0.0
	 *
	 * @param $blog_id
	 *
	 * @return void
	 */
	public function prepare_baseurl( $blog_id = null ) {
		$basedir = $this->get_basedir( $blog_id );
		if ( empty( $basedir ) ) {
			return;
		}

		if ( ! $blog_id ) {
			$blog_id = get_current_blog_id();
		} elseif ( is_multisite() ) {
			switch_to_blog( $blog_id );
		}

		$upload_dir = wp_upload_dir();
		if ( ! empty( $uploads['error'] ) ) {
			return;
		}

		/** This filter is documented in includes/common/class-filesystem.php */
		$um_dir = apply_filters( 'um_base_upload_directory', 'ultimatemember', $blog_id );

		$baseurl = $upload_dir['baseurl'] . '/' . $um_dir;

		if ( is_multisite() ) {
			/** This filter is documented in includes/common/class-filesystem.php */
			$sites_dir = apply_filters( 'um_multisite_upload_sites_directory', 'sites', $blog_id );

			$baseurl .= '/' . $sites_dir . '/' . $blog_id;
		}

		if ( is_multisite() ) {
			restore_current_blog();
		}

		/**
		 * Filters Ultimate Member uploads base URL.
		 *
		 * @param {string} $basedir The base UM uploads URL.
		 * @param {int}    $blog_id Current blog ID. For no-multisite installation equals 1.
		 *
		 * @return {string} The base UM uploads URL.
		 *
		 * @since 3.0.0
		 * @hook um_upload_baseurl
		 *
		 * @example <caption>Change the Ultimate Member uploads base URL.</caption>
		 * function my_um_upload_baseurl( $baseurl, $blog_id ) {
		 *      $basedir = 'your custom URL';
		 *      return $basedir;
		 * }
		 * add_filter( 'um_upload_baseurl', 'my_um_upload_baseurl', 10, 2 );
		 */
		$this->upload_url[ $blog_id ] = apply_filters( 'um_upload_baseurl', $baseurl, $blog_id );
	}

	/**
	 * @since 3.0.0
	 *
	 * @param $blog_id
	 *
	 * @return void
	 */
	public function prepare_tempurl( $blog_id = null ) {
		$baseurl = $this->get_baseurl( $blog_id );
		if ( empty( $baseurl ) ) {
			return;
		}

		$temp_url = $baseurl . '/temp';
		/**
		 * Filters the Ultimate Member temp URL.
		 *
		 * @param {string} $temp_url Temp UM uploads URL.
		 * @param {int}    $blog_id  Current blog ID. For no-multisite installation equals 1.
		 *
		 * @return {string} Temp UM uploads URL.
		 *
		 * @since 3.0.0
		 * @hook um_upload_temp_url
		 */
		$this->temp_upload_url[ $blog_id ] = apply_filters( 'um_upload_temp_url', $temp_url, $blog_id );
	}

	/**
	 * @since 3.0.0
	 *
	 * @param $dir
	 *
	 * @return bool
	 */
	public static function maybe_create_dir( $dir ) {
		global $wp_filesystem;

		self::maybe_init_wp_filesystem();

		$dir = wp_normalize_path( $dir );
		if ( $wp_filesystem->is_dir( $dir ) ) {
			return true;
		}

		return wp_mkdir_p( $dir );
	}

	/**
	 * @since 3.0.0
	 *
	 * @param int|null $blog_id
	 *
	 * @return string
	 */
	public function get_basedir( $blog_id = null ) {
		if ( ! $blog_id ) {
			$blog_id = get_current_blog_id();
		}

		if ( empty( $this->upload_dir[ $blog_id ] ) ) {
			$this->prepare_basedir( $blog_id );
		}

		return ! empty( $this->upload_dir[ $blog_id ] ) ? $this->upload_dir[ $blog_id ] : '';
	}

	/**
	 * @since 3.0.0
	 *
	 * @param int|null $blog_id
	 *
	 * @return string
	 */
	public function get_baseurl( $blog_id = null ) {
		if ( ! $blog_id ) {
			$blog_id = get_current_blog_id();
		}

		if ( empty( $this->upload_url[ $blog_id ] ) ) {
			$this->prepare_baseurl( $blog_id );
		}

		return ! empty( $this->upload_url[ $blog_id ] ) ? $this->upload_url[ $blog_id ] : '';
	}

	/**
	 * @since 3.0.0
	 *
	 * @param int|null $blog_id
	 *
	 * @return string
	 */
	public function get_tempdir( $blog_id = null ) {
		if ( ! $blog_id ) {
			$blog_id = get_current_blog_id();
		}

		if ( empty( $this->temp_upload_dir[ $blog_id ] ) ) {
			$this->prepare_tempdir( $blog_id );
		}

		return ! empty( $this->temp_upload_dir[ $blog_id ] ) ? $this->temp_upload_dir[ $blog_id ] : '';
	}

	/**
	 * @since 3.0.0
	 *
	 * @param int|null $blog_id
	 *
	 * @return string
	 */
	public function get_tempurl( $blog_id = null ) {
		if ( ! $blog_id ) {
			$blog_id = get_current_blog_id();
		}

		if ( empty( $this->temp_upload_url[ $blog_id ] ) ) {
			$this->prepare_tempurl( $blog_id );
		}

		return ! empty( $this->temp_upload_url[ $blog_id ] ) ? $this->temp_upload_url[ $blog_id ] : '';
	}

	/**
	 * Get upload dir of plugin
	 *
	 * @param string   $sub_dir Any subdirectory path inside the Ultimate Member uploads basedir
	 * @param int|null $blog_id Blog ID
	 *
	 * @return string
	 *
	 * @since 2.8.7
	 */
	public function get_upload_dir( $sub_dir, $blog_id = null ) {
		$basedir = $this->get_basedir( $blog_id );
		if ( empty( $basedir ) ) {
			return '';
		}

		$upload_dir = $basedir . DIRECTORY_SEPARATOR . untrailingslashit( $sub_dir );
		if ( ! self::maybe_create_dir( $upload_dir ) ) {
			return '';
		}
		return wp_normalize_path( $upload_dir );
	}

	/**
	 * Get upload url of plugin
	 *
	 * @param string   $url     Any subdirectory path inside the Ultimate Member uploads basedir
	 * @param int|null $blog_id Blog ID
	 *
	 * @return string
	 *
	 * @since 2.8.7
	 */
	public function get_upload_url( $url, $blog_id = null ) {
		$baseurl = $this->get_baseurl( $blog_id );
		if ( empty( $baseurl ) ) {
			return '';
		}

		return $baseurl . '/' . untrailingslashit( $url );
	}

	/**
	 * Files Age in the temp folder. By default, it's 24 hours.
	 *
	 * @return int Temp file age in seconds.
	 * @since 2.8.7
	 *
	 */
	public static function files_age() {
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

			$default_image_types = array( 'jpg', 'jpeg', 'jpe', 'gif', 'png', 'bmp', 'tif', 'tiff', 'ico', 'heic', 'webp', 'avif' );
			/**
			 * Filters the allowed image mimes.
			 *
			 * @param {array} $mimes Allowed image mimes.
			 *
			 * @since 3.0.0
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
			 * @since 3.0.0
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
	 * @since 3.0.0
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
				 * @since 3.0.0
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
			 * @since 3.0.0
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
			 * @since 3.0.0
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
	 * Remove all files, which are older than 24 hours
	 *
	 * Can duplicate this function functionality `remove_old_files`
	 *
	 * @since 2.8.7
	 */
	public function clear_temp_dir( $blog_id = null ) {
		global $wp_filesystem;

		$temp_path = $this->get_tempdir( $blog_id );
		if ( empty( $temp_path ) ) {
			return;
		}
		$temp_path .= DIRECTORY_SEPARATOR;

		self::maybe_init_wp_filesystem();

		$dirlist = $wp_filesystem->dirlist( $temp_path );
		$dirlist = $dirlist ? $dirlist : array();
		if ( empty( $dirlist ) ) {
			return;
		}

		foreach ( array_keys( $dirlist ) as $file ) {
			if ( '.' === $file || '..' === $file ) {
				continue;
			}

			$filepath = wp_normalize_path( $temp_path . $file );

			// Remove temp file if it is older than the max age and is not the current file
			if ( $wp_filesystem->mtime( $filepath ) < time() - self::files_age() ) {
				$wp_filesystem->delete( $filepath );
			}
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

	/**
	 * @since 3.0.0
	 *
	 * @param string $path File path
	 *
	 * @return bool
	 */
	public static function remove_file( $path ) {
		global $wp_filesystem;

		self::maybe_init_wp_filesystem();

		if ( ! $wp_filesystem->is_file( $path ) ) {
			return false;
		}

		return $wp_filesystem->delete( $path );
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
	 * @param string $file_hash
	 *
	 * @return bool|string
	 */
	public function get_file_by_hash( $file_hash ) {
		global $wp_filesystem;

		$file_path = null;

		$temp_dir = $this->get_user_temp_dir();
		if ( empty( $temp_dir ) ) {
			// Possible hijacking.
			return false;
		}
		$temp_dir .= DIRECTORY_SEPARATOR;

		self::maybe_init_wp_filesystem();

		$dirlist = $wp_filesystem->dirlist( $temp_dir );
		$dirlist = $dirlist ? $dirlist : array();
		if ( empty( $dirlist ) ) {
			return false;
		}

		foreach ( array_keys( $dirlist ) as $file ) {
			if ( '.' === $file || '..' === $file ) {
				continue;
			}

			$hash = md5( $file . '_um_uploader_security_salt' );

			if ( 0 === strpos( $file_hash, $hash ) ) {
				$file_path = wp_normalize_path( $temp_dir . $file );
				break;
			}
		}

		// Validate traversal file.
		if ( empty( $file_path ) || ! file_exists( $file_path ) || validate_file( $file_path ) === 1 ) {
			return false;
		}

		return $file_path;
	}

	/**
	 * @since 3.0.0
	 *
	 * @param string $file_hash
	 * @param int    $queried_user
	 *
	 * @return bool
	 */
	public function is_file_author( $file_hash, $queried_user = false ) {
		$is_author = true;

		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
		} else {
			$user_id = UM()->common()->guest()->get_guest_token();
		}

		if ( ! empty( $queried_user ) && (string) $queried_user !== (string) $user_id ) {
			$is_author = false;
		} else {
			$file_path = $this->get_file_by_hash( $file_hash );
			if ( false === $file_path ) {
				$is_author = false;
			}
		}

		return apply_filters( 'um_is_file_author', $is_author, $file_hash, $queried_user );
	}

	/**
	 * Get user uploads directory
	 *
	 * @param int $user_id
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_user_uploads_dir( $user_id ) {
		if ( ! UM()->common()->users()::user_exists( $user_id ) ) {
			return '';
		}

		$user_dir = $this->get_basedir() . DIRECTORY_SEPARATOR . $user_id;
		/**
		 * Filters the user uploads directory.
		 *
		 * @param {string} $url     User uploads directory.
		 * @param {int}    $user_id User ID.
		 *
		 * @since 3.0.0
		 * @hook  um_user_uploads_dir
		 */
		$user_dir = apply_filters( 'um_user_uploads_dir', $user_dir, $user_id );
		if ( ! self::maybe_create_dir( $user_dir ) ) {
			// Flush data on false directory exists or creation. Then directory doesn't exist.
			return '';
		}

		return $user_dir;
	}

	/**
	 * Get user uploads URL
	 *
	 * @param int $user_id
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_user_uploads_url( $user_id ) {
		if ( ! UM()->common()->users()::user_exists( $user_id ) ) {
			return '';
		}

		$url = $this->get_baseurl() . '/' . $user_id;
		/**
		 * Filters the user uploads URL.
		 *
		 * @param {string} $url     User uploads URL.
		 * @param {int}    $user_id User ID.
		 *
		 * @since 3.0.0
		 * @hook  um_user_uploads_url
		 */
		return apply_filters( 'um_user_uploads_url', $url, $user_id );
	}

	/**
	 * Get user temp uploads directory
	 *
	 * @param int|string|null $user_id
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_user_temp_dir( $user_id = null ) {
		if ( ! $user_id ) {
			if ( is_user_logged_in() ) {
				$user_id = get_current_user_id();
			} else {
				$user_id = UM()->common()->guest()->get_guest_token();
				if ( is_null( $user_id ) ) {
					// Possible hijacking.
					return '';
				}
			}
		} elseif ( ! UM()->common()->users()::user_exists( $user_id ) ) {
			return '';
		}

		$user_dir = $this->get_tempdir() . DIRECTORY_SEPARATOR . $user_id;
		/**
		 * Filters the user uploads temp directory.
		 *
		 * @param {string} $url     User uploads temp directory.
		 * @param {int}    $user_id User ID.
		 *
		 * @since 3.0.0
		 * @hook  um_user_temp_dir
		 */
		$user_dir = apply_filters( 'um_user_temp_dir', $user_dir, $user_id );
		if ( ! self::maybe_create_dir( $user_dir ) ) {
			// Flush data on false directory exists or creation. Then directory doesn't exist.
			return '';
		}

		return $user_dir;
	}

	/**
	 * Get a directory size
	 *
	 * @param string $directory
	 *
	 * @return string
	 */
	public static function dir_size( $directory ) {
		$size = get_dirsize( $directory );
		return number_format( $size / ( 1024 * 1024 ), 2 ) . ' MB';
	}

	/**
	 * Generate a temporary file URL for download.
	 *
	 * @param array $fileinfo Array containing file information.
	 *                        - file: The file to generate URL for.
	 *                        - hash: Hash of the file.
	 *
	 * @return string Temporary file URL for download.
	 */
	public function get_temp_file_url( $fileinfo ) {
		$filetype    = wp_check_filetype( $fileinfo['file'] );
		$field_value = $fileinfo['hash'] . '.' . $filetype['ext'];

		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
		} else {
			$user_id = UM()->common()->guest()->get_guest_token();
		}

		$nonce = wp_create_nonce( $user_id . $field_value . 'um-temp-download-nonce' );

		$url = get_home_url( get_current_blog_id() );
		if ( UM()->is_permalinks ) {
			$url .= "/um-temp/{$user_id}/{$nonce}/{$field_value}";
		} else {
			$url = add_query_arg(
				array(
					'um_action'   => 'temp-download',
					'um_user'     => $user_id,
					'um_nonce'    => $nonce,
					'um_filename' => $field_value,
				),
				$url
			);
		}
		return self::add_timestamp( $url );
	}

	/**
	 * Add timestamp to URL if filter allows
	 *
	 * @param string $url The URL to add timestamp to
	 *
	 * @return string The URL with timestamp added if conditional function allows, original URL otherwise
	 * @since 3.0.0
	 */
	public static function add_timestamp( $url ) {
		if ( ! self::is_timestamp_addable() ) {
			return $url;
		}

		// Add the timestamp using WordPress's add_query_arg function
		return add_query_arg( 'timestamp', time(), $url );
	}

	/**
	 * Determines if timestamp can be added to URL.
	 *
	 * @return bool Whether timestamp can be added to URL.
	 * @since 3.0.0
	 *
	 */
	public static function is_timestamp_addable() {
		/**
		 * Filters whether to add a timestamp to the URL.
		 *
		 * @param bool $add_timestamp Whether to add a timestamp to the URL. Default true.
		 *
		 * @return bool
		 *
		 * @since 3.0.0
		 * @hook um_filesystem_url_has_timestamp
		 *
		 * @example <caption>Avoid timestamp in URLs</caption>
		 * ```php
		 *  add_filter( 'um_filesystem_url_has_timestamp', '__return_false' );
		 *  function my_custom_upload_handlers( $handlers ) {
		 *      $handlers[] = 'my-handler';
		 *      return $handlers;
		 *  }
		 *  ```
		 */
		return apply_filters( 'um_filesystem_url_has_timestamp', true );
	}
}
