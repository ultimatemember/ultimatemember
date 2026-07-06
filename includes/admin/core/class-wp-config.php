<?php
namespace um\admin\core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\admin\core\WP_Config' ) ) {

	/**
	 * Class WP_Config
	 *
	 * Thin wrapper around the bundled `wp-cli/wp-config-transformer` library used to safely
	 * add/update/remove PHP constants in wp-config.php. Backs the `api_key` settings field type,
	 * whose secret values are stored as constants instead of in the `um_options` DB array.
	 *
	 * @package um\admin\core
	 * @since 2.12.1
	 */
	class WP_Config {

		/**
		 * Path to the bundled WPConfigTransformer library.
		 *
		 * Mirrors the Action Scheduler bundling convention ({@see \um\action_scheduler\Init}) — the
		 * file is copied into includes/lib/ at build time via the `wp-config-transformer` composer script.
		 *
		 * @var string
		 */
		protected $lib_path = UM_PATH . 'includes/lib/wp-config-transformer/WPConfigTransformer.php';

		/**
		 * Whether the WPConfigTransformer library is loadable.
		 *
		 * @return bool
		 */
		public function is_available() {
			if ( ! class_exists( '\WPConfigTransformer' ) && file_exists( $this->lib_path ) ) {
				require_once $this->lib_path;
			}

			return class_exists( '\WPConfigTransformer' );
		}

		/**
		 * Resolve the path to wp-config.php, handling the standard WordPress non-subdir fallback
		 * (wp-config.php one level above ABSPATH when it isn't a subdirectory install).
		 *
		 * @return string
		 */
		public function get_config_path() {
			$path = ABSPATH . 'wp-config.php';

			if ( ! file_exists( $path )
				&& file_exists( dirname( ABSPATH ) . '/wp-config.php' )
				&& ! file_exists( dirname( ABSPATH ) . '/wp-settings.php' )
			) {
				$path = dirname( ABSPATH ) . '/wp-config.php';
			}

			return $path;
		}

		/**
		 * Whether constants can currently be written to wp-config.php.
		 *
		 * @return bool
		 */
		public function is_writable() {
			$path = $this->get_config_path();
			return $this->is_available() && file_exists( $path ) && is_writable( $path );
		}

		/**
		 * Add or update a constant in wp-config.php.
		 *
		 * Uses WPConfigTransformer::update() with `add => true`, which replaces the value in place
		 * when the constant already exists and adds it otherwise — so it never duplicates a define().
		 *
		 * @param string $name  Constant name.
		 * @param string $value Constant value.
		 *
		 * @return bool True on success, false on failure (e.g. wp-config.php not writable).
		 */
		public function set_constant( $name, $value ) {
			if ( ! $this->is_available() ) {
				return false;
			}

			try {
				$transformer = new \WPConfigTransformer( $this->get_config_path() );
				// update() returns false when the value is already identical (save() short-circuits an
				// unchanged file) — with add => true that means the constant is present and correct, which
				// is still success. Only a genuine write problem throws (caught below).
				$written = $transformer->update( 'constant', $name, (string) $value, array( 'raw' => false, 'add' => true ) );
				$ok      = $written || $transformer->exists( 'constant', $name );
				if ( $ok ) {
					$this->flush_config_opcache();
				}
				return $ok;
			} catch ( \Exception $e ) {
				return false;
			}
		}

		/**
		 * Remove a constant from wp-config.php.
		 *
		 * @param string $name Constant name.
		 *
		 * @return bool True on success (including when the constant was already absent), false on failure.
		 */
		public function remove_constant( $name ) {
			if ( ! $this->is_available() ) {
				return false;
			}

			try {
				$transformer = new \WPConfigTransformer( $this->get_config_path() );
				if ( ! $transformer->exists( 'constant', $name ) ) {
					return true;
				}
				$removed = (bool) $transformer->remove( 'constant', $name );
				if ( $removed ) {
					$this->flush_config_opcache();
				}
				return $removed;
			} catch ( \Exception $e ) {
				return false;
			}
		}

		/**
		 * Invalidate the OPcache entry for wp-config.php after writing to it.
		 *
		 * OPcache caches the compiled wp-config.php and only revalidates its mtime every
		 * `opcache.revalidate_freq` seconds (default 2). Without this, the redirect that follows a
		 * settings save reloads the stale wp-config.php, so a freshly written constant isn't defined
		 * yet and the field renders empty until the next request. Forcing invalidation makes the new
		 * constant available on the very next request.
		 *
		 * @since 2.12.1
		 */
		protected function flush_config_opcache() {
			if ( function_exists( 'opcache_invalidate' ) ) {
				// @ suppresses notices when opcache.restrict_api blocks the call; harmless if it no-ops.
				@opcache_invalidate( $this->get_config_path(), true ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			}
		}
	}
}
