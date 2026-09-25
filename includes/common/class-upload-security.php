<?php
namespace um\common;

use DirectoryIterator;
use Throwable;
use WP_Error;

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
		add_action( 'admin_post_um_set_htaccess_rule', array( $this, 'set_htaccess_rule' ) );
		add_filter( 'um_settings_structure', array( $this, 'settings_section' ) );
	}

	/** Add actionable server instructions to Access > Other. */
	public function settings_section( $settings ) {
		$settings['access']['sections']['other']['form_sections']['upload_protection'] = array(
			'title'       => __( 'Upload protection', 'ultimate-member' ),
			'description' => __( 'Control direct access to files stored by Ultimate Member.', 'ultimate-member' ),
			'fields'      => array(
				array(
					'id'    => 'um_upload_protection_instructions',
					'type'  => 'info_text',
					'value' => wp_slash( '<div id="um-upload-protection" style="scroll-margin-top: 48px;">' . $this->setup_instructions() . '</div>' ),
				),
			),
		);
		return $settings;
	}

	/** Check the actual target file, or its parent when creating it. */
	public function can_write_htaccess() {
		$directory = UM()->uploader()->get_upload_base_dir();
		$path      = trailingslashit( $directory ) . '.htaccess';
		return ! is_link( $path ) && ( file_exists( $path ) ? is_file( $path ) && wp_is_writable( $path ) : is_dir( $directory ) && wp_is_writable( $directory ) );
	}

	/** @return string Escaped instructions; no server configuration is changed here. */
	public function setup_instructions() {
		global $is_apache, $is_nginx;
		$html  = esc_html__( 'Private uploads should be available only through Ultimate Member download links, where access permissions are checked. Blocking direct URLs helps prevent visitors from bypassing those checks.', 'ultimate-member' );
		$html .= '<br><br><strong>' . esc_html__( 'Before applying protection', 'ultimate-member' ) . '</strong><br><br>' . esc_html__( 'This directory can also contain avatars, cover photos and upload previews. Older uploaders use direct URLs for these images. A directory-wide block will stop them from loading. Confirm that your uploaders and public images use compatible download handlers before applying these rules.', 'ultimate-member' );
		if ( $is_apache ) {
			$path  = wp_normalize_path( trailingslashit( UM()->uploader()->get_upload_base_dir() ) . '.htaccess' );
			$html .= '<h4>' . esc_html__( 'Apache / LiteSpeed', 'ultimate-member' ) . '</h4>';
			$html .= '<br>' . esc_html__( 'Add the following rule to the .htaccess file at this exact path. Existing rules should be preserved. Your hosting provider must allow .htaccess access restrictions for the rule to take effect.', 'ultimate-member' ) . '<br><br><i>' . esc_html( $path ) . '</i><br><pre><code>deny from all</code></pre>';
			if ( $this->can_write_htaccess() && current_user_can( 'manage_options' ) ) {
				$html .= '<br><br><a class="button" href="' . esc_url( $this->set_htaccess_url() ) . '">' . esc_html__( 'Add upload protection to .htaccess', 'ultimate-member' ) . '</a><br>';
			} else {
				$html .= '<br>' . esc_html__( 'WordPress cannot update this file automatically with the current permissions. Use your hosting file manager or SFTP to add the rule, or ask your hosting provider to do it. If the file does not exist, create it at the path shown above.', 'ultimate-member' );
			}
		}
		if ( $is_nginx ) {
			$url   = UM()->uploader()->get_upload_base_url();
			$path  = wp_parse_url( $url, PHP_URL_PATH );
			$html .= '<h4>' . esc_html__( 'nginx', 'ultimate-member' ) . '</h4><br>' . esc_html__( 'nginx does not read .htaccess files. Ask your hosting provider to add this restriction inside the server block that serves your upload URL. The configuration file location depends on your hosting setup. If your control panel offers custom nginx directives, use that facility with your provider\'s guidance; placing a file in the WordPress folder alone has no effect.', 'ultimate-member' ) . '<br><br><i>' . esc_html( $url ) . '</i>';
			if ( is_string( $path ) && '' !== $path && '/' !== $path ) {
				$path  = str_replace( array( '\\', '"', '$' ), array( '\\\\', '\\"', '\\$' ), trailingslashit( $path ) );
				$html .= '<pre><code>' . esc_html( 'location ^~ "' . $path . '" {' . "\n    return 403;\n}" ) . '</code></pre>';
			}
			$html .= '<br>' . esc_html__( 'This rule blocks direct file URLs; it does not configure download routing. Keep your existing WordPress routing and PHP handler. Have your provider validate the configuration with nginx -t and reload nginx. If uploads are served through a CDN, review its access rules and cached copies too.', 'ultimate-member' );
		}
		if ( ! $is_apache && ! $is_nginx ) {
			$html .= '<br>' . esc_html__( 'The web server type could not be identified as Apache or nginx. Ask your hosting provider how to restrict direct access to the Ultimate Member upload directory while keeping authorized downloads available.', 'ultimate-member' );
		}
		$html .= '<br>' . esc_html__( 'After applying protection, run the check again. Also test an authorized download, access by a user without permission, and avatar uploading and display. A successful direct-access check does not verify those workflows.', 'ultimate-member' ) . '<br><br><a href="' . esc_url( $this->recheck_url() ) . '">' . esc_html__( 'Check upload protection again', 'ultimate-member' ) . '</a><br>';
		return $html;
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
		$default = array(
			'state'         => 'pending',
			'checked_at'    => 0,
			'reason'        => '',
			'probes'        => array(),
			'htaccess_rule' => false,
		);
		return wp_parse_args( get_option( self::OPTION, array() ), $default );
	}

	/** @return string Administrator-only manual retry URL. */
	public function recheck_url() {
		return wp_nonce_url( admin_url( 'admin-post.php?action=um_check_upload_security' ), self::EVENT );
	}

	/** @return string Administrator-only manual set htaccess rule. */
	public function set_htaccess_url() {
		global $is_apache;
		if ( ! $is_apache ) {
			return '';
		}
		return wp_nonce_url( admin_url( 'admin-post.php?action=um_set_htaccess_rule' ), 'set_htaccess_rule' );
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

	/** Run a bounded manual check, including when WP-Cron is disabled. */
	public function set_htaccess_rule() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to run this check.', 'ultimate-member' ), '', array( 'response' => 403 ) );
		}

		global $is_apache;
		if ( ! $is_apache ) {
			wp_die( esc_html__( 'No need .htaccess for non-Apache servers.', 'ultimate-member' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( 'set_htaccess_rule' );

		$upload_dir = UM()->uploader()->get_upload_base_dir();
		if ( ! $this->can_write_htaccess() ) {
			wp_die( esc_html__( 'Upload directory is not writable.', 'ultimate-member' ), '', array( 'response' => 403 ) );
		}

		require_once ABSPATH . 'wp-admin/includes/misc.php';
		if ( ! insert_with_markers( wp_normalize_path( trailingslashit( $upload_dir ) . '.htaccess' ), 'Ultimate Member Upload Protection', array( 'deny from all' ) ) ) {
			wp_die( esc_html__( 'The upload protection rule could not be saved. Please add it manually using the instructions in Access > Other.', 'ultimate-member' ) );
		}
		delete_transient( self::LOCK );

		$this->run(); // recheck direct access to the files after writing .htaccess

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
	 * @param array|WP_Error $response HTTP response.
	 * @param string          $marker   Expected synthetic content.
	 *
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

	/**
	 * Run the upload access check, unless a recent run holds the lock.
	 *
	 * A short-lived lock prevents overlapping manual and cron probes.
	 *
	 * @return array Latest check result (fresh, or the cached result when locked).
	 */
	public function run() {
		$transient = get_transient( self::LOCK );
		if ( false !== $transient ) {
			return $this->get_result(); // Returns cached result if transient exists.
		}

		set_transient( self::LOCK, 1, 2 * MINUTE_IN_SECONDS );

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
				global $is_apache;
				// .htaccess is only honored by Apache (and LiteSpeed, which WordPress also reports as Apache); skip the read on nginx/IIS.
				if ( $is_apache ) {
					$htaccess = trailingslashit( $base ) . '.htaccess';
					if ( is_readable( $htaccess ) ) {
						$rules                   = file_get_contents( $htaccess, false, null, 0, 65536 );
						$result['htaccess_rule'] = (bool) preg_match( '/^\s*(?:deny\s+from\s+all|require\s+all\s+denied)\s*(?:#.*)?$/mi', $rules );
					}
				}
				$directories = array( '' );
				// Sample one real user directory, where rules can differ from the root.
				foreach ( new DirectoryIterator( $base ) as $entry ) {
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
		} catch ( Throwable $error ) {
			if ( 'exposed' !== $result['state'] ) {
				$result['state']  = 'unknown';
				$result['reason'] = 'check_failed';
			}
		} finally {
			update_option( self::OPTION, $result, false );
		}
		return $result;
	}
}
