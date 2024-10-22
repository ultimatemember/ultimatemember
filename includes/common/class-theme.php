<?php
namespace um\common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Theme
 *
 * @package um\common
 */
class Theme {

	public function hooks() {
		add_action( 'after_switch_theme', array( &$this, 'flush_transient_templates_data' ) );
	}

	/**
	 * Find outdated UM templates and notify Administrator.
	 *
	 */
	public function flush_transient_templates_data() {
		// Flush transient with the custom templates list.
		delete_transient( 'um_custom_templates_list' );
	}

	/**
	 * Scan the template files.
	 *
	 * @param  string $template_path Path to the template directory.
	 * @return array
	 */
	public static function scan_template_files( $template_path ) {
		$files  = @scandir( $template_path ); // @codingStandardsIgnoreLine.
		$result = array();

		if ( ! empty( $files ) ) {

			foreach ( $files as $value ) {

				if ( ! in_array( $value, array( '.', '..' ), true ) ) {

					if ( is_dir( $template_path . DIRECTORY_SEPARATOR . $value ) ) {
						$sub_files = self::scan_template_files( $template_path . DIRECTORY_SEPARATOR . $value );
						foreach ( $sub_files as $sub_file ) {
							$result[] = $value . DIRECTORY_SEPARATOR . $sub_file;
						}
					} else {
						$result[] = $value;
					}
				}
			}
		}
		return $result;
	}

	public static function get_all_templates() {
		$scan_files['um'] = self::scan_template_files( UM_PATH . '/templates/' );
		/**
		 * Filters an array of the template files for scanning versions.
		 *
		 * @since 2.6.1
		 * @hook um_override_templates_scan_files
		 *
		 * @param {array} $scan_files Template files for scanning versions.
		 *
		 * @return {array} Template files for scanning versions.
		 */
		return apply_filters( 'um_override_templates_scan_files', $scan_files );
	}

	/**
	 * @param $file string
	 *
	 * @return string
	 */
	public static function get_file_version( $file ) {
		// Avoid notices if file does not exist.
		if ( ! file_exists( $file ) ) {
			return '';
		}

		// We don't need to write to the file, so just open for reading.
		$fp = fopen( $file, 'r' ); // @codingStandardsIgnoreLine.

		// Pull only the first 8kiB of the file in.
		$file_data = fread( $fp, 8192 ); // @codingStandardsIgnoreLine.

		// PHP will close a file handle, but we are good citizens.
		fclose( $fp ); // @codingStandardsIgnoreLine.

		// Make sure we catch CR-only line endings.
		$file_data = str_replace( "\r", "\n", $file_data );
		$version   = '';

		if ( preg_match( '/^[ \t\/*#@]*' . preg_quote( '@version', '/' ) . '(.*)$/mi', $file_data, $match ) && $match[1] ) {
			$version = _cleanup_header_comment( $match[1] );
		}

		return $version;
	}

	public function get_custom_templates_list() {
		$files_in_theme = array();
		$scan_files     = self::get_all_templates();
		foreach ( $scan_files as $key => $files ) {
			foreach ( $files as $file ) {
				$file = wp_normalize_path( $file ); // for not a Linux hosting.

				if ( false === strpos( $file, 'email/' ) ) {
					/**
					 * Filters an array of the template files for scanning versions based on $key.
					 *
					 * Note: $key - means um or extension key.
					 *
					 * @since 2.6.1
					 * @hook um_override_templates_get_template_path__{$key}
					 *
					 * @param {array}  $located Template file paths for scanning versions.
					 * @param {string} $file    Template file name.
					 *
					 * @return {array} Template file paths for scanning versions.
					 */
					$located = apply_filters( "um_override_templates_get_template_path__{$key}", array(), $file );

					$exceptions = array(
						'members-grid.php',
						'members-header.php',
						'members-list.php',
						'members-pagination.php',
						'searchform.php',
						'login-to-view.php',
						'profile/comments.php',
						'profile/comments-single.php',
						'profile/posts.php',
						'profile/posts-single.php',
						'modal/upload-single.php',
						'modal/view-photo.php',
					);

					$theme_file = false;
					if ( ! empty( $located ) ) {
						$theme_file = $located['theme'];
					} elseif ( in_array( $file, $exceptions, true ) && file_exists( get_stylesheet_directory() . '/ultimate-member/' . $file ) ) {
						$theme_file = get_stylesheet_directory() . '/ultimate-member/' . $file;
					} elseif ( file_exists( get_stylesheet_directory() . '/ultimate-member/templates/' . $file ) ) {
						$theme_file = get_stylesheet_directory() . '/ultimate-member/templates/' . $file;
					}

					if ( ! empty( $theme_file ) ) {
						if ( ! empty( $located ) ) {
							$core_path = $located['core'];
						} else {
							$core_path = UM_PATH . 'templates/' . $file;
						}

						$files_in_theme[] = array(
							'core'  => $core_path,
							'theme' => $theme_file,
						);
					}
				}
			}
		}

		return $files_in_theme;
	}

	public function is_outdated_template_exist() {
		$outdated_exists = false;
		$templates       = $this->get_custom_templates_list();
		foreach ( $templates as $files ) {
			if ( ! array_key_exists( 'core', $files ) || ! array_key_exists( 'theme', $files ) ) {
				continue;
			}

			$core_path  = $files['core'];
			$theme_file = $files['theme'];

			$core_version  = self::get_file_version( $core_path );
			$theme_version = self::get_file_version( $theme_file );

			if ( '' === $theme_version || version_compare( $theme_version, $core_version, '<' ) ) {
				$outdated_exists = true;
				break;
			}
		}

		return $outdated_exists;
	}

	public function build_templates_data() {
		$templates_data = array();

		// Get from cache if isn't empty and request isn't force.
		$transient = get_transient( 'um_custom_templates_list' );
		if ( false !== $transient && array_key_exists( 'data', $transient ) ) {
			return $transient['data'];
		}

		$templates = $this->get_custom_templates_list();
		foreach ( $templates as $files ) {
			if ( ! array_key_exists( 'core', $files ) || ! array_key_exists( 'theme', $files ) ) {
				continue;
			}

			$core_path  = $files['core'];
			$theme_file = $files['theme'];

			$core_version  = self::get_file_version( $core_path );
			$theme_version = self::get_file_version( $theme_file );

			$status      = esc_html__( 'Theme version up to date', 'ultimate-member' );
			$status_code = 1;

			if ( '' === $theme_version ) {
				$status      = esc_html__( 'Theme version is empty', 'ultimate-member' );
				$status_code = 0;
			} elseif ( version_compare( $theme_version, $core_version, '<' ) ) {
				$status      = esc_html__( 'Theme version is out of date', 'ultimate-member' );
				$status_code = 0;
			}

			$templates_data[] = array(
				'core_version'  => $core_version,
				'theme_version' => $theme_version,
				'core_file'     => stristr( $core_path, 'wp-content' ),
				'theme_file'    => stristr( $theme_file, 'wp-content' ),
				'status'        => $status,
				'status_code'   => $status_code,
			);
		}

		// Cache results via transient setting.
		$transient = array(
			'data' => $templates_data,
			'time' => time(),
		);
		set_transient( 'um_custom_templates_list', $transient, 5 * MINUTE_IN_SECONDS );

		return $templates_data;
	}
}
