<?php
namespace um\common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Checks anonymous HTTP access without changing server access rules.
 */
class Upload_Security {
	const OPTION = 'um_upload_security_result';
	const EVENT  = 'um_check_upload_security';
	const LOCK   = 'um_upload_security_lock';

	/** Register on frontend, admin and cron requests. */
	public function hooks() {
		add_action( 'init', array( $this, 'maybe_schedule' ), 20 );
		add_action( self::EVENT, array( $this, 'run' ) );
		add_action( 'admin_post_um_check_upload_security', array( $this, 'recheck' ) );
	}

	/** Register one daily action after Action Scheduler has initialized. */
	public function maybe_schedule() {
		// Remove the one-off WP-Cron event created by the initial implementation.
		if ( wp_next_scheduled( self::EVENT ) ) {
			wp_clear_scheduled_hook( self::EVENT );
		}
		$scheduler = UM()->maybe_action_scheduler();
		if ( ! $scheduler->is_enabled() || ! $scheduler->is_hook_enabled( self::EVENT ) || ! did_action( 'action_scheduler_init' ) ) {
			return;
		}
		if ( $scheduler->has_scheduled_action( self::EVENT ) ) {
			return;
		}
		$result = $this->get_result();
		$scheduler->schedule_recurring_action(
			max( time() + MINUTE_IN_SECONDS, $result['checked_at'] + DAY_IN_SECONDS ),
			DAY_IN_SECONDS,
			self::EVENT,
			array(),
			'',
			true
		);
	}

	/** @return array Cached evidence shared by notices and Site Health. */
	public function get_result() {
		return wp_parse_args(
			get_option( self::OPTION, array() ),
			array(
				'state'         => 'pending',
				'checked_at'    => 0,
				'reason'        => '',
				'probes'        => array(),
				'htaccess_rule' => false,
			)
		);
	}

	/** @return string Administrator-only manual retry URL. */
	public function recheck_url() {
		return wp_nonce_url( admin_url( 'admin-post.php?action=um_check_upload_security' ), self::EVENT );
	}

