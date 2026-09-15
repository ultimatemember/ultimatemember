<?php
namespace um\core;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\core\WP_Stateless_Integration' ) ) {


	/**
	 * Class WP_Stateless_Integration
	 *
	 * Keeps Ultimate Member uploads working when the WP-Stateless plugin offloads
	 * the uploads directory to Google Cloud Storage.
	 *
	 * @package um\core
	 * @since 2.14.0
	 */
	class WP_Stateless_Integration {


		/**
		 * Priority WP-Stateless uses for the `upload_dir` filter. It replaces the whole
		 * uploads array there, so only priorities above it see the cloud paths.
		 *
		 * @var int
		 * @since 2.14.0
		 */
		var $stateless_priority = 99;


		/**
		 * Guards `fix_upload_dir()` against re-entering itself. Reading the uploader
		 * instance may construct it, and its constructor calls `wp_upload_dir()`.
		 *
		 * @var bool
		 * @since 2.14.0
		 */
		var $fixing_upload_dir = false;


		/**
		 * Integration constructor.
		 *
		 * @since 2.14.0
		 */
		function __construct() {
			// Restore the local upload paths for UM's own upload requests.
			add_filter( 'upload_dir', array( &$this, 'fix_upload_dir' ), $this->stateless_priority + 1, 1 );

			// Keep UM's cached uploads base path on the local filesystem.
			add_filter( 'um_upload_basedir_filter', array( &$this, 'fix_files_basedir' ), $this->stateless_priority - 9, 1 );
			add_filter( 'um_upload_baseurl_filter', array( &$this, 'fix_files_baseurl' ), $this->stateless_priority - 9, 1 );

			add_action( 'init', array( &$this, 'init' ), 1 );

			// UM only reports the files it replaced on form submission, so the
			// removed ones are caught before the submission is processed.
			add_filter( 'um_user_pre_updating_files_array', array( &$this, 'remove_empty_files' ), 20, 2 );

			// Sync the uploaded files to GCS.
			add_action( 'um_after_move_temporary_files', array( &$this, 'sync_moved_files' ), 20, 3 );
			add_filter( 'um_upload_image_process__profile_photo', array( &$this, 'sync_processed_photo' ), 20, 7 );
			add_filter( 'um_upload_image_process__cover_photo', array( &$this, 'sync_processed_photo' ), 20, 7 );

			// Mirror the removals on GCS.
			add_action( 'um_after_remove_profile_photo', array( &$this, 'remove_profile_photo' ), 20, 1 );
			add_action( 'um_after_remove_cover_photo', array( &$this, 'remove_cover_photo' ), 20, 1 );
			add_action( 'um_delete_user', array( &$this, 'remove_user_files' ), 20, 1 );
		}


		/**
		 * Check whether WP-Stateless is loaded.
		 *
		 * The plugin registers most of its hooks during `plugins_loaded`, so every
		 * entry point re-checks it instead of relying on a constructor-time flag.
		 *
		 * @since 2.14.0
		 *
		 * @return bool
		 */
		function is_active() {
			return function_exists( 'ud_get_stateless_media' ) && class_exists( '\wpCloud\StatelessMedia\Bootstrap' );
		}


		/**
		 * Get the WP-Stateless instance.
		 *
		 * @since 2.14.0
		 *
		 * @return \wpCloud\StatelessMedia\Bootstrap|false
		 */
		function get_stateless() {
			if ( ! $this->is_active() ) {
				return false;
			}

			$stateless = ud_get_stateless_media();

			return is_object( $stateless ) ? $stateless : false;
		}


		/**
		 * Get the current WP-Stateless mode.
		 *
		 * @since 2.14.0
		 *
		 * @return string
		 */
		function get_mode() {
			$stateless = $this->get_stateless();
			if ( ! $stateless || ! method_exists( $stateless, 'get' ) ) {
				return '';
			}

			$mode = $stateless->get( 'sm.mode' );

			return is_string( $mode ) ? $mode : '';
		}


		/**
		 * Is the uploads directory offloaded in stateless mode?
		 *
		 * Only stateless mode replaces the uploads array with `gs://` paths.
		 *
		 * @since 2.14.0
		 *
		 * @return bool
		 */
		function is_stateless() {
			$stateless = $this->get_stateless();
			if ( ! $stateless || ! method_exists( $stateless, 'is_mode' ) ) {
				return false;
			}

			return (bool) $stateless->is_mode( 'stateless' );
		}


		/**
		 * Should UM push its uploads to GCS?
		 *
		 * @since 2.14.0
		 *
		 * @return bool
		 */
		function is_syncable() {
			$stateless = $this->get_stateless();
			if ( ! $stateless ) {
				return false;
			}

			if ( in_array( $this->get_mode(), array( '', 'disabled' ), true ) ) {
				return false;
			}

			if ( method_exists( $stateless, 'is_connected_to_gs' ) && ! $stateless->is_connected_to_gs() ) {
				return false;
			}

			return true;
		}


		/**
		 * Get the uploads directory without any WP-Stateless filtering applied.
		 *
		 * WP-Stateless skips its `upload_dir` filter while `$default_dir` is set, the
		 * same way the plugin itself reads the real paths.
		 *
		 * @since 2.14.0
		 *
		 * @return array
		 */
		function get_local_upload_dir() {
			global $default_dir;

			$previous    = $default_dir;
			$default_dir = true;

			try {
				$uploads = wp_upload_dir();
			} finally {
				// Never leave the global set, it disables the offloading for the
				// rest of the request.
				$default_dir = $previous;
			}

			return $uploads;
		}


		/**
		 * Refresh the upload directories UM cached at instantiation with local paths.
		 *
		 * UM builds its upload paths while the plugins are being loaded, so they may
		 * already point to GCS depending on the plugin load order.
		 *
		 * @since 2.14.0
		 */
		function init() {
			if ( ! $this->is_stateless() ) {
				return;
			}

			$local = $this->get_local_upload_dir();
			if ( ! empty( $local['error'] ) ) {
				return;
			}

			$uploader = UM()->uploader();
			if ( $uploader && false !== strpos( (string) $uploader->wp_upload_dir['basedir'], 'gs://' ) ) {
				$uploader->wp_upload_dir = $local;
			}

			$files = UM()->files();
			if ( $files && false !== strpos( (string) $files->upload_basedir, 'gs://' ) ) {
				$files->setup_paths();
			}

			// Make UM uploads available in the WP-Stateless "Compatibility" sync tab.
			if ( $this->is_syncable() ) {
				do_action( 'sm:sync::register_dir', 'ultimatemember' ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores, WordPress.NamingConventions.ValidHookName.NotLowercase
			}
		}


		/**
		 * Restore the local upload paths for UM's upload requests.
		 *
		 * Runs after WP-Stateless replaced the uploads array. UM's own filter at
		 * priority 10 already computed the correct local user paths from the original
		 * arguments, so they are reused here instead of being recalculated.
		 *
		 * @since 2.14.0
		 *
		 * @param array $args
		 *
		 * @return array
		 */
		function fix_upload_dir( $args ) {
			global $default_dir;

			if ( ! empty( $default_dir ) || $this->fixing_upload_dir || ! $this->is_stateless() ) {
				return $args;
			}

			$this->fixing_upload_dir = true;

			$uploader = UM()->uploader();
			if ( empty( $uploader->replace_upload_dir ) ) {
				$this->fixing_upload_dir = false;
				return $args;
			}

			$local = $this->get_local_upload_dir();
			if ( ! empty( $local['error'] ) || empty( $local['basedir'] ) ) {
				$this->fixing_upload_dir = false;
				return $args;
			}

			$uploader->wp_upload_dir = $local;

			$args['basedir'] = $local['basedir'];
			$args['baseurl'] = $local['baseurl'];

			if ( ! empty( $uploader->upload_user_basedir ) ) {
				$args['path'] = $uploader->upload_user_basedir;
			}

			if ( ! empty( $uploader->upload_user_baseurl ) ) {
				$args['url'] = $uploader->upload_user_baseurl;
			}

			$this->fixing_upload_dir = false;

			return $args;
		}


		/**
		 * Keep UM's uploads base directory on the local filesystem.
		 *
		 * Directory creation, globbing and removal all run on this path, and none of
		 * them work through the `gs://` stream wrapper.
		 *
		 * @since 2.14.0
		 *
		 * @param string $basedir
		 *
		 * @return string
		 */
		function fix_files_basedir( $basedir ) {
			if ( ! $this->is_stateless() ) {
				return $basedir;
			}

			$local = $this->get_local_upload_dir();
			if ( ! empty( $local['error'] ) || empty( $local['basedir'] ) ) {
				return $basedir;
			}

			return $local['basedir'] . '/ultimatemember/';
		}


		/**
		 * Keep UM's uploads base URL on the site domain.
		 *
		 * @since 2.14.0
		 *
		 * @param string $baseurl
		 *
		 * @return string
		 */
		function fix_files_baseurl( $baseurl ) {
			if ( ! $this->is_stateless() ) {
				return $baseurl;
			}

			$local = $this->get_local_upload_dir();
			if ( ! empty( $local['error'] ) || empty( $local['baseurl'] ) ) {
				return $baseurl;
			}

			return $local['baseurl'] . '/ultimatemember/';
		}


		/**
		 * Get a file path relative to the uploads directory.
		 *
		 * This is the object name WP-Stateless expects, and it keeps the directory
		 * structure inside the bucket.
		 *
		 * @since 2.14.0
		 *
		 * @param string $path
		 *
		 * @return string
		 */
		function get_relative_name( $path ) {
			$path = wp_normalize_path( $path );

			// Never hand a traversing name to the cloud client. The stored file meta
			// is user data, so a name with parent segments is not trusted.
			if ( false !== strpos( $path, '../' ) || './' === substr( $path, 0, 2 ) || false !== strpos( $path, "\0" ) ) {
				return '';
			}

			$local = $this->get_local_upload_dir();

			$basedir = isset( $local['basedir'] ) ? wp_normalize_path( $local['basedir'] ) : '';

			if ( ! empty( $basedir ) && 0 === strpos( $path, trailingslashit( $basedir ) ) ) {
				$path = substr( $path, strlen( trailingslashit( $basedir ) ) );
			}

			return ltrim( $path, '/' );
		}


		/**
		 * Is the file inside UM's temporary upload directory?
		 *
		 * Temporary uploads are only moved into the user directory on form
		 * submission, so syncing them would leave orphaned objects in the bucket.
		 *
		 * Accepts an absolute path or a name relative to the uploads directory.
		 *
		 * @since 2.14.0
		 *
		 * @param string $path
		 *
		 * @return bool
		 */
		function is_temp_file( $path ) {
			$name = $this->get_relative_name( $path );
			if ( empty( $name ) ) {
				return false;
			}

			$temp_dir = trailingslashit( trim( wp_normalize_path( UM()->uploader()->get_core_upload_dir() ), '/' ) ) . 'temp';

			return 0 === strpos( $name, trailingslashit( $temp_dir ) );
		}


		/**
		 * Upload a local file to GCS.
		 *
		 * @since 2.14.0
		 *
		 * @param string $path Absolute local file path.
		 *
		 * @return bool
		 */
		function sync_file( $path ) {
			if ( ! $this->is_syncable() || $this->is_temp_file( $path ) || ! file_exists( $path ) || ! is_readable( $path ) ) {
				return false;
			}

			$name = $this->get_relative_name( $path );
			if ( empty( $name ) ) {
				return false;
			}

			// For stateless requests outside of AJAX, WP-Stateless only patches the
			// metadata of an existing object and never sends the bytes, so the file
			// is uploaded here first. The sync action below then updates the plugin
			// bookkeeping for the object it finds.
			if ( $this->is_stateless() && ! wp_doing_ajax() ) {
				// Bail out instead of reporting a success for the action below when
				// the bytes never reached the bucket.
				if ( ! $this->upload_to_bucket( $name, $path ) ) {
					return false;
				}
			}

			/**
			 * Sync a non-media file to GCS.
			 *
			 * @type action
			 * @title sm:sync::syncFile
			 * @description Sync the file with Google Cloud Storage.
			 * @input_vars
			 * [{"var":"$name","type":"string","desc":"File name relative to the uploads directory"},
			 *  {"var":"$absolutePath","type":"string","desc":"Absolute local file path"},
			 *  {"var":"$forced","type":"int","desc":"2 forces the existing object to be overwritten"},
			 *  {"var":"$args","type":"array","desc":"Sync arguments"}]
			 * @change_log
			 * ["Since: 2.14.0"]
			 * @usage add_action( 'sm:sync::syncFile', 'function_name', 10, 4 );
			 * @example
			 * <?php
			 * add_action( 'sm:sync::syncFile', 'my_sync_file', 10, 4 );
			 * function my_sync_file( $name, $absolutePath, $forced, $args ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action(
				'sm:sync::syncFile', // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores, WordPress.NamingConventions.ValidHookName.NotLowercase
				$name,
				$path,
				2,
				array(
					// Keep UM's local copies, its file checks rely on them.
					'ephemeral'      => false,
					// Keep the directory structure inside the bucket.
					'use_root'       => 0,
					'source'         => 'ultimate-member',
					'source_version' => UM_VERSION,
				)
			);

			return true;
		}


		/**
		 * Send a file to the bucket through the WP-Stateless client.
		 *
		 * @since 2.14.0
		 *
		 * @param string $name Object name relative to the uploads directory.
		 * @param string $path Absolute local file path.
		 *
		 * @return bool
		 */
		function upload_to_bucket( $name, $path ) {
			$stateless = $this->get_stateless();
			if ( ! $stateless || ! method_exists( $stateless, 'get_client' ) ) {
				return false;
			}

			$client = $stateless->get_client();
			if ( is_wp_error( $client ) || ! is_object( $client ) || ! method_exists( $client, 'add_media' ) ) {
				return false;
			}

			$filetype  = wp_check_filetype( $path );
			$mime_type = ! empty( $filetype['type'] ) ? $filetype['type'] : 'application/octet-stream';

			try {
				$media = $client->add_media(
					array(
						'use_root'     => 0,
						'force'        => true,
						'name'         => $name,
						'absolutePath' => $path,
						'mimeType'     => $mime_type,
						'metadata'     => array(
							'child-of'      => dirname( $name ),
							'file-hash'     => md5( $name ),
							'source'        => 'ultimate-member',
							'sourceVersion' => UM_VERSION,
						),
					)
				);
			} catch ( \Throwable $e ) {
				return false;
			}

			// The client returns a WP_Error instead of throwing when the upload fails.
			if ( is_wp_error( $media ) ) {
				return false;
			}

			return ! empty( $media );
		}


		/**
		 * Delete a local file from GCS.
		 *
		 * @since 2.14.0
		 *
		 * @param string $name Object name relative to the uploads directory.
		 *
		 * @return void
		 */
		function delete_file( $name ) {
			if ( ! $this->is_syncable() || empty( $name ) ) {
				return;
			}

			$name = ltrim( wp_normalize_path( $name ), '/' );
			if ( empty( $name ) ) {
				return;
			}

			/**
			 * Delete a non-media file from GCS.
			 *
			 * @type action
			 * @title sm:sync::deleteFile
			 * @description Remove the file from Google Cloud Storage.
			 * @input_vars
			 * [{"var":"$name","type":"string","desc":"File name relative to the uploads directory"}]
			 * @change_log
			 * ["Since: 2.14.0"]
			 * @usage add_action( 'sm:sync::deleteFile', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_action( 'sm:sync::deleteFile', 'my_delete_file', 10, 1 );
			 * function my_delete_file( $name ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'sm:sync::deleteFile', $name ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores, WordPress.NamingConventions.ValidHookName.NotLowercase
		}


		/**
		 * Resolve a file value stored in the user meta to an uploads relative name.
		 *
		 * The profile and cover photos are stored as an absolute path, while the
		 * custom field files are stored as a name inside the user directory.
		 *
		 * @since 2.14.0
		 *
		 * @param int    $user_id
		 * @param string $file
		 *
		 * @return string
		 */
		function get_user_file_name( $user_id, $file ) {
			if ( empty( $file ) ) {
				return '';
			}

			$path = wp_normalize_path( $file );

			if ( 0 === strpos( $path, '/' ) || false !== strpos( $path, ':' ) ) {
				return $this->get_relative_name( $path );
			}

			$user_basedir = UM()->uploader()->get_upload_user_base_dir( $user_id );

			return $this->get_relative_name( $user_basedir . DIRECTORY_SEPARATOR . $path );
		}


		/**
		 * Delete the files of the fields that were cleared on form submission.
		 *
		 * UM removes the local file of a cleared field without reporting it, so the
		 * matching object has to be removed from the bucket here.
		 *
		 * @since 2.14.0
		 *
		 * @param array $files
		 * @param int   $user_id
		 *
		 * @return array
		 */
		function remove_empty_files( $files, $user_id ) {
			if ( ! $this->is_syncable() || empty( $files ) || ! is_array( $files ) || empty( $user_id ) ) {
				return $files;
			}

			foreach ( $files as $key => $file ) {
				if ( 'empty_file' !== $file ) {
					continue;
				}

				$stored = get_user_meta( $user_id, $key, true );
				if ( ! empty( $stored ) ) {
					$this->delete_file( $this->get_user_file_name( $user_id, $stored ) );
				}
			}

			return $files;
		}


		/**
		 * Sync the files moved from the temporary directory on form submission.
		 *
		 * @since 2.14.0
		 *
		 * @param int   $user_id
		 * @param array $new_files
		 * @param array $old_files
		 *
		 * @return void
		 */
		function sync_moved_files( $user_id, $new_files, $old_files = array() ) {
			if ( ! $this->is_syncable() ) {
				return;
			}

			foreach ( (array) $old_files as $old_file ) {
				if ( ! empty( $old_file ) ) {
					$this->delete_file( $this->get_user_file_name( $user_id, $old_file ) );
				}
			}

			if ( empty( $new_files ) ) {
				return;
			}

			$user_basedir = UM()->uploader()->get_upload_user_base_dir( $user_id );

			foreach ( $new_files as $new_file ) {
				if ( ! empty( $new_file ) ) {
					$this->sync_file( $user_basedir . DIRECTORY_SEPARATOR . $new_file );
				}
			}
		}


		/**
		 * Sync the processed profile or cover photo with its resized versions.
		 *
		 * Hooked after UM saved the photo, so the resized files are already on disk.
		 *
		 * @since 2.14.0
		 *
		 * @param array  $response
		 * @param string $image_path
		 * @param string $src
		 * @param string $key
		 * @param int    $user_id
		 * @param string $coord
		 * @param array  $crop
		 *
		 * @return array
		 */
		function sync_processed_photo( $response, $image_path, $src, $key, $user_id, $coord, $crop ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			if ( ! $this->is_syncable() ) {
				return $response;
			}

			foreach ( $this->get_dir_files( dirname( $image_path ), $key . '*' ) as $file ) {
				$this->sync_file( $file );
			}

			return $response;
		}


		/**
		 * Delete the profile photo with its resized versions from GCS.
		 *
		 * @since 2.14.0
		 *
		 * @param int $user_id
		 *
		 * @return void
		 */
		function remove_profile_photo( $user_id ) {
			$this->remove_processed_photo( $user_id, 'profile_photo' );
		}


		/**
		 * Delete the cover photo with its resized versions from GCS.
		 *
		 * @since 2.14.0
		 *
		 * @param int $user_id
		 *
		 * @return void
		 */
		function remove_cover_photo( $user_id ) {
			$this->remove_processed_photo( $user_id, 'cover_photo' );
		}


		/**
		 * Delete a processed photo from GCS.
		 *
		 * Hooked before UM removes the local files, so the directory can still be read.
		 *
		 * @since 2.14.0
		 *
		 * @param int    $user_id
		 * @param string $type
		 *
		 * @return void
		 */
		function remove_processed_photo( $user_id, $type ) {
			if ( ! $this->is_syncable() || empty( $user_id ) ) {
				return;
			}

			$user_basedir = UM()->uploader()->get_upload_user_base_dir( $user_id );

			foreach ( $this->get_dir_files( $user_basedir, $type . '*' ) as $file ) {
				$this->delete_file( $this->get_relative_name( $file ) );
			}
		}


		/**
		 * Delete the files of a deleted user from GCS.
		 *
		 * Hooked before UM removes the local user directory.
		 *
		 * @since 2.14.0
		 *
		 * @param int $user_id
		 *
		 * @return void
		 */
		function remove_user_files( $user_id ) {
			if ( ! $this->is_syncable() || empty( $user_id ) ) {
				return;
			}

			$user_basedir = UM()->uploader()->get_upload_user_base_dir( $user_id );

			foreach ( $this->get_dir_files( $user_basedir, '*' ) as $file ) {
				$this->delete_file( $this->get_relative_name( $file ) );
			}
		}


		/**
		 * List the local files matching a pattern in a directory.
		 *
		 * @since 2.14.0
		 *
		 * @param string $dir
		 * @param string $pattern Glob pattern.
		 *
		 * @return array
		 */
		function get_dir_files( $dir, $pattern ) {
			if ( ! is_dir( $dir ) ) {
				return array();
			}

			$matches = glob( trailingslashit( $dir ) . $pattern );
			if ( empty( $matches ) || ! is_array( $matches ) ) {
				return array();
			}

			$files = array();

			foreach ( $matches as $match ) {
				if ( is_file( $match ) ) {
					$files[] = wp_normalize_path( $match );
				}
			}

			return $files;
		}
	}
}
