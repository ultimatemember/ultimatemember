<?php
namespace um\common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\common\Filesystem' ) ) {

	/**
	 * Class Filesystem
	 *
	 * @package um\common
	 */
	class Filesystem {

		/**
		 * @var array
		 *
		 * @since 1.0
		 */
		public $upload_dir = array();

		/**
		 * @var array
		 *
		 * @since 1.0
		 */
		public $upload_url = array();

		/**
		 * @var string
		 *
		 * @since 1.0
		 */
		public $temp_upload_dir = '';

		/**
		 * @var string
		 *
		 * @since 1.0
		 */
		public $temp_upload_url = '';

		/**
		 * Filesystem constructor.
		 */
		public function __construct() {
			add_action( 'admin_init', array( &$this, 'init_paths' ) );
		}

		/**
		 * Init uploading URL and directory
		 *
		 * @since 1.0
		 */
		public function init_paths() {
			$this->temp_upload_dir = $this->get_upload_dir( 'ultimatemember/temp' );
			$this->temp_upload_url = $this->get_upload_url( 'ultimatemember/temp' );
		}

		/**
		 * Get upload dir of plugin
		 *
		 * @param string $dir
		 * @param int|null $blog_id
		 *
		 * @return string
		 *
		 * @since 1.0
		 */
		public function get_upload_dir( $dir = '', $blog_id = null ) {
			/** @var $wp_filesystem \WP_Filesystem_Base */
			global $wp_filesystem;

			if ( ! $blog_id ) {
				$blog_id = get_current_blog_id();
			} else {
				if ( is_multisite() ) {
					switch_to_blog( $blog_id );
				}
			}

			if ( empty( $this->upload_dir[ $blog_id ] ) ) {
				$uploads                      = wp_upload_dir();
				$this->upload_dir[ $blog_id ] = $uploads['basedir'];
			}

			$upload_dir = wp_normalize_path( trailingslashit( $this->upload_dir[ $blog_id ] ) . untrailingslashit( $dir ) );

			if ( is_null( $wp_filesystem ) ) {
				if ( is_dir( $upload_dir ) ) {
					wp_mkdir_p( $upload_dir );
				}
			} else {
				if ( ! $wp_filesystem->is_dir( $upload_dir ) ) {
					wp_mkdir_p( $upload_dir );
				}
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
		 * @since 1.0
		 */
		public function get_upload_url( $url = '', $blog_id = null ) {
			if ( ! $blog_id ) {
				$blog_id = get_current_blog_id();
			} else {
				if ( is_multisite() ) {
					switch_to_blog( $blog_id );
				}
			}

			if ( empty( $this->upload_url[ $blog_id ] ) ) {
				$uploads                      = wp_upload_dir();
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
		 * Get a directory size
		 *
		 * @param $directory
		 *
		 * @return float|int
		 */
		public function dir_size( $directory ) {
			if ( $directory == 'temp' ) {
				$directory = UM()->files()->upload_temp;
				$size = 0;

				if ( empty( $directory ) || ! is_dir( $directory ) ) {
					return 0;
				}

				if ( ! class_exists( '\RecursiveIteratorIterator' ) || ! class_exists( '\RecursiveIteratorIterator' ) ) {
					return 0;
				}

				foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $directory ) ) as $file ) {
					$filename = $file->getFilename();
					if ( $filename == '.' || $filename == '..' ) {
						continue;
					}

					$size += $file->getSize();
				}
				return round ( $size / 1048576, 2 );
			}
			return 0;
		}

		/**
		 * Remove all files, which are older than 24 hours
		 *
		 * @since 3.0.0
		 */
		public function clear_temp_dir() {
			/** @var $wp_filesystem \WP_Filesystem_Base */
			global $wp_filesystem;

			/**
			 * Filters the maximum file age in the temp folder. By default, it's 24 hours.
			 *
			 * @since 1.0
			 * @hook jb_filesystem_max_file_age
			 *
			 * @param {int} $file_age Temp file age in seconds.
			 *
			 * @return {int} Temp file age in seconds.
			 */
			$file_age = apply_filters( 'jb_filesystem_max_file_age', 24 * 3600 ); // Temp file age in seconds

			if ( is_null( $wp_filesystem ) ) {
				if ( ! is_dir( $this->temp_upload_dir ) ) {
					return;
				}
			} else {
				if ( ! $wp_filesystem->is_dir( $this->temp_upload_dir ) ) {
					return;
				}
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

				if ( is_null( $wp_filesystem ) ) {
					if ( @filemtime( $filepath ) < time() - $file_age ) {
						@unlink( $filepath );
					}
				} else {
					// Remove temp file if it is older than the max age and is not the current file
					if ( $wp_filesystem->mtime( $filepath ) < time() - $file_age ) {
						$wp_filesystem->delete( $filepath );
					}
				}
			}

			@closedir( $dir );
			// phpcs:enable WordPress.PHP.NoSilencedErrors.Discouraged
		}
	}
}