	/** Run a bounded manual check, including when WP-Cron is disabled. */
	public function recheck() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to run this check.', 'ultimate-member' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( self::EVENT );
		$this->run();
		wp_safe_redirect( admin_url( 'site-health.php' ) );
		exit;
	}

	/**
	 * Write only synthetic files; always remove them after the HTTP request.
	 *
	 * @param string $directory Existing local directory.
	 * @param string $url       Configured public URL of that directory.
	 * @param string $extension Non-executable probe extension.
	 * @return array
	 */
	private function probe( $directory, $url, $extension ) {
		$name   = 'um-access-check-' . wp_generate_uuid4() . '.' . $extension;
		$path   = trailingslashit( $directory ) . $name;
		$marker = 'um-access-check:' . wp_generate_uuid4();
		$result = array(
			'state'  => 'unknown',
			'code'   => 0,
			'reason' => 'write_failed',
		);
		$handle = @fopen( $path, 'x' );
		if ( false === $handle ) {
			return $result;
		}

		try {
			$written = fwrite( $handle, $marker );
			fclose( $handle );
			$handle = null;
			if ( strlen( $marker ) !== $written || ! is_file( $path ) ) {
				return $result;
			}
			// Only administrator-configured upload URLs; never follow redirects or send credentials.
			$response = wp_remote_get(
				trailingslashit( $url ) . $name,
				array(
					'timeout'             => 2,
					'redirection'         => 0,
					'cookies'             => array(),
					'headers'             => array( 'Cache-Control' => 'no-cache, no-store' ),
					'limit_response_size' => 4096,
				)
			);
			$result   = self::classify_response( $response, $marker );
		} finally {
			if ( is_resource( $handle ) ) {
				fclose( $handle );
			}
			if ( ! @unlink( $path ) ) {
				$result['cleanup_failed'] = true;
			}
		}
		return $result;
	}

	/**
	 * An HTTP error alone must never establish successful protection.
	 *
	 * @param array|\WP_Error $response HTTP response.
	 * @param string          $marker   Expected synthetic content.
	 * @return array
	 */
	public static function classify_response( $response, $marker ) {
		if ( is_wp_error( $response ) ) {
			return array(
				'state'  => 'unknown',
				'code'   => 0,
				'reason' => 'request_failed',
			);
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( false !== strpos( wp_remote_retrieve_body( $response ), $marker ) ) {
			return array(
				'state'  => 'exposed',
				'code'   => $code,
				'reason' => 'content_received',
			);
		}
		if ( in_array( $code, array( 403, 404 ), true ) ) {
			return array(
				'state'  => 'blocked',
				'code'   => $code,
				'reason' => 'access_denied',
			);
		}
		return array(
			'state'  => 'unknown',
			'code'   => $code,
			'reason' => 'unexpected_response',
		);
	}

	/** @return array Latest check. A lock prevents overlapping manual and cron probes. */
	public function run() {
		$lock = (int) get_option( self::LOCK );
		if ( $lock && $lock < time() - 120 ) {
			delete_option( self::LOCK );
		}
		if ( ! add_option( self::LOCK, time(), '', false ) ) {
			return $this->get_result();
		}
		$result = array(
			'state'         => 'unknown',
			'checked_at'    => time(),
			'reason'        => 'directory_missing',
			'probes'        => array(),
			'htaccess_rule' => false,
		);
		try {
			$base = UM()->uploader()->get_upload_base_dir();
			$url  = UM()->uploader()->get_upload_base_url();
			if ( is_dir( $base ) ) {
				$htaccess = trailingslashit( $base ) . '.htaccess';
				if ( is_readable( $htaccess ) ) {
					$rules                   = file_get_contents( $htaccess, false, null, 0, 65536 );
					$result['htaccess_rule'] = (bool) preg_match( '/^\s*(?:deny\s+from\s+all|require\s+all\s+denied)\s*(?:#.*)?$/mi', $rules );
				}
				$directories = array( '' );
				// Sample one real user directory, where rules can differ from the root.
				foreach ( new \DirectoryIterator( $base ) as $entry ) {
					if ( $entry->isDir() && ! $entry->isLink() && ctype_digit( $entry->getFilename() ) ) {
						$directories[] = $entry->getFilename();
						break;
					}
				}
				$uploads = wp_upload_dir();
				// A public control file verifies the local-filesystem-to-public-URL mapping.
				$control           = $this->probe( $uploads['basedir'], $uploads['baseurl'], 'txt' );
				$result['control'] = $control;
				$result['state']   = 'blocked';
				$result['reason']  = 'access_denied';
				foreach ( $directories as $directory ) {
					foreach ( array( 'txt', 'jpg', 'pdf' ) as $extension ) {
						$probe              = $this->probe( trailingslashit( $base ) . $directory, trailingslashit( $url ) . $directory, $extension );
						$result['probes'][] = array_merge(
							$probe,
							array(
								'directory' => $directory,
								'extension' => $extension,
							)
						);
						if ( 'exposed' === $probe['state'] ) {
							$result['state']  = 'exposed';
							$result['reason'] = 'content_received';
						} elseif ( 'exposed' !== $result['state'] && ( 'unknown' === $probe['state'] || ! empty( $probe['cleanup_failed'] ) ) ) {
							$result['state']  = 'unknown';
							$result['reason'] = ! empty( $probe['cleanup_failed'] ) ? 'cleanup_failed' : $probe['reason'];
						}
					}
				}
				if ( 'blocked' === $result['state'] && ( 'exposed' !== $control['state'] || ! empty( $control['cleanup_failed'] ) ) ) {
					$result['state']  = 'unknown';
					$result['reason'] = 'control_failed';
				}
			}
		} catch ( \Throwable $error ) {
			if ( 'exposed' !== $result['state'] ) {
				$result['state']  = 'unknown';
				$result['reason'] = 'check_failed';
			}
		} finally {
			update_option( self::OPTION, $result, false );
			delete_option( self::LOCK );
		}
		return $result;
	}
}
